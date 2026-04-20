@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
    $isRtl = app()->getLocale() === 'ar';
    $authUser = auth()->user();
    $branchText = mb_strtolower(trim((string) optional($authUser?->branch)->name . ' ' . (string) optional($authUser?->branch)->city));
    $isKhaldaHq = str_contains($branchText, 'khalda') || str_contains($branchText, 'خلدا') || str_contains($branchText, 'عمان') || str_contains($branchText, 'عمّان') || str_contains($branchText, 'amman');
    $canManageAgenda = $authUser?->can('agenda.create') ?? false;
    $canBranchInteract = ($authUser?->can('agenda.participation.update') ?? false) && ! $isKhaldaHq;

    $agendaStatusLabels = collect($agendaStatusOptions ?? [])->pluck('name', 'code')->all();
    $agendaStatusLabel = function (?string $status) use ($agendaStatusLabels): string {
        if (! $status) {
            return '-';
        }

        return $agendaStatusLabels[$status]
            ?? \App\Models\EventStatusLookup::labelFor('agenda', $status);
    };

    $branchesById = \App\Models\Branch::query()
        ->get()
        ->mapWithKeys(fn ($branch) => [
            $branch->id => [
                'name' => $branch->name,
                'color_hex' => $branch->color_hex,
                'icon' => $branch->icon,
            ],
        ]);
    $unitsById = \App\Models\DepartmentUnit::query()
        ->get()
        ->mapWithKeys(fn ($unit) => [
            $unit->id => [
                'name' => $unit->name,
                'color_hex' => $unit->color_hex,
                'icon' => $unit->icon,
            ],
        ]);

    $agendaEvents = collect($calendarEvents ?? $events->getCollection())->map(function ($event) use ($canManageAgenda, $agendaStatusLabel, $authUser, $branchesById, $unitsById) {
        $resolvedDate = optional($event->event_date)->format('Y-m-d')
            ?? sprintf('%04d-%02d-%02d', now()->year, $event->month, $event->day);
        $workflowSummary = $event->workflow_summary ?? [];
        $branchParticipation = $event->participations
            ->where('entity_type', 'branch')
            ->where('entity_id', $authUser?->branch_id)
            ->first();
        $participantBranches = $event->participations
            ->where('entity_type', 'branch')
            ->where('participation_status', 'participant')
            ->map(function ($participation) use ($branchesById) {
                return [
                    'id' => (int) $participation->entity_id,
                    'name' => $branchesById[$participation->entity_id]['name'] ?? ('#'.$participation->entity_id),
                    'color_hex' => $branchesById[$participation->entity_id]['color_hex'] ?? null,
                    'icon' => $branchesById[$participation->entity_id]['icon'] ?? null,
                ];
            })
            ->values()
            ->all();
        $partnerDepartments = $event->partnerDepartments
            ->map(fn ($department) => [
                'id' => (int) $department->id,
                'name' => $department->name,
                'color_hex' => $department->color_hex,
                'icon' => $department->icon,
            ])
            ->values()
            ->all();
        $participantUnits = $event->participations
            ->where('entity_type', 'department_unit')
            ->where('participation_status', 'participant')
            ->map(function ($participation) use ($unitsById) {
                return [
                    'id' => (int) $participation->entity_id,
                    'name' => $unitsById[$participation->entity_id]['name'] ?? ('#'.$participation->entity_id),
                    'color_hex' => $unitsById[$participation->entity_id]['color_hex'] ?? null,
                    'icon' => $unitsById[$participation->entity_id]['icon'] ?? null,
                ];
            })
            ->values()
            ->all();

        return [
            'id' => $event->id,
            'name' => $event->event_name,
            'date' => $resolvedDate,
            'department_id' => (int) ($event->department_id ?? 0),
            'department' => $event->department?->name ?? '-',
            'department_color_hex' => $event->department?->color_hex,
            'department_icon' => $event->department?->icon,
            'partner_departments' => $partnerDepartments,
            'participant_units' => $participantUnits,
            'category' => $event->eventCategory?->name ?? $event->event_category ?? '-',
            'status' => $workflowSummary['status_key'] ?? $event->status,
            'status_label' => $workflowSummary['status_label'] ?? $agendaStatusLabel($event->status),
            'workflow_state' => $workflowSummary['workflow_state_label'] ?? __('app.common.na'),
            'current_step_label' => $workflowSummary['current_step_label'] ?? __('app.common.na'),
            'current_role_label' => $workflowSummary['current_role_label'] ?? __('app.common.na'),
            'edit_url' => $canManageAgenda ? route('role.relations.agenda.edit', $event) : null,
            'view_url' => route('role.relations.agenda.show', $event),
            'submit_url' => $canManageAgenda ? route('role.relations.agenda.submit', $event) : null,
            'participant_count' => $event->participations->where('entity_type', 'branch')->where('participation_status', 'participant')->count(),
            'participant_branches' => $participantBranches,
            'plan_type' => $event->plan_type,
            'event_type' => $event->event_type,
            'branch_participation_status' => $branchParticipation?->participation_status,
            'branch_proposed_date' => optional($branchParticipation?->proposed_date)?->format('Y-m-d'),
            'branch_actual_execution_date' => optional($branchParticipation?->actual_execution_date)?->format('Y-m-d'),
        ];
    })->values();
