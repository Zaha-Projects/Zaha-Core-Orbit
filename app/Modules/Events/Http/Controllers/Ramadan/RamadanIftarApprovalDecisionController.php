<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Requests\Ramadan\DecideRamadanIftarRequest;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarApprovalService;
use App\Services\WorkflowNotificationService;

class RamadanIftarApprovalDecisionController extends Controller
{
    public function decide(DecideRamadanIftarRequest $request, RamadanIftar $ramadanIftar, RamadanIftarApprovalService $approvals, WorkflowNotificationService $notifications)
    {
        $user = $request->user();
        abort_unless($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $ramadanIftar->branch_id), 403);
        $data = $request->validated();
        [$ramadanIftar, $instance] = $approvals->decide($ramadanIftar, $user, (int) $data['workflow_step_id'], $data['decision'], $data['comment'] ?? null);
        $notifications->approvalDecision($instance, $ramadanIftar, $user, $data['decision'], route('events.ramadan.approvals.show', $ramadanIftar), $data['comment'] ?? null);

        return redirect()->route('events.ramadan.approvals.index')->with('success', 'Ramadan Iftar workflow decision recorded.');
    }
}
