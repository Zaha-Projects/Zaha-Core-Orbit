<?php

namespace Tests\Feature;

use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Services\WorkflowNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgendaBranchInteractionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    protected function createBranchUser(Branch $branch): User
    {
        $role = Role::findOrCreate('relations_officer');
        $participationPermission = Permission::findOrCreate('agenda.participation.update');
        $branchVisibilityPermission = Permission::findOrCreate('branches.view.own');
        $role->givePermissionTo([$participationPermission, $branchVisibilityPermission]);

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole($role);

        return $user;
    }

    protected function createAgendaManager(): User
    {
        $role = Role::findOrCreate('relations_manager', 'web');
        $role->givePermissionTo([
            Permission::findOrCreate('agenda.create', 'web'),
            Permission::findOrCreate('agenda.update', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function createBranchCoordinator(Branch $branch): User
    {
        $role = Role::findOrCreate('branch_coordinator', 'web');
        $role->givePermissionTo([
            Permission::findOrCreate('agenda.view', 'web'),
            Permission::findOrCreate('branches.view.own', 'web'),
        ]);

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole($role);

        return $user;
    }

    public function test_branch_user_cannot_set_proposed_date_outside_seven_days(): void
    {
        $branch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $user = $this->createBranchUser($branch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-15',
            'month' => 4,
            'day' => 15,
            'event_name' => 'Optional Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('role.relations.agenda.branch_participation.update', $event), [
                'will_participate' => 'yes',
                'proposed_date' => '2026-05-01',
            ])
            ->assertStatus(422);
    }

    public function test_participation_creates_or_updates_monthly_activity_link(): void
    {
        $branch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $user = $this->createBranchUser($branch);
        Storage::fake('public');

        $event = AgendaEvent::create([
            'event_date' => '2026-04-15',
            'month' => 4,
            'day' => 15,
            'event_name' => 'Optional Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('role.relations.agenda.branch_participation.update', $event), [
                'will_participate' => 'yes',
                'proposed_date' => '2026-04-18',
                'branch_plan_file' => UploadedFile::fake()->create('branch-plan.pdf', 64, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('agenda_participations', [
            'agenda_event_id' => $event->id,
            'entity_type' => 'branch',
            'entity_id' => $branch->id,
            'participation_status' => 'participant',
        ]);

        $this->assertDatabaseHas('monthly_activities', [
            'agenda_event_id' => $event->id,
            'branch_id' => $branch->id,
            'proposed_date' => '2026-04-18',
        ]);
    }

    public function test_quick_subscribe_redirects_branch_user_to_monthly_plan_editor(): void
    {
        $branch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $user = $this->createBranchUser($branch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-22',
            'month' => 4,
            'day' => 22,
            'event_name' => 'Optional Branch Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('role.relations.agenda.quick_subscribe', $event));

        $monthlyActivity = MonthlyActivity::query()
            ->where('agenda_event_id', $event->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $response->assertRedirect(route('role.relations.activities.edit', [
            'monthlyActivity' => $monthlyActivity,
            'form' => 1,
        ]));

        $this->assertDatabaseHas('agenda_participations', [
            'agenda_event_id' => $event->id,
            'entity_type' => 'branch',
            'entity_id' => $branch->id,
            'participation_status' => 'participant',
            'proposed_date' => '2026-04-22',
        ]);
    }

    public function test_quick_subscribed_monthly_plan_hides_system_managed_agenda_fields(): void
    {
        $branch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $user = $this->createBranchUser($branch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-22',
            'month' => 4,
            'day' => 22,
            'event_name' => 'Optional Branch Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('role.relations.agenda.quick_subscribe', $event));

        $monthlyActivity = MonthlyActivity::query()
            ->where('agenda_event_id', $event->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        $this->actingAs($user)
            ->get(route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'form' => 1]))
            ->assertOk()
            ->assertDontSee('فعالية الأجندة السنوية المرتبطة')
            ->assertDontSee('الجهة المالكة')
            ->assertDontSee('أجندة الحفل')
            ->assertSee('name="agenda_event_id"', false)
            ->assertSee('value="'.$event->id.'"', false);
    }

    public function test_branch_user_can_see_optional_agenda_event_without_branch_participations(): void
    {
        $branch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $creator = User::factory()->create();
        $user = $this->createBranchUser($branch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-22',
            'month' => 4,
            'day' => 22,
            'event_name' => 'Open Optional Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $creator->id,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.agenda.index'))
            ->assertOk()
            ->assertSee('Open Optional Event');

        $this->actingAs($user)
            ->get(route('role.relations.agenda.show', $event))
            ->assertOk()
            ->assertSee('Open Optional Event');
    }

    public function test_creating_active_mandatory_agenda_event_immediately_creates_monthly_plans_for_selected_branches(): void
    {
        Carbon::setTestNow('2026-04-01 10:00:00');

        $manager = $this->createAgendaManager();
        $selectedBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $otherBranch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $branchUser = User::factory()->create(['branch_id' => $selectedBranch->id]);
        $owner = Department::query()->create(['name' => 'Relations']);

        $this->actingAs($manager)
            ->post(route('role.relations.agenda.store'), [
                'event_name' => 'Mandatory Selected Branch Event',
                'event_date' => '2026-04-20',
                'owner_department_id' => $owner->id,
                'event_type' => 'mandatory',
                'plan_type' => 'non_unified',
                'is_active' => 1,
                'notes' => 'Auto monthly plan',
                'branch_participation' => [
                    $selectedBranch->id => 'participant',
                    $otherBranch->id => 'not_participant',
                ],
            ])
            ->assertRedirect(route('role.relations.agenda.index'));

        $event = AgendaEvent::query()
            ->where('event_name', 'Mandatory Selected Branch Event')
            ->firstOrFail();

        $this->assertDatabaseHas('monthly_activities', [
            'agenda_event_id' => $event->id,
            'branch_id' => $selectedBranch->id,
            'title' => 'Mandatory Selected Branch Event',
            'proposed_date' => '2026-04-20',
            'activity_date' => '2026-04-20',
            'is_in_agenda' => true,
            'is_from_agenda' => true,
            'participation_status' => 'participant',
            'status' => 'approved',
            'created_by' => $branchUser->id,
        ]);

        $this->assertDatabaseMissing('monthly_activities', [
            'agenda_event_id' => $event->id,
            'branch_id' => $otherBranch->id,
        ]);
    }

    public function test_creating_optional_agenda_event_does_not_create_monthly_plan_until_branch_participates(): void
    {
        Carbon::setTestNow('2026-04-01 10:00:00');

        $manager = $this->createAgendaManager();
        $selectedBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $owner = Department::query()->create(['name' => 'Relations']);

        $this->actingAs($manager)
            ->post(route('role.relations.agenda.store'), [
                'event_name' => 'Optional Selected Branch Event',
                'event_date' => '2026-04-21',
                'owner_department_id' => $owner->id,
                'event_type' => 'optional',
                'plan_type' => 'non_unified',
                'is_active' => 1,
                'branch_participation' => [
                    $selectedBranch->id => 'participant',
                ],
            ])
            ->assertRedirect(route('role.relations.agenda.index'));

        $event = AgendaEvent::query()
            ->where('event_name', 'Optional Selected Branch Event')
            ->firstOrFail();

        $this->assertDatabaseMissing('monthly_activities', [
            'agenda_event_id' => $event->id,
            'branch_id' => $selectedBranch->id,
        ]);
    }

    public function test_branch_user_cannot_see_optional_agenda_event_before_publication(): void
    {
        $branch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $creator = User::factory()->create();
        $user = $this->createBranchUser($branch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-22',
            'month' => 4,
            'day' => 22,
            'event_name' => 'Submitted Optional Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'submitted',
            'relations_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
            'created_by' => $creator->id,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.agenda.index'))
            ->assertOk()
            ->assertDontSee('Submitted Optional Event');

        $this->actingAs($user)
            ->get(route('role.relations.agenda.show', $event))
            ->assertForbidden();
    }

    public function test_branch_user_cannot_see_mandatory_agenda_event_before_publication(): void
    {
        $branch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $creator = User::factory()->create();
        $user = $this->createBranchUser($branch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-23',
            'month' => 4,
            'day' => 23,
            'event_name' => 'Submitted Mandatory Event',
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'status' => 'submitted',
            'relations_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
            'created_by' => $creator->id,
        ]);

        AgendaParticipation::create([
            'agenda_event_id' => $event->id,
            'entity_type' => 'branch',
            'entity_id' => $branch->id,
            'participation_status' => 'participant',
            'updated_by' => $creator->id,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.agenda.index'))
            ->assertOk()
            ->assertDontSee('Submitted Mandatory Event');

        $this->actingAs($user)
            ->get(route('role.relations.agenda.show', $event))
            ->assertForbidden();
    }

    public function test_published_agenda_notification_opens_show_page_for_non_workflow_user(): void
    {
        $actor = User::factory()->create(['status' => 'active']);
        $recipient = User::factory()->create(['status' => 'active']);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-22',
            'month' => 4,
            'day' => 22,
            'event_name' => 'Published Notification Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $actor->id,
        ]);

        app(WorkflowNotificationService::class)->published(
            $event->fresh('creator'),
            $actor,
            route('role.relations.approvals.index')
        );

        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $recipient->id,
            'type' => 'workflow_published',
            'action_url' => route('role.relations.agenda.show', $event),
        ]);

        $this->actingAs($recipient)
            ->get(route('role.relations.agenda.show', $event))
            ->assertOk()
            ->assertSee('Published Notification Event');
    }

    public function test_branch_user_can_see_published_mandatory_event_even_when_branch_is_not_selected_as_participant(): void
    {
        $selectedBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $otherBranch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $creator = User::factory()->create();
        $selectedUser = $this->createBranchUser($selectedBranch);
        $otherUser = $this->createBranchUser($otherBranch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-25',
            'month' => 4,
            'day' => 25,
            'event_name' => 'Selected Mandatory Event',
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $creator->id,
        ]);

        AgendaParticipation::create([
            'agenda_event_id' => $event->id,
            'entity_type' => 'branch',
            'entity_id' => $selectedBranch->id,
            'participation_status' => 'participant',
            'updated_by' => $creator->id,
        ]);

        AgendaParticipation::create([
            'agenda_event_id' => $event->id,
            'entity_type' => 'branch',
            'entity_id' => $otherBranch->id,
            'participation_status' => 'not_participant',
            'updated_by' => $creator->id,
        ]);

        $this->actingAs($selectedUser)
            ->get(route('role.relations.agenda.index'))
            ->assertOk()
            ->assertSee('Selected Mandatory Event');

        $this->actingAs($otherUser)
            ->get(route('role.relations.agenda.index'))
            ->assertOk()
            ->assertSee('Selected Mandatory Event');

        $this->actingAs($otherUser)
            ->get(route('role.relations.agenda.show', $event))
            ->assertOk()
            ->assertSee('Selected Mandatory Event');
    }

    public function test_branch_coordinator_can_see_published_agenda_event_for_any_branch(): void
    {
        $coordinatorBranch = Branch::factory()->create(['name' => 'Aqaba Branch', 'city' => 'Aqaba']);
        $selectedBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $creator = User::factory()->create();
        $coordinator = $this->createBranchCoordinator($coordinatorBranch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-25',
            'month' => 4,
            'day' => 25,
            'event_name' => 'Published Agenda For Everyone',
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $creator->id,
        ]);

        AgendaParticipation::create([
            'agenda_event_id' => $event->id,
            'entity_type' => 'branch',
            'entity_id' => $selectedBranch->id,
            'participation_status' => 'participant',
            'updated_by' => $creator->id,
        ]);

        $this->actingAs($coordinator)
            ->get(route('role.relations.agenda.index'))
            ->assertOk()
            ->assertSee('Published Agenda For Everyone');

        $this->actingAs($coordinator)
            ->get(route('role.relations.agenda.show', $event))
            ->assertOk()
            ->assertSee('Published Agenda For Everyone');
    }

    public function test_inactive_optional_event_is_visible_but_cannot_be_quick_subscribed(): void
    {
        $branch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $user = $this->createBranchUser($branch);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-22',
            'month' => 4,
            'day' => 22,
            'event_name' => 'Inactive Optional Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'is_active' => false,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.agenda.index'))
            ->assertOk()
            ->assertSee('Inactive Optional Event')
            ->assertSee('غير نشطة');

        $this->actingAs($user)
            ->post(route('role.relations.agenda.quick_subscribe', $event))
            ->assertStatus(422);

        $this->assertDatabaseMissing('monthly_activities', [
            'agenda_event_id' => $event->id,
            'branch_id' => $branch->id,
        ]);
    }

    public function test_monthly_sync_creates_only_active_mandatory_participant_branch_events(): void
    {
        $selectedBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $otherBranch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $user = $this->createBranchUser($selectedBranch);

        $activeEvent = AgendaEvent::create([
            'event_date' => '2026-04-25',
            'month' => 4,
            'day' => 25,
            'event_name' => 'Active Mandatory Participant Event',
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $inactiveEvent = AgendaEvent::create([
            'event_date' => '2026-04-26',
            'month' => 4,
            'day' => 26,
            'event_name' => 'Inactive Mandatory Participant Event',
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'is_active' => false,
            'created_by' => $user->id,
        ]);

        foreach ([$activeEvent, $inactiveEvent] as $event) {
            AgendaParticipation::create([
                'agenda_event_id' => $event->id,
                'entity_type' => 'branch',
                'entity_id' => $selectedBranch->id,
                'participation_status' => 'participant',
                'updated_by' => $user->id,
            ]);
        }

        AgendaParticipation::create([
            'agenda_event_id' => $activeEvent->id,
            'entity_type' => 'branch',
            'entity_id' => $otherBranch->id,
            'participation_status' => 'participant',
            'updated_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('role.relations.activities.sync_from_agenda'), [
                'branch_id' => $selectedBranch->id,
                'month' => 4,
                'year' => 2026,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('monthly_activities', [
            'agenda_event_id' => $activeEvent->id,
            'branch_id' => $selectedBranch->id,
        ]);
        $this->assertDatabaseMissing('monthly_activities', [
            'agenda_event_id' => $activeEvent->id,
            'branch_id' => $otherBranch->id,
        ]);
        $this->assertDatabaseMissing('monthly_activities', [
            'agenda_event_id' => $inactiveEvent->id,
            'branch_id' => $selectedBranch->id,
        ]);
    }

    public function test_agenda_event_delete_is_limited_to_future_events_and_notifies_branch_plan_owner(): void
    {
        Carbon::setTestNow('2026-04-28 10:00:00');

        $role = Role::findOrCreate('relations_manager', 'web');
        $role->givePermissionTo([
            Permission::findOrCreate('agenda.create', 'web'),
            Permission::findOrCreate('agenda.update', 'web'),
            Permission::findOrCreate('agenda.delete', 'web'),
        ]);

        $manager = User::factory()->create();
        $manager->assignRole($role);
        $branch = Branch::factory()->create();
        $branchUser = User::factory()->create(['branch_id' => $branch->id]);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-29',
            'month' => 4,
            'day' => 29,
            'event_name' => 'Future Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'created_by' => $manager->id,
        ]);

        $activity = MonthlyActivity::factory()->create([
            'agenda_event_id' => $event->id,
            'branch_id' => $branch->id,
            'created_by' => $branchUser->id,
            'title' => 'Branch Plan',
            'proposed_date' => '2026-04-29',
            'is_in_agenda' => true,
            'is_from_agenda' => true,
        ]);

        $this->actingAs($manager)
            ->delete(route('role.relations.agenda.destroy', $event))
            ->assertRedirect(route('role.relations.agenda.index'));

        $this->assertDatabaseMissing('agenda_events', ['id' => $event->id]);
        $this->assertDatabaseHas('monthly_activities', [
            'id' => $activity->id,
            'agenda_event_id' => null,
            'is_in_agenda' => false,
            'is_from_agenda' => false,
            'execution_status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('in_app_notifications', [
            'user_id' => $branchUser->id,
            'type' => 'agenda_event_cancelled',
        ]);
    }

    public function test_agenda_event_delete_rejects_today_and_past_events(): void
    {
        Carbon::setTestNow('2026-04-28 10:00:00');

        $role = Role::findOrCreate('relations_manager', 'web');
        $role->givePermissionTo([
            Permission::findOrCreate('agenda.create', 'web'),
            Permission::findOrCreate('agenda.update', 'web'),
            Permission::findOrCreate('agenda.delete', 'web'),
        ]);
        $manager = User::factory()->create();
        $manager->assignRole($role);

        $event = AgendaEvent::create([
            'event_date' => '2026-04-28',
            'month' => 4,
            'day' => 28,
            'event_name' => 'Today Event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'created_by' => $manager->id,
        ]);

        $this->actingAs($manager)
            ->delete(route('role.relations.agenda.destroy', $event))
            ->assertStatus(422);

        $this->assertDatabaseHas('agenda_events', ['id' => $event->id]);
    }

    public function test_agenda_event_cannot_be_created_with_yesterday_or_older_date(): void
    {
        Carbon::setTestNow('2026-04-28 10:00:00');

        $role = Role::findOrCreate('relations_manager', 'web');
        $role->givePermissionTo(Permission::findOrCreate('agenda.create', 'web'));
        $user = User::factory()->create();
        $user->assignRole($role);
        $owner = Department::query()->create(['name' => 'Owner Dept']);

        $this->actingAs($user)
            ->post(route('role.relations.agenda.store'), [
                'event_name' => 'Old Event',
                'event_date' => '2026-04-27',
                'owner_department_id' => $owner->id,
                'event_type' => 'mandatory',
                'plan_type' => 'unified',
                'is_active' => 1,
            ])
            ->assertSessionHasErrors('event_date');

        $this->assertDatabaseMissing('agenda_events', [
            'event_name' => 'Old Event',
        ]);
    }
}
