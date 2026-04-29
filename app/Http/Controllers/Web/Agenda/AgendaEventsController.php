<?php

namespace App\Http\Controllers\Web\Agenda;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\DepartmentUnit;
use App\Models\EventCategory;
use App\Models\EventStatusLookup;
use App\Models\MonthlyActivity;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkflowLog;
use App\Services\AgendaWorkflowPresenter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\AgendaWorkflowBridgeService;
use App\Services\ConflictDetectionService;
use App\Services\DynamicWorkflowService;
use App\Services\NotificationService;
use App\Services\WorkflowNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Collection;

class AgendaEventsController extends Controller
{
    protected function clearPartnerDepartments(AgendaEvent $event): void
    {
        $event->partnerDepartments()->sync([]);
    }

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

        abort_unless($user->can('agenda.create'), 403);
    }

    protected function assertEventManageAccess(Request $request, AgendaEvent $agendaEvent): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        if ($user->can('agenda.update')) {
            return;
        }

        abort(403);
    }

    protected function assertEventDeleteAccess(Request $request, AgendaEvent $agendaEvent): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin') || $user->can('agenda.delete')) {
            return;
        }

        abort(403);
    }

    protected function minimumAgendaEventDate(): string
    {
        return now()->toDateString();
    }

    protected function canDeleteAgendaEvent(AgendaEvent $agendaEvent): bool
    {
        return Carbon::parse($this->resolveAgendaEventDate($agendaEvent))->isAfter(today());
    }

    protected function notifyBranchPlanOwnersOfAgendaCancellation(
        AgendaEvent $agendaEvent,
        Collection $monthlyActivities,
        NotificationService $notifications
    ): void {
        $monthlyActivities
            ->loadMissing('creator')
            ->filter(fn (MonthlyActivity $activity): bool => $activity->creator !== null)
            ->groupBy('created_by')
            ->each(function (Collection $activities) use ($agendaEvent, $notifications) {
                $activity = $activities->first();

                $notifications->notifyUsers(
                    collect([$activity->creator]),
                    'agenda_event_cancelled',
                    __('app.roles.relations.agenda.notifications.cancelled_title'),
                    __('app.roles.relations.agenda.notifications.cancelled_message', [
                        'event' => $agendaEvent->event_name,
                        'date' => $this->resolveAgendaEventDate($agendaEvent),
                    ]),
                    route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'form' => 1]),
                    [
                        'agenda_event_id' => $agendaEvent->id,
                        'monthly_activity_ids' => $activities->pluck('id')->values()->all(),
                    ]
                );
            });
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

        $canUpdateParticipation = $user->can('agenda.participation.update');
        $isHq = $this->branchCode($user->branch) === 'khalda';

        if ($canUpdateParticipation && ! $isHq) {
            return $user;
        }

        return null;
    }

    protected function resolveAgendaEventDate(AgendaEvent $agendaEvent): string
    {
        // نوحد استخراج التاريخ حتى يبقى fallback في مكان واحد فقط.
        return optional($agendaEvent->event_date)?->toDateString()
            ?? Carbon::create(now()->year, $agendaEvent->month, $agendaEvent->day)->toDateString();
    }

    protected function flashAgendaCreatePrefill(Request $request): void
    {
        if ($request->session()->hasOldInput()) {
            return;
        }

        $date = trim((string) $request->query('date', ''));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return;
        }

        $request->session()->flash('_old_input', [
            'event_date' => $date,
        ]);
    }

    protected function upsertBranchParticipation(
        AgendaEvent $agendaEvent,
        User $branchActor,
        string $status,
        ?string $proposedDate = null,
        ?string $actualExecutionDate = null,
        ?string $branchPlanFile = null
    ): AgendaParticipation {
        return $agendaEvent->participations()->updateOrCreate(
            [
                'entity_type' => 'branch',
                'entity_id' => $branchActor->branch_id,
            ],
            [
                'participation_status' => $status,
                'proposed_date' => $status === 'participant' ? $proposedDate : null,
                'actual_execution_date' => $status === 'participant' ? $actualExecutionDate : null,
                'branch_plan_file' => $branchPlanFile,
                'updated_by' => $branchActor->id,
            ]
        );
    }

    protected function upsertBranchMonthlyActivityFromAgenda(
        AgendaEvent $agendaEvent,
        User $branchActor,
        string $proposedDate,
        array $template = []
    ): MonthlyActivity {
        // هذه هي نقطة الربط الموحدة بين فعالية الأجندة وسجل الخطة الشهرية للفرع.
        $agendaDate = $this->resolveAgendaEventDate($agendaEvent);
        $isUnifiedPlan = (string) ($agendaEvent->plan_type ?? 'non_unified') === 'unified';

        $templateTitle = trim((string) ($template['title'] ?? ''));
        $templateDescription = trim((string) ($template['description'] ?? ''));
        $templateExecutionTime = trim((string) ($template['execution_time'] ?? ''));
        $templateLocationDetails = trim((string) ($template['location_details'] ?? ''));
        $templateRequiredVolunteers = $template['required_volunteers'] ?? null;

        $monthlyActivity = MonthlyActivity::firstOrNew([
            'agenda_event_id' => $agendaEvent->id,
            'branch_id' => $branchActor->branch_id,
        ]);

        $monthlyActivity->fill([
            'month' => (int) Carbon::parse($agendaDate)->format('m'),
            'day' => (int) Carbon::parse($agendaDate)->format('d'),
            'title' => $templateTitle !== '' ? $templateTitle : $agendaEvent->event_name,
            'proposed_date' => $isUnifiedPlan
                ? (optional($monthlyActivity->proposed_date)->toDateString() ?: $proposedDate)
                : $proposedDate,
            'is_in_agenda' => true,
            'is_from_agenda' => true,
            'participation_status' => 'participant',
            'plan_type' => $agendaEvent->plan_type ?? 'non_unified',
            'description' => $templateDescription !== '' ? $templateDescription : $agendaEvent->notes,
            'location_type' => $monthlyActivity->location_type ?? 'inside_center',
            'location_details' => $templateLocationDetails !== '' ? $templateLocationDetails : $monthlyActivity->location_details,
            'execution_time' => $templateExecutionTime !== '' ? $templateExecutionTime : $monthlyActivity->execution_time,
            'required_volunteers' => is_numeric($templateRequiredVolunteers) ? (int) $templateRequiredVolunteers : $monthlyActivity->required_volunteers,
            'status' => $monthlyActivity->status ?? 'draft',
            'created_by' => $monthlyActivity->created_by ?: $branchActor->id,
        ]);

        $monthlyActivity->save();

        return $monthlyActivity;
    }

    protected function unifiedTemplatePayload(array $data): array
    {
        return [
            'title' => $data['monthly_template_title'] ?? null,
            'proposed_date' => $data['monthly_template_proposed_date'] ?? null,
            'description' => $data['monthly_template_description'] ?? null,
            'execution_time' => $data['monthly_template_execution_time'] ?? null,
            'location_details' => $data['monthly_template_location_details'] ?? null,
            'required_volunteers' => $data['monthly_template_required_volunteers'] ?? null,
        ];
    }


    protected function applyBranchVisibilityScope($query, User $user)
    {
        return $query;
    }

    protected function applyDraftVisibilityScope($query, ?User $user)
    {
        return $query->where(function ($visibilityQuery) use ($user) {
            $visibilityQuery->where('status', '!=', 'draft');

            if ($user) {
                $visibilityQuery->orWhere('created_by', $user->id);
            }
        });
    }

    protected function applyPublicationVisibilityScope($query, ?User $user): void
    {
        if (! $user || $user->hasRole('super_admin')) {
            return;
        }

        $roleIds = $user->roles()->pluck('roles.id')->all();
        $permissionIds = $user->getAllPermissions()->pluck('id')->all();

        $query->where(function ($visibilityQuery) use ($roleIds, $permissionIds) {
            $visibilityQuery
                ->where('status', 'published')
                ->orWhereHas('workflowInstance', function ($instanceQuery) use ($roleIds, $permissionIds) {
                    $instanceQuery
                        ->whereIn('status', ['pending', 'in_progress', DynamicWorkflowService::DECISION_CHANGES_REQUESTED])
                        ->whereHas('currentStep', function ($stepQuery) use ($roleIds, $permissionIds) {
                            $stepQuery
                                ->where('step_type', 'main')
                                ->where(function ($assigneeQuery) use ($roleIds, $permissionIds) {
                                    if ($roleIds === [] && $permissionIds === []) {
                                        $assigneeQuery->whereRaw('1 = 0');

                                        return;
                                    }

                                    if ($roleIds !== []) {
                                        $assigneeQuery->whereIn('role_id', $roleIds);
                                    }

                                    if ($permissionIds !== []) {
                                        $method = $roleIds === [] ? 'whereIn' : 'orWhereIn';
                                        $assigneeQuery->{$method}('permission_id', $permissionIds);
                                    }
                                });
                        });
                });
        });
    }

    /**
     * @return array<int, int>
     */
    protected function scopedBranchIds(?User $user): array
    {
        if (! $user
            || ! method_exists($user, 'hasBranchScopedAgendaVisibility')
            || ! $user->hasBranchScopedAgendaVisibility()
        ) {
            return [];
        }

        return method_exists($user, 'scopedBranchIds')
            ? $user->scopedBranchIds()
            : (filled($user->branch_id) ? [(int) $user->branch_id] : []);
    }

    protected function ensureAgendaVisibleToUser(AgendaEvent $agendaEvent, User $user): void
    {
        $canSeeBeforePublication = $this->canSeeAgendaBeforePublication($user, $agendaEvent);

        if ((string) $agendaEvent->status !== 'published' && ! $canSeeBeforePublication) {
            abort(403);
        }
    }

    protected function canSeeAgendaBeforePublication(User $user, ?AgendaEvent $agendaEvent = null): bool
    {
        if ($user->hasRole('super_admin')) {
            return true;
        }

        if (! $agendaEvent) {
            return false;
        }

        $dynamicWorkflowService = app(DynamicWorkflowService::class);
        $instance = $dynamicWorkflowService->forModel('agenda', $agendaEvent);
        $currentStep = $instance ? $dynamicWorkflowService->currentStep($instance) : null;

        return $instance
            && $dynamicWorkflowService->canDecide($instance)
            && (string) ($currentStep?->step_type ?? '') === 'main'
            && $dynamicWorkflowService->currentStepForUser($instance, $user) !== null;
    }

    protected function agendaStatusOptions(?string $currentStatus = null)
    {
        return EventStatusLookup::query()
            ->forModule('agenda')
            ->where(function ($query) use ($currentStatus) {
                $query->where('is_active', true);

                if (filled($currentStatus)) {
                    $query->orWhere('code', $currentStatus);
                }
            })
            ->ordered()
            ->get()
            ->unique('code')
            ->values();
    }

    protected function agendaDepartmentsForForm(?AgendaEvent $agendaEvent = null)
    {
        $selectedIds = collect([$agendaEvent?->owner_department_id])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return Department::query()
            ->where(function ($query) use ($selectedIds) {
                $query->where('is_active', true);

                if ($selectedIds->isNotEmpty()) {
                    $query->orWhereIn('id', $selectedIds->all());
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function agendaCategoriesForForm(?AgendaEvent $agendaEvent = null)
    {
        $selectedCategoryId = $agendaEvent?->event_category_id;

        return EventCategory::query()
            ->where(function ($query) use ($selectedCategoryId) {
                $query->where('active', true);

                if (filled($selectedCategoryId)) {
                    $query->orWhere($query->getModel()->getQualifiedKeyName(), $selectedCategoryId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function departmentUnitsForAgenda(?AgendaEvent $agendaEvent = null)
    {
        $selectedUnitIds = collect($agendaEvent?->participations ?? [])
            ->where('entity_type', 'department_unit')
            ->pluck('entity_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return DepartmentUnit::query()
            ->where(function ($query) use ($selectedUnitIds) {
                $query->where('is_active', true);

                if ($selectedUnitIds->isNotEmpty()) {
                    $query->orWhereIn('id', $selectedUnitIds->all());
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function index(Request $request, AgendaWorkflowPresenter $agendaWorkflowPresenter)
    {
        $allowedPerPage = [8, 16, 24, 50, 100];
        $perPage = (int) $request->input('per_page', 8);
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 8;
        }
        $selectedStatus = trim((string) $request->input('status', ''));

        $filteredBranchId = $this->canUserFilterAgendaBranches($request->user())
            ? $request->input('branch_id')
            : null;
        $agendaFilters = array_merge($request->except('status'), [
            'branch_id' => $filteredBranchId,
        ]);

        $eventsQuery = AgendaEvent::with([
            'creator',
            'department',
            'ownerDepartment',
            'eventCategory',
            'monthlyActivities',
            'participations',
            'workflowInstance.workflow.steps.role',
            'workflowInstance.currentStep.role',
            'workflowInstance.logs.step.role',
            'workflowInstance.logs.actor',
        ])
            ->enterpriseFilter($agendaFilters)
            ->notArchived();

        $this->applyPublicationVisibilityScope($eventsQuery, $request->user());
        $this->applyBranchVisibilityScope($eventsQuery, $request->user());
        $this->applyDraftVisibilityScope($eventsQuery, $request->user());
        $this->applyAgendaPageStatusFilter($eventsQuery, $selectedStatus);

        $events = $eventsQuery
            ->orderBy('event_date')->orderBy('month')->orderBy('day')
            ->paginate($perPage)
            ->withQueryString();

        $events->getCollection()->transform(function (AgendaEvent $event) use ($agendaWorkflowPresenter, $request) {
            return $agendaWorkflowPresenter->attach($event, $request->user());
        });

        $calendarEvents = (clone $eventsQuery)
            ->orderBy('event_date')->orderBy('month')->orderBy('day')
            ->get();

        $calendarEvents->transform(function (AgendaEvent $event) use ($agendaWorkflowPresenter, $request) {
            return $agendaWorkflowPresenter->attach($event, $request->user());
        });

        $branchActor = $this->branchActor($request);
        $branches = Branch::query()
            ->when($this->scopedBranchIds($request->user()) !== [] && $this->canUserFilterAgendaBranches($request->user()), function ($query) use ($request) {
                $query->whereIn('id', $this->scopedBranchIds($request->user()));
            })
            ->orderBy('name')
            ->get();
        $canFilterBranches = $this->canUserFilterAgendaBranches($request->user());

        $filters = array_merge($agendaFilters, [
            'status' => $selectedStatus,
            'per_page' => $perPage,
        ]);

        $agendaStatusOptions = $this->agendaPageStatusOptions();

        return view('pages.agenda.events.index', compact('events', 'calendarEvents', 'filters', 'branchActor', 'branches', 'canFilterBranches', 'agendaStatusOptions'));
    }

    protected function agendaPageStatusOptions(): Collection
    {
        return collect([
            (object) ['code' => 'draft', 'name' => __('app.roles.relations.agenda.status_labels.draft')],
            (object) ['code' => 'submitted', 'name' => __('app.roles.relations.agenda.status_labels.submitted')],
            (object) ['code' => 'approved', 'name' => __('app.roles.relations.agenda.status_labels.approved')],
        ]);
    }

    protected function applyAgendaPageStatusFilter($query, ?string $status): void
    {
        $status = trim((string) $status);

        if ($status === '') {
            return;
        }

        $query->where(function ($statusQuery) use ($status) {
            match ($status) {
                'draft' => $statusQuery->where('status', 'draft'),
                'approved' => $statusQuery->whereIn('status', ['approved', 'relations_approved', 'published']),
                'submitted' => $statusQuery->whereIn('status', ['submitted', 'pending', 'in_review', 'changes_requested', 'rejected']),
                default => $statusQuery->where('status', $status),
            };
        });
    }

    protected function currentUserBranchIdForFilters(Request $request): ?int
    {
        return null;
    }

    protected function canUserFilterAgendaBranches(?User $user): bool
    {
        $scopedBranchIds = $this->scopedBranchIds($user);

        return $scopedBranchIds === [] || count($scopedBranchIds) > 1;
    }

    public function show(Request $request, AgendaEvent $agendaEvent, AgendaWorkflowPresenter $agendaWorkflowPresenter)
    {
        $agendaEvent = AgendaEvent::query()
            ->whereKey($agendaEvent->id)
            ->with([
                'creator',
                'department',
                'ownerDepartment',
                'eventCategory',
                'monthlyActivities',
                'participations',
                'workflowInstance.workflow.steps.role',
                'workflowInstance.currentStep.role',
                'workflowInstance.logs.step.role',
                'workflowInstance.logs.actor',
            ])
            ->firstOrFail();

        $this->ensureAgendaVisibleToUser($agendaEvent, $request->user());

        $branchesById = Branch::query()
            ->get()
            ->mapWithKeys(fn ($branch) => [$branch->id => ['name' => $branch->name, 'color_hex' => $branch->color_hex, 'icon' => $branch->icon]]);
        $branchesTotalCount = $branchesById->count();
        $unitsById = DepartmentUnit::query()
            ->get()
            ->mapWithKeys(fn ($unit) => [$unit->id => ['name' => $unit->name, 'color_hex' => $unit->color_hex, 'icon' => $unit->icon]]);

        $branchParticipations = $agendaEvent->participations
            ->where('entity_type', 'branch')
            ->map(function ($participation) use ($branchesById) {
                return [
                    'name' => $branchesById[$participation->entity_id]['name'] ?? ('#'.$participation->entity_id),
                    'color_hex' => $branchesById[$participation->entity_id]['color_hex'] ?? null,
                    'icon' => $branchesById[$participation->entity_id]['icon'] ?? null,
                    'status' => $participation->participation_status,
                    'proposed_date' => $participation->proposed_date,
                    'actual_execution_date' => $participation->actual_execution_date,
                ];
            })
            ->values();

        $unitParticipations = $agendaEvent->participations
            ->where('entity_type', 'department_unit')
            ->map(function ($participation) use ($unitsById) {
                return [
                    'name' => $unitsById[$participation->entity_id]['name'] ?? ('#'.$participation->entity_id),
                    'color_hex' => $unitsById[$participation->entity_id]['color_hex'] ?? null,
                    'icon' => $unitsById[$participation->entity_id]['icon'] ?? null,
                    'status' => $participation->participation_status,
                ];
            })
            ->values();

        $agendaWorkflowPresenter->attach($agendaEvent, $request->user());

        return view('pages.agenda.events.show', compact('agendaEvent', 'branchParticipations', 'unitParticipations', 'branchesTotalCount'));
    }

    public function create(Request $request)
    {
        $this->assertKhaldaHqAgendaAuthority($request);
        $this->flashAgendaCreatePrefill($request);

        $departments = $this->agendaDepartmentsForForm();
        $categories = $this->agendaCategoriesForForm();
        $branches = Branch::orderBy('name')->get();

        return view('pages.agenda.events.create', compact('departments', 'categories', 'branches'));
    }

    public function store(
        Request $request,
        ConflictDetectionService $conflicts,
        WorkflowNotificationService $workflowNotifications,
        AgendaWorkflowBridgeService $agendaWorkflowBridgeService
    )
    {
        $this->assertKhaldaHqAgendaAuthority($request);

        $data = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:'.$this->minimumAgendaEventDate()],
            'owner_department_id' => ['required', 'exists:departments,id'],
            'event_category_id' => [
                'nullable',
                Rule::exists('event_categories', 'id')->where(function ($query) use ($request) {
                    $ownerDepartmentId = (int) $request->input('owner_department_id');

                    if ($ownerDepartmentId > 0) {
                        $query->where('department_id', $ownerDepartmentId);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
            'event_type' => ['required', 'in:mandatory,optional'],
            'plan_type' => ['required', 'in:unified,non_unified'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'unified_plan_source' => ['nullable', 'in:monthly_auto'],
            'monthly_template_title' => ['nullable', 'string', 'max:255'],
            'monthly_template_proposed_date' => ['nullable', 'date'],
            'monthly_template_description' => ['nullable', 'string', 'max:2000'],
            'monthly_template_target_group' => ['nullable', 'string', 'max:255'],
            'monthly_template_execution_time' => ['nullable', 'string', 'max:255'],
            'monthly_template_location_details' => ['nullable', 'string', 'max:255'],
            'monthly_template_required_volunteers' => ['nullable', 'integer', 'min:0'],
            'branch_participation' => ['array'],
            'branch_participation.*' => ['in:participant,not_participant,unspecified'],
        ]);

        $data['unified_plan_source'] = ($data['plan_type'] ?? null) === 'unified' ? 'monthly_auto' : null;
        if (($data['plan_type'] ?? null) === 'unified') {
            validator($data, [
                'monthly_template_title' => ['required', 'string', 'max:255'],
                'monthly_template_proposed_date' => ['required', 'date'],
                'monthly_template_description' => ['required', 'string', 'max:2000'],
                'monthly_template_target_group' => ['required', 'string', 'max:255'],
            ])->validate();
        }

        $date = Carbon::parse($data['event_date']);

        $conflictNames = $conflicts->findAgendaConflicts($date->toDateString(), array_keys($data['branch_participation'] ?? []));
        $conflictWarning = empty($conflictNames) ? null : __('Potential conflict with: :events', ['events' => implode(', ', $conflictNames)]);

        $event = null;
        DB::transaction(function () use (&$event, $date, $data, $request, $agendaWorkflowBridgeService) {
            $event = AgendaEvent::create([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'event_date' => $date->toDateString(),
            'event_day' => $date->translatedFormat('l'),
            'event_name' => $data['event_name'],
            'department_id' => (int) $data['owner_department_id'],
            'owner_department_id' => (int) $data['owner_department_id'],
            'event_category_id' => $data['event_category_id'] ?? null,
            'event_type' => $data['event_type'],
            'is_mandatory' => $data['event_type'] === 'mandatory',
            'plan_type' => $data['plan_type'],
            'is_unified' => $data['plan_type'] === 'unified',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'event_category' => optional(EventCategory::find($data['event_category_id'] ?? null))->name,
            'status' => 'draft',
            'relations_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
            'created_by' => $request->user()->id,
            'notes' => $data['notes'] ?? null,
            'agenda_plan_file' => null,
            'version' => 1,
            ]);

            $this->clearPartnerDepartments($event);
            Log::info('agenda_event.created', [
                'agenda_event_id' => $event->id,
                'owner_department_id' => $event->owner_department_id,
                'created_by' => $request->user()->id,
            ]);

            $selectedStatuses = $data['branch_participation'] ?? [];

            foreach ($selectedStatuses as $branchId => $status) {
                AgendaParticipation::create([
                    'agenda_event_id' => $event->id,
                    'entity_type' => 'branch',
                    'entity_id' => $branchId,
                    'participation_status' => $status,
                    'updated_by' => $request->user()->id,
                ]);
            }

            $agendaWorkflowBridgeService->syncMandatoryAgendaToMonthlyPlans($event);
        });
        $workflowNotifications->created($event, $request->user(), route('role.relations.agenda.show', $event));

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.created'))
            ->with('warning', $conflictWarning);
    }

    public function edit(AgendaEvent $agendaEvent)
    {
        $this->assertKhaldaHqAgendaAuthority(request());
        $this->assertEventManageAccess(request(), $agendaEvent);

        $agendaEvent->load(['participations']);
        $departments = $this->agendaDepartmentsForForm($agendaEvent);
        $categories = $this->agendaCategoriesForForm($agendaEvent);
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

        $departmentUnits = $this->departmentUnitsForAgenda($agendaEvent);

        return view('pages.agenda.events.edit', compact('agendaEvent', 'departments', 'categories', 'branches', 'branchParticipations', 'departmentUnits', 'unitStatuses'));
    }

    public function update(
        Request $request,
        AgendaEvent $agendaEvent,
        ConflictDetectionService $conflicts,
        AgendaWorkflowBridgeService $agendaWorkflowBridgeService
    )
    {
        $this->assertKhaldaHqAgendaAuthority($request);
        $this->assertEventManageAccess($request, $agendaEvent);

        $data = $request->validate([
            'event_name' => ['required', 'string', 'max:255'],
            'event_date' => ['required', 'date', 'after_or_equal:'.$this->minimumAgendaEventDate()],
            'owner_department_id' => ['required', 'exists:departments,id'],
            'event_category_id' => [
                'nullable',
                Rule::exists('event_categories', 'id')->where(function ($query) use ($request) {
                    $ownerDepartmentId = (int) $request->input('owner_department_id');

                    if ($ownerDepartmentId > 0) {
                        $query->where('department_id', $ownerDepartmentId);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }),
            ],
            'event_type' => ['required', 'in:mandatory,optional'],
            'plan_type' => ['required', 'in:unified,non_unified'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
            'unified_plan_source' => ['nullable', 'in:monthly_auto'],
            'monthly_template_title' => ['nullable', 'string', 'max:255'],
            'monthly_template_proposed_date' => ['nullable', 'date'],
            'monthly_template_description' => ['nullable', 'string', 'max:2000'],
            'monthly_template_target_group' => ['nullable', 'string', 'max:255'],
            'monthly_template_execution_time' => ['nullable', 'string', 'max:255'],
            'monthly_template_location_details' => ['nullable', 'string', 'max:255'],
            'monthly_template_required_volunteers' => ['nullable', 'integer', 'min:0'],
            'branch_participation' => ['array'],
            'branch_participation.*' => ['in:participant,not_participant,unspecified'],
        ]);

        $data['unified_plan_source'] = ($data['plan_type'] ?? null) === 'unified' ? 'monthly_auto' : null;
        if (($data['plan_type'] ?? null) === 'unified') {
            validator($data, [
                'monthly_template_title' => ['required', 'string', 'max:255'],
                'monthly_template_proposed_date' => ['required', 'date'],
                'monthly_template_description' => ['required', 'string', 'max:2000'],
                'monthly_template_target_group' => ['required', 'string', 'max:255'],
            ])->validate();
        }

        $date = Carbon::parse($data['event_date']);

        $conflictNames = $conflicts->findAgendaConflicts($date->toDateString(), array_keys($data['branch_participation'] ?? []), $agendaEvent->id);
        $conflictWarning = empty($conflictNames) ? null : __('Potential conflict with: :events', ['events' => implode(', ', $conflictNames)]);

        $agendaPlanFile = $agendaEvent->agenda_plan_file;
        if ($agendaPlanFile) {
            Storage::disk('public')->delete($agendaPlanFile);
        }
        $agendaPlanFile = null;

        DB::transaction(function () use ($agendaEvent, $date, $data, $request, $agendaWorkflowBridgeService) {
            $agendaEvent->update([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'event_date' => $date->toDateString(),
            'event_day' => $date->translatedFormat('l'),
            'event_name' => $data['event_name'],
            'department_id' => (int) $data['owner_department_id'],
            'owner_department_id' => (int) $data['owner_department_id'],
            'event_category_id' => $data['event_category_id'] ?? null,
            'event_type' => $data['event_type'],
            'is_mandatory' => $data['event_type'] === 'mandatory',
            'plan_type' => $data['plan_type'],
            'is_unified' => $data['plan_type'] === 'unified',
            'is_active' => (bool) ($data['is_active'] ?? true),
            'event_category' => optional(EventCategory::find($data['event_category_id'] ?? null))->name,
            'notes' => $data['notes'] ?? null,
            'agenda_plan_file' => $agendaPlanFile,
            'version' => (int) ($agendaEvent->version ?? 1) + 1,
            ]);

        $this->clearPartnerDepartments($agendaEvent);
        Log::info('agenda_event.updated', [
            'agenda_event_id' => $agendaEvent->id,
            'owner_department_id' => $agendaEvent->owner_department_id,
            'updated_by' => $request->user()->id,
        ]);

            $agendaEvent->participations()->where('entity_type', 'branch')->delete();
            $selectedStatuses = $data['branch_participation'] ?? [];

            foreach ($selectedStatuses as $branchId => $status) {
                AgendaParticipation::create([
                    'agenda_event_id' => $agendaEvent->id,
                    'entity_type' => 'branch',
                    'entity_id' => $branchId,
                    'participation_status' => $status,
                    'updated_by' => $request->user()->id,
                ]);
            }

            $agendaWorkflowBridgeService->syncMandatoryAgendaToMonthlyPlans($agendaEvent);
        });

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.updated', ['event' => $agendaEvent->event_name]))
            ->with('warning', $conflictWarning);
    }

    public function destroy(Request $request, AgendaEvent $agendaEvent, NotificationService $notifications, WorkflowNotificationService $workflowNotifications)
    {
        $this->assertKhaldaHqAgendaAuthority($request);
        $this->assertEventDeleteAccess($request, $agendaEvent);

        abort_unless($this->canDeleteAgendaEvent($agendaEvent), 422, __('app.roles.relations.agenda.errors.delete_future_only'));

        $agendaEvent->load(['monthlyActivities.creator', 'workflowInstance']);
        $monthlyActivities = $agendaEvent->monthlyActivities;
        $eventName = $agendaEvent->event_name;

        DB::transaction(function () use ($agendaEvent, $monthlyActivities, $notifications, $workflowNotifications, $request) {
            $this->notifyBranchPlanOwnersOfAgendaCancellation($agendaEvent, $monthlyActivities, $notifications);
            $workflowNotifications->deleted(
                $agendaEvent,
                $request->user(),
                $monthlyActivities->map(fn (MonthlyActivity $activity) => $activity->creator)->filter(),
                route('role.relations.agenda.index')
            );

            $monthlyActivities->each(function (MonthlyActivity $activity) use ($agendaEvent) {
                $activity->forceFill([
                    'agenda_event_id' => null,
                    'is_in_agenda' => false,
                    'is_from_agenda' => false,
                    'execution_status' => 'cancelled',
                    'cancellation_reason' => __('app.roles.relations.agenda.cancellation_reason', [
                        'event' => $agendaEvent->event_name,
                        'date' => $this->resolveAgendaEventDate($agendaEvent),
                    ]),
                ])->save();
            });

            $agendaEvent->workflowInstance?->delete();
            $agendaEvent->delete();

            Log::info('agenda_event.deleted', [
                'agenda_event_id' => $agendaEvent->id,
                'deleted_by' => $request->user()->id,
                'notified_monthly_activities_count' => $monthlyActivities->count(),
            ]);
        });

        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.deleted', ['event' => $eventName]));
    }

    public function submit(
        Request $request,
        AgendaEvent $agendaEvent,
        WorkflowNotificationService $workflowNotifications,
        DynamicWorkflowService $dynamicWorkflowService,
        AgendaWorkflowBridgeService $agendaWorkflowBridgeService
    )
    {
        $this->assertKhaldaHqAgendaAuthority($request);
        $this->assertEventManageAccess($request, $agendaEvent);

        $instance = $dynamicWorkflowService->forModel('agenda', $agendaEvent);
        abort_unless($instance !== null, 422, __('app.roles.programs.monthly_activities.approvals.errors.no_active_workflow'));

        $currentStep = $dynamicWorkflowService->currentStep($instance);

        if ($instance->status === DynamicWorkflowService::DECISION_CHANGES_REQUESTED && $currentStep?->step_type !== 'sub') {
            $dynamicWorkflowService->markResubmitted($instance);
            $instance = $instance->fresh();
            $currentStep = $dynamicWorkflowService->currentStep($instance);
        }

        if ($currentStep?->step_type === 'sub') {
            WorkflowLog::query()->create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $currentStep->id,
                'acted_by' => $request->user()->id,
                'action' => DynamicWorkflowService::DECISION_APPROVED,
                'comment' => null,
                'edit_request_iteration' => (int) $instance->edit_request_count,
                'acted_at' => now(),
            ]);

            $dynamicWorkflowService->advanceToNextStep($instance->fresh());
            $instance = $instance->fresh();
        }

        $agendaEvent = $agendaWorkflowBridgeService->syncApprovalState($agendaEvent, $instance);

        $workflowNotifications->approvalRequested($instance, $agendaEvent, route('role.relations.approvals.index'), $request->user());


        return redirect()
            ->route('role.relations.agenda.index')
            ->with('status', __('app.roles.relations.agenda.submitted', ['event' => $agendaEvent->event_name]));
    }

    public function updateUnitParticipation(Request $request, AgendaEvent $agendaEvent)
    {
        abort_unless($request->user()->can('agenda.participation.update'), 403);

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
        $this->ensureAgendaVisibleToUser($agendaEvent, $branchActor);
        abort_unless((bool) ($agendaEvent->is_active ?? true), 422, 'هذه الفعالية غير نشطة حالياً ولا يمكن المشاركة بها.');

        $data = $request->validate([
            'will_participate' => ['required', 'in:yes,no'],
            'proposed_date' => ['nullable', 'date'],
            'actual_execution_date' => ['nullable', 'date'],
            'branch_plan_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
        ]);

        $agendaDate = $this->resolveAgendaEventDate($agendaEvent);
        $minDate = Carbon::parse($agendaDate)->subDays(7)->toDateString();
        $maxDate = Carbon::parse($agendaDate)->addDays(7)->toDateString();

        $status = $agendaEvent->event_type === 'mandatory' ? 'participant' : ($data['will_participate'] === 'yes' ? 'participant' : 'not_participant');
        $isParticipating = $status === 'participant';
        $isUnifiedPlan = $agendaEvent->plan_type === 'unified';

        $existing = $agendaEvent->participations()
            ->where('entity_type', 'branch')
            ->where('entity_id', $branchActor->branch_id)
            ->first();

        if ($isParticipating) {
            if (! $isUnifiedPlan) {
                abort_if(empty($data['proposed_date']), 422, 'التاريخ المقترح مطلوب عند المشاركة.');
                abort_if($data['proposed_date'] < $minDate || $data['proposed_date'] > $maxDate, 422, 'التاريخ المقترح يجب أن يكون ضمن ±7 أيام من تاريخ الأجندة.');
            } else {
                $data['proposed_date'] = optional($existing?->proposed_date)?->toDateString() ?: $agendaDate;
            }
        }

        if ($isUnifiedPlan && $request->hasFile('branch_plan_file')) {
            abort(422, 'لا يمكن رفع خطة فرع لفعالية موحدة.');
        }

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

        $participation = $this->upsertBranchParticipation(
            $agendaEvent,
            $branchActor,
            $status,
            $data['proposed_date'] ?? null,
            $isUnifiedPlan ? optional($existing?->actual_execution_date)?->toDateString() : ($data['actual_execution_date'] ?? null),
            $planFile
        );

        if ($isParticipating) {
            $monthlyActivity = $this->upsertBranchMonthlyActivityFromAgenda($agendaEvent, $branchActor, $data['proposed_date']);

            if ($isUnifiedPlan) {
                $participation->forceFill([
                    'proposed_date' => optional($monthlyActivity->proposed_date)->toDateString() ?: $data['proposed_date'],
                    'actual_execution_date' => optional($monthlyActivity->actual_date)->toDateString(),
                ])->save();
            }
        }

        return back()->with('status', 'تم تحديث المشاركة وربط الفعالية بالخطة الشهرية بنجاح.');
    }

    public function quickSubscribeBranchToPlan(Request $request, AgendaEvent $agendaEvent)
    {
        $branchActor = $this->branchActor($request);
        abort_unless($branchActor !== null, 403);
        abort_if(empty($branchActor->branch_id), 422, 'Branch is required for branch participation.');

        $this->ensureAgendaVisibleToUser($agendaEvent, $branchActor);
        abort_unless((bool) ($agendaEvent->is_active ?? true), 422, 'هذه الفعالية غير نشطة حالياً ولا يمكن الاشتراك بها.');
        abort_if((string) $agendaEvent->event_type !== 'optional', 422, 'Quick subscription is available for optional agenda events only.');

        $existingPlanFile = $agendaEvent->participations()
            ->where('entity_type', 'branch')
            ->where('entity_id', $branchActor->branch_id)
            ->value('branch_plan_file');

        $agendaDate = $this->resolveAgendaEventDate($agendaEvent);
        $monthlyActivity = $this->upsertBranchMonthlyActivityFromAgenda($agendaEvent, $branchActor, $agendaDate);

        $this->upsertBranchParticipation(
            $agendaEvent,
            $branchActor,
            'participant',
            optional($monthlyActivity->proposed_date)->toDateString() ?: $agendaDate,
            optional($monthlyActivity->actual_date)->toDateString(),
            $existingPlanFile
        );

        return redirect()
            ->route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'form' => 1])
            ->with('status', 'تم اشتراك الفرع وربط الفعالية بالخطة الشهرية. يمكنك الآن استكمال تعبئة الخطة.');
    }
}
