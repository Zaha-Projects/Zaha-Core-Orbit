<?php

namespace Tests\Unit;

use App\Models\MonthlyActivity;
use App\Models\Role;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\DynamicWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DynamicWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rolls_back_and_resubmits_after_changes_requested(): void
    {
        $workflow = Workflow::query()->create([
            'module' => 'monthly_activities',
            'code' => 'monthly_flow',
            'is_active' => true,
        ]);

        $role = Role::query()->create(['name' => 'relations_officer', 'guard_name' => 'web']);
        $step1 = WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 's1',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'sub',
            'role_id' => $role->id,
            'is_editable' => true,
        ]);
        $step2 = WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 's2',
            'step_order' => 2,
            'approval_level' => 1,
            'step_type' => 'main',
            'role_id' => $role->id,
            'is_editable' => false,
        ]);

        $activity = MonthlyActivity::factory()->create();
        $actor = User::factory()->create();
        $actor->assignRole($role);

        $service = app(DynamicWorkflowService::class);
        $instance = $service->forModel('monthly_activities', $activity);

        $service->recordDecision($instance, $step1, $actor, 'approved');
        $instance = $instance->fresh();
        $this->assertSame('in_progress', $instance->status);
        $this->assertSame($step2->id, $instance->current_step_id);

        $service->recordDecision($instance, $step2, $actor, 'changes_requested', 'Fix budget');
        $instance = $instance->fresh();
        $this->assertSame('changes_requested', $instance->status);
        $this->assertSame($step1->id, $instance->current_step_id);

        $service->markResubmitted($instance);
        $this->assertSame('in_progress', $instance->fresh()->status);
    }

    public function test_it_finishes_on_rejected(): void
    {
        $workflow = Workflow::query()->create([
            'module' => 'monthly_activities',
            'code' => 'monthly_flow_reject',
            'is_active' => true,
        ]);

        $role = Role::query()->create(['name' => 'programs_manager', 'guard_name' => 'web']);
        $step = WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 's1',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'main',
            'role_id' => $role->id,
            'is_editable' => true,
        ]);

        $activity = MonthlyActivity::factory()->create();
        $actor = User::factory()->create();
        $actor->assignRole($role);

        $service = app(DynamicWorkflowService::class);
        $instance = $service->forModel('monthly_activities', $activity);

        $service->recordDecision($instance, $step, $actor, 'rejected', 'Not aligned');

        $instance = $instance->fresh();
        $this->assertSame('rejected', $instance->status);
        $this->assertNull($instance->current_step_id);
        $this->assertNotNull($instance->completed_at);
    }
}
