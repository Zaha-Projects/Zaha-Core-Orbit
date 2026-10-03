<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns;

use App\Modules\Events\Support\EventAggregateIdentity;
use App\Models\Branch;
use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Models\MonthlyPlanDeleteRequest;
use App\Modules\Events\Models\MonthlyPlanEditRequest;
use App\Models\WorkflowLog;
use App\Models\WorkflowInstance;
use App\Modules\Events\Models\EventStatusLookup;
use App\Models\WorkflowActionLog;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\WorkflowNotificationService;
use App\Services\MonthlyActivityLifecycleService;
use App\Services\DynamicWorkflowService;
use App\Services\PlanChangeRequestWorkflowService;
use Illuminate\Database\Eloquent\Builder;

trait InteractsWithMonthlyActivities
{
    /** @return array<int, string> */
    protected function monthlyActivityEditRoles(): array
    {
        return [
            'relations_manager', 'relations_officer', 'supervisor', 'relations_officer',
            'followup_officer', 'evaluation_officer', 'volunteer_coordinator',
            'branch_coordinator', 'communication_head', 'transport_officer',
            'movement_manager', 'administrative_unit_manager', 'super_admin',
        ];
    }

    protected function ownBranchId(?User $user): ?int
    {
        return filled($user?->branch_id) ? (int) $user->branch_id : null;
    }

    protected function followupOfficerBranchId(?User $user): ?int
    {
        if (! $user?->hasRole('followup_officer')) {
            return null;
        }

        $branchIds = $user->scopedBranchIds();
        abort_if(count($branchIds) !== 1, 403, __('evaluation.validation.single_branch'));

        return $branchIds[0];
    }

    /**
     * @return array<int, int>
     */
    protected function scopedBranchIds(?User $user): array
    {
        if (! $this->shouldScopeToUserBranch($user)) {
            return [];
        }

        return method_exists($user, 'scopedBranchIds')
            ? $user->scopedBranchIds()
            : (filled($user?->branch_id) ? [(int) $user->branch_id] : []);
    }

    protected function canAccessScopedBranch(?User $user, ?int $branchId): bool
    {
        $scopedBranchIds = $this->scopedBranchIds($user);

        return $scopedBranchIds === [] || in_array((int) $branchId, $scopedBranchIds, true);
    }

    protected function isApprovedVersion(MonthlyActivity $monthlyActivity): bool
    {
        $workflowInstance = WorkflowInstance::query()
            ->whereIn('entity_type', EventAggregateIdentity::acceptedTypes(MonthlyActivity::class))
            ->where('entity_id', $monthlyActivity->id)
            ->latest('id')
            ->first();

        return $workflowInstance?->status === 'approved'
            || $monthlyActivity->status === 'approved'
            || $monthlyActivity->executive_approval_status === 'approved'
            || $monthlyActivity->lifecycle_status === 'Exec Director Approved';
    }

    protected function isSupersededVersion(MonthlyActivity $monthlyActivity): bool
    {
        return $monthlyActivity->newerVersions()->exists();
    }

    protected function shouldScopeToUserBranch(?User $user): bool
    {
        return $user !== null
            && method_exists($user, 'hasBranchScopedMonthlyVisibility')
            && $user->hasBranchScopedMonthlyVisibility()
            && ! empty($user->branch_id);
    }

    protected function applyBranchVisibilityScope($query, ?User $user)
    {
        if (! $this->shouldScopeToUserBranch($user)) {
            return $query;
        }

        $ownBranchId = $this->ownBranchId($user);
        if ($ownBranchId) {
            $query->where('branch_id', $ownBranchId);
        }

        return $query;
    }

    protected function applyOtherBranchesScope($query, ?User $user)
    {
        $ownBranchId = $this->ownBranchId($user);

        if ($ownBranchId) {
            $query->where('branch_id', '!=', $ownBranchId);
        }

        return $query;
    }

    protected function canViewOtherBranches(?User $user): bool
    {
        return $user !== null
            && ($user->can('monthly_activities.view_other_branches') || $user->hasRole('super_admin'));
    }

