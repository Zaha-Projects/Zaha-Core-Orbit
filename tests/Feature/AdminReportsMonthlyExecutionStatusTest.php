<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Services\AdminReports\AdminReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsMonthlyExecutionStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_monthly_activity_is_not_counted_as_executed_in_admin_relations_report(): void
    {
        $branch = Branch::factory()->create();
        $creator = User::factory()->create(['branch_id' => $branch->id]);

        $approvedActivity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $creator->id,
            'activity_date' => '2026-06-10',
            'proposed_date' => '2026-06-10',
            'month' => 6,
            'day' => 10,
            'status' => 'approved',
            'execution_status' => 'planned',
            'lifecycle_status' => 'Exec Director Approved',
            'actual_date' => null,
        ]);

        $workflow = Workflow::query()->create([
            'code' => 'monthly_report_execution_status',
            'module' => 'monthly_activities',
            'is_active' => true,
        ]);

        WorkflowInstance::query()->create([
            'workflow_id' => $workflow->id,
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $approvedActivity->id,
            'status' => 'approved',
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $creator->id,
            'activity_date' => '2026-06-11',
            'proposed_date' => '2026-06-11',
            'month' => 6,
            'day' => 11,
            'status' => 'closed',
            'execution_status' => 'executed',
            'lifecycle_status' => 'Closed',
            'actual_date' => '2026-06-11',
        ]);

        $report = app(AdminReportsService::class)->build(2026, 6);
        $branchRow = $report['relations']['monthly_by_branch']
            ->firstWhere('branch', $branch->name);

        $this->assertNotNull($branchRow);
        $this->assertSame(2, $branchRow['total']);
        $this->assertSame(1, $branchRow['completed']);
    }
}
