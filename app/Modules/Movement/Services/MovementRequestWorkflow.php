<?php

namespace App\Modules\Movement\Services;

use App\Models\User;
use App\Modules\Movement\Exceptions\IllegalMovementTransition;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Models\MovementRequestLeg;
use App\Modules\Movement\Support\MovementAudit;
use App\Modules\Movement\Support\MovementTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Request lifecycle (plan §8.4, Revision 5). Every change runs in one transaction that writes the
 * row, its audit entry (module 'movement', action 'request.<event>') and the notification.
 * Illegal transitions throw IllegalMovementTransition with a user-facing message.
 *
 * Authorization is done by the caller (MovementRequestPolicy); this class enforces the lifecycle.
 */
class MovementRequestWorkflow
{
    public function __construct(private MovementNotifier $notifier)
    {
    }

    /**
     * Create a draft. Idempotent on client_token (§11.7): a repeat returns the existing request,
     * including when a concurrent insert wins the unique constraint.
     *
     * @param array{header: array, legs: array<string, array>} $data normalised by MovementRequestForm
     */
    public function create(User $requester, array $data, ?string $clientToken): MovementRequest
    {
        if ($clientToken && ($existing = $this->findByToken($requester, $clientToken))) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($requester, $data, $clientToken) {
                $request = MovementRequest::create($data['header'] + [
                    'requester_id' => $requester->id,
                    'status' => MovementRequest::STATUS_DRAFT,
                    'fulfilment_status' => MovementRequest::FULFILMENT_NOT_APPLICABLE,
                    'client_token' => $clientToken,
                ]);
                // Insert first, then derive the reference from the id: unique by construction (Revision 5).
                $request->update(['reference_no' => sprintf('MV-%s-%06d', CarbonImmutable::now(MovementTime::timezone())->format('Y'), $request->id)]);
                $this->syncLegs($request, $data['legs']);
                MovementAudit::record('request.created', $request, null, $this->snapshot($request), $requester->id);

                return $request;
            });
        } catch (QueryException $e) {
            if ($clientToken && $this->isUniqueViolation($e) && ($existing = $this->findByToken($requester, $clientToken))) {
                return $existing;
            }

            throw $e;
        }
    }

    public function update(MovementRequest $request, array $data): MovementRequest
    {
        return DB::transaction(function () use ($request, $data) {
            $request = $this->lock($request);
            if (! $request->isEditable()) {
                throw new IllegalMovementTransition(__('movement.requests.errors.not_editable'));
            }

            $before = $this->snapshot($request);
            $request->update($data['header']);
            $this->syncLegs($request, $data['legs']);
            MovementAudit::record('request.updated', $request, $before, $this->snapshot($request->fresh()));

            return $request;
        });
    }

    /** draft → submitted, or returned → submitted (resubmit). */
    public function submit(MovementRequest $request, User $actor): MovementRequest
    {
        return DB::transaction(function () use ($request, $actor) {
            $request = $this->lock($request);
            $resubmitted = $request->status === MovementRequest::STATUS_RETURNED;
            $this->guard($request, MovementRequest::STATUS_SUBMITTED);
            $this->guardLegsSubmittable($request);

            $this->move($request, MovementRequest::STATUS_SUBMITTED, $resubmitted ? 'request.resubmitted' : 'request.submitted', $actor, null, [
                'submitted_at' => now(),
            ]);
            $this->notifier->requestSubmitted($request, $resubmitted);

            return $request;
        });
    }

    public function startReview(MovementRequest $request, User $manager): MovementRequest
    {
        return DB::transaction(function () use ($request, $manager) {
            $request = $this->lock($request);
            $this->guard($request, MovementRequest::STATUS_UNDER_REVIEW);
            $this->move($request, MovementRequest::STATUS_UNDER_REVIEW, 'request.review_started', $manager);

            return $request;
        });
    }

    /** Approve, return or reject (§8.4). A comment is required to return or reject. */
    public function decide(MovementRequest $request, User $manager, string $decision, ?string $comment): MovementRequest
    {
        $target = [
            'approve' => MovementRequest::STATUS_APPROVED,
            'return' => MovementRequest::STATUS_RETURNED,
            'reject' => MovementRequest::STATUS_REJECTED,
        ][$decision] ?? null;

        if ($target === null) {
            throw new IllegalMovementTransition(__('movement.requests.errors.illegal'));
        }

        $comment = $this->clean($comment);
        if ($target !== MovementRequest::STATUS_APPROVED && $comment === null) {
            throw new IllegalMovementTransition(__('movement.requests.errors.comment_required'));
        }

        return DB::transaction(function () use ($request, $manager, $target, $decision, $comment) {
            $request = $this->lock($request);
            $this->guard($request, $target);

            $extra = ['decided_at' => now(), 'decided_by' => $manager->id, 'decision_comment' => $comment];
            $action = ['approve' => 'request.approved', 'return' => 'request.returned', 'reject' => 'request.rejected'][$decision];
            $this->move($request, $target, $action, $manager, $comment, $extra);
            $this->notifier->requestDecided($request);

            return $request;
        });
    }

    /**
     * Cancel (§8.4). A reason is required once past draft. Phase 4 handles the request side only;
     * cancelling bookings and the in_progress guard arrive in Phase 5 (Revision 5).
     */
    public function cancel(MovementRequest $request, User $actor, ?string $reason): MovementRequest
    {
        $reason = $this->clean($reason);

        return DB::transaction(function () use ($request, $actor, $reason) {
            $request = $this->lock($request);
            $this->guard($request, MovementRequest::STATUS_CANCELLED);
            if ($request->status !== MovementRequest::STATUS_DRAFT && $reason === null) {
                throw new IllegalMovementTransition(__('movement.requests.errors.reason_required'));
            }

            $this->move($request, MovementRequest::STATUS_CANCELLED, 'request.cancelled', $actor, $reason, [
                'cancelled_at' => now(),
                'cancelled_by' => $actor->id,
                'cancellation_reason' => $reason,
            ]);
            $this->notifier->requestCancelled($request, $actor);

            return $request;
        });
    }

    /** Only drafts may be (soft-)deleted (§7.12). */
    public function deleteDraft(MovementRequest $request): void
    {
        DB::transaction(function () use ($request) {
            $request = $this->lock($request);
            if ($request->status !== MovementRequest::STATUS_DRAFT) {
                throw new IllegalMovementTransition(__('movement.requests.errors.only_drafts_deleted'));
            }

            $request->delete();
            MovementAudit::record('request.deleted', $request, ['status' => $request->status], ['deleted_at' => (string) $request->deleted_at]);
        });
    }

    /**
     * Phase 4 subset of §8.5 (Revision 5): not_applicable / unallocated / cancelled.
     * Called inside the transaction of every status change.
     */
    public function refreshFulfilment(MovementRequest $request): void
    {
        $value = match ($request->status) {
            MovementRequest::STATUS_CANCELLED => MovementRequest::FULFILMENT_CANCELLED,
            MovementRequest::STATUS_APPROVED => MovementRequest::FULFILMENT_UNALLOCATED,
            default => MovementRequest::FULFILMENT_NOT_APPLICABLE,
        };

        if ($request->fulfilment_status !== $value) {
            $request->forceFill(['fulfilment_status' => $value])->save();
        }
    }

    private function move(MovementRequest $request, string $to, string $action, User $actor, ?string $comment = null, array $extra = []): void
    {
        $from = $request->status;
        $request->update(['status' => $to] + $extra);
        $this->refreshFulfilment($request);

        MovementAudit::record(
            $action,
            $request,
            ['status' => $from],
            array_filter(['status' => $to, 'fulfilment_status' => $request->fulfilment_status, 'comment' => $comment], fn ($v) => $v !== null),
            $actor->id
        );
    }

    private function guard(MovementRequest $request, string $to): void
    {
        if (! $request->canTransitionTo($to)) {
            throw new IllegalMovementTransition(__('movement.requests.errors.illegal_from', [
                'from' => __('movement.request_statuses.'.$request->status),
                'to' => __('movement.request_statuses.'.$to),
            ]));
        }
    }

    /** §8.4 guard for draft/returned → submitted: legs valid and every deadline still in the future. */
    private function guardLegsSubmittable(MovementRequest $request): void
    {
        $legs = $request->legs()->get();
        $outbound = $legs->firstWhere('leg_type', MovementRequestLeg::TYPE_OUTBOUND);
        $return = $legs->firstWhere('leg_type', MovementRequestLeg::TYPE_RETURN);

        $shapeOk = $outbound !== null
            && ($request->trip_direction === MovementRequest::DIRECTION_ROUND_TRIP) === ($return !== null)
            && (! $return || $return->required_arrival_at->gt($outbound->required_arrival_at));

        if (! $shapeOk) {
            throw new IllegalMovementTransition(__('movement.requests.errors.legs_invalid'));
        }

        if ($legs->contains(fn (MovementRequestLeg $leg) => ! $leg->required_arrival_at->isFuture())) {
            throw new IllegalMovementTransition(__('movement.requests.errors.deadline_passed'));
        }
    }

    /** @param array<string, array> $legs keyed by leg_type; a missing return leg is deleted */
    private function syncLegs(MovementRequest $request, array $legs): void
    {
        foreach (MovementRequestLeg::TYPES as $type) {
            if (isset($legs[$type])) {
                $request->legs()->updateOrCreate(['leg_type' => $type], $legs[$type]);
            } else {
                $request->legs()->where('leg_type', $type)->delete();
            }
        }
    }

    private function snapshot(MovementRequest $request): array
    {
        return [
            'header' => $request->only(['status', 'trip_direction', 'purpose', 'notes', 'branch_id', 'department_id', 'context_label']),
            'legs' => $request->legs()->get()->map(fn (MovementRequestLeg $leg) => $leg->only([
                'leg_type', 'origin_location_id', 'origin_text', 'destination_location_id', 'destination_text', 'passenger_count',
            ]) + [
                'travel_date' => $leg->travel_date?->format('Y-m-d'),
                'required_arrival_at' => $leg->required_arrival_at?->format('Y-m-d H:i:s'),
            ])->all(),
        ];
    }

    private function lock(MovementRequest $request): MovementRequest
    {
        return MovementRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
    }

    private function findByToken(User $requester, string $token): ?MovementRequest
    {
        return MovementRequest::withTrashed()->where('client_token', $token)->where('requester_id', $requester->id)->first();
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062 || ($e->errorInfo[0] ?? null) === '23000' || str_contains($e->getMessage(), 'UNIQUE');
    }

    private function clean(?string $text): ?string
    {
        $text = trim((string) $text);

        return $text === '' ? null : $text;
    }
}
