<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Models\User;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Modules\Events\Services\RamadanIftarExecutionService;
use App\Modules\Events\Services\RamadanIftarMonitoringService;
use App\Modules\Events\Services\RamadanIftarClosureService;
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
        try {
            $service->complete($iftar->fresh(), $actor);
            $this->fail('Completion should have been rejected.');
        } catch (ValidationException) {
            $this->assertSame(RamadanIftar::EXECUTION_STATUS_COMPLETED, $iftar->fresh()->execution_status);
        }
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

    public function test_completed_workspace_exposes_close_only_with_approved_monitoring_and_permission(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        $actor->givePermissionTo(['ramadan_iftars.view', 'ramadan_iftars.monitor']);
        app(RamadanIftarExecutionService::class)->complete($iftar, $actor);
        $this->actingAs($actor)->get(route('events.ramadan.iftars.show', $iftar))
            ->assertOk()->assertSee(__('ramadan_iftars.actions.view_execution'))->assertDontSee(__('ramadan_iftars.actions.close'));
        $supervisor = User::factory()->create(['branch_id' => $iftar->branch_id, 'status' => 'active']);
        $supervisor->assignRole('supervisor');
        $this->approvedReport($iftar, $actor);
        $this->actingAs($supervisor)->get(route('events.ramadan.iftars.show', $iftar))
            ->assertOk()->assertSee(__('ramadan_iftars.actions.close'));
        $this->actingAs($actor)->get(route('events.ramadan.iftars.execution.show', $iftar))
            ->assertOk()->assertDontSee(__('ramadan_iftars.actions.save_actual'));
    }

    public function test_closure_requires_completed_execution_and_approved_monitoring(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        $service = app(RamadanIftarClosureService::class);
        $closer = $this->closureActor($iftar);
        $this->assertClosureRejected($service, $iftar, $closer);
        app(RamadanIftarExecutionService::class)->complete($iftar, $actor);
        $this->assertClosureRejected($service, $iftar->fresh(), $closer);
        $report = $this->approvedReport($iftar->fresh(), $actor);
        $plannedDate = $iftar->fresh()->planned_date->toDateString();
        $actualAttendance = $iftar->fresh()->actual_attendance;

        $service->close($iftar->fresh(), $closer);
        $iftar->refresh();
        $this->assertNotNull($iftar->closed_at);
        $this->assertSame(RamadanIftar::STATUS_APPROVED, $iftar->status);
        $this->assertSame(RamadanIftar::EXECUTION_STATUS_COMPLETED, $iftar->execution_status);
        $this->assertSame($plannedDate, $iftar->planned_date->toDateString());
        $this->assertSame($actualAttendance, $iftar->actual_attendance);
        $this->assertDatabaseHas('workflow_action_logs', [
            'module' => RamadanIftar::WORKFLOW_MODULE, 'entity_id' => $iftar->id,
            'action_type' => 'iftar_closed', 'performed_by' => $closer->id,
        ]);
        $this->assertSame($report->id, data_get(\App\Models\WorkflowActionLog::query()->where('action_type', 'iftar_closed')->first()->meta, 'monitoring_report_id'));
    }

    public function test_documented_approved_mismatch_does_not_block_closure_and_duplicate_is_safe(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        app(RamadanIftarExecutionService::class)->complete($iftar, $actor);
        $report = $this->approvedReport($iftar->fresh(), $actor);
        $report->verifications()->create([
            'field_key' => 'attendance', 'field_label' => 'Attendance',
            'planned_value' => ['value' => 10], 'actual_value' => ['value' => 8],
            'match_status' => \App\Modules\Events\Models\PostExecutionVerification::MISMATCHED,
            'note' => 'Two invitees did not attend.', 'verified_by' => $actor->id, 'verified_at' => now(),
        ]);
        $service = app(RamadanIftarClosureService::class);
        $closer = $this->closureActor($iftar);
        $service->close($iftar->fresh(), $closer);
        $closedAt = $iftar->fresh()->closed_at->toDateTimeString();
        try {
            $service->close($iftar->fresh(), $closer);
            $this->fail('Closure should have been rejected.');
        } catch (ValidationException) {
            $this->assertSame($closedAt, $iftar->fresh()->closed_at->toDateTimeString());
        }
        $this->assertSame($closedAt, $iftar->fresh()->closed_at->toDateTimeString());
        $this->assertSame(1, \App\Models\WorkflowActionLog::query()->where('action_type', 'iftar_closed')->count());
    }

    public function test_closure_endpoint_enforces_supervisor_permission_and_branch(): void
    {
        [$iftar, $actor] = $this->readyIftar();
        app(RamadanIftarExecutionService::class)->complete($iftar, $actor);
        $this->approvedReport($iftar->fresh(), $actor);
        $supervisor = User::factory()->create(['branch_id' => $iftar->branch_id, 'status' => 'active']);
        $supervisor->assignRole('supervisor');
        $unauthorized = User::factory()->create(['branch_id' => $iftar->branch_id, 'status' => 'active']);
        $this->actingAs($unauthorized)->post(route('events.ramadan.iftars.close', $iftar))->assertForbidden();
        $this->actingAs($supervisor)->post(route('events.ramadan.iftars.close', $iftar))->assertRedirect();

        [$other, $otherActor] = $this->readyIftar();
        app(RamadanIftarExecutionService::class)->complete($other, $otherActor);
        $this->approvedReport($other->fresh(), $otherActor);
        $this->actingAs($supervisor)->post(route('events.ramadan.iftars.close', $other))->assertForbidden();
    }

    public function test_closed_iftar_remains_viewable_but_operational_writes_are_locked(): void
    {
        [$iftar, $executor] = $this->readyIftar();
        app(RamadanIftarExecutionService::class)->complete($iftar, $executor);
        $report = $this->approvedReport($iftar->fresh(), $executor);
        $closer = $this->closureActor($iftar);
        app(RamadanIftarClosureService::class)->close($iftar->fresh(), $closer);

        $this->actingAs($closer)->get(route('events.ramadan.iftars.show', $iftar))
            ->assertOk()
            ->assertSee(__('ramadan_iftars.closure.historical'))
            ->assertDontSee(__('ramadan_iftars.actions.close'));
        $this->actingAs($closer)->get(route('events.ramadan.iftars.edit', $iftar))->assertForbidden();

        try {
            app(RamadanIftarMonitoringService::class)->review($iftar->fresh(), $report, $closer, MonitoringReport::STATUS_RETURNED, 'Late change');
            $this->fail('Closed monitoring evidence was reviewed.');
        } catch (ValidationException $exception) {
            $this->assertSame(MonitoringReport::STATUS_APPROVED, $report->fresh()->status);
        }
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
        $type = ExecutionNeedType::query()->create(['code' => 'required-'.$iftar->id, 'name' => 'Required need '.$iftar->id]);
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

    private function closureActor(RamadanIftar $iftar): User
    {
        $user = User::factory()->create(['branch_id' => $iftar->branch_id, 'status' => 'active']);
        $user->assignRole('supervisor');

        return $user;
    }

    private function approvedReport(RamadanIftar $iftar, User $monitor): MonitoringReport
    {
        $method = MonitoringMethod::query()->create([
            'code' => 'closure-'.$iftar->id.'-'.MonitoringMethod::query()->count(),
            'name_ar' => 'متابعة الإغلاق '.$iftar->id, 'name_en' => 'Closure monitoring '.$iftar->id,
        ]);

        return $iftar->monitoringReports()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'monitoring_method_id' => $method->id, 'monitor_user_id' => $monitor->id,
            'status' => MonitoringReport::STATUS_APPROVED, 'submitted_at' => now(),
        ]);
    }

    private function assertClosureRejected(RamadanIftarClosureService $service, RamadanIftar $iftar, User $actor): void
    {
        try {
            $service->close($iftar, $actor);
            $this->fail('Closure should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertNull($iftar->fresh()->closed_at);
        }
    }
}
