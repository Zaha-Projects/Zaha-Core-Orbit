<?php

namespace Tests\Feature;

use App\Models\MonthlyActivity;
use App\Models\Role;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkflowGovernanceAndApprovalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_keeps_only_one_active_workflow_per_module_on_update(): void
    {
        $adminRole = Role::query()->create(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $first = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'w1', 'is_active' => true]);
        $second = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'w2', 'is_active' => false]);

        $this->actingAs($admin)
            ->put(route('role.super_admin.workflows.update', $second), [
                'module' => 'monthly_activities',
                'code' => 'w2',
                'is_active' => 1,
            ])
            ->assertRedirect();

        $this->assertFalse($first->fresh()->is_active);
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_unauthorized_user_cannot_approve_current_step(): void
    {
        $this->seedApprovalSetup();

        $unauthorizedRole = Role::query()->create(['name' => 'finance_officer', 'guard_name' => 'web']);
        $unauthorized = User::factory()->create();
        $unauthorized->assignRole($unauthorizedRole);

        $activity = MonthlyActivity::factory()->create(['status' => 'submitted']);

        $this->actingAs($unauthorized)
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'approved',
            ])
            ->assertForbidden();
    }

    public function test_changes_requested_requires_comment(): void
    {
        [$approver, $activity] = $this->seedApprovalSetup(withActivity: true);

        $this->actingAs($approver)
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'changes_requested',
                'comment' => '',
            ])
            ->assertStatus(422);
    }

    public function test_approve_sequential_flow_and_changes_then_resubmit(): void
    {
        [$approver, $activity] = $this->seedApprovalSetup(withActivity: true, withTwoSteps: true);

        $this->actingAs($approver)
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'approved',
            ])
            ->assertRedirect();

        $this->assertSame('in_review', $activity->fresh()->status);

        $this->actingAs($approver)
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'changes_requested',
                'comment' => 'Need revisions',
            ])
            ->assertRedirect();

        $this->assertSame('changes_requested', $activity->fresh()->status);

        $this->actingAs($activity->creator)
            ->patch(route('role.relations.activities.submit', $activity))
            ->assertRedirect();

        $this->assertSame('submitted', $activity->fresh()->status);
        $this->assertSame('in_progress', $activity->fresh()->workflowInstance->status);
    }

    private function seedApprovalSetup(bool $withActivity = false, bool $withTwoSteps = false): array
    {
        $relationsRole = Role::query()->firstOrCreate(['name' => 'relations_officer', 'guard_name' => 'web']);
        Role::query()->firstOrCreate(['name' => 'relations_manager', 'guard_name' => 'web']);

        $workflow = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'wf_' . uniqid(), 'is_active' => true]);
        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 's1',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'sub',
            'role_id' => $relationsRole->id,
            'is_editable' => true,
        ]);

        if ($withTwoSteps) {
            WorkflowStep::query()->create([
                'workflow_id' => $workflow->id,
                'step_key' => 's2',
                'step_order' => 2,
                'approval_level' => 1,
                'step_type' => 'main',
                'role_id' => $relationsRole->id,
                'is_editable' => true,
            ]);
        }

        $approver = User::factory()->create();
        $approver->assignRole($relationsRole);

        if (! $withActivity) {
            return [$approver];
        }

        $creator = User::factory()->create();
        $creator->assignRole($relationsRole);
        $activity = MonthlyActivity::factory()->create(['created_by' => $creator->id, 'status' => 'submitted']);

        app(\App\Services\DynamicWorkflowService::class)->forModel('monthly_activities', $activity);

        return [$approver, $activity];
    }
}
