<?php

namespace Tests\Feature;

use App\Models\MonthlyActivity;
use App\Services\ConflictDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConflictDetectionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_detects_time_overlap_within_same_branch(): void
    {
        MonthlyActivity::factory()->create([
            'title' => 'Existing Event',
            'branch_id' => 1,
            'proposed_date' => '2026-03-19',
            'execution_time' => '10:00-12:00',
        ]);

        $conflicts = app(ConflictDetectionService::class)->findMonthlyActivityConflicts('2026-03-19', 1, null, '11:30-13:00');

        $this->assertContains('Existing Event', $conflicts);
    }
}