@endphp

@section('content')
    <div class="event-module agenda-module" data-rtl="{{ $isRtl ? '1' : '0' }}" data-selected-year="{{ (int) request('year', 0) }}" data-selected-month="{{ (int) request('month', 0) }}">
        <div class="event-header">
            <div>
                <h1 class="h4 mb-1">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
            @if($canManageAgenda)
                <a class="btn btn-primary" href="{{ route('role.relations.agenda.create') }}">{{ __('app.roles.relations.agenda.actions.create') }}</a>
            @endif
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif


        <form method="GET" class="card card-body mb-3">
            <div class="row g-2">
                <div class="col-md-2"><input class="form-control" type="text" inputmode="numeric" pattern="[0-9]*" name="year" placeholder="اكتب السنة" value="{{ request('year') }}"></div>
                <div class="col-md-2"><input class="form-control" type="number" min="1" max="12" name="month" placeholder="اكتب رقم الشهر" value="{{ request('month') }}"></div>
                <div class="col-md-2">
                    <select class="form-select" name="status">
                        <option value="">{{ __('app.roles.relations.agenda.filters.all_statuses') }}</option>
                        @foreach ($agendaStatusOptions as $statusOption)
                            <option value="{{ $statusOption->code }}" {{ request('status') === $statusOption->code ? 'selected' : '' }}>{{ $agendaStatusLabel($statusOption->code) }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($canFilterBranches)
                    <div class="col-md-2">
                        <select class="form-select" name="branch_id">
                            <option value="">{{ __('app.roles.relations.agenda.filters.all_branches') }}</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <select class="form-select" name="event_type">
                        <option value="">{{ __('app.roles.relations.agenda.filters.all_event_types') }}</option>
                        <option value="mandatory" {{ request('event_type') === 'mandatory' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.types.mandatory') }}</option>
                        <option value="optional" {{ request('event_type') === 'optional' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.types.optional') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="plan_type">
                        <option value="">{{ __('app.roles.relations.agenda.filters.all_plan_types') }}</option>
                        <option value="unified" {{ request('plan_type') === 'unified' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.plans.unified') }}</option>
                        <option value="non_unified" {{ request('plan_type') === 'non_unified' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.plans.non_unified') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="per_page">
                        @foreach ([8, 16, 24, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 8) === $size ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.filters.show_count', ['count' => $size]) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.common.filter') }}</button></div>
            </div>
        </form>

        <div class="event-kpi-grid">
            <div class="event-kpi-card">
                <div class="text-muted small">{{ __('app.roles.relations.agenda.title') }}</div>
                <div class="event-kpi-value">{{ $events->total() }}</div>
            </div>
        </div>

        <div class="alert alert-info mt-3">{{ __('app.roles.relations.agenda.hints.scope_notice') }}</div>

        @if($canBranchInteract)
            <div class="card event-card mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.branch_interaction_title') }}</h2>
                    <div class="row g-3">
                        @foreach($events as $event)
                            @php
                                $branchParticipation = $event->participations->where('entity_type', 'branch')->where('entity_id', $authUser?->branch_id)->first();
                                $baseDate = optional($event->event_date)->format('Y-m-d') ?? sprintf('%04d-%02d-%02d', now()->year, $event->month, $event->day);
                                $isParticipating = ($branchParticipation?->participation_status ?? 'unspecified') === 'participant' || $event->event_type === 'mandatory';
                            @endphp
                            <div class="col-12 col-xl-6">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <div class="fw-semibold">{{ $event->event_name }}</div>
                                            <div class="small text-muted">📅 {{ $baseDate }}</div>
                                        </div>
                                        <div class="d-flex gap-1 flex-wrap justify-content-end">
                                            <span class="badge {{ $event->event_type === 'mandatory' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' }}">{{ __('app.roles.relations.agenda.types.' . $event->event_type) }}</span>
                                            <span class="badge {{ $event->plan_type === 'unified' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">{{ __('app.roles.relations.agenda.plans.' . $event->plan_type) }}</span>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('role.relations.agenda.branch_participation.update', $event) }}" enctype="multipart/form-data" class="row g-2">
                                        @csrf
                                        @method('PATCH')
                                        <div class="col-12">
                                            @if($event->event_type === 'optional')
                                                <label class="form-label mb-1">{{ __('app.roles.relations.agenda.branch_interaction.will_participate') }}</label>
                                                <div class="d-flex gap-3">
                                                    <label><input type="radio" name="will_participate" value="yes" {{ (($branchParticipation?->participation_status ?? null) === 'participant') ? 'checked' : '' }}> {{ __('app.roles.relations.agenda.branch_interaction.yes') }}</label>
                                                    <label><input type="radio" name="will_participate" value="no" {{ (($branchParticipation?->participation_status ?? null) === 'not_participant') ? 'checked' : '' }}> {{ __('app.roles.relations.agenda.branch_interaction.no') }}</label>
                                                </div>
                                            @else
                                                <input type="hidden" name="will_participate" value="yes">
                                                <div class="alert alert-info py-2 px-3 mb-0">{{ __('app.roles.relations.agenda.branch_interaction.mandatory_notice') }}</div>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-1">📌 {{ __('app.roles.relations.agenda.branch_interaction.proposed_date') }}</label>
                                            <input type="date" class="form-control" name="proposed_date" value="{{ optional($branchParticipation?->proposed_date)->format('Y-m-d') }}" {{ ! $isParticipating ? 'disabled' : '' }}>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-1">✅ {{ __('app.roles.relations.agenda.branch_interaction.actual_execution_date') }}</label>
                                            <input type="date" class="form-control" name="actual_execution_date" value="{{ optional($branchParticipation?->actual_execution_date)->format('Y-m-d') }}">
                                        </div>
                                        @if($event->plan_type === 'unified')
                                            <div class="col-12"><div class="alert alert-primary py-2 px-3 mb-0">{{ __('app.roles.relations.agenda.branch_interaction.unified_notice') }}</div></div>
                                        @else
                                            <div class="col-12">
                                                <label class="form-label mb-1">{{ __('app.roles.relations.agenda.branch_interaction.branch_plan_file') }}</label>
                                                <input type="file" class="form-control" name="branch_plan_file" accept=".pdf,.doc,.docx,.xls,.xlsx" {{ ! $isParticipating ? 'disabled' : '' }}>
                                            </div>
                                        @endif
                                        <div class="col-12 d-flex justify-content-end">
                                            <button class="btn btn-sm btn-primary">{{ __('app.roles.relations.agenda.branch_interaction.save') }}</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="agenda-view-switch mb-3" role="tablist" aria-label="{{ __('app.roles.relations.agenda.calendar.view_switcher') }}">
            <button type="button" class="btn btn-sm btn-primary active" data-view-toggle="table" aria-pressed="true">
                {{ __('app.roles.relations.agenda.calendar.table_view') }}
            </button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-view-toggle="calendar" aria-pressed="false">
                {{ __('app.roles.relations.agenda.calendar.calendar_view') }}
            </button>
        </div>

        <div class="agenda-view-pane" data-view-pane="table">
            <div class="card event-card">
                <div class="card-body">
                    <div class="agenda-cards-grid">
                        @forelse ($events as $event)
                            @php($workflowSummary = $event->workflow_summary ?? [])
                            <article class="agenda-event-card">
                                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                    <h3 class="h6 mb-0">{{ $event->event_name }}</h3>
                                    <span class="event-status status-{{ $workflowSummary['status_key'] ?? $event->status }}">{{ $workflowSummary['status_label'] ?? $agendaStatusLabel($event->status) }}</span>
                                </div>
                                <div class="agenda-card-meta">
                                    <span>📅 {{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}</span>
                                    <span>V{{ (int) ($event->version ?? 1) }}</span>
                                    <span>{{ $event->department?->name ?? '-' }}</span>
                                    <span>{{ $event->eventCategory?->name ?? $event->event_category ?? '-' }}</span>
                                </div>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <span class="badge {{ $event->event_type === 'mandatory' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning' }}">{{ __('app.roles.relations.agenda.types.' . $event->event_type) }}</span>
                                    <span class="badge {{ $event->plan_type === 'unified' ? 'bg-primary-subtle text-primary' : 'bg-info-subtle text-info' }}">{{ __('app.roles.relations.agenda.plans.' . $event->plan_type) }}</span>
                                    <span class="badge bg-light text-dark border">الفروع المشاركة: {{ $event->participations->where('entity_type', 'branch')->where('participation_status', 'participant')->count() }}</span>
                                </div>
                                <div class="approval-sequence-list mt-2">
                                    <div class="approval-sequence-item">
                                        <div class="approval-sequence-role">{{ __('workflow_ui.common.current_step') }}</div>
                                        <span>{{ $workflowSummary['current_step_label'] ?? __('app.common.na') }}</span>
                                    </div>
                                    <div class="approval-sequence-item">
                                        <div class="approval-sequence-role">{{ __('workflow_ui.common.assignee') }}</div>
                                        <span>{{ $workflowSummary['current_role_label'] ?? __('app.common.na') }}</span>
                                    </div>
                                </div>
                                <div class="event-actions mt-3">
                                    <a class="btn btn-sm btn-outline-dark" href="{{ route('role.relations.agenda.show', $event) }}">{{ __('app.roles.relations.agenda.actions.view') }}</a>
                                    @if($canManageAgenda)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.agenda.edit', $event) }}">{{ __('app.roles.relations.agenda.actions.edit') }}</a>
                                        <form method="POST" action="{{ route('role.relations.agenda.submit', $event) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('app.roles.relations.agenda.actions.submit') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="text-muted">{{ __('app.roles.relations.agenda.table.empty') }}</div>
                        @endforelse
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 flex-wrap gap-2">
                        <small class="text-muted">
                            {{ __('عرض') }} {{ $events->firstItem() ?? 0 }} - {{ $events->lastItem() ?? 0 }} {{ __('من') }} {{ $events->total() }}
                        </small>
                        <div class="d-flex align-items-center gap-2">
                            {{ $events->links() }}
                            @if ($events->hasMorePages())
                                <a class="btn btn-outline-primary btn-sm" href="{{ $events->nextPageUrl() }}">عرض المزيد</a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="agenda-view-pane d-none" data-view-pane="calendar">
            <div class="card event-card">
                <div class="card-body">
                    <div class="agenda-calendar-toolbar">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-nav="prev">{{ __('app.roles.relations.agenda.calendar.previous_month') }}</button>
                        <h2 class="h6 mb-0" data-calendar-title></h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-nav="next">{{ __('app.roles.relations.agenda.calendar.next_month') }}</button>
                    </div>
                    <div class="agenda-legend-explainer">
                        <div class="agenda-legend-card">
                            <span class="legend-badge legend-badge--square legend-badge--soft"></span>
                            <div>
                                <div class="fw-semibold">لون المربع</div>
                                <div class="small text-muted">يدل على الباب أو الوحدة أو القسم المرتبط بالفعالية.</div>
                            </div>
                        </div>
                        <div class="agenda-legend-card">
                            <span class="legend-badge legend-badge--circle legend-badge--soft"></span>
                            <div>
                                <div class="fw-semibold">النقطة الدائرية</div>
                                <div class="small text-muted">تدل على الفرع المشارك في الفعالية.</div>
                            </div>
                        </div>
                        <div class="agenda-legend-card">
                            <span class="event-status status-published">الحالة</span>
                            <div>
                                <div class="fw-semibold">شريط الحالة</div>
                                <div class="small text-muted">يبين وضع الفعالية الحالي داخل مسار الأجندة.</div>
                            </div>
                        </div>
                    </div>
                    <div class="agenda-calendar-legend agenda-calendar-legend--top" data-calendar-legend-top></div>

                    <div class="agenda-calendar-weekdays" data-calendar-weekdays></div>
                    <div class="agenda-calendar-grid" data-calendar-grid></div>
                    <div class="agenda-calendar-legend agenda-calendar-legend--bottom" data-calendar-legend-bottom></div>
                </div>
            </div>
        </div>
    </div>


@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/event-ui-shared.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/agenda-events-index.css') }}">
@endpush

@push('scripts')
    <script type="application/json" id="agenda-events-json">{!! $agendaEvents->toJson(JSON_UNESCAPED_UNICODE) !!}</script>
    <script type="application/json" id="agenda-weekdays-json">@json(__('app.roles.relations.agenda.calendar.weekdays'))</script>
    <script type="application/json" id="agenda-months-json">@json(__('app.roles.relations.agenda.calendar.months'))</script>
    <script src="{{ asset('assets/js/ui-shared.js') }}"></script>
    <script src="{{ asset('assets/js/agenda-events-index.js') }}"></script>
@endpush
@endsection
