<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DepartmentsManagementTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        $role = Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user);

        return $user;
    }

    public function test_super_admin_can_view_departments_page(): void
    {
        $this->actingAsSuperAdmin();
        $department = Department::create(['name' => 'Programs']);

        $response = $this->get(route('role.super_admin.departments'));

        $response->assertOk();
        $response->assertSee($department->name);
    }

    public function test_super_admin_can_create_department(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->post(route('role.super_admin.departments.store'), [
            'name' => 'Operations',
            'search' => 'Oper',
            'sort' => 'name_desc',
        ]);

        $response->assertRedirect(route('role.super_admin.departments', ['search' => 'Oper', 'sort' => 'name_desc']));
        $this->assertDatabaseHas('departments', ['name' => 'Operations']);
    }

    public function test_super_admin_can_update_department(): void
    {
        $this->actingAsSuperAdmin();
        $department = Department::create(['name' => 'Old Name']);

        $response = $this->put(route('role.super_admin.departments.update', $department), [
            'name' => 'New Name',
            'search' => 'New',
            'sort' => 'name_asc',
        ]);

        $response->assertRedirect(route('role.super_admin.departments', ['search' => 'New', 'sort' => 'name_asc']));
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'New Name']);
    }

    public function test_super_admin_can_soft_delete_department(): void
    {
        $this->actingAsSuperAdmin();
        $department = Department::create(['name' => 'Temporary']);

        $response = $this->delete(route('role.super_admin.departments.destroy', $department), [
            'search' => 'Tem',
            'sort' => 'name_desc',
        ]);

        $response->assertRedirect(route('role.super_admin.departments', ['search' => 'Tem', 'sort' => 'name_desc']));
        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }

    public function test_non_super_admin_cannot_access_departments_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('role.super_admin.departments'));

        $response->assertForbidden();
    }
}
