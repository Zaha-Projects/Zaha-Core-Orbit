<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\WorkflowNotificationService;
use App\Services\NotificationService;
use App\Services\MonthlyActivityLifecycleService;
use App\Services\DynamicWorkflowService;
use Illuminate\Support\Collection;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivities;

class MonthlyActivityLifecycleController extends Controller
{
    use InteractsWithMonthlyActivities;
    public function submit(MonthlyActivity $monthlyActivity, WorkflowNotificationService $workflowNotifications, MonthlyActivityLifecycleService $lifecycle, DynamicWorkflowService $dynamicWorkflowService)
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

        if (! $this->canSubmitActivityForApproval($monthlyActivity, $actor)) {
            return back()->with('warning', 'تم إرسال هذا النشاط للاعتماد مسبقًا أو أن حالته الحالية لا تسمح بإعادة الإرسال.');
        }

        $this->submitActivityForApproval($monthlyActivity, $actor, $workflowNotifications, $lifecycle, $dynamicWorkflowService, request());

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.submitted', ['activity' => $monthlyActivity->title]));
    }

    public function close(Request $request, MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycle)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, $request->user());
        $canCompleteAfterExecution = $this->canCompleteAfterExecution($monthlyActivity, $request->user());
        $canReviewPostExecution = $this->canReviewPostExecution($monthlyActivity, $request->user());

        abort_unless($canCompleteAfterExecution || $canReviewPostExecution, 403);

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
            ...$this->evaluationSummaryRules(),
            'actual_date' => ['nullable', 'date'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'execution_needs_followup' => ['nullable', 'array'],
            'execution_needs_followup.*.post_status' => ['nullable', 'in:provided,not_provided'],
            'execution_needs_followup.*.post_feedback' => ['nullable', 'string', 'max:2000'],
            'post_execution' => ['nullable', 'array'],
            'post_execution.teams' => ['nullable', 'array'],
            'post_execution.teams.*.team_name' => ['nullable', 'string', 'max:255'],
            'post_execution.teams.*.planned_members_count' => ['nullable', 'integer', 'min:0'],
            'post_execution.teams.*.all_members_attended' => ['nullable', 'in:1,0'],
            'post_execution.teams.*.actual_attendance_count' => ['nullable', 'integer', 'min:0'],
            'post_execution.teams.*.accomplished_tasks' => ['nullable', 'string', 'max:2000'],
            'post_execution.ceremony_items' => ['nullable', 'array'],
            'post_execution.ceremony_items.*.order' => ['nullable', 'integer', 'min:1'],
            'post_execution.ceremony_items.*.name' => ['nullable', 'string', 'max:255'],
            'post_execution.ceremony_items.*.was_implemented' => ['nullable', 'in:1,0'],
            'post_execution.ceremony_items.*.feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $activityForPostExecution = $monthlyActivity->fresh(['creator', 'supplies', 'team']);
        $this->normalizeExecutionNeedsFollowup($data, $activityForPostExecution);
        $this->filterExecutionNeedsFollowupToEnabled($activityForPostExecution, $data);
        $postExecutionPayload = $this->normalizePostExecutionPayload($activityForPostExecution, $data['post_execution'] ?? []);
        $executionNeedsFollowup = array_key_exists('execution_needs_followup', $data)
            ? $this->mergeExecutionNeedsFollowupRows($activityForPostExecution, $data['execution_needs_followup'] ?? [])
            : ($monthlyActivity->execution_needs_followup ?? null);

        $notificationService = app(NotificationService::class);

        if (! $canReviewPostExecution) {
            $monthlyActivity->update([
                'actual_date' => $data['actual_date'] ?? $monthlyActivity->actual_date,
                'actual_attendance' => $data['actual_attendance'] ?? $monthlyActivity->actual_attendance,
                'execution_needs_followup' => $executionNeedsFollowup === [] ? null : $executionNeedsFollowup,
                'post_execution_payload' => $postExecutionPayload,
                'status' => 'post_execution_submitted',
                'execution_status' => 'executed',
                'is_official' => true,
            ]);

            $supervisors = $this->branchScopedRoleUsers('supervisor', (int) $monthlyActivity->branch_id);
            $notificationService->notifyUsers(
                $supervisors,
                'monthly_post_execution_submitted',
                'تم إرسال ما بعد التنفيذ لاعتماد رئيس الفرع',
                "تم إرسال إكمال ما بعد التنفيذ للنشاط ({$monthlyActivity->title}) بانتظار اعتماد رئيس الفرع.",
                route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity->id, 'mode' => 'post']),
                ['monthly_activity_id' => $monthlyActivity->id]
            );

            $this->logWorkflowAction('post_execution_submitted', $monthlyActivity, $request, 'post_execution_submitted');

            return redirect()
                ->route('role.relations.activities.index')
                ->with('status', 'تم إرسال ما بعد التنفيذ لاعتماد رئيس الفرع.');
        }

        $evaluationOfficer = $this->branchScopedRoleUsers('evaluation_officer', (int) $monthlyActivity->branch_id)->first();
        $followupUsers = $this->branchScopedRoleUsers('followup_officer', (int) $monthlyActivity->branch_id);

        $monthlyActivity->update([
            'actual_date' => $data['actual_date'] ?? $monthlyActivity->actual_date,
            'actual_attendance' => $data['actual_attendance'] ?? $monthlyActivity->actual_attendance,
            'evaluation_score' => $data['evaluation_score'] ?? $monthlyActivity->evaluation_score,
            'evaluation_reason' => $data['evaluation_reason'] ?? $monthlyActivity->evaluation_reason,
            'evaluation_assigned_user_id' => $evaluationOfficer?->id,
            'evaluation_assigned_at' => $evaluationOfficer ? now() : null,
            'execution_needs_followup' => $executionNeedsFollowup === [] ? null : $executionNeedsFollowup,
            'post_execution_payload' => $postExecutionPayload,
            'status' => 'closed',
            'execution_status' => 'executed',
            'is_official' => true,
        ]);

        $notificationService->notifyUsers(
            $followupUsers,
            'monthly_post_execution_approved_followup',
            'تم اعتماد ما بعد التنفيذ للمتابعة',
            "تم اعتماد ما بعد التنفيذ للنشاط ({$monthlyActivity->title}) من رئيس الفرع وأصبح جاهزًا للمتابعة.",
            route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity->id, 'mode' => 'post']),
            ['monthly_activity_id' => $monthlyActivity->id]
        );

        if ($evaluationOfficer) {
            $notificationService->notifyUsers(
                collect([$evaluationOfficer]),
                'monthly_post_execution_approved_evaluation',
                'تم تحويل نشاط للتقييم',
                "تم اعتماد ما بعد التنفيذ للنشاط ({$monthlyActivity->title}) وتحويله إليك للتقييم.",
                route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity->id, 'mode' => 'post']),
                ['monthly_activity_id' => $monthlyActivity->id]
            );
        }

        $this->closeLifecycle($monthlyActivity, $lifecycle);

        $this->logWorkflowAction('closed', $monthlyActivity, $request, 'closed', [
            'evaluation_score' => $monthlyActivity->evaluation_score,
            'post_execution_approved_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.closed', ['activity' => $monthlyActivity->title]));
    }

    protected function closeLifecycle(MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycle): void
    {
        foreach (['Executed', 'Evaluated', 'Closed'] as $target) {
            $monthlyActivity->refresh();

            if ((string) $monthlyActivity->lifecycle_status === 'Closed') {
                return;
            }

            if ($lifecycle->canTransition((string) $monthlyActivity->lifecycle_status, $target)) {
                $lifecycle->transitionOrFail($monthlyActivity, $target);
            }
        }

        $monthlyActivity->refresh();

        if ((string) $monthlyActivity->lifecycle_status !== 'Closed') {
            $monthlyActivity->update(['lifecycle_status' => 'Closed']);
        }
    }

    protected function branchScopedRoleUsers(string $role, ?int $branchId): Collection
    {
        if (! $branchId) {
            return collect();
        }

        return User::role($role)
            ->where('status', 'active')
            ->where(function ($query) use ($branchId): void {
                $query->whereHas('assignedBranches', fn ($branchQuery) => $branchQuery->whereKey($branchId))
                    ->orWhere(function ($fallbackQuery) use ($branchId): void {
                        $fallbackQuery
                            ->whereDoesntHave('assignedBranches')
                            ->where('branch_id', $branchId);
                    });
            })
            ->get();
    }

    protected function normalizePostExecutionPayload(MonthlyActivity $monthlyActivity, array $rows): ?array
    {
        $teamRows = collect($rows['teams'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row, $key): ?array {
                $teamName = trim((string) ($row['team_name'] ?? $key));
                $actualAttendance = $row['actual_attendance_count'] ?? null;
                $actualAttendance = $actualAttendance === '' || $actualAttendance === null ? null : max(0, (int) $actualAttendance);
                $allAttended = $row['all_members_attended'] ?? null;
                $allAttended = in_array((string) $allAttended, ['1', '0'], true) ? (bool) $allAttended : null;
                $plannedMembers = $row['planned_members_count'] ?? null;
                $plannedMembers = $plannedMembers === '' || $plannedMembers === null ? null : max(0, (int) $plannedMembers);
                $tasks = trim((string) ($row['accomplished_tasks'] ?? ''));

                if ($teamName === '' && $actualAttendance === null && $allAttended === null && $tasks === '') {
                    return null;
                }

                return [
                    'team_name' => $teamName,
                    'planned_members_count' => $plannedMembers,
                    'all_members_attended' => $allAttended,
                    'actual_attendance_count' => $actualAttendance,
                    'accomplished_tasks' => $tasks !== '' ? $tasks : null,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $ceremonyItems = collect($rows['ceremony_items'] ?? [])
            ->filter(fn ($row) => is_array($row))
            ->map(function (array $row, $key): ?array {
                $name = trim((string) ($row['name'] ?? ''));
                $implemented = $row['was_implemented'] ?? null;
                $implemented = in_array((string) $implemented, ['1', '0'], true) ? (bool) $implemented : null;
                $feedback = trim((string) ($row['feedback'] ?? ''));
                $order = $row['order'] ?? null;
                $order = $order === '' || $order === null ? ((int) $key + 1) : (int) $order;

                if ($name === '' && $implemented === null && $feedback === '') {
                    return null;
                }

                return [
                    'order' => $order,
                    'name' => $name,
                    'was_implemented' => $implemented,
                    'feedback' => $feedback !== '' ? $feedback : null,
                ];
            })
            ->filter()
            ->values()
            ->all();

        if ($teamRows === [] && $ceremonyItems === []) {
            return $monthlyActivity->post_execution_payload ?: null;
        }

        return [
            'schema_version' => 1,
            'completed_at' => now()->toDateTimeString(),
            'teams' => $teamRows,
            'ceremony_items' => $ceremonyItems,
        ];
    }
}
