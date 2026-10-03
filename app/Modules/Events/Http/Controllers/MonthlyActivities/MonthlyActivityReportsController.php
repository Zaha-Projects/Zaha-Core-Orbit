<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Models\Branch;
use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Models\MonthlyPlanDeleteRequest;
use App\Modules\Events\Models\MonthlyPlanEditRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivities;

class MonthlyActivityReportsController extends Controller
{
    use InteractsWithMonthlyActivities;
    public function changeRequestReports(Request $request)
    {
        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'request_type' => ['nullable', 'in:delete,edit'],
            'status' => ['nullable', 'string', 'max:50'],
            'current_step' => ['nullable', 'string', 'max:100'],
            'requester_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $applyFilters = function (Builder $query) use ($filters): Builder {
            return $query
                ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
                ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
                ->when($filters['requester_id'] ?? null, fn ($query, $requesterId) => $query->where('requester_id', $requesterId))
                ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('requested_at', '>=', $date))
                ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('requested_at', '<=', $date))
                ->when($filters['current_step'] ?? null, fn ($query, $step) => $query->whereHas(
                    'workflowInstance.currentStep',
                    fn ($stepQuery) => $stepQuery->where('step_key', $step)
                ));
        };

        $deleteBase = $applyFilters(MonthlyPlanDeleteRequest::query());
        $editBase = $applyFilters(MonthlyPlanEditRequest::query());

        if (($filters['request_type'] ?? null) === 'delete') {
            $editBase->whereRaw('1 = 0');
        } elseif (($filters['request_type'] ?? null) === 'edit') {
            $deleteBase->whereRaw('1 = 0');
        }

        $statistics = [
            'total_delete_requests' => (clone $deleteBase)->count(),
            'pending_delete_requests' => (clone $deleteBase)->where('status', 'pending')->count(),
            'approved_delete_requests' => (clone $deleteBase)->where('status', 'approved')->count(),
            'rejected_delete_requests' => (clone $deleteBase)->where('status', 'rejected')->count(),
            'total_edit_requests' => (clone $editBase)->count(),
            'pending_edit_requests' => (clone $editBase)->where('status', 'pending')->count(),
            'approved_edit_requests' => (clone $editBase)->where('status', 'approved')->count(),
            'rejected_edit_requests' => (clone $editBase)->where('status', 'rejected')->count(),
            'soft_deleted_monthly_activities' => MonthlyActivity::onlyTrashed()->count(),
            'activities_with_versions' => MonthlyActivity::query()->whereHas('childVersions')->count(),
        ];

        $recentDeleteRequests = (clone $deleteBase)
            ->with(['requester', 'currentApprover', 'monthlyActivity.branch', 'workflowInstance.currentStep'])
            ->latest('requested_at')
            ->take(10)
            ->get();
        $recentEditRequests = (clone $editBase)
            ->with(['requester', 'currentApprover', 'monthlyActivity.branch', 'workflowInstance.currentStep'])
            ->latest('requested_at')
            ->take(10)
            ->get();
        $requestsByBranch = collect([
            'delete' => (clone $deleteBase)->with('monthlyActivity.branch')->get(),
            'edit' => (clone $editBase)->with('monthlyActivity.branch')->get(),
        ])->flatten(1)
            ->groupBy(fn ($changeRequest) => $changeRequest->monthlyActivity?->branch?->name ?? 'غير محدد')
            ->map->count();
        $requestsByStatus = collect([
            'delete' => (clone $deleteBase)->get(),
            'edit' => (clone $editBase)->get(),
        ])->flatten(1)->groupBy('status')->map->count();
        $requestsByStep = collect([
            'delete' => (clone $deleteBase)->with(['workflowInstance.currentStep', 'currentApprover'])->get(),
            'edit' => (clone $editBase)->with(['workflowInstance.currentStep', 'currentApprover'])->get(),
        ])->flatten(1)
            ->groupBy(fn ($changeRequest) => $changeRequest->workflowInstance?->currentStep?->name_ar
                ?? $changeRequest->currentApprover?->name
                ?? 'لا يوجد')
            ->map->count();

        $branches = Branch::query()->orderBy('name')->get();
        $requesterIds = collect([
            (clone $deleteBase)->pluck('requester_id'),
            (clone $editBase)->pluck('requester_id'),
        ])->flatten()->filter()->unique();
        $requesters = User::query()->whereIn('id', $requesterIds)->orderBy('name')->get();

        return view('pages.monthly_activities.reports.change_requests', compact(
            'statistics',
            'recentDeleteRequests',
            'recentEditRequests',
            'requestsByBranch',
            'requestsByStatus',
            'requestsByStep',
            'branches',
            'requesters',
            'filters'
        ));
    }
}
