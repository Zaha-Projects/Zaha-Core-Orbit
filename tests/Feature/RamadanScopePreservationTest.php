<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\Setting;
use App\Models\User;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\EventGuidanceAcknowledgement;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanPeriod;
use Database\Seeders\RamadanReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RamadanScopePreservationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        foreach (['branch_coordinator', 'relations_officer', 'relations_manager', 'programs_manager', 'programs_officer', 'executive_manager', 'followup_officer', 'supervisor', 'volunteer_coordinator', 'liaison_officer'] as $role) Role::findOrCreate($role, 'web');
        $this->seed(RamadanReferenceDataSeeder::class);
        $user = User::factory()->create(['branch_id' => Branch::factory()->create()->id]);
        $user->assignRole(Role::findOrCreate('super_admin', 'web'));
        EventGuidanceAcknowledgement::create(['user_id' => $user->id, 'event_guidance_version_id' => EventGuidanceVersion::currentForRamadan()->id, 'acknowledged_at' => now()]);
        $this->actingAs($user);

        return $user;
    }

    private function iftarPayload(User $user): array
    {
        $host = CommunityOrganization::create(['branch_id' => $user->branch_id, 'name' => 'Test host']);

        return ['title' => 'Original title', 'planned_date' => '2026-02-20', 'relations_officer_id' => $user->id,
            'host_type' => 'association', 'location_type' => 'outside_center', 'community_organization_id' => $host->id,
            'execution_needs' => ExecutionNeedType::ramadanAvailableTypes()->filter->isMandatoryForRamadan()->map(fn ($type) => ['execution_need_type_id' => $type->id, 'is_required' => true])->values()->all(),
            'execution_teams' => [['name' => 'Historic team', 'members' => [['member_name' => 'Historic member', 'task_description' => 'Welcome']]]],
        ];
    }

    public function test_iftar_title_edit_preserves_all_hidden_details_and_rejects_forged_changes(): void
    {
        $user = $this->admin();
        $payload = $this->iftarPayload($user) + [
            'gifts' => [['gift_type' => 'gifts', 'description' => 'Historic gift', 'planned_quantity' => 3, 'has_supporting_entity' => false]],
            'supplies' => [['item_name' => 'Historic supply', 'planned_quantity' => 4, 'planned_available' => true]],
            'volunteer_requirements' => [['planned_count' => 5, 'tasks_summary' => 'Historic volunteer task']],
        ];
        $payload['execution_needs'] = ExecutionNeedType::ramadanAvailableTypes()->map(fn ($type) => ['execution_need_type_id' => $type->id, 'is_required' => true, 'planned_details' => 'Historic need detail'])->all();
        $this->post(route('events.ramadan.iftars.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $iftar = RamadanIftar::query()->sole();
        $relations = ['gifts', 'supplies', 'executionTeams', 'volunteerRequirements', 'executionNeeds'];
        $before = collect($relations)->mapWithKeys(fn ($relation) => [$relation => $iftar->$relation()->get()->toArray()]);
        $members = $iftar->executionTeams()->first()->members()->get()->toArray();
        ExecutionNeedType::query()->each(fn ($type) => $type->update(['usage_scope' => 'none']));
        foreach (['execution_needs', ...array_values(ExecutionNeedType::IFTAR_DETAIL_FIELDS)] as $field) unset($payload[$field]);
        $payload['title'] = 'Changed title only';
        $this->put(route('events.ramadan.iftars.update', $iftar), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Changed title only', $iftar->fresh()->title);
        foreach ($relations as $relation) $this->assertSame($before[$relation], $iftar->$relation()->get()->toArray(), $relation);
        $this->assertSame($members, $iftar->executionTeams()->first()->members()->get()->toArray());
        $this->get(route('events.ramadan.iftars.edit', $iftar))->assertOk()->assertSee('Historic gift')->assertSee('Historic volunteer task')->assertSee('Historic need detail')->assertDontSee('name="volunteer_requirements[', false);
        foreach (ExecutionNeedType::IFTAR_DETAIL_FIELDS as $field) {
            $this->put(route('events.ramadan.iftars.update', $iftar), $payload + [$field => []])->assertSessionHasErrors($field);
        }
        $this->put(route('events.ramadan.iftars.update', $iftar), $payload + ['execution_needs' => [['id' => $before['executionNeeds'][0]['id'], 'execution_need_type_id' => $before['executionNeeds'][0]['execution_need_type_id'], 'is_required' => true, 'planned_details' => 'Forged']]])->assertSessionHasErrors('execution_needs.0.execution_need_type_id');
    }

    public function test_volunteer_form_and_direct_requests_follow_all_four_scopes(): void
    {
        $user = $this->admin();
        $type = ExecutionNeedType::where('code', 'volunteers')->firstOrFail();
        foreach (ExecutionNeedType::usageScopes() as $scope) {
            $type->update(['usage_scope' => $scope]);
            $available = in_array($scope, ['iftars', 'both'], true);
            $response = $this->get(route('events.ramadan.iftars.create'))->assertOk();
            $available ? $response->assertSee('name="volunteer_requirements[', false) : $response->assertDontSee('name="volunteer_requirements[', false);
            $payload = $this->iftarPayload($user) + ['volunteer_requirements' => [['planned_count' => 2, 'tasks_summary' => 'Test volunteers']]];
            $result = $this->post(route('events.ramadan.iftars.store'), $payload);
            $available ? $result->assertSessionHasNoErrors()->assertRedirect() : $result->assertSessionHasErrors('volunteer_requirements');
            $this->assertSame($available, ExecutionNeedType::ramadanAvailableTypes()->contains('code', 'volunteers'));
        }
        $this->assertDatabaseCount('subject_volunteer_requirements', 2);
    }

    public function test_monthly_title_edit_preserves_hidden_sponsors_partners_and_payload(): void
    {
        $user = $this->admin();
        $user->syncRoles(['relations_officer']);
        $payload = ['title' => 'Monthly original', 'description' => 'Test detailed description', 'activity_date' => '2026-10-05', 'proposed_date' => '2026-10-05',
            'branch_id' => $user->branch_id, 'location_type' => 'inside_center', 'internal_location' => 'Hall',
            'execution_status' => 'planned', 'submit_action' => 'draft',
            'has_sponsor' => true, 'sponsors' => [['name' => 'Historic sponsor']],
            'has_partners' => true, 'partners' => [['name' => 'Historic partner', 'role' => 'Support']],
            'needs_gifts' => true, 'gifts_count' => 3, 'gifts_description' => 'Historic gifts',
            'needs_volunteers' => true, 'required_volunteers' => 4, 'volunteer_age_from' => 18, 'volunteer_age_to' => 30,
            'volunteer_gender' => 'both', 'volunteer_tasks_summary' => 'Historic monthly volunteers',
            'requires_supplies' => true, 'supplies' => [['item_name' => 'Historic monthly supply', 'quantity' => 2, 'available' => true]],
            'team_groups' => [['team_name' => 'Historic monthly team', 'members' => [['member_name' => 'Historic monthly member']]]],
        ];
        $this->post(route('role.relations.activities.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $activity = MonthlyActivity::query()->sole();
        $sponsors = $activity->sponsors()->get()->toArray();
        $partners = $activity->partners()->get()->toArray();
        $oldPayload = $activity->execution_needs_payload;
        $relations = ['team', 'volunteerNeed', 'supplies'];
        $before = collect($relations)->mapWithKeys(fn ($relation) => [$relation => $activity->$relation()->get()->toArray()]);
        foreach (['official_sponsorship', 'external_partners', 'gifts_shields', 'volunteers', 'supplies', 'execution_team'] as $code) {
            ExecutionNeedType::where('code', $code)->firstOrFail()->update(['usage_scope' => 'none']);
            foreach (ExecutionNeedType::MONTHLY_INPUT_FIELDS[$code] as $field) unset($payload[$field]);
        }
        $payload['title'] = 'Monthly changed';
        $this->put(route('role.relations.activities.update', $activity), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Monthly changed', $activity->fresh()->title, json_encode([$activity->fresh()->only(['status', 'lifecycle_status', 'relations_manager_approval_status', 'executive_approval_status']), session('status')], JSON_UNESCAPED_UNICODE));
        $this->assertSame($sponsors, $activity->sponsors()->get()->toArray());
        $this->assertSame($partners, $activity->partners()->get()->toArray());
        foreach ($relations as $relation) $this->assertSame($before[$relation], $activity->$relation()->get()->toArray(), $relation);
        $this->assertSame($oldPayload['gifts'], $activity->fresh()->execution_needs_payload['gifts']);
        $this->assertTrue($activity->fresh()->has_sponsor);
        $this->assertTrue($activity->fresh()->execution_needs_payload['needs_gifts']);
        $this->get(route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'form' => 1]))->assertOk()->assertSee('Historic sponsor')->assertSee('Historic gifts')->assertDontSee('name="needs_gifts"', false);
        foreach (['sponsors', 'partners', 'gifts_count'] as $field) $this->put(route('role.relations.activities.update', $activity), $payload + [$field => []])->assertSessionHasErrors($field);
    }

    public function test_year_dates_must_match_and_calendar_shows_inactive_historical_records(): void
    {
        $user = $this->admin();
        $payload = $this->iftarPayload($user);
        $this->post(route('events.ramadan.iftars.store'), $payload)->assertSessionHasNoErrors();
        $iftar = RamadanIftar::query()->sole();
        RamadanPeriod::where('year', 2026)->update(['is_active' => false, 'start_date' => '2026-02-25']);
        RamadanPeriod::create(['year' => 2027, 'start_date' => '2027-02-08', 'end_date' => '2027-03-09', 'is_active' => true]);
        Setting::updateOrCreate(['key' => 'ramadan_default_year'], ['value' => '2027']);
        $this->get(route('events.ramadan.iftars.calendar', ['year' => 2026]))->assertOk()->assertSee('Original title')->assertSee('غير فعالة');
        $this->get(route('events.ramadan.iftars.calendar', ['year' => 2027]))->assertOk()->assertDontSee('Original title');
        $payload['title'] = 'Historic edit';
        $payload['execution_needs'][0]['id'] = $iftar->executionNeeds()->sole()->id;
        $payload['execution_teams'][0]['id'] = $iftar->executionTeams()->sole()->id;
        $payload['execution_teams'][0]['members'][0]['id'] = $iftar->executionTeams()->sole()->members()->sole()->id;
        $this->put(route('events.ramadan.iftars.update', $iftar), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('Historic edit', $iftar->fresh()->title);
        $this->put(route('events.ramadan.iftars.update', $iftar), array_replace($payload, ['planned_date' => '2026-02-21']))->assertSessionHasErrors('planned_date');
        $this->assertTrue(Validator::make(['year' => 2027, 'start_date' => '2026-02-18', 'end_date' => '2027-03-19'], RamadanPeriod::rules())->fails());
        $this->assertSame('2027', Setting::valueOf('ramadan_default_year'));
    }

    public function test_editing_a_season_does_not_change_default_year_and_scope_choice_survives_seeding(): void
    {
        $this->admin();
        Setting::updateOrCreate(['key' => 'ramadan_default_year'], ['value' => '2026']);
        $team = ExecutionNeedType::where('code', 'execution_team')->firstOrFail();
        $this->assertSame('both', $team->usage_scope);
        $payload = ['admin_reports_cache_ttl_minutes' => 10, 'admin_reports_cache_prefix' => 'test',
            'ramadan_period_year' => 2027, 'ramadan_period_start_date' => '2027-02-08', 'ramadan_period_end_date' => '2027-03-09',
            'execution_need_scopes' => [['id' => $team->id, 'usage_scope' => 'iftars']],
        ];
        $this->put(route('role.super_admin.site_settings.update'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2026', Setting::valueOf('ramadan_default_year'));
        $this->assertNotNull($team->fresh()->scope_configured_at);
        $team->update(['is_canonical' => false]);
        $this->seed(RamadanReferenceDataSeeder::class);
        $this->assertSame('iftars', $team->fresh()->usage_scope);
        $this->put(route('role.super_admin.site_settings.update'), array_replace($payload, ['ramadan_period_start_date' => '2026-02-08']))->assertSessionHasErrors('ramadan_period_start_date');
    }
}
