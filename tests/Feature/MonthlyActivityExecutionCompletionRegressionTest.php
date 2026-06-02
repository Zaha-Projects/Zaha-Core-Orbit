<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyActivityExecutionCompletionRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_planning_form_saves_attendance_range_multiple_sponsors_and_center_availability(): void
    {
        $user = $this->relationsOfficer();
        $branch = Branch::factory()->create();
        $user->forceFill(['branch_id' => $branch->id])->save();

        $this->actingAs($user)
            ->post(route('role.relations.activities.store'), $this->planningPayload($branch))
            ->assertRedirect();

        $activity = MonthlyActivity::query()->latest('id')->firstOrFail();

        $this->assertSame(40, $activity->expected_attendance_from);
        $this->assertSame(65, $activity->expected_attendance_to);
        $this->assertSame(65, $activity->expected_attendance);
        $this->assertSame('available', data_get($activity->execution_needs_payload, 'availability.volunteers'));
        $this->assertSame('not_available', data_get($activity->execution_needs_payload, 'availability.official_sponsorship'));
        $this->assertSame('not_available', data_get($activity->execution_needs_payload, 'availability.official_correspondence'));
        $this->assertSame('not_available', data_get($activity->execution_needs_payload, 'availability.supplies'));
        $this->assertSame('not_available', data_get($activity->execution_needs_payload, 'availability.certificates'));

        $this->assertSame(['Sponsor One', 'Sponsor Two'], $activity->sponsors()->orderBy('id')->pluck('name')->all());

        $supply = $activity->supplies()->firstOrFail();
        $this->assertFalse($supply->available);
        $this->assertSame('purchase', $supply->provider_type);
        $this->assertSame(3, $supply->quantity);
    }

    public function test_creator_submits_post_execution_payload_for_branch_head_approval_then_supervisor_closes_activity(): void
    {
        $user = $this->relationsOfficer();
        $branch = Branch::factory()->create();
        $user->forceFill(['branch_id' => $branch->id])->save();

        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $user->id,
            'status' => 'executed',
            'execution_status' => 'executed',
            'needs_volunteers' => true,
            'expected_attendance_from' => 40,
            'expected_attendance_to' => 65,
            'expected_attendance' => 65,
            'execution_needs_payload' => [
                'needs_ceremony_agenda' => true,
                'ceremony' => [
                    'items' => [
                        ['order' => 1, 'name' => 'Opening'],
                    ],
                ],
            ],
        ]);

        MonthlyActivityTeam::query()->create([
            'monthly_activity_id' => $activity->id,
            'team_name' => 'Operations',
            'member_name' => 'Member One',
            'role_desc' => 'Setup',
        ]);

        $postExecutionPayload = [
                'actual_date' => '2026-04-16',
                'actual_attendance' => 58,
                'execution_needs_followup' => [
                    'volunteers' => [
                        'post_status' => 'provided',
                        'post_feedback' => 'All volunteer tasks were covered.',
                    ],
                ],
                'post_execution' => [
                    'teams' => [
                        [
                            'team_name' => 'Operations',
                            'planned_members_count' => 1,
                            'all_members_attended' => '1',
                            'actual_attendance_count' => 1,
                            'accomplished_tasks' => 'Prepared the hall.',
                        ],
                    ],
                    'ceremony_items' => [
                        [
                            'order' => 1,
                            'name' => 'Opening',
                            'was_implemented' => '1',
                            'feedback' => 'Started on time.',
                        ],
                    ],
                ],
            ];

        $this->actingAs($user)
            ->patch(route('role.relations.activities.close', $activity), $postExecutionPayload)
            ->assertRedirect(route('role.relations.activities.index'));

        $activity->refresh();

        $this->assertSame('post_execution_submitted', $activity->status);
        $this->assertSame(58, $activity->actual_attendance);
        $this->assertSame('provided', data_get($activity->execution_needs_followup, '0.post_status'));
        $this->assertSame('Operations', data_get($activity->post_execution_payload, 'teams.0.team_name'));
        $this->assertSame(1, data_get($activity->post_execution_payload, 'teams.0.actual_attendance_count'));
        $this->assertTrue(data_get($activity->post_execution_payload, 'ceremony_items.0.was_implemented'));

        $supervisor = User::factory()->create(['branch_id' => $branch->id]);
        $supervisor->assignRole('supervisor');

        $evaluationOfficer = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $evaluationOfficer->assignRole('evaluation_officer');

        $this->actingAs($supervisor)
            ->patch(route('role.relations.activities.close', $activity), $postExecutionPayload)
            ->assertRedirect(route('role.relations.activities.index'));

        $activity->refresh();

        $this->assertSame('closed', $activity->status);
        $this->assertSame($evaluationOfficer->id, $activity->evaluation_assigned_user_id);
    }

    private function relationsOfficer(): User
    {
        foreach ([
            'relations_officer',
            'volunteer_coordinator',
            'branch_coordinator',
            'supervisor',
            'evaluation_officer',
            'followup_officer',
        ] as $role) {
            Role::findOrCreate($role, 'web');
        }

        $user = User::factory()->create();
        $user->assignRole('relations_officer');

        return $user;
    }

    private function planningPayload(Branch $branch): array
    {
        return [
            'title' => 'Production regression activity',
            'activity_date' => '2026-04-15',
            'proposed_date' => '2026-04-15',
            'branch_id' => $branch->id,
            'status' => 'draft',
            'execution_status' => 'executed',
            'location_type' => 'inside_center',
            'internal_location' => 'Training Hall',
            'description' => 'A complete activity description for production regression coverage.',
            'expected_attendance_from' => 40,
            'expected_attendance_to' => 65,
            'needs_volunteers' => 1,
            'required_volunteers' => 5,
            'volunteer_age_from' => 18,
            'volunteer_age_to' => 30,
            'volunteer_gender' => 'both',
            'volunteer_tasks_summary' => 'Registration and hall coordination.',
            'has_sponsor' => 1,
            'sponsors' => [
                ['name' => 'Sponsor One', 'title' => 'Gold Sponsor'],
                ['name' => 'Sponsor Two', 'title' => 'Silver Sponsor'],
            ],
            'requires_supplies' => 1,
            'supplies' => [
                [
                    'item_name' => 'Projector',
                    'available' => 0,
                    'quantity' => 3,
                    'provider_type' => 'purchase',
                    'provider_name' => 'Procurement',
                ],
            ],
            'need_availability' => [
                'volunteers' => 'available',
                'official_sponsorship' => 'not_available',
                'official_correspondence' => 'available',
                'certificates' => 'available',
            ],
        ];
    }
}
