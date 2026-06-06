<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyActivityApprovalsBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_approval_list_is_limited_to_own_branch(): void
    {
        $ownBranch = Branch::factory()->create(['name' => 'Own Approval Branch']);
        $otherBranch = Branch::factory()->create(['name' => 'Other Approval Branch']);

        $role = Role::findOrCreate('supervisor', 'web');
        $role->givePermissionTo(Permission::findOrCreate('branches.view.own', 'web'));

        $workflow = Workflow::create([
            'code' => 'monthly_scope_test',
            'name_ar' => 'Monthly Scope Test',
            'name_en' => 'Monthly Scope Test',
            'module' => 'monthly_activities',
            'is_active' => true,
        ]);

        WorkflowStep::create([
            'workflow_id' => $workflow->id,
            'step_order' => 1,
            'step_key' => 'monthly_supervisor_review',
            'name_ar' => 'Supervisor Review',
            'name_en' => 'Supervisor Review',
            'step_type' => 'main',
            'approval_level' => 1,
            'role_id' => $role->id,
            'is_editable' => true,
        ]);

        $supervisor = User::factory()->create(['branch_id' => $ownBranch->id]);
        $supervisor->assignRole($role);

        MonthlyActivity::factory()->create([
            'title' => 'Own branch approval activity',
            'branch_id' => $ownBranch->id,
            'status' => 'submitted',
            'is_from_agenda' => false,
            'agenda_event_id' => null,
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Other branch approval activity',
            'branch_id' => $otherBranch->id,
            'status' => 'submitted',
            'is_from_agenda' => false,
            'agenda_event_id' => null,
        ]);

        $this->actingAs($supervisor)
            ->get(route('role.programs.approvals.index'))
            ->assertOk()
            ->assertSee('Own branch approval activity')
            ->assertDontSee('Other branch approval activity')
            ->assertDontSee('Other Approval Branch');
    }
}
