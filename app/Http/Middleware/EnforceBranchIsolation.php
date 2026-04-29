<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnforceBranchIsolation
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || empty($user->branch_id)) {
            return $next($request);
        }

        $isScoped = (method_exists($user, 'hasBranchScopedMonthlyVisibility') && $user->hasBranchScopedMonthlyVisibility())
            || (method_exists($user, 'hasBranchScopedAgendaVisibility') && $user->hasBranchScopedAgendaVisibility());

        if (! $isScoped) {
            return $next($request);
        }

        if ($request->routeIs('role.relations.agenda.*')) {
            return $next($request);
        }

        if ($request->input('scope') === 'all_branches' && $user->can('monthly_activities.view_other_branches')) {
            return $next($request);
        }

        $allowedBranchIds = method_exists($user, 'scopedBranchIds')
            ? $user->scopedBranchIds()
            : (filled($user->branch_id) ? [(int) $user->branch_id] : []);

        $branchId = (int) $request->input('branch_id', 0);
        if ($branchId > 0 && ! in_array($branchId, $allowedBranchIds, true)) {
            abort(403);
        }

        if ($branchId <= 0 && count($allowedBranchIds) === 1) {
            $request->merge(['branch_id' => $allowedBranchIds[0]]);
        }

        return $next($request);
    }
}
