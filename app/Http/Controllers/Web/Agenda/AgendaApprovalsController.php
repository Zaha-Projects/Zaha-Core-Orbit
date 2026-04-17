<?php

namespace App\Http\Controllers\Web\Agenda;

use App\Http\Controllers\Controller;
use App\Models\AgendaApproval;
use App\Models\AgendaEvent;
use App\Models\WorkflowActionLog;
use App\Services\AgendaWorkflowPresenter;
use App\Services\AgendaWorkflowBridgeService;
use App\Services\DynamicWorkflowService;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class AgendaApprovalsController extends Controller
{
    public function index(Request $request, DynamicWorkflowService $dynamicWorkflowService, AgendaWorkflowPresenter $agendaWorkflowPresenter)
    {
        $viewer = $request->user();
        abort_unless(
            $dynamicWorkflowService->userMayParticipateInWorkflow('agenda', $viewer) || $viewer->can('agenda.approve'),
            403
        );

        $events = AgendaEvent::query()
            ->with([
                'approvals.approver',
                'creator',
                'ownerDepartment',
                'workflowInstance.currentStep.role',
                'workflowInstance.logs.step.role',
                'workflowInstance.logs.actor',
            ])
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $events->transform(function (AgendaEvent $event) use ($dynamicWorkflowService) {
            $instance = $dynamicWorkflowService->forModel('agenda', $event);
            $event->setRelation('workflowInstance', $instance);

            return $event;
        });

        $events->load('workflowInstance.currentStep.role', 'workflowInstance.logs.step.role', 'workflowInstance.logs.actor');

        $events->transform(function (AgendaEvent $event) use ($dynamicWorkflowService, $viewer) {
            $instance = $event->workflowInstance;
            $currentStep = $instance ? $dynamicWorkflowService->currentStep($instance) : null;

            $event->setAttribute('workflow_state', $instance?->status ?? 'pending');
            $event->setAttribute('current_step_label', $currentStep?->name_ar ?: ($currentStep?->name_en ?: __('app.common.na')));
            $event->setAttribute('current_role_label', $currentStep?->role?->display_name ?: ($currentStep?->role?->name ?: __('app.common.na')));
            $event->setAttribute(
                'can_current_user_decide',
                $instance
                    && $dynamicWorkflowService->canDecide($instance)
                    && $dynamicWorkflowService->currentStepForUser($instance, $viewer) !== null
            );

            return $event;
        });

        $events->transform(function (AgendaEvent $event) use ($agendaWorkflowPresenter, $viewer) {
            return $agendaWorkflowPresenter->attach($event, $viewer);
        });

        return view('pages.agenda.approvals.index', compact('events'));
    }

    public function update(
        Request $request,
        NotificationService $notifications,
        AgendaEvent $agendaEvent,
        DynamicWorkflowService $dynamicWorkflowService,
        AgendaWorkflowBridgeService $agendaWorkflowBridgeService
    ) {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,changes_requested,rejected'],
            'comment' => ['nullable', 'string', 'required_if:decision,changes_requested,rejected'],
        ]);

        $user = $request->user();
        abort_unless(
            $dynamicWorkflowService->userMayParticipateInWorkflow('agenda', $user) || $user->can('agenda.approve'),
            403
        );

        $instance = $dynamicWorkflowService->forModel('agenda', $agendaEvent);
        abort_unless($instance !== null, 422, __('app.roles.programs.monthly_activities.approvals.errors.no_active_workflow'));
        abort_if(! $dynamicWorkflowService->canDecide($instance), 422, __('app.roles.programs.monthly_activities.approvals.errors.not_available_for_current_state'));

        $step = $dynamicWorkflowService->currentStepForUser($instance, $user);
        abort_if(! $step, 403, __('app.roles.programs.monthly_activities.approvals.errors.not_assigned_to_current_step'));
        abort_if((int) $agendaEvent->created_by === (int) $user->id, 422, __('app.roles.relations.approvals.errors.self_approval_forbidden'));

        $dynamicWorkflowService->assertPrerequisites($instance, $step);

        AgendaApproval::create([
            'agenda_event_id' => $agendaEvent->id,
            'step' => $step->step_key,
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        $dynamicWorkflowService->recordDecision($instance, $step, $user, $data['decision'], $data['comment'] ?? null);
        $instance = $instance->fresh();
        $agendaEvent = $agendaWorkflowBridgeService->syncApprovalState($agendaEvent, $instance);

        $nextRecipients = $dynamicWorkflowService->eligibleUsersForStep($instance);
        $notifications->notifyUsers(
            collect([$agendaEvent->creator])->filter()->merge($nextRecipients)->unique('id'),
            'approval_decision',
            'Approval update',
            'Decision: ' . $data['decision'],
            route('role.relations.approvals.index')
        );

        WorkflowActionLog::create([
            'module' => 'agenda',
            'entity_type' => AgendaEvent::class,
            'entity_id' => $agendaEvent->id,
            'action_type' => 'approval_decision',
            'status' => $data['decision'],
            'performed_by' => $user->id,
            'meta' => [
                'step' => $step->step_key,
                'comment' => $data['comment'] ?? null,
                'iteration' => $instance->edit_request_count,
                'new_status' => $instance->status,
            ],
            'performed_at' => now(),
        ]);

        return redirect()
            ->route('role.relations.approvals.index')
            ->with('status', __('app.roles.relations.approvals.updated', ['event' => $agendaEvent->event_name]));
    }
}