    protected function applyDraftVisibilityScope($query, ?User $user)
    {
        return $query->where(function ($visibilityQuery) use ($user) {
            $visibilityQuery->where('status', '!=', 'draft');

            if ($user) {
                $visibilityQuery->orWhere('created_by', $user->id);
            }
        });
    }

    protected function isVolunteerCoordinatorOnly(?User $user): bool
    {
        return $user !== null
            && $user->hasRole('volunteer_coordinator')
            && ! $user->hasRole('super_admin');
    }

    protected function applyVolunteerCoordinatorVisibilityScope($query, ?User $user)
    {
        if (! $this->isVolunteerCoordinatorOnly($user)) {
            return $query;
        }

        return $query->where('needs_volunteers', true);
    }

    protected function canCompleteAfterExecution(MonthlyActivity $monthlyActivity, ?User $user): bool
    {
        if ($user === null || ! $this->canUseMonthlyActivityEditRoute($user) || in_array((string) $monthlyActivity->status, ['post_execution_submitted', 'closed'], true)) {
            return false;
        }

        if ((int) $monthlyActivity->created_by === (int) $user->id) {
            return true;
        }

        $reviewDecision = (string) data_get($monthlyActivity->post_execution_payload ?? [], 'review.decision');

        return $user->hasRole('volunteer_coordinator')
            && in_array((string) $monthlyActivity->status, ['changes_requested', 'rejected'], true)
            && in_array($reviewDecision, ['clarification', 'rejected'], true)
            && (
                (int) ($user->branch_id ?? 0) === (int) $monthlyActivity->branch_id
                || $user->assignedBranches()->whereKey((int) $monthlyActivity->branch_id)->exists()
            );
    }

    protected function canReviewPostExecution(MonthlyActivity $monthlyActivity, ?User $user): bool
    {
        if ($user === null || (string) $monthlyActivity->status !== 'post_execution_submitted') {
            return false;
        }

        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $user->hasRole('supervisor')) {
            return false;
        }

        $branchId = (int) $monthlyActivity->branch_id;

