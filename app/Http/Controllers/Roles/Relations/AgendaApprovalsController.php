<?php

namespace App\Http\Controllers\Roles\Relations;

use App\Http\Controllers\Controller;
use App\Models\AgendaApproval;
use App\Models\AgendaEvent;
use Illuminate\Http\Request;

class AgendaApprovalsController extends Controller
{
    public function index()
    {
        $events = AgendaEvent::with(['approvals', 'creator'])
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        return view('roles.relations.agenda.approvals', compact('events'));
    }

    public function update(Request $request, AgendaEvent $agendaEvent)
    {
        $data = $request->validate([
            'decision' => ['required', 'string', 'in:approved,changes_requested'],
            'comment' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $step = $user->hasRole('executive_manager') ? 'executive_review' : 'relations_review';

        if ((int) $agendaEvent->created_by === (int) $user->id) {
            return back()->withErrors(['decision' => 'لا يمكن لمنشئ الفعالية اعتمادها بنفسه.']);
        }

        if ($step === 'executive_review' && $agendaEvent->relations_approval_status !== 'approved') {
            return back()->withErrors(['decision' => 'لا يمكن اعتماد المدير التنفيذي قبل اعتماد العلاقات.']);
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

        return redirect()
            ->route('role.relations.approvals.index')
            ->with('status', __('app.roles.relations.approvals.updated', ['event' => $agendaEvent->event_name]));
    }
}
