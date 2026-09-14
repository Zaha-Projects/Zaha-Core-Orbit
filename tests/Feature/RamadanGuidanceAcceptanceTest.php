<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\RamadanIftar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RamadanGuidanceAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_ramadan_guidance_requires_one_active_published_version(): void
    {
        $old = $this->guidance(1, false);
        $future = $this->guidance(2, true, now()->addDay());
        $current = $this->guidance(3, true);

        $this->assertTrue($current->is(EventGuidanceVersion::currentForRamadan()));
        $this->assertFalse($old->is(EventGuidanceVersion::currentForRamadan()));
        $this->assertFalse($future->is(EventGuidanceVersion::currentForRamadan()));

        $this->guidance(4, true);
        $this->expectException(LogicException::class);
        EventGuidanceVersion::currentForRamadan();
    }

    public function test_acceptance_and_creation_use_server_resolved_version_and_timestamp(): void
    {
        [$user, $branch, $organization] = $this->planningActor();
        $old = $this->guidance(1, false, null, $user);
        $current = $this->guidance(2, true, null, $user);

        $this->actingAs($user)->get(route('events.ramadan.guidance.show'))
            ->assertOk()
            ->assertSee($current->title);

        $this->actingAs($user)->post(route('events.ramadan.guidance.accept'), [
            'accept_guidance' => '1',
            'guidance_version_id' => $old->id,
            'guidance_accepted_at' => '2000-01-01 00:00:00',
        ])->assertRedirect(route('events.ramadan.iftars.create'));

        $payload = $this->payload($branch, $user, $organization) + [
            'guidance_version_id' => $old->id,
            'guidance_accepted_at' => '2000-01-01 00:00:00',
        ];

        $this->actingAs($user)->post(route('events.ramadan.iftars.store'), $payload)->assertRedirect();

        $iftar = RamadanIftar::query()->sole();
        $this->assertSame($current->id, $iftar->guidance_version_id);
        $this->assertNotNull($iftar->guidance_accepted_at);
        $this->assertNotSame('2000-01-01 00:00:00', $iftar->guidance_accepted_at->format('Y-m-d H:i:s'));
        $this->assertTrue($iftar->hasValidGuidanceAcceptance());
    }

    public function test_stale_or_unpresented_guidance_cannot_be_accepted(): void
    {
        [$user] = $this->planningActor();
        $first = $this->guidance(1, true, null, $user);
        $this->actingAs($user)->get(route('events.ramadan.guidance.show'))->assertOk();

        $first->update(['is_active' => false]);
        $this->guidance(2, true, null, $user);

        $this->actingAs($user)->post(route('events.ramadan.guidance.accept'), [
            'accept_guidance' => '1',
        ])->assertSessionHasErrors('guidance');
    }

    public function test_missing_current_guidance_blocks_the_create_flow(): void
    {
        [$user] = $this->planningActor();
        $this->guidance(1, false, null, $user);
        EventGuidanceVersion::query()->create([
            'code' => 'another_event_type',
            'version_number' => 1,
            'title' => 'Unrelated guidance',
            'content' => 'Not valid for Ramadan.',
            'is_active' => true,
            'published_at' => now()->subMinute(),
        ]);

        $this->actingAs($user)->get(route('events.ramadan.iftars.create'))->assertStatus(503);
    }

    public function test_store_without_server_recorded_acceptance_is_rejected(): void
    {
        [$user, $branch, $organization] = $this->planningActor();
        $this->guidance(1, true, null, $user);

        $this->actingAs($user)
            ->post(route('events.ramadan.iftars.store'), $this->payload($branch, $user, $organization))
            ->assertSessionHasErrors('guidance');

        $this->assertDatabaseCount('ramadan_iftars', 0);
    }

    public function test_existing_iftar_keeps_accepted_version_and_accepted_content_is_immutable(): void
    {
        [$user, $branch, $organization] = $this->planningActor();
        $first = $this->guidance(1, true, null, $user);
        $this->actingAs($user)->get(route('events.ramadan.guidance.show'))->assertOk();
        $this->actingAs($user)->post(route('events.ramadan.guidance.accept'), ['accept_guidance' => '1'])->assertRedirect();
        $this->actingAs($user)->post(route('events.ramadan.iftars.store'), $this->payload($branch, $user, $organization))->assertRedirect();
        $iftar = RamadanIftar::query()->sole();

        $first->update(['is_active' => false]);
        $this->guidance(2, true, null, $user);

        $this->assertSame($first->id, $iftar->fresh()->guidance_version_id);

        $this->expectException(LogicException::class);
        $first->update(['content' => 'Silently changed content']);
    }

    private function guidance(int $version, bool $active, $publishedAt = null, ?User $creator = null): EventGuidanceVersion
    {
        return EventGuidanceVersion::query()->create([
            'code' => EventGuidanceVersion::RAMADAN_IFTAR,
            'version_number' => $version,
            'title' => 'Guidance version '.$version,
            'content' => 'Approved guidance content '.$version,
            'is_active' => $active,
            'published_at' => $publishedAt ?: now()->subMinute(),
            'created_by' => $creator ? $creator->id : null,
        ]);
    }

    private function planningActor(): array
    {
        $branch = Branch::factory()->create();
        $role = Role::findOrCreate('relations_officer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('branches.view.own', 'web'));
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole($role);
        $organization = CommunityOrganization::query()->create(['branch_id' => $branch->id, 'name' => 'Host']);

        return [$user, $branch, $organization];
    }

    private function payload(Branch $branch, User $user, CommunityOrganization $organization): array
    {
        return [
            'branch_id' => $branch->id,
            'title' => 'Guided Ramadan plan',
            'relations_officer_id' => $user->id,
            'planned_date' => '2027-03-01',
            'location_type' => RamadanIftar::LOCATION_OUTSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_ASSOCIATION,
            'community_organization_id' => $organization->id,
            'target_groups' => [],
            'meals' => [],
            'gifts' => [],
            'program_segments' => [],
            'execution_teams' => [],
            'volunteer_requirements' => [],
            'supplies' => [],
        ];
    }
}
