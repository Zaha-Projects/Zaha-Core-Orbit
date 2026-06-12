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
use Illuminate\Validation\ValidationException;

class PlanChangeRequestWorkflowService
{
    public function __construct(
        protected DynamicWorkflowService $workflows,
        protected NotificationService $directNotifications,
    ) {}

    public function startMonthlyDeleteRequest(MonthlyActivity $activity, User $requester, string $reason): MonthlyPlanDeleteRequest
    {
        return DB::transaction(function () use ($activity, $requester, $reason) {
            $this->assertNoActiveMonthlyChangeRequest($activity, 'delete');

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
            $this->assertNoActiveMonthlyChangeRequest($activity, 'edit');

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
            $this->assertNoActiveAgendaChangeRequest($event, 'delete');

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
            $this->assertNoActiveAgendaChangeRequest($event, 'edit');

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




    /**
     * @return array<int, string>
     */
    public function activeRequestStatuses(): array
    {
        return ['pending', 'pending_approval', 'in_progress', 'waiting_approval', 'waiting', 'changes_requested'];
    }


    /**
     * @return array{delete: ?MonthlyPlanDeleteRequest, edit: ?MonthlyPlanEditRequest}
     */
    public function activeMonthlyChangeRequests(MonthlyActivity $activity): array
    {
        $statuses = $this->activeRequestStatuses();

        return [
            'delete' => MonthlyPlanDeleteRequest::query()
                ->with(['requester', 'currentApprover', 'workflowInstance.currentStep.role', 'workflowInstance.logs.step.role', 'workflowInstance.logs.actor'])
                ->where('entity_id', $activity->id)
                ->whereIn('status', $statuses)
                ->latest()
                ->first(),
            'edit' => MonthlyPlanEditRequest::query()
                ->with(['requester', 'currentApprover', 'workflowInstance.currentStep.role', 'workflowInstance.logs.step.role', 'workflowInstance.logs.actor'])
                ->where('entity_id', $activity->id)
                ->whereIn('status', $statuses)
                ->latest()
                ->first(),
        ];
    }

    public function hasActiveMonthlyChangeRequest(MonthlyActivity $activity): bool
    {
        $active = $this->activeMonthlyChangeRequests($activity);

        return $active['delete'] !== null || $active['edit'] !== null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function workflowTimelineForRequest(Model $request, ?WorkflowInstance $instance = null): array
    {
        $instance = $instance ?: $request->workflowInstance;
        if (! $instance) {
            return [];
        }

        $instance->loadMissing('workflow.steps.role', 'logs.step.role', 'logs.actor', 'currentStep.role');
        $logsByStep = $instance->logs->keyBy('workflow_step_id');
        $applicableStepKeys = $this->applicableMonthlyRequestStepKeys($request);

        return $instance->workflow->steps
            ->filter(fn ($step): bool => $applicableStepKeys === [] || in_array((string) $step->step_key, $applicableStepKeys, true))
            ->map(function ($step) use ($logsByStep, $instance): array {
                $log = $logsByStep->get($step->id);
                $isCurrent = (int) $instance->current_step_id === (int) $step->id;

                return [
                    'step_name' => $step->name_ar ?: ($step->name_en ?: $step->step_key),
                    'role_name' => $step->role?->display_name ?? $step->role?->name ?? '-',
                    'approver_name' => $log?->actor?->name,
                    'status' => $log?->action ?: ($isCurrent ? 'pending' : 'waiting'),
                    'decided_at' => optional($log?->acted_at)->format('Y-m-d H:i'),
                    'comment' => $log?->comment,
                    'is_current' => $isCurrent,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function applicableMonthlyRequestStepKeys(Model $request): array
    {
        if (! $request instanceof MonthlyPlanDeleteRequest && ! $request instanceof MonthlyPlanEditRequest) {
            return [];
        }

        $activity = MonthlyActivity::query()->find($request->entity_id);
        if (! $activity) {
            return [];
        }

        $activityWorkflowInstance = WorkflowInstance::query()
            ->where('entity_type', MonthlyActivity::class)
            ->where('entity_id', $activity->id)
            ->latest('id')
            ->first();
        $keys = $activityWorkflowInstance
            ? $activityWorkflowInstance->logs()
                ->where('action', DynamicWorkflowService::DECISION_APPROVED)
                ->whereHas('step')
                ->with('step')
                ->get()
                ->map(fn ($log): string => (string) $log->step?->step_key)
                ->filter()
                ->values()
                ->all()
            : [];

        if ((string) $activity->relations_officer_approval_status === DynamicWorkflowService::DECISION_APPROVED) {
            $keys[] = 'monthly_relations_officer_submit';
        }
        if ((string) $activity->relations_manager_approval_status === DynamicWorkflowService::DECISION_APPROVED) {
            $keys[] = 'monthly_supervisor_review';
        }
        if ((string) $activity->liaison_approval_status === DynamicWorkflowService::DECISION_APPROVED) {
            $keys[] = 'monthly_branch_coordinator_review';
        }
        if ((string) $activity->hq_relations_manager_approval_status === DynamicWorkflowService::DECISION_APPROVED) {
            $keys[] = 'monthly_relations_manager_review';
        }
        if ((string) $activity->executive_approval_status === DynamicWorkflowService::DECISION_APPROVED) {
            $keys[] = 'monthly_executive_manager_final_approval';
        }

        return array_values(array_unique($keys));
    }

    protected function assertNoActiveMonthlyChangeRequest(MonthlyActivity $activity, string $requestType): void
    {
        $activeStatuses = $this->activeRequestStatuses();
        $activeDelete = MonthlyPlanDeleteRequest::query()
            ->where('entity_id', $activity->id)
            ->whereIn('status', $activeStatuses)
            ->exists();
        $activeEdit = MonthlyPlanEditRequest::query()
            ->where('entity_id', $activity->id)
            ->whereIn('status', $activeStatuses)
            ->exists();

        if ($requestType === 'delete' && $activeDelete) {
            throw ValidationException::withMessages(['delete_reason' => 'يوجد طلب حذف نشط لهذه الخطة الشهرية ولا يمكن إنشاء طلب حذف آخر.']);
        }
        if ($requestType === 'edit' && $activeEdit) {
            throw ValidationException::withMessages(['edit_reason' => 'يوجد طلب تعديل نشط لهذه الخطة الشهرية ولا يمكن إنشاء طلب تعديل آخر.']);
        }
        if ($requestType === 'delete' && $activeEdit) {
            throw ValidationException::withMessages(['delete_reason' => 'لا يمكن إنشاء طلب حذف أثناء وجود طلب تعديل نشط لهذه الخطة الشهرية.']);
        }
        if ($requestType === 'edit' && $activeDelete) {
            throw ValidationException::withMessages(['edit_reason' => 'لا يمكن إنشاء طلب تعديل أثناء وجود طلب حذف نشط لهذه الخطة الشهرية.']);
        }
    }

    protected function assertNoActiveAgendaChangeRequest(AgendaEvent $event, string $requestType): void
    {
        $activeStatuses = $this->activeRequestStatuses();
        $activeDelete = AnnualAgendaDeleteRequest::query()
            ->where('entity_id', $event->id)
            ->whereIn('status', $activeStatuses)
            ->exists();
        $activeEdit = AnnualAgendaEditRequest::query()
            ->where('entity_id', $event->id)
            ->whereIn('status', $activeStatuses)
            ->exists();

        if ($requestType === 'delete' && $activeDelete) {
            throw ValidationException::withMessages(['delete_reason' => 'يوجد طلب حذف نشط لهذه الأجندة ولا يمكن إنشاء طلب حذف آخر.']);
        }
        if ($requestType === 'edit' && $activeEdit) {
            throw ValidationException::withMessages(['edit_reason' => 'يوجد طلب تعديل نشط لهذه الأجندة ولا يمكن إنشاء طلب تعديل آخر.']);
        }
        if ($requestType === 'delete' && $activeEdit) {
            throw ValidationException::withMessages(['delete_reason' => 'لا يمكن إنشاء طلب حذف أثناء وجود طلب تعديل نشط لهذه الأجندة.']);
        }
        if ($requestType === 'edit' && $activeDelete) {
            throw ValidationException::withMessages(['edit_reason' => 'لا يمكن إنشاء طلب تعديل أثناء وجود طلب حذف نشط لهذه الأجندة.']);
        }
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

        return $instance->fresh(['currentStep.role', 'currentStep.permission', 'workflow.steps.role', 'workflow.steps.permission', 'logs.step.role', 'logs.actor']);
    }

    public function decide(Model $request, string $module, User $actor, string $decision, ?string $comment = null): void
    {
        DB::transaction(function () use ($request, $module, $actor, $decision, $comment) {
            $instance = $this->normalizeRequesterSubmission($request, $module);
            abort_unless($instance !== null, 422);
            abort_if(! $this->workflows->canDecide($instance), 422);
            $step = $this->workflows->currentStepForUser($instance, $actor);
            if (! $step && (int) ($request->current_approver_id ?? 0) === (int) $actor->id) {
                $step = $this->workflows->currentStep($instance);
            }
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

            $this->notifyDecisionResult($request, $instance, $actor, $decision, $module === 'agenda' ? route('role.relations.approvals.index') : route('role.programs.approvals.index'));
        });
    }

    protected function startWorkflow(string $module, Model $request, string $url, User $requester): void
    {
        $instance = $this->workflows->forModel($module, $request);
        abort_unless($instance !== null, 422, 'لا يوجد مسار اعتماد نشط.');
        $this->advanceRequesterSubmissionSteps($instance, $requester);
        $instance = $instance->fresh();
        $this->syncCurrentApprover($request, $instance);
        $this->notifyCurrentApprover($request, $instance, $url);
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
            $request->forceFill(['current_approver_id' => null])->save();
            return;
        }

        $approver = $this->workflows->eligibleUsersForStep($instance, $instance->currentStep)->first();
        $request->forceFill(['current_approver_id' => $approver?->id])->save();
    }

    protected function applyApprovedRequest(Model $request, User $actor): void
    {
        if ($request instanceof MonthlyPlanDeleteRequest) {
            $entity = $request->monthlyActivity()->first();
            if ($entity) {
                $entity->forceFill([
                    'status' => 'cancelled',
                    'execution_status' => 'cancelled',
                    'cancellation_reason' => $request->reason,
                ])->save();
                $entity->delete();
            }
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


    protected function notifyCurrentApprover(Model $request, WorkflowInstance $instance, string $url): void
    {
        if ($instance->status === DynamicWorkflowService::DECISION_APPROVED) {
            $this->notifyRequester($request, 'تمت الموافقة على الطلب', $this->completionMessage($request), $url, 'plan_change_request_completed');
            return;
        }

        $this->directNotifications->notifyUsers(
            $this->workflows->eligibleUsersForStep($instance),
            'plan_change_request_pending',
            $this->requestTitle($request),
            $this->requestMessage($request),
            $url,
            $this->requestMeta($request)
        );
    }

    protected function notifyDecisionResult(Model $request, WorkflowInstance $instance, User $actor, string $decision, string $url): void
    {
        if ($instance->status === DynamicWorkflowService::DECISION_REJECTED || $decision === DynamicWorkflowService::DECISION_REJECTED) {
            $this->notifyRequester($request, 'تم رفض الطلب', 'تم رفض ' . $this->requestLabel($request) . ' بواسطة ' . $actor->name . '.', $url, 'plan_change_request_rejected');
            return;
        }

        if ($instance->status === DynamicWorkflowService::DECISION_APPROVED) {
            $this->notifyRequester($request, 'تمت الموافقة على الطلب', $this->completionMessage($request), $url, 'plan_change_request_completed');
            return;
        }

        $this->notifyRequester($request, 'تم اعتماد خطوة في الطلب', 'وافق ' . $actor->name . ' على ' . $this->requestLabel($request) . ' وتم تحويله للمعتمد التالي.', $url, 'plan_change_request_step_approved');
        $this->notifyCurrentApprover($request, $instance, $url);
    }

    protected function notifyRequester(Model $request, string $title, string $message, string $url, string $type): void
    {
        $request->loadMissing('requester');
        $this->directNotifications->notifyUsers(
            collect([$request->requester])->filter(),
            $type,
            $title,
            $message,
            $url,
            $this->requestMeta($request)
        );
    }

    protected function requestTitle(Model $request): string
    {
        return 'طلب بانتظار الاعتماد: ' . $this->requestLabel($request);
    }

    protected function requestMessage(Model $request): string
    {
        return $this->requestLabel($request) . ' بانتظار قرارك.';
    }

    protected function completionMessage(Model $request): string
    {
        return match (true) {
            $request instanceof MonthlyPlanDeleteRequest => 'تمت الموافقة على طلب الحذف وتم إلغاء/حذف الخطة الشهرية.',
            $request instanceof MonthlyPlanEditRequest => 'تمت الموافقة على طلب التعديل وتم تفعيل نسخة جديدة من الخطة الشهرية.',
            $request instanceof AnnualAgendaDeleteRequest => 'تمت الموافقة على طلب الحذف وتم حذف الأجندة.',
            $request instanceof AnnualAgendaEditRequest => 'تمت الموافقة على طلب التعديل وتم تفعيل نسخة جديدة من الأجندة.',
            default => 'تمت الموافقة على الطلب.',
        };
    }

    protected function requestLabel(Model $request): string
    {
        return match (true) {
            $request instanceof MonthlyPlanDeleteRequest => 'طلب حذف خطة شهرية',
            $request instanceof MonthlyPlanEditRequest => 'طلب تعديل خطة شهرية',
            $request instanceof AnnualAgendaDeleteRequest => 'طلب حذف أجندة',
            $request instanceof AnnualAgendaEditRequest => 'طلب تعديل أجندة',
            default => 'طلب تغيير',
        };
    }

    protected function requestMeta(Model $request): array
    {
        return [
            'request_type' => $request->request_type ?? null,
            'entity_type' => $request->entity_type ?? null,
            'entity_id' => $request->entity_id ?? null,
            'request_id' => $request->getKey(),
        ];
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
