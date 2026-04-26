<?php

namespace Tests\Feature;

use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgendaBranchInteractionTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_branch_user_cannot_see_mandatory_event_unless_branch_is_selected_as_participant(): void
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
            ->assertDontSee('Selected Mandatory Event');

        $this->actingAs($otherUser)
            ->get(route('role.relations.agenda.show', $event))
            ->assertForbidden();
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
}
