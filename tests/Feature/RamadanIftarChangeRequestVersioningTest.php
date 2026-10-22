<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Models\User;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarChangeRequest;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Modules\Events\Models\TargetGroup;
use App\Modules\Events\Services\RamadanIftarChangeRequestService;
use App\Modules\Events\Services\RamadanIftarExecutionService;
use App\Modules\Events\Services\RamadanIftarSubmissionService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\WorkflowSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RamadanIftarChangeRequestVersioningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->seed(WorkflowSeeder::class);
    }

    public function test_only_approved_open_unstarted_current_version_can_request_change(): void
    {
        [$source, $requester] = $this->source();
        $service = app(RamadanIftarChangeRequestService::class);
        foreach ([RamadanIftar::STATUS_DRAFT, RamadanIftar::STATUS_SUBMITTED] as $status) {
            $source->update(['status' => $status]);
            $this->assertRejected(fn () => $service->create($source->fresh(), $requester, 'A meaningful revision reason'));
        }
        $source->update(['status' => RamadanIftar::STATUS_APPROVED, 'execution_status' => RamadanIftar::EXECUTION_STATUS_IN_PROGRESS]);
        $this->assertRejected(fn () => $service->create($source->fresh(), $requester, 'A meaningful revision reason'));
        $source->update(['execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED, 'closed_at' => now()]);
        $this->assertRejected(fn () => $service->create($source->fresh(), $requester, 'A meaningful revision reason'));
    }

    public function test_duplicate_active_request_is_rejected(): void
    {
        [$source, $requester] = $this->source();
        $service = app(RamadanIftarChangeRequestService::class);
        $service->create($source, $requester, 'The approved location must be revised');
        $this->assertRejected(fn () => $service->create($source->fresh(), $requester, 'Another pending request'));
        $this->assertDatabaseCount('ramadan_iftar_change_requests', 1);
    }

    public function test_request_endpoint_enforces_permission_and_branch(): void
    {
        [$source] = $this->source();
        $unauthorized = User::factory()->create(['branch_id' => $source->branch_id, 'status' => 'active']);
        $this->actingAs($unauthorized)->post(route('events.ramadan.iftars.change-request.store', $source), ['reason' => 'A meaningful request reason'])->assertForbidden();
        $otherBranch = User::factory()->create(['branch_id' => Branch::factory()->create()->id, 'status' => 'active']);
        $otherBranch->assignRole('relations_officer');
        $this->actingAs($otherBranch)->post(route('events.ramadan.iftars.change-request.store', $source), ['reason' => 'A meaningful request reason'])->assertForbidden();
    }

    public function test_review_is_branch_scoped_and_self_review_is_forbidden(): void
    {
        [$source, $requester] = $this->source();
        $service = app(RamadanIftarChangeRequestService::class);
        $request = $service->create($source, $requester, 'A separately reviewed approved-plan change');
        $wrongBranch = User::factory()->create(['branch_id' => Branch::factory()->create()->id, 'status' => 'active']);
        $wrongBranch->assignRole('supervisor');
        $this->actingAs($wrongBranch)->get(route('events.ramadan.change-requests.reviews.show', $request))->assertForbidden();

        $requester->assignRole('supervisor');
        try {
            $service->decide($request->fresh(), $requester->fresh(), 'approved', null);
            $this->fail('A requester reviewed their own change request.');
        } catch (AuthorizationException $exception) {
            $this->assertSame(RamadanIftarChangeRequest::STATUS_PENDING, $request->fresh()->status);
        }
    }

    public function test_rejection_preserves_source_and_creates_no_revision(): void
    {
        [$source, $requester] = $this->source();
        $service = app(RamadanIftarChangeRequestService::class);
        $request = $service->create($source, $requester, 'A request that will be rejected');
        $reviewer = User::factory()->create(['branch_id' => $source->branch_id, 'status' => 'active']);
        $reviewer->assignRole('supervisor');
        $request = $service->decide($request, $reviewer, 'rejected', 'The approved plan remains suitable.');
        $this->assertSame(RamadanIftarChangeRequest::STATUS_REJECTED, $request->status);
        $this->assertNull($request->created_version_id);
        $this->assertSame(RamadanIftar::STATUS_APPROVED, $source->fresh()->status);
        $this->assertFalse($source->versions()->exists());
    }

    public function test_final_approval_deep_copies_plan_without_operational_history(): void
    {
        [$source, $requester] = $this->source();
        $meal = $source->meals()->create(['description' => 'Meal', 'planned_quantity' => 20, 'actual_quantity' => 17, 'rating' => 4]);
        $meal->items()->create(['name' => 'Rice', 'item_type' => 'main', 'quantity' => 20, 'sort_order' => 1]);
        $source->gifts()->create(['description' => 'Gift', 'planned_quantity' => 5, 'actual_quantity' => 4]);
        $source->programSegments()->create(['name' => 'Welcome', 'sort_order' => 1, 'execution_status' => 'completed', 'actual_notes' => 'Done']);
        $team = $source->executionTeams()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'name' => 'Hosts', 'planned_members_count' => 2, 'actual_members_count' => 2]);
        $team->members()->create(['member_name' => 'Member', 'task_description' => 'Welcome', 'task_completed' => true]);
        $targetGroup = TargetGroup::query()->create(['name' => 'Revision guests', 'code' => 'revision-guests']);
        $source->targetGroupSelections()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'target_group_id' => $targetGroup->id, 'target_group_custom_text' => 'Guests', 'planned_count' => 20, 'actual_count' => 18]);
        $source->volunteerRequirements()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'planned_count' => 3, 'actual_count' => 2, 'status' => 'pending']);
        $source->supplies()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'item_name' => 'Water', 'planned_quantity' => 20, 'actual_quantity' => 18, 'status' => 'pending']);
        $type = ExecutionNeedType::query()->create(['code' => 'revision-need', 'name' => 'Transport', 'is_canonical' => true, 'is_active' => true, 'is_ramadan_iftar' => true]);
        $source->executionNeeds()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'execution_need_type_id' => $type->id, 'is_required' => true, 'planned_details' => 'Bus', 'status' => SubjectExecutionNeed::STATUS_COMPLETED, 'actual_details' => 'Provided']);
        $source->attendees()->create(['full_name' => 'Attendee', 'attended' => true]);
        $method = MonitoringMethod::query()->create(['code' => 'field', 'name_ar' => 'ميداني', 'name_en' => 'Field', 'is_active' => true, 'sort_order' => 1]);
        $source->monitoringReports()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'monitoring_method_id' => $method->id, 'monitor_user_id' => $requester->id, 'status' => MonitoringReport::STATUS_APPROVED]);

        $service = app(RamadanIftarChangeRequestService::class);
        $request = $service->create($source, $requester, 'The approved plan needs a date adjustment');
        foreach (['supervisor', 'branch_coordinator', 'relations_manager', 'executive_manager'] as $role) {
            $reviewer = User::factory()->create(['branch_id' => $source->branch_id, 'status' => 'active']);
            $reviewer->assignRole($role);
            $request = $service->decide($request->fresh(), $reviewer, 'approved', null);
        }
        $revision = $request->createdVersion;

        $this->assertSame($source->id, $revision->parent_version_id);
        $this->assertSame(2, $revision->version_number);
        $this->assertSame($source->branch_id, $revision->branch_id);
        $this->assertSame($source->relations_officer_id, $revision->relations_officer_id);
        $this->assertSame($reviewer->id, $revision->created_by);
        $this->assertSame($source->guidance_version_id, $revision->guidance_version_id);
        $this->assertTrue($source->guidance_accepted_at->equalTo($revision->guidance_accepted_at));
        $this->assertSame(RamadanIftar::STATUS_DRAFT, $revision->status);
        $this->assertSame(RamadanIftar::EXECUTION_STATUS_PLANNED, $revision->execution_status);
        $this->assertNull($revision->actual_date);
        $this->assertNull($revision->actual_attendance);
        $this->assertNull($revision->submitted_at);
        $this->assertNull($revision->approved_at);
        $this->assertNull($revision->closed_at);
        $this->assertCount(1, $revision->meals);
        $this->assertNotSame($meal->id, $revision->meals->first()->id);
        $this->assertCount(1, $revision->meals->first()->items);
        $this->assertNull($revision->meals->first()->actual_quantity);
        $this->assertCount(1, $revision->gifts);
        $this->assertNull($revision->gifts->first()->actual_quantity);
        $this->assertCount(1, $revision->programSegments);
        $this->assertSame('planned', $revision->programSegments->first()->execution_status);
        $this->assertNull($revision->programSegments->first()->actual_notes);
        $this->assertCount(1, $revision->executionTeams);
        $this->assertNotSame($team->id, $revision->executionTeams->first()->id);
        $this->assertNull($revision->executionTeams->first()->actual_members_count);
        $this->assertFalse((bool) $revision->executionTeams->first()->members->first()->task_completed);
        $this->assertCount(1, $revision->targetGroupSelections);
        $this->assertNull($revision->targetGroupSelections->first()->actual_count);
        $this->assertCount(1, $revision->volunteerRequirements);
        $this->assertNull($revision->volunteerRequirements->first()->actual_count);
        $this->assertCount(1, $revision->supplies);
        $this->assertNull($revision->supplies->first()->actual_quantity);
        $this->assertCount(1, $revision->executionNeeds);
        $this->assertSame(EventSubjectTypes::RAMADAN_IFTAR, $revision->executionNeeds->first()->subject_type);
        $this->assertSame($revision->id, $revision->executionNeeds->first()->subject_id);
        $this->assertSame(SubjectExecutionNeed::STATUS_PENDING, $revision->executionNeeds->first()->status);
        $this->assertNull($revision->executionNeeds->first()->actual_details);
        $this->assertCount(1, $revision->attendees);
        $this->assertSame('Attendee', $revision->attendees->first()->full_name);
        $this->assertFalse((bool) $revision->attendees->first()->attended);
        $this->assertCount(0, $revision->monitoringReports);
        $this->assertNull($revision->workflowInstance);
        $this->assertSame(RamadanIftar::STATUS_APPROVED, $source->fresh()->status);
        $this->assertSame(17, $meal->fresh()->actual_quantity);
        $this->assertSame(4, $meal->fresh()->rating);
        $this->assertSame('completed', $source->programSegments()->first()->execution_status);
        $this->assertFalse($source->fresh()->canAccessExecution());
        $this->assertRejected(fn () => app(RamadanIftarExecutionService::class)->start($source->fresh(), $requester));
        app(RamadanIftarSubmissionService::class)->submit($revision->fresh(), $requester);
        $this->assertNotNull($revision->fresh()->workflowInstance);
        $this->assertNotSame($request->workflowInstance->id, $revision->fresh()->workflowInstance->id);
    }

    public function test_version_chain_is_linear_and_current_version_is_deterministic(): void
    {
        [$v1, $requester] = $this->source();
        $service = app(RamadanIftarChangeRequestService::class);
        $v2 = $this->approveAll($service, $service->create($v1, $requester, 'Create the second approved-plan version'), $v1)->createdVersion;
        $v2->update(['status' => RamadanIftar::STATUS_APPROVED]);
        $v3 = $this->approveAll($service, $service->create($v2, $requester, 'Create the third approved-plan version'), $v2)->createdVersion;

        $this->assertSame(2, $v2->version_number);
        $this->assertSame($v1->id, $v2->parent_version_id);
        $this->assertSame(3, $v3->version_number);
        $this->assertSame($v2->id, $v3->parent_version_id);
        $this->assertSame($v3->id, $v1->fresh()->latestVersion()->id);
        $this->assertSame([1, 2, 3], $v2->fresh()->versionHistory()->pluck('version_number')->all());
    }

    private function source(): array
    {
        $branch = Branch::factory()->create();
        $requester = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $requester->assignRole('relations_officer');
        $guidance = EventGuidanceVersion::query()->create(['code' => EventGuidanceVersion::RAMADAN_IFTAR, 'version_number' => 1, 'title' => 'Guidance', 'content' => 'Approved guidance', 'is_active' => true, 'published_at' => now(), 'created_by' => $requester->id]);
        $source = RamadanIftar::query()->create(['branch_id' => $branch->id, 'title' => 'Approved Iftar', 'relations_officer_id' => $requester->id, 'created_by' => $requester->id, 'planned_date' => '2026-03-01', 'location_type' => RamadanIftar::LOCATION_INSIDE_CENTER, 'host_type' => RamadanIftar::HOST_CENTER, 'planned_meals_count' => 0, 'expected_attendance' => 0, 'status' => RamadanIftar::STATUS_APPROVED, 'execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED, 'version_number' => 1, 'guidance_version_id' => $guidance->id, 'guidance_accepted_at' => now(), 'approved_at' => now()]);
        return [$source, $requester];
    }

    private function approveAll(RamadanIftarChangeRequestService $service, RamadanIftarChangeRequest $request, RamadanIftar $source): RamadanIftarChangeRequest
    {
        foreach (['supervisor', 'branch_coordinator', 'relations_manager', 'executive_manager'] as $role) {
            $reviewer = User::factory()->create(['branch_id' => $source->branch_id, 'status' => 'active']);
            $reviewer->assignRole($role);
            $request = $service->decide($request->fresh(), $reviewer, 'approved', null);
        }
        return $request;
    }

    private function assertRejected(callable $action): void
    {
        try { $action(); $this->fail('The change request should have been rejected.'); }
        catch (ValidationException $exception) { $this->assertNotEmpty($exception->errors()); }
    }
}
