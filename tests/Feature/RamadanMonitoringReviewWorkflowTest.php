<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\FieldVerification;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Services\RamadanIftarMonitoringService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RamadanMonitoringReviewWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    public function test_supervisor_can_approve_documented_mismatch_without_mutating_subject_data(): void
    {
        [$iftar, $report, $monitor, $reviewer] = $this->submittedReport(FieldVerification::MISMATCHED, 'Documented variance');
        $plannedDate = $iftar->planned_date->toDateString();
        $actualAttendance = $iftar->actual_attendance;

        app(RamadanIftarMonitoringService::class)->review($iftar, $report, $reviewer, MonitoringReport::STATUS_APPROVED, 'Accepted variance');

        $this->assertSame(MonitoringReport::STATUS_APPROVED, $report->fresh()->status);
        $this->assertSame($report->id, $iftar->fresh()->approvedMonitoringReportForClosure()->id);
        $this->assertSame($plannedDate, $iftar->fresh()->planned_date->toDateString());
        $this->assertSame($actualAttendance, $iftar->fresh()->actual_attendance);
        $this->assertDatabaseHas('workflow_action_logs', ['action_type' => 'monitoring_approved', 'performed_by' => $reviewer->id]);
    }

    public function test_undocumented_mismatch_cannot_be_approved(): void
    {
        [$iftar, $report, , $reviewer] = $this->submittedReport(FieldVerification::MISMATCHED, null);
        $this->expectException(ValidationException::class);
        app(RamadanIftarMonitoringService::class)->review($iftar, $report, $reviewer, MonitoringReport::STATUS_APPROVED, null);
    }

    public function test_submitted_report_can_be_returned_edited_and_resubmitted_with_audit_history(): void
    {
        [$iftar, $report, $monitor, $reviewer, $method] = $this->submittedReport();
        $service = app(RamadanIftarMonitoringService::class);
        $service->review($iftar, $report, $reviewer, MonitoringReport::STATUS_RETURNED, 'Clarify observation');
        $this->assertSame(MonitoringReport::STATUS_RETURNED, $report->fresh()->status);

        $verification = $report->verifications()->first();
        $service->save($iftar, $report->fresh(), [
            'monitoring_method_id' => $method->id,
            'general_notes' => 'Clarified',
            'verifications' => [[
                'id' => $verification->id, 'detail_type' => null, 'detail_id' => null,
                'field_key' => 'attendance', 'field_label' => 'forged',
                'match_status' => FieldVerification::MATCHED, 'note' => 'Confirmed',
            ]],
        ], $monitor);
        $service->submit($iftar, $report->fresh(), $monitor);

        $this->assertSame(MonitoringReport::STATUS_SUBMITTED, $report->fresh()->status);
        $this->assertDatabaseHas('workflow_action_logs', ['action_type' => 'monitoring_returned']);
        $this->assertDatabaseHas('workflow_action_logs', ['action_type' => 'monitoring_resubmitted']);
    }

    public function test_monitor_cannot_self_review_and_wrong_branch_reviewer_is_rejected(): void
    {
        [$iftar, $report, $monitor] = $this->submittedReport();
        $monitor->givePermissionTo('ramadan_iftars.monitor.review');
        try {
            app(RamadanIftarMonitoringService::class)->review($iftar, $report, $monitor, MonitoringReport::STATUS_APPROVED, null);
            $this->fail('Self-review must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(MonitoringReport::STATUS_SUBMITTED, $report->fresh()->status);
        }

        $other = User::factory()->create(['branch_id' => Branch::factory()->create()->id, 'status' => 'active']);
        $other->givePermissionTo(['ramadan_iftars.monitor.review', 'branches.view.own']);
        $this->actingAs($other)->post(route('events.ramadan.monitoring-reviews.decision', $report), ['decision' => 'approved'])->assertForbidden();
    }

    public function test_review_queue_filters_subject_branch_and_self_review_before_pagination(): void
    {
        [$iftar, $ownReport, , $reviewer] = $this->submittedReport();
        $foreignBranch = Branch::factory()->create();
        $foreignIftar = RamadanIftar::query()->create([
            'branch_id' => $foreignBranch->id, 'title' => 'Foreign', 'relations_officer_id' => $reviewer->id,
            'created_by' => $reviewer->id, 'planned_date' => '2026-03-01', 'location_type' => RamadanIftar::LOCATION_INSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_CENTER, 'planned_meals_count' => 0, 'expected_attendance' => 0,
            'status' => RamadanIftar::STATUS_APPROVED, 'execution_status' => RamadanIftar::EXECUTION_STATUS_COMPLETED,
        ]);
        for ($i = 0; $i < 16; $i++) {
            MonitoringReport::query()->create([
                'subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $foreignIftar->id,
                'monitoring_method_id' => $ownReport->monitoring_method_id, 'monitor_user_id' => $reviewer->id,
                'status' => MonitoringReport::STATUS_SUBMITTED, 'submitted_at' => now(),
            ]);
        }

        $this->actingAs($reviewer)->get(route('events.ramadan.monitoring-reviews.index'))
            ->assertOk()->assertViewHas('reports', function ($reports) use ($ownReport) {
                return $reports->total() === 1 && $reports->first()->id === $ownReport->id;
            });
        $this->assertSame($iftar->id, $ownReport->ramadanIftar->id);
    }

    public function test_approved_report_is_immutable_and_latest_approved_report_is_authoritative(): void
    {
        [$iftar, $first, $monitor, $reviewer, $method] = $this->submittedReport();
        $service = app(RamadanIftarMonitoringService::class);
        $service->review($iftar, $first, $reviewer, MonitoringReport::STATUS_APPROVED, null);
        $first->forceFill(['updated_at' => now()->subMinute()])->save();

        $second = MonitoringReport::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id,
            'monitoring_method_id' => $method->id, 'monitor_user_id' => $monitor->id,
            'status' => MonitoringReport::STATUS_SUBMITTED, 'submitted_at' => now(),
        ]);
        $second->verifications()->create([
            'field_key' => 'attendance', 'field_label' => 'Attendance',
            'planned_value' => ['value' => 10], 'actual_value' => ['value' => 10],
            'match_status' => FieldVerification::MATCHED, 'verified_by' => $monitor->id, 'verified_at' => now(),
        ]);
        $service->review($iftar, $second, $reviewer, MonitoringReport::STATUS_APPROVED, null);
        $this->assertSame($second->id, $iftar->fresh()->approvedMonitoringReportForClosure()->id);

        $this->expectException(ValidationException::class);
        $service->save($iftar, $second->fresh(), ['monitoring_method_id' => $method->id, 'verifications' => []], $monitor);
    }

    private function submittedReport(string $match = FieldVerification::MATCHED, ?string $note = null): array
    {
        $branch = Branch::factory()->create();
        $monitor = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $reviewer = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $reviewer->assignRole('supervisor');
        $iftar = RamadanIftar::query()->create([
            'branch_id' => $branch->id, 'title' => 'Review Iftar', 'relations_officer_id' => $monitor->id,
            'created_by' => $monitor->id, 'planned_date' => '2026-03-01', 'actual_date' => '2026-03-01',
            'location_type' => RamadanIftar::LOCATION_INSIDE_CENTER, 'host_type' => RamadanIftar::HOST_CENTER,
            'planned_meals_count' => 0, 'actual_meals_count' => 0, 'expected_attendance' => 10,
            'actual_attendance' => 10, 'status' => RamadanIftar::STATUS_APPROVED,
            'execution_status' => RamadanIftar::EXECUTION_STATUS_COMPLETED,
        ]);
        $method = MonitoringMethod::query()->create(['code' => 'review', 'name_ar' => 'زيارة ميدانية', 'name_en' => 'Field visit']);
        $report = MonitoringReport::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id,
            'monitoring_method_id' => $method->id, 'monitor_user_id' => $monitor->id,
            'status' => MonitoringReport::STATUS_SUBMITTED, 'submitted_at' => now(),
        ]);
        $report->verifications()->create([
            'field_key' => 'attendance', 'field_label' => 'Attendance',
            'planned_value' => ['value' => 10], 'actual_value' => ['value' => 10],
            'match_status' => $match, 'note' => $note, 'verified_by' => $monitor->id, 'verified_at' => now(),
        ]);

        return [$iftar, $report, $monitor, $reviewer, $method];
    }
}
