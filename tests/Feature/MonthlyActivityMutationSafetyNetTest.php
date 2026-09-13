<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyActivityMutationSafetyNetTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_scoped_relations_officer_cannot_update_another_branch_activity(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $officer = $this->userWithRole('relations_officer', $ownBranch, ['branches.view.own']);
        $activity = MonthlyActivity::factory()->create(['branch_id' => $otherBranch->id]);

        $this->actingAs($officer)
            ->put(route('role.relations.activities.update', $activity), ['title' => 'Cross-branch change'])
            ->assertForbidden();

        $this->assertNotSame('Cross-branch change', $activity->fresh()->title);
    }

    public function test_unauthorized_role_cannot_delete_a_monthly_activity(): void
    {
        $branch = Branch::factory()->create();
        $staff = $this->userWithRole('staff', $branch);
        $activity = MonthlyActivity::factory()->create(['branch_id' => $branch->id]);

        $this->actingAs($staff)
            ->delete(route('role.relations.activities.destroy', $activity))
            ->assertForbidden();

        $this->assertDatabaseHas('monthly_activities', [
            'id' => $activity->id,
            'deleted_at' => null,
        ]);
    }

    public function test_authorized_officer_can_soft_delete_a_draft_and_admin_can_restore_it(): void
    {
        $branch = Branch::factory()->create();
        $officer = $this->userWithRole('relations_officer', $branch, ['branches.view.own']);
        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $officer->id,
            'status' => 'draft',
        ]);

        $this->actingAs($officer)
            ->delete(route('role.relations.activities.destroy', $activity))
            ->assertRedirect(route('role.relations.activities.index'))
            ->assertSessionHas('status', 'تم حذف الخطة الشهرية بنجاح.');

        $this->assertSoftDeleted('monthly_activities', ['id' => $activity->id]);
        $this->assertSame('cancelled', $activity->fresh()->status);

        $admin = $this->userWithRole('super_admin', $branch);

        $this->actingAs($admin)
            ->patch(route('role.relations.activities.trash.restore', $activity->id))
            ->assertRedirect(route('role.relations.activities.trash'))
            ->assertSessionHas('status', 'تمت استعادة الخطة الشهرية بنجاح.');

        $restored = MonthlyActivity::query()->findOrFail($activity->id);
        $this->assertSame('draft', $restored->status);
        $this->assertNull($restored->deleted_at);
    }

    private function userWithRole(string $roleName, Branch $branch, array $permissions = []): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole($role);

        return $user;
    }
}
