<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use App\Models\WorkflowActionLog;
use Illuminate\Http\Request;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivityApprovals;

class MonthlyActivityPostExecutionDecisionController extends Controller
{
    use InteractsWithMonthlyActivityApprovals;
    public function decidePostExecution(Request $request, MonthlyActivity $monthlyActivity, NotificationService $notifications)
    {
        $viewer = $request->user();
        $this->abortIfProgramsManagerViewOnly($viewer);

        abort_unless($this->canReviewPostExecution($monthlyActivity, $viewer), 403);

        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,clarification,rejected'],
            'comment' => ['nullable', 'string', 'max:2000', 'required_unless:decision,approved'],
        ]);

        if ($data['decision'] === 'approved') {
            return redirect()
                ->route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'mode' => 'post'])
                ->with('status', 'للاعتماد النهائي وإغلاق الفرصة يرجى استخدام نموذج اعتماد ما بعد التنفيذ.');
        }

        $payload = $monthlyActivity->post_execution_payload ?? [];
        $payload['review'] = [
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'reviewed_by' => $viewer?->id,
            'reviewed_by_name' => $viewer?->name,
            'reviewed_at' => now()->toDateTimeString(),
        ];

        $status = $data['decision'] === 'clarification' ? 'changes_requested' : 'rejected';
        $decisionComment = $data['comment'] ?? null;

        $monthlyActivity->update([
            'status' => $status,
            'post_execution_payload' => $payload,
        ]);

        MonthlyActivityApproval::query()->create([
            'monthly_activity_id' => $monthlyActivity->id,
            'step' => 'post_execution_review',
            'decision' => $data['decision'],
            'comment' => $decisionComment,
            'approved_by' => $viewer->id,
            'approved_at' => now(),
        ]);

        WorkflowActionLog::query()->create([
            'module' => 'monthly_activities',
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $monthlyActivity->id,
            'action_type' => 'post_execution_'.$data['decision'],
            'status' => $status,
            'performed_by' => $viewer->id,
            'notes' => $data['comment'] ?? null,
            'meta' => [
                'status' => $status,
                'comment' => $data['comment'] ?? null,
            ],
            'performed_at' => now(),
        ]);

        $volunteerCoordinators = $this->branchScopedRoleUsers('volunteer_coordinator', (int) $monthlyActivity->branch_id);
        $title = $data['decision'] === 'clarification'
            ? 'مطلوب توضيح على ما بعد التنفيذ'
            : 'تم رفض ما بعد التنفيذ';
        $message = $data['decision'] === 'clarification'
            ? "طلب رئيس الفرع توضيحًا على ما بعد التنفيذ للنشاط ({$monthlyActivity->title})."
            : "تم رفض ما بعد التنفيذ للنشاط ({$monthlyActivity->title}) ويجب إعادة تعبئة ما بعد التنفيذ.";

        $notifications->notifyUsers(
            $volunteerCoordinators,
            'monthly_post_execution_'.$data['decision'],
            $title,
            trim($message.' السبب: '.($data['comment'] ?? '-')),
            route('role.relations.activities.post_execution_feedback'),
            ['monthly_activity_id' => $monthlyActivity->id, 'decision' => $data['decision']]
        );

        return redirect()
            ->route('role.programs.approvals.index', ['tab' => 'post_execution'])
            ->with('status', $data['decision'] === 'clarification' ? 'تم طلب التوضيح وإشعار مسؤول التطوع بالفرع.' : 'تم رفض ما بعد التنفيذ وإشعار مسؤول التطوع بالفرع.');
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
}
