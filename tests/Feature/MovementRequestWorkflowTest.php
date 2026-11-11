<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\InAppNotification;
use App\Models\User;
use App\Modules\Movement\Exceptions\IllegalMovementTransition;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Services\MovementNotifier;
use App\Modules\Movement\Services\MovementRequestWorkflow;
use App\Modules\Movement\Support\MovementPermission as P;
use Carbon\CarbonImmutable;
use Database\Seeders\rightDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * MovementRequestWorkflow (plan §8.4, §8.5 Phase 4 subset, §15.5, Revision 5).
 * Database test — run only against a disposable database (CLAUDE.md, database safety).
 */
class MovementRequestWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private MovementRequestWorkflow $workflow;
    private User $requester;
    private User $manager;

    /** action => target status (submit also covers resubmit from returned). */
    private const ACTIONS = [
        'submit' => MovementRequest::STATUS_SUBMITTED,
        'startReview' => MovementRequest::STATUS_UNDER_REVIEW,
        'approve' => MovementRequest::STATUS_APPROVED,
        'return' => MovementRequest::STATUS_RETURNED,
        'reject' => MovementRequest::STATUS_REJECTED,
        'cancel' => MovementRequest::STATUS_CANCELLED,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(rightDatabaseSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->workflow = app(MovementRequestWorkflow::class);
        $this->requester = $this->user('relations_officer');
        $this->manager = $this->user('movement_manager');
    }

    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['status' => 'active']);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function payload(string $direction = 'one_way'): array
    {
        $arrival = CarbonImmutable::now('UTC')->addDays(3)->startOfHour();
        $legs = ['outbound' => [
            'origin_location_id' => null, 'origin_text' => 'Khalda', 'origin_maps_url' => null,
            'destination_location_id' => null, 'destination_text' => 'Irbid', 'destination_maps_url' => null,
            'passenger_count' => 12, 'required_arrival_at' => $arrival, 'travel_date' => $arrival->setTimezone('Asia/Amman')->format('Y-m-d'),
            'preferred_departure_at' => null, 'notes' => null,
        ]];
        if ($direction === 'round_trip') {
            $legs['return'] = ['origin_text' => 'Irbid', 'destination_text' => 'Khalda', 'required_arrival_at' => $arrival->addHours(6), 'travel_date' => $arrival->addHours(6)->setTimezone('Asia/Amman')->format('Y-m-d')] + $legs['outbound'];
        }

        return ['header' => ['trip_direction' => $direction, 'purpose' => 'Field visit', 'notes' => null, 'department_id' => null, 'branch_id' => null, 'context_label' => null], 'legs' => $legs];
    }

    private function draft(): MovementRequest
    {
        return $this->workflow->create($this->requester, $this->payload(), null);
    }

    private function inStatus(string $status): MovementRequest
    {
        $request = $this->draft();
        $request->forceFill(['status' => $status])->save();

        return $request->fresh();
    }

    private function act(string $action, MovementRequest $request): MovementRequest
    {
        return match ($action) {
            'submit' => $this->workflow->submit($request, $this->requester),
            'startReview' => $this->workflow->startReview($request, $this->manager),
            'approve' => $this->workflow->decide($request, $this->manager, 'approve', null),
            'return' => $this->workflow->decide($request, $this->manager, 'return', 'Please add the return time'),
            'reject' => $this->workflow->decide($request, $this->manager, 'reject', 'Not possible'),
            'cancel' => $this->workflow->cancel($request, $this->requester, 'No longer needed'),
        };
    }

    public function test_full_status_by_action_matrix_matches_section_8_4(): void
    {
        foreach (MovementRequest::STATUSES as $from) {
            foreach (self::ACTIONS as $action => $to) {
                $request = $this->inStatus($from);
                $allowed = in_array($to, MovementRequest::TRANSITIONS[$from], true);

                try {
                    $result = $this->act($action, $request);
                    $this->assertTrue($allowed, "{$action} from {$from} must be rejected.");
                    $this->assertSame($to, $result->fresh()->status, "{$action} from {$from}.");
                } catch (IllegalMovementTransition $e) {
                    $this->assertFalse($allowed, "{$action} from {$from} must be allowed: ".$e->getMessage());
                    $this->assertSame($from, $request->fresh()->status, 'A rejected transition changes nothing.');
                }
            }
        }
    }

    public function test_transition_map_is_exactly_section_8_4(): void
    {
        $this->assertSame([
            'draft' => ['submitted', 'cancelled'],
            'submitted' => ['under_review', 'approved', 'returned', 'rejected', 'cancelled'],
            'under_review' => ['approved', 'returned', 'rejected', 'cancelled'],
            'returned' => ['submitted', 'cancelled'],
            'approved' => ['cancelled'],
            'rejected' => [],
            'cancelled' => [],
        ], MovementRequest::TRANSITIONS);
        $this->assertNotContains('pending_secondary_approval', MovementRequest::STATUSES, 'D2: no second stage.');
    }

    public function test_comment_required_on_return_and_reject_and_reason_on_cancel_past_draft(): void
    {
        foreach (['return', 'reject'] as $decision) {
            $request = $this->inStatus(MovementRequest::STATUS_SUBMITTED);
            try {
                $this->workflow->decide($request, $this->manager, $decision, '   ');
                $this->fail("{$decision} without a comment must fail.");
            } catch (IllegalMovementTransition $e) {
                $this->assertSame(__('movement.requests.errors.comment_required'), $e->getMessage());
            }
        }

        $this->workflow->decide($this->inStatus(MovementRequest::STATUS_SUBMITTED), $this->manager, 'approve', null);

        $submitted = $this->inStatus(MovementRequest::STATUS_SUBMITTED);
        try {
            $this->workflow->cancel($submitted, $this->requester, null);
            $this->fail('Cancelling past draft without a reason must fail.');
        } catch (IllegalMovementTransition $e) {
            $this->assertSame(__('movement.requests.errors.reason_required'), $e->getMessage());
        }

        $this->assertSame('cancelled', $this->workflow->cancel($this->draft(), $this->requester, null)->status, 'A draft may be cancelled without a reason.');
    }

    public function test_edits_only_in_draft_and_returned(): void
    {
        foreach (MovementRequest::STATUSES as $status) {
            $request = $this->inStatus($status);
            try {
                $this->workflow->update($request, $this->payload('round_trip'));
                $this->assertContains($status, ['draft', 'returned']);
                $this->assertCount(2, $request->legs()->get());
            } catch (IllegalMovementTransition $e) {
                $this->assertNotContains($status, ['draft', 'returned']);
                $this->assertCount(1, $request->legs()->get());
            }
        }
    }

    public function test_only_drafts_can_be_soft_deleted(): void
    {
        $draft = $this->draft();
        $this->workflow->deleteDraft($draft);
        $this->assertSoftDeleted('movement_requests', ['id' => $draft->id]);

        $this->expectException(IllegalMovementTransition::class);
        $this->workflow->deleteDraft($this->inStatus(MovementRequest::STATUS_SUBMITTED));
    }

    public function test_reference_number_and_fulfilment_subset(): void
    {
        $request = $this->draft();
        $this->assertSame(sprintf('MV-%s-%06d', CarbonImmutable::now('Asia/Amman')->format('Y'), $request->id), $request->reference_no);
        $this->assertSame('not_applicable', $request->fulfilment_status);

        $this->workflow->submit($request, $this->requester);
        $this->assertSame('not_applicable', $request->fresh()->fulfilment_status);
        $this->workflow->decide($request, $this->manager, 'approve', null);
        $this->assertSame('unallocated', $request->fresh()->fulfilment_status);
        $this->workflow->cancel($request, $this->manager, 'Bus broke down');
        $this->assertSame('cancelled', $request->fresh()->fulfilment_status);
        $this->assertSame($this->manager->id, $request->fresh()->cancelled_by);
    }

    public function test_submit_guard_rejects_a_past_deadline(): void
    {
        $request = $this->draft();
        $request->legs()->update(['required_arrival_at' => CarbonImmutable::now('UTC')->subHour()]);

        $this->expectException(IllegalMovementTransition::class);
        $this->workflow->submit($request, $this->requester);
    }

    public function test_every_transition_writes_one_audit_row_with_old_new_status_and_comment(): void
    {
        $request = $this->draft();
        $this->workflow->submit($request, $this->requester);
        $this->workflow->startReview($request, $this->manager);
        $this->workflow->decide($request, $this->manager, 'return', 'Add the return leg');
        $this->workflow->update($request, $this->payload('round_trip'));
        $this->workflow->submit($request, $this->requester);
        $this->workflow->decide($request, $this->manager, 'approve', 'OK');
        $this->workflow->cancel($request, $this->requester, 'Event postponed');

        $rows = AuditLog::query()->where('entity_type', MovementRequest::class)->where('entity_id', $request->id)->orderBy('id')->get();
        $this->assertSame(
            ['request.created', 'request.submitted', 'request.review_started', 'request.returned', 'request.updated', 'request.resubmitted', 'request.approved', 'request.cancelled'],
            $rows->pluck('action')->all()
        );
        $this->assertTrue($rows->every(fn ($row) => $row->module === 'movement'));

        $returned = $rows->firstWhere('action', 'request.returned');
        $this->assertSame(['status' => 'under_review'], $returned->old_values);
        $this->assertSame('returned', $returned->new_values['status']);
        $this->assertSame('Add the return leg', $returned->new_values['comment']);
        $this->assertSame($this->manager->id, $returned->user_id, 'The audit row records the acting manager.');
        $this->assertSame($this->requester->id, $rows->firstWhere('action', 'request.submitted')->user_id);
        $this->assertSame('Event postponed', $rows->last()->new_values['comment']);
    }

    // ---- Notifications (§15.5, Revision 5) -------------------------------------------------------

    public function test_manager_recipients_exclude_denied_and_inactive_users(): void
    {
        $denied = $this->user('movement_manager');
        $denied->deniedPermissions()->attach(Permission::findByName(P::REQUESTS_REVIEW, 'web'));
        $inactive = $this->user('movement_manager', ['status' => 'inactive']);
        $direct = $this->user('staff');
        $direct->givePermissionTo(P::REQUESTS_REVIEW);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $ids = app(MovementNotifier::class)->managers()->pluck('id')->all();

        $this->assertContains($this->manager->id, $ids);
        $this->assertContains($direct->id, $ids, 'Direct permission holders are managers too.');
        $this->assertNotContains($denied->id, $ids, 'A per-user denial excludes the manager.');
        $this->assertNotContains($inactive->id, $ids, 'Inactive users are excluded.');
        $this->assertNotContains($this->requester->id, $ids);
    }

    public function test_notifications_go_to_the_right_recipients(): void
    {
        $count = fn (User $user, string $type) => InAppNotification::query()->where('user_id', $user->id)->where('type', $type)->count();

        $request = $this->draft();
        $this->workflow->submit($request, $this->requester);
        $this->assertSame(1, $count($this->manager, 'movement.request.submitted'));
        $this->assertSame(0, $count($this->requester, 'movement.request.submitted'));

        $this->workflow->decide($request, $this->manager, 'return', 'Fix it');
        $this->assertSame(1, $count($this->requester, 'movement.request.decided'));
        $this->assertSame(0, $count($this->manager, 'movement.request.decided'));

        $this->workflow->submit($request, $this->requester); // resubmitted
        $this->assertSame(2, $count($this->manager, 'movement.request.submitted'));
        $resubmitted = InAppNotification::query()->where('user_id', $this->manager->id)->latest('id')->first();
        $this->assertSame(__('movement.notifications.resubmitted.title'), $resubmitted->title);
        $this->assertSame('movement.notifications.resubmitted.title', $resubmitted->meta['i18n']['title_key']);
        $this->assertSame(route('movement.requests.show', $request), $resubmitted->action_url);

        $this->workflow->cancel($request, $this->requester, 'Not needed');
        $this->assertSame(1, $count($this->manager, 'movement.request.cancelled'), 'A requester cancel notifies managers.');
        $this->assertSame(0, $count($this->requester, 'movement.request.cancelled'));

        $other = $this->draft();
        $this->workflow->submit($other, $this->requester);
        $this->workflow->cancel($other, $this->manager, 'Duplicate');
        $this->assertSame(1, $count($this->requester, 'movement.request.cancelled'), 'A manager cancel notifies the requester.');
    }

    public function test_both_languages_define_every_notification_text(): void
    {
        foreach (['submitted', 'resubmitted', 'approved', 'returned', 'rejected', 'cancelled'] as $event) {
            foreach (['ar', 'en'] as $locale) {
                foreach (['title', 'message'] as $part) {
                    $key = "movement.notifications.{$event}.{$part}";
                    $this->assertNotSame($key, __($key, ['reference' => 'MV-1'], $locale), "{$locale}: {$key} missing.");
                }
            }
        }
    }
}
