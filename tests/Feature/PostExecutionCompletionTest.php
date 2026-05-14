<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityTeam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostExecutionCompletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_post_execution_needs_section_shows_only_enabled_needs_and_post_fields(): void
    {
        [$creator, $activity] = $this->makeCreatorAndActivity([
            'needs_volunteers' => true,
            'needs_media_coverage' => false,
        ]);

        MonthlyActivityTeam::query()->create([
            'monthly_activity_id' => $activity->id,
            'team_name' => 'فريق التنظيم',
            'member_name' => 'عضو تجريبي',
            'role_desc' => 'تنظيم',
        ]);

        $this->actingAs($creator)
            ->get(route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post']))
            ->assertOk()
            ->assertSee('execution_needs_followup[volunteers][post_status]', false)
            ->assertSee('execution_needs_followup[volunteers][post_feedback]', false)
            ->assertDontSee('execution_needs_followup[media_coverage][post_status]', false)
            ->assertDontSee('حالة التأمين')
            ->assertDontSee('سبب عدم التأمين')
            ->assertDontSee('تقييم فعالية التأمين /10')
            ->assertDontSee('تقييم الحالة /100')
            ->assertDontSee('مبرر التقييم')
            ->assertDontSee('الدور الذي اتخذ القرار')
            ->assertDontSee('اسم متخذ القرار')
            ->assertSee('ماذا أنجزوا من مهام؟')
            ->assertDontSee('شو أنجزوا من مهام؟');
    }

    public function test_creator_can_save_post_execution_need_feedback_without_decision_role_restriction(): void
    {
        [$creator, $activity] = $this->makeCreatorAndActivity([
            'needs_volunteers' => true,
            'execution_needs_followup' => [
                [
                    'key' => 'volunteers',
                    'status' => 'secured',
                    'reason' => 'Pre-execution owner confirmed availability.',
                    'notes' => 'Pre-execution owner confirmed availability.',
                    'decision_by_role' => 'volunteer_coordinator',
                    'decision_by_name' => 'Coordinator',
                ],
            ],
        ]);

        $this->actingAs($creator)
            ->put(route('role.relations.activities.update', $activity), [
                'post_execution_needs_only' => 1,
                'execution_needs_followup' => [
                    'volunteers' => [
                        'post_status' => 'provided',
                        'post_feedback' => 'تم توفير المتطوعين وحضروا حسب الخطة.',
                    ],
                    'media_coverage' => [
                        'post_status' => 'provided',
                        'post_feedback' => 'هذا الاحتياج غير مفعل ويجب تجاهله.',
                    ],
                ],
            ])
            ->assertRedirect(route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post']));

        $activity->refresh();
        $followupRows = collect($activity->execution_needs_followup)->keyBy('key');

        $this->assertSame('secured', $followupRows->get('volunteers')['status']);
        $this->assertSame('volunteer_coordinator', $followupRows->get('volunteers')['decision_by_role']);
        $this->assertSame('Coordinator', $followupRows->get('volunteers')['decision_by_name']);
        $this->assertSame('provided', $followupRows->get('volunteers')['post_status']);
        $this->assertSame('تم توفير المتطوعين وحضروا حسب الخطة.', $followupRows->get('volunteers')['post_feedback']);
        $this->assertFalse($followupRows->has('media_coverage'));
    }

    public function test_close_saves_team_attendance_ceremony_feedback_and_need_feedback(): void
    {
        [$creator, $activity] = $this->makeCreatorAndActivity([
            'needs_volunteers' => true,
            'execution_needs_payload' => [
                'needs_ceremony_agenda' => true,
                'ceremony_items' => [
                    ['name' => 'السلام الملكي'],
                    ['name' => 'كلمة المركز'],
                ],
            ],
        ]);

        Role::findOrCreate('evaluation_officer', 'web');

        MonthlyActivityTeam::query()->create([
            'monthly_activity_id' => $activity->id,
            'team_name' => 'فريق التنظيم',
            'member_name' => 'عضو أول',
            'role_desc' => 'تنظيم',
        ]);

        $this->actingAs($creator)
            ->patch(route('role.relations.activities.close', $activity), [
                'actual_date' => '2026-05-10',
                'actual_attendance' => 42,
                'execution_needs_followup' => [
                    'volunteers' => [
                        'post_status' => 'provided',
                        'post_feedback' => 'تم تأمين المتطوعين بالكامل.',
                    ],
                ],
                'post_execution' => [
                    'teams' => [
                        [
                            'team_name' => 'فريق التنظيم',
                            'planned_members_count' => 1,
                            'all_members_attended' => '1',
                            'actual_attendance_count' => 1,
                            'accomplished_tasks' => 'تنظيم الدخول وتجهيز القاعة.',
                        ],
                    ],
                    'ceremony_items' => [
                        [
                            'order' => 1,
                            'name' => 'السلام الملكي',
                            'was_implemented' => '1',
                            'feedback' => 'تم تطبيق الفقرة في وقتها.',
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('role.relations.activities.index'));

        $activity->refresh();
        $followupRows = collect($activity->execution_needs_followup)->keyBy('key');

        $this->assertSame('closed', $activity->status);
        $this->assertSame('executed', $activity->execution_status);
        $this->assertSame('Closed', $activity->lifecycle_status);
        $this->assertSame(42, $activity->actual_attendance);
        $this->assertSame('provided', $followupRows->get('volunteers')['post_status']);
        $this->assertSame('تم تأمين المتطوعين بالكامل.', $followupRows->get('volunteers')['post_feedback']);
        $this->assertSame('فريق التنظيم', $activity->post_execution_payload['teams'][0]['team_name']);
        $this->assertTrue($activity->post_execution_payload['teams'][0]['all_members_attended']);
        $this->assertSame(1, $activity->post_execution_payload['teams'][0]['actual_attendance_count']);
        $this->assertSame('تنظيم الدخول وتجهيز القاعة.', $activity->post_execution_payload['teams'][0]['accomplished_tasks']);
        $this->assertSame('السلام الملكي', $activity->post_execution_payload['ceremony_items'][0]['name']);
        $this->assertTrue($activity->post_execution_payload['ceremony_items'][0]['was_implemented']);
        $this->assertSame('تم تطبيق الفقرة في وقتها.', $activity->post_execution_payload['ceremony_items'][0]['feedback']);

        $this->actingAs($creator)
            ->get(route('role.relations.activities.show', $activity))
            ->assertOk()
            ->assertSee('تم تأمين المتطوعين بالكامل.')
            ->assertSee('تنظيم الدخول وتجهيز القاعة.')
            ->assertSee('تم تطبيق الفقرة في وقتها.');
    }

    private function makeCreatorAndActivity(array $activityOverrides = []): array
    {
        $branch = Branch::factory()->create([
            'name' => 'Zarqa Branch',
            'city' => 'Zarqa',
            'is_main' => false,
        ]);

        $role = Role::findOrCreate('relations_officer', 'web');

        $creator = User::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
        $creator->assignRole($role);

        $activity = MonthlyActivity::factory()->create(array_merge([
            'title' => 'Post execution QA activity',
            'branch_id' => $branch->id,
            'created_by' => $creator->id,
            'status' => 'submitted',
            'lifecycle_status' => 'Scheduled',
            'proposed_date' => '2026-05-08',
            'month' => 5,
            'day' => 8,
            'needs_volunteers' => false,
            'needs_media_coverage' => false,
            'needs_official_correspondence' => false,
            'has_sponsor' => false,
            'has_partners' => false,
            'execution_needs_payload' => [],
            'execution_needs_followup' => null,
        ], $activityOverrides));

        return [$creator, $activity];
    }
}
