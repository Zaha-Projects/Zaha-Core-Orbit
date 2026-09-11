<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Models\TargetGroup;
use App\Modules\Events\Http\Requests\Ramadan\UpdateRamadanIftarExecutionRequest;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarExecutionService;
use Illuminate\Http\Request;

class RamadanIftarExecutionController extends Controller
{
    public function show(Request $request, RamadanIftar $ramadanIftar)
    {
        $this->authorizeExecution($request, $ramadanIftar);
        $ramadanIftar->load([
            'branch', 'relationsOfficer', 'attendees.targetGroup', 'attendees.beneficiarySegment',
            'meals.items', 'gifts', 'programSegments', 'executionTeams.members.user',
            'volunteerRequirements.beneficiarySegment', 'supplies', 'executionNeeds.executionNeedType',
        ]);

        $targetGroups = TargetGroup::query()->active()->forRamadanIftars()->orderBy('sort_order')->get();
        $beneficiarySegments = BeneficiarySegment::query()->active()->ordered()->get();

        return view('pages.events.ramadan.execution', compact('ramadanIftar', 'targetGroups', 'beneficiarySegments'));
    }

    public function start(Request $request, RamadanIftar $ramadanIftar, RamadanIftarExecutionService $execution)
    {
        $this->authorizeExecution($request, $ramadanIftar);
        $execution->start($ramadanIftar, $request->user());

        return redirect()->route('events.ramadan.iftars.execution.show', $ramadanIftar)
            ->with('success', 'Ramadan Iftar execution started.');
    }

    public function update(UpdateRamadanIftarExecutionRequest $request, RamadanIftar $ramadanIftar, RamadanIftarExecutionService $execution)
    {
        $execution->update($ramadanIftar, $request->validated(), $request->user());

        return redirect()->route('events.ramadan.iftars.execution.show', $ramadanIftar)
            ->with('success', 'Actual execution data saved.');
    }

    private function authorizeExecution(Request $request, RamadanIftar $iftar): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.execute')), 403);
        abort_unless($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id), 403);
        abort_unless($iftar->canAccessExecution(), 403);
    }
}
