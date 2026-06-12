<?php

namespace App\Services;

use App\Models\AgendaEvent;
use App\Models\AnnualAgendaDeleteRequest;
use App\Models\AnnualAgendaEditRequest;
use App\Models\MonthlyActivity;
use App\Models\MonthlyPlanDeleteRequest;
use App\Models\MonthlyPlanEditRequest;
use App\Models\User;
use App\Models\WorkflowActionLog;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PlanChangeRequestWorkflowService
{
    public function __construct(
        protected DynamicWorkflowService $workflows,
        protected WorkflowNotificationService $notifications,
    ) {}

    public function startMonthlyDeleteRequest(MonthlyActivity $activity, User $requester, string $reason): MonthlyPlanDeleteRequest
    {
        return DB::transaction(function () use ($activity, $requester, $reason) {
            $request = MonthlyPlanDeleteRequest::create([
                'requester_id' => $requester->id,
                'request_type' => 'delete',
                'entity_type' => MonthlyActivity::class,
                'entity_id' => $activity->id,
                'branch_id' => $activity->branch_id,
                'reason' => $reason,
                'status' => 'pending',
                'approval_history' => [],
                'requested_at' => now(),
            ]);

            $this->startWorkflow('monthly_activities', $request, route('role.programs.approvals.index', ['tab' => 'delete']), $requester);
            $this->log('monthly_activities', $activity, 'delete_request_created', 'pending', $requester, ['request_id' => $request->id, 'reason' => $reason]);

            return $request;
        });
    }

    public function startMonthlyEditRequest(MonthlyActivity $activity, User $requester, array $oldValues, array $newValues, array $changedValues, ?string $reason = null): MonthlyPlanEditRequest
    {
        return DB::transaction(function () use ($activity, $requester, $oldValues, $newValues, $changedValues, $reason) {
            $request = MonthlyPlanEditRequest::create([
                'requester_id' => $requester->id,
                'request_type' => 'edit',
                'entity_type' => MonthlyActivity::class,
                'entity_id' => $activity->id,
                'branch_id' => $activity->branch_id,
                'reason' => $reason,
                'status' => 'pending',
                'approval_history' => [],
                'old_values' => Arr::only($oldValues, array_keys($changedValues)),
                'new_values' => $newValues,
                'changed_values' => $changedValues,
                'requested_at' => now(),
            ]);

            $this->startWorkflow('monthly_activities', $request, route('role.programs.approvals.index', ['tab' => 'edit']), $requester);
            $this->log('monthly_activities', $activity, 'edit_request_created', 'pending', $requester, ['request_id' => $request->id, 'changed_fields' => array_keys($changedValues)]);

            return $request;
        });
    }

    public function startAgendaDeleteRequest(AgendaEvent $event, User $requester, string $reason): AnnualAgendaDeleteRequest
    {
        return DB::transaction(function () use ($event, $requester, $reason) {
            $request = AnnualAgendaDeleteRequest::create([
                'requester_id' => $requester->id,
                'request_type' => 'delete',
                'entity_type' => AgendaEvent::class,
                'entity_id' => $event->id,
                'reason' => $reason,
                'status' => 'pending',
                'approval_history' => [],
                'requested_at' => now(),
            ]);

            $this->startWorkflow('agenda', $request, route('role.relations.approvals.index', ['tab' => 'delete']), $requester);
            $this->log('agenda', $event, 'delete_request_created', 'pending', $requester, ['request_id' => $request->id, 'reason' => $reason]);

            return $request;
        });
    }

    public function startAgendaEditRequest(AgendaEvent $event, User $requester, array $oldValues, array $newValues, array $changedValues, ?string $reason = null): AnnualAgendaEditRequest
    {
        return DB::transaction(function () use ($event, $requester, $oldValues, $newValues, $changedValues, $reason) {
            $request = AnnualAgendaEditRequest::create([
                'requester_id' => $requester->id,
                'request_type' => 'edit',
                'entity_type' => AgendaEvent::class,
                'entity_id' => $event->id,
                'reason' => $reason,
                'status' => 'pending',
                'approval_history' => [],
                'old_values' => Arr::only($oldValues, array_keys($changedValues)),
                'new_values' => $newValues,
                'changed_values' => $changedValues,
                'requested_at' => now(),
            ]);

            $this->startWorkflow('agenda', $request, route('role.relations.approvals.index', ['tab' => 'edit']), $requester);
            $this->log('agenda', $event, 'edit_request_created', 'pending', $requester, ['request_id' => $request->id, 'changed_fields' => array_keys($changedValues)]);

            return $request;
        });
    }


    public function normalizeRequesterSubmission(Model $request, string $module): ?WorkflowInstance
    {
        $instance = $this->workflows->forModel($module, $request);

        if (! $instance) {
            return null;
        }

        $this->advanceRequesterSubmissionSteps($instance, $request->requester);
        $instance = $instance->fresh();
        $this->syncCurrentApprover($request, $instance);

        return $instance;
    }

    public function decide(Model $request, string $module, User $actor, string $decision, ?string $comment = null): void
    {
        DB::transaction(function () use ($request, $module, $actor, $decision, $comment) {
            $instance = $this->normalizeRequesterSubmission($request, $module);
            abort_unless($instance !== null, 422);
            abort_if(! $this->workflows->canDecide($instance), 422);
            $step = $this->workflows->currentStepForUser($instance, $actor);
            abort_unless($step, 403);
            $this->workflows->assertPrerequisites($instance, $step);

            $this->workflows->recordDecision($instance, $step, $actor, $decision, $comment);
            $instance = $instance->fresh();
            $history = $request->approval_history ?? [];
            $history[] = [
                'step' => $step->step_key,
                'decision' => $decision,
                'comment' => $comment,
                'approver_id' => $actor->id,
                'approver_name' => $actor->name,
                'decided_at' => now()->toDateTimeString(),
            ];

            $request->forceFill([
                'status' => $instance->status,
                'current_approver_id' => null,
                'approval_history' => $history,
                'decided_at' => in_array($instance->status, ['approved', 'rejected'], true) ? now() : null,
            ])->save();
            if (! in_array($instance->status, ['approved', 'rejected'], true)) {
                $this->syncCurrentApprover($request, $instance);
            }

            if ($instance->status === 'approved') {
                $this->applyApprovedRequest($request, $actor);
            }

            $entity = $this->underlyingEntity($request);
            if ($entity) {
                $this->notifications->approvalDecision($instance, $entity, $actor, $decision, $module === 'agenda' ? route('role.relations.approvals.index') : route('role.programs.approvals.index'));
            }
        });
    }

    protected function startWorkflow(string $module, Model $request, string $url, User $requester): void
    {
        $instance = $this->workflows->forModel($module, $request);
        abort_unless($instance !== null, 422, 'لا يوجد مسار اعتماد نشط.');
        $this->advanceRequesterSubmissionSteps($instance, $requester);
        $instance = $instance->fresh();
        $this->syncCurrentApprover($request, $instance);
        $entity = $this->underlyingEntity($request);
        if ($entity) {
            $this->notifications->approvalRequested($instance, $entity, $url);
        }
    }


    protected function advanceRequesterSubmissionSteps(WorkflowInstance $instance, ?User $requester): void
    {
        if (! $requester || $requester->hasRole('super_admin')) {
            return;
        }

        $processedStepIds = [];

        while ($this->workflows->canDecide($instance)) {
            $step = $this->workflows->currentStep($instance);

            if (! $step || ! $step->role || ! $requester->hasRole($step->role->name)) {
                return;
            }

            if (in_array((int) $step->id, $processedStepIds, true)) {
                return;
            }

            if (! $this->workflows->currentStepForUser($instance, $requester)) {
                return;
            }

            $processedStepIds[] = (int) $step->id;
            $this->workflows->recordDecision($instance, $step, $requester, DynamicWorkflowService::DECISION_APPROVED, 'تم إرسال طلب التغيير للاعتماد.');
            $instance = $instance->fresh();
        }
    }

    protected function syncCurrentApprover(Model $request, ?WorkflowInstance $instance): void
    {
        if (! $instance?->currentStep) {
            return;
        }

        $approver = $this->workflows->eligibleUsersForStep($instance, $instance->currentStep)->first();
        $request->forceFill(['current_approver_id' => $approver?->id])->save();
    }

    protected function applyApprovedRequest(Model $request, User $actor): void
    {
        if ($request instanceof MonthlyPlanDeleteRequest) {
            $entity = $request->monthlyActivity()->first();
            $entity?->delete();
            return;
        }

        if ($request instanceof AnnualAgendaDeleteRequest) {
            $entity = $request->agendaEvent()->first();
            $entity?->delete();
            return;
        }

        if ($request instanceof MonthlyPlanEditRequest) {
            $source = $request->monthlyActivity()->firstOrFail();
            $values = array_merge($source->getAttributes(), $request->new_values ?? []);
            unset($values['id'], $values['created_at'], $values['updated_at'], $values['deleted_at']);
            $values['status'] = 'approved';
            $values['lifecycle_status'] = 'Exec Director Approved';
            $values['is_archived'] = false;
            $values['plan_version'] = ((int) ($source->plan_version ?? $source->version_number ?? 1)) + 1;
            $values['version_number'] = ((int) ($source->version_number ?? $source->plan_version ?? 1)) + 1;
            $values['previous_version_id'] = $source->id;
            $values['parent_version_id'] = $source->id;
            $version = MonthlyActivity::create(Arr::only($values, (new MonthlyActivity())->getFillable()));
            $source->forceFill(['status' => 'archived', 'lifecycle_status' => 'Closed', 'is_archived' => true])->save();
            $request->forceFill(['approved_version_id' => $version->id])->save();
            return;
        }

        if ($request instanceof AnnualAgendaEditRequest) {
            $source = $request->agendaEvent()->firstOrFail();
            $values = array_merge($source->getAttributes(), $request->new_values ?? []);
            unset($values['id'], $values['created_at'], $values['updated_at'], $values['deleted_at']);
            $values['status'] = 'published';
            $values['is_archived'] = false;
            $values['version'] = ((int) ($source->version ?? 1)) + 1;
            $values['version_number'] = ((int) ($source->version_number ?? $source->version ?? 1)) + 1;
            $values['parent_version_id'] = $source->id;
            $version = AgendaEvent::create(Arr::only($values, (new AgendaEvent())->getFillable()));
            $source->forceFill(['status' => 'archived', 'is_archived' => true])->save();
            $request->forceFill(['approved_version_id' => $version->id])->save();
        }
    }

    protected function underlyingEntity(Model $request): ?Model
    {
        return match (true) {
            $request instanceof MonthlyPlanDeleteRequest => $request->monthlyActivity()->first(),
            $request instanceof MonthlyPlanEditRequest => $request->monthlyActivity()->first(),
            $request instanceof AnnualAgendaDeleteRequest => $request->agendaEvent()->first(),
            $request instanceof AnnualAgendaEditRequest => $request->agendaEvent()->first(),
            default => null,
        };
    }

    protected function log(string $module, Model $entity, string $action, string $status, User $actor, array $meta = []): void
    {
        WorkflowActionLog::create([
            'module' => $module,
            'entity_type' => $entity::class,
            'entity_id' => $entity->getKey(),
            'action_type' => $action,
            'status' => $status,
            'performed_by' => $actor->id,
            'meta' => $meta,
            'performed_at' => now(),
        ]);
    }
}
