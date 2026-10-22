<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Models\MonthlyPlanDeleteRequest;
use App\Modules\Events\Models\MonthlyPlanEditRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivities;

class MonthlyActivityFeedbackController extends Controller
{
    use InteractsWithMonthlyActivities;
    public function returnedFeedback(Request $request)
    {
        $viewer = $request->user();
        abort_unless($viewer?->hasAnyRole(['relations_manager', 'relations_officer', 'super_admin']), 403);

        $branchIds = null;
        if (! $viewer->hasRole('super_admin')) {
            $branchIds = method_exists($viewer, 'approvalBranchIds') ? $viewer->approvalBranchIds() : [];
            if ($branchIds === []) {
                $branchIds = filled($viewer->branch_id) ? [(int) $viewer->branch_id] : [];
            }
        }

        $activityId = filter_var($request->input('activity_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
        $type = $request->input('type', 'all');
        $applyBranchScope = function ($query) use ($branchIds): void {
            if ($branchIds === null) {
                return;
            }

            $branchIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchIds);
        };
        $latestHistory = static function (?array $history): array {
            return collect($history ?? [])->sortByDesc(fn ($row) => $row['decided_at'] ?? $row['acted_at'] ?? $row['created_at'] ?? '')->first() ?? [];
        };

        $approvalReturns = MonthlyActivity::query()
            ->with(['branch', 'creator', 'approvals' => fn ($query) => $query->with('approver')->latest('approved_at')])
            ->whereDoesntHave('newerVersions')
            ->whereIn('status', ['changes_requested', 'rejected'])
            ->when($activityId, fn ($query) => $query->whereKey($activityId))
            ->tap($applyBranchScope)
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->map(function (MonthlyActivity $activity) {
                $approval = $activity->approvals->first(fn ($row) => in_array((string) $row->decision, ['changes_requested', 'rejected'], true))
                    ?? $activity->approvals->first();

                return [
                    'type' => 'approval',
                    'type_label' => 'اعتماد الخطة',
                    'title' => $activity->title,
                    'branch' => $activity->branch?->name,
                    'status' => $activity->status,
                    'reason' => $approval?->comment,
                    'actor' => $approval?->approver?->name,
                    'date' => $approval?->approved_at ?? $activity->updated_at,
                    'url' => route('role.relations.activities.show', $activity),
                ];
            });

        $deleteReturns = MonthlyPlanDeleteRequest::query()
            ->with(['requester', 'monthlyActivity.branch'])
            ->where('status', 'rejected')
            ->when($activityId, fn ($query) => $query->where('entity_id', $activityId))
            ->tap($applyBranchScope)
            ->latest('decided_at')
            ->limit(50)
            ->get()
            ->map(function (MonthlyPlanDeleteRequest $deleteRequest) use ($latestHistory) {
                $history = $latestHistory($deleteRequest->approval_history);
                $activity = $deleteRequest->monthlyActivity;

                return [
                    'type' => 'delete',
                    'type_label' => 'طلب حذف مرفوض',
                    'title' => $activity?->title ?? '#'.$deleteRequest->entity_id,
                    'branch' => $activity?->branch?->name,
                    'status' => $deleteRequest->status,
                    'reason' => $history['comment'] ?? null,
                    'actor' => $history['approver_name'] ?? $history['actor_name'] ?? null,
                    'date' => $deleteRequest->decided_at ?? $deleteRequest->updated_at,
                    'url' => $activity ? route('role.relations.activities.show', $activity) : '#',
                ];
            });

        $editReturns = MonthlyPlanEditRequest::query()
            ->with(['requester', 'monthlyActivity.branch'])
            ->where('status', 'rejected')
            ->when($activityId, fn ($query) => $query->where('entity_id', $activityId))
            ->tap($applyBranchScope)
            ->latest('decided_at')
            ->limit(50)
            ->get()
            ->map(function (MonthlyPlanEditRequest $editRequest) use ($latestHistory) {
                $history = $latestHistory($editRequest->approval_history);
                $activity = $editRequest->monthlyActivity;

                return [
                    'type' => 'edit',
                    'type_label' => 'طلب تعديل مرفوض',
                    'title' => $activity?->title ?? '#'.$editRequest->entity_id,
                    'branch' => $activity?->branch?->name,
                    'status' => $editRequest->status,
                    'reason' => $history['comment'] ?? null,
                    'actor' => $history['approver_name'] ?? $history['actor_name'] ?? null,
                    'date' => $editRequest->decided_at ?? $editRequest->updated_at,
                    'url' => $activity ? route('role.relations.activities.show', $activity) : '#',
                ];
            });

        $postExecutionReturns = MonthlyActivity::query()
            ->with(['branch', 'creator'])
            ->whereIn('status', ['changes_requested', 'rejected'])
            ->whereNotNull('post_execution_payload')
            ->where(function ($query): void {
                $query->where('post_execution_payload->review->decision', 'clarification')
                    ->orWhere('post_execution_payload->review->decision', 'rejected');
            })
            ->when($activityId, fn ($query) => $query->whereKey($activityId))
            ->tap($applyBranchScope)
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->map(fn (MonthlyActivity $activity) => [
                'type' => 'post_execution',
                'type_label' => 'ما بعد التنفيذ',
                'title' => $activity->title,
                'branch' => $activity->branch?->name,
                'status' => data_get($activity->post_execution_payload, 'review.decision', $activity->status),
                'reason' => data_get($activity->post_execution_payload, 'review.comment'),
                'actor' => data_get($activity->post_execution_payload, 'review.reviewed_by_name'),
                'date' => data_get($activity->post_execution_payload, 'review.reviewed_at') ?: $activity->updated_at,
                'url' => route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post']),
            ]);

        $executionNeedReturns = MonthlyActivity::query()
            ->with(['branch', 'creator'])
            ->whereNotNull('execution_needs_followup')
            ->when($activityId, fn ($query) => $query->whereKey($activityId))
            ->tap($applyBranchScope)
            ->latest('updated_at')
            ->limit(50)
            ->get()
            ->flatMap(function (MonthlyActivity $activity) {
                return collect($activity->execution_needs_followup ?? [])
                    ->where('status', 'not_secured')
                    ->map(fn ($need) => [
                        'type' => 'execution_need',
                        'type_label' => 'احتياج تنفيذ مرفوض',
                        'title' => $activity->title.' - '.($need['key'] ?? 'احتياج'),
                        'branch' => $activity->branch?->name,
                        'status' => $need['status'] ?? 'not_secured',
                        'reason' => $need['notes'] ?? $need['reason'] ?? null,
                        'actor' => $need['decision_by_name'] ?? null,
                        'date' => $activity->updated_at,
                        'url' => route('role.relations.activities.show', $activity).'#execution-needs-summary',
                    ]);
            });

        $items = $approvalReturns
            ->merge($deleteReturns)
            ->merge($editReturns)
            ->merge($postExecutionReturns)
            ->merge($executionNeedReturns)
            ->when($type !== 'all', fn ($collection) => $collection->where('type', $type))
            ->sortByDesc(fn ($item) => (string) ($item['date'] ?? ''))
            ->values();

        $counts = [
            'all' => $items->count(),
            'approval' => $approvalReturns->count(),
            'delete' => $deleteReturns->count(),
            'edit' => $editReturns->count(),
            'execution_need' => $executionNeedReturns->count(),
            'post_execution' => $postExecutionReturns->count(),
        ];

        return view('pages.monthly_activities.activities.returned-feedback', compact('items', 'counts', 'type', 'activityId'));
    }

    public function postExecutionFeedback(Request $request)
    {
        $viewer = $request->user();
        abort_unless($viewer?->hasAnyRole(['volunteer_coordinator', 'super_admin']), 403);

        $activities = MonthlyActivity::query()
            ->with(['branch', 'creator'])
            ->whereIn('status', ['changes_requested', 'rejected'])
            ->whereNotNull('post_execution_payload')
            ->where(function ($query): void {
                $query->where('post_execution_payload->review->decision', 'clarification')
                    ->orWhere('post_execution_payload->review->decision', 'rejected');
            })
            ->when(! $viewer->hasRole('super_admin'), function ($query) use ($viewer): void {
                $branchIds = method_exists($viewer, 'approvalBranchIds') ? $viewer->approvalBranchIds() : [];
                if ($branchIds === []) {
                    $branchIds = filled($viewer->branch_id) ? [(int) $viewer->branch_id] : [];
                }

                $branchIds === [] ? $query->whereRaw('1 = 0') : $query->whereIn('branch_id', $branchIds);
            })
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('pages.monthly_activities.activities.post-execution-feedback', compact('activities'));
    }
}
