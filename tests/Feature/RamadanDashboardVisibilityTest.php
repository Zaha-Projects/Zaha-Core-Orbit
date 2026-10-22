<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\User;
use App\Modules\Events\Models\RamadanPeriod;
use Database\Seeders\RamadanDashboardSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RamadanDashboardVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_requires_active_period_and_permission(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        Permission::findOrCreate('ramadan_iftars.view', 'web');
        $user->givePermissionTo('ramadan_iftars.view');
        $this->seed(RamadanDashboardSettingSeeder::class);

        $this->assertNull($this->dashboardFor($user));
        RamadanPeriod::query()->create(['year' => 2026, 'hijri_year' => 1447, 'start_date' => '2026-02-18', 'end_date' => '2026-03-19', 'is_confirmed' => true, 'is_active' => true]);
        $this->assertIsArray($this->dashboardFor($user));

        $unauthorized = User::factory()->create(['branch_id' => $branch->id]);
        $this->assertNull($this->dashboardFor($unauthorized));
    }

    public function test_disabled_skips_period_and_metric_queries_and_seeder_preserves_choice(): void
    {
        $user = User::factory()->create(['branch_id' => Branch::factory()->create()->id]);
        Setting::query()->create(['key' => 'ramadan_dashboard_enabled', 'value' => '0']);
        $queries = [];
        DB::listen(function ($query) use (&$queries): void { $queries[] = $query->sql; });

        $this->assertNull($this->dashboardFor($user));
        $this->assertFalse(collect($queries)->contains(fn (string $sql) => str_contains($sql, 'ramadan_periods') || str_contains($sql, 'ramadan_iftars')));
        $this->seed(RamadanDashboardSettingSeeder::class);
        $this->assertSame('0', Setting::valueOf('ramadan_dashboard_enabled'));
    }

    public function test_only_super_admin_can_persist_dashboard_visibility(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $regular = User::factory()->create();
        $route = route('events.ramadan.admin.dashboard-visibility.update');

        $this->actingAs($regular)->put($route, ['ramadan_dashboard_enabled' => 0])->assertForbidden();
        $this->actingAs($admin)->put($route, ['ramadan_dashboard_enabled' => 0])->assertRedirect();
        $this->assertSame('0', Setting::valueOf('ramadan_dashboard_enabled'));
    }

    private function dashboardFor(User $user)
    {
        $method = new ReflectionMethod(DashboardController::class, 'configuredRamadanDashboard');
        $method->setAccessible(true);

        return $method->invoke(app(DashboardController::class), $user);
    }
}
