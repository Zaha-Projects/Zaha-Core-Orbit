<?php

namespace App\Policies;

use App\Models\ActivityEvaluation;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Support\EvaluationVisibility;

class MonthlyActivityEvaluationPolicy
{
    public function viewActivity(User $user, MonthlyActivity $activity): bool
    {
        if ($user->can('evaluation.view_all')) return true;
        return $user->can('evaluation.view_branch') && $user->hasAccessToScopedBranch($activity->branch_id);
    }

    public function verify(User $user, MonthlyActivity $activity): bool
    {
        return $user->can('post_execution.verify_branch') && $user->hasRole('followup_officer') && $user->hasAccessToScopedBranch($activity->branch_id);
    }

    public function submit(User $user, MonthlyActivity $activity): bool
    {
        return $user->can('evaluation.submit_branch') && $user->hasRole('followup_officer') && $user->hasAccessToScopedBranch($activity->branch_id);
    }

    public function view(User $user, ActivityEvaluation $evaluation): bool
    {
        if ($user->can('evaluation.view_all')) return true;
        if (! $user->can('evaluation.view_branch')) return false;
        return $evaluation->visibility === EvaluationVisibility::AUTHORIZED_USERS || $user->hasAccessToScopedBranch($evaluation->branch_id);
    }

    public function updateVisibility(User $user, ActivityEvaluation $evaluation): bool
    {
        return $user->can('evaluation.visibility.manage') && ($user->hasRole('relations_manager') || $user->hasAccessToScopedBranch($evaluation->branch_id));
    }
}
