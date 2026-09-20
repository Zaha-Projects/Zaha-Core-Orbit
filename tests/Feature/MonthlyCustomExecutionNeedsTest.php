<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\EventGuidanceAcknowledgement;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\RamadanIftar;
use Database\Seeders\RamadanReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonthlyCustomExecutionNeedsTest extends TestCase
{
    use RefreshDatabase;

    private function planner(): User
    {
        foreach (['branch_coordinator', 'relations_officer', 'relations_manager', 'programs_manager', 'programs_officer', 'executive_manager', 'followup_officer', 'supervisor', 'volunteer_coordinator', 'liaison_officer', 'super_admin'] as $role) Role::findOrCreate($role, 'web');
        $this->seed(RamadanReferenceDataSeeder::class);
        $user = User::factory()->create(['branch_id' => Branch::factory()->create()->id]);
        $user->assignRole('relations_officer');
        $this->actingAs($user);

        return $user;
    }

    private function payload(User $user): array
    {
        return ['title' => 'Custom monthly plan', 'description' => 'Planning details', 'activity_date' => '2026-10-05', 'proposed_date' => '2026-10-05',
            'branch_id' => $user->branch_id, 'location_type' => 'inside_center', 'internal_location' => 'Hall', 'execution_status' => 'planned', 'submit_action' => 'draft'];
    }

    private function type(string $scope, bool $active = true): ExecutionNeedType
    {
        return ExecutionNeedType::create(['code' => 'custom_'.$scope, 'name' => 'Custom '.$scope, 'is_active' => $active, 'is_canonical' => true, 'usage_scope' => $scope]);
    }

    public function test_four_scopes_and_inactive_types_are_visible_only_in_the_right_new_forms(): void
    {
        $user = $this->planner();
        $types = collect(ExecutionNeedType::usageScopes())->mapWithKeys(fn ($scope) => [$scope => $this->type($scope)]);
        $inactive = ExecutionNeedType::create(['code' => 'custom_inactive', 'name' => 'Custom inactive', 'is_active' => false, 'is_canonical' => true, 'usage_scope' => 'both']);
        $form = $this->get(route('role.relations.activities.create'))->assertOk()
            ->assertSee('Custom monthly_plans')->assertSee('Custom both')->assertDontSee('Custom iftars')->assertDontSee('Custom none')->assertDontSee($inactive->name);
        $dom = new \DOMDocument();
        @$dom->loadHTML($form->getContent());
        $xpath = new \DOMXPath($dom);
        $this->assertSame(1, $xpath->query('//form[@action="'.route('role.relations.activities.store').'"]//input[@id="custom-need-'.$types['monthly_plans']->id.'"]')->length, 'Custom need must be inside the submitted planning form.');
        $user->syncRoles(['super_admin']);
        EventGuidanceAcknowledgement::create(['user_id' => $user->id, 'event_guidance_version_id' => EventGuidanceVersion::currentForRamadan()->id, 'acknowledged_at' => now()]);
        $this->get(route('events.ramadan.iftars.create'))->assertOk()
            ->assertSee('Custom iftars')->assertSee('Custom both')->assertDontSee('Custom monthly_plans')->assertDontSee('Custom none')->assertDontSee($inactive->name);
        $host = CommunityOrganization::create(['branch_id' => $user->branch_id, 'name' => 'Test host']);
        $iftarPayload = ['title' => 'Custom iftar', 'planned_date' => '2026-02-20', 'relations_officer_id' => $user->id,
            'host_type' => 'association', 'location_type' => 'outside_center', 'community_organization_id' => $host->id,
            'contact_name' => 'Liaison', 'contact_phone' => '0790000000', 'location_name' => 'Hall',
            'execution_needs' => ExecutionNeedType::ramadanAvailableTypes()->filter->isMandatoryForRamadan()->map(fn ($type) => ['execution_need_type_id' => $type->id, 'is_required' => true])->values()->all(),
            'execution_teams' => [['name' => 'Team', 'members' => [['member_name' => 'Host', 'task_description' => 'Welcome']]]]];
        $iftarPayload['execution_needs'][] = ['execution_need_type_id' => $types['both']->id, 'is_required' => true, 'planned_details' => 'Both systems'];
        $this->post(route('events.ramadan.iftars.store'), $iftarPayload)->assertSessionHasNoErrors();
        $this->assertSame('Both systems', RamadanIftar::sole()->executionNeeds()->where('execution_need_type_id', $types['both']->id)->sole()->planned_details);
        foreach (['none', 'monthly_plans'] as $scope) {
            $forged = $iftarPayload;
            $forged['execution_needs'][] = ['execution_need_type_id' => $types[$scope]->id, 'is_required' => true];
            $this->post(route('events.ramadan.iftars.store'), $forged)->assertSessionHasErrors('execution_needs.2.execution_need_type_id');
        }
    }

    public function test_monthly_and_both_save_reopen_and_survive_title_edits_without_duplicates(): void
    {
        $user = $this->planner();
        $monthly = $this->type('monthly_plans');
        $both = $this->type('both');
        $payload = $this->payload($user) + ['custom_execution_needs' => [
            ['execution_need_type_id' => $monthly->id, 'is_required' => true, 'planned_details' => 'Monthly notes'],
            ['execution_need_type_id' => $both->id, 'is_required' => true, 'planned_details' => 'Both notes'],
        ]];
        $this->post(route('role.relations.activities.store'), $payload)->assertSessionHasNoErrors();
        $plan = MonthlyActivity::sole();
        $before = $plan->executionNeeds()->orderBy('id')->get()->toArray();
        $this->get(route('role.relations.activities.edit', [$plan, 'form' => 1]))->assertOk()->assertSee('Monthly notes')->assertSee('Both notes');
        $this->get(route('role.relations.activities.show', $plan))->assertOk()->assertSee('Monthly notes');
        foreach (range(1, 2) as $save) {
            $this->put(route('role.relations.activities.update', $plan), array_replace($payload, ['title' => 'Renamed '.$save]))->assertSessionHasNoErrors();
        }
        unset($payload['custom_execution_needs']);
        $this->put(route('role.relations.activities.update', $plan), $payload)->assertSessionHasNoErrors();
        $this->assertSame(2, $plan->executionNeeds()->count());
        $this->assertSame($before, $plan->executionNeeds()->orderBy('id')->get()->toArray());
    }

    public function test_historic_needs_are_readable_preserved_and_cannot_be_added_or_changed(): void
    {
        $user = $this->planner();
        $type = $this->type('both');
        $row = ['execution_need_type_id' => $type->id, 'is_required' => true, 'planned_details' => 'Historic notes'];
        $payload = $this->payload($user) + ['custom_execution_needs' => [$row]];
        $this->post(route('role.relations.activities.store'), $payload)->assertSessionHasNoErrors();
        $plan = MonthlyActivity::sole();
        foreach ([['is_active' => false], ['is_active' => true, 'usage_scope' => 'iftars'], ['usage_scope' => 'none']] as $change) {
            $type->update($change);
            $this->get(route('role.relations.activities.create'))->assertOk()->assertDontSee($type->name);
            $this->get(route('role.relations.activities.edit', [$plan, 'form' => 1]))->assertOk()->assertSee('Historic notes')->assertSee('للقراءة');
            $this->put(route('role.relations.activities.update', $plan), $payload)->assertSessionHasNoErrors();
            $this->post(route('role.relations.activities.store'), $payload)->assertSessionHasErrors('custom_execution_needs.0.execution_need_type_id');
            $forged = $payload;
            $forged['custom_execution_needs'][0]['planned_details'] = 'Tampered';
            $this->put(route('role.relations.activities.update', $plan), $forged)->assertSessionHasErrors('custom_execution_needs.0.execution_need_type_id');
        }
        $this->assertSame('Historic notes', $plan->executionNeeds()->sole()->planned_details);
    }

    public function test_invalid_duplicate_and_specialized_ids_are_rejected_and_explicit_deselection_clears_planning_only(): void
    {
        $user = $this->planner();
        $type = $this->type('monthly_plans');
        $row = ['execution_need_type_id' => $type->id, 'is_required' => true, 'planned_details' => 'Original'];
        $payload = $this->payload($user);
        $this->post(route('role.relations.activities.store'), $payload + ['custom_execution_needs' => [$row, $row]])->assertSessionHasErrors('custom_execution_needs.0.execution_need_type_id');
        $this->post(route('role.relations.activities.store'), $payload + ['custom_execution_needs' => [array_replace($row, ['execution_need_type_id' => ExecutionNeedType::where('code', 'transport')->sole()->id])]])->assertSessionHasErrors('custom_execution_needs.0.execution_need_type_id');
        $this->post(route('role.relations.activities.store'), $payload + ['custom_execution_needs' => [$row + ['actual_details' => 'Forged']]])->assertSessionHasErrors('custom_execution_needs.0');
        $this->post(route('role.relations.activities.store'), $payload + ['custom_execution_needs' => [$row]])->assertSessionHasNoErrors();
        $plan = MonthlyActivity::sole();
        $this->put(route('role.relations.activities.update', $plan), $payload + ['custom_execution_needs' => [array_replace($row, ['is_required' => false])]])->assertSessionHasNoErrors();
        $this->assertFalse($plan->executionNeeds()->sole()->is_required);
        $this->assertNull($plan->executionNeeds()->sole()->planned_details);
    }

    public function test_approved_plan_changes_wait_for_approval_and_copy_custom_needs_into_the_new_version(): void
    {
        $user = $this->planner();
        $type = $this->type('both');
        $payload = $this->payload($user) + ['custom_execution_needs' => [['execution_need_type_id' => $type->id, 'is_required' => true, 'planned_details' => 'Approved detail']]];
        $this->post(route('role.relations.activities.store'), $payload)->assertSessionHasNoErrors();
        $source = MonthlyActivity::sole();
        $source->update(['relations_manager_approval_status' => 'approved', 'status' => 'approved']);
        $workflow = \App\Models\Workflow::create(['code' => 'custom_monthly_review', 'module' => 'monthly_activities', 'is_active' => true]);
        $workflow->steps()->create(['step_order' => 1, 'step_key' => 'monthly_supervisor_review', 'step_type' => 'main', 'approval_level' => 1, 'role_id' => Role::findByName('supervisor')->id, 'is_editable' => true]);
        $reviewer = User::factory()->create(['branch_id' => $user->branch_id]);
        $reviewer->assignRole('supervisor');
        $workflows = app(\App\Services\DynamicWorkflowService::class);
        $sourceWorkflow = $workflows->forModel('monthly_activities', $source);
        $workflows->recordDecision($sourceWorkflow, $workflows->currentStepForUser($sourceWorkflow, $reviewer), $reviewer, 'approved');
        $payload['custom_execution_needs'][0]['planned_details'] = 'Proposed new detail';
        $this->put(route('role.relations.activities.update', $source), $payload)->assertSessionHasNoErrors();
        $change = \App\Modules\Events\Models\MonthlyPlanEditRequest::sole();
        $this->assertSame('Approved detail', $source->executionNeeds()->sole()->planned_details);
        $this->assertSame('Proposed new detail', $change->new_values['custom_execution_needs'][0]['planned_details']);
        app(\App\Services\PlanChangeRequestWorkflowService::class)->decide($change, 'monthly_activities', $reviewer, 'approved');
        $version = MonthlyActivity::findOrFail($change->fresh()->approved_version_id);
        $this->assertSame('Proposed new detail', $version->executionNeeds()->sole()->planned_details);
        $this->assertSame('pending', $version->executionNeeds()->sole()->status);
        $this->assertSame('Approved detail', $source->executionNeeds()->sole()->planned_details);
    }

    public function test_non_admin_cannot_manage_types_and_foreign_branch_cannot_edit_the_plan(): void
    {
        $user = $this->planner();
        $type = $this->type('both');
        $this->post(route('role.super_admin.ramadan_reference_data.store', 'execution_need_types'), [])->assertForbidden();
        $this->put(route('role.super_admin.ramadan_reference_data.update', ['execution_need_types', $type->id]), [])->assertForbidden();
        $this->post(route('role.relations.activities.store'), $this->payload($user))->assertSessionHasNoErrors();
        $plan = MonthlyActivity::sole();
        $foreign = User::factory()->create(['branch_id' => Branch::factory()->create()->id]);
        $foreign->assignRole('relations_officer');
        $this->actingAs($foreign)->put(route('role.relations.activities.update', $plan), $this->payload($foreign))->assertForbidden();
    }

    public function test_local_qa_cleanup_refuses_production_and_the_test_database(): void
    {
        foreach (['production', 'testing', 'local'] as $environment) {
            $this->app->instance('env', $environment);
            $this->artisan('ramadan:cleanup-local-qa', ['--execute' => true])
                ->expectsOutput('Refusing cleanup outside local zaha_core_orbit on loopback.')
                ->assertExitCode(1);
        }
        $this->app->instance('env', 'testing');
    }
}
