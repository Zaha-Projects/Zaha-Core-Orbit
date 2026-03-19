<?php

namespace App\Http\Controllers\Web\Agenda;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentUnit;
use App\Models\EventCategory;
use App\Models\MonthlyActivity;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Center;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\ConflictDetectionService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;

class AgendaEventsController extends Controller
{

    protected function branchCode(?Branch $branch): ?string
    {
        if (! $branch) {
            return null;
        }

        $explicitCode = strtolower(trim((string) data_get($branch, 'code', '')));
        if (in_array($explicitCode, ['khalda', 'zarqa', 'irbid'], true)) {
            return $explicitCode;
        }

        $text = $this->normalizeBranchText((string) ($branch->name ?? '').' '.(string) ($branch->city ?? ''));

        if (str_contains($text, 'khalda') || str_contains($text, 'خلدا') || str_contains($text, 'عمان') || str_contains($text, 'amman')) {
            return 'khalda';
        }

        if (str_contains($text, 'zarqa') || str_contains($text, 'زرق')) {
            return 'zarqa';
        }

        if (str_contains($text, 'irbid') || str_contains($text, 'اربد') || str_contains($text, 'إربد')) {
            return 'irbid';
        }

        return null;
    }

    protected function normalizeBranchText(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['أ', 'إ', 'آ'], 'ا', $value);
        $value = preg_replace('/[\x{064B}-\x{0652}]/u', '', $value) ?? $value;

