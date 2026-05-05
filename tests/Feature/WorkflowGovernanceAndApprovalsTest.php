<?php

namespace Tests\Feature;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\Role;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\DynamicWorkflowService;
use App\Services\MonthlyWorkflowPresenter;
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

    public function test_submit_advances_past_submission_step_to_first_approver(): void
    {
        [$approver, $activity] = $this->seedApprovalSetup(withActivity: true, withTwoSteps: true);

        $this->actingAs($activity->creator)
            ->patch(route('role.relations.activities.submit', $activity))
            ->assertRedirect();

        $activity = $activity->fresh()->load('workflowInstance.currentStep');

        $this->assertSame('submitted', $activity->status);
        $this->assertSame('in_progress', $activity->workflowInstance->status);
        $this->assertSame('s2', $activity->workflowInstance->currentStep?->step_key);
    }

    public function test_auto_approval_skips_enabled_approvers_and_continues_to_next_manual_step(): void
    {
        $creatorRole = Role::query()->firstOrCreate(['name' => 'relations_officer', 'guard_name' => 'web']);
        $autoRole = Role::query()->firstOrCreate(['name' => 'executive_manager', 'guard_name' => 'web']);
        $manualRole = Role::query()->firstOrCreate(['name' => 'relations_manager', 'guard_name' => 'web']);

        $workflow = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'wf_auto_' . uniqid(), 'is_active' => true]);

        foreach ([
            ['step_key' => 'submit', 'step_order' => 1, 'approval_level' => 1, 'step_type' => 'sub', 'role_id' => $creatorRole->id, 'is_editable' => true],
            ['step_key' => 'programs', 'step_order' => 2, 'approval_level' => 2, 'step_type' => 'main', 'role_id' => $autoRole->id, 'is_editable' => false],
            ['step_key' => 'relations', 'step_order' => 3, 'approval_level' => 3, 'step_type' => 'main', 'role_id' => $manualRole->id, 'is_editable' => false],
        ] as $definition) {
            WorkflowStep::query()->create(array_merge(['workflow_id' => $workflow->id], $definition));
        }

        $creator = User::factory()->create();
        $creator->assignRole($creatorRole);

        $autoApprover = User::factory()->create(['auto_approve_workflow_steps' => true]);
        $autoApprover->assignRole($autoRole);

        $manualApprover = User::factory()->create();
        $manualApprover->assignRole($manualRole);

        $activity = MonthlyActivity::factory()->create(['created_by' => $creator->id, 'status' => 'submitted']);

        $workflowService = app(DynamicWorkflowService::class);
        $instance = $workflowService->forModel('monthly_activities', $activity);
        $submitStep = $workflowService->currentStepForUser($instance, $creator);

        $workflowService->recordDecision($instance, $submitStep, $creator, DynamicWorkflowService::DECISION_APPROVED);

        $instance = $instance->fresh()->load('currentStep');

        $this->assertSame('relations', $instance->currentStep?->step_key);
        $this->assertDatabaseHas('workflow_logs', [
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => WorkflowStep::query()->where('step_key', 'programs')->value('id'),
            'acted_by' => $autoApprover->id,
            'action' => DynamicWorkflowService::DECISION_APPROVED,
        ]);
        $this->assertNotNull($workflowService->currentStepForUser($instance, $manualApprover));
    }

    public function test_auto_approval_toggle_processes_items_already_waiting_for_user(): void
    {
        $creatorRole = Role::query()->firstOrCreate(['name' => 'relations_officer', 'guard_name' => 'web']);
        $executiveRole = Role::query()->firstOrCreate(['name' => 'executive_manager', 'guard_name' => 'web']);

        $workflow = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'wf_exec_auto_' . uniqid(), 'is_active' => true]);
        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'submit',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'sub',
            'role_id' => $creatorRole->id,
            'is_editable' => true,
        ]);
        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'executive',
            'step_order' => 2,
            'approval_level' => 2,
            'step_type' => 'main',
            'role_id' => $executiveRole->id,
            'is_editable' => false,
        ]);

        $creator = User::factory()->create();
        $creator->assignRole($creatorRole);

        $approver = User::factory()->create();
        $approver->assignRole($executiveRole);

        $activity = MonthlyActivity::factory()->create(['created_by' => $creator->id, 'status' => 'submitted']);

        $workflowService = app(DynamicWorkflowService::class);
        $instance = $workflowService->forModel('monthly_activities', $activity);
        $submitStep = $workflowService->currentStepForUser($instance, $creator);
        $workflowService->recordDecision($instance, $submitStep, $creator, DynamicWorkflowService::DECISION_APPROVED);

        $this->assertSame('executive', $instance->fresh()->currentStep?->step_key);

        $this->actingAs($approver)
            ->patch(route('workflow_auto_approval.update'), [
                'auto_approve_workflow_steps' => true,
            ])
            ->assertRedirect();

        $this->assertTrue($approver->fresh()->auto_approve_workflow_steps);
        $this->assertSame(DynamicWorkflowService::DECISION_APPROVED, $instance->fresh()->status);
    }

    public function test_non_executive_approvers_cannot_enable_auto_approval(): void
    {
        $approverRole = Role::query()->firstOrCreate(['name' => 'relations_manager', 'guard_name' => 'web']);

        $workflow = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'wf_non_exec_auto_' . uniqid(), 'is_active' => true]);
        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'relations_review',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'main',
            'role_id' => $approverRole->id,
            'is_editable' => false,
        ]);

        $approver = User::factory()->create();
        $approver->assignRole($approverRole);

        $this->assertFalse(app(DynamicWorkflowService::class)->userMayAutoApproveWorkflow('monthly_activities', $approver));

        $this->actingAs($approver)
            ->patch(route('workflow_auto_approval.update'), [
                'auto_approve_workflow_steps' => true,
            ])
            ->assertForbidden();

        $this->assertFalse($approver->fresh()->auto_approve_workflow_steps);
    }

    public function test_submit_only_workflow_users_cannot_enable_auto_approval(): void
    {
        $submitRole = Role::query()->firstOrCreate(['name' => 'relations_officer', 'guard_name' => 'web']);
        $approverRole = Role::query()->firstOrCreate(['name' => 'relations_manager', 'guard_name' => 'web']);

        $workflow = Workflow::query()->create(['module' => 'agenda', 'code' => 'wf_submit_only_' . uniqid(), 'is_active' => true]);
        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'submit',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'sub',
            'role_id' => $submitRole->id,
            'is_editable' => true,
        ]);
        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'review',
            'step_order' => 2,
            'approval_level' => 2,
            'step_type' => 'main',
            'role_id' => $approverRole->id,
            'is_editable' => false,
        ]);

        $submitter = User::factory()->create();
        $submitter->assignRole($submitRole);

        $this->assertFalse(app(DynamicWorkflowService::class)->userMayAutoApproveWorkflow('agenda', $submitter));

        $this->actingAs($submitter)
            ->patch(route('workflow_auto_approval.update'), [
                'auto_approve_workflow_steps' => true,
            ])
            ->assertForbidden();

        $this->assertFalse($submitter->fresh()->auto_approve_workflow_steps);
    }

    public function test_agenda_approval_is_driven_by_dynamic_workflow_roles(): void
    {
        $approverRole = Role::query()->firstOrCreate(['name' => 'programs_manager', 'guard_name' => 'web']);
        $creatorRole = Role::query()->firstOrCreate(['name' => 'relations_officer', 'guard_name' => 'web']);

        $workflow = Workflow::query()->create([
            'module' => 'agenda',
            'code' => 'agenda_dynamic_' . uniqid(),
            'is_active' => true,
        ]);

        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'agenda_programs_review',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'main',
            'role_id' => $approverRole->id,
            'is_editable' => false,
        ]);

        $approver = User::factory()->create();
        $approver->assignRole($approverRole);

        $creator = User::factory()->create();
        $creator->assignRole($creatorRole);

        $event = AgendaEvent::query()->create([
            'event_date' => '2026-04-15',
            'month' => 4,
            'day' => 15,
            'event_name' => 'Dynamic agenda event',
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'status' => 'submitted',
            'relations_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
            'created_by' => $creator->id,
        ]);

        app(DynamicWorkflowService::class)->forModel('agenda', $event);

        $this->actingAs($approver)
            ->get(route('role.relations.approvals.index'))
            ->assertOk();

        $this->actingAs($approver)
            ->put(route('role.relations.approvals.update', $event), [
                'decision' => 'approved',
            ])
            ->assertRedirect();

        $event = $event->fresh()->load('workflowInstance');

        $this->assertSame('published', $event->status);
        $this->assertSame('approved', $event->workflowInstance?->status);
        $this->assertDatabaseHas('agenda_approvals', [
            'agenda_event_id' => $event->id,
            'step' => 'agenda_programs_review',
            'decision' => 'approved',
        ]);
    }

    public function test_relations_manager_cannot_approve_before_required_department_steps_are_completed(): void
    {
        $branch = Branch::factory()->create();

        $creatorRole = Role::query()->firstOrCreate(['name' => 'branch_relations_officer', 'guard_name' => 'web']);
        $branchManagerRole = Role::query()->firstOrCreate(['name' => 'branch_relations_manager', 'guard_name' => 'web']);
        $branchCoordinatorRole = Role::query()->firstOrCreate(['name' => 'branch_coordinator', 'guard_name' => 'web']);
        $relationsOfficerRole = Role::query()->firstOrCreate(['name' => 'relations_officer', 'guard_name' => 'web']);
        $programsManagerRole = Role::query()->firstOrCreate(['name' => 'programs_manager', 'guard_name' => 'web']);
        $workshopsSecretaryRole = Role::query()->firstOrCreate(['name' => 'workshops_secretary', 'guard_name' => 'web']);
        $communicationHeadRole = Role::query()->firstOrCreate(['name' => 'communication_head', 'guard_name' => 'web']);
        $relationsManagerRole = Role::query()->firstOrCreate(['name' => 'relations_manager', 'guard_name' => 'web']);

        $workflow = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'wf_seq_' . uniqid(), 'is_active' => true]);

        foreach ([
            ['step_key' => 'submit', 'step_order' => 1, 'approval_level' => 1, 'step_type' => 'sub', 'role_id' => $creatorRole->id],
            ['step_key' => 'branch_manager', 'step_order' => 2, 'approval_level' => 2, 'step_type' => 'main', 'role_id' => $branchManagerRole->id],
            ['step_key' => 'branch_coordinator', 'step_order' => 3, 'approval_level' => 3, 'step_type' => 'main', 'role_id' => $branchCoordinatorRole->id],
            ['step_key' => 'relations_officer', 'step_order' => 4, 'approval_level' => 4, 'step_type' => 'main', 'role_id' => $relationsOfficerRole->id],
            ['step_key' => 'programs', 'step_order' => 5, 'approval_level' => 5, 'step_type' => 'main', 'role_id' => $programsManagerRole->id, 'condition_field' => 'requires_programs', 'condition_value' => '1'],
            ['step_key' => 'workshops', 'step_order' => 6, 'approval_level' => 6, 'step_type' => 'main', 'role_id' => $workshopsSecretaryRole->id, 'condition_field' => 'requires_workshops', 'condition_value' => '1'],
            ['step_key' => 'communications', 'step_order' => 7, 'approval_level' => 7, 'step_type' => 'main', 'role_id' => $communicationHeadRole->id, 'condition_field' => 'requires_communications', 'condition_value' => '1'],
            ['step_key' => 'relations_manager', 'step_order' => 8, 'approval_level' => 8, 'step_type' => 'main', 'role_id' => $relationsManagerRole->id],
        ] as $definition) {
            WorkflowStep::query()->create(array_merge([
                'workflow_id' => $workflow->id,
                'is_editable' => $definition['step_type'] === 'sub',
            ], $definition));
        }

        $creator = User::factory()->create(['branch_id' => $branch->id]);
        $creator->assignRole($creatorRole);

        $branchManager = User::factory()->create(['branch_id' => $branch->id]);
        $branchManager->assignRole($branchManagerRole);

        $branchCoordinator = User::factory()->create(['branch_id' => $branch->id]);
        $branchCoordinator->assignRole($branchCoordinatorRole);
        $branchCoordinator->assignedBranches()->sync([$branch->id]);

        $relationsOfficer = User::factory()->create();
        $relationsOfficer->assignRole($relationsOfficerRole);

        $programsManager = User::factory()->create();
        $programsManager->assignRole($programsManagerRole);

        $workshopsSecretary = User::factory()->create();
        $workshopsSecretary->assignRole($workshopsSecretaryRole);

        $communicationHead = User::factory()->create();
        $communicationHead->assignRole($communicationHeadRole);

        $relationsManager = User::factory()->create();
        $relationsManager->assignRole($relationsManagerRole);

        $activity = MonthlyActivity::factory()->create([
            'created_by' => $creator->id,
            'branch_id' => $branch->id,
            'status' => 'submitted',
            'requires_programs' => true,
            'requires_workshops' => true,
            'requires_communications' => true,
        ]);

        $workflowService = app(DynamicWorkflowService::class);
        $instance = $workflowService->forModel('monthly_activities', $activity);

        foreach ([$creator, $branchManager, $branchCoordinator, $relationsOfficer] as $actor) {
            $step = $workflowService->currentStepForUser($instance->fresh(), $actor);
            $this->assertNotNull($step);
            $workflowService->recordDecision($instance->fresh(), $step, $actor, DynamicWorkflowService::DECISION_APPROVED);
        }

        $this->assertSame('programs', $instance->fresh()->currentStep?->step_key);

        $this->actingAs($relationsManager)
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'approved',
            ])
            ->assertForbidden();

        foreach ([$programsManager, $workshopsSecretary] as $actor) {
            $step = $workflowService->currentStepForUser($instance->fresh(), $actor);
            $this->assertNotNull($step);
            $workflowService->recordDecision($instance->fresh(), $step, $actor, DynamicWorkflowService::DECISION_APPROVED);
        }

        $this->assertSame('communications', $instance->fresh()->currentStep?->step_key);

        $this->actingAs($relationsManager)
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'approved',
            ])
            ->assertForbidden();

        $step = $workflowService->currentStepForUser($instance->fresh(), $communicationHead);
        $this->assertNotNull($step);
        $workflowService->recordDecision($instance->fresh(), $step, $communicationHead, DynamicWorkflowService::DECISION_APPROVED);

        $this->assertSame('relations_manager', $instance->fresh()->currentStep?->step_key);
        $this->assertNotNull($workflowService->currentStepForUser($instance->fresh(), $relationsManager));
    }

    public function test_relations_manager_can_publish_monthly_plan_without_executive_step(): void
    {
        [$activity, $actors] = $this->seedMonthlyBranchApprovalFlow();
        $workflowService = app(DynamicWorkflowService::class);
        $instance = $workflowService->forModel('monthly_activities', $activity);

        foreach (['creator', 'branch_manager', 'branch_coordinator'] as $actorKey) {
            $step = $workflowService->currentStepForUser($instance->fresh(), $actors[$actorKey]);
            $this->assertNotNull($step);
            $workflowService->recordDecision($instance->fresh(), $step, $actors[$actorKey], DynamicWorkflowService::DECISION_APPROVED);
        }

        $this->assertSame('monthly_relations_manager_review', $instance->fresh()->currentStep?->step_key);

        $this->actingAs($actors['relations_manager'])
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'approved_final',
            ])
            ->assertRedirect();

        $activity = $activity->fresh('workflowInstance');

        $this->assertSame('approved', $activity->status);
        $this->assertFalse((bool) $activity->executive_review_required);
        $this->assertSame('skipped', $activity->executive_approval_status);
        $this->assertSame('approved', $activity->workflowInstance?->status);
        $this->assertNull($activity->workflowInstance?->current_step_id);
    }

    public function test_relations_manager_can_send_monthly_plan_to_executive_before_publish(): void
    {
        [$activity, $actors] = $this->seedMonthlyBranchApprovalFlow();
        $workflowService = app(DynamicWorkflowService::class);
        $instance = $workflowService->forModel('monthly_activities', $activity);

        foreach (['creator', 'branch_manager', 'branch_coordinator'] as $actorKey) {
            $step = $workflowService->currentStepForUser($instance->fresh(), $actors[$actorKey]);
            $this->assertNotNull($step);
            $workflowService->recordDecision($instance->fresh(), $step, $actors[$actorKey], DynamicWorkflowService::DECISION_APPROVED);
        }

        $this->actingAs($actors['relations_manager'])
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'approved_send_executive',
            ])
            ->assertRedirect();

        $activity = $activity->fresh('workflowInstance.currentStep');

        $this->assertSame('in_review', $activity->status);
        $this->assertTrue((bool) $activity->executive_review_required);
        $this->assertSame('pending', $activity->executive_approval_status);
        $this->assertSame('monthly_executive_manager_final_approval', $activity->workflowInstance?->currentStep?->step_key);

        $this->actingAs($actors['executive_manager'])
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'approved',
            ])
            ->assertRedirect();

        $activity = $activity->fresh('workflowInstance');

        $this->assertSame('approved', $activity->status);
        $this->assertSame('approved', $activity->executive_approval_status);
        $this->assertSame('approved', $activity->workflowInstance?->status);
    }

    public function test_primary_relations_officer_created_monthly_plan_goes_directly_to_relations_manager(): void
    {
        [$activity, $actors] = $this->seedMonthlyBranchApprovalFlow(primaryCreator: true);
        $workflowService = app(DynamicWorkflowService::class);
        $instance = $workflowService->forModel('monthly_activities', $activity);

        $this->actingAs($actors['creator'])
            ->patch(route('role.relations.activities.submit', $activity))
            ->assertRedirect();

        $activity = $activity->fresh('workflowInstance.currentStep');

        $this->assertSame('submitted', $activity->status);
        $this->assertSame('monthly_relations_manager_review', $activity->workflowInstance?->currentStep?->step_key);
        $this->assertNull($workflowService->currentStepForUser($activity->workflowInstance, $actors['creator']));
        $this->assertNotNull($workflowService->currentStepForUser($activity->workflowInstance, $actors['relations_manager']));
    }

    public function test_monthly_plan_skips_branch_coordinator_when_branch_has_no_coordinator(): void
    {
        [$activity, $actors] = $this->seedMonthlyBranchApprovalFlow(withCoordinatorAssignment: false);
        $workflowService = app(DynamicWorkflowService::class);
        $instance = $workflowService->forModel('monthly_activities', $activity);

        foreach (['creator', 'branch_manager'] as $actorKey) {
            $step = $workflowService->currentStepForUser($instance->fresh(), $actors[$actorKey]);
            $this->assertNotNull($step);
            $workflowService->recordDecision($instance->fresh(), $step, $actors[$actorKey], DynamicWorkflowService::DECISION_APPROVED);
        }

        $this->assertSame('monthly_relations_manager_review', $instance->fresh()->currentStep?->step_key);
    }

    public function test_primary_relations_officer_does_not_have_monthly_or_agenda_approval_access(): void
    {
        $relationsOfficerRole = Role::query()->firstOrCreate(['name' => 'relations_officer', 'guard_name' => 'web']);
        $relationsOfficer = User::factory()->create();
        $relationsOfficer->assignRole($relationsOfficerRole);

        $monthlyWorkflow = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'monthly_submit_only_' . uniqid(), 'is_active' => true]);
        WorkflowStep::query()->create([
            'workflow_id' => $monthlyWorkflow->id,
            'step_key' => 'submit',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'sub',
            'role_id' => $relationsOfficerRole->id,
            'is_editable' => true,
        ]);

        $agendaWorkflow = Workflow::query()->create(['module' => 'agenda', 'code' => 'agenda_submit_only_' . uniqid(), 'is_active' => true]);
        WorkflowStep::query()->create([
            'workflow_id' => $agendaWorkflow->id,
            'step_key' => 'agenda_submit',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'sub',
            'role_id' => $relationsOfficerRole->id,
            'is_editable' => true,
        ]);

        $workflowService = app(DynamicWorkflowService::class);

        $this->assertFalse($workflowService->userMayParticipateInWorkflow('monthly_activities', $relationsOfficer));
        $this->assertFalse($workflowService->userMayParticipateInWorkflow('agenda', $relationsOfficer));
    }

    public function test_monthly_presenter_marks_submission_step_as_awaiting_resubmission_after_changes_requested(): void
    {
        $creatorRole = Role::query()->firstOrCreate(['name' => 'branch_relations_officer', 'guard_name' => 'web']);
        $approverRole = Role::query()->firstOrCreate(['name' => 'relations_manager', 'guard_name' => 'web']);
        $branch = Branch::factory()->create();

        $workflow = Workflow::query()->create(['module' => 'monthly_activities', 'code' => 'wf_resubmit_' . uniqid(), 'is_active' => true]);
        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'submit',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'sub',
            'role_id' => $creatorRole->id,
            'is_editable' => true,
        ]);
        WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'review',
            'step_order' => 2,
            'approval_level' => 2,
            'step_type' => 'main',
            'role_id' => $approverRole->id,
            'is_editable' => false,
        ]);

        $creator = User::factory()->create(['branch_id' => $branch->id]);
        $creator->assignRole($creatorRole);

        $approver = User::factory()->create();
        $approver->assignRole($approverRole);

        $activity = MonthlyActivity::factory()->create([
            'created_by' => $creator->id,
            'status' => 'submitted',
            'branch_id' => $branch->id,
        ]);

        $workflowService = app(DynamicWorkflowService::class);
        $instance = $workflowService->forModel('monthly_activities', $activity);

        $submitStep = $workflowService->currentStepForUser($instance->fresh(), $creator);
        $this->assertNotNull($submitStep);
        $workflowService->recordDecision($instance->fresh(), $submitStep, $creator, DynamicWorkflowService::DECISION_APPROVED);

        $reviewStep = $workflowService->currentStepForUser($instance->fresh(), $approver);
        $this->assertNotNull($reviewStep);
        $workflowService->recordDecision($instance->fresh(), $reviewStep, $approver, DynamicWorkflowService::DECISION_CHANGES_REQUESTED, 'Needs revision');

        $summary = app(MonthlyWorkflowPresenter::class)->present($activity->fresh());
        $submitState = collect($summary['steps'])->firstWhere('key', 'submit');

        $this->assertSame('awaiting_resubmission', $submitState['state'] ?? null);
        $this->assertSame('changes_requested', $summary['status_key']);
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

    private function seedMonthlyBranchApprovalFlow(bool $primaryCreator = false, bool $withCoordinatorAssignment = true): array
    {
        $branch = Branch::factory()->create();

        $roles = collect([
            'branch_relations_officer',
            'relations_officer',
            'branch_relations_manager',
            'branch_coordinator',
            'relations_manager',
            'executive_manager',
        ])->mapWithKeys(fn (string $role): array => [
            $role => Role::query()->firstOrCreate(['name' => $role, 'guard_name' => 'web']),
        ]);

        $workflow = Workflow::query()->create([
            'module' => 'monthly_activities',
            'code' => 'monthly_branch_flow_' . uniqid(),
            'is_active' => true,
        ]);

        foreach ([
            ['step_key' => 'monthly_branch_relations_officer_submit', 'step_order' => 1, 'approval_level' => 1, 'step_type' => 'sub', 'role_id' => $roles['branch_relations_officer']->id, 'condition_field' => 'monthly_created_by_branch_relations', 'condition_value' => '1', 'is_editable' => true],
            ['step_key' => 'monthly_primary_relations_officer_submit', 'step_order' => 2, 'approval_level' => 2, 'step_type' => 'sub', 'role_id' => $roles['relations_officer']->id, 'condition_field' => 'monthly_created_by_primary_relations', 'condition_value' => '1', 'is_editable' => true],
            ['step_key' => 'monthly_branch_relations_manager_review', 'step_order' => 3, 'approval_level' => 3, 'step_type' => 'main', 'role_id' => $roles['branch_relations_manager']->id, 'condition_field' => 'monthly_created_by_branch_relations', 'condition_value' => '1', 'is_editable' => false],
            ['step_key' => 'monthly_branch_coordinator_review', 'step_order' => 4, 'approval_level' => 4, 'step_type' => 'main', 'role_id' => $roles['branch_coordinator']->id, 'condition_field' => 'monthly_branch_coordinator_required', 'condition_value' => '1', 'is_editable' => false],
            ['step_key' => 'monthly_relations_manager_review', 'step_order' => 5, 'approval_level' => 5, 'step_type' => 'main', 'role_id' => $roles['relations_manager']->id, 'is_editable' => false],
            ['step_key' => 'monthly_executive_manager_final_approval', 'step_order' => 6, 'approval_level' => 6, 'step_type' => 'main', 'role_id' => $roles['executive_manager']->id, 'condition_field' => 'executive_review_required', 'condition_value' => '1', 'is_editable' => false],
        ] as $definition) {
            WorkflowStep::query()->create(array_merge(['workflow_id' => $workflow->id], $definition));
        }

        $actors = [
            'creator' => User::factory()->create(['branch_id' => $branch->id]),
            'branch_manager' => User::factory()->create(['branch_id' => $branch->id]),
            'branch_coordinator' => User::factory()->create(['branch_id' => null]),
            'relations_manager' => User::factory()->create(),
            'executive_manager' => User::factory()->create(),
        ];

        $actors['creator']->assignRole($primaryCreator ? $roles['relations_officer'] : $roles['branch_relations_officer']);
        $actors['branch_manager']->assignRole($roles['branch_relations_manager']);
        $actors['branch_coordinator']->assignRole($roles['branch_coordinator']);
        if ($withCoordinatorAssignment) {
            $actors['branch_coordinator']->assignedBranches()->sync([$branch->id]);
        }
        $actors['relations_manager']->assignRole($roles['relations_manager']);
        $actors['executive_manager']->assignRole($roles['executive_manager']);

        $activity = MonthlyActivity::factory()->create([
            'created_by' => $actors['creator']->id,
            'branch_id' => $branch->id,
            'status' => 'submitted',
            'executive_review_required' => false,
            'executive_approval_status' => 'skipped',
        ]);

        return [$activity, $actors];
    }
}
