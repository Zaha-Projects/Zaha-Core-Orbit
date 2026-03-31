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

        $branchId = (int) $request->input('branch_id', 0);
        if ($branchId > 0 && $branchId !== (int) $user->branch_id) {
            abort(403);
        }

        $request->merge(['branch_id' => $request->input('branch_id', $user->branch_id)]);

        return $next($request);
    }
}
