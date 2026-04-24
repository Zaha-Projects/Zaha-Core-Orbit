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
}
