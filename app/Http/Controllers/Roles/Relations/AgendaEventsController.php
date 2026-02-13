<?php

namespace App\Http\Controllers\Roles\Relations;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentUnit;
use App\Models\EventCategory;
use App\Models\AuditLog;
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

    protected function allowedUnitRoleMap(): array
    {
        return [
            'workshops_committee' => ['workshops_secretary', 'relations_manager'],
            'communication_head' => ['communication_head', 'relations_manager'],
            'khalda_programs_manager' => ['programs_manager', 'relations_manager'],
            'khalda_events_relations' => ['relations_manager'],
        ];
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

        $unitStatuses = $agendaEvent->participations
            ->where('entity_type', 'department_unit')
            ->mapWithKeys(function ($participation) {
                $unit = DepartmentUnit::find($participation->entity_id);

                return $unit ? [$unit->unit_key => $participation->participation_status] : [];
            })
            ->toArray();

        $departmentUnits = DepartmentUnit::orderBy('id')->get();

        return view('roles.relations.agenda.edit', compact('agendaEvent', 'departments', 'categories', 'branches', 'branchParticipations', 'departmentUnits', 'unitStatuses'));
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

    public function updateUnitParticipation(Request $request, AgendaEvent $agendaEvent)
    {
        $data = $request->validate([
            'unit_key' => ['required', 'string'],
            'status' => ['required', 'in:participant,not_participant,unspecified'],
        ]);

        $roleMap = $this->allowedUnitRoleMap();
        abort_unless(isset($roleMap[$data['unit_key']]), 422);

        $user = $request->user();
        $allowedRoles = $roleMap[$data['unit_key']];
        $hasAllowedRole = collect($allowedRoles)->contains(fn ($role) => $user->hasRole($role));
        abort_unless($hasAllowedRole, 403);

        $unit = DepartmentUnit::where('unit_key', $data['unit_key'])->firstOrFail();

        $participation = $agendaEvent->participations()
            ->where('entity_type', 'department_unit')
            ->where('entity_id', $unit->id)
            ->first();

        $oldStatus = $participation?->participation_status;

        $agendaEvent->participations()->updateOrCreate(
            [
                'entity_type' => 'department_unit',
                'entity_id' => $unit->id,
            ],
            [
                'participation_status' => $data['status'],
                'updated_by' => $user->id,
            ]
        );

        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'agenda.unit_participation.updated',
            'module' => 'agenda',
            'entity_type' => AgendaEvent::class,
            'entity_id' => $agendaEvent->id,
            'old_values' => ['unit_key' => $data['unit_key'], 'status' => $oldStatus],
            'new_values' => ['unit_key' => $data['unit_key'], 'status' => $data['status']],
        ]);

        return back()->with('status', 'تم تحديث مشاركة الجهة بنجاح.');
    }
}
