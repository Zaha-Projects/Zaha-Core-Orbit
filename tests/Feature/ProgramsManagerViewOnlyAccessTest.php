<?php

namespace Tests\Feature;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProgramsManagerViewOnlyAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function seedAcl(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    protected function programsManager(): User
    {
        $role = Role::findOrCreate('programs_manager', 'web');
        $role->syncPermissions([
            Permission::findOrCreate('agenda.view', 'web'),
            Permission::findOrCreate('monthly_activities.view', 'web'),
            Permission::findOrCreate('monthly_activities.view_other_branches', 'web'),
            Permission::findOrCreate('branches.view.all', 'web'),
        ]);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    protected function userWithRole(string $roleName, ?Branch $branch = null): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        $user = User::factory()->create(['branch_id' => $branch?->id]);
        $user->assignRole($role);

        return $user;
    }

    protected function agendaEventFor(User $user): AgendaEvent
    {
        return AgendaEvent::query()->create([
            'event_date' => '2026-03-19',
            'month' => 3,
            'day' => 19,
            'event_name' => 'Smoke agenda event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $user->id,
        ]);
    }

    protected function monthlyActivityFor(User $user, Branch $branch, array $overrides = []): MonthlyActivity
    {
        return MonthlyActivity::factory()->create(array_merge([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'title' => 'Smoke monthly activity',
            'status' => 'approved',
            'executive_approval_status' => 'approved',
            'lifecycle_status' => 'Approved',
        ], $overrides));
    }

    public function test_programs_manager_can_view_agenda_and_monthly_activity_pages_only(): void
    {
        $user = $this->programsManager();
        $branch = Branch::factory()->create();
        $agendaEvent = AgendaEvent::query()->create([
            'event_date' => '2026-03-19',
            'month' => 3,
            'day' => 19,
            'event_name' => 'Read only agenda event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'relations_approval_status' => 'approved',
            'executive_approval_status' => 'approved',
            'created_by' => $user->id,
        ]);
        $monthlyActivity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'title' => 'Read only monthly activity',
            'status' => 'approved',
            'executive_approval_status' => 'approved',
            'lifecycle_status' => 'Approved',
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.agenda.index'))
            ->assertOk()
            ->assertSee('Read only agenda event');

        $this->actingAs($user)
            ->get(route('role.relations.agenda.show', $agendaEvent))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('role.relations.activities.index', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertSee('Read only monthly activity');

        $this->actingAs($user)
            ->get(route('role.relations.activities.show', $monthlyActivity))
            ->assertOk();
    }

    public function test_programs_manager_cannot_create_update_delete_or_approve_agenda_and_monthly_activities(): void
    {
        $user = $this->programsManager();
        $branch = Branch::factory()->create();
        $agendaEvent = AgendaEvent::query()->create([
            'event_date' => '2026-03-19',
            'month' => 3,
            'day' => 19,
            'event_name' => 'Protected agenda event',
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'status' => 'published',
            'created_by' => $user->id,
        ]);
        $monthlyActivity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'status' => 'approved',
            'executive_approval_status' => 'approved',
            'lifecycle_status' => 'Approved',
        ]);

        $this->actingAs($user)->get(route('role.relations.agenda.create'))->assertForbidden();
        $this->actingAs($user)->get(route('role.relations.agenda.edit', $agendaEvent))->assertForbidden();
        $this->actingAs($user)->put(route('role.relations.agenda.update', $agendaEvent), [])->assertForbidden();
        $this->actingAs($user)->delete(route('role.relations.agenda.destroy', $agendaEvent), [])->assertForbidden();
        $this->actingAs($user)->patch(route('role.relations.agenda.unit_participation.update', $agendaEvent), [])->assertForbidden();
        $this->actingAs($user)->get(route('role.relations.approvals.index'))->assertForbidden();

        $this->actingAs($user)->get(route('role.relations.activities.create'))->assertForbidden();
        $this->actingAs($user)->get(route('role.relations.activities.edit', $monthlyActivity))->assertForbidden();
        $this->actingAs($user)->put(route('role.relations.activities.update', $monthlyActivity), [])->assertForbidden();
        $this->actingAs($user)->delete(route('role.relations.activities.destroy', $monthlyActivity), [])->assertForbidden();
        $this->actingAs($user)->post(route('role.programs.supplies.store', $monthlyActivity), [])->assertForbidden();
        $this->actingAs($user)->post(route('role.programs.team.store', $monthlyActivity), [])->assertForbidden();
        $this->actingAs($user)->post(route('role.programs.attachments.store', $monthlyActivity), [])->assertForbidden();
        $this->actingAs($user)->get(route('role.programs.approvals.index'))->assertForbidden();
    }

    public function test_impacted_agenda_ui_pages_render_for_all_seeded_roles_with_agenda_view(): void
    {
        $this->seedAcl();
        $branch = Branch::factory()->create(['is_main' => true]);
        $owner = $this->userWithRole('super_admin', $branch);
        $agendaEvent = $this->agendaEventFor($owner);

        $roles = [
            'super_admin',
            'executive_manager',
            'programs_manager',
            'relations_manager',
            'supervisor',
            'relations_officer',
            'followup_officer',
            'evaluation_officer',
            'evaluation_followup_viewer',
            'workshops_secretary',
            'branch_coordinator',
            'volunteer_coordinator',
            'administrative_unit_manager',
            'communication_head',
            'finance_officer',
            'maintenance_officer',
            'transport_officer',
            'reports_viewer',
            'staff',
            'movement_manager',
            'movement_editor',
            'movement_viewer',
        ];

        foreach ($roles as $roleName) {
            $user = $this->userWithRole($roleName, $branch);

            $this->actingAs($user)
                ->get(route('role.relations.agenda.index'))
                ->assertOk();

            $this->actingAs($user)
                ->get(route('role.relations.agenda.show', $agendaEvent))
                ->assertOk();
        }
    }

    public function test_impacted_monthly_activity_ui_pages_keep_existing_role_access(): void
    {
        $this->seedAcl();
        $branch = Branch::factory()->create(['is_main' => true]);
        $owner = $this->userWithRole('super_admin', $branch);
        $monthlyActivity = $this->monthlyActivityFor($owner, $branch, [
            'needs_volunteers' => true,
        ]);

        $monthlyIndexRoles = [
            'super_admin',
            'executive_manager',
            'programs_manager',
            'relations_manager',
            'supervisor',
            'relations_officer',
            'followup_officer',
            'evaluation_officer',
            'evaluation_followup_viewer',
            'workshops_secretary',
            'branch_coordinator',
            'volunteer_coordinator',
            'administrative_unit_manager',
            'communication_head',
            'staff',
        ];

        foreach ($monthlyIndexRoles as $roleName) {
            $this->actingAs($this->userWithRole($roleName, $branch))
                ->get(route('role.relations.activities.index', ['year' => 2026, 'month' => 3]))
                ->assertOk();
        }

        $monthlyShowRoles = [
            'super_admin',
            'programs_manager',
            'relations_manager',
            'supervisor',
            'relations_officer',
            'followup_officer',
            'evaluation_officer',
            'evaluation_followup_viewer',
            'branch_coordinator',
            'volunteer_coordinator',
            'administrative_unit_manager',
            'communication_head',
            'transport_officer',
            'movement_manager',
        ];

        foreach ($monthlyShowRoles as $roleName) {
            $response = $this->actingAs($this->userWithRole($roleName, $branch))
                ->get(route('role.relations.activities.show', $monthlyActivity));

            $this->assertTrue($response->isOk(), "{$roleName} should keep access to monthly activity show.");
        }
    }

    public function test_non_programs_manager_need_decision_ui_stays_unchanged(): void
    {
        $this->seedAcl();
        $branch = Branch::factory()->create(['is_main' => true]);
        $owner = $this->userWithRole('super_admin', $branch);
        $monthlyActivity = $this->monthlyActivityFor($owner, $branch, [
            'execution_needs_payload' => ['needs_transport' => true],
        ]);

        $this->actingAs($this->userWithRole('transport_officer', $branch))
            ->get(route('role.relations.activities.show', $monthlyActivity))
            ->assertOk()
            ->assertSee('need_decision', false);

        $this->actingAs($this->userWithRole('programs_manager', $branch))
            ->get(route('role.relations.activities.show', $monthlyActivity))
            ->assertOk()
            ->assertDontSee('need_decision', false);
    }
}