        return (int) ($user->branch_id ?? 0) === $branchId
            || $user->assignedBranches()->whereKey($branchId)->exists();
    }

    protected function canSubmitActivityForApproval(MonthlyActivity $monthlyActivity, ?User $user): bool
    {
        return $user !== null
            && $this->canUseMonthlyActivityEditRoute($user)
            && ! $this->isReadOnlyUnifiedAgendaActivity($monthlyActivity)
            && ! $this->isSupersededVersion($monthlyActivity)
            && in_array((string) $monthlyActivity->status, ['draft', 'changes_requested'], true);
    }

    protected function canUseMonthlyActivityEditRoute(?User $user): bool
    {
        return $user !== null
            && $user->hasAnyRole($this->monthlyActivityEditRoles());
    }

    protected function monthlyActivityChangeRequestRoles(): array
    {
        return array_values(array_filter((array) config('monthly_activity.change_requests.allowed_roles', ['relations_officer'])));
    }

    protected function canManageMonthlyActivityChangeRequest(?User $user, ?MonthlyActivity $monthlyActivity = null): bool
    {
        if ($user === null || ! $user->hasAnyRole($this->monthlyActivityChangeRequestRoles())) {
            return false;
        }

        if ($monthlyActivity === null || blank($monthlyActivity->branch_id)) {
            return true;
        }

        if ((int) ($user->branch_id ?? 0) === (int) $monthlyActivity->branch_id) {
            return true;
        }

        return method_exists($user, 'assignedBranches')
            && $user->assignedBranches()->whereKey((int) $monthlyActivity->branch_id)->exists();
    }

    protected function creatorIsPrimaryBranchRelationsOfficer(MonthlyActivity $monthlyActivity): bool
    {
        $creator = $monthlyActivity->relationLoaded('creator')
            ? $monthlyActivity->creator
            : $monthlyActivity->creator()->first();

        return (bool) $creator?->hasRole('relations_officer')
            && (bool) optional($creator->branch)->is_main;
    }

    protected function executionNeedDecisionRoles(MonthlyActivity $monthlyActivity, string $needKey): array
    {
        $roles = (array) data_get(config('execution_needs.decision_matrix', []), $needKey.'.roles', []);

        if ($this->creatorIsPrimaryBranchRelationsOfficer($monthlyActivity)) {
            $roles = collect($roles)
                ->map(fn (string $role): string => in_array($role, ['branch_coordinator', 'supervisor'], true)
                    ? 'relations_manager'
                    : $role)
                ->unique()
                ->values()
                ->all();
        }

        return $roles;
    }

    protected function executionNeedDecisionKeysForUser(MonthlyActivity $monthlyActivity, ?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->hasRole('super_admin')) {
            return array_keys($monthlyActivity->enabledExecutionNeeds());
        }

        return collect($monthlyActivity->enabledExecutionNeeds())
            ->filter(function (array $definition, string $needKey) use ($monthlyActivity, $user): bool {
                $roles = $this->executionNeedDecisionRoles($monthlyActivity, $needKey);

                if ($roles === [] || ! $user->hasAnyRole($roles)) {
                    return false;
                }

                if ($user->hasAnyRole(['branch_coordinator', 'supervisor']) && ! $user->can('branches.view.all')) {
                    return $user->hasAccessToScopedBranch((int) $monthlyActivity->branch_id);
                }

                return true;
            })
            ->keys()
            ->values()
            ->all();
    }

    protected function canDecideAnyExecutionNeed(MonthlyActivity $monthlyActivity, ?User $user): bool
    {
        return $this->executionNeedDecisionKeysForUser($monthlyActivity, $user) !== [];
    }

    protected function ensureActivityVisibleToUser(MonthlyActivity $monthlyActivity, User $user): void
    {
        $canDecideExecutionNeed = $this->canDecideAnyExecutionNeed($monthlyActivity, $user);
        $canViewOtherBranches = $this->canViewOtherBranches($user)
            && (int) ($monthlyActivity->branch_id ?? 0) !== (int) ($this->ownBranchId($user) ?? 0)
            && ((string) $monthlyActivity->status === 'approved'
                || (string) $monthlyActivity->executive_approval_status === 'approved'
                || in_array((string) $monthlyActivity->lifecycle_status, ['Exec Director Approved', 'Approved', 'Published'], true)
                || $monthlyActivity->workflowInstance()?->where('status', 'approved')->exists());

        if (! $canDecideExecutionNeed && ! $canViewOtherBranches && ! $this->canAccessScopedBranch($user, $monthlyActivity->branch_id)) {
            abort(403);
        }

        if ((string) $monthlyActivity->status === 'draft' && (int) $monthlyActivity->created_by !== (int) $user->id && ! $canDecideExecutionNeed) {
            abort(403);
        }

        if ($this->isVolunteerCoordinatorOnly($user) && ! $this->activityNeedsVolunteers($monthlyActivity) && ! $canDecideExecutionNeed) {
            abort(403);
        }
    }

    protected function activityNeedsVolunteers(MonthlyActivity $monthlyActivity): bool
    {
        return (bool) $monthlyActivity->needs_volunteers;
    }

    protected function logWorkflowAction(string $actionType, MonthlyActivity $monthlyActivity, Request $request, ?string $status = null, ?array $meta = null): void
    {
        WorkflowActionLog::create([
            'module' => 'monthly_activities',
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $monthlyActivity->id,
            'action_type' => $actionType,
            'status' => $status,
            'performed_by' => $request->user()->id,
            'meta' => $meta,
            'performed_at' => now(),
        ]);
    }

    protected function evaluationSummaryRules(): array
    {
        return [
            'evaluation_score' => ['nullable', 'numeric', 'between:0,100'],
            'evaluation_reason' => ['nullable', 'string', 'max:5000'],
        ];
    }

    protected function statusLookupOptions(string $module, array $allowedCodes = [], ?string $currentCode = null)
    {
        return EventStatusLookup::query()
            ->forModule($module)
            ->when($allowedCodes !== [], fn ($query) => $query->whereIn('code', $allowedCodes))
            ->availableForSelection($currentCode)
            ->ordered()
            ->get()
            ->unique('code')
            ->values();
    }

    protected function executionStatusLabels(): array
    {
        return [
            'planned' => 'بانتظار التنفيذ',
            'executed' => 'منفذة',
            'postponed' => 'مؤجلة',
            'cancelled' => 'ملغية',
        ];
    }

    protected function normalizeExecutionNeedsFollowup(array &$data, ?MonthlyActivity $monthlyActivity = null): void
    {
        $rows = $data['execution_needs_followup'] ?? null;
        if (! is_array($rows)) {
            $data['execution_needs_followup'] = null;
            return;
        }

        $normalized = collect($rows)
            ->map(function ($row, $key) use ($monthlyActivity) {
                if (! is_array($row)) {
                    return null;
                }

                $status = in_array((string) ($row['status'] ?? ''), ['secured', 'not_secured'], true)
                    ? (string) $row['status']
                    : null;
                $reason = trim((string) ($row['reason'] ?? ($row['notes'] ?? '')));
                $score = $row['effectiveness_score'] ?? null;
                $score = $score === '' || $score === null ? null : (int) $score;
                $evaluationScore = $row['evaluation_score'] ?? null;
                $evaluationScore = $evaluationScore === '' || $evaluationScore === null ? null : (float) $evaluationScore;
                $evaluationReason = trim((string) ($row['evaluation_reason'] ?? ''));
                $postStatus = in_array((string) ($row['post_status'] ?? ''), ['provided', 'not_provided'], true)
                    ? (string) $row['post_status']
                    : null;
                $postFeedback = trim((string) ($row['post_feedback'] ?? ''));
                $decisionByRole = trim((string) ($row['decision_by_role'] ?? ''));
                $decisionByName = trim((string) ($row['decision_by_name'] ?? ''));
                $allowedRoles = $monthlyActivity
                    ? $this->executionNeedDecisionRoles($monthlyActivity, (string) $key)
                    : (array) data_get(config('execution_needs.decision_matrix', []), (string) $key.'.roles', []);
                $currentUser = auth()->user();
                if ($decisionByRole === '' && $currentUser) {
                    $decisionByRole = collect($allowedRoles)->first(fn (string $role) => $currentUser->hasRole($role)) ?? '';
                }
                if ($decisionByName === '' && $currentUser) {
                    $decisionByName = (string) ($currentUser->name ?? '');
                }
                if ($decisionByRole !== '' && $allowedRoles !== [] && ! in_array($decisionByRole, $allowedRoles, true)) {
                    $decisionByRole = null;
                }

                if (! $status && $reason === '' && $score === null && $evaluationScore === null && $evaluationReason === '' && ! $postStatus && $postFeedback === '' && $decisionByRole === '' && $decisionByName === '') {
                    return null;
                }

                return [
                    'key' => (string) $key,
                    'status' => $status,
                    'reason' => $reason !== '' ? $reason : null,
                    'notes' => $reason !== '' ? $reason : null,
                    'effectiveness_score' => $score,
                    'evaluation_score' => $evaluationScore,
                    'evaluation_reason' => $evaluationReason !== '' ? $evaluationReason : null,
                    'post_status' => $postStatus,
                    'post_feedback' => $postFeedback !== '' ? $postFeedback : null,
                    'decision_by_role' => $decisionByRole !== '' ? $decisionByRole : null,
                    'decision_by_name' => $decisionByName !== '' ? $decisionByName : null,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $data['execution_needs_followup'] = $normalized === [] ? null : $normalized;
    }

    protected function filterExecutionNeedsFollowupToEnabled(MonthlyActivity $monthlyActivity, array &$data): void
    {
        if (empty($data['execution_needs_followup'])) {
            return;
        }

        $enabledKeys = array_keys($monthlyActivity->enabledExecutionNeeds());

        $data['execution_needs_followup'] = collect($data['execution_needs_followup'])
            ->filter(fn (array $row) => in_array((string) ($row['key'] ?? ''), $enabledKeys, true))
            ->values()
            ->all();
    }

    protected function mergeExecutionNeedsFollowupRows(MonthlyActivity $monthlyActivity, array $incomingRows): array
    {
        $incomingByKey = collect($incomingRows)
            ->filter(fn (array $row) => filled($row['key'] ?? null))
            ->keyBy(fn (array $row) => (string) $row['key']);

        $existingByKey = collect($monthlyActivity->execution_needs_followup ?? [])
            ->filter(fn ($row) => is_array($row) && filled($row['key'] ?? null))
            ->keyBy(fn (array $row) => (string) $row['key']);

        foreach ($incomingByKey as $key => $row) {
            $safeRow = collect($row)
                ->reject(fn ($value, string $field): bool => in_array($field, ['decision_by_role', 'decision_by_name'], true)
                    || ($value === null && ! in_array($field, ['post_status', 'post_feedback'], true)))
                ->all();

            $existingByKey->put($key, array_merge($existingByKey->get($key, []), $safeRow));
        }

        return $existingByKey->values()->all();
    }

    protected function submitActivityForApproval(
        MonthlyActivity $monthlyActivity,
        User $actor,
        WorkflowNotificationService $workflowNotifications,
        MonthlyActivityLifecycleService $lifecycle,
        DynamicWorkflowService $dynamicWorkflowService,
        ?Request $request = null
    ): void {
        $instance = $dynamicWorkflowService->forModel('monthly_activities', $monthlyActivity);
        abort_unless($instance !== null, 422, __('app.roles.programs.monthly_activities.approvals.errors.no_active_workflow'));
        abort_unless($this->canSubmitActivityForApproval($monthlyActivity, $actor), 422, 'تم إرسال هذا النشاط للاعتماد مسبقًا أو أن حالته الحالية لا تسمح بإعادة الإرسال.');

        $currentStep = $dynamicWorkflowService->currentStep($instance);

        if ($instance->status === 'changes_requested' && $currentStep?->step_type !== 'sub') {
            $dynamicWorkflowService->markResubmitted($instance);
            $instance = $instance->fresh();
            $currentStep = $dynamicWorkflowService->currentStep($instance);
        }

        if ($currentStep?->step_type === 'sub') {
            WorkflowLog::query()->create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $currentStep->id,
                'acted_by' => $actor->id,
                'action' => 'approved',
                'comment' => null,
                'edit_request_iteration' => (int) $instance->edit_request_count,
                'acted_at' => now(),
            ]);

            $dynamicWorkflowService->advanceToNextStep($instance->fresh());
            $instance = $instance->fresh();
        }

        $monthlyActivity->update([
            'status' => 'submitted',
        ]);

        if (($monthlyActivity->lifecycle_status ?: 'Draft') !== 'Submitted') {
            $lifecycle->transitionOrFail($monthlyActivity, 'Submitted');
        }

        $workflowNotifications->approvalRequested($instance, $monthlyActivity, route('role.programs.approvals.index'), $actor);

        if ($request && $request->user()) {
            $this->logWorkflowAction('submitted', $monthlyActivity, $request, 'submitted');
        }
    }

    protected function normalizeMonthlyIndexYear(mixed $year): int
    {
        $normalizedYear = filter_var($year, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 2000, 'max_range' => 2100],
        ]);

        return $normalizedYear ?: now()->year;
    }

    protected function normalizeMonthlyIndexMonth(mixed $month): int
    {
        $normalizedMonth = filter_var($month, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 12],
        ]);

        return $normalizedMonth ?: now()->month;
    }

    protected function applyMonthlyPageMonthFilter(Builder $query, int $year, int $month): void
    {
        $query->where(function (Builder $dateQuery) use ($year, $month): void {
            $dateQuery
                ->where(function (Builder $proposedDateQuery) use ($year, $month): void {
                    $proposedDateQuery
                        ->whereNotNull('proposed_date')
                        ->whereYear('proposed_date', $year)
                        ->whereMonth('proposed_date', $month);
                })
                ->orWhere(function (Builder $fallbackMonthQuery) use ($month): void {
                    $fallbackMonthQuery
                        ->whereNull('proposed_date')
                        ->where('month', $month);
                });
        });
    }

    protected function applyMonthlyPageStatusFilter($query, ?string $status): void
    {
        $status = trim((string) $status);

        if ($status === '') {
            return;
        }

        $query->where(function ($statusQuery) use ($status) {
            match ($status) {
                'draft' => $statusQuery->where('status', 'draft'),
                'approved' => $statusQuery->whereIn('status', ['approved']),
                'submitted' => $statusQuery->whereIn('status', ['submitted', 'pending', 'in_review', 'changes_requested', 'rejected', 'postponed', 'cancelled', 'closed', 'completed', 'executed']),
                default => $statusQuery->where('status', $status),
            };
        });
    }

    protected function hasManagerOrLaterApproval(MonthlyActivity $monthlyActivity): bool
    {
        return in_array((string) $monthlyActivity->relations_manager_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->programs_manager_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->liaison_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->hq_relations_manager_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->executive_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->lifecycle_status, ['Branch Relations Manager Approved', 'Primary Relations Manager Approved', 'Executive Manager Approved', 'Exec Director Approved'], true)
            || $this->isApprovedVersion($monthlyActivity);
    }

    protected function isReadOnlyUnifiedAgendaActivity(MonthlyActivity $monthlyActivity): bool
    {
        $monthlyActivity->loadMissing('agendaEvent');

        return (bool) $monthlyActivity->is_from_agenda
            && (string) $monthlyActivity->plan_type === 'unified'
            && (string) optional($monthlyActivity->agendaEvent)->event_type === 'mandatory';
    }

    protected function canBranchEditUnifiedNonCoreFields(MonthlyActivity $monthlyActivity, ?User $user): bool
    {
        return $this->isReadOnlyUnifiedAgendaActivity($monthlyActivity)
            && (bool) config('monthly_activity.unified_branch_edit.enabled', true)
            && $this->shouldScopeToUserBranch($user)
            && ! $user?->hasRole('super_admin');
    }

    /**
     * @return array<int, string>
     */

    /**
     * Relations required to render the same approval path on the show and edit-mirror pages.
     *
     * @return array<int, string>
     */
    protected function monthlyActivityWorkflowViewRelations(): array
    {
        return [
            'branch',
            'creator',
            'agendaEvent',
            'sponsors',
            'partners',
            'supplies',
            'team',
            'targetGroups',
            'workflowInstance.workflow.steps.role',
            'workflowInstance.workflow.steps.permission',
            'workflowInstance.currentStep.role',
            'workflowInstance.currentStep.permission',
            'workflowInstance.logs.step.role',
            'workflowInstance.logs.step.permission',
            'workflowInstance.logs.actor',
        ];
    }

    /**
     * @return array{activeDeleteRequest: ?MonthlyPlanDeleteRequest, activeEditRequest: ?MonthlyPlanEditRequest, hasActiveChangeRequest: bool}
     */
    protected function activeMonthlyChangeRequestViewData(MonthlyActivity $monthlyActivity, PlanChangeRequestWorkflowService $changeRequests): array
    {
        $active = $changeRequests->activeMonthlyChangeRequests($monthlyActivity);

        foreach (['delete', 'edit'] as $key) {
            $request = $active[$key] ?? null;
            if (! $request) {
                continue;
            }

            $instance = $changeRequests->normalizeRequesterSubmission($request, 'monthly_activities');
            $request->setRelation('workflowInstance', $instance);
            $request->load('requester', 'currentApprover');
            $request->workflow_timeline = $changeRequests->workflowTimelineForRequest($request, $instance);
        }

        return [
            'activeDeleteRequest' => $active['delete'] ?? null,
            'activeEditRequest' => $active['edit'] ?? null,
            'hasActiveChangeRequest' => (bool) (($active['delete'] ?? null) || ($active['edit'] ?? null)),
        ];
    }

}
