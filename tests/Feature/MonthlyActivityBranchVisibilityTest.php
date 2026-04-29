<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyActivityBranchVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_khelda_helper_detects_hq_branch(): void
    {
        $branch = Branch::factory()->create(['name' => 'Khalda HQ', 'city' => 'Amman']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->assertTrue($user->isKheldaUser());
        $this->assertFalse($user->hasBranchScopedMonthlyVisibility());
    }

    public function test_non_khelda_branch_user_is_scoped_when_role_exists(): void
    {
        $branch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $role = Role::findOrCreate('relations_officer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('branches.view.own', 'web'));
        $user->assignRole($role);

        $this->assertFalse($user->isKheldaUser());
        $this->assertTrue($user->hasBranchScopedMonthlyVisibility());
    }

    public function test_branch_scoped_user_sees_only_own_branch_in_default_monthly_activities_index(): void
    {
        $primaryBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $secondaryBranch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $otherBranch = Branch::factory()->create(['name' => 'Aqaba Branch', 'city' => 'Aqaba']);

        $role = Role::findOrCreate('branch_coordinator', 'web');
        $viewPermission = Permission::findOrCreate('monthly_activities.view', 'web');
        $viewOtherBranchesPermission = Permission::findOrCreate('monthly_activities.view_other_branches', 'web');
        $ownBranchPermission = Permission::findOrCreate('branches.view.own', 'web');
        $role->givePermissionTo([$viewPermission, $viewOtherBranchesPermission, $ownBranchPermission]);

        $user = User::factory()->create(['branch_id' => $primaryBranch->id]);
        $user->assignRole($role);
        $user->assignedBranches()->sync([$primaryBranch->id, $secondaryBranch->id]);

        MonthlyActivity::factory()->create([
            'title' => 'Primary branch activity',
            'branch_id' => $primaryBranch->id,
            'status' => 'submitted',
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Secondary branch activity',
            'branch_id' => $secondaryBranch->id,
            'status' => 'submitted',
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Other branch activity',
            'branch_id' => $otherBranch->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.index'))
            ->assertOk()
            ->assertSee('Primary branch activity')
            ->assertDontSee('Secondary branch activity')
            ->assertDontSee('Other branch activity');
    }

    public function test_other_branches_scope_shows_approved_other_branch_plans_not_own_branch_plans(): void
    {
        $primaryBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $secondaryBranch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);

        $role = Role::findOrCreate('branch_coordinator', 'web');
        $viewPermission = Permission::findOrCreate('monthly_activities.view', 'web');
        $viewOtherBranchesPermission = Permission::findOrCreate('monthly_activities.view_other_branches', 'web');
        $ownBranchPermission = Permission::findOrCreate('branches.view.own', 'web');
        $role->givePermissionTo([$viewPermission, $viewOtherBranchesPermission, $ownBranchPermission]);

        $user = User::factory()->create(['branch_id' => $primaryBranch->id]);
        $user->assignRole($role);

        MonthlyActivity::factory()->create([
            'title' => 'Own approved plan',
            'branch_id' => $primaryBranch->id,
            'status' => 'approved',
            'executive_approval_status' => 'approved',
            'lifecycle_status' => 'Approved',
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Other approved plan',
            'branch_id' => $secondaryBranch->id,
            'status' => 'approved',
            'executive_approval_status' => 'approved',
            'lifecycle_status' => 'Approved',
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Other draft plan',
            'branch_id' => $secondaryBranch->id,
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.index', ['scope' => 'all_branches']))
            ->assertOk()
            ->assertSee('Other approved plan')
            ->assertDontSee('Own approved plan')
            ->assertDontSee('Other draft plan');
    }

    public function test_volunteer_coordinator_sees_only_monthly_activities_that_need_volunteers(): void
    {
        $role = Role::findOrCreate('volunteer_coordinator', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        MonthlyActivity::factory()->create([
            'title' => 'Needs volunteers',
            'status' => 'submitted',
            'needs_volunteers' => true,
            'required_volunteers' => 4,
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'No volunteers needed',
            'status' => 'submitted',
            'needs_volunteers' => false,
            'required_volunteers' => null,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.index'))
            ->assertOk()
            ->assertSee('Needs volunteers')
            ->assertDontSee('No volunteers needed');
    }

    public function test_volunteer_coordinator_cannot_open_activity_that_does_not_need_volunteers(): void
    {
        $role = Role::findOrCreate('volunteer_coordinator', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $activity = MonthlyActivity::factory()->create([
            'status' => 'submitted',
            'needs_volunteers' => false,
            'required_volunteers' => null,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.show', $activity))
            ->assertForbidden();
    }

    public function test_calendar_exposes_post_execution_button_only_to_activity_creator(): void
    {
        $role = Role::findOrCreate('relations_officer', 'web');
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $creator->assignRole($role);
        $otherUser->assignRole($role);

        $activity = MonthlyActivity::factory()->create([
            'title' => 'Creator calendar activity',
            'status' => 'submitted',
            'created_by' => $creator->id,
            'proposed_date' => '2026-03-19',
            'month' => 3,
            'day' => 19,
        ]);

        $this->actingAs($creator)
            ->getJson(route('role.relations.activities.calendar', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $activity->id,
                'can_complete_after_execution' => true,
            ]);

        $this->actingAs($otherUser)
            ->getJson(route('role.relations.activities.calendar', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $activity->id,
                'can_complete_after_execution' => false,
                'post_execution_url' => null,
            ]);
    }
}
