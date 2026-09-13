<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Requests\Ramadan\StoreRamadanIftarChangeRequest;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarChangeRequestService;
use Illuminate\Http\Request;

class RamadanIftarChangeRequestController extends Controller
{
    public function create(Request $request, RamadanIftar $ramadanIftar)
    {
        $this->authorizeSource($request, $ramadanIftar);
        abort_unless($ramadanIftar->status === RamadanIftar::STATUS_APPROVED && $ramadanIftar->execution_status === RamadanIftar::EXECUTION_STATUS_PLANNED && $ramadanIftar->closed_at === null && ! $ramadanIftar->isSuperseded(), 403);
        return view('pages.events.ramadan.change_requests.create', compact('ramadanIftar'));
    }

    public function store(StoreRamadanIftarChangeRequest $request, RamadanIftar $ramadanIftar, RamadanIftarChangeRequestService $service)
    {
        $service->create($ramadanIftar, $request->user(), $request->input('reason'));
        return redirect()->route('events.ramadan.iftars.show', $ramadanIftar)->with('success', __('ramadan_iftars.change_requests.messages.created'));
    }

    private function authorizeSource(Request $request, RamadanIftar $iftar): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || ($user->hasRole('relations_officer') && $user->can('ramadan_iftars.change_request.create') && ($user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id)))), 403);
    }
}
