<?php

namespace App\Http\Controllers\Roles\Relations;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\AgendaEventTarget;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AgendaEventsController extends Controller
{
    public function index()
    {
        $events = AgendaEvent::with(['targets', 'creator'])->orderBy('month')->orderBy('day')->get();

        return view('roles.relations.agenda.index', compact('events'));
    }

    public function create()
    {
        return view('roles.relations.agenda.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'event_category' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'target_type' => ['nullable', 'string', 'max:50'],
            'target_id' => ['nullable', 'integer'],
            'is_participant' => ['nullable', 'boolean'],
        ]);

        $date = Carbon::parse($data['event_date']);

        $event = AgendaEvent::create([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'event_name' => $data['event_name'],
            'event_category' => $data['event_category'] ?? null,
            'status' => 'draft',
            'created_by' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
        ]);

        if (!empty($data['target_type']) && !empty($data['target_id'])) {
            AgendaEventTarget::create([
                'agenda_event_id' => $event->id,
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
                'is_participant' => (bool) ($data['is_participant'] ?? false),
            ]);
        }

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.created'));
    }

    public function edit(AgendaEvent $agendaEvent)
    {
        $agendaEvent->load('targets');

        return view('roles.relations.agenda.edit', compact('agendaEvent'));
    }

    public function update(Request $request, AgendaEvent $agendaEvent)
    {
        $data = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'event_category' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'target_type' => ['nullable', 'string', 'max:50'],
            'target_id' => ['nullable', 'integer'],
            'is_participant' => ['nullable', 'boolean'],
        ]);

        $date = Carbon::parse($data['event_date']);

        $agendaEvent->update([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'event_name' => $data['event_name'],
            'event_category' => $data['event_category'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        if (!empty($data['target_type']) && !empty($data['target_id'])) {
            $agendaEvent->targets()->delete();
            AgendaEventTarget::create([
                'agenda_event_id' => $agendaEvent->id,
                'target_type' => $data['target_type'],
                'target_id' => $data['target_id'],
                'is_participant' => (bool) ($data['is_participant'] ?? false),
            ]);
        }

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.updated', ['event' => $agendaEvent->event_name]));
    }

    public function submit(Request $request, AgendaEvent $agendaEvent)
    {
        $agendaEvent->update([
            'status' => 'submitted',
        ]);

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.submitted', ['event' => $agendaEvent->event_name]));
    }
}
