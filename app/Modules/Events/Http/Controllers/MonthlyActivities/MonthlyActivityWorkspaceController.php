<?php

namespace App\Modules\Events\Http\Controllers\MonthlyActivities;

use App\Modules\Events\Models\MonthlyActivity;
use App\Services\MonthlyWorkflowPresenter;
use App\Services\PlanChangeRequestWorkflowService;
use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Controllers\MonthlyActivities\Concerns\InteractsWithMonthlyActivities;

class MonthlyActivityWorkspaceController extends Controller
{
    use InteractsWithMonthlyActivities;
    public function showDeleted(int $monthlyActivity, MonthlyWorkflowPresenter $monthlyWorkflowPresenter, PlanChangeRequestWorkflowService $changeRequests)
    {
        abort(404);
    }

    public function show(MonthlyActivity $monthlyActivity, MonthlyWorkflowPresenter $monthlyWorkflowPresenter, PlanChangeRequestWorkflowService $changeRequests)
    {
        abort_if(method_exists($monthlyActivity, 'trashed') && $monthlyActivity->trashed(), 404);

        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());

        $monthlyActivity->load(array_merge(
            $this->monthlyActivityWorkflowViewRelations(),
            ['attachments.uploader']
        ))
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

        $activeChangeRequestData = $this->activeMonthlyChangeRequestViewData($monthlyActivity, $changeRequests);

        return view('pages.monthly_activities.activities.show', array_merge(
            compact('monthlyActivity', 'monthlyStatusLabels', 'executionStatusLabels', 'archivedVersions'),
            $activeChangeRequestData
        ));
    }
}
