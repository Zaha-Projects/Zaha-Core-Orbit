<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarClosureService;
use Illuminate\Http\Request;

class RamadanIftarClosureController extends Controller
{
    public function store(Request $request, RamadanIftar $ramadanIftar, RamadanIftarClosureService $closure)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || ($user->hasRole('supervisor') && $user->can('ramadan_iftars.close'))), 403);
        abort_unless($user->hasRole('super_admin') || $user->hasAccessToScopedBranch((int) $ramadanIftar->branch_id), 403);

        $closure->close($ramadanIftar, $user);

        return redirect()->route('events.ramadan.iftars.show', $ramadanIftar)
            ->with('success', __('ramadan_iftars.messages.closed'));
    }
}
