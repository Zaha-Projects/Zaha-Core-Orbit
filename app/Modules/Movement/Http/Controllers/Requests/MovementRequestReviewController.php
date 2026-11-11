<?php

namespace App\Modules\Movement\Http\Controllers\Requests;

use App\Http\Controllers\Controller;
use App\Modules\Movement\Exceptions\IllegalMovementTransition;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Models\MovementRequestLeg;
use App\Modules\Movement\Services\MovementRequestWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Manager review queue and decisions (plan §16 screen 7, §8.4). Requires movement.requests.review. */
class MovementRequestReviewController extends Controller
{
    public function __construct(private MovementRequestWorkflow $workflow)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('review', MovementRequest::class);

        $status = $request->validate(['status' => ['nullable', Rule::in(MovementRequest::STATUSES)]])['status'] ?? null;
        $deadline = MovementRequestLeg::query()->selectRaw('MIN(required_arrival_at)')->whereColumn('movement_request_legs.movement_request_id', 'movement_requests.id');

        $requests = MovementRequest::query()
            ->with(['requester', 'legs.originLocation', 'legs.destinationLocation'])
            ->when($status, fn ($q) => $q->where('status', $status), fn ($q) => $q->whereIn('status', [MovementRequest::STATUS_SUBMITTED, MovementRequest::STATUS_UNDER_REVIEW]))
            ->where('status', '!=', MovementRequest::STATUS_DRAFT) // drafts are private to the requester
            ->orderBy($deadline) // by deadline (earliest required arrival first)
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('pages.movement.requests.review', ['requests' => $requests, 'status' => $status]);
    }

    public function startReview(Request $request, MovementRequest $movementRequest)
    {
        $this->authorize('decide', $movementRequest);

        return $this->run(fn () => $this->workflow->startReview($movementRequest, $request->user()), $movementRequest, 'movement.requests.flash.review_started');
    }

    public function decide(Request $request, MovementRequest $movementRequest)
    {
        $this->authorize('decide', $movementRequest);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['approve', 'return', 'reject'])],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        return $this->run(fn () => $this->workflow->decide($movementRequest, $request->user(), $data['decision'], $data['comment'] ?? null), $movementRequest, 'movement.requests.flash.decided');
    }

    private function run(callable $transition, MovementRequest $movementRequest, string $successKey)
    {
        try {
            $transition();
        } catch (IllegalMovementTransition $e) {
            return redirect()->route('movement.requests.show', $movementRequest)->withErrors(['request' => $e->getMessage()])->withInput();
        }

        return redirect()->route('movement.requests.show', $movementRequest)->with('success', __($successKey));
    }
}
