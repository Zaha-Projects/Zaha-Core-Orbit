<?php

namespace Tests\Feature;

use App\Modules\Events\Models\RamadanIftarGift;
use App\Modules\Events\Models\SubjectTargetGroup;
use App\Modules\Events\Models\EventSupply;
use Tests\TestCase;

class RamadanSourceConsistencyAndDashboardTest extends TestCase
{
    public function test_subject_target_group_remains_bound_to_generalized_event_table(): void
    {
        $this->assertSame('event_target_group', (new SubjectTargetGroup())->getTable());
        $migrationSources = collect(glob(database_path('migrations/*.php')))
            ->map(fn (string $path) => file_get_contents($path))->implode("\n");

        $this->assertStringNotContainsString("Schema::create('subject_target_groups'", $migrationSources);
    }

    public function test_gift_type_contract_is_centralized(): void
    {
        $this->assertSame(['gifts', 'shields', 'both'], RamadanIftarGift::types());
    }

    public function test_supply_planning_availability_is_separate_from_execution_actual(): void
    {
        $supply = new EventSupply();
        $this->assertArrayHasKey('planned_available', $supply->getCasts());
        $this->assertArrayHasKey('is_available', $supply->getCasts());

        $planning = file_get_contents(app_path('Modules/Events/Services/RamadanIftarPlanningService.php'));
        $execution = file_get_contents(app_path('Modules/Events/Services/RamadanIftarExecutionService.php'));
        $this->assertStringContainsString("'planned_available'", $planning);
        $this->assertStringContainsString("['actual_quantity', 'is_available']", $execution);
    }

    public function test_dashboard_source_enforces_period_permission_scope_and_bounded_upcoming_query(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/DashboardController.php'));

        $this->assertStringContainsString('RamadanPeriod::active()', $source);
        $this->assertStringContainsString("can('ramadan_iftars.view')", $source);
        $this->assertStringContainsString("can('branches.view.all')", $source);
        $this->assertStringContainsString('scopedBranchIds()', $source);
        $this->assertStringContainsString("whereBetween('planned_date'", $source);
        $this->assertStringContainsString('limit(5)', $source);
        $this->assertStringNotContainsString('BRANCH_ID', $source);
    }

    public function test_dashboard_view_has_authorized_empty_and_quick_action_states(): void
    {
        $source = file_get_contents(resource_path('views/dashboard.blade.php'));

        $this->assertStringContainsString("$".'ramadanDashboard[\'can_create\']', $source);
        $this->assertStringContainsString('لا توجد إفطارات مسجلة ضمن فترة رمضان الحالية.', $source);
        $this->assertStringContainsString("events.ramadan.iftars.calendar", $source);
    }
}
