<?php

namespace Tests\Feature;

use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\Center;
use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AgendaBranchInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_branch_user_cannot_set_proposed_date_outside_seven_days(): void
    {
        $branch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $center = Center::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create(['branch_id' => $branch->id, 'center_id' => $center->id]);
        Role::findOrCreate('relations_officer');
        $user->assignRole('relations_officer');

        $event = AgendaEvent::create([
            'event_date' => '2026-04-15',
            'month' => 4,
            'day' => 15,
            'event_name' => 'Optional Event',
            'event_type' => 'optional',
            'plan_type' => 'unified',
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
        $center = Center::factory()->create(['branch_id' => $branch->id]);
        $user = User::factory()->create(['branch_id' => $branch->id, 'center_id' => $center->id]);
        Role::findOrCreate('relations_officer');
        $user->assignRole('relations_officer');

        $event = AgendaEvent::create([
            'event_date' => '2026-04-15',
            'month' => 4,
            'day' => 15,
            'event_name' => 'Optional Event',
            'event_type' => 'optional',
            'plan_type' => 'unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->patch(route('role.relations.agenda.branch_participation.update', $event), [
                'will_participate' => 'yes',
                'proposed_date' => '2026-04-18',
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
}
