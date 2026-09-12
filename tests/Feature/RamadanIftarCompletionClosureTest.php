<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ExecutionNeedType;
use App\Models\User;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Modules\Events\Services\RamadanIftarExecutionService;
use App\Modules\Events\Services\RamadanIftarMonitoringService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RamadanIftarCompletionClosureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    public function test_only_approved_in_progress_execution_may_attempt_completion(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        $service = app(RamadanIftarExecutionService::class);
        $iftar->update(['execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED]);
        $this->assertCompletionRejected($service, $iftar, $actor);
        foreach ([RamadanIftar::STATUS_DRAFT, RamadanIftar::STATUS_SUBMITTED, RamadanIftar::STATUS_CHANGES_REQUESTED] as $status) {
            $iftar->update(['status' => $status, 'execution_status' => RamadanIftar::EXECUTION_STATUS_IN_PROGRESS]);
            $this->assertCompletionRejected($service, $iftar, $actor);
        }
    }

    public function test_required_execution_need_must_have_completed_result_and_details(): void
    {
        [$iftar, $actor, $need] = $this->readyIftar(false);
        $plannedDate = $iftar->planned_date->toDateString();
        $approvedAt = $iftar->approved_at->toDateTimeString();
        $this->assertCompletionRejected(app(RamadanIftarExecutionService::class), $iftar, $actor);
        $need->update(['status' => SubjectExecutionNeed::STATUS_COMPLETED, 'actual_details' => 'Transport provided', 'completed_at' => now()]);

        app(RamadanIftarExecutionService::class)->complete($iftar->fresh(), $actor);
        $iftar->refresh();
        $this->assertSame(RamadanIftar::EXECUTION_STATUS_COMPLETED, $iftar->execution_status);
        $this->assertSame(RamadanIftar::STATUS_APPROVED, $iftar->status);
        $this->assertNull($iftar->closed_at);
        $this->assertSame($plannedDate, $iftar->planned_date->toDateString());
        $this->assertSame($approvedAt, $iftar->approved_at->toDateTimeString());
    }

    public function test_zero_attendance_is_captured_but_null_attendance_blocks_completion(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        $iftar->update(['actual_attendance' => null]);
        $this->assertCompletionRejected(app(RamadanIftarExecutionService::class), $iftar, $actor);
        $iftar->update(['actual_attendance' => 0]);
        app(RamadanIftarExecutionService::class)->complete($iftar->fresh(), $actor);
        $this->assertSame(RamadanIftar::EXECUTION_STATUS_COMPLETED, $iftar->fresh()->execution_status);
    }

    public function test_duplicate_completion_is_rejected_without_duplicate_audit(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        $service = app(RamadanIftarExecutionService::class);
        $service->complete($iftar, $actor);
        $this->assertCompletionRejected($service, $iftar->fresh(), $actor);
        $this->assertDatabaseCount('workflow_action_logs', 1);
        $this->assertDatabaseHas('workflow_action_logs', ['action_type' => 'execution_completed']);
    }

    public function test_completion_endpoint_rechecks_permission_and_branch(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        $this->actingAs($actor)->post(route('events.ramadan.iftars.execution.complete', $iftar))->assertRedirect();

        [$otherIftar] = $this->readyIftar();
        $wrongBranch = User::factory()->create(['branch_id' => Branch::factory()->create()->id, 'status' => 'active']);
        $wrongBranch->givePermissionTo(['ramadan_iftars.execute', 'branches.view.own']);
        $this->actingAs($wrongBranch)->post(route('events.ramadan.iftars.execution.complete', $otherIftar))->assertForbidden();

        $unauthorized = User::factory()->create(['branch_id' => $otherIftar->branch_id, 'status' => 'active']);
        $this->actingAs($unauthorized)->post(route('events.ramadan.iftars.execution.complete', $otherIftar))->assertForbidden();
    }

    public function test_completed_workspace_is_read_only_and_has_no_close_action(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        $actor->givePermissionTo(['ramadan_iftars.view', 'ramadan_iftars.monitor']);
        app(RamadanIftarExecutionService::class)->complete($iftar, $actor);
        $this->actingAs($actor)->get(route('events.ramadan.iftars.show', $iftar))
            ->assertOk()->assertSee('View execution')->assertSee('Open')->assertDontSee('Close Iftar');
        $this->actingAs($actor)->get(route('events.ramadan.iftars.execution.show', $iftar))
            ->assertOk()->assertDontSee('Save actual execution data');
    }

    public function test_existing_closed_record_rejects_execution_and_monitoring_writes(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        $iftar->update(['closed_at' => now()]);
        $this->assertCompletionRejected(app(RamadanIftarExecutionService::class), $iftar, $actor);
        $method = MonitoringMethod::query()->create(['code' => 'closed-check', 'name_ar' => 'فحص', 'name_en' => 'Check']);
        try {
            app(RamadanIftarMonitoringService::class)->save($iftar->fresh(), new MonitoringReport(), [
                'monitoring_method_id' => $method->id, 'verifications' => [],
            ], $actor);
            $this->fail('Closed monitoring evidence was changed.');
        } catch (ValidationException $exception) {
            $this->assertDatabaseCount('monitoring_reports', 0);
        }
    }

    private function readyIftar(bool $completedNeed = true): array
    {
        $branch = Branch::factory()->create();
        $actor = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $actor->givePermissionTo(['ramadan_iftars.execute', 'branches.view.own']);
        $iftar = RamadanIftar::query()->create([
            'branch_id' => $branch->id, 'title' => 'Completion ready', 'relations_officer_id' => $actor->id, 'created_by' => $actor->id,
            'planned_date' => '2026-03-01', 'actual_date' => '2026-03-01', 'location_type' => RamadanIftar::LOCATION_INSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_CENTER, 'planned_meals_count' => 0, 'actual_meals_count' => 0,
            'expected_attendance' => 0, 'actual_attendance' => 0, 'status' => RamadanIftar::STATUS_APPROVED,
            'execution_status' => RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, 'approved_at' => now(),
        ]);
        $type = ExecutionNeedType::query()->create(['code' => 'required-'.$iftar->id, 'name' => 'Required need']);
        $need = SubjectExecutionNeed::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id,
            'execution_need_type_id' => $type->id, 'is_required' => true, 'planned_details' => 'Required',
            'status' => $completedNeed ? SubjectExecutionNeed::STATUS_COMPLETED : SubjectExecutionNeed::STATUS_PENDING,
            'actual_details' => $completedNeed ? 'Provided' : null, 'completed_at' => $completedNeed ? now() : null,
        ]);

        return [$iftar, $actor, $need];
    }

    private function assertCompletionRejected(RamadanIftarExecutionService $service, RamadanIftar $iftar, User $actor): void
    {
        try {
            $service->complete($iftar->fresh(), $actor);
            $this->fail('Completion should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertNotSame(RamadanIftar::EXECUTION_STATUS_COMPLETED, $iftar->fresh()->execution_status);
        }
    }
}
