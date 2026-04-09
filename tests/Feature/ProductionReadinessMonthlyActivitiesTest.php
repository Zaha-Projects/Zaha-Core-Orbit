<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Center;
use App\Models\Department;
use App\Models\MonthlyActivity;
use App\Models\User;
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
