<?php

namespace Tests\Feature;

use App\Console\Commands\MovementLegacyRoleHolders;
use App\Models\Role;
use App\Models\User;
use App\Modules\Movement\Support\MovementPermission as P;
use Database\Seeders\MovementPermissionSeeder;
use Database\Seeders\rightDatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Movement Phase 2 acceptance (plan Revision 3): permission grants per role.
 * Database test — run only against a disposable database (see CLAUDE.md, database safety).
 */
class MovementPermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    /** Plan §14.2 as approved (D3, D12). */
    private const EXPECTED = [
        'super_admin' => 'all',
        'movement_manager' => 'all-except-create-and-workspace',
        'relations_officer' => [P::REQUESTS_CREATE],
        'supervisor' => [P::REQUESTS_CREATE],
        'executive_manager' => [P::REPORTS_VIEW],
        'movement_driver' => [P::DRIVER_WORKSPACE],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // The verified production-safe chain; it ends with MovementPermissionSeeder.
        $this->seed(rightDatabaseSeeder::class);
        $this->resetPermissionCache();
    }

    private function resetPermissionCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function expectedFor(string $role): array
    {
        $expected = self::EXPECTED[$role] ?? [];
        if ($expected === 'all') {
            return P::all();
        }
        if ($expected === 'all-except-create-and-workspace') {
            return array_values(array_diff(P::all(), [P::REQUESTS_CREATE, P::DRIVER_WORKSPACE]));
        }

        return $expected;
    }

    private function movementPermissionsOf(Role $role): array
    {
        return $role->permissions()->where('name', 'like', 'movement.%')->pluck('name')->sort()->values()->all();
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user->fresh();
    }

    public function test_permission_catalogue_is_seeded_with_module_guard_and_labels(): void
    {
        $permissions = Permission::query()->where('name', 'like', 'movement.%')->get();

        $this->assertEqualsCanonicalizing(P::all(), $permissions->pluck('name')->all());
        foreach ($permissions as $permission) {
            $this->assertSame('web', $permission->guard_name);
            $this->assertSame('movement', $permission->module);
            $this->assertNotEmpty($permission->name_ar);
            $this->assertNotEmpty($permission->name_en);
        }
    }

    public function test_every_role_holds_exactly_its_section_14_2_movement_permissions(): void
    {
        $this->assertTrue(Role::query()->where('name', 'movement_driver')->exists(), 'movement_driver role missing.');

        foreach (Role::query()->where('guard_name', 'web')->get() as $role) {
            $expected = $this->expectedFor($role->name);
            sort($expected);

            $this->assertSame($expected, $this->movementPermissionsOf($role), "Role [{$role->name}] has unexpected movement permissions.");
        }
    }

    public function test_key_role_rules_hold_for_real_users(): void
    {
        $manager = $this->userWithRole('movement_manager');
        $this->assertFalse($manager->hasPermissionTo(P::REQUESTS_CREATE));
        $this->assertFalse($manager->hasPermissionTo(P::DRIVER_WORKSPACE));
        $this->assertTrue($manager->hasPermissionTo(P::REQUESTS_REVIEW));
        $this->assertTrue($manager->hasPermissionTo(P::REPORTS_VIEW));

        foreach (['relations_officer', 'supervisor'] as $requester) {
            $user = $this->userWithRole($requester);
            $this->assertTrue($user->hasPermissionTo(P::REQUESTS_CREATE), "{$requester} must be able to create requests.");
            $this->assertFalse($user->hasPermissionTo(P::REQUESTS_REVIEW));
        }

        $this->assertTrue($this->userWithRole('executive_manager')->hasPermissionTo(P::REPORTS_VIEW));
        $this->assertFalse($this->userWithRole('reports_viewer')->hasPermissionTo(P::REPORTS_VIEW));
        $this->assertTrue($this->userWithRole('movement_driver')->hasPermissionTo(P::DRIVER_WORKSPACE));
    }

    public function test_per_user_grant_works_and_per_user_denial_wins(): void
    {
        $staff = $this->userWithRole('staff');
        $this->assertFalse($staff->hasPermissionTo(P::REQUESTS_CREATE));
        $staff->givePermissionTo(P::REQUESTS_CREATE);
        $this->resetPermissionCache();
        $staff = $staff->fresh();
        $this->assertTrue($staff->hasPermissionTo(P::REQUESTS_CREATE));
        $this->assertTrue($staff->can(P::REQUESTS_CREATE));

        $supervisor = $this->userWithRole('supervisor');
        $supervisor->deniedPermissions()->attach(Permission::findByName(P::REQUESTS_CREATE, 'web'));
        $this->resetPermissionCache();
        $supervisor = $supervisor->fresh();
        $this->assertFalse($supervisor->hasPermissionTo(P::REQUESTS_CREATE), 'A per-user denial must override the role grant.');
    }

    public function test_super_admin_holds_every_movement_permission_explicitly_and_via_gate(): void
    {
        $role = Role::findByName('super_admin', 'web');
        $this->assertEqualsCanonicalizing(P::all(), $this->movementPermissionsOf($role));

        $admin = $this->userWithRole('super_admin');
        foreach (P::all() as $permission) {
            $this->assertTrue($admin->hasPermissionTo($permission));
            $this->assertTrue($admin->can($permission));
        }
        // Gate::before (AuthServiceProvider) also allows abilities that no permission defines.
        $this->assertTrue($admin->can('movement.some.future_ability'));
    }

    public function test_reseeding_is_idempotent_and_never_revokes(): void
    {
        // Manual grants an administrator might have made; the seeder must keep them.
        Role::findByName('reports_viewer', 'web')->givePermissionTo(P::REPORTS_VIEW);
        Role::findByName('movement_manager', 'web')->givePermissionTo(P::REQUESTS_CREATE);

        $snapshot = fn () => [
            DB::table('permissions')->orderBy('id')->get(['id', 'name', 'guard_name', 'module', 'action', 'name_ar', 'name_en'])->toArray(),
            DB::table('role_has_permissions')->orderBy('role_id')->orderBy('permission_id')->get()->toArray(),
            DB::table('roles')->orderBy('id')->get(['id', 'name', 'guard_name'])->toArray(),
        ];

        $before = $snapshot();
        $this->seed(MovementPermissionSeeder::class);
        $this->seed(MovementPermissionSeeder::class);
        $this->resetPermissionCache();

        $this->assertEquals($before, $snapshot());
        $this->assertTrue(Role::findByName('reports_viewer', 'web')->hasPermissionTo(P::REPORTS_VIEW));
        $this->assertTrue(Role::findByName('movement_manager', 'web')->hasPermissionTo(P::REQUESTS_CREATE));
    }

    public function test_legacy_roles_are_untouched(): void
    {
        $permissionsOf = fn (string $role) => Role::findByName($role, 'web')->permissions()->pluck('name')->sort()->values()->all();

        foreach (MovementLegacyRoleHolders::LEGACY_ROLES as $legacy) {
            $this->assertTrue(Role::query()->where('name', $legacy)->exists(), "Legacy role [{$legacy}] must still exist.");
            $this->assertSame([], array_values(array_filter($permissionsOf($legacy), fn ($p) => str_starts_with($p, 'movement.'))), "Legacy role [{$legacy}] must not receive movement permissions.");
        }

        $before = array_map($permissionsOf, MovementLegacyRoleHolders::LEGACY_ROLES);
        $this->seed(MovementPermissionSeeder::class);
        $this->resetPermissionCache();
        $this->assertSame($before, array_map($permissionsOf, MovementLegacyRoleHolders::LEGACY_ROLES));
    }

    public function test_legacy_role_holder_command_reports_holders_and_writes_nothing(): void
    {
        $officer = $this->userWithRole('transport_officer');
        $this->userWithRole('movement_viewer');
        $this->userWithRole('movement_manager');

        // Capture real command output (the test case mocks console output by default).
        $this->withoutMockingConsoleOutput();

        $statements = [];
        DB::listen(function ($query) use (&$statements) {
            $statements[] = $query->sql;
        });

        $this->assertSame(0, Artisan::call('movement:legacy-role-holders'));
        $output = Artisan::output();

        $this->assertNotEmpty($statements);
        foreach ($statements as $sql) {
            $this->assertMatchesRegularExpression('/^\s*select\b/i', $sql, "The holder report issued a non-SELECT statement: {$sql}");
        }

        $this->assertMatchesRegularExpression('/transport_officer\s*\|\s*1\b/', $output);
        $this->assertMatchesRegularExpression('/movement_viewer\s*\|\s*1\b/', $output);
        $this->assertMatchesRegularExpression('/movement_editor\s*\|\s*0\b/', $output);
        $this->assertStringContainsString($officer->email, $output);
        $this->assertStringNotContainsString('movement_manager', $output);
    }
}
