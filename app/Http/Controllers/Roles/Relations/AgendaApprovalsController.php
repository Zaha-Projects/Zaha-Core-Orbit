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

        AgendaApproval::create([
            'agenda_event_id' => $agendaEvent->id,
            'step' => 'relations_review',
            'decision' => $data['decision'],
            'comment' => $data['comment'] ?? null,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $agendaEvent->update([
            'status' => $data['decision'] === 'approved' ? 'relations_approved' : 'changes_requested',
            'approved_by_relations_at' => $data['decision'] === 'approved' ? now() : null,
        ]);

        return redirect()
            ->route('role.relations.approvals.index')
            ->with('status', __('app.roles.relations.approvals.updated', ['event' => $agendaEvent->event_name]));
    }
}
