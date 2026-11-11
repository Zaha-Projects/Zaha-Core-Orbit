<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Movement\Models\Location;
use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Support\MovementPermission as P;
use Carbon\CarbonImmutable;
use Database\Seeders\rightDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\RateLimiter;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Movement requests over HTTP (plan §14.3 request rows, §8.2, §11.7, Revision 5).
 * Database test — run only against a disposable database (CLAUDE.md, database safety).
 */
class MovementRequestHttpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(rightDatabaseSeeder::class);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        RateLimiter::clear('movement-requests');
    }

    private function user(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['status' => 'active']);
        $user->assignRole($role);

        return $user->fresh();
    }

    /** Local (Asia/Amman) wall-clock string, days ahead. */
    private function local(int $days, string $time = '10:00'): string
    {
        return CarbonImmutable::now('Asia/Amman')->addDays($days)->format('Y-m-d').' '.$time;
    }

    private function form(array $overrides = [], array $outbound = [], ?array $return = null): array
    {
        $data = array_replace([
            'trip_direction' => 'one_way',
            'purpose' => 'Volunteers to the bazaar',
            'submit_action' => 'draft',
            'legs' => ['outbound' => array_replace([
                'origin_text' => 'Khalda centre',
                'destination_text' => 'Irbid branch',
                'passenger_count' => 14,
                'required_arrival_at' => $this->local(5),
            ], $outbound)],
        ], $overrides);
        if ($return !== null) {
            $data['legs']['return'] = $return;
        }

        return $data;
    }

    /** Opens the create form (which issues the client_token) and posts it. */
    private function createAs(User $user, array $data)
    {
        $token = $this->actingAs($user)->get(route('movement.requests.create'))->assertOk()->viewData('clientToken');

        return $this->actingAs($user)->post(route('movement.requests.store'), $data + ['client_token' => $token])->assertStatus(302);
    }

    // ---- Authorization ---------------------------------------------------------------------------

    public function test_requesters_can_create_and_the_manager_cannot(): void
    {
        foreach (['relations_officer', 'supervisor', 'super_admin'] as $role) {
            $this->createAs($this->user($role), $this->form())->assertSessionHasNoErrors()->assertRedirect();
        }
        $this->assertSame(3, MovementRequest::query()->count());

        $manager = $this->user('movement_manager');
        $this->actingAs($manager)->get(route('movement.requests.create'))->assertForbidden();
        $this->actingAs($manager)->post(route('movement.requests.store'), $this->form())->assertForbidden();
        $this->actingAs($manager)->get(route('movement.requests.review.index'))->assertOk();

        $staff = $this->user('staff');
        $this->actingAs($staff)->get(route('movement.requests.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('movement.requests.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('movement.requests.review.index'))->assertForbidden();
    }

    public function test_per_user_grant_allows_and_per_user_denial_blocks_creation(): void
    {
        $staff = $this->user('staff');
        $staff->givePermissionTo(P::REQUESTS_CREATE);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->createAs($staff->fresh(), $this->form())->assertSessionHasNoErrors();

        $officer = $this->user('relations_officer');
        $officer->deniedPermissions()->attach(Permission::findByName(P::REQUESTS_CREATE, 'web'));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($officer->fresh())->get(route('movement.requests.create'))->assertForbidden();
    }

    public function test_a_requester_cannot_see_or_change_another_users_request(): void
    {
        $owner = $this->user('relations_officer');
        $this->createAs($owner, $this->form())->assertSessionHasNoErrors();
        $request = MovementRequest::query()->firstOrFail();

        $other = $this->user('supervisor');
        $this->actingAs($other)->get(route('movement.requests.show', $request))->assertForbidden();
        $this->actingAs($other)->get(route('movement.requests.edit', $request))->assertForbidden();
        $this->actingAs($other)->put(route('movement.requests.update', $request), $this->form())->assertForbidden();
        $this->actingAs($other)->post(route('movement.requests.submit', $request))->assertForbidden();
        $this->actingAs($other)->post(route('movement.requests.cancel', $request), ['reason' => 'x'])->assertForbidden();
        $this->assertNotContains($request->id, $this->actingAs($other)->get(route('movement.requests.index'))->viewData('requests')->pluck('id')->all());

        // Managers and super_admin see it; only managers decide.
        $this->actingAs($this->user('movement_manager'))->get(route('movement.requests.show', $request))->assertOk();
        $this->actingAs($this->user('super_admin'))->get(route('movement.requests.show', $request))->assertOk();
        $this->actingAs($owner)->post(route('movement.requests.review.decide', $request), ['decision' => 'approve'])->assertForbidden();
    }

    public function test_review_flow_over_http(): void
    {
        $owner = $this->user('relations_officer');
        $this->createAs($owner, $this->form(['submit_action' => 'submit']))->assertSessionHasNoErrors();
        $request = MovementRequest::query()->firstOrFail();
        $this->assertSame('submitted', $request->status);

        $manager = $this->user('movement_manager');
        $queue = $this->actingAs($manager)->get(route('movement.requests.review.index'))->assertOk();
        $this->assertSame([$request->id], $queue->viewData('requests')->pluck('id')->all());

        $this->actingAs($manager)->post(route('movement.requests.review.decide', $request), ['decision' => 'reject'])
            ->assertRedirect(route('movement.requests.show', $request))->assertSessionHasErrors('request');
        $this->assertSame('submitted', $request->fresh()->status);

        $this->actingAs($manager)->post(route('movement.requests.review.start', $request))->assertSessionHasNoErrors();
        $this->actingAs($manager)->post(route('movement.requests.review.decide', $request), ['decision' => 'return', 'comment' => 'Add the return'])->assertSessionHasNoErrors();
        $this->assertSame('returned', $request->fresh()->status);

        $page = $this->actingAs($owner)->get(route('movement.requests.show', $request))->assertOk();
        $this->assertCount(4, $page->viewData('timeline'));
        $page->assertSee('Add the return');
    }

    // ---- Validation -------------------------------------------------------------------------------

    public function test_leg_and_direction_rules(): void
    {
        // Many writes in one test: the limiter has its own test below.
        $this->withoutMiddleware(ThrottleRequests::class);
        $officer = $this->user('relations_officer');
        $back = ['origin_text' => 'Irbid branch', 'destination_text' => 'Khalda centre', 'passenger_count' => 10];

        $this->createAs($officer, $this->form(['trip_direction' => 'round_trip']))->assertSessionHasErrors('legs.return');
        $this->createAs($officer, $this->form([], [], $back + ['required_arrival_at' => $this->local(6)]))->assertSessionHasErrors('legs.return');
        $this->createAs($officer, $this->form(['trip_direction' => 'round_trip'], [], $back + ['required_arrival_at' => $this->local(5, '09:00')]))
            ->assertSessionHasErrors(['legs.return.required_arrival_at' => __('movement.requests.errors.return_after_outbound')]);
        $this->createAs($officer, $this->form([], ['passenger_count' => 0]))->assertSessionHasErrors('legs.outbound.passenger_count');
        $this->createAs($officer, $this->form([], ['origin_text' => null]))->assertSessionHasErrors('legs.outbound.origin_text');
        $this->createAs($officer, $this->form([], ['required_arrival_at' => $this->local(-1)]))
            ->assertSessionHasErrors(['legs.outbound.required_arrival_at' => __('movement.requests.errors.deadline_future')]);
        $this->createAs($officer, $this->form([], ['required_arrival_at' => '2026/12/01 10:00']))->assertSessionHasErrors('legs.outbound.required_arrival_at');
        $this->createAs($officer, $this->form([], ['preferred_departure_at' => $this->local(5, '11:00')]))->assertSessionHasErrors('legs.outbound.preferred_departure_at');
        $this->createAs($officer, $this->form([], ['origin_maps_url' => 'https://example.com/nothing']))->assertSessionHasErrors('legs.outbound.origin_maps_url');

        $inactive = Location::create(['name_en' => 'Old depot', 'is_active' => false]);
        $this->createAs($officer, $this->form([], ['origin_location_id' => $inactive->id, 'origin_text' => null]))->assertSessionHasErrors('legs.outbound.origin_location_id');

        $this->assertSame(0, MovementRequest::query()->count());

        $this->createAs($officer, $this->form(['trip_direction' => 'round_trip'], [], $back + ['required_arrival_at' => $this->local(5, '18:00')]))->assertSessionHasNoErrors();
        $this->assertCount(2, MovementRequest::query()->firstOrFail()->legs);
    }

    public function test_local_time_is_stored_in_utc_and_travel_date_follows_the_local_deadline(): void
    {
        $officer = $this->user('relations_officer');
        $day = CarbonImmutable::now('Asia/Amman')->addDays(10)->format('Y-m-d');
        $next = CarbonImmutable::parse($day)->addDay()->format('Y-m-d');
        $saved = Location::create(['name_ar' => 'مركز خلدا', 'is_active' => true]);

        // 00:30 local is 21:30 UTC the previous day; the travel date is the local day.
        $this->createAs($officer, $this->form(['trip_direction' => 'round_trip'],
            ['required_arrival_at' => $day.' 23:30', 'preferred_departure_at' => $day.' 21:00', 'origin_location_id' => $saved->id, 'origin_text' => 'ignored'],
            ['origin_text' => 'Irbid', 'destination_text' => 'Khalda', 'passenger_count' => 9, 'required_arrival_at' => $next.' 00:30']
        ))->assertSessionHasNoErrors();

        $request = MovementRequest::query()->with('legs')->firstOrFail();
        $out = $request->legs->firstWhere('leg_type', 'outbound');
        $ret = $request->legs->firstWhere('leg_type', 'return');

        $this->assertSame($day.' 20:30:00', $out->getRawOriginal('required_arrival_at'));
        $this->assertSame($day.' 18:00:00', $out->getRawOriginal('preferred_departure_at'));
        $this->assertSame($day, $out->travel_date->format('Y-m-d'));
        $this->assertSame($saved->id, $out->origin_location_id);
        $this->assertNull($out->origin_text, 'A saved location wins over custom text.');

        $this->assertSame($day.' 21:30:00', $ret->getRawOriginal('required_arrival_at'), 'Midnight-crossing deadline stored in UTC.');
        $this->assertSame($next, $ret->travel_date->format('Y-m-d'), 'travel_date is the local date, not the UTC date.');
        $this->assertSame($officer->branch_id, $request->branch_id);
    }

    // ---- Idempotency and limits --------------------------------------------------------------------

    public function test_submitting_twice_with_the_same_token_creates_one_request(): void
    {
        $officer = $this->user('relations_officer');
        $token = $this->actingAs($officer)->get(route('movement.requests.create'))->viewData('clientToken');
        $data = $this->form(['submit_action' => 'submit']) + ['client_token' => $token];

        $first = $this->actingAs($officer)->post(route('movement.requests.store'), $data)->assertSessionHasNoErrors();
        $second = $this->actingAs($officer)->post(route('movement.requests.store'), $data)->assertSessionHasNoErrors();

        $this->assertSame(1, MovementRequest::query()->count());
        $request = MovementRequest::query()->firstOrFail();
        $this->assertSame($token, $request->client_token);
        $first->assertRedirect(route('movement.requests.show', $request));
        $second->assertRedirect(route('movement.requests.show', $request))->assertSessionHas('info', __('movement.requests.flash.already_saved'));
        $this->assertSame(1, \App\Models\AuditLog::query()->where('action', 'request.submitted')->count());

        // A token the server never issued is refused.
        $this->actingAs($officer)->post(route('movement.requests.store'), $this->form() + ['client_token' => '0d4b8f0e-6a39-4f5e-9d8f-111111111111'])
            ->assertSessionHasErrors('client_token');
    }

    public function test_limiter_returns_429_after_ten_writes_in_a_minute(): void
    {
        $officer = $this->user('relations_officer');
        $this->createAs($officer, $this->form())->assertSessionHasNoErrors();
        $request = MovementRequest::query()->firstOrFail();

        for ($i = 2; $i <= 10; $i++) {
            $this->actingAs($officer)->put(route('movement.requests.update', $request), $this->form(['purpose' => "Edit {$i}"]))->assertStatus(302);
        }
        $this->actingAs($officer)->put(route('movement.requests.update', $request), $this->form())->assertStatus(429);

        // Manager decisions are not throttled by the requester's limiter.
        $this->actingAs($this->user('movement_manager'))->get(route('movement.requests.review.index'))->assertOk();
    }

    // ---- Availability JSON -------------------------------------------------------------------------

    public function test_availability_endpoint_is_indicative_and_aggregate_only(): void
    {
        $json = $this->actingAs($this->user('staff'))
            ->getJson(route('movement.availability.summary', ['required_arrival_at' => $this->local(2), 'passenger_count' => 10]))
            ->assertOk()->assertJsonPath('indicative', true)->json();

        $this->assertSame(['indicative', 'window', 'types', 'drivers'], array_keys($json));
        $this->actingAs($this->user('staff'))->getJson(route('movement.availability.summary'))->assertStatus(422);
    }

    public function test_sidebar_shows_request_items_by_permission(): void
    {
        $officerHtml = $this->actingAs($this->user('relations_officer'))->get(route('movement.requests.index'))->assertOk()->getContent();
        $this->assertStringContainsString(route('movement.requests.index'), $officerHtml);
        $this->assertStringNotContainsString(route('movement.requests.review.index'), $officerHtml);

        $managerHtml = $this->actingAs($this->user('movement_manager'))->get(route('movement.requests.review.index'))->assertOk()->getContent();
        $this->assertStringContainsString(route('movement.requests.review.index'), $managerHtml);
    }
}