        return $value;
    }

    protected function assertKhaldaHqAgendaAuthority(Request $request): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        $user->loadMissing('branch');
        $isKhaldaHq = $this->branchCode($user->branch) === 'khalda';

        abort_unless(
            $isKhaldaHq
            && $user->hasAnyRole(['super_admin', 'relations_manager']),
            403
        );
    }

    protected function assertEventManageAccess(Request $request, AgendaEvent $agendaEvent): void
    {
        $user = $request->user();

        if ($user->hasRole('relations_manager') || $user->hasRole('super_admin')) {
            return;
        }

        abort(403);
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

    protected function branchActor(Request $request): ?User
    {
        $user = $request->user();
        $user->loadMissing('branch');

        $isBranchRole = $user->hasAnyRole(['relations_officer', 'branch_relations_officer']);
        $isHq = $this->branchCode($user->branch) === 'khalda';

        if ($isBranchRole && ! $isHq) {
            return $user;
        }

        return null;
    }

    public function index(Request $request)
    {
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $events = AgendaEvent::with(['creator', 'department', 'eventCategory', 'participations'])
            ->enterpriseFilter($request->all())
            ->notArchived()
            ->orderBy('event_date')->orderBy('month')->orderBy('day')
            ->paginate($perPage)
            ->withQueryString();

        $branchActor = $this->branchActor($request);

        $filters = array_merge($request->all(), [
            'per_page' => $perPage,
        ]);

        return view('pages.agenda.events.index', compact('events', 'filters', 'branchActor'));
    }

    public function create()
    {
        $this->assertKhaldaHqAgendaAuthority(request());

        $departments = Department::orderBy('name')->get();
        $categories = EventCategory::where('active', true)->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('pages.agenda.events.create', compact('departments', 'categories', 'branches'));
    }

    public function store(Request $request, ConflictDetectionService $conflicts)
    {
        $this->assertKhaldaHqAgendaAuthority($request);

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
            'agenda_plan_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'branch_participation' => ['array'],
            'branch_participation.*' => ['in:participant,not_participant,unspecified'],
        ]);

        if (($data['plan_type'] ?? null) === 'non_unified' && ! $request->hasFile('agenda_plan_file')) {
            return back()->withErrors(['agenda_plan_file' => 'رفع خطة HQ مطلوب للفعاليات غير الموحدة.'])->withInput();
        }

        $date = Carbon::parse($data['event_date']);

        $conflictNames = $conflicts->findAgendaConflicts($date->toDateString(), array_keys($data['branch_participation'] ?? []));
        $conflictWarning = empty($conflictNames) ? null : __('Potential conflict with: :events', ['events' => implode(', ', $conflictNames)]);

        $event = AgendaEvent::create([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'event_date' => $date->toDateString(),
            'event_day' => $date->translatedFormat('l'),
            'event_name' => $data['event_name'],
            'department_id' => $data['department_id'] ?? null,
            'event_category_id' => $data['event_category_id'] ?? null,
            'event_type' => $data['event_type'],
            'is_mandatory' => $data['event_type'] === 'mandatory',
            'plan_type' => $data['plan_type'],
            'is_unified' => $data['plan_type'] === 'unified',
            'event_category' => optional(EventCategory::find($data['event_category_id'] ?? null))->name,
            'status' => 'draft',
            'relations_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
            'created_by' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
            'agenda_plan_file' => $request->file('agenda_plan_file')?->store('agenda/plans', 'public'),
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
            ->with('status', __('app.roles.relations.agenda.created'))
            ->with('warning', $conflictWarning);
    }

    public function edit(AgendaEvent $agendaEvent)
    {
        $this->assertKhaldaHqAgendaAuthority(request());
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

        return view('pages.agenda.events.edit', compact('agendaEvent', 'departments', 'categories', 'branches', 'branchParticipations', 'departmentUnits', 'unitStatuses'));
    }

    public function update(Request $request, AgendaEvent $agendaEvent, ConflictDetectionService $conflicts)
    {
        $this->assertKhaldaHqAgendaAuthority($request);
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
            'agenda_plan_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'branch_participation' => ['array'],
            'branch_participation.*' => ['in:participant,not_participant,unspecified'],
        ]);

        if (($data['plan_type'] ?? null) === 'non_unified' && ! $request->hasFile('agenda_plan_file') && empty($agendaEvent->agenda_plan_file)) {
            return back()->withErrors(['agenda_plan_file' => 'رفع خطة HQ مطلوب للفعاليات غير الموحدة.'])->withInput();
        }

        $date = Carbon::parse($data['event_date']);

        $conflictNames = $conflicts->findAgendaConflicts($date->toDateString(), array_keys($data['branch_participation'] ?? []), $agendaEvent->id);
        $conflictWarning = empty($conflictNames) ? null : __('Potential conflict with: :events', ['events' => implode(', ', $conflictNames)]);

        $agendaPlanFile = $agendaEvent->agenda_plan_file;
        if ($request->hasFile('agenda_plan_file')) {
            if ($agendaPlanFile) {
                Storage::disk('public')->delete($agendaPlanFile);
            }
            $agendaPlanFile = $request->file('agenda_plan_file')->store('agenda/plans', 'public');
        }

        $agendaEvent->update([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'event_date' => $date->toDateString(),
            'event_day' => $date->translatedFormat('l'),
            'event_name' => $data['event_name'],
            'department_id' => $data['department_id'] ?? null,
            'event_category_id' => $data['event_category_id'] ?? null,
            'event_type' => $data['event_type'],
            'is_mandatory' => $data['event_type'] === 'mandatory',
            'plan_type' => $data['plan_type'],
            'is_unified' => $data['plan_type'] === 'unified',
            'event_category' => optional(EventCategory::find($data['event_category_id'] ?? null))->name,
            'notes' => $data['notes'] ?? null,
            'agenda_plan_file' => $agendaPlanFile,
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
            ->with('status', __('app.roles.relations.agenda.updated', ['event' => $agendaEvent->event_name]))
            ->with('warning', $conflictWarning);
    }

    public function submit(Request $request, AgendaEvent $agendaEvent, NotificationService $notifications)
    {
        $this->assertKhaldaHqAgendaAuthority($request);
        $this->assertEventManageAccess($request, $agendaEvent);

        if (
            $agendaEvent->event_type === 'optional'
            && ! $agendaEvent->participations()
                ->where('entity_type', 'branch')
                ->where('participation_status', 'participant')
                ->exists()
        ) {
            return back()->withErrors(['branch_participation' => __('app.roles.relations.agenda.errors.optional_requires_branch_participation')]);
        }

        $agendaEvent->update([
            'status' => 'submitted',
        ]);
        $notifications->notifyUsers(User::role('relations_manager')->get(), 'approval_requested', 'Agenda approval requested', $agendaEvent->event_name, route('role.relations.approvals.index'));


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

        return back()->with('status', __('app.roles.relations.agenda.unit_participation_updated'));
    }

    public function updateBranchParticipation(Request $request, AgendaEvent $agendaEvent)
    {
        $branchActor = $this->branchActor($request);
        abort_unless($branchActor !== null, 403);
        abort_if(empty($branchActor->branch_id), 422, 'Branch is required for branch participation.');

        $data = $request->validate([
            'will_participate' => ['required', 'in:yes,no'],
            'proposed_date' => ['nullable', 'date'],
            'actual_execution_date' => ['nullable', 'date'],
            'branch_plan_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
        ]);

        $agendaDate = optional($agendaEvent->event_date)?->toDateString()
            ?? Carbon::create(now()->year, $agendaEvent->month, $agendaEvent->day)->toDateString();
        $minDate = Carbon::parse($agendaDate)->subDays(7)->toDateString();
        $maxDate = Carbon::parse($agendaDate)->addDays(7)->toDateString();

        $status = $agendaEvent->event_type === 'mandatory' ? 'participant' : ($data['will_participate'] === 'yes' ? 'participant' : 'not_participant');
        $isParticipating = $status === 'participant';

        if ($isParticipating) {
            abort_if(empty($data['proposed_date']), 422, 'التاريخ المقترح مطلوب عند المشاركة.');
            abort_if($data['proposed_date'] < $minDate || $data['proposed_date'] > $maxDate, 422, 'التاريخ المقترح يجب أن يكون ضمن ±7 أيام من تاريخ الأجندة.');
        }

        if ($agendaEvent->plan_type === 'unified' && $request->hasFile('branch_plan_file')) {
            abort(422, 'لا يمكن رفع خطة فرع لفعالية موحدة.');
        }

        $existing = $agendaEvent->participations()
            ->where('entity_type', 'branch')
            ->where('entity_id', $branchActor->branch_id)
            ->first();

        $planFile = $existing?->branch_plan_file;
        if ($request->hasFile('branch_plan_file')) {
            if ($planFile) {
                Storage::disk('public')->delete($planFile);
            }
            $planFile = $request->file('branch_plan_file')->store('agenda/branch-plans', 'public');
        }

        if ($agendaEvent->plan_type === 'non_unified' && $isParticipating && empty($planFile)) {
            abort(422, 'رفع خطة الفرع مطلوب للفعالية غير الموحدة.');
        }

        $participation = $agendaEvent->participations()->updateOrCreate(
            [
                'entity_type' => 'branch',
                'entity_id' => $branchActor->branch_id,
            ],
            [
                'participation_status' => $status,
                'proposed_date' => $isParticipating ? $data['proposed_date'] : null,
                'actual_execution_date' => $data['actual_execution_date'] ?? null,
                'branch_plan_file' => $planFile,
                'updated_by' => $branchActor->id,
            ]
        );

        if ($isParticipating) {
            $centerId = $branchActor->center_id ?: Center::where('branch_id', $branchActor->branch_id)->value('id');
            abort_if(empty($centerId), 422, 'يجب تحديد مركز للفرع قبل إنشاء الخطة الشهرية.');

            $monthlyActivity = MonthlyActivity::firstOrNew([
                'agenda_event_id' => $agendaEvent->id,
                'branch_id' => $branchActor->branch_id,
            ]);

            $monthlyActivity->fill([
                'month' => (int) Carbon::parse($agendaDate)->format('m'),
                'day' => (int) Carbon::parse($agendaDate)->format('d'),
                'title' => $agendaEvent->event_name,
                'proposed_date' => $data['proposed_date'],
                'is_in_agenda' => true,
                'is_from_agenda' => true,
                'participation_status' => 'participant',
                'plan_type' => $agendaEvent->plan_type ?? 'non_unified',
                'description' => $agendaEvent->notes,
                'location_type' => $monthlyActivity->location_type ?? 'inside_center',
                'status' => $monthlyActivity->status ?? 'draft',
                'center_id' => $centerId,
                'created_by' => $monthlyActivity->created_by ?: $branchActor->id,
            ]);
            $monthlyActivity->save();
        }

        return back()->with('status', 'تم تحديث المشاركة وربط الفعالية بالخطة الشهرية بنجاح.');
    }
}
