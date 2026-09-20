<?php

namespace App\Modules\Events\Services;

use App\Models\User;
use App\Models\WorkflowActionLog;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarChangeRequest;
use App\Modules\Events\Models\RamadanIftarProgramSegment;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Modules\Events\Models\EventSupply;
use App\Modules\Events\Models\SubjectVolunteerRequirement;
use App\Services\DynamicWorkflowService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RamadanIftarChangeRequestService
{
    private const CORE_FIELDS = [
        'agenda_event_id', 'branch_id', 'ramadan_period_id', 'title', 'description', 'relations_officer_id', 'planned_date',
        'time_from', 'time_to', 'location_type', 'location_name', 'address', 'google_maps_url',
        'contact_name', 'contact_phone', 'supporting_entity_name', 'host_type',
        'community_organization_id', 'local_community_id', 'mobilization_method_id',
        'mobilization_method_other', 'planned_meals_count', 'expected_attendance',
        'guidance_version_id', 'guidance_accepted_at',
    ];

    public function __construct(private DynamicWorkflowService $workflows) {}

    public function create(RamadanIftar $source, User $actor, string $reason): RamadanIftarChangeRequest
    {
        return DB::transaction(function () use ($source, $actor, $reason) {
            $source = RamadanIftar::query()->lockForUpdate()->findOrFail($source->id);
            $this->authorizeRequester($source, $actor);
            $this->assertEligibleSource($source);
            if (strlen(trim($reason)) < 10) $this->invalid('reason', 'reason_required');
            if ($source->changeRequests()->where('status', RamadanIftarChangeRequest::STATUS_PENDING)->exists()) {
                $this->invalid('reason', 'duplicate');
            }
            $request = $source->changeRequests()->create([
                'requested_by' => $actor->id, 'branch_id' => $source->branch_id,
                'reason' => trim($reason), 'status' => RamadanIftarChangeRequest::STATUS_PENDING,
            ]);
            $instance = $this->workflows->forModel(RamadanIftarChangeRequest::WORKFLOW_MODULE, $request);
            if (! $instance) $this->invalid('workflow', 'workflow_missing');
            $this->audit($source, $actor, 'change_request_created', $request);

            return $request->fresh('workflowInstance.currentStep.role');
        });
    }

    public function decide(RamadanIftarChangeRequest $request, User $actor, string $decision, ?string $comment): RamadanIftarChangeRequest
    {
        return DB::transaction(function () use ($request, $actor, $decision, $comment) {
            $request = RamadanIftarChangeRequest::query()->lockForUpdate()->findOrFail($request->id);
            $source = RamadanIftar::query()->lockForUpdate()->findOrFail($request->ramadan_iftar_id);
            $this->authorizeReviewer($source, $request, $actor);
            if ($request->status !== RamadanIftarChangeRequest::STATUS_PENDING) $this->invalid('decision', 'not_pending');
            $this->assertEligibleSource($source);
            if (! in_array($decision, [DynamicWorkflowService::DECISION_APPROVED, DynamicWorkflowService::DECISION_REJECTED], true)) $this->invalid('decision', 'invalid_decision');
            if ($decision === DynamicWorkflowService::DECISION_REJECTED && blank($comment)) $this->invalid('comment', 'rejection_comment');

            $instance = $request->workflowInstance()->with('workflow.steps.role', 'currentStep.role')->lockForUpdate()->first();
            $step = $instance ? $this->workflows->currentStepForUser($instance, $actor) : null;
            if (! $instance || ! $step) throw new AuthorizationException(__('ramadan_iftars.change_requests.errors.unauthorized_review'));
            $this->workflows->recordDecision($instance, $step, $actor, $decision, $comment);
            $instance->refresh();

            if ($instance->status === DynamicWorkflowService::DECISION_REJECTED) {
                $request->update(['status' => RamadanIftarChangeRequest::STATUS_REJECTED, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_comment' => $comment]);
                $this->audit($source, $actor, 'change_request_rejected', $request, $comment);
            } elseif ($instance->status === DynamicWorkflowService::DECISION_APPROVED) {
                $revision = $this->copyPlanning($source, $actor);
                $request->update(['status' => RamadanIftarChangeRequest::STATUS_APPROVED, 'reviewed_by' => $actor->id, 'reviewed_at' => now(), 'review_comment' => $comment, 'created_version_id' => $revision->id]);
                $this->audit($source, $actor, 'change_request_approved', $request, $comment, $revision->id);
            }

            return $request->fresh(['createdVersion', 'workflowInstance.currentStep.role']);
        });
    }

    private function copyPlanning(RamadanIftar $source, User $actor): RamadanIftar
    {
        if ($source->versions()->exists()) $this->invalid('version', 'revision_exists');
        $source->load(['attendees', 'targetGroupSelections', 'meals.items', 'gifts', 'programSegments', 'executionTeams.members', 'volunteerRequirements', 'supplies', 'executionNeeds']);
        $revision = RamadanIftar::query()->create(array_merge(Arr::only($source->getAttributes(), self::CORE_FIELDS), [
            'created_by' => $actor->id, 'parent_version_id' => $source->id,
            'version_number' => $source->version_number + 1, 'status' => RamadanIftar::STATUS_DRAFT,
            'execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED,
            'actual_date' => null, 'actual_attendance' => null, 'actual_meals_count' => null,
            'submitted_at' => null, 'approved_at' => null, 'closed_at' => null,
        ]));

        foreach ($source->attendees as $row) $revision->attendees()->create(Arr::only($row->getAttributes(), ['full_name','phone','age','target_group_id','beneficiary_segment_id','notes']));
        foreach ($source->targetGroupSelections as $row) $revision->targetGroupSelections()->create(array_merge(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR], Arr::only($row->getAttributes(), ['target_group_id','target_group_custom_text','beneficiary_segment_id','segment_custom_text','planned_count','notes'])));
        foreach ($source->meals as $row) {
            $copy = $revision->meals()->create(Arr::only($row->getAttributes(), ['description','planned_quantity','source_type','source_name','restaurant_name','restaurant_contact','estimated_value']));
            foreach ($row->items as $item) $copy->items()->create(Arr::only($item->getAttributes(), ['name','item_type','quantity','notes','sort_order']));
        }
        foreach ($source->gifts as $row) $revision->gifts()->create(Arr::only($row->getAttributes(), ['gift_type','description','planned_quantity','has_supporting_entity','supporting_entity_name','unit_value','estimated_total_value']));
        foreach ($source->programSegments as $row) $revision->programSegments()->create(array_merge(Arr::only($row->getAttributes(), ['name','starts_at','ends_at','duration_minutes','sort_order','executor_user_id','external_executor_name']), ['execution_status' => RamadanIftarProgramSegment::STATUS_PLANNED]));
        foreach ($source->executionTeams as $row) {
            $copy = $revision->executionTeams()->create(array_merge(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR], Arr::only($row->getAttributes(), ['name','leader_user_id','planned_members_count','notes'])));
            foreach ($row->members as $member) $copy->members()->create(Arr::only($member->getAttributes(), ['user_id','member_name','phone','role_name','task_description']));
        }
        foreach ($source->volunteerRequirements as $row) $revision->volunteerRequirements()->create(array_merge(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR], Arr::only($row->getAttributes(), ['beneficiary_segment_id','gender','planned_count','tasks_summary']), ['status' => SubjectVolunteerRequirement::STATUS_PENDING]));
        foreach ($source->supplies as $row) $revision->supplies()->create(array_merge(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR], Arr::only($row->getAttributes(), ['item_name','planned_quantity','planned_available','provider_type','provider_name','estimated_value','notes']), ['status' => EventSupply::STATUS_PENDING]));
        foreach ($source->executionNeeds as $row) $revision->executionNeeds()->create(array_merge(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR], Arr::only($row->getAttributes(), ['execution_need_type_id','is_required','planned_details']), ['status' => SubjectExecutionNeed::STATUS_PENDING]));

        $this->audit($source, $actor, 'revision_created', null, null, $revision->id);
        return $revision;
    }

    private function assertEligibleSource(RamadanIftar $source): void
    {
        if ($source->status !== RamadanIftar::STATUS_APPROVED || $source->execution_status !== RamadanIftar::EXECUTION_STATUS_PLANNED || $source->closed_at !== null) $this->invalid('status', 'ineligible');
        if ($source->versions()->exists()) $this->invalid('version', 'revision_exists');
    }

    private function authorizeRequester(RamadanIftar $source, User $actor): void
    {
        if (! $actor->hasRole('super_admin') && (
            ! $actor->hasRole('relations_officer')
            || ! $actor->can('ramadan_iftars.change_request.create')
            || (! $actor->can('branches.view.all') && ! $actor->hasAccessToScopedBranch((int) $source->branch_id))
        )) {
            throw new AuthorizationException();
        }
    }

    private function authorizeReviewer(RamadanIftar $source, RamadanIftarChangeRequest $request, User $actor): void
    {
        if (! $actor->hasRole('super_admin') && (! $actor->can('ramadan_iftars.change_request.review') || (! $actor->can('branches.view.all') && ! $actor->hasAccessToScopedBranch((int) $source->branch_id)))) throw new AuthorizationException();
        if (! $actor->hasRole('super_admin') && $request->requested_by === $actor->id) throw new AuthorizationException(__('ramadan_iftars.change_requests.errors.self_review'));
    }

    private function invalid(string $field, string $key): void { throw ValidationException::withMessages([$field => __('ramadan_iftars.change_requests.errors.'.$key)]); }
    private function audit(RamadanIftar $source, User $actor, string $action, ?RamadanIftarChangeRequest $request, ?string $notes = null, ?int $revisionId = null): void
    {
        WorkflowActionLog::query()->create(['module' => RamadanIftar::WORKFLOW_MODULE, 'entity_type' => RamadanIftar::class, 'entity_id' => $source->id, 'action_type' => $action, 'status' => $request?->status, 'performed_by' => $actor->id, 'notes' => $notes, 'meta' => ['change_request_id' => $request?->id, 'revision_id' => $revisionId], 'performed_at' => now()]);
    }
}
