<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Center;
use App\Models\Department;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowLog;
use App\Models\WorkflowStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionReadinessMonthlyActivitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_volunteers_count_if_enabled(): void
    {
        [$user, $branch, $center] = $this->actingRelationsOfficer();

        $this->actingAs($user)
            ->post(route('role.relations.activities.store'), $this->validPayload($branch, [
                'center_id' => $center->id,
                'needs_volunteers' => 1,
                'required_volunteers' => null,
            ]))
            ->assertSessionHasErrors('required_volunteers');
    }

    public function test_validates_outside_contact_number_format(): void
    {
        [$user, $branch, $center] = $this->actingRelationsOfficer();

        $this->actingAs($user)
            ->post(route('role.relations.activities.store'), $this->validPayload($branch, [
                'center_id' => $center->id,
                'outside_contact_number' => '1234',
            ]))
            ->assertSessionHasErrors('outside_contact_number');
    }

    public function test_approved_activity_edit_creates_new_version_and_cancels_previous(): void
    {
        [$user, $branch, $center] = $this->actingRelationsOfficer();

        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'center_id' => $center->id,
            'created_by' => $user->id,
            'status' => 'approved',
            'executive_approval_status' => 'approved',
            'plan_version' => 1,
            'plan_stage' => 1,
            'proposed_date' => '2026-04-15',
            'activity_date' => '2026-04-15',
            'location_type' => 'inside_center',
            'description' => str_repeat('x', 30),
            'short_description' => 'short',
        ]);

        $this->actingAs($user)
            ->put(route('role.relations.activities.update', $activity), $this->validPayload($branch, [
                'center_id' => $center->id,
                'title' => 'Updated title',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('monthly_activities', [
            'id' => $activity->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('monthly_activities', [
            'previous_version_id' => $activity->id,
            'plan_version' => 2,
            'title' => 'Updated title',
        ]);
    }

    public function test_submitted_activity_with_existing_approval_trail_creates_new_version_when_edited(): void
    {
        [$user, $branch, $center] = $this->actingRelationsOfficer();

        $approverRole = Role::findOrCreate('relations_manager', 'web');
        $workflow = Workflow::query()->create([
            'module' => 'monthly_activities',
            'code' => 'monthly_versioning_' . uniqid(),
            'is_active' => true,
        ]);
        $step = WorkflowStep::query()->create([
            'workflow_id' => $workflow->id,
            'step_key' => 'relations_review',
            'step_order' => 1,
            'approval_level' => 1,
            'step_type' => 'main',
            'role_id' => $approverRole->id,
            'is_editable' => false,
        ]);

        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'center_id' => $center->id,
            'created_by' => $user->id,
            'status' => 'submitted',
            'plan_version' => 1,
            'plan_stage' => 1,
            'proposed_date' => '2026-04-15',
            'activity_date' => '2026-04-15',
            'location_type' => 'inside_center',
            'description' => str_repeat('x', 30),
            'short_description' => 'short',
        ]);

        $instance = WorkflowInstance::query()->create([
            'workflow_id' => $workflow->id,
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $activity->id,
            'status' => 'in_progress',
            'current_step_id' => $step->id,
            'started_at' => now(),
        ]);

        WorkflowLog::query()->create([
            'workflow_instance_id' => $instance->id,
            'workflow_step_id' => $step->id,
            'acted_by' => $user->id,
            'action' => 'approved',
            'edit_request_iteration' => 0,
            'acted_at' => now(),
        ]);

        $this->actingAs($user)
            ->put(route('role.relations.activities.update', $activity), $this->validPayload($branch, [
                'center_id' => $center->id,
                'title' => 'Submitted version edited',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('monthly_activities', [
            'id' => $activity->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('monthly_activities', [
            'previous_version_id' => $activity->id,
            'plan_version' => 2,
            'title' => 'Submitted version edited',
        ]);

        $this->assertDatabaseHas('workflow_instances', [
            'id' => $instance->id,
            'status' => 'rejected',
        ]);
    }

    public function test_superseded_activity_cannot_be_submitted_again(): void
    {
        [$user, $branch, $center] = $this->actingRelationsOfficer();

        $oldActivity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'center_id' => $center->id,
            'created_by' => $user->id,
            'status' => 'cancelled',
        ]);

        MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'center_id' => $center->id,
            'created_by' => $user->id,
            'previous_version_id' => $oldActivity->id,
            'plan_version' => 2,
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->patch(route('role.relations.activities.submit', $oldActivity))
            ->assertSessionHasErrors('status');
    }

    public function test_agenda_event_requires_owner_and_owner_not_partner(): void
    {
        $role = Role::findOrCreate('relations_manager', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $owner = Department::query()->create(['name' => 'Owner Dept']);

        $this->actingAs($user)
            ->post(route('role.relations.agenda.store'), [
                'event_name' => 'Event',
                'event_date' => '2026-04-20',
                'owner_department_id' => $owner->id,
                'partner_department_ids' => [$owner->id],
                'event_type' => 'mandatory',
                'plan_type' => 'unified',
            ])
            ->assertSessionHasErrors('partner_department_ids.0');
    }

    public function test_planning_attachment_accessor_is_backward_compatible(): void
    {
        $activity = MonthlyActivity::factory()->create([
            'branch_plan_file' => 'monthly/plans/legacy.docx',
        ]);

        $this->assertSame('monthly/plans/legacy.docx', $activity->planning_attachment);

        $activity->planning_attachment = 'monthly/plans/new.pdf';
        $activity->save();

        $this->assertSame('monthly/plans/new.pdf', $activity->fresh()->branch_plan_file);
    }

    private function actingRelationsOfficer(): array
    {
        $role = Role::findOrCreate('relations_officer', 'web');
        $branch = Branch::factory()->create();
        $center = Center::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create(['branch_id' => $branch->id, 'center_id' => $center->id]);
        $user->assignRole($role);

        return [$user, $branch, $center];
    }

    private function validPayload(Branch $branch, array $overrides = []): array
    {
        return array_merge([
            'title' => 'نشاط شهري جاهز للإنتاج',
            'activity_date' => '2026-04-15',
            'proposed_date' => '2026-04-15',
            'branch_id' => $branch->id,
            'status' => 'draft',
            'location_type' => 'inside_center',
            'internal_location' => 'قاعة التدريب',
            'description' => 'هذا وصف تفصيلي للنشاط الشهري بما يزيد عن عشرين حرفاً.',
            'short_description' => 'وصف قصير',
        ], $overrides);
    }
}
