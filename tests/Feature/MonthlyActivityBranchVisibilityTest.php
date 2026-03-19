<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
