<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyActivityBranchVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_activities_index_requires_authentication(): void
    {
        $this->get(route('role.relations.activities.index'))
            ->assertRedirect(route('login'));
    }

    public function test_user_without_other_branches_permission_cannot_request_all_branches_scope(): void
    {
        $role = Role::findOrCreate('relations_officer', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('role.relations.activities.index', ['scope' => 'all_branches']))
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson(route('role.relations.activities.calendar', ['scope' => 'all_branches']))
            ->assertForbidden();
    }

    public function test_khelda_helper_detects_hq_branch(): void
    {
        $branch = Branch::factory()->create(['name' => 'Khalda HQ', 'city' => 'Amman']);
        $user = User::factory()->create(['branch_id' => $branch->id]);

        $this->assertTrue($user->isKheldaUser());
        $this->assertFalse($user->hasBranchScopedMonthlyVisibility());
    }

    public function test_non_khelda_branch_user_is_scoped_when_role_exists(): void
    {
        $branch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $role = Role::findOrCreate('relations_officer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('branches.view.own', 'web'));
        $user->assignRole($role);

        $this->assertFalse($user->isKheldaUser());
        $this->assertTrue($user->hasBranchScopedMonthlyVisibility());
    }

    public function test_branch_scoped_user_sees_only_own_branch_in_default_monthly_activities_index(): void
    {
        $primaryBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $secondaryBranch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);
        $otherBranch = Branch::factory()->create(['name' => 'Aqaba Branch', 'city' => 'Aqaba']);

        $role = Role::findOrCreate('branch_coordinator', 'web');
        $viewPermission = Permission::findOrCreate('monthly_activities.view', 'web');
        $viewOtherBranchesPermission = Permission::findOrCreate('monthly_activities.view_other_branches', 'web');
        $ownBranchPermission = Permission::findOrCreate('branches.view.own', 'web');
        $role->givePermissionTo([$viewPermission, $viewOtherBranchesPermission, $ownBranchPermission]);

        $user = User::factory()->create(['branch_id' => $primaryBranch->id]);
        $user->assignRole($role);
        $user->assignedBranches()->sync([$primaryBranch->id, $secondaryBranch->id]);

        MonthlyActivity::factory()->create([
            'title' => 'Primary branch activity',
            'branch_id' => $primaryBranch->id,
            'status' => 'submitted',
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Secondary branch activity',
            'branch_id' => $secondaryBranch->id,
            'status' => 'submitted',
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Other branch activity',
            'branch_id' => $otherBranch->id,
            'status' => 'submitted',
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.index', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertSee('Primary branch activity')
            ->assertDontSee('Secondary branch activity')
            ->assertDontSee('Other branch activity');
    }

    public function test_other_branches_scope_shows_approved_other_branch_plans_not_own_branch_plans(): void
    {
        $primaryBranch = Branch::factory()->create(['name' => 'Irbid Branch', 'city' => 'Irbid']);
        $secondaryBranch = Branch::factory()->create(['name' => 'Zarqa Branch', 'city' => 'Zarqa']);

        $role = Role::findOrCreate('branch_coordinator', 'web');
        $viewPermission = Permission::findOrCreate('monthly_activities.view', 'web');
        $viewOtherBranchesPermission = Permission::findOrCreate('monthly_activities.view_other_branches', 'web');
        $ownBranchPermission = Permission::findOrCreate('branches.view.own', 'web');
        $role->givePermissionTo([$viewPermission, $viewOtherBranchesPermission, $ownBranchPermission]);

        $user = User::factory()->create(['branch_id' => $primaryBranch->id]);
        $user->assignRole($role);

        MonthlyActivity::factory()->create([
            'title' => 'Own approved plan',
            'branch_id' => $primaryBranch->id,
            'status' => 'approved',
            'executive_approval_status' => 'approved',
            'lifecycle_status' => 'Approved',
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Other approved plan',
            'branch_id' => $secondaryBranch->id,
            'status' => 'approved',
            'executive_approval_status' => 'approved',
            'lifecycle_status' => 'Approved',
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Other draft plan',
            'branch_id' => $secondaryBranch->id,
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.index', ['scope' => 'all_branches', 'year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertSee('Other approved plan')
            ->assertDontSee('Own approved plan')
            ->assertDontSee('Other draft plan');
    }

    public function test_volunteer_coordinator_sees_only_monthly_activities_that_need_volunteers(): void
    {
        $role = Role::findOrCreate('volunteer_coordinator', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        MonthlyActivity::factory()->create([
            'title' => 'Needs volunteers',
            'status' => 'submitted',
            'needs_volunteers' => true,
            'required_volunteers' => 4,
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'No volunteers needed',
            'status' => 'submitted',
            'needs_volunteers' => false,
            'required_volunteers' => null,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.index', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertSee('Needs volunteers')
            ->assertDontSee('No volunteers needed');
    }

    public function test_monthly_activities_index_filters_by_grouped_status(): void
    {
        $role = Role::findOrCreate('relations_officer', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        MonthlyActivity::factory()->create([
            'title' => 'In review filtered activity',
            'status' => 'in_review',
            'proposed_date' => '2026-03-08',
            'month' => 3,
            'day' => 8,
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Draft excluded by submitted filter',
            'status' => 'draft',
            'created_by' => $user->id,
            'proposed_date' => '2026-03-09',
            'month' => 3,
            'day' => 9,
        ]);
        MonthlyActivity::factory()->create([
            'title' => 'Different month activity',
            'status' => 'in_review',
            'proposed_date' => '2026-04-09',
            'month' => 4,
            'day' => 9,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.index', ['year' => 2026, 'month' => 3, 'status' => 'submitted']))
            ->assertOk()
            ->assertSee('In review filtered activity')
            ->assertDontSee('Draft excluded by submitted filter')
            ->assertDontSee('Different month activity');
    }

    public function test_monthly_activities_index_preserves_supported_pagination_size(): void
    {
        $role = Role::findOrCreate('relations_officer', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        MonthlyActivity::factory()->count(9)->create([
            'status' => 'submitted',
            'proposed_date' => '2026-03-08',
            'month' => 3,
            'day' => 8,
        ]);

        $response = $this->actingAs($user)
            ->get(route('role.relations.activities.index', [
                'year' => 2026,
                'month' => 3,
                'per_page' => 8,
            ]))
            ->assertOk();

        $this->assertSame(8, $response->viewData('activities')->perPage());
        $this->assertSame(9, $response->viewData('activities')->total());
    }

    public function test_monthly_activities_calendar_filters_by_status(): void
    {
        $role = Role::findOrCreate('relations_officer', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $inReviewActivity = MonthlyActivity::factory()->create([
            'title' => 'Calendar in review activity',
            'status' => 'in_review',
            'proposed_date' => '2026-03-10',
            'month' => 3,
            'day' => 10,
        ]);
        $draftActivity = MonthlyActivity::factory()->create([
            'title' => 'Calendar draft activity',
            'status' => 'draft',
            'created_by' => $user->id,
            'proposed_date' => '2026-03-11',
            'month' => 3,
            'day' => 11,
        ]);

        $this->actingAs($user)
            ->getJson(route('role.relations.activities.calendar', ['year' => 2026, 'month' => 3, 'status' => 'submitted']))
            ->assertOk()
            ->assertJsonFragment(['id' => $inReviewActivity->id])
            ->assertJsonMissing(['id' => $draftActivity->id]);
    }

    public function test_monthly_activities_calendar_preserves_json_contract_and_order(): void
    {
        $role = Role::findOrCreate('relations_officer', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $laterActivity = MonthlyActivity::factory()->create([
            'title' => 'Later calendar activity',
            'status' => 'submitted',
            'created_by' => $user->id,
            'proposed_date' => '2026-03-12',
            'month' => 3,
            'day' => 12,
        ]);
        $earlierActivity = MonthlyActivity::factory()->create([
            'title' => 'Earlier calendar activity',
            'status' => 'submitted',
            'created_by' => $user->id,
            'proposed_date' => '2026-03-05',
            'month' => 3,
            'day' => 5,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('role.relations.activities.calendar', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertJsonStructure([
                'year',
                'month',
                'items' => [[
                    'id',
                    'title',
                    'date',
                    'branch',
                    'status',
                    'source_label',
                    'event_type',
                    'event_type_label',
                    'plan_type',
                    'plan_type_label',
                    'plan_version',
                    'requires_workshops',
                    'requires_communications',
                    'edit_url',
                    'post_execution_url',
                    'can_complete_after_execution',
                    'open_url',
                    'read_only_unified',
                ]],
            ])
            ->assertJsonPath('year', 2026)
            ->assertJsonPath('month', 3)
            ->assertJsonPath('items.0.id', $earlierActivity->id)
            ->assertJsonPath('items.1.id', $laterActivity->id)
            ->assertJsonPath('items.0.date', '2026-03-05')
            ->assertJsonPath('items.0.plan_version', 1)
            ->assertJsonPath('items.0.requires_workshops', false)
            ->assertJsonPath('items.0.requires_communications', false)
            ->assertJsonPath('items.0.can_complete_after_execution', true)
            ->assertJsonPath('items.0.read_only_unified', false);

        $this->assertCount(18, $response->json('items.0'));
    }

    public function test_volunteer_coordinator_cannot_open_activity_that_does_not_need_volunteers(): void
    {
        $role = Role::findOrCreate('volunteer_coordinator', 'web');
        $user = User::factory()->create();
        $user->assignRole($role);

        $activity = MonthlyActivity::factory()->create([
            'status' => 'submitted',
            'needs_volunteers' => false,
            'required_volunteers' => null,
        ]);

        $this->actingAs($user)
            ->get(route('role.relations.activities.show', $activity))
            ->assertForbidden();
    }

    public function test_calendar_exposes_post_execution_button_only_to_activity_creator(): void
    {
        $role = Role::findOrCreate('relations_officer', 'web');
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $creator->assignRole($role);
        $otherUser->assignRole($role);

        $activity = MonthlyActivity::factory()->create([
            'title' => 'Creator calendar activity',
            'status' => 'submitted',
            'created_by' => $creator->id,
            'proposed_date' => '2026-03-19',
            'month' => 3,
            'day' => 19,
        ]);

        $this->actingAs($creator)
            ->getJson(route('role.relations.activities.calendar', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $activity->id,
                'can_complete_after_execution' => true,
            ]);

        $this->actingAs($otherUser)
            ->getJson(route('role.relations.activities.calendar', ['year' => 2026, 'month' => 3]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $activity->id,
                'can_complete_after_execution' => false,
                'post_execution_url' => null,
            ]);
    }
}
