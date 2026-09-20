<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Modules\Events\Support\EventAggregateIdentity;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\WorkflowActionLog;
use Illuminate\Http\Request;
use App\Services\PlanChangeRequestWorkflowService;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivities;

class MonthlyActivityTrashController extends Controller
{
    use InteractsWithMonthlyActivities;
    public function trash(Request $request)
    {
        $user = $request->user();
        $selectedBranchId = filter_var($request->input('branch_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
        $selectedYear = $this->normalizeMonthlyIndexYear($request->input('year'));
        $selectedMonth = $this->normalizeMonthlyIndexMonth($request->input('month'));

        $activitiesQuery = MonthlyActivity::query()
            ->onlyTrashed()
            ->whereDoesntHave('newerVersions')
            ->notArchived();

        $this->applyMonthlyPageMonthFilter($activitiesQuery, $selectedYear, $selectedMonth);
        if ($selectedBranchId) {
            $activitiesQuery->where('branch_id', $selectedBranchId);
        }
        $this->applyBranchVisibilityScope($activitiesQuery, $user);

        $activities = $activitiesQuery
            ->with([
                'branch',
                'creator',
                'deleteRequests.requester',
                'deleteRequests.currentApprover',
            ])
            ->orderByDesc('deleted_at')
            ->paginate(12)
            ->withQueryString();

        $deletedBy = WorkflowActionLog::query()
            ->with('performer')
            ->where('module', 'monthly_activities')
            ->whereIn('entity_type', EventAggregateIdentity::acceptedTypes(MonthlyActivity::class))
            ->where('action_type', 'deleted')
            ->whereIn('entity_id', $activities->getCollection()->pluck('id')->all())
            ->orderByDesc('performed_at')
            ->get()
            ->unique('entity_id')
            ->keyBy('entity_id');

        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $this->scopedBranchIds($user);
        $ownBranchId = $this->ownBranchId($user);
        if ($scopedBranchIds !== []) {
            $branches->where('id', $ownBranchId);
        }

        $filters = [
            'branch_id' => $selectedBranchId,
            'year' => $selectedYear,
            'month' => $selectedMonth,
        ];

        return view('pages.monthly_activities.activities.trash', [
            'activities' => $activities,
            'branches' => $branches->get(),
            'deletedBy' => $deletedBy,
            'filters' => $filters,
        ]);
    }

    public function restore(int $monthlyActivity)
    {
        $activity = MonthlyActivity::onlyTrashed()->findOrFail($monthlyActivity);
        $this->ensureActivityVisibleToUser($activity, request()->user());

        $activity->restore();

        $activity->forceFill([
            'status' => $activity->status === 'cancelled' ? 'draft' : $activity->status,
            'execution_status' => $activity->execution_status === 'cancelled' ? null : $activity->execution_status,
        ])->save();

        return redirect()
            ->route('role.relations.activities.trash', request()->only(['branch_id', 'year', 'month']))
            ->with('status', 'تمت استعادة الخطة الشهرية بنجاح.');
    }

    public function destroy(Request $request, MonthlyActivity $monthlyActivity, PlanChangeRequestWorkflowService $changeRequests)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, $request->user());

        if (! $this->canManageMonthlyActivityChangeRequest($request->user(), $monthlyActivity)) {
            abort(403);
        }

        if ($this->isSupersededVersion($monthlyActivity)) {
            return back()->withErrors(['status' => 'لا يمكن حذف نسخة قديمة من الخطة.']);
        }

        if ($changeRequests->hasActiveMonthlyChangeRequest($monthlyActivity)) {
            return back()->withErrors(['status' => 'يوجد طلب حذف أو تعديل نشط لهذه الخطة الشهرية. لا يمكن حذف النشاط حتى يتم اعتماد الطلب أو رفضه.']);
        }

        if ($this->hasManagerOrLaterApproval($monthlyActivity)) {
            $data = $request->validate([
                'delete_reason' => ['required', 'string', 'max:2000'],
            ]);

            $changeRequests->startMonthlyDeleteRequest($monthlyActivity, $request->user(), $data['delete_reason']);

            return redirect()
                ->route('role.relations.activities.index')
                ->with('status', 'تم إنشاء طلب حذف للخطة الشهرية وإرساله للاعتماد.');
        }

        $monthlyActivity->forceFill([
            'status' => 'cancelled',
            'execution_status' => 'cancelled',
        ])->save();
        $monthlyActivity->delete();

        $this->logWorkflowAction('deleted', $monthlyActivity, $request, 'deleted');

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', 'تم حذف الخطة الشهرية بنجاح.');
    }
}
