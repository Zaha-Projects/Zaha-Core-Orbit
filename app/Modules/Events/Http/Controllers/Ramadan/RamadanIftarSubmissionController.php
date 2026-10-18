<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarSubmissionService;
use Illuminate\Http\Request;

class RamadanIftarSubmissionController extends Controller
{
    public function submit(Request $request, RamadanIftar $ramadanIftar, RamadanIftarSubmissionService $submissions)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.submit')), 403);
        abort_unless($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $ramadanIftar->branch_id), 403);

        $submissions->submitAndNotify($ramadanIftar, $user);

        return redirect()->route('dashboard')
            ->with('success', __('ramadan_iftars.messages.submitted'));
    }
}
