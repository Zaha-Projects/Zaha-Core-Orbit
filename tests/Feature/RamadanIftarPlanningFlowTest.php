<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\TargetGroup;
use App\Models\User;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\ExecutionTeam;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarMeal;
use App\Modules\Events\Models\SubjectSupply;
use App\Modules\Events\Models\SubjectTargetGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RamadanIftarPlanningFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_open_create_and_unauthorized_user_cannot(): void
    {
        $branch = Branch::factory()->create();
        $officer = $this->userWithRole('relations_officer', $branch, ['branches.view.own']);
        $staff = $this->userWithRole('staff', $branch);

        $this->actingAs($officer)->get(route('events.ramadan.iftars.create'))->assertOk()->assertViewIs('pages.events.ramadan.create');
        $this->actingAs($staff)->get(route('events.ramadan.iftars.create'))->assertForbidden();
    }

    public function test_store_controls_lifecycle_and_persists_nested_planning_with_derived_totals(): void
    {
        $branch = Branch::factory()->create();
        $officer = $this->userWithRole('relations_officer', $branch, ['branches.view.own']);
        $organization = CommunityOrganization::query()->create(['branch_id' => $branch->id, 'name' => 'Host']);
        $group = TargetGroup::query()->create(['name' => 'Families', 'is_active' => true, 'is_ramadan_iftar' => true]);
        $segment = BeneficiarySegment::query()->create(['code' => 'children', 'name_ar' => 'أطفال', 'name_en' => 'Children', 'dimension' => 'age']);

        $payload = $this->payload($branch, $officer, $organization, [
            'created_by' => User::factory()->create()->id,
            'status' => 'approved',
            'actual_attendance' => 999,
            'target_groups' => [['target_group_id' => $group->id, 'beneficiary_segment_id' => $segment->id, 'planned_count' => 12]],
            'meals' => [['description' => 'Dinner', 'planned_quantity' => 15, 'items' => [['name' => 'Rice', 'item_type' => 'main', 'quantity' => 15]]]],
            'gifts' => [['description' => 'Toy', 'planned_quantity' => 2, 'has_supporting_entity' => true, 'supporting_entity_name' => 'Donor', 'unit_value' => '2.50']],
            'program_segments' => [['name' => 'Welcome', 'executor_user_id' => $officer->id]],
            'execution_teams' => [['name' => 'Operations', 'members' => [['user_id' => $officer->id, 'task_description' => 'Setup']]]],
            'volunteer_requirements' => [['beneficiary_segment_id' => $segment->id, 'planned_count' => 3]],
            'supplies' => [['item_name' => 'Tables', 'planned_quantity' => 4, 'estimated_value' => '20.00']],
        ]);

        $this->actingAs($officer)->post(route('events.ramadan.iftars.store'), $payload)->assertRedirect();
        $iftar = RamadanIftar::query()->sole();
        $this->assertSame($officer->id, $iftar->created_by);
        $this->assertSame(RamadanIftar::STATUS_DRAFT, $iftar->status);
        $this->assertSame(RamadanIftar::EXECUTION_STATUS_PLANNED, $iftar->execution_status);
        $this->assertSame(12, $iftar->expected_attendance);
        $this->assertSame(15, $iftar->planned_meals_count);
        $this->assertNull($iftar->actual_attendance);
        $this->assertSame(EventSubjectTypes::RAMADAN_IFTAR, $iftar->targetGroupSelections()->sole()->subject_type);
        $this->assertSame('Rice', $iftar->meals()->sole()->items()->sole()->name);
        $this->assertSame('5.00', $iftar->gifts()->sole()->estimated_total_value);
        $this->assertCount(1, $iftar->programSegments);
        $this->assertCount(1, $iftar->executionTeams()->sole()->members);
        $this->assertCount(1, $iftar->volunteerRequirements);
        $this->assertCount(1, $iftar->supplies);
    }

    public function test_branch_references_and_other_values_are_validated(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $officer = $this->userWithRole('relations_officer', $branch, ['branches.view.own']);
        $foreignOrganization = CommunityOrganization::query()->create(['branch_id' => $otherBranch->id, 'name' => 'Foreign']);
        $otherGroup = TargetGroup::query()->create(['name' => 'Other', 'is_other' => true, 'is_active' => true, 'is_ramadan_iftar' => true]);
        $otherSegment = BeneficiarySegment::query()->create(['code' => 'other', 'name_ar' => 'أخرى', 'name_en' => 'Other', 'dimension' => 'other', 'is_other' => true]);
        $otherMobilization = \App\Modules\Events\Models\MobilizationMethod::query()->create(['code' => 'other', 'name_ar' => 'أخرى', 'name_en' => 'Other', 'is_other' => true]);

        $this->actingAs($officer)->post(route('events.ramadan.iftars.store'), $this->payload($branch, $officer, $foreignOrganization, [
            'mobilization_method_id' => $otherMobilization->id,
            'target_groups' => [['target_group_id' => $otherGroup->id, 'beneficiary_segment_id' => $otherSegment->id, 'planned_count' => 1]],
        ]))->assertSessionHasErrors(['community_organization_id', 'mobilization_method_other', 'target_groups.0.target_group_custom_text', 'target_groups.0.segment_custom_text']);
        $this->assertDatabaseCount('ramadan_iftars', 0);
    }

    public function test_branch_scoped_user_cannot_edit_another_branch_iftar(): void
    {
        $branchA = Branch::factory()->create();
        $branchB = Branch::factory()->create();
        $officerA = $this->userWithRole('relations_officer', $branchA, ['branches.view.own']);
        $officerB = $this->userWithRole('relations_officer', $branchB, ['branches.view.own']);
        $organizationB = CommunityOrganization::query()->create(['branch_id' => $branchB->id, 'name' => 'Host B']);
        [, , , $iftarB] = $this->draftFixture($branchB, $officerB, $organizationB);

        $this->actingAs($officerA)->get(route('events.ramadan.iftars.edit', $iftarB))->assertForbidden();
    }

    public function test_update_is_scoped_preserves_actual_values_and_synchronizes_owned_rows(): void
    {
        [$branch, $officer, $organization, $iftar] = $this->draftFixture();
        $meal = $iftar->meals()->create(['description' => 'Old', 'planned_quantity' => 10, 'actual_quantity' => 8]);
        $removed = $iftar->gifts()->create(['description' => 'Remove', 'planned_quantity' => 1]);
        $supply = $iftar->supplies()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id, 'item_name' => 'Old', 'planned_quantity' => 2, 'actual_quantity' => 1, 'status' => 'pending']);
        $iftar->update(['actual_attendance' => 7]);

        $payload = $this->payload($branch, $officer, $organization, [
            'meals' => [['id' => $meal->id, 'description' => 'Updated', 'planned_quantity' => 12, 'items' => []], ['description' => 'New', 'planned_quantity' => 3, 'items' => []]],
            'supplies' => [['id' => $supply->id, 'item_name' => 'Updated', 'planned_quantity' => 5]],
        ]);
        $this->actingAs($officer)->put(route('events.ramadan.iftars.update', $iftar), $payload)->assertRedirect();

        $this->assertSame(7, $iftar->fresh()->actual_attendance);
        $this->assertSame(8, $meal->fresh()->actual_quantity);
        $this->assertSame(1, $supply->fresh()->actual_quantity);
        $this->assertDatabaseMissing('ramadan_iftar_gifts', ['id' => $removed->id]);
        $this->assertSame(2, $iftar->meals()->count());
    }

    public function test_foreign_child_ids_are_rejected_without_changing_either_iftar(): void
    {
        [$branch, $officer, $organization, $iftarA] = $this->draftFixture();
        [, , , $iftarB] = $this->draftFixture($branch, $officer, $organization);
        $foreignMeal = $iftarB->meals()->create(['description' => 'Protected', 'planned_quantity' => 2]);
        $foreignTeam = $iftarB->executionTeams()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftarB->id, 'name' => 'Protected']);
        $foreignSupply = $iftarB->supplies()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftarB->id, 'item_name' => 'Protected', 'planned_quantity' => 1, 'status' => 'pending']);
        $foreignTarget = $iftarB->targetGroupSelections()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftarB->id, 'target_group_id' => TargetGroup::query()->create(['name' => 'G'])->id, 'planned_count' => 1]);

        foreach ([
            ['meals' => [['id' => $foreignMeal->id, 'description' => 'Hacked', 'planned_quantity' => 1, 'items' => []]]],
            ['execution_teams' => [['id' => $foreignTeam->id, 'name' => 'Hacked', 'members' => []]]],
            ['supplies' => [['id' => $foreignSupply->id, 'item_name' => 'Hacked', 'planned_quantity' => 1]]],
            ['target_groups' => [['id' => $foreignTarget->id, 'target_group_id' => $foreignTarget->target_group_id, 'planned_count' => 1]]],
        ] as $override) {
            $this->actingAs($officer)->put(route('events.ramadan.iftars.update', $iftarA), $this->payload($branch, $officer, $organization, array_merge(['title' => 'Tampered'], $override)))->assertSessionHasErrors();
            $this->assertSame('Ramadan Plan', $iftarA->fresh()->title);
        }
        $this->assertSame('Protected', $foreignMeal->fresh()->description);
        $this->assertSame('Protected', $foreignTeam->fresh()->name);
        $this->assertSame('Protected', $foreignSupply->fresh()->item_name);
    }

    private function draftFixture(?Branch $branch = null, ?User $officer = null, ?CommunityOrganization $organization = null): array
    {
        $branch ??= Branch::factory()->create();
        $officer ??= $this->userWithRole('relations_officer', $branch, ['branches.view.own']);
        $organization ??= CommunityOrganization::query()->create(['branch_id' => $branch->id, 'name' => 'Host']);
        $iftar = RamadanIftar::query()->create(array_merge($this->payload($branch, $officer, $organization), [
            'created_by' => $officer->id, 'planned_meals_count' => 0, 'expected_attendance' => 0,
        ]));
        return [$branch, $officer, $organization, $iftar];
    }

    private function payload(Branch $branch, User $officer, CommunityOrganization $organization, array $override = []): array
    {
        return array_merge([
            'branch_id' => $branch->id, 'title' => 'Ramadan Plan', 'relations_officer_id' => $officer->id,
            'planned_date' => '2027-03-01', 'location_type' => RamadanIftar::LOCATION_OUTSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_ASSOCIATION, 'community_organization_id' => $organization->id,
            'target_groups' => [], 'meals' => [], 'gifts' => [], 'program_segments' => [],
            'execution_teams' => [], 'volunteer_requirements' => [], 'supplies' => [],
        ], $override);
    }

    private function userWithRole(string $roleName, Branch $branch, array $permissions = []): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        foreach ($permissions as $permission) $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole($role);
        return $user;
    }
}
