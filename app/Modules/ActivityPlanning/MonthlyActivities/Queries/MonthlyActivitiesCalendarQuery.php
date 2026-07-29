<?php

namespace App\Modules\ActivityPlanning\MonthlyActivities\Queries;

use App\Models\MonthlyActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class MonthlyActivitiesCalendarQuery
{
    private MonthlyActivitiesIndexQuery $indexQuery;
    private MonthlyActivityVisibilityQuery $visibility;

    public function __construct(MonthlyActivitiesIndexQuery $indexQuery, MonthlyActivityVisibilityQuery $visibility)
    {
        $this->indexQuery = $indexQuery;
        $this->visibility = $visibility;
    }

    public function build(Request $request, MonthlyActivityListFilters $filters): Builder
    {
        $user = $request->user();

        if ($filters->viewScope === 'all_branches' && ! $this->visibility->canViewOtherBranches($user)) {
            abort(403);
        }

        $query = MonthlyActivity::query()
            ->with(['branch', 'agendaEvent'])
            ->whereDoesntHave('newerVersions')
            ->notArchived();

        $this->indexQuery->applyMonth($query, $filters->year, $filters->month);

        if ($filters->branchId) {
            $query->where('branch_id', $filters->branchId);
        }

        if ($filters->viewScope !== 'all_branches') {
            $this->visibility->applyDefault($query, $user);
        }

        $this->visibility->applyDrafts($query, $user);
        $this->visibility->applyVolunteerCoordinator($query, $user);
        $this->indexQuery->applyStatus($query, $filters->status);

        if ($filters->viewScope === 'all_branches') {
            $this->indexQuery->applyPublishedOtherBranches($query, $user);
        }

        return $query;
    }
}
