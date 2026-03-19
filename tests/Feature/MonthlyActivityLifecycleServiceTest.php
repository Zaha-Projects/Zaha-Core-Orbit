<?php

namespace Tests\Feature;

use App\Models\MonthlyActivity;
use App\Services\MonthlyActivityLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyActivityLifecycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_blocks_invalid_transition(): void
    {
        $activity = MonthlyActivity::factory()->create(['lifecycle_status' => 'Draft']);
        $service = app(MonthlyActivityLifecycleService::class);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $service->transitionOrFail($activity, 'Executed');
    }

    public function test_it_allows_valid_transition(): void
    {
        $activity = MonthlyActivity::factory()->create(['lifecycle_status' => 'Draft']);
        $service = app(MonthlyActivityLifecycleService::class);

        $service->transitionOrFail($activity, 'Submitted');

        $this->assertSame('Submitted', $activity->fresh()->lifecycle_status);
    }
}
