<?php

namespace Tests\Feature;

use App\Models\InAppNotification;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Services\WorkflowNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyActivityApprovalsPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_my_pending_is_filtered_counted_and_ordered_before_pagination(): void
    {
        [$approver, $otherApprover, $workflow, $myStep, $otherStep] = $this->approvalSetup();

        $expectedIds = collect();
        foreach (range(1, 18) as $number) {
            $activity = $this->activityWithStep($workflow, $myStep, 'Mine '.$number, '2026-08-20');
            $expectedIds->prepend($activity->id);
        }
        foreach (range(1, 3) as $number) {
            $this->activityWithStep($workflow, $otherStep, 'Other '.$number, '2026-08-21');
        }

        $response = $this->actingAs($approver)->get(route('role.programs.approvals.index', ['my_pending' => 1]));
        $response->assertOk()->assertDontSee('Other 1');

        $paginator = $response->viewData('activities');
        $this->assertSame(18, $paginator->total());
        $this->assertSame(2, $paginator->lastPage());
        $this->assertSame($expectedIds->take(15)->all(), $paginator->pluck('id')->all());
        $response->assertSee('my_pending=1&amp;page=2', false);

        $secondPage = $this->actingAs($approver)->get(route('role.programs.approvals.index', ['my_pending' => 1, 'page' => 2]));
        $this->assertSame($expectedIds->slice(15)->values()->all(), $secondPage->viewData('activities')->pluck('id')->all());
        $this->assertEmpty(array_intersect($paginator->pluck('id')->all(), $secondPage->viewData('activities')->pluck('id')->all()));
    }

    public function test_out_of_range_pending_page_redirects_to_last_matching_page(): void
    {
        [$approver, , $workflow, $myStep] = $this->approvalSetup();
        $this->activityWithStep($workflow, $myStep, 'Only pending result', '2026-08-20');

        $this->actingAs($approver)
            ->get(route('role.programs.approvals.index', ['my_pending' => 1, 'page' => 99]))
            ->assertRedirect(route('role.programs.approvals.index', ['my_pending' => 1, 'page' => 1]));
    }

    public function test_rejection_persists_and_returns_to_the_filtered_approvals_page(): void
    {
        [$approver, , $workflow, $myStep] = $this->approvalSetup();
        $activity = $this->activityWithStep($workflow, $myStep, 'Activity to reject', '2026-08-20');
        $returnUrl = route('role.programs.approvals.index', ['branch_id' => $activity->branch_id, 'my_pending' => 1]);

        $this->actingAs($approver)
            ->put(route('role.programs.approvals.update', $activity), [
                'decision' => 'rejected',
                'comment' => 'The activity cannot be approved in its current form.',
                'focus_areas' => ['basic_info'],
                'return_url' => $returnUrl,
            ])
            ->assertRedirect($returnUrl);

        $this->assertSame('rejected', $activity->fresh()->status);
        $this->assertSame('rejected', $activity->workflowInstance->fresh()->status);
        $this->assertDatabaseHas('monthly_activity_approvals', [
            'monthly_activity_id' => $activity->id,
            'step' => $myStep->step_key,
            'decision' => 'rejected',
            'approved_by' => $approver->id,
        ]);

        $this->actingAs($approver)
            ->get($returnUrl)
            ->assertOk()
            ->assertDontSee('Activity to reject');
    }

    public function test_rejection_requires_reason_and_focus_area_before_writing_a_decision(): void
    {
        [$approver, , $workflow, $myStep] = $this->approvalSetup();
        $activity = $this->activityWithStep($workflow, $myStep, 'Invalid rejection', '2026-08-20');

        $this->actingAs($approver)
            ->from(route('role.programs.approvals.index', ['my_pending' => 1]))
            ->put(route('role.programs.approvals.update', $activity), ['decision' => 'rejected'])
            ->assertSessionHasErrors(['comment', 'focus_areas']);

        $this->assertSame('in_progress', $activity->workflowInstance->fresh()->status);
        $this->assertDatabaseMissing('monthly_activity_approvals', [
            'monthly_activity_id' => $activity->id,
            'decision' => 'rejected',
        ]);
    }

    public function test_relationship_manager_notification_is_active_authorized_deduplicated_and_record_specific(): void
    {
        [$manager, , $workflow, $managerStep] = $this->approvalSetup();
        $inactiveManager = User::factory()->create(['status' => 'inactive']);
        $inactiveManager->assignRole($manager->roles->first());
        $unrelatedUser = User::factory()->create(['status' => 'active']);
        $activity = $this->activityWithStep($workflow, $managerStep, 'Relationship review item', '2026-08-20');
        $instance = $activity->workflowInstance;

        $service = app(WorkflowNotificationService::class);
        $service->approvalRequested($instance, $activity, route('role.programs.approvals.index'));
        $service->approvalRequested($instance->fresh(), $activity->fresh(), route('role.programs.approvals.index'));

        $notification = InAppNotification::where('user_id', $manager->id)->where('type', 'approval_requested')->sole();
        $this->assertSame(route('role.programs.approvals.details', $activity), $notification->action_url);
        $this->assertStringContainsString('Relationship review item', $notification->message);
        $this->assertSame($activity->id, json_decode($notification->meta, true)['entity_id']);
        $this->assertFalse(InAppNotification::whereIn('user_id', [$inactiveManager->id, $unrelatedUser->id])->exists());
    }

    private function approvalSetup(): array
    {
        $managerRole = Role::findOrCreate('relations_manager', 'web');
        $otherRole = Role::findOrCreate('executive_manager', 'web');
        $workflow = Workflow::create(['code' => 'pagination_'.uniqid(), 'module' => 'monthly_activities', 'is_active' => true]);
        $managerStep = WorkflowStep::create(['workflow_id' => $workflow->id, 'step_order' => 1, 'approval_level' => 1, 'step_key' => 'monthly_relations_manager_review', 'step_type' => 'main', 'role_id' => $managerRole->id]);
        $otherStep = WorkflowStep::create(['workflow_id' => $workflow->id, 'step_order' => 2, 'approval_level' => 2, 'step_key' => 'monthly_executive_manager_final_approval', 'step_type' => 'main', 'role_id' => $otherRole->id]);
        $manager = User::factory()->create(['status' => 'active']);
        $manager->assignRole($managerRole);
        $other = User::factory()->create(['status' => 'active']);
        $other->assignRole($otherRole);

        return [$manager, $other, $workflow, $managerStep, $otherStep];
    }

    private function activityWithStep(Workflow $workflow, WorkflowStep $step, string $title, string $date): MonthlyActivity
    {
        $activity = MonthlyActivity::factory()->create(['title' => $title, 'proposed_date' => $date, 'status' => 'submitted', 'is_from_agenda' => false, 'agenda_event_id' => null]);
        WorkflowInstance::create(['workflow_id' => $workflow->id, 'entity_type' => MonthlyActivity::class, 'entity_id' => $activity->id, 'current_step_id' => $step->id, 'status' => 'in_progress', 'started_at' => now()]);

        return $activity->fresh('workflowInstance');
    }
}
