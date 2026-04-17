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
        Role::findOrCreate('relations_officer');
        $user->assignRole('relations_officer');

        $this->assertFalse($user->isKheldaUser());
        $this->assertTrue($user->hasBranchScopedMonthlyVisibility());
    }

    public function test_branch_scoped_user_can_see_assigned_branches_in_monthly_activities_index(): void
    {
        $primaryBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $secondaryBranch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $otherBranch = Branch::factory()->create(['name' => 'Aqaba Branch', 'city' => 'Aqaba']);

        $role = Role::findOrCreate('branch_coordinator', 'web');
        $viewPermission = Permission::findOrCreate('monthly_activities.view', 'web');
        $ownBranchPermission = Permission::findOrCreate('branches.view.own', 'web');
        $role->givePermissionTo([$viewPermission, $ownBranchPermission]);

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
            ->assertSee('Secondary branch activity')
            ->assertDontSee('Other branch activity');
    }
}
