<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\FieldVerification;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CommonEventMonitoringFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ramadan_allows_multiple_reports_with_method_monitor_and_draft_defaults(): void
    {
        [$iftar, $monitor, $method] = $this->fixture();
        $first = MonitoringReport::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'monitoring_method_id' => $method->id,
            'monitor_user_id' => $monitor->id,
        ]);
        $second = MonitoringReport::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'monitoring_method_id' => $method->id,
        ]);

        $this->assertSame(MonitoringReport::STATUS_DRAFT, $first->refresh()->status);
        $this->assertNull($first->observed_at);
        $this->assertNull($first->submitted_at);
        $this->assertTrue($first->monitoringMethod->is($method));
        $this->assertTrue($first->monitor->is($monitor));
        $this->assertSame([$first->id, $second->id], $iftar->monitoringReports()->orderBy('id')->pluck('id')->all());
    }

    public function test_same_numeric_subject_ids_are_isolated_by_type(): void
    {
        [$iftar, , $method] = $this->fixture();
        $monthlyActivity = MonthlyActivity::factory()->create();
        $ramadanReport = MonitoringReport::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'monitoring_method_id' => $method->id,
        ]);
        MonitoringReport::query()->create([
            'subject_type' => EventSubjectTypes::MONTHLY_ACTIVITY,
            'subject_id' => $monthlyActivity->id,
            'monitoring_method_id' => $method->id,
        ]);

        $this->assertSame($iftar->id, $monthlyActivity->id);
        $this->assertSame([$ramadanReport->id], $iftar->monitoringReports()->pluck('id')->all());
    }

    public function test_field_verifications_preserve_snapshots_labels_status_and_verifier(): void
    {
        [$iftar, $monitor, $method] = $this->fixture();
        $report = $this->report($iftar, $method);
        $verification = FieldVerification::query()->create([
            'monitoring_report_id' => $report->id,
            'detail_type' => 'meal',
            'detail_id' => 10,
            'field_key' => 'planned_quantity',
            'field_label' => 'Planned meal quantity',
            'planned_value' => ['value' => 100, 'unit' => 'meal'],
            'actual_value' => ['value' => 92, 'unit' => 'meal'],
            'match_status' => FieldVerification::MISMATCHED,
            'verified_by' => $monitor->id,
        ]);

        $verification->refresh();
        $this->assertSame(['value' => 100, 'unit' => 'meal'], $verification->planned_value);
        $this->assertSame(['value' => 92, 'unit' => 'meal'], $verification->actual_value);
        $this->assertSame('planned_quantity', $verification->field_key);
        $this->assertSame('Planned meal quantity', $verification->field_label);
        $this->assertSame(FieldVerification::MISMATCHED, $verification->match_status);
        $this->assertNull($verification->verified_at);
        $this->assertTrue($verification->monitoringReport->is($report));
        $this->assertTrue($verification->verifier->is($monitor));
    }

    public function test_same_field_key_is_allowed_for_multiple_details(): void
    {
        [$iftar, , $method] = $this->fixture();
        $report = $this->report($iftar, $method);

        foreach ([10, 11] as $detailId) {
            FieldVerification::query()->create([
                'monitoring_report_id' => $report->id,
                'detail_type' => 'meal',
                'detail_id' => $detailId,
                'field_key' => 'planned_quantity',
                'field_label' => 'Planned quantity',
                'match_status' => FieldVerification::MATCHED,
            ]);
        }

        $this->assertSame(2, $report->verifications()->count());
    }

    public function test_deleting_report_cascades_to_owned_verifications(): void
    {
        [$iftar, , $method] = $this->fixture();
        $report = $this->report($iftar, $method);
        $verification = FieldVerification::query()->create([
            'monitoring_report_id' => $report->id,
            'field_key' => 'attendance',
            'field_label' => 'Attendance',
            'match_status' => FieldVerification::NOT_OBSERVED,
        ]);

        $report->delete();

        $this->assertDatabaseMissing('field_verifications', ['id' => $verification->id]);
    }

    public function test_subject_scope_rejects_arbitrary_class_names(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MonitoringReport::query()->forSubject(User::class, 1)->get();
    }

    private function fixture(): array
    {
        $branch = Branch::factory()->create();
        $monitor = User::factory()->create(['branch_id' => $branch->id]);
        $method = MonitoringMethod::query()->create([
            'code' => 'field_visit',
            'name_ar' => 'زيارة ميدانية',
            'name_en' => 'Field visit',
        ]);
        $iftar = RamadanIftar::query()->create([
            'branch_id' => $branch->id,
            'title' => 'Ramadan Iftar',
            'relations_officer_id' => $monitor->id,
            'created_by' => $monitor->id,
            'planned_date' => '2027-03-01',
            'location_type' => RamadanIftar::LOCATION_OUTSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_LOCAL_COMMUNITY,
            'planned_meals_count' => 0,
            'expected_attendance' => 0,
        ]);

        return [$iftar, $monitor, $method];
    }

    private function report(RamadanIftar $iftar, MonitoringMethod $method): MonitoringReport
    {
        return MonitoringReport::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'monitoring_method_id' => $method->id,
        ]);
    }
}
