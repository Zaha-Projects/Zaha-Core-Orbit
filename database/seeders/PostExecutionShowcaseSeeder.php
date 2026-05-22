<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivitySupply;
use App\Models\MonthlyActivityTeam;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class PostExecutionShowcaseSeeder extends Seeder
{
    public function run(): void
    {
        $zarqaBranch = $this->resolveBranch(['zarqa', 'الزرقاء', 'الرصيفة']);

        $creator = $this->ensureShowcaseUser($zarqaBranch);
        $date = Carbon::create(now()->year, 5, 6);

        $activity = MonthlyActivity::query()->updateOrCreate(
            ['title' => 'Showcase Post Execution - Zarqa All Needs Completed'],
            [
                'month' => (int) $date->format('m'),
                'day' => (int) $date->format('d'),
                'activity_date' => $date->toDateString(),
                'proposed_date' => $date->toDateString(),
                'actual_date' => $date->toDateString(),
                'is_in_agenda' => false,
                'is_from_agenda' => false,
                'responsible_party' => 'Showcase Zarqa Team',
                'description' => 'Completed showcase activity with every execution need enabled and filled after execution.',
                'location_type' => 'inside_center',
                'internal_location' => 'Zarqa main hall',
                'execution_time' => '10:00-13:00',
                'time_from' => '10:00',
                'time_to' => '13:00',
                'target_group' => 'families',
                'short_description' => 'Post execution showcase for Zarqa user.',
                'work_teams_count' => 2,
                'needs_volunteers' => true,
                'expected_attendance' => 80,
                'actual_attendance' => 74,
                'attendance_notes' => 'Attendance was strong with minor late arrivals.',
                'has_sponsor' => true,
                'sponsor_name_title' => 'Zarqa Community Sponsor',
                'has_partners' => true,
                'needs_official_correspondence' => true,
                'official_correspondence_reason' => 'Official coordination letter',
                'official_correspondence_target' => 'Zarqa Municipality',
                'official_correspondence_brief' => 'Coordinate venue access and public attendance.',
                'needs_media_coverage' => true,
                'media_coverage_notes' => 'Media team covered opening, activities, and closing remarks.',
                'requires_programs' => true,
                'requires_workshops' => true,
                'requires_communications' => true,
                'is_program_related' => true,
                'execution_needs_payload' => $this->executionNeedsPayload(),
                'execution_needs_followup' => $this->executionNeedsFollowup(),
                'post_execution_payload' => $this->postExecutionPayload(),
                'status' => 'closed',
                'execution_status' => 'executed',
                'plan_stage' => 1,
                'plan_version' => 1,
                'lifecycle_status' => 'Closed',
                'participation_status' => 'participant',
                'plan_type' => 'non_unified',
                'relations_officer_approval_status' => 'approved',
                'relations_manager_approval_status' => 'approved',
                'programs_officer_approval_status' => 'approved',
                'programs_manager_approval_status' => 'approved',
                'liaison_approval_status' => 'approved',
                'hq_relations_manager_approval_status' => 'approved',
                'executive_approval_status' => 'approved',
                'executive_review_required' => false,
                'lock_at' => $date->copy()->subDays(5)->endOfDay(),
                'is_official' => true,
                'branch_id' => $zarqaBranch->id,
                'created_by' => $creator->id,
            ]
        );

        $activity->volunteerNeed()->updateOrCreate(
            ['monthly_activity_id' => $activity->id],
            [
                'required_volunteers' => 8,
                'volunteer_need' => 'Registration and crowd guidance volunteers.',
                'volunteer_age_range' => '18-35',
                'volunteer_gender' => 'both',
                'volunteer_tasks_summary' => 'Registration, ushering, and activity support.',
                'volunteers_required' => true,
                'volunteers_count' => 8,
            ]
        );

        $activity->supplies()->delete();
        foreach (['Projector and screen', 'Sound system', 'Printed invitations'] as $item) {
            MonthlyActivitySupply::query()->create([
                'monthly_activity_id' => $activity->id,
                'item_name' => $item,
                'available' => true,
                'status' => 'available',
                'quantity' => 1,
            ]);
        }

        $activity->team()->delete();
        foreach ($this->teamMembers() as $member) {
            MonthlyActivityTeam::query()->create([
                'monthly_activity_id' => $activity->id,
                'team_name' => $member['team_name'],
                'member_name' => $member['member_name'],
                'role_desc' => $member['role_desc'],
            ]);
        }
    }

    protected function executionNeedsPayload(): array
    {
        return [
            'schema_version' => 2,
            'needs_ceremony_agenda' => true,
            'ceremony' => [
                'items_count' => 3,
                'time_from' => '10:00',
                'time_to' => '13:00',
                'items' => [
                    ['order' => 1, 'name' => 'Opening remarks', 'time_from' => '10:00', 'time_to' => '10:20', 'description' => 'Welcome and goals.'],
                    ['order' => 2, 'name' => 'Interactive workshop', 'time_from' => '10:30', 'time_to' => '12:00', 'description' => 'Hands-on family activity.'],
                    ['order' => 3, 'name' => 'Certificates and closing', 'time_from' => '12:15', 'time_to' => '13:00', 'description' => 'Recognition and photos.'],
                ],
            ],
            'needs_transport' => true,
            'transport' => ['vehicles_count' => 1, 'vehicle_type' => 'bus', 'passengers_count' => 35, 'trip_direction' => 'round_trip'],
            'needs_maintenance_workers' => true,
            'maintenance' => ['type' => 'On-site technical support'],
            'needs_gifts' => true,
            'gifts' => ['count' => 40, 'description' => 'Children participation gifts', 'delivery_entity' => 'Zarqa branch'],
            'needs_programs_participation' => true,
            'programs' => ['need_trainer' => true, 'trainer_description' => 'Family workshop trainer', 'trainer_count' => 1, 'zaha_time_options' => ['storytelling']],
            'needs_certificates_and_thanks' => true,
            'certificates' => ['count' => 25, 'template' => 'Official template', 'for' => 'Participants and partners'],
            'thanks_letters' => ['count' => 4, 'template' => 'Official letter', 'for' => 'Partners'],
            'needs_invitations' => true,
            'invitations' => ['type' => 'electronic', 'electronic_template' => 'Zaha event invite'],
        ];
    }

    protected function executionNeedsFollowup(): array
    {
        return collect(array_keys(MonthlyActivity::EXECUTION_NEED_DEFINITIONS))
            ->map(fn (string $key): array => [
                'key' => $key,
                'status' => 'secured',
                'notes' => 'Pre-execution owner confirmed availability.',
                'post_status' => 'provided',
                'post_feedback' => 'Provided during execution and worked as expected.',
            ])
            ->all();
    }

    protected function postExecutionPayload(): array
    {
        return [
            'schema_version' => 1,
            'completed_at' => now()->toDateTimeString(),
            'teams' => [
                ['team_name' => 'Registration Team', 'planned_members_count' => 3, 'all_members_attended' => true, 'actual_attendance_count' => 3, 'accomplished_tasks' => 'Registered guests and prepared attendance sheets.'],
                ['team_name' => 'Activities Team', 'planned_members_count' => 3, 'all_members_attended' => false, 'actual_attendance_count' => 2, 'accomplished_tasks' => 'Delivered the main workshop and coordinated certificates.'],
            ],
            'ceremony_items' => [
                ['order' => 1, 'name' => 'Opening remarks', 'was_implemented' => true, 'feedback' => 'Started on time.'],
                ['order' => 2, 'name' => 'Interactive workshop', 'was_implemented' => true, 'feedback' => 'High engagement from families.'],
                ['order' => 3, 'name' => 'Certificates and closing', 'was_implemented' => true, 'feedback' => 'Certificates were distributed successfully.'],
            ],
        ];
    }

    protected function teamMembers(): array
    {
        return [
            ['team_name' => 'Registration Team', 'member_name' => 'Zarqa Showcase Member 1', 'role_desc' => 'Registration lead'],
            ['team_name' => 'Registration Team', 'member_name' => 'Zarqa Showcase Member 2', 'role_desc' => 'Attendance tracking'],
            ['team_name' => 'Registration Team', 'member_name' => 'Zarqa Showcase Member 3', 'role_desc' => 'Guest guidance'],
            ['team_name' => 'Activities Team', 'member_name' => 'Zarqa Showcase Member 4', 'role_desc' => 'Workshop facilitator'],
            ['team_name' => 'Activities Team', 'member_name' => 'Zarqa Showcase Member 5', 'role_desc' => 'Materials support'],
            ['team_name' => 'Activities Team', 'member_name' => 'Zarqa Showcase Member 6', 'role_desc' => 'Certificates support'],
        ];
    }

    protected function ensureShowcaseUser(Branch $branch): User
    {
        $this->ensureRelationsOfficerRole();

        $user = User::query()->updateOrCreate(
            ['email' => 'showcase-branch-relations-officer-zarqa@zaha.test'],
            [
                'name' => 'Showcase Branch Relations Officer Zarqa',
                'phone' => '0795551400',
                'status' => 'active',
                'branch_id' => $branch->id,
                'password' => Hash::make('password'),
            ]
        );

        $user->syncRoles(['relations_officer']);

        return $user;
    }

    protected function resolveBranch(array $needles): Branch
    {
        return Branch::query()
            ->get()
            ->first(function (Branch $branch) use ($needles): bool {
                $haystack = mb_strtolower(trim((string) $branch->name.' '.(string) $branch->city));

                foreach ($needles as $needle) {
                    if (str_contains($haystack, mb_strtolower($needle))) {
                        return true;
                    }
                }

                return false;
            }) ?? Branch::query()->create([
                'name' => 'مركز زها الثقافي - الزرقاء',
                'city' => 'الزرقاء',
                'address' => null,
                'is_main' => false,
            ]);
    }

    protected function ensureRelationsOfficerRole(): void
    {
        Role::findOrCreate('relations_officer', 'web');
    }
}
