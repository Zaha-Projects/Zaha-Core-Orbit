<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyActivityPhaseZeroEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_change_request_reports_are_available_to_super_admin_and_rejected_for_staff(): void
    {
        $branch = Branch::factory()->create();
        $superAdmin = $this->userWithRole('super_admin', $branch);
        $staff = $this->userWithRole('staff', $branch);

        $this->actingAs($superAdmin)
            ->get(route('role.super_admin.monthly_activities.change_requests.reports'))
            ->assertOk()
            ->assertViewIs('pages.monthly_activities.reports.change_requests')
            ->assertViewHasAll([
                'statistics',
                'recentDeleteRequests',
                'recentEditRequests',
                'requestsByBranch',
                'requestsByStatus',
                'requestsByStep',
                'branches',
                'requesters',
                'filters',
            ]);

        $this->actingAs($staff)
            ->get(route('role.super_admin.monthly_activities.change_requests.reports'))
            ->assertForbidden();
    }

    public function test_trash_is_branch_scoped_and_deleted_show_keeps_its_current_not_found_contract(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $officer = $this->userWithRole('relations_officer', $ownBranch, ['branches.view.own']);

        $ownActivity = $this->trashedActivity($ownBranch, $officer, 'Own deleted monthly plan');
        $otherActivity = $this->trashedActivity($otherBranch, User::factory()->create(), 'Other deleted monthly plan');

        $this->actingAs($officer)
            ->get(route('role.relations.activities.trash', ['year' => now()->year, 'month' => now()->month]))
            ->assertOk()
            ->assertViewIs('pages.monthly_activities.activities.trash')
            ->assertSee('Own deleted monthly plan')
            ->assertDontSee('Other deleted monthly plan');

        $this->actingAs($officer)
            ->get(route('role.relations.activities.deleted.show', $ownActivity->id))
            ->assertNotFound();

        $this->actingAs($officer)
            ->get(route('role.relations.activities.deleted.show', $otherActivity->id))
            ->assertNotFound();
    }

    public function test_returned_feedback_is_branch_scoped_and_keeps_the_current_view(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $manager = $this->userWithRole('relations_manager', $ownBranch, ['branches.view.own']);

        MonthlyActivity::factory()->create([
            'branch_id' => $ownBranch->id,
            'title' => 'Own returned monthly plan',
            'status' => 'changes_requested',
        ]);
        MonthlyActivity::factory()->create([
            'branch_id' => $otherBranch->id,
            'title' => 'Other returned monthly plan',
            'status' => 'changes_requested',
        ]);

        $this->actingAs($manager)
            ->get(route('role.relations.activities.returned_feedback'))
            ->assertOk()
            ->assertViewIs('pages.monthly_activities.activities.returned-feedback')
            ->assertSee('Own returned monthly plan')
            ->assertDontSee('Other returned monthly plan');
    }

    public function test_post_execution_feedback_is_branch_scoped_and_keeps_the_current_view(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $coordinator = $this->userWithRole('volunteer_coordinator', $ownBranch, ['branches.view.own']);

        MonthlyActivity::factory()->create([
            'branch_id' => $ownBranch->id,
            'title' => 'Own post execution return',
            'status' => 'changes_requested',
            'post_execution_payload' => $this->clarificationPayload(),
        ]);
        MonthlyActivity::factory()->create([
            'branch_id' => $otherBranch->id,
            'title' => 'Other post execution return',
            'status' => 'changes_requested',
            'post_execution_payload' => $this->clarificationPayload(),
        ]);

        $this->actingAs($coordinator)
            ->get(route('role.relations.activities.post_execution_feedback'))
            ->assertOk()
            ->assertViewIs('pages.monthly_activities.activities.post-execution-feedback')
            ->assertSee('Own post execution return')
            ->assertDontSee('Other post execution return');
    }

    private function trashedActivity(Branch $branch, User $creator, string $title): MonthlyActivity
    {
        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'created_by' => $creator->id,
            'title' => $title,
            'status' => 'cancelled',
            'proposed_date' => now()->toDateString(),
            'month' => now()->month,
            'day' => now()->day,
        ]);
        $activity->delete();

        return $activity;
    }

    private function clarificationPayload(): array
    {
        return [
            'review' => [
                'decision' => 'clarification',
                'comment' => 'Please clarify.',
            ],
        ];
    }

    private function userWithRole(string $roleName, Branch $branch, array $permissions = []): User
    {
        $role = Role::findOrCreate($roleName, 'web');
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }

        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole($role);

        return $user;
    }
}
