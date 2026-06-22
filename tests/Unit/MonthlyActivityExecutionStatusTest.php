<?php

namespace Tests\Unit;

use App\Models\MonthlyActivity;
use PHPUnit\Framework\TestCase;

class MonthlyActivityExecutionStatusTest extends TestCase
{
    public function test_planning_activity_with_legacy_executed_value_displays_pending_execution(): void
    {
        $activity = new MonthlyActivity([
            'status' => 'approved',
            'execution_status' => 'executed',
            'actual_date' => null,
        ]);

        $this->assertSame('planned', $activity->executionStatusForDisplay());
    }

    public function test_activity_displays_executed_after_actual_execution(): void
    {
        $activity = new MonthlyActivity();
        $activity->setRawAttributes([
            'status' => 'closed',
            'execution_status' => 'executed',
            'actual_date' => '2026-06-09',
        ], true);

        $this->assertSame('executed', $activity->executionStatusForDisplay());
    }

    public function test_activity_with_post_execution_payload_displays_executed_even_if_legacy_status_is_planned(): void
    {
        $activity = new MonthlyActivity([
            'status' => 'approved',
            'execution_status' => 'planned',
            'post_execution_payload' => [
                'teams' => [
                    ['team_name' => 'Unit team', 'accomplished_tasks' => 'Completed the activity.'],
                ],
            ],
        ]);

        $this->assertSame('executed', $activity->executionStatusForDisplay());
    }

    public function test_activity_with_actual_attendance_displays_executed_even_if_legacy_status_is_planned(): void
    {
        $activity = new MonthlyActivity([
            'status' => 'approved',
            'execution_status' => 'planned',
            'actual_attendance' => 25,
        ]);

        $this->assertSame('executed', $activity->executionStatusForDisplay());
    }

    public function test_postponed_and_cancelled_values_remain_visible(): void
    {
        $this->assertSame('postponed', (new MonthlyActivity(['execution_status' => 'postponed']))->executionStatusForDisplay());
        $this->assertSame('cancelled', (new MonthlyActivity(['execution_status' => 'cancelled']))->executionStatusForDisplay());
    }
}
