<?php

namespace App\Http\Controllers\Web\Agenda;

use App\Http\Controllers\Controller;
use App\Models\AgendaApproval;
use App\Models\AgendaEvent;
use App\Models\WorkflowActionLog;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class AgendaApprovalsController extends Controller
{
    public function index()
    {
        $events = AgendaEvent::with(['approvals', 'creator'])
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        return view('pages.agenda.approvals.index', compact('events'));
    }

    public function update(Request $request, NotificationService $notifications, AgendaEvent $agendaEvent)
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,changes_requested'],
            'comment' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $step = $user->hasRole('executive_manager') ? 'executive_review' : 'relations_review';

        if ($step === 'relations_review' && ! $user->hasRole('relations_manager')) {
            abort(403);
        }

        if ($step === 'executive_review' && ! $user->hasRole('executive_manager')) {
            abort(403);
        }

        if ((int) $agendaEvent->created_by === (int) $user->id) {
            return back()->withErrors(['decision' => __('app.roles.relations.approvals.errors.self_approval_forbidden')]);
        }

        if ($step === 'executive_review' && $agendaEvent->relations_approval_status !== 'approved') {
            return back()->withErrors(['decision' => __('app.roles.relations.approvals.errors.executive_before_relations')]);
        }

        if ($step === 'relations_review' && ! in_array($agendaEvent->status, ['submitted', 'changes_requested'], true)) {
            return back()->withErrors(['decision' => __('app.roles.relations.approvals.errors.invalid_state')]);
        }

        if ($step === 'executive_review' && $agendaEvent->status !== 'relations_approved') {
            return back()->withErrors(['decision' => __('app.roles.relations.approvals.errors.executive_requires_relations_completion')]);
        }

        AgendaApproval::create([
            'agenda_event_id' => $agendaEvent->id,
            'step' => $step,
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);

        if ($step === 'relations_review') {
            $agendaEvent->update([
                'status' => $data['decision'] === 'approved' ? 'relations_approved' : 'changes_requested',
                'relations_approval_status' => $data['decision'],
                'executive_approval_status' => 'pending',
                'approved_by_relations_at' => $data['decision'] === 'approved' ? now() : null,
                'approved_by_executive_at' => null,
            ]);
        }

        if ($step === 'executive_review') {
            $agendaEvent->update([
                'status' => $data['decision'] === 'approved' ? 'published' : 'changes_requested',
                'executive_approval_status' => $data['decision'],
                'approved_by_executive_at' => $data['decision'] === 'approved' ? now() : null,
            ]);
        }


        $notifications->notifyUsers(collect([$agendaEvent->creator])->filter(), 'approval_decision', 'Approval update', 'Decision: '. $data['decision'], route('role.relations.approvals.index'));

        WorkflowActionLog::create([
            'module' => 'agenda',
            'entity_type' => AgendaEvent::class,
            'entity_id' => $agendaEvent->id,
            'action_type' => 'approval_decision',
            'status' => $data['decision'],
            'performed_by' => $user->id,
            'meta' => ['step' => $step],
            'performed_at' => now(),
        ]);

        return redirect()
            ->route('role.relations.approvals.index')
            ->with('status', __('app.roles.relations.approvals.updated', ['event' => $agendaEvent->event_name]));
    }
}
