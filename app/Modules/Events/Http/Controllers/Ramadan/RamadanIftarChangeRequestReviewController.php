<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Http\Requests\Ramadan\DecideRamadanIftarChangeRequest;
use App\Modules\Events\Models\RamadanIftarChangeRequest;
use App\Modules\Events\Services\RamadanIftarChangeRequestService;
use App\Services\DynamicWorkflowService;
use Illuminate\Http\Request;

class RamadanIftarChangeRequestReviewController extends Controller
{
    public function index(Request $request, DynamicWorkflowService $workflows)
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.change_request.review')), 403);
        $query = RamadanIftarChangeRequest::query()->where('status', RamadanIftarChangeRequest::STATUS_PENDING)
            ->whereHas('workflowInstance', fn ($query) => $query->whereIn('status', ['pending','in_progress']))
            ->with(['source.branch', 'requester', 'workflowInstance.currentStep.role']);
        if (! $user->hasRole('super_admin') && ! $user->can('branches.view.all')) $query->whereIn('branch_id', $user->scopedBranchIds());
        $requests = $query->latest()->get()->filter(fn ($item) => $item->requested_by !== $user->id && $workflows->currentStepForUser($item->workflowInstance, $user))->values();
        return view('pages.events.ramadan.change_requests.reviews.index', compact('requests'));
    }

    public function show(Request $request, RamadanIftarChangeRequest $changeRequest, DynamicWorkflowService $workflows)
    {
        $changeRequest->load(['source.branch', 'requester', 'workflowInstance.currentStep.role']);
        $this->authorizeReview($request, $changeRequest, $workflows);
        return view('pages.events.ramadan.change_requests.reviews.show', compact('changeRequest'));
    }

    public function decide(DecideRamadanIftarChangeRequest $request, RamadanIftarChangeRequest $changeRequest, RamadanIftarChangeRequestService $service)
    {
        $result = $service->decide($changeRequest, $request->user(), $request->input('decision'), $request->input('comment'));

        if ($result->created_version_id) {
            return redirect()->route('events.ramadan.iftars.show', $result->created_version_id)
                ->with('success', __('ramadan_iftars.change_requests.messages.reviewed'));
        }

        return redirect()->route('events.ramadan.change-requests.reviews.index')
            ->with('success', __('ramadan_iftars.change_requests.messages.reviewed'));
    }

    private function authorizeReview(Request $request, RamadanIftarChangeRequest $changeRequest, DynamicWorkflowService $workflows): void
    {
        $user = $request->user();
        abort_unless($user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.change_request.review')), 403);
        abort_unless($user->hasRole('super_admin') || ($changeRequest->requested_by !== $user->id && ($user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $changeRequest->branch_id)) && $workflows->currentStepForUser($changeRequest->workflowInstance, $user)), 403);
    }
}
