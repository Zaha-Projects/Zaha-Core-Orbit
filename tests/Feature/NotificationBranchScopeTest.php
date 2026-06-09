<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Http\Controllers\Web\MonthlyActivities\MonthlyActivitiesController;
use App\Models\InAppNotification;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Services\WorkflowNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationBranchScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_activity_published_notification_targets_only_activity_branch_roles(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        foreach (['branch_coordinator', 'volunteer_coordinator', 'communication_head', 'supervisor', 'relations_officer'] as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        $actor = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $creator = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $sameBranchCoordinator = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $assignedCoordinator = User::factory()->create(['status' => 'active', 'branch_id' => $otherBranch->id]);
        $sameBranchVolunteer = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $sameBranchCommunicationHead = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $otherBranchCoordinator = User::factory()->create(['status' => 'active', 'branch_id' => $otherBranch->id]);
        $inactiveBranchCoordinator = User::factory()->create(['status' => 'inactive', 'branch_id' => $branch->id]);
        $unrelatedActiveUser = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);

        $sameBranchCoordinator->assignRole('branch_coordinator');
        $assignedCoordinator->assignRole('branch_coordinator');
        $assignedCoordinator->assignedBranches()->sync([$branch->id]);
        $sameBranchVolunteer->assignRole('volunteer_coordinator');
        $sameBranchCommunicationHead->assignRole('communication_head');
        $otherBranchCoordinator->assignRole('branch_coordinator');
        $inactiveBranchCoordinator->assignRole('branch_coordinator');

        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $creator->id,
            'title' => 'Scoped published activity',
        ]);

        app(WorkflowNotificationService::class)->published(
            $activity->fresh('creator'),
            $actor,
            route('role.relations.activities.show', $activity)
        );

        foreach ([$creator, $sameBranchCoordinator, $assignedCoordinator, $sameBranchVolunteer, $sameBranchCommunicationHead] as $recipient) {
            $this->assertDatabaseHas('in_app_notifications', [
                'user_id' => $recipient->id,
                'type' => 'workflow_published',
            ]);
        }

        foreach ([$otherBranchCoordinator, $inactiveBranchCoordinator, $unrelatedActiveUser] as $nonRecipient) {
            $this->assertDatabaseMissing('in_app_notifications', [
                'user_id' => $nonRecipient->id,
                'type' => 'workflow_published',
            ]);
        }
    }

    public function test_execution_need_notifications_scope_volunteer_and_department_heads_to_activity_branch(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();

        Role::findOrCreate('volunteer_coordinator', 'web');
        Role::findOrCreate('communication_head', 'web');

        $sameBranchVolunteer = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $assignedVolunteer = User::factory()->create(['status' => 'active', 'branch_id' => $otherBranch->id]);
        $otherBranchVolunteer = User::factory()->create(['status' => 'active', 'branch_id' => $otherBranch->id]);
        $sameBranchCommunicationHead = User::factory()->create(['status' => 'active', 'branch_id' => $branch->id]);
        $otherBranchCommunicationHead = User::factory()->create(['status' => 'active', 'branch_id' => $otherBranch->id]);

        $sameBranchVolunteer->assignRole('volunteer_coordinator');
        $assignedVolunteer->assignRole('volunteer_coordinator');
        $assignedVolunteer->assignedBranches()->sync([$branch->id]);
        $otherBranchVolunteer->assignRole('volunteer_coordinator');
        $sameBranchCommunicationHead->assignRole('communication_head');
        $otherBranchCommunicationHead->assignRole('communication_head');

        $activity = MonthlyActivity::factory()->create(['branch_id' => $branch->id]);
        $controller = new MonthlyActivitiesController();
        $method = new \ReflectionMethod($controller, 'executionNeedOwnerUsers');
        $method->setAccessible(true);

        $volunteerRecipients = $method->invoke($controller, 'volunteer_coordinator', $activity);
        $communicationRecipients = $method->invoke($controller, 'communication_head', $activity);

        $this->assertTrue($volunteerRecipients->contains('id', $sameBranchVolunteer->id));
        $this->assertTrue($volunteerRecipients->contains('id', $assignedVolunteer->id));
        $this->assertFalse($volunteerRecipients->contains('id', $otherBranchVolunteer->id));
        $this->assertTrue($communicationRecipients->contains('id', $sameBranchCommunicationHead->id));
        $this->assertFalse($communicationRecipients->contains('id', $otherBranchCommunicationHead->id));
    }

    public function test_notifications_menu_shows_notification_date_and_time(): void
    {
        $user = User::factory()->create();
        InAppNotification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Timed notification',
            'message' => 'Notification body',
            'created_at' => Carbon::parse('2026-06-09 15:45:00'),
            'updated_at' => Carbon::parse('2026-06-09 15:45:00'),
        ]);

        $this->actingAs($user);

        $html = view('layouts.app.partials.notifications-menu')->render();

        $this->assertStringContainsString('2026-06-09', $html);
        $this->assertStringContainsString('15:45', $html);
        $this->assertStringContainsString(__('app.layout.notification_timestamp'), $html);
    }

    public function test_opening_notification_marks_it_as_read_and_redirects_to_action_url(): void
    {
        $user = User::factory()->create();
        $notification = InAppNotification::create([
            'user_id' => $user->id,
            'type' => 'test',
            'title' => 'Open me',
            'action_url' => route('role.relations.activities.index'),
        ]);

        $this->actingAs($user)
            ->get(route('role.notifications.open', $notification))
            ->assertRedirect(route('role.relations.activities.index'));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
