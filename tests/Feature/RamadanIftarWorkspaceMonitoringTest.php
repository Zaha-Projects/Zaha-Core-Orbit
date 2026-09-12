<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Events\Models\FieldVerification;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarMonitoringService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RamadanIftarWorkspaceMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    public function test_index_is_authorized_and_branch_filtered_before_pagination(): void
    {
        $own = Branch::factory()->create();
        $foreign = Branch::factory()->create();
        $viewer = User::factory()->create(['branch_id' => $own->id, 'status' => 'active']);
        $viewer->givePermissionTo(['ramadan_iftars.view', 'branches.view.own']);
        $this->iftar($own, $viewer, RamadanIftar::STATUS_DRAFT);
        for ($i = 0; $i < 16; $i++) $this->iftar($foreign, $viewer, RamadanIftar::STATUS_DRAFT, 'Foreign '.$i);

        $response = $this->actingAs($viewer)->get(route('events.ramadan.iftars.index'));
        $response->assertOk()->assertViewHas('iftars', function ($paginator) {
            return $paginator->total() === 1 && $paginator->count() === 1;
        });

        $unauthorized = User::factory()->create(['branch_id' => $own->id, 'status' => 'active']);
        $this->actingAs($unauthorized)->get(route('events.ramadan.iftars.index'))->assertForbidden();
    }

    public function test_show_rejects_wrong_branch_and_exposes_only_contextual_actions(): void
    {
        $branch = Branch::factory()->create();
        $planner = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $planner->givePermissionTo(['ramadan_iftars.view', 'ramadan_iftars.edit', 'ramadan_iftars.submit', 'branches.view.own']);
        $draft = $this->iftar($branch, $planner, RamadanIftar::STATUS_DRAFT);
        $this->actingAs($planner)->get(route('events.ramadan.iftars.show', $draft))
            ->assertOk()->assertSee(__('ramadan_iftars.actions.edit'))->assertSee(__('ramadan_iftars.actions.submit'))->assertDontSee(__('ramadan_iftars.actions.start_execution'));

        $draft->update(['status' => RamadanIftar::STATUS_SUBMITTED]);
        $this->actingAs($planner)->get(route('events.ramadan.iftars.show', $draft))
            ->assertOk()->assertDontSee(__('ramadan_iftars.actions.edit'))->assertDontSee(__('ramadan_iftars.actions.start_execution'));

        $executor = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $executor->givePermissionTo(['ramadan_iftars.view', 'ramadan_iftars.execute', 'branches.view.own']);
        $draft->update(['status' => RamadanIftar::STATUS_APPROVED, 'execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED]);
        $this->actingAs($executor)->get(route('events.ramadan.iftars.show', $draft))->assertOk()->assertSee(__('ramadan_iftars.actions.start_execution'))->assertDontSee(__('ramadan_iftars.actions.monitoring'));
        $executor->givePermissionTo('ramadan_iftars.monitor');
        $draft->update(['execution_status' => RamadanIftar::EXECUTION_STATUS_IN_PROGRESS]);
        $this->actingAs($executor)->get(route('events.ramadan.iftars.show', $draft))->assertOk()->assertSee(__('ramadan_iftars.actions.view_execution'))->assertSee(__('ramadan_iftars.actions.monitoring'));

        $wrongBranch = User::factory()->create(['branch_id' => Branch::factory()->create()->id, 'status' => 'active']);
        $wrongBranch->givePermissionTo(['ramadan_iftars.view', 'branches.view.own']);
        $this->actingAs($wrongBranch)->get(route('events.ramadan.iftars.show', $draft))->assertForbidden();
    }

    public function test_navigation_keeps_monthly_and_ramadan_as_separate_entries(): void
    {
        $blade = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString("route('role.relations.activities.index')", $blade);
        $this->assertStringContainsString("route('events.ramadan.iftars.index')", $blade);
        $this->assertStringContainsString("route('events.ramadan.approvals.index')", $blade);
        $this->assertStringContainsString("ramadan_iftars.navigation.title", $blade);
        $this->assertStringContainsString("events.ramadan.monitoring-reviews.index", file_get_contents(resource_path('views/pages/events/ramadan/index.blade.php')));
    }

    public function test_monitor_can_create_update_and_submit_server_snapshots(): void
    {
        $branch = Branch::factory()->create();
        $monitor = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $monitor->givePermissionTo(['ramadan_iftars.view', 'ramadan_iftars.monitor', 'branches.view.own']);
        $iftar = $this->iftar($branch, $monitor, RamadanIftar::STATUS_APPROVED);
        $iftar->update(['execution_status' => RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, 'actual_attendance' => 8]);
        $method = MonitoringMethod::query()->create(['code' => 'field_visit', 'name_ar' => 'زيارة', 'name_en' => 'Field visit', 'is_active' => true]);
        $service = app(RamadanIftarMonitoringService::class);
        $candidate = $service->candidates($iftar->fresh())[0];
        $data = ['monitoring_method_id' => $method->id, 'observed_at' => now(), 'general_notes' => 'Observed', 'verifications' => [[
            'detail_type' => null, 'detail_id' => null, 'field_key' => $candidate['field_key'], 'field_label' => 'Forged label',
            'planned_value' => ['value' => 999], 'actual_value' => ['value' => 999], 'match_status' => FieldVerification::MISMATCHED, 'note' => 'Variance',
        ]]];
        $report = $service->save($iftar, new MonitoringReport(), $data, $monitor);
        $verification = $report->verifications()->sole();
        $this->assertSame('Attendance', $verification->field_label);
        $this->assertSame(20, $verification->planned_value['value']);
        $this->assertSame(8, $verification->actual_value['value']);
        $this->assertSame($monitor->id, $verification->verified_by);
        $this->assertNotNull($verification->verified_at);

        $service->submit($iftar, $report, $monitor);
        $this->assertSame(MonitoringReport::STATUS_SUBMITTED, $report->fresh()->status);
        $this->assertNotNull($report->fresh()->submitted_at);
    }

    public function test_foreign_monitoring_report_is_rejected(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $first = $this->iftar($branch, $user, RamadanIftar::STATUS_APPROVED);
        $second = $this->iftar($branch, $user, RamadanIftar::STATUS_APPROVED, 'Second');
        $first->update(['execution_status' => RamadanIftar::EXECUTION_STATUS_IN_PROGRESS]);
        $second->update(['execution_status' => RamadanIftar::EXECUTION_STATUS_IN_PROGRESS]);
        $foreign = MonitoringReport::query()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $second->id, 'monitoring_method_id' => MonitoringMethod::query()->create(['code' => 'camera', 'name_ar' => 'كاميرا', 'name_en' => 'Camera', 'is_active' => true])->id]);

        $this->expectException(ValidationException::class);
        app(RamadanIftarMonitoringService::class)->submit($first, $foreign, $user);
    }

    private function iftar(Branch $branch, User $creator, string $status, string $title = 'Ramadan Iftar'): RamadanIftar
    {
        return RamadanIftar::query()->create([
            'branch_id' => $branch->id, 'title' => $title, 'relations_officer_id' => $creator->id, 'created_by' => $creator->id,
            'planned_date' => '2026-03-01', 'location_type' => RamadanIftar::LOCATION_INSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_CENTER, 'planned_meals_count' => 10, 'expected_attendance' => 20,
            'status' => $status, 'execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED,
        ]);
    }
}
