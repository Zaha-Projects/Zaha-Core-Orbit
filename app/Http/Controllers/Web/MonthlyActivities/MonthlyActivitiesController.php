<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\MonthlyActivityChangeLog;
use App\Models\MonthlyActivityPartner;
use App\Models\MonthlyActivitySponsor;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivitySupply;
use App\Models\MonthlyActivityTeam;
use App\Models\WorkflowLog;
use App\Models\WorkflowInstance;
use App\Models\EvaluationQuestion;
use App\Models\EventStatusLookup;
use App\Models\MonthlyActivityFollowup;
use App\Models\MonthlyActivityEvaluationResponse;
use App\Models\TargetGroup;
use App\Models\Setting;
use App\Models\WorkflowActionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\ConflictDetectionService;
use App\Services\NotificationService;
use App\Services\MonthlyActivityWorkflowService;
use App\Services\MonthlyActivityLifecycleService;
use App\Services\DynamicWorkflowService;
use App\Services\MonthlyWorkflowPresenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MonthlyActivitiesController extends Controller
{
    protected function currentUserBranchId(?User $user): ?int
    {
        $branchIds = $this->scopedBranchIds($user);

        return count($branchIds) === 1 ? $branchIds[0] : null;
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
            ->where('entity_type', MonthlyActivity::class)
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

    protected function syncTargetGroups(MonthlyActivity $monthlyActivity, array $data): void
    {
        $ids = collect($data['target_group_ids'] ?? [])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $monthlyActivity->targetGroups()->sync(collect($ids)->mapWithKeys(fn ($id) => [$id => ['custom_text' => null]])->all());
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
        $scopedBranchIds = $this->scopedBranchIds($user);

        if ($scopedBranchIds !== []) {
            $query->whereIn('branch_id', $scopedBranchIds);
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

    protected function ensureActivityVisibleToUser(MonthlyActivity $monthlyActivity, User $user): void
    {
        if (! $this->canAccessScopedBranch($user, $monthlyActivity->branch_id)) {
            abort(403);
        }

        if ((string) $monthlyActivity->status === 'draft' && (int) $monthlyActivity->created_by !== (int) $user->id) {
            abort(403);
        }
    }

    protected function monthlyLockDays(): int
    {
        return max(0, (int) Setting::valueOf('monthly_plan_lock_days', '5'));
    }

    protected function monthlyIndexPerPage(): int
    {
        return max(1, min(50, (int) Setting::valueOf('monthly_activities_index_per_page', '10')));
    }

    protected function buildLockAt(string $proposedDate): ?Carbon
    {
        return Carbon::parse($proposedDate)->subDays($this->monthlyLockDays())->endOfDay();
    }

    protected function isLocked(MonthlyActivity $monthlyActivity): bool
    {
        return $monthlyActivity->lock_at !== null && now()->greaterThanOrEqualTo($monthlyActivity->lock_at);
    }

    protected function safeExternalUrlRules(): array
    {
        return [
            'nullable',
            'url',
            'max:500',
            function (string $attribute, mixed $value, \Closure $fail) {
                if (! filled($value)) {
                    return;
                }

                $url = trim((string) $value);
                $parts = parse_url($url);
                $scheme = strtolower((string) ($parts['scheme'] ?? ''));
                $host = strtolower((string) ($parts['host'] ?? ''));

                if (! in_array($scheme, ['http', 'https'], true)) {
                    $fail('صيغة الرابط غير آمنة.');
                    return;
                }

                $allowedHosts = ['google.com', 'drive.google.com'];
                $isAllowed = collect($allowedHosts)->contains(fn (string $allowed) => $host === $allowed || Str::endsWith($host, '.'.$allowed));
                if (! $isAllowed) {
                    $fail('الرابط يجب أن يكون ضمن النطاقات الموثوقة.');
                }
            },
        ];
    }

    protected function logChanges(MonthlyActivity $monthlyActivity, array $oldValues, array $newValues, int $userId): void
    {
        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;
            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            MonthlyActivityChangeLog::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'changed_by' => $userId,
                'field_name' => $field,
                'old_value' => $oldValue !== null ? (string) $oldValue : null,
                'new_value' => $newValue !== null ? (string) $newValue : null,
                'changed_at' => now(),
            ]);
        }
    }

    protected function syncSponsorsAndPartners(MonthlyActivity $monthlyActivity, array $data): void
    {
        $monthlyActivity->sponsors()->delete();
        foreach (($data['sponsors'] ?? []) as $sponsor) {
            $name = trim((string) ($sponsor['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            MonthlyActivitySponsor::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'name' => $name,
                'title' => $sponsor['title'] ?? null,
                'is_official' => (bool) ($sponsor['is_official'] ?? true),
            ]);
        }

        $monthlyActivity->partners()->delete();
        $seen = [];
        foreach (($data['partners'] ?? []) as $index => $partner) {
            $name = trim((string) ($partner['name'] ?? ''));
            if ($name === '' || in_array(mb_strtolower($name), $seen, true)) {
                continue;
            }

            $seen[] = mb_strtolower($name);

            MonthlyActivityPartner::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'name' => $name,
                'role' => $partner['role'] ?? null,
                'contact_info' => $partner['contact_info'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
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


    protected function syncEvaluationData(MonthlyActivity $monthlyActivity, array $data, int $userId): void
    {
        $monthlyActivity->evaluationResponses()->delete();
        foreach (($data['evaluations'] ?? []) as $questionId => $payload) {
            if (empty($payload['score']) && empty($payload['answer_value']) && empty($payload['note'])) {
                continue;
            }

            MonthlyActivityEvaluationResponse::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'evaluation_question_id' => (int) $questionId,
                'score' => $payload['score'] ?? null,
                'answer_value' => $payload['answer_value'] ?? null,
                'note' => $payload['note'] ?? null,
                'created_by' => $userId,
            ]);
        }

        if (! empty($data['followup_remarks'])) {
            MonthlyActivityFollowup::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'remarks' => $data['followup_remarks'],
                'created_by' => $userId,
            ]);
        }
    }

    protected function canSubmitPostEvaluation(MonthlyActivity $monthlyActivity): bool
    {
        return in_array($monthlyActivity->status, ['executed', 'completed', 'closed'], true)
            || ! empty($monthlyActivity->actual_date)
            || in_array((string) $monthlyActivity->lifecycle_status, ['Executed', 'Evaluated', 'Closed'], true);
    }

    protected function statusLookupOptions(string $module, array $allowedCodes = [], ?string $currentCode = null)
    {
        return EventStatusLookup::query()
            ->forModule($module)
            ->when($allowedCodes !== [], fn ($query) => $query->whereIn('code', $allowedCodes))
            ->where(function ($query) use ($currentCode) {
                $query->where('is_active', true);

                if (filled($currentCode)) {
                    $query->orWhere('code', $currentCode);
                }
            })
            ->ordered()
            ->get()
            ->unique('code')
            ->values();
    }

    protected function monthlyPlanningStatusOptions(?string $currentCode = null)
    {
        return $this->statusLookupOptions('monthly_activities', [
            'draft',
            'submitted',
            'changes_requested',
            'postponed',
            'cancelled',
            'closed',
        ], $currentCode);
    }

    protected function monthlyCreationStatusOptions(?string $currentCode = null)
    {
        return $this->statusLookupOptions('monthly_activities', [
            'draft',
            'submitted',
            'postponed',
            'cancelled',
        ], $currentCode);
    }

    protected function monthlyCloseStatusOptions(?string $currentCode = null)
    {
        return $this->statusLookupOptions('monthly_activities', [
            'closed',
            'completed',
            'executed',
        ], $currentCode);
    }

    protected function executionStatusLabels(): array
    {
        return [
            'executed' => 'منفذة',
            'postponed' => 'مؤجلة',
            'cancelled' => 'ملغية',
        ];
    }

    protected function agendaEventsForUser(?User $user, ?MonthlyActivity $monthlyActivity = null)
    {
        $selectedEventId = $monthlyActivity?->agenda_event_id;
        $scopedBranchIds = $this->scopedBranchIds($user);

        return AgendaEvent::query()
            ->when($scopedBranchIds !== [], function ($query) use ($scopedBranchIds, $selectedEventId) {
                $query->where(function ($scopedQuery) use ($scopedBranchIds, $selectedEventId) {
                    $scopedQuery->where('event_type', 'mandatory')
                        ->orWhereHas('participations', function ($participationQuery) use ($scopedBranchIds) {
                            $participationQuery
                                ->where('entity_type', 'branch')
                                ->whereIn('entity_id', $scopedBranchIds)
                                ->where('participation_status', 'participant');
                        });

                    if ($selectedEventId) {
                        $scopedQuery->orWhere($scopedQuery->getModel()->getQualifiedKeyName(), $selectedEventId);
                    }
                });
            })
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }

    protected function normalizePlanningPayload(array &$data): void
    {
        $data['execution_status'] = $data['execution_status'] ?? 'executed';
        $data['status'] = $data['status'] ?? 'draft';

        if (($data['location_type'] ?? null) === 'inside_center') {
            $data['outside_place_name'] = null;
            $data['outside_google_maps_url'] = null;
            $data['outside_contact_number'] = null;
            $data['external_liaison_name'] = null;
            $data['external_liaison_phone'] = null;
            $data['outside_address'] = null;
        } else {
            $data['internal_location'] = null;
        }

        if (! (bool) ($data['needs_official_correspondence'] ?? false)) {
            $data['official_correspondence_reason'] = null;
            $data['official_correspondence_target'] = null;
            $data['official_correspondence_brief'] = null;
        }

        if (! (bool) ($data['needs_volunteers'] ?? false)) {
            $data['required_volunteers'] = null;
            $data['volunteer_need'] = null;
            $data['volunteer_age_range'] = null;
            $data['volunteer_gender'] = null;
            $data['volunteer_tasks_summary'] = null;
        }

        if (($data['execution_status'] ?? 'executed') !== 'postponed') {
            $data['rescheduled_date'] = null;
            $data['reschedule_reason'] = null;
        }

        if (($data['execution_status'] ?? 'executed') !== 'cancelled') {
            $data['cancellation_reason'] = null;
        }

        if (! (bool) ($data['requires_supplies'] ?? false)) {
            $data['supplies'] = [];
        }

        if (! (bool) ($data['has_partners'] ?? false)) {
            $data['partners'] = [];
        }

        if (! (bool) ($data['has_sponsor'] ?? false)) {
            $data['sponsors'] = [];
        }
    }

    protected function shouldSubmitFromRequest(Request $request): bool
    {
        return $request->input('submit_action') === 'submit';
    }

    protected function submitActivityForApproval(
        MonthlyActivity $monthlyActivity,
        User $actor,
        NotificationService $notifications,
        MonthlyActivityLifecycleService $lifecycle,
        DynamicWorkflowService $dynamicWorkflowService,
        ?Request $request = null
    ): void {
        $instance = $dynamicWorkflowService->forModel('monthly_activities', $monthlyActivity);
        abort_unless($instance !== null, 422, __('app.roles.programs.monthly_activities.approvals.errors.no_active_workflow'));

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

        $nextRecipients = $dynamicWorkflowService->eligibleUsersForStep($instance);
        $notifications->notifyUsers(
            $nextRecipients,
            'approval_requested',
            __('app.roles.programs.monthly_activities.approvals.notifications.submit_title'),
            __('app.roles.programs.monthly_activities.approvals.notifications.submit_body', ['activity' => $monthlyActivity->title]),
            route('role.programs.approvals.index')
        );

        if ($request && $request->user()) {
            $this->logWorkflowAction('submitted', $monthlyActivity, $request, 'submitted');
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $viewScope = $request->input('scope', 'default');

        if ($viewScope === 'all_branches' && ! $this->canViewOtherBranches($user)) {
            abort(403);
        }

        $activitiesQuery = MonthlyActivity::with([
            'branch',
            'agendaEvent',
            'creator',
            'workflowInstance.workflow.steps.role',
            'workflowInstance.logs.step',
        ])
            ->withCount('newerVersions')
            ->whereDoesntHave('newerVersions')
            ->enterpriseFilter($request->all())
            ->notArchived();

        if ($viewScope !== 'all_branches') {
            $this->applyBranchVisibilityScope($activitiesQuery, $user);
        }
        $this->applyDraftVisibilityScope($activitiesQuery, $user);

        if ($viewScope === 'all_branches') {
            $activitiesQuery
                ->where('status', 'approved')
                ->where(function ($query) {
                    $query->where('executive_approval_status', 'approved')
                        ->orWhereIn('lifecycle_status', ['Exec Director Approved', 'Approved', 'Published'])
                        ->orWhereHas('workflowInstance', fn ($workflowQuery) => $workflowQuery->where('status', 'approved'));
                });
        }

        $activities = $activitiesQuery
            ->orderBy('month')
            ->orderBy('day')
            ->paginate($this->monthlyIndexPerPage())
            ->withQueryString();

        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $this->scopedBranchIds($user);
        if ($scopedBranchIds !== [] && $viewScope !== 'all_branches') {
            $branches->whereIn('id', $scopedBranchIds);
        }
        $branches = $branches->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();
        $filters = [
            'year' => $request->input('year'),
            'month' => $request->input('month'),
            'status' => $request->input('status'),
            'branch_id' => $request->input('branch_id'),
        ];
        $canFilterBranches = $viewScope === 'all_branches'
            ? $this->canViewOtherBranches($user)
            : ($scopedBranchIds === [] || count($scopedBranchIds) > 1);

        $monthlyStatusOptions = $this->statusLookupOptions('monthly_activities', [], (string) $request->input('status'));

        return view('pages.monthly_activities.activities.index', compact(
            'activities',
            'branches',
            'agendaEvents',
            'filters',
            'canFilterBranches',
            'viewScope',
            'monthlyStatusOptions',
        ));
    }

    public function create()
    {
        $user = request()->user();
        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $this->scopedBranchIds($user);
        if ($scopedBranchIds !== []) {
            $branches->whereIn('id', $scopedBranchIds);
        }
        $branches = $branches->get();
        $agendaEvents = $this->agendaEventsForUser($user);
        $targetGroups = TargetGroup::where('is_active', true)->orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::where('is_active', true)->orderBy('sort_order')->get();
        $monthlyStatusOptions = $this->monthlyCreationStatusOptions('draft');
        $executionStatusLabels = $this->executionStatusLabels();

        return view('pages.monthly_activities.activities.create', compact(
            'branches',
            'agendaEvents',
            'targetGroups',
            'evaluationQuestions',
            'monthlyStatusOptions',
            'executionStatusLabels',
        ));
    }

    protected function flashFormPrefill(MonthlyActivity $monthlyActivity): void
    {
        if (session()->hasOldInput()) {
            return;
        }

        $monthlyActivity->loadMissing(['sponsors', 'partners', 'supplies', 'targetGroups']);

        $needsVolunteers = (bool) $monthlyActivity->needs_volunteers;
        $needsOfficialCorrespondence = (bool) $monthlyActivity->needs_official_correspondence;
        $outsideCenter = $monthlyActivity->location_type === 'outside_center';
        $needsSupplies = $monthlyActivity->supplies->isNotEmpty();

        $prefill = array_merge($monthlyActivity->getAttributes(), [
            'title' => $monthlyActivity->title,
            'activity_date' => optional($monthlyActivity->activity_date)->toDateString() ?: optional($monthlyActivity->proposed_date)->toDateString(),
            'proposed_date' => optional($monthlyActivity->proposed_date)->toDateString(),
            'branch_id' => $monthlyActivity->branch_id,
            'agenda_event_id' => $monthlyActivity->agenda_event_id,
            'is_in_agenda' => (int) $monthlyActivity->is_in_agenda,
            'status' => $monthlyActivity->status,
            'execution_status' => $monthlyActivity->execution_status ?: 'executed',
            'location_type' => $monthlyActivity->location_type,
            'internal_location' => $outsideCenter ? null : $monthlyActivity->internal_location,
            'outside_place_name' => $outsideCenter ? $monthlyActivity->outside_place_name : null,
            'outside_google_maps_url' => $outsideCenter ? $monthlyActivity->outside_google_maps_url : null,
            'outside_contact_number' => $outsideCenter ? $monthlyActivity->outside_contact_number : null,
            'external_liaison_name' => $outsideCenter ? $monthlyActivity->external_liaison_name : null,
            'external_liaison_phone' => $outsideCenter ? $monthlyActivity->external_liaison_phone : null,
            'outside_address' => $outsideCenter ? $monthlyActivity->outside_address : null,
            'time_from' => optional($monthlyActivity->time_from)->format('H:i'),
            'time_to' => optional($monthlyActivity->time_to)->format('H:i'),
            'short_description' => $monthlyActivity->short_description,
            'description' => $monthlyActivity->description,
            'needs_volunteers' => (int) $needsVolunteers,
            'required_volunteers' => $needsVolunteers ? $monthlyActivity->required_volunteers : null,
            'volunteer_need' => $needsVolunteers ? $monthlyActivity->volunteer_need : null,
            'volunteer_age_range' => $needsVolunteers ? $monthlyActivity->volunteer_age_range : null,
            'volunteer_gender' => $needsVolunteers ? $monthlyActivity->volunteer_gender : null,
            'volunteer_tasks_summary' => $needsVolunteers ? $monthlyActivity->volunteer_tasks_summary : null,
            'needs_official_correspondence' => (int) $needsOfficialCorrespondence,
            'official_correspondence_reason' => $needsOfficialCorrespondence ? $monthlyActivity->official_correspondence_reason : null,
            'official_correspondence_target' => $needsOfficialCorrespondence ? $monthlyActivity->official_correspondence_target : null,
            'official_correspondence_brief' => $needsOfficialCorrespondence ? $monthlyActivity->official_correspondence_brief : null,
            'rescheduled_date' => optional($monthlyActivity->rescheduled_date)->toDateString(),
            'reschedule_reason' => $monthlyActivity->reschedule_reason,
            'cancellation_reason' => $monthlyActivity->cancellation_reason,
            'requires_supplies' => (int) $needsSupplies,
            'supplies' => $needsSupplies
                ? $monthlyActivity->supplies->map(fn ($supply) => [
                    'item_name' => $supply->item_name,
                    'available' => (int) $supply->available,
                    'provider_type' => $supply->provider_type,
                    'provider_name' => $supply->provider_name,
                ])->values()->all()
                : [],
            'has_sponsor' => (int) $monthlyActivity->has_sponsor,
            'sponsors' => $monthlyActivity->sponsors->map(fn ($sponsor) => [
                'name' => $sponsor->name,
                'title' => $sponsor->title,
            ])->values()->all(),
            'has_partners' => (int) $monthlyActivity->has_partners,
            'partners' => $monthlyActivity->partners->map(fn ($partner) => [
                'name' => $partner->name,
                'role' => $partner->role,
            ])->values()->all(),
            'target_group_ids' => $monthlyActivity->targetGroups->pluck('id')->all(),
            'target_group_other' => $monthlyActivity->target_group_other,
            'planning_attachment' => $monthlyActivity->planning_attachment,
        ]);

        session()->flash('_old_input', $prefill);
    }

    protected function normalizeComparableValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    protected function meaningfulChangedFields(array $oldValues, array $newValues): array
    {
        return collect($newValues)
            ->filter(function ($newValue, string $field) use ($oldValues) {
                return $this->normalizeComparableValue($oldValues[$field] ?? null) !== $this->normalizeComparableValue($newValue);
            })
            ->keys()
            ->values()
            ->all();
    }

    protected function activityHasApprovalTrail(MonthlyActivity $monthlyActivity): bool
    {
        $instance = WorkflowInstance::query()
            ->where('entity_type', MonthlyActivity::class)
            ->where('entity_id', $monthlyActivity->id)
            ->withCount('logs')
            ->first();

        if (! $instance) {
            return ! in_array((string) $monthlyActivity->status, ['draft', 'cancelled'], true);
        }

        return $instance->logs_count > 0
            || ! in_array((string) $instance->status, ['pending'], true)
            || ! in_array((string) $monthlyActivity->status, ['draft', 'cancelled'], true);
    }

    protected function shouldStartNewVersion(MonthlyActivity $monthlyActivity, array $changedFields, bool $isRescheduled): bool
    {
        if ($changedFields === []) {
            return false;
        }

        if (! $this->hasManagerOrLaterApproval($monthlyActivity)) {
            return false;
        }

        return $this->isApprovedVersion($monthlyActivity)
            || $isRescheduled
            || $this->activityHasApprovalTrail($monthlyActivity);
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
    protected function unifiedLockedFields(): array
    {
        return collect(config('monthly_activity.unified_branch_edit.locked_fields', []))
            ->filter(fn ($field) => is_string($field) && $field !== '')
            ->values()
            ->all();
    }

    protected function applyUnifiedLockedFieldValues(MonthlyActivity $monthlyActivity, array &$data, User $user): void
    {
        if (! $this->canBranchEditUnifiedNonCoreFields($monthlyActivity, $user)) {
            return;
        }

        $lockedFields = $this->unifiedLockedFields();
        $locked = fn (string $field): bool => in_array($field, $lockedFields, true);

        if ($locked('title')) {
            $data['title'] = $monthlyActivity->title;
        }
        if ($locked('activity_date')) {
            $data['activity_date'] = optional($monthlyActivity->activity_date)->toDateString()
                ?: optional($monthlyActivity->proposed_date)->toDateString()
                ?: ($data['activity_date'] ?? null);
        }
        if ($locked('proposed_date')) {
            $data['proposed_date'] = optional($monthlyActivity->proposed_date)->toDateString() ?: ($data['proposed_date'] ?? null);
        }
        if ($locked('branch_id')) {
            $data['branch_id'] = (int) $monthlyActivity->branch_id;
        }
        if ($locked('agenda_event_id')) {
            $data['agenda_event_id'] = $monthlyActivity->agenda_event_id;
            $data['is_in_agenda'] = (bool) $monthlyActivity->is_in_agenda;
        }
        if ($locked('target_group_ids')) {
            $data['target_group_ids'] = $monthlyActivity->targetGroups()->pluck('target_groups.id')->map(fn ($id) => (int) $id)->all();
            $data['target_group_id'] = $monthlyActivity->target_group_id;
            $data['target_group_other'] = $monthlyActivity->target_group_other;
        }
        if ($locked('responsible_entities')) {
            $data['responsible_entities'] = array_values(array_filter([
                $monthlyActivity->requires_communications ? 'relations' : null,
                $monthlyActivity->requires_programs ? 'programs' : null,
            ]));
            $data['requires_programs'] = (bool) $monthlyActivity->requires_programs;
            $data['requires_communications'] = (bool) $monthlyActivity->requires_communications;
        }
    }

    public function syncFromAgenda(Request $request)
    {
        if ($branchId = $this->currentUserBranchId($request->user())) {
            $request->merge(['branch_id' => $branchId]);
        }

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        if (! $this->canAccessScopedBranch($request->user(), (int) $data['branch_id'])) {
            abort(403);
        }

        $events = AgendaEvent::query()
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->whereNotNull('event_date')
                        ->whereMonth('event_date', $data['month'])
                        ->whereYear('event_date', $data['year']);
                })->orWhere(function ($q) use ($data) {
                    $q->whereNull('event_date')
                        ->where('month', $data['month']);
                });
            })
            ->whereIn('status', ['relations_approved', 'published'])
            ->where(function ($query) use ($data) {
                $query->where('event_type', 'mandatory')
                    ->orWhereHas('participations', function ($participationQuery) use ($data) {
                        $participationQuery
                            ->where('entity_type', 'branch')
                            ->where('entity_id', $data['branch_id'])
                            ->where('participation_status', 'participant');
                    });
            })
            ->get();

        $created = 0;
        foreach ($events as $event) {
            $exists = MonthlyActivity::query()
                ->where('agenda_event_id', $event->id)
                ->where('branch_id', $data['branch_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            $isReadOnlyUnified = (string) ($event->plan_type ?? 'non_unified') === 'unified'
                && (string) ($event->event_type ?? '') === 'mandatory';

            MonthlyActivity::create([
                'month' => (int) $event->month,
                'day' => (int) $event->day,
                'title' => $event->event_name,
                'proposed_date' => optional($event->event_date)?->toDateString() ?? Carbon::create($data['year'], $event->month, $event->day)->toDateString(),
                'is_in_agenda' => true,
                'is_from_agenda' => true,
                'agenda_event_id' => $event->id,
                'participation_status' => $event->event_type === 'optional' ? 'unspecified' : 'participant',
                'plan_type' => $event->plan_type ?? 'non_unified',
                'description' => $event->notes,
                'location_type' => 'inside_center',
                'location_details' => null,
                'status' => $isReadOnlyUnified ? 'approved' : 'draft',
                'execution_status' => 'executed',
                'relations_manager_approval_status' => $isReadOnlyUnified ? 'approved' : null,
                'executive_approval_status' => $isReadOnlyUnified ? 'approved' : null,
                'lifecycle_status' => $isReadOnlyUnified ? 'Approved' : null,
                'lock_at' => $this->buildLockAt(optional($event->event_date)?->toDateString() ?? Carbon::create($data['year'], $event->month, $event->day)->toDateString()),
                'is_official' => false,
                'branch_id' => (int) $data['branch_id'],
                'created_by' => $request->user()->id,
            ]);

            $created++;
        }

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.sync.done', ['count' => $created]));
    }

    public function store(
        Request $request,
        ConflictDetectionService $conflicts,
        MonthlyActivityWorkflowService $workflowService,
        NotificationService $notifications,
        MonthlyActivityLifecycleService $lifecycle,
        DynamicWorkflowService $dynamicWorkflowService
    )
    {
        if ($request->hasFile('planning_attachment') && ! $request->hasFile('branch_plan_file')) {
            $request->files->set('branch_plan_file', $request->file('planning_attachment'));
        }

        if ($branchId = $this->currentUserBranchId($request->user())) {
            $request->merge(['branch_id' => $branchId]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
            'is_in_agenda' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
            'submit_action' => ['nullable', 'in:draft,submit'],
            'execution_status' => ['required', 'in:executed,postponed,cancelled'],
            'responsible_party' => ['nullable', 'string', 'max:255'],

            'location_type' => ['required', 'in:inside_center,outside_center'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'internal_location' => ['nullable', 'string', 'max:255', 'required_if:location_type,inside_center'],
            'outside_place_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'outside_google_maps_url' => array_merge($this->safeExternalUrlRules(), ['required_if:location_type,outside_center']),
            'outside_contact_number' => ['nullable', 'required_if:location_type,outside_center', 'regex:/^(\\+962|0)7[789]\\d{7}$/'],
            'external_liaison_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'external_liaison_phone' => ['nullable', 'string', 'max:50', 'required_if:location_type,outside_center'],
            'outside_address' => ['nullable', 'string'],
            'execution_time' => ['nullable', 'string', 'max:255'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_to' => ['nullable', 'date_format:H:i', 'after:time_from'],
            'target_group' => ['nullable', 'string', 'max:255'],
            'target_group_id' => ['nullable', 'exists:target_groups,id'],
            'target_group_ids' => ['nullable', 'array'],
            'target_group_ids.*' => ['nullable', 'integer', 'exists:target_groups,id'],
            'target_group_other' => ['nullable', 'string', 'max:255', 'required_if:target_group,other'],
            'short_description' => ['required', 'string', 'max:255'],
            'volunteer_need' => ['nullable', 'string', 'max:255'],
            'needs_volunteers' => ['nullable', 'boolean'],
            'required_volunteers' => ['nullable', 'integer', 'min:1', 'required_if:needs_volunteers,1'],
            'volunteer_age_range' => ['nullable', 'string', 'max:255', 'required_if:needs_volunteers,1'],
            'volunteer_gender' => ['nullable', 'string', 'max:255', 'required_if:needs_volunteers,1'],
            'volunteer_tasks_summary' => ['nullable', 'string', 'max:1500', 'required_if:needs_volunteers,1'],
            'expected_attendance' => ['nullable', 'integer', 'min:0'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'attendance_notes' => ['nullable', 'string'],
            'work_teams_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'needs_media_coverage' => ['nullable', 'boolean'],
            'media_coverage_notes' => ['nullable', 'string'],
            'requires_programs' => ['nullable', 'boolean'],
            'requires_workshops' => ['nullable', 'boolean'],
            'requires_communications' => ['nullable', 'boolean'],
            'responsible_entities' => ['nullable', 'array'],
            'responsible_entities.*' => ['in:relations,programs'],
            'is_program_related' => ['nullable', 'boolean'],
            'participation_status' => ['nullable', 'in:participant,not_participant,unspecified'],
            'branch_plan_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'planning_attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'needs_official_correspondence' => ['nullable', 'boolean'],
            'official_correspondence_reason' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_target' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_brief' => ['nullable', 'string', 'max:1500', 'required_if:needs_official_correspondence,1'],
            'has_sponsor' => ['nullable', 'boolean'],
            'sponsor_name_title' => ['nullable', 'string', 'max:255'],
            'has_partners' => ['nullable', 'boolean'],
            'partner_1_name' => ['nullable', 'string', 'max:255'],
            'partner_1_role' => ['nullable', 'string', 'max:255'],
            'partner_2_name' => ['nullable', 'string', 'max:255'],
            'partner_2_role' => ['nullable', 'string', 'max:255'],
            'partner_3_name' => ['nullable', 'string', 'max:255'],
            'partner_3_role' => ['nullable', 'string', 'max:255'],
            'needs_official_letters' => ['nullable', 'boolean'],
            'letter_purpose' => ['nullable', 'string', 'max:255'],
            'rescheduled_date' => ['nullable', 'date', 'required_if:execution_status,postponed'],
            'reschedule_reason' => ['nullable', 'string', 'required_if:execution_status,postponed'],
            'cancellation_reason' => ['nullable', 'string', 'required_if:execution_status,cancelled'],
            'relations_approval_on_reschedule' => ['nullable', 'boolean'],
            'audience_satisfaction_percent' => ['nullable', 'numeric', 'between:0,100'],
            'evaluation_score' => ['nullable', 'numeric', 'between:0,100'],
            'sponsors' => ['array'],
            'sponsors.*.name' => ['nullable', 'string', 'max:255'],
            'sponsors.*.title' => ['nullable', 'string', 'max:255'],
            'sponsors.*.is_official' => ['nullable', 'boolean'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:255'],
            'partners.*.role' => ['nullable', 'required_with:partners.*.name', 'string', 'max:255'],
            'partners.*.contact_info' => ['nullable', 'string', 'max:255'],
            'team_members' => ['nullable', 'array'],
            'team_members.*.team_name' => ['nullable', 'string', 'max:255'],
            'team_members.*.member_name' => ['nullable', 'string', 'max:255'],
            'team_members.*.member_email' => ['nullable', 'email', 'max:255'],
            'team_members.*.role_desc' => ['nullable', 'string', 'max:255'],
            'team_groups' => ['nullable', 'array'],
            'team_groups.*.team_name' => ['nullable', 'string', 'max:255'],
            'team_groups.*.members' => ['nullable', 'array'],
            'team_groups.*.members.*.member_name' => ['nullable', 'string', 'max:255'],
            'team_groups.*.members.*.role_desc' => ['nullable', 'string', 'max:255'],
            'requires_supplies' => ['nullable', 'boolean'],
            'supplies' => ['nullable', 'array'],
            'supplies.*.item_name' => ['nullable', 'string', 'max:255'],
            'supplies.*.available' => ['nullable', 'boolean'],
            'supplies.*.provider_type' => ['nullable', 'string', 'max:255', 'required_if:supplies.*.available,false'],
            'supplies.*.provider_name' => ['nullable', 'string', 'max:255', 'required_if:supplies.*.available,false'],
            'evaluations' => ['nullable', 'array'],
            'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
            'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
            'evaluations.*.note' => ['nullable', 'string'],
            'followup_remarks' => ['nullable', 'string'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        if (! $this->canAccessScopedBranch($request->user(), (int) $data['branch_id'])) {
            abort(403);
        }

        if (! empty($data['agenda_event_id'])) {
            $hasActiveForSameAgenda = MonthlyActivity::query()
                ->where('branch_id', (int) $data['branch_id'])
                ->where('agenda_event_id', (int) $data['agenda_event_id'])
                ->where('status', '!=', 'cancelled')
                ->whereDoesntHave('newerVersions')
                ->exists();

            if ($hasActiveForSameAgenda) {
                return back()->withErrors(['agenda_event_id' => 'لا يمكن ربط أكثر من خطة فعالة لنفس الفرع مع نفس فعالية الأجندة.'])->withInput();
            }
        }

        $this->normalizePlanningPayload($data);

        $date = Carbon::parse($data['activity_date']);
        $conflictNames = $conflicts->findMonthlyActivityConflicts($data['proposed_date'], (int) $data['branch_id'], null, $data['execution_time'] ?? null);
        $conflictWarning = empty($conflictNames) ? null : __('Potential overlap with: :activities', ['activities' => implode(', ', $conflictNames)]);
        $isFromAgenda = ! empty($data['agenda_event_id']);
        $planType = $isFromAgenda ? optional(AgendaEvent::find($data['agenda_event_id']))->plan_type : 'non_unified';

        $monthlyActivity = MonthlyActivity::create([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'proposed_date' => $data['proposed_date'],
            'is_in_agenda' => (bool) ($data['is_in_agenda'] ?? !empty($data['agenda_event_id'])),
            'is_from_agenda' => $isFromAgenda,
            'agenda_event_id' => $data['agenda_event_id'] ?? null,
            'participation_status' => $data['participation_status'] ?? 'unspecified',
            'plan_type' => $planType ?? 'non_unified',
            'activity_date' => $date->toDateString(),
            'branch_plan_file' => $request->file('branch_plan_file')?->store('monthly/plans/v1', 'public'),
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_contact_number' => $data['outside_contact_number'] ?? null,
            'external_liaison_name' => $data['external_liaison_name'] ?? null,
            'external_liaison_phone' => $data['external_liaison_phone'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'status' => 'draft',
            'execution_status' => $data['execution_status'],
            'plan_stage' => 1,
            'plan_version' => 1,
            'previous_version_id' => null,
            'responsible_party' => $data['responsible_party'] ?? null,
            'execution_time' => $data['execution_time'] ?? null,
            'time_from' => $data['time_from'] ?? null,
            'time_to' => $data['time_to'] ?? null,
            'target_group' => $data['target_group'] ?? null,
            'target_group_id' => $data['target_group_id'] ?? ($data['target_group_ids'][0] ?? null),
            'target_group_other' => $data['target_group_other'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'work_teams_count' => $data['work_teams_count'] ?? null,
            'volunteer_need' => $data['volunteer_need'] ?? null,
            'needs_volunteers' => (bool) ($data['needs_volunteers'] ?? false),
            'required_volunteers' => $data['required_volunteers'] ?? null,
            'volunteer_age_range' => $data['volunteer_age_range'] ?? null,
            'volunteer_gender' => $data['volunteer_gender'] ?? null,
            'volunteer_tasks_summary' => $data['volunteer_tasks_summary'] ?? null,
            'expected_attendance' => $data['expected_attendance'] ?? null,
            'actual_attendance' => $data['actual_attendance'] ?? null,
            'attendance_notes' => $data['attendance_notes'] ?? null,
            'has_sponsor' => (bool) (($data['has_sponsor'] ?? false) || !empty($data['sponsors'] ?? [])),
            'sponsor_name_title' => $data['sponsor_name_title'] ?? null,
            'has_partners' => (bool) (($data['has_partners'] ?? false) || !empty($data['partners'] ?? [])),
            'needs_official_letters' => (bool) ($data['needs_official_letters'] ?? false),
            'needs_official_correspondence' => (bool) ($data['needs_official_correspondence'] ?? false),
            'official_correspondence_reason' => $data['official_correspondence_reason'] ?? null,
            'official_correspondence_target' => $data['official_correspondence_target'] ?? null,
            'official_correspondence_brief' => $data['official_correspondence_brief'] ?? null,
            'letter_purpose' => $data['letter_purpose'] ?? null,
            'rescheduled_date' => $data['rescheduled_date'] ?? null,
            'reschedule_reason' => $data['reschedule_reason'] ?? null,
            'cancellation_reason' => $data['cancellation_reason'] ?? null,
            'relations_approval_on_reschedule' => (bool) ($data['relations_approval_on_reschedule'] ?? false),
            'audience_satisfaction_percent' => $data['audience_satisfaction_percent'] ?? null,
            'evaluation_score' => $data['evaluation_score'] ?? null,
            'needs_media_coverage' => (bool) ($data['needs_media_coverage'] ?? false),
            'media_coverage_notes' => $data['media_coverage_notes'] ?? null,
            'requires_programs' => (bool) (($data['requires_programs'] ?? false) || in_array('programs', $data['responsible_entities'] ?? [], true)),
            'is_program_related' => (bool) ($data['is_program_related'] ?? false),
            'requires_workshops' => (bool) ($data['requires_workshops'] ?? false),
            'requires_communications' => (bool) (($data['requires_communications'] ?? false) || in_array('relations', $data['responsible_entities'] ?? [], true)),
            'lock_at' => $this->buildLockAt($data['proposed_date']),
            'is_official' => false,
            'branch_id' => $data['branch_id'],
            'created_by' => $request->user()->id,
        ]);

        $workflowService->initializeDynamicStatuses($monthlyActivity);
        $this->syncTargetGroups($monthlyActivity, $data);
        Log::info('monthly_activity.created', [
            'monthly_activity_id' => $monthlyActivity->id,
            'created_by' => $request->user()->id,
            'plan_version' => $monthlyActivity->plan_version,
        ]);

        $this->syncSponsorsAndPartners($monthlyActivity, $data);
        foreach (($data['team_groups'] ?? []) as $groupIndex => $group) {
            $teamName = trim((string) ($group['team_name'] ?? '')) ?: 'فريق '.((int) $groupIndex + 1);
            foreach (($group['members'] ?? []) as $member) {
                $memberName = trim((string) ($member['member_name'] ?? ''));
                if ($memberName === '') {
                    continue;
                }
                MonthlyActivityTeam::create([
                    'monthly_activity_id' => $monthlyActivity->id,
                    'team_name' => $teamName,
                    'member_name' => $memberName,
                    'member_email' => null,
                    'role_desc' => $member['role_desc'] ?? null,
                ]);
            }
        }
        foreach (($data['team_members'] ?? []) as $member) {
            $memberName = trim((string) ($member['member_name'] ?? ''));
            if ($memberName === '') {
                continue;
            }
            MonthlyActivityTeam::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'team_name' => $member['team_name'] ?? null,
                'member_name' => $memberName,
                'member_email' => $member['member_email'] ?? null,
                'role_desc' => $member['role_desc'] ?? null,
            ]);
        }
        foreach (($data['supplies'] ?? []) as $supply) {
            $itemName = trim((string) ($supply['item_name'] ?? ''));
            if ($itemName === '') {
                continue;
            }
            $available = (bool) ($supply['available'] ?? false);
            MonthlyActivitySupply::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'item_name' => $itemName,
                'available' => $available,
                'status' => $available ? 'available' : 'needed',
                'provider_type' => $available ? null : ($supply['provider_type'] ?? null),
                'provider_name' => $available ? null : ($supply['provider_name'] ?? null),
            ]);
        }
        if ($request->user()->hasRole('followup_officer') || $request->user()->hasRole('super_admin')) {
            $this->syncEvaluationData($monthlyActivity, $data, $request->user()->id);
        }
        $this->logWorkflowAction('created', $monthlyActivity, $request, $monthlyActivity->status);

        if ($this->shouldSubmitFromRequest($request)) {
            $this->submitActivityForApproval($monthlyActivity, $request->user(), $notifications, $lifecycle, $dynamicWorkflowService, $request);
        }

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.created'))
            ->with('warning', $conflictWarning);
    }

    public function edit(MonthlyActivity $monthlyActivity)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());

        if (! request()->boolean('form') && request('mode') !== 'post') {
            $monthlyActivity->load(['branch', 'creator', 'agendaEvent', 'sponsors', 'partners', 'supplies', 'team', 'targetGroups'])
                ->loadCount('newerVersions');

            $monthlyStatusLabels = $this->statusLookupOptions('monthly_activities', [], (string) $monthlyActivity->status)
                ->pluck('name', 'code')
                ->all();
            $executionStatusLabels = $this->executionStatusLabels();
            $archivedVersions = collect();
            $cursor = $monthlyActivity->previousVersion;
            while ($cursor) {
                $archivedVersions->push($cursor);
                $cursor = $cursor->previousVersion;
            }

            return view('pages.monthly_activities.activities.show', [
                'monthlyActivity' => $monthlyActivity,
                'editMirrorMode' => true,
                'monthlyStatusLabels' => $monthlyStatusLabels,
                'executionStatusLabels' => $executionStatusLabels,
                'archivedVersions' => $archivedVersions,
            ]);
        }

        $monthlyActivity->load(['agendaEvent', 'supplies', 'team', 'attachments', 'approvals', 'sponsors', 'partners', 'evaluationResponses.question', 'followups']);
        if (request()->boolean('form')) {
            $this->flashFormPrefill($monthlyActivity);
        }
        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $this->scopedBranchIds(request()->user());
        if ($scopedBranchIds !== []) {
            $branches->whereIn('id', $scopedBranchIds);
        }
        $branches = $branches->get();
        $agendaEvents = $this->agendaEventsForUser(request()->user(), $monthlyActivity);
        $targetGroups = TargetGroup::where('is_active', true)->orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::where('is_active', true)->orderBy('sort_order')->get();
        $monthlyStatusOptions = $this->monthlyPlanningStatusOptions((string) $monthlyActivity->status);
        $monthlyCloseStatusOptions = $this->monthlyCloseStatusOptions((string) $monthlyActivity->status);
        $executionStatusLabels = $this->executionStatusLabels();

        return view('pages.monthly_activities.activities.edit', compact(
            'monthlyActivity',
            'branches',
            'agendaEvents',
            'targetGroups',
            'evaluationQuestions',
            'monthlyStatusOptions',
            'monthlyCloseStatusOptions',
            'executionStatusLabels',
        ));
    }

    public function show(MonthlyActivity $monthlyActivity, MonthlyWorkflowPresenter $monthlyWorkflowPresenter)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());

        $monthlyActivity->load([
            'branch',
            'creator',
            'agendaEvent',
            'sponsors',
            'partners',
            'supplies',
            'team',
            'targetGroups',
            'attachments.uploader',
            'workflowInstance.workflow.steps.role',
            'workflowInstance.currentStep.role',
            'workflowInstance.currentStep.permission',
            'workflowInstance.logs.step.role',
            'workflowInstance.logs.step.permission',
            'workflowInstance.logs.actor',
        ])
            ->loadCount('newerVersions');
        $monthlyWorkflowPresenter->attach($monthlyActivity, request()->user());
        $monthlyStatusLabels = $this->statusLookupOptions('monthly_activities', [], (string) $monthlyActivity->status)
            ->pluck('name', 'code')
            ->all();
        $executionStatusLabels = $this->executionStatusLabels();
        $archivedVersions = collect();
        $cursor = $monthlyActivity->previousVersion;
        while ($cursor) {
            $archivedVersions->push($cursor);
            $cursor = $cursor->previousVersion;
        }

        return view('pages.monthly_activities.activities.show', compact('monthlyActivity', 'monthlyStatusLabels', 'executionStatusLabels', 'archivedVersions'));
    }

    public function update(
        Request $request,
        MonthlyActivity $monthlyActivity,
        ConflictDetectionService $conflicts,
        MonthlyActivityWorkflowService $workflowService,
        NotificationService $notifications,
        MonthlyActivityLifecycleService $lifecycle,
        DynamicWorkflowService $dynamicWorkflowService
    )
    {
        if ($request->hasFile('planning_attachment') && ! $request->hasFile('branch_plan_file')) {
            $request->files->set('branch_plan_file', $request->file('planning_attachment'));
        }

        $this->ensureActivityVisibleToUser($monthlyActivity, $request->user());

        if ($this->isSupersededVersion($monthlyActivity)) {
            return back()->withErrors([
                'status' => 'هذه نسخة قديمة من النشاط. يرجى متابعة آخر نسخة فقط.',
            ]);
        }

        if ($branchId = $this->currentUserBranchId($request->user())) {
            $request->merge(['branch_id' => $branchId]);
        }

        if ($request->user()->hasRole('followup_officer') && ! $request->user()->hasRole('super_admin')) {
            abort_unless($this->canSubmitPostEvaluation($monthlyActivity), 422, 'التقييم متاح فقط بعد تنفيذ الفعالية.');

            $data = $request->validate([
                'evaluations' => ['nullable', 'array'],
                'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
                'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
                'evaluations.*.note' => ['nullable', 'string'],
                'followup_remarks' => ['nullable', 'string'],
            ]);

            $this->syncEvaluationData($monthlyActivity, $data, $request->user()->id);
            $this->logWorkflowAction('evaluation_submitted', $monthlyActivity, $request, $monthlyActivity->status);

            return redirect()
                ->route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'mode' => 'post'])
                ->with('status', 'تم حفظ متابعة وتقييم الفعالية بنجاح.');
        }

        $isCreator = (int) $monthlyActivity->created_by === (int) $request->user()->id;

        if ($this->isLocked($monthlyActivity) && ! $request->user()->hasRole('super_admin') && ! $isCreator) {
            return back()->withErrors(['status' => __('app.roles.programs.monthly_activities.errors.locked')]);
        }

        if ($request->user()->hasRole('programs_officer') && $monthlyActivity->executive_approval_status === 'approved' && ! $isCreator) {
            return back()->withErrors(['status' => 'لا يمكن تعديل الفعالية بعد الاعتماد التنفيذي النهائي.']);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
            'is_in_agenda' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
            'submit_action' => ['nullable', 'in:draft,submit'],
            'execution_status' => ['required', 'in:executed,postponed,cancelled'],
            'responsible_party' => ['nullable', 'string', 'max:255'],

            'location_type' => ['required', 'in:inside_center,outside_center'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'internal_location' => ['nullable', 'string', 'max:255', 'required_if:location_type,inside_center'],
            'outside_place_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'outside_google_maps_url' => array_merge($this->safeExternalUrlRules(), ['required_if:location_type,outside_center']),
            'outside_contact_number' => ['nullable', 'required_if:location_type,outside_center', 'regex:/^(\\+962|0)7[789]\\d{7}$/'],
            'external_liaison_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'external_liaison_phone' => ['nullable', 'string', 'max:50', 'required_if:location_type,outside_center'],
            'outside_address' => ['nullable', 'string'],
            'execution_time' => ['nullable', 'string', 'max:255'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_to' => ['nullable', 'date_format:H:i', 'after:time_from'],
            'target_group' => ['nullable', 'string', 'max:255'],
            'target_group_id' => ['nullable', 'exists:target_groups,id'],
            'target_group_ids' => ['nullable', 'array'],
            'target_group_ids.*' => ['nullable', 'integer', 'exists:target_groups,id'],
            'target_group_other' => ['nullable', 'string', 'max:255', 'required_if:target_group,other'],
            'short_description' => ['required', 'string', 'max:255'],
            'volunteer_need' => ['nullable', 'string', 'max:255'],
            'needs_volunteers' => ['nullable', 'boolean'],
            'required_volunteers' => ['nullable', 'integer', 'min:1', 'required_if:needs_volunteers,1'],
            'volunteer_age_range' => ['nullable', 'string', 'max:255', 'required_if:needs_volunteers,1'],
            'volunteer_gender' => ['nullable', 'string', 'max:255', 'required_if:needs_volunteers,1'],
            'volunteer_tasks_summary' => ['nullable', 'string', 'max:1500', 'required_if:needs_volunteers,1'],
            'expected_attendance' => ['nullable', 'integer', 'min:0'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'attendance_notes' => ['nullable', 'string'],
            'work_teams_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'needs_media_coverage' => ['nullable', 'boolean'],
            'media_coverage_notes' => ['nullable', 'string'],
            'requires_programs' => ['nullable', 'boolean'],
            'requires_workshops' => ['nullable', 'boolean'],
            'requires_communications' => ['nullable', 'boolean'],
            'is_program_related' => ['nullable', 'boolean'],
            'participation_status' => ['nullable', 'in:participant,not_participant,unspecified'],
            'branch_plan_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'planning_attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'needs_official_correspondence' => ['nullable', 'boolean'],
            'official_correspondence_reason' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_target' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_brief' => ['nullable', 'string', 'max:1500', 'required_if:needs_official_correspondence,1'],
            'has_sponsor' => ['nullable', 'boolean'],
            'sponsor_name_title' => ['nullable', 'string', 'max:255'],
            'has_partners' => ['nullable', 'boolean'],
            'partner_1_name' => ['nullable', 'string', 'max:255'],
            'partner_1_role' => ['nullable', 'string', 'max:255'],
            'partner_2_name' => ['nullable', 'string', 'max:255'],
            'partner_2_role' => ['nullable', 'string', 'max:255'],
            'partner_3_name' => ['nullable', 'string', 'max:255'],
            'partner_3_role' => ['nullable', 'string', 'max:255'],
            'needs_official_letters' => ['nullable', 'boolean'],
            'letter_purpose' => ['nullable', 'string', 'max:255'],
            'rescheduled_date' => ['nullable', 'date', 'required_if:execution_status,postponed'],
            'reschedule_reason' => ['nullable', 'string', 'required_if:execution_status,postponed'],
            'cancellation_reason' => ['nullable', 'string', 'required_if:execution_status,cancelled'],
            'relations_approval_on_reschedule' => ['nullable', 'boolean'],
            'audience_satisfaction_percent' => ['nullable', 'numeric', 'between:0,100'],
            'evaluation_score' => ['nullable', 'numeric', 'between:0,100'],
            'sponsors' => ['array'],
            'sponsors.*.name' => ['nullable', 'string', 'max:255'],
            'sponsors.*.title' => ['nullable', 'string', 'max:255'],
            'sponsors.*.is_official' => ['nullable', 'boolean'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:255'],
            'partners.*.role' => ['nullable', 'required_with:partners.*.name', 'string', 'max:255'],
            'partners.*.contact_info' => ['nullable', 'string', 'max:255'],
            'evaluations' => ['nullable', 'array'],
            'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
            'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
            'evaluations.*.note' => ['nullable', 'string'],
            'followup_remarks' => ['nullable', 'string'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $this->applyUnifiedLockedFieldValues($monthlyActivity, $data, $request->user());

        if (! $this->canAccessScopedBranch($request->user(), (int) $data['branch_id'])) {
            abort(403);
        }

        if (! empty($data['agenda_event_id'])) {
            $hasActiveForSameAgenda = MonthlyActivity::query()
                ->where('branch_id', (int) $data['branch_id'])
                ->where('agenda_event_id', (int) $data['agenda_event_id'])
                ->where('status', '!=', 'cancelled')
                ->whereDoesntHave('newerVersions')
                ->where('id', '!=', $monthlyActivity->id)
                ->exists();

            if ($hasActiveForSameAgenda) {
                return back()->withErrors(['agenda_event_id' => 'لا يمكن ربط أكثر من خطة فعالة لنفس الفرع مع نفس فعالية الأجندة.'])->withInput();
            }
        }

        $this->normalizePlanningPayload($data);

        $date = Carbon::parse($data['activity_date']);
        $conflictNames = $conflicts->findMonthlyActivityConflicts($data['proposed_date'], (int) $data['branch_id'], $monthlyActivity->id, $data['execution_time'] ?? null);
        $conflictWarning = empty($conflictNames) ? null : __('Potential overlap with: :activities', ['activities' => implode(', ', $conflictNames)]);
        $isFromAgenda = ! empty($data['agenda_event_id']);
        $planType = $isFromAgenda ? optional(AgendaEvent::find($data['agenda_event_id']))->plan_type : 'non_unified';
        $branchPlanFile = $monthlyActivity->branch_plan_file;
        $branchPlanLocked = $this->canBranchEditUnifiedNonCoreFields($monthlyActivity, $request->user())
            && in_array('planning_attachment', $this->unifiedLockedFields(), true);
        if ($request->hasFile('branch_plan_file') && ! $branchPlanLocked) {
            if ($branchPlanFile) {
                Storage::disk('public')->delete($branchPlanFile);
            }
            $nextVersionPath = 'monthly/plans/v'.((int) ($monthlyActivity->plan_version ?: 1));
            $branchPlanFile = $request->file('branch_plan_file')->store($nextVersionPath, 'public');
        }

        $oldValues = $monthlyActivity->only([
            'title',
            'activity_date',
            'proposed_date',
            'agenda_event_id',
            'is_in_agenda',
            'description',
            'location_type',
            'location_details',
            'internal_location',
            'outside_place_name',
            'outside_google_maps_url',
            'outside_contact_number',
            'external_liaison_name',
            'external_liaison_phone',
            'outside_address',
            'status',
            'execution_status',
            'plan_stage',
            'plan_version',
            'responsible_party',
            'execution_time',
            'time_from',
            'time_to',
            'target_group',
            'target_group_id',
            'target_group_other',
            'short_description',
            'work_teams_count',
            'volunteer_need',
            'needs_volunteers',
            'required_volunteers',
            'volunteer_age_range',
            'volunteer_gender',
            'volunteer_tasks_summary',
            'expected_attendance',
            'actual_attendance',
            'attendance_notes',
            'has_sponsor',
            'sponsor_name_title',
            'has_partners',
            'partner_1_name',
            'partner_1_role',
            'partner_2_name',
            'partner_2_role',
            'partner_3_name',
            'partner_3_role',
            'needs_official_letters',
            'needs_official_correspondence',
            'official_correspondence_reason',
            'official_correspondence_target',
            'official_correspondence_brief',
            'letter_purpose',
            'rescheduled_date',
            'reschedule_reason',
            'cancellation_reason',
            'relations_approval_on_reschedule',
            'audience_satisfaction_percent',
            'evaluation_score',
            'requires_programs',
            'requires_workshops',
            'requires_communications',
            'is_program_related',
            'participation_status',
            'plan_type',
            'branch_plan_file',
            'branch_id',
            'month',
            'day',
            'lifecycle_status',
            'relations_officer_approval_status',
            'relations_manager_approval_status',
            'programs_officer_approval_status',
            'programs_manager_approval_status',
            'liaison_approval_status',
            'hq_relations_manager_approval_status',
            'executive_approval_status',
        ]);

        $isRescheduled = ($data['execution_status'] ?? null) === 'postponed'
            || (
                ! empty($data['rescheduled_date'])
                && optional($monthlyActivity->rescheduled_date)?->toDateString() !== (string) $data['rescheduled_date']
            );
        $nextStage = (int) ($monthlyActivity->plan_stage ?: 1);
        $nextVersion = (int) ($monthlyActivity->plan_version ?: 1);
        $newStatus = $this->shouldSubmitFromRequest($request) ? $monthlyActivity->status : 'draft';
        $newLifecycleStatus = $monthlyActivity->lifecycle_status ?: 'Draft';
        $startsNewVersion = false;

        $newValues = [
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'activity_date' => $date->toDateString(),
            'proposed_date' => $data['proposed_date'],
            'agenda_event_id' => $data['agenda_event_id'] ?? null,
            'is_in_agenda' => (bool) ($data['is_in_agenda'] ?? $isFromAgenda),
            'is_from_agenda' => $isFromAgenda,
            'participation_status' => $data['participation_status'] ?? 'unspecified',
            'plan_type' => $planType ?? 'non_unified',
            'branch_plan_file' => $branchPlanFile,
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_contact_number' => $data['outside_contact_number'] ?? null,
            'external_liaison_name' => $data['external_liaison_name'] ?? null,
            'external_liaison_phone' => $data['external_liaison_phone'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'status' => $newStatus,
            'execution_status' => $data['execution_status'],
            'plan_stage' => $nextStage,
            'plan_version' => $nextVersion,
            'previous_version_id' => $startsNewVersion ? $monthlyActivity->id : $monthlyActivity->previous_version_id,
            'responsible_party' => $data['responsible_party'] ?? null,
            'execution_time' => $data['execution_time'] ?? null,
            'time_from' => $data['time_from'] ?? null,
            'time_to' => $data['time_to'] ?? null,
            'target_group' => $data['target_group'] ?? null,
            'target_group_id' => $data['target_group_id'] ?? ($data['target_group_ids'][0] ?? null),
            'target_group_other' => $data['target_group_other'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'work_teams_count' => $data['work_teams_count'] ?? null,
            'volunteer_need' => $data['volunteer_need'] ?? null,
            'needs_volunteers' => (bool) ($data['needs_volunteers'] ?? false),
            'required_volunteers' => $data['required_volunteers'] ?? null,
            'volunteer_age_range' => $data['volunteer_age_range'] ?? null,
            'volunteer_gender' => $data['volunteer_gender'] ?? null,
            'volunteer_tasks_summary' => $data['volunteer_tasks_summary'] ?? null,
            'expected_attendance' => $data['expected_attendance'] ?? null,
            'actual_attendance' => $data['actual_attendance'] ?? null,
            'attendance_notes' => $data['attendance_notes'] ?? null,
            'has_sponsor' => (bool) (($data['has_sponsor'] ?? false) || !empty($data['sponsors'] ?? [])),
            'sponsor_name_title' => $data['sponsor_name_title'] ?? null,
            'has_partners' => (bool) (($data['has_partners'] ?? false) || !empty($data['partners'] ?? [])),
            'needs_official_letters' => (bool) ($data['needs_official_letters'] ?? false),
            'needs_official_correspondence' => (bool) ($data['needs_official_correspondence'] ?? false),
            'official_correspondence_reason' => $data['official_correspondence_reason'] ?? null,
            'official_correspondence_target' => $data['official_correspondence_target'] ?? null,
            'official_correspondence_brief' => $data['official_correspondence_brief'] ?? null,
            'letter_purpose' => $data['letter_purpose'] ?? null,
            'rescheduled_date' => $data['rescheduled_date'] ?? null,
            'reschedule_reason' => $data['reschedule_reason'] ?? null,
            'cancellation_reason' => $data['cancellation_reason'] ?? null,
            'relations_approval_on_reschedule' => (bool) ($data['relations_approval_on_reschedule'] ?? false),
            'audience_satisfaction_percent' => $data['audience_satisfaction_percent'] ?? null,
            'evaluation_score' => $data['evaluation_score'] ?? null,
            'needs_media_coverage' => (bool) ($data['needs_media_coverage'] ?? false),
            'media_coverage_notes' => $data['media_coverage_notes'] ?? null,
            'requires_programs' => (bool) ($data['requires_programs'] ?? false),
            'is_program_related' => (bool) ($data['is_program_related'] ?? false),
            'requires_workshops' => (bool) ($data['requires_workshops'] ?? false),
            'requires_communications' => (bool) ($data['requires_communications'] ?? false),
            'branch_id' => $data['branch_id'],
            'lifecycle_status' => $newLifecycleStatus,
            'relations_officer_approval_status' => $monthlyActivity->relations_officer_approval_status,
            'relations_manager_approval_status' => $monthlyActivity->relations_manager_approval_status,
            'programs_officer_approval_status' => $monthlyActivity->programs_officer_approval_status,
            'programs_manager_approval_status' => $monthlyActivity->programs_manager_approval_status,
            'liaison_approval_status' => $monthlyActivity->liaison_approval_status,
            'hq_relations_manager_approval_status' => $monthlyActivity->hq_relations_manager_approval_status,
            'executive_approval_status' => $monthlyActivity->executive_approval_status,
        ];

        $changedFields = $this->meaningfulChangedFields($oldValues, $newValues);
        $startsNewVersion = $this->shouldStartNewVersion($monthlyActivity, $changedFields, $isRescheduled);

        if ($startsNewVersion) {
            $nextStage++;
            $nextVersion++;
            $newStatus = 'draft';
            $newLifecycleStatus = 'Draft';

            $newValues['status'] = $newStatus;
            $newValues['plan_stage'] = $nextStage;
            $newValues['plan_version'] = $nextVersion;
            $newValues['previous_version_id'] = $monthlyActivity->id;
            $newValues['lifecycle_status'] = $newLifecycleStatus;
            $newValues['relations_officer_approval_status'] = 'pending';
            $newValues['relations_manager_approval_status'] = 'pending';
            $newValues['programs_officer_approval_status'] = 'pending';
            $newValues['programs_manager_approval_status'] = 'pending';
            $newValues['liaison_approval_status'] = 'pending';
            $newValues['hq_relations_manager_approval_status'] = 'pending';
            $newValues['executive_approval_status'] = 'pending';
        }

        $activityToSave = $monthlyActivity;

        DB::transaction(function () use ($startsNewVersion, $newValues, $data, $request, $monthlyActivity, &$activityToSave) {
            $lockedCurrent = MonthlyActivity::query()->whereKey($monthlyActivity->id)->lockForUpdate()->firstOrFail();
            $activityToSave = $lockedCurrent;

            if ($startsNewVersion) {
                $activityToSave = MonthlyActivity::create([
                'month' => $newValues['month'],
                'day' => $newValues['day'],
                'activity_date' => $newValues['activity_date'],
                'title' => $newValues['title'],
                'proposed_date' => $newValues['proposed_date'],
                'is_in_agenda' => $newValues['is_in_agenda'],
                'agenda_event_id' => $newValues['agenda_event_id'],
                'is_from_agenda' => $newValues['is_from_agenda'],
                'participation_status' => $newValues['participation_status'],
                'plan_type' => $newValues['plan_type'],
                'branch_plan_file' => $newValues['branch_plan_file'],
                'description' => $newValues['description'],
                'location_type' => $newValues['location_type'],
                'location_details' => $newValues['location_details'],
                'internal_location' => $newValues['internal_location'],
                'outside_place_name' => $newValues['outside_place_name'],
                'outside_google_maps_url' => $newValues['outside_google_maps_url'],
                'outside_contact_number' => $newValues['outside_contact_number'],
                'external_liaison_name' => $newValues['external_liaison_name'],
                'external_liaison_phone' => $newValues['external_liaison_phone'],
                'outside_address' => $newValues['outside_address'],
                'status' => $newValues['status'],
                'execution_status' => $newValues['execution_status'],
                'plan_stage' => $newValues['plan_stage'],
                'plan_version' => $newValues['plan_version'],
                'previous_version_id' => $newValues['previous_version_id'],
                'responsible_party' => $newValues['responsible_party'],
                'execution_time' => $newValues['execution_time'],
                'time_from' => $newValues['time_from'],
                'time_to' => $newValues['time_to'],
                'target_group' => $newValues['target_group'],
                'target_group_id' => $newValues['target_group_id'],
                'target_group_other' => $newValues['target_group_other'],
                'short_description' => $newValues['short_description'],
                'work_teams_count' => $newValues['work_teams_count'],
                'volunteer_need' => $newValues['volunteer_need'],
                'needs_volunteers' => $newValues['needs_volunteers'],
                'required_volunteers' => $newValues['required_volunteers'],
                'volunteer_age_range' => $newValues['volunteer_age_range'],
                'volunteer_gender' => $newValues['volunteer_gender'],
                'volunteer_tasks_summary' => $newValues['volunteer_tasks_summary'],
                'expected_attendance' => $newValues['expected_attendance'],
                'actual_attendance' => $newValues['actual_attendance'],
                'attendance_notes' => $newValues['attendance_notes'],
                'has_sponsor' => $newValues['has_sponsor'],
                'sponsor_name_title' => $newValues['sponsor_name_title'],
                'has_partners' => $newValues['has_partners'],
                'needs_official_letters' => $newValues['needs_official_letters'],
                'needs_official_correspondence' => $newValues['needs_official_correspondence'],
                'official_correspondence_reason' => $newValues['official_correspondence_reason'],
                'official_correspondence_target' => $newValues['official_correspondence_target'],
                'official_correspondence_brief' => $newValues['official_correspondence_brief'],
                'letter_purpose' => $newValues['letter_purpose'],
                'rescheduled_date' => $newValues['rescheduled_date'],
                'reschedule_reason' => $newValues['reschedule_reason'],
                'cancellation_reason' => $newValues['cancellation_reason'],
                'relations_approval_on_reschedule' => $newValues['relations_approval_on_reschedule'],
                'audience_satisfaction_percent' => $newValues['audience_satisfaction_percent'],
                'evaluation_score' => $newValues['evaluation_score'],
                'needs_media_coverage' => $newValues['needs_media_coverage'],
                'media_coverage_notes' => $newValues['media_coverage_notes'],
                'requires_programs' => $newValues['requires_programs'],
                'is_program_related' => $newValues['is_program_related'],
                'requires_workshops' => $newValues['requires_workshops'],
                'requires_communications' => $newValues['requires_communications'],
                'branch_id' => $newValues['branch_id'],
                'lifecycle_status' => $newValues['lifecycle_status'],
                'relations_officer_approval_status' => $newValues['relations_officer_approval_status'],
                'relations_manager_approval_status' => $newValues['relations_manager_approval_status'],
                'programs_officer_approval_status' => $newValues['programs_officer_approval_status'],
                'programs_manager_approval_status' => $newValues['programs_manager_approval_status'],
                'liaison_approval_status' => $newValues['liaison_approval_status'],
                'hq_relations_manager_approval_status' => $newValues['hq_relations_manager_approval_status'],
                'executive_approval_status' => $newValues['executive_approval_status'],
                'lock_at' => $this->buildLockAt($data['proposed_date']),
                'is_official' => $this->buildLockAt($data['proposed_date'])?->isPast() ?? false,
                'created_by' => $request->user()->id,
            ]);

                $lockedCurrent->update([
                    'status' => 'cancelled',
                ]);

                WorkflowInstance::query()
                    ->where('entity_type', MonthlyActivity::class)
                    ->where('entity_id', $lockedCurrent->id)
                    ->update([
                        'status' => 'rejected',
                        'current_step_id' => null,
                        'completed_at' => now(),
                    ]);
            } else {
                $lockedCurrent->update([
            'month' => $newValues['month'],
            'day' => $newValues['day'],
            'activity_date' => $newValues['activity_date'],
            'title' => $newValues['title'],
            'proposed_date' => $newValues['proposed_date'],
            'is_in_agenda' => $newValues['is_in_agenda'],
            'agenda_event_id' => $newValues['agenda_event_id'],
            'is_from_agenda' => $newValues['is_from_agenda'],
            'participation_status' => $newValues['participation_status'],
            'plan_type' => $newValues['plan_type'],
            'branch_plan_file' => $newValues['branch_plan_file'],
            'description' => $newValues['description'],
            'location_type' => $newValues['location_type'],
            'location_details' => $newValues['location_details'],
            'internal_location' => $newValues['internal_location'],
            'outside_place_name' => $newValues['outside_place_name'],
            'outside_google_maps_url' => $newValues['outside_google_maps_url'],
            'outside_contact_number' => $newValues['outside_contact_number'],
            'external_liaison_name' => $newValues['external_liaison_name'],
            'external_liaison_phone' => $newValues['external_liaison_phone'],
            'outside_address' => $newValues['outside_address'],
            'status' => $newValues['status'],
            'execution_status' => $newValues['execution_status'],
            'plan_stage' => $newValues['plan_stage'],
            'plan_version' => $newValues['plan_version'],
            'previous_version_id' => $newValues['previous_version_id'],
            'responsible_party' => $newValues['responsible_party'],
            'execution_time' => $newValues['execution_time'],
            'time_from' => $newValues['time_from'],
            'time_to' => $newValues['time_to'],
            'target_group' => $newValues['target_group'],
            'target_group_id' => $newValues['target_group_id'],
            'target_group_other' => $newValues['target_group_other'],
            'short_description' => $newValues['short_description'],
            'work_teams_count' => $newValues['work_teams_count'],
            'volunteer_need' => $newValues['volunteer_need'],
            'needs_volunteers' => $newValues['needs_volunteers'],
            'required_volunteers' => $newValues['required_volunteers'],
            'volunteer_age_range' => $newValues['volunteer_age_range'],
            'volunteer_gender' => $newValues['volunteer_gender'],
            'volunteer_tasks_summary' => $newValues['volunteer_tasks_summary'],
            'expected_attendance' => $newValues['expected_attendance'],
            'actual_attendance' => $newValues['actual_attendance'],
            'attendance_notes' => $newValues['attendance_notes'],
            'has_sponsor' => $newValues['has_sponsor'],
            'sponsor_name_title' => $newValues['sponsor_name_title'],
            'has_partners' => $newValues['has_partners'],
            'needs_official_letters' => $newValues['needs_official_letters'],
            'needs_official_correspondence' => $newValues['needs_official_correspondence'],
            'official_correspondence_reason' => $newValues['official_correspondence_reason'],
            'official_correspondence_target' => $newValues['official_correspondence_target'],
            'official_correspondence_brief' => $newValues['official_correspondence_brief'],
            'letter_purpose' => $newValues['letter_purpose'],
            'rescheduled_date' => $newValues['rescheduled_date'],
            'reschedule_reason' => $newValues['reschedule_reason'],
            'cancellation_reason' => $newValues['cancellation_reason'],
            'relations_approval_on_reschedule' => $newValues['relations_approval_on_reschedule'],
            'audience_satisfaction_percent' => $newValues['audience_satisfaction_percent'],
            'evaluation_score' => $newValues['evaluation_score'],
            'needs_media_coverage' => $newValues['needs_media_coverage'],
            'media_coverage_notes' => $newValues['media_coverage_notes'],
            'requires_programs' => $newValues['requires_programs'],
            'is_program_related' => $newValues['is_program_related'],
            'requires_workshops' => $newValues['requires_workshops'],
            'requires_communications' => $newValues['requires_communications'],
            'branch_id' => $newValues['branch_id'],
            'lifecycle_status' => $newValues['lifecycle_status'],
            'relations_officer_approval_status' => $newValues['relations_officer_approval_status'],
            'relations_manager_approval_status' => $newValues['relations_manager_approval_status'],
            'programs_officer_approval_status' => $newValues['programs_officer_approval_status'],
            'programs_manager_approval_status' => $newValues['programs_manager_approval_status'],
            'liaison_approval_status' => $newValues['liaison_approval_status'],
            'hq_relations_manager_approval_status' => $newValues['hq_relations_manager_approval_status'],
            'executive_approval_status' => $newValues['executive_approval_status'],
            'lock_at' => $this->buildLockAt($data['proposed_date']),
            'is_official' => $this->buildLockAt($data['proposed_date'])?->isPast() ?? false,
            ]);
                $activityToSave = $lockedCurrent;
            }
        });

        if ($startsNewVersion) {
            $workflowService->initializeDynamicStatuses($activityToSave);
        }
        $this->syncTargetGroups($activityToSave, $data);
        Log::info('monthly_activity.updated', [
            'monthly_activity_id' => $activityToSave->id,
            'updated_by' => $request->user()->id,
            'plan_version' => $activityToSave->plan_version,
            'new_version_created' => $startsNewVersion,
        ]);

        $this->syncSponsorsAndPartners($activityToSave, $data);
        if (($request->user()->hasRole('followup_officer') || $request->user()->hasRole('super_admin')) && $this->canSubmitPostEvaluation($activityToSave)) {
            $this->syncEvaluationData($activityToSave, $data, $request->user()->id);
        }
        $this->logChanges($activityToSave, $oldValues, $newValues, $request->user()->id);
        $this->logWorkflowAction($startsNewVersion ? 'new_version_created' : 'updated', $activityToSave, $request, $activityToSave->status, [
            'changed_fields' => $changedFields,
            'source_activity_id' => $startsNewVersion ? $monthlyActivity->id : null,
        ]);

        if ($this->shouldSubmitFromRequest($request)) {
            $this->submitActivityForApproval($activityToSave, $request->user(), $notifications, $lifecycle, $dynamicWorkflowService, $request);
        }

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.updated', ['activity' => $monthlyActivity->title]))
            ->with('warning', $conflictWarning);
    }

    public function submit(MonthlyActivity $monthlyActivity, NotificationService $notifications, MonthlyActivityLifecycleService $lifecycle, DynamicWorkflowService $dynamicWorkflowService)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());
        $actor = request()->user();

        if ($this->isReadOnlyUnifiedAgendaActivity($monthlyActivity)) {
            return redirect()
                ->route('role.relations.activities.show', $monthlyActivity)
                ->with('warning', 'هذه فعالية موحدة ومعتمدة ولا تحتاج إرسالًا للاعتماد.');
        }

        if ($this->isSupersededVersion($monthlyActivity)) {
            return back()->withErrors([
                'status' => 'هذه نسخة قديمة من النشاط ولا يمكن إرسالها للاعتماد.',
            ]);
        }

        $this->submitActivityForApproval($monthlyActivity, $actor, $notifications, $lifecycle, $dynamicWorkflowService, request());

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.submitted', ['activity' => $monthlyActivity->title]));
    }

    public function close(Request $request, MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycle)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, $request->user());

        if ($this->isReadOnlyUnifiedAgendaActivity($monthlyActivity)) {
            return redirect()
                ->route('role.relations.activities.show', $monthlyActivity)
                ->with('warning', 'هذه فعالية موحدة ومعتمدة ومخصصة للعرض فقط.');
        }

        if ($this->isSupersededVersion($monthlyActivity)) {
            return back()->withErrors([
                'status' => 'هذه نسخة قديمة من النشاط ولا يمكن إغلاقها.',
            ]);
        }

        $data = $request->validate([
            'actual_date' => ['nullable', 'date'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $monthlyActivity->update([
            'actual_date' => $data['actual_date'] ?? $monthlyActivity->actual_date,
            'actual_attendance' => $data['actual_attendance'] ?? $monthlyActivity->actual_attendance,
            'status' => $data['status'],
            'execution_status' => $data['status'] === 'executed' ? 'executed' : $monthlyActivity->execution_status,
            'is_official' => true,
        ]);

        if (($data['status'] ?? null) === 'executed') {
            $lifecycle->transitionOrFail($monthlyActivity, 'Executed');
        }

        $this->logWorkflowAction('closed', $monthlyActivity, $request, $data['status']);

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.closed', ['activity' => $monthlyActivity->title]));
    }

    public function calendar(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);
        $viewScope = $request->input('scope', 'default');

        if ($viewScope === 'all_branches' && ! $this->canViewOtherBranches($request->user())) {
            abort(403);
        }

        $query = MonthlyActivity::query()
            ->with(['branch', 'agendaEvent'])
            ->whereDoesntHave('newerVersions')
            ->notArchived();

        $query->where(function ($dateQuery) use ($year, $month) {
            $dateQuery
                ->where(function ($proposedDateQuery) use ($year, $month) {
                    $proposedDateQuery
                        ->whereNotNull('proposed_date')
                        ->whereYear('proposed_date', $year)
                        ->whereMonth('proposed_date', $month);
                })
                ->orWhere(function ($fallbackMonthQuery) use ($month) {
                    $fallbackMonthQuery
                        ->whereNull('proposed_date')
                        ->where('month', $month);
                });
        });

        if ($viewScope !== 'all_branches') {
            $this->applyBranchVisibilityScope($query, $request->user());
        }
        $this->applyDraftVisibilityScope($query, $request->user());

        if ($viewScope === 'all_branches') {
            $query
                ->where('status', 'approved')
                ->where(function ($approvalQuery) {
                    $approvalQuery
                        ->where('executive_approval_status', 'approved')
                        ->orWhereIn('lifecycle_status', ['Exec Director Approved', 'Approved', 'Published'])
                        ->orWhereHas('workflowInstance', fn ($workflowQuery) => $workflowQuery->where('status', 'approved'));
                });
        }

        $items = $query->orderBy('month')
            ->orderBy('day')
            ->orderBy('proposed_date')
            ->get()
            ->map(function (MonthlyActivity $activity) use ($year, $request) {
            $isReadOnlyUnified = $this->isReadOnlyUnifiedAgendaActivity($activity);
            $canBranchPartialEditUnified = $this->canBranchEditUnifiedNonCoreFields($activity, $request->user());

            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'date' => optional($activity->proposed_date)->format('Y-m-d')
                    ?? sprintf('%04d-%02d-%02d', $year, $activity->month, $activity->day),
                'branch' => $activity->branch?->name,
                'status' => $activity->status,
                'requires_workshops' => (bool) $activity->requires_workshops,
                'requires_communications' => (bool) $activity->requires_communications,
                'edit_url' => route('role.relations.activities.edit', $activity),
                'open_url' => ($isReadOnlyUnified && ! $canBranchPartialEditUnified)
                    ? route('role.relations.activities.show', $activity)
                    : route('role.relations.activities.edit', $activity),
                'read_only_unified' => $isReadOnlyUnified && ! $canBranchPartialEditUnified,
            ];
            })->values();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'items' => $items,
        ]);
    }
}
