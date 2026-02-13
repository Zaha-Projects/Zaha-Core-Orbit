<?php

namespace App\Http\Controllers\Roles\Relations;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EventCategory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgendaEventsController extends Controller
{
    protected function assertEventManageAccess(Request $request, AgendaEvent $agendaEvent): void
    {
        $user = $request->user();

        if ($user->hasRole('relations_manager')) {
            return;
        }

        abort_unless(
            $user->hasRole('relations_officer')
            && (int) $agendaEvent->created_by === (int) $user->id
            && in_array($agendaEvent->status, ['draft', 'changes_requested'], true),
            403
        );
    }

    public function index()
    {
        $events = AgendaEvent::with(['creator', 'department', 'eventCategory', 'participations'])
            ->orderBy('event_date')->orderBy('month')->orderBy('day')
            ->get();

        return view('roles.relations.agenda.index', compact('events'));
    }

    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $categories = EventCategory::where('active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('roles.relations.agenda.create', compact('departments', 'categories', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'event_category_id' => [
                'nullable',
                Rule::exists('event_categories', 'id')->where(function ($query) use ($request) {
                    $departmentId = (int) $request->input('department_id');

                    if ($departmentId > 0) {
                        $query->where('department_id', $departmentId);
                    }
                }),
            ],
            'event_type' => ['required', 'in:mandatory,optional'],
            'plan_type' => ['required', 'in:unified,non_unified'],
            'notes' => ['nullable', 'string'],
            'branch_participation' => ['array'],
            'branch_participation.*' => ['in:participant,not_participant,unspecified'],
        ]);

        $date = Carbon::parse($data['event_date']);

        $event = AgendaEvent::create([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'event_date' => $date->toDateString(),
            'event_day' => $date->translatedFormat('l'),
            'event_name' => $data['event_name'],
            'department_id' => $data['department_id'] ?? null,
            'event_category_id' => $data['event_category_id'] ?? null,
            'event_type' => $data['event_type'],
            'plan_type' => $data['plan_type'],
            'event_category' => optional(EventCategory::find($data['event_category_id'] ?? null))->name,
            'status' => 'draft',
            'relations_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
            'created_by' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
        ]);

        foreach (($data['branch_participation'] ?? []) as $branchId => $status) {
            AgendaParticipation::create([
                'agenda_event_id' => $event->id,
                'entity_type' => 'branch',
                'entity_id' => $branchId,
                'participation_status' => $status,
                'updated_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.created'));
    }

    public function edit(AgendaEvent $agendaEvent)
    {
        $this->assertEventManageAccess(request(), $agendaEvent);

        $agendaEvent->load('participations');
        $departments = Department::orderBy('name')->get();
        $categories = EventCategory::where('active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $branchParticipations = $agendaEvent->participations
            ->where('entity_type', 'branch')
            ->pluck('participation_status', 'entity_id')
            ->toArray();

        return view('roles.relations.agenda.edit', compact('agendaEvent', 'departments', 'categories', 'branches', 'branchParticipations'));
    }

    public function update(Request $request, AgendaEvent $agendaEvent)
    {
        $this->assertEventManageAccess($request, $agendaEvent);

        $data = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'event_category_id' => [
                'nullable',
                Rule::exists('event_categories', 'id')->where(function ($query) use ($request) {
                    $departmentId = (int) $request->input('department_id');

                    if ($departmentId > 0) {
                        $query->where('department_id', $departmentId);
                    }
                }),
            ],
            'event_type' => ['required', 'in:mandatory,optional'],
            'plan_type' => ['required', 'in:unified,non_unified'],
            'notes' => ['nullable', 'string'],
            'branch_participation' => ['array'],
            'branch_participation.*' => ['in:participant,not_participant,unspecified'],
        ]);

        $date = Carbon::parse($data['event_date']);

        $agendaEvent->update([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'event_date' => $date->toDateString(),
            'event_day' => $date->translatedFormat('l'),
            'event_name' => $data['event_name'],
            'department_id' => $data['department_id'] ?? null,
            'event_category_id' => $data['event_category_id'] ?? null,
            'event_type' => $data['event_type'],
            'plan_type' => $data['plan_type'],
            'event_category' => optional(EventCategory::find($data['event_category_id'] ?? null))->name,
            'notes' => $data['notes'] ?? null,
        ]);

        $agendaEvent->participations()->where('entity_type', 'branch')->delete();
        foreach (($data['branch_participation'] ?? []) as $branchId => $status) {
            AgendaParticipation::create([
                'agenda_event_id' => $agendaEvent->id,
                'entity_type' => 'branch',
                'entity_id' => $branchId,
                'participation_status' => $status,
                'updated_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.updated', ['event' => $agendaEvent->event_name]));
    }

    public function submit(Request $request, AgendaEvent $agendaEvent)
    {
        $this->assertEventManageAccess($request, $agendaEvent);

        if (
            $agendaEvent->event_type === 'optional'
            && ! $agendaEvent->participations()
                ->where('entity_type', 'branch')
                ->where('participation_status', 'participant')
                ->exists()
        ) {
            return back()->withErrors(['branch_participation' => 'لا يمكن إرسال فعالية اختيارية بدون تحديد مشاركة الفروع.']);
        }

        $agendaEvent->update([
            'status' => 'submitted',
        ]);

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.submitted', ['event' => $agendaEvent->event_name]));
    }
}
