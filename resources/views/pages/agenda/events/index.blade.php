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

    $roleDisplayNames = \App\Models\Role::query()
        ->whereIn('name', ['relations_manager', 'executive_manager'])
        ->get()
        ->mapWithKeys(fn ($role) => [$role->name => $role->display_name])
        ->all();

    $roleLabel = function (?string $roleKey) use ($roleDisplayNames): string {
        if (! $roleKey) {
            return '-';
        }

        if (! empty($roleDisplayNames[$roleKey])) {
            return $roleDisplayNames[$roleKey];
        }

        $translated = __('app.acl.roles.' . $roleKey);
        if ($translated !== 'app.acl.roles.' . $roleKey) {
            return $translated;
        }

        return (string) \Illuminate\Support\Str::of($roleKey)->replace('_', ' ')->title();
    };

    $agendaApprovalSteps = [
        ['role_label' => $roleLabel('relations_manager'), 'status_field' => 'relations_approval_status'],
        ['role_label' => $roleLabel('executive_manager'), 'status_field' => 'executive_approval_status'],
    ];

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

    $agendaEvents = $events->getCollection()->map(function ($event) use ($canManageAgenda, $agendaStatusLabel, $authUser, $branchesById, $unitsById) {
        $resolvedDate = optional($event->event_date)->format('Y-m-d')
            ?? sprintf('%04d-%02d-%02d', now()->year, $event->month, $event->day);
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
            'status' => $event->relations_approval_status ?? $event->status,
            'status_label' => $agendaStatusLabel($event->relations_approval_status ?? $event->status),
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
    <div class="event-module agenda-module" data-rtl="{{ $isRtl ? '1' : '0' }}">
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
                        <option value="">كل الحالات</option>
                        @foreach ($agendaStatusOptions as $statusOption)
                            <option value="{{ $statusOption->code }}" @selected(request('status') === $statusOption->code)>{{ $agendaStatusLabel($statusOption->code) }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($canFilterBranches)
                    <div class="col-md-2">
                        <select class="form-select" name="branch_id">
                            <option value="">كل الفروع</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-2">
                    <select class="form-select" name="event_type">
                        <option value="">كل أنواع الفعالية</option>
                        <option value="mandatory" @selected(request('event_type') === 'mandatory')>{{ __('app.roles.relations.agenda.types.mandatory') }}</option>
                        <option value="optional" @selected(request('event_type') === 'optional')>{{ __('app.roles.relations.agenda.types.optional') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="plan_type">
                        <option value="">كل أنواع الخطة</option>
                        <option value="unified" @selected(request('plan_type') === 'unified')>{{ __('app.roles.relations.agenda.plans.unified') }}</option>
                        <option value="non_unified" @selected(request('plan_type') === 'non_unified')>{{ __('app.roles.relations.agenda.plans.non_unified') }}</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="per_page">
                        @foreach ([10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" @selected((int) request('per_page', 20) === $size)>عرض {{ $size }}</option>
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

        @if($canBranchInteract)
            <div class="card event-card mb-3">
                <div class="card-body">
                    <h2 class="h6 mb-3">تفاعل الفرع مع الأجندة</h2>
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
                                                <label class="form-label mb-1">هل ستشارك؟</label>
                                                <div class="d-flex gap-3">
                                                    <label><input type="radio" name="will_participate" value="yes" @checked(($branchParticipation?->participation_status ?? null) === 'participant')> نعم</label>
                                                    <label><input type="radio" name="will_participate" value="no" @checked(($branchParticipation?->participation_status ?? null) === 'not_participant')> لا</label>
                                                </div>
                                            @else
                                                <input type="hidden" name="will_participate" value="yes">
                                                <div class="alert alert-info py-2 px-3 mb-0">فعالية إجبارية: سيتم اعتبار المشاركة تلقائياً.</div>
                                            @endif
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-1">📌 التاريخ المقترح</label>
                                            <input type="date" class="form-control" name="proposed_date" value="{{ optional($branchParticipation?->proposed_date)->format('Y-m-d') }}" @disabled(! $isParticipating)>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label mb-1">✅ تاريخ التنفيذ الفعلي</label>
                                            <input type="date" class="form-control" name="actual_execution_date" value="{{ optional($branchParticipation?->actual_execution_date)->format('Y-m-d') }}">
                                        </div>
                                        @if($event->plan_type === 'unified')
                                            <div class="col-12"><div class="alert alert-primary py-2 px-3 mb-0">الخطة موحدة من خلدا ويجب الالتزام بها.</div></div>
                                        @else
                                            <div class="col-12">
                                                <label class="form-label mb-1">رفع خطة الفرع</label>
                                                <input type="file" class="form-control" name="branch_plan_file" accept=".pdf,.doc,.docx,.xls,.xlsx" @disabled(! $isParticipating)>
                                            </div>
                                        @endif
                                        <div class="col-12 d-flex justify-content-end">
                                            <button class="btn btn-sm btn-primary">حفظ التفاعل</button>
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
                    <div class="event-table-wrap table-responsive">
                        <table class="table table-sm align-middle event-table">
                            <thead>
                                <tr>
                                    <th>{{ __('app.roles.relations.agenda.table.event_date') }}</th>
                                    <th>{{ __('app.roles.relations.agenda.table.event_name') }}</th>
                                    <th>الإصدار</th>
                                    <th>{{ __('app.roles.relations.agenda.fields_ext.department') }}</th>
                                    <th>{{ __('app.roles.relations.agenda.fields.event_category') }}</th>
                                    <th>{{ __('app.roles.relations.agenda.fields_ext.event_type') }}/{{ __('app.roles.relations.agenda.fields_ext.plan_type') }}</th>
                                    <th>{{ __('app.roles.relations.agenda.fields_ext.review_status') }}</th>
                                    <th>{{ __('app.roles.relations.agenda.fields_ext.participating_branches') }}</th>
                                    <th class="text-end">{{ __('app.roles.relations.agenda.table.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($events as $event)
                                    <tr>
                                        <td>{{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}</td>
                                        <td>{{ $event->event_name }}</td>
                                        <td>V{{ (int) ($event->version ?? 1) }}</td>
                                        <td>{{ $event->department?->name ?? '-' }}</td>
                                        <td>{{ $event->eventCategory?->name ?? $event->event_category ?? '-' }}</td>
                                        <td>{{ __('app.roles.relations.agenda.types.' . $event->event_type) }} / {{ __('app.roles.relations.agenda.plans.' . $event->plan_type) }}</td>
                                        <td>
                                            <div class="approval-sequence-list">
                                                @foreach($agendaApprovalSteps as $approvalStep)
                                                    @php($stepStatus = data_get($event, $approvalStep['status_field']) ?? 'pending')
                                                    <div class="approval-sequence-item">
                                                        <div class="approval-sequence-role">{{ $approvalStep['role_label'] }}</div>
                                                        <span class="event-status status-{{ $stepStatus }}">{{ $agendaStatusLabel($stepStatus) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>{{ $event->participations->where('entity_type', 'branch')->where('participation_status', 'participant')->count() }}</td>
                                        <td class="text-end">
                                            <div class="event-actions">
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
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-muted">{{ __('app.roles.relations.agenda.table.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="event-mobile-cards">
                        @foreach ($events as $event)
                            <div class="event-mobile-card">
                                <div class="fw-semibold mb-2">{{ $event->event_name }}</div>
                                <div class="event-mobile-row"><span class="text-muted">الإصدار</span><span>V{{ (int) ($event->version ?? 1) }}</span></div>
                                <div class="event-mobile-row"><span class="text-muted">{{ __('app.roles.relations.agenda.table.event_date') }}</span><span>{{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}</span></div>
                                <div class="event-mobile-row align-items-start">
                                    <span class="text-muted">{{ __('app.roles.relations.agenda.fields_ext.review_status') }}</span>
                                    <span class="approval-sequence-list">
                                        @foreach($agendaApprovalSteps as $approvalStep)
                                            @php($stepStatus = data_get($event, $approvalStep['status_field']) ?? 'pending')
                                            <span class="approval-sequence-item">
                                                <span class="approval-sequence-role">{{ $approvalStep['role_label'] }}</span>
                                                <span class="event-status status-{{ $stepStatus }}">{{ $agendaStatusLabel($stepStatus) }}</span>
                                            </span>
                                        @endforeach
                                    </span>
                                </div>
                                <div class="event-actions mt-2">
                                    <a class="btn btn-sm btn-outline-dark" href="{{ route('role.relations.agenda.show', $event) }}">{{ __('app.roles.relations.agenda.actions.view') }}</a>
                                    @if($canManageAgenda)
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.agenda.edit', $event) }}">{{ __('app.roles.relations.agenda.actions.edit') }}</a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <small class="text-muted">
                            {{ __('عرض') }} {{ $events->firstItem() ?? 0 }} - {{ $events->lastItem() ?? 0 }} {{ __('من') }} {{ $events->total() }}
                        </small>
                        {{ $events->links() }}
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
                    <div class="agenda-calendar-legend agenda-calendar-legend--top" data-calendar-legend-top></div>

                    <div class="agenda-calendar-weekdays" data-calendar-weekdays></div>
                    <div class="agenda-calendar-grid" data-calendar-grid></div>
                    <div class="agenda-calendar-legend agenda-calendar-legend--bottom" data-calendar-legend-bottom></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .approval-sequence-list { display: flex; flex-direction: column; gap: .35rem; }
        .approval-sequence-item { display: flex; flex-direction: column; gap: .15rem; }
        .approval-sequence-role { font-size: .75rem; color: #64748b; font-weight: 600; line-height: 1.2; }
        .agenda-calendar-legend { display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin: .75rem 0 1rem; padding: .5rem; border: 1px solid #e2e8f0; border-radius: .5rem; }
        .agenda-calendar-legend--top { background: #f8fafc; }
        .agenda-calendar-legend--bottom { background: #fff7ed; margin-top: 1rem; }
        .legend-item { display: inline-flex; align-items: center; gap: .4rem; font-size: .8rem; color: #334155; }
        .legend-badge { width: .8rem; height: .8rem; display: inline-block; border: 1px solid rgba(0,0,0,.12); }
        .legend-badge--square { border-radius: .2rem; }
        .legend-badge--circle { border-radius: 999px; }
        .agenda-event-chip-branches { display: flex; align-items: center; gap: .2rem; margin-top: .25rem; }
        .agenda-event-chip-units { display: flex; align-items: center; gap: .2rem; margin-top: .2rem; }
        .agenda-event-chip-dot { width: .5rem; height: .5rem; border-radius: 999px; display: inline-block; border: 1px solid rgba(0,0,0,.15); }
        .agenda-event-chip-square { width: .52rem; height: .52rem; border-radius: .12rem; display: inline-block; border: 1px solid rgba(0,0,0,.15); }
        .agenda-event-tooltip {
            position: fixed; z-index: 1080; pointer-events: none; min-width: 230px; max-width: 320px;
            background: #0f172a; color: #f8fafc; border-radius: .5rem; padding: .65rem .7rem;
            box-shadow: 0 10px 30px rgba(2, 6, 23, .35); font-size: .8rem; line-height: 1.35;
        }
        .agenda-event-tooltip .tooltip-title { font-weight: 700; margin-bottom: .35rem; }
        .agenda-event-tooltip .tooltip-row { margin-bottom: .25rem; }
        .agenda-event-tooltip .tooltip-list { display: flex; flex-wrap: wrap; gap: .25rem .4rem; }
        .agenda-event-tooltip .tooltip-pill { display: inline-flex; align-items: center; gap: .3rem; background: rgba(255,255,255,.12); border-radius: 999px; padding: .15rem .45rem; }
    </style>

    <script type="application/json" id="agenda-events-json">{!! $agendaEvents->toJson(JSON_UNESCAPED_UNICODE) !!}</script>
    <script>
        (function () {
            const module = document.querySelector('.agenda-module');
            if (!module) return;

            const isRtl = module.dataset.rtl === '1';
            const toggleButtons = module.querySelectorAll('[data-view-toggle]');
            const panes = module.querySelectorAll('[data-view-pane]');

            function switchView(nextView) {
                panes.forEach((pane) => pane.classList.toggle('d-none', pane.dataset.viewPane !== nextView));
                toggleButtons.forEach((button) => {
                    const active = button.dataset.viewToggle === nextView;
                    button.classList.toggle('btn-primary', active);
                    button.classList.toggle('btn-outline-primary', !active);
                    button.classList.toggle('active', active);
                    button.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            }

            toggleButtons.forEach((button) => {
                button.addEventListener('click', () => switchView(button.dataset.viewToggle));
            });

            const events = JSON.parse(document.getElementById('agenda-events-json')?.textContent ?? '[]');
            const weekDayLabels = @json(__('app.roles.relations.agenda.calendar.weekdays'));

            const monthNames = @json(__('app.roles.relations.agenda.calendar.months'));

            const selectedYear = Number(@json((int) request('year', 0)));
            const selectedMonth = Number(@json((int) request('month', 0)));
            let currentDate = new Date();
            if (selectedYear > 0 && selectedMonth >= 1 && selectedMonth <= 12) {
                currentDate = new Date(selectedYear, selectedMonth - 1, 1);
            } else if (events.length > 0) {
                currentDate = new Date(events[0].date);
                currentDate.setDate(1);
            } else {
                currentDate.setDate(1);
            }

            const weekdaysContainer = module.querySelector('[data-calendar-weekdays]');
            const gridContainer = module.querySelector('[data-calendar-grid]');
            const titleContainer = module.querySelector('[data-calendar-title]');
            const legendTopContainer = module.querySelector('[data-calendar-legend-top]');
            const legendBottomContainer = module.querySelector('[data-calendar-legend-bottom]');
            const palette = ['#E11D48', '#0EA5E9', '#22C55E', '#F59E0B', '#8B5CF6', '#14B8A6', '#F97316', '#3B82F6', '#84CC16', '#EC4899', '#06B6D4', '#A855F7'];
            const icons = ['🏢', '📍', '⭐', '🧭', '🎯', '🛰️', '🪄', '🛡️', '🔷', '🔶'];
            let tooltipEl = null;

            function mapDayPosition(jsDayIndex) {
                return isRtl ? 6 - jsDayIndex : jsDayIndex;
            }

            function colorForEntity(entity, id = null) {
                if (entity?.color_hex) return entity.color_hex;
                const key = Math.abs(Number(id || entity?.id || 0));
                return palette[key % palette.length];
            }

            function iconForEntity(entity, id = null) {
                if (entity?.icon) return entity.icon;
                const key = Math.abs(Number(id || entity?.id || 0));
                return icons[key % icons.length];
            }

            function ensureTooltip() {
                if (tooltipEl) return tooltipEl;
                tooltipEl = document.createElement('div');
                tooltipEl.className = 'agenda-event-tooltip d-none';
                document.body.appendChild(tooltipEl);
                return tooltipEl;
            }

            function setTooltipPosition(evt) {
                if (!tooltipEl) return;
                const offset = 16;
                const maxX = window.innerWidth - tooltipEl.offsetWidth - 12;
                const maxY = window.innerHeight - tooltipEl.offsetHeight - 12;
                const left = Math.min(maxX, Math.max(12, evt.clientX + offset));
                const top = Math.min(maxY, Math.max(12, evt.clientY + offset));
                tooltipEl.style.left = `${left}px`;
                tooltipEl.style.top = `${top}px`;
            }

            function renderLegend(monthEvents) {
                if (!legendTopContainer || !legendBottomContainer) return;
                const branches = new Map();
                const departments = new Map();
                const units = new Map();

                monthEvents.forEach((event) => {
                    (event.participant_branches ?? []).forEach((branch) => branches.set(branch.id, branch));
                    if (event.department_id && event.department && event.department !== '-') {
                        departments.set(event.department_id, {
                            id: event.department_id,
                            name: event.department,
                            color_hex: event.department_color_hex,
                            icon: event.department_icon,
                        });
                    }
                    (event.partner_departments ?? []).forEach((department) => departments.set(department.id, department));
                    (event.participant_units ?? []).forEach((unit) => units.set(unit.id, unit));
                });

                const renderItems = (entries, prefix, shapeClass) => Array.from(entries.entries()).map(([id, value]) => {
                    const entity = typeof value === 'object' ? value : { name: value };
                    return `
                        <span class="legend-item">
                            <span>${iconForEntity(entity, id)}</span>
                            <span class="legend-badge ${shapeClass}" style="background:${colorForEntity(entity, id)}"></span>
                            <span>${prefix}${entity.name}</span>
                        </span>
                    `;
                }).join('');

                legendTopContainer.innerHTML = `
                    ${renderItems(departments, '🏢 ', 'legend-badge--square')}
                    ${renderItems(units, '🧩 ', 'legend-badge--square')}
                `;
                legendBottomContainer.innerHTML = `${renderItems(branches, '🏳️ ', 'legend-badge--circle')}`;
            }

            function renderWeekdays() {
                weekdaysContainer.innerHTML = '';
                weekDayLabels.forEach((label) => {
                    const item = document.createElement('div');
                    item.className = 'agenda-weekday';
                    item.textContent = label;
                    weekdaysContainer.appendChild(item);
                });
            }

            function renderCalendar() {
                const year = currentDate.getFullYear();
                const month = currentDate.getMonth();
                const today = new Date();

                titleContainer.textContent = `${monthNames[month]} ${year}`;

                const monthEvents = events.filter((event) => {
                    const dateObj = new Date(event.date);
                    return dateObj.getFullYear() === year && dateObj.getMonth() === month;
                });
                renderLegend(monthEvents);

                const eventsByDay = new Map();
                monthEvents.forEach((event) => {
                    const day = new Date(event.date).getDate();
                    if (!eventsByDay.has(day)) eventsByDay.set(day, []);
                    eventsByDay.get(day).push(event);
                });

                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const firstDayPosition = mapDayPosition(new Date(year, month, 1).getDay());

                gridContainer.innerHTML = '';

                for (let i = 0; i < firstDayPosition; i += 1) {
                    const emptyCell = document.createElement('div');
                    emptyCell.className = 'agenda-calendar-day agenda-calendar-day--empty';
                    gridContainer.appendChild(emptyCell);
                }

                for (let day = 1; day <= daysInMonth; day += 1) {
                    const dayCell = document.createElement('div');
                    dayCell.className = 'agenda-calendar-day';

                    const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === day;
                    if (isToday) dayCell.classList.add('agenda-calendar-day--today');

                    const dayHeader = document.createElement('div');
                    dayHeader.className = 'agenda-calendar-day-number';
                    dayHeader.textContent = String(day);
                    dayCell.appendChild(dayHeader);

                    const dayEvents = eventsByDay.get(day) ?? [];
                    dayEvents.forEach((event) => {
                        const eventLink = document.createElement(event.view_url ? 'a' : 'div');
                        if (event.view_url) {
                            eventLink.href = event.view_url;
                        }
                        eventLink.className = `agenda-event-chip status-${event.status}`;
                        const branchDots = (event.participant_branches ?? []).slice(0, 5).map((branch) => {
                            return `<span class="agenda-event-chip-dot" style="background:${colorForEntity(branch)}" title="${branch.name}"></span>`;
                        }).join('');
                        const unitSquares = [
                            ...(event.department && event.department !== '-' ? [{
                                id: event.department_id,
                                name: event.department,
                                color_hex: event.department_color_hex,
                                icon: event.department_icon,
                            }] : []),
                            ...(event.partner_departments ?? []),
                            ...(event.participant_units ?? []),
                        ].slice(0, 5).map((entity) => (
                            `<span class="agenda-event-chip-square" style="background:${colorForEntity(entity)}" title="${entity.name}"></span>`
                        )).join('');
                        eventLink.innerHTML = `
                            <span class="agenda-event-chip-title">${event.name}</span>
                            <span class="event-status status-${event.status}">${event.status_label ?? event.status}</span>
                            <span class="agenda-event-chip-units">${unitSquares}</span>
                            <span class="agenda-event-chip-branches">${branchDots}</span>
                        `;

                        eventLink.addEventListener('mouseenter', (evt) => {
                            const tooltip = ensureTooltip();
                            const branchPills = (event.participant_branches ?? []).map((branch) => (
                                `<span class="tooltip-pill"><span>${iconForEntity(branch)}</span><span class="legend-badge legend-badge--circle" style="background:${colorForEntity(branch)}"></span><span>${branch.name}</span></span>`
                            )).join('');
                            const partnerDepartmentPills = (event.partner_departments ?? []).map((department) => (
                                `<span class="tooltip-pill"><span>${iconForEntity(department)}</span><span class="legend-badge legend-badge--square" style="background:${colorForEntity(department)}"></span><span>${department.name}</span></span>`
                            )).join('');
                            const unitPills = (event.participant_units ?? []).map((unit) => (
                                `<span class="tooltip-pill"><span>${iconForEntity(unit)}</span><span class="legend-badge legend-badge--square" style="background:${colorForEntity(unit)}"></span><span>${unit.name}</span></span>`
                            )).join('');

                            tooltip.innerHTML = `
                                <div class="tooltip-title">${event.name}</div>
                                <div class="tooltip-row">📅 ${event.date}</div>
                                <div class="tooltip-row">🏢 ${event.department ?? '-'}</div>
                                <div class="tooltip-row">🏷️ ${event.category ?? '-'}</div>
                                <div class="tooltip-row">📝 ${(event.event_type ?? '-') + ' / ' + (event.plan_type ?? '-')}</div>
                                <div class="tooltip-row">✅ ${event.status_label ?? event.status}</div>
                                <div class="tooltip-row"><strong>الفروع المشاركة:</strong></div>
                                <div class="tooltip-list">${branchPills || '<span class="text-muted">-</span>'}</div>
                                <div class="tooltip-row mt-1"><strong>الوحدات/الأقسام الشريكة:</strong></div>
                                <div class="tooltip-list">${partnerDepartmentPills || '<span class="text-muted">-</span>'}</div>
                                <div class="tooltip-row mt-1"><strong>الوحدات المشاركة:</strong></div>
                                <div class="tooltip-list">${unitPills || '<span class="text-muted">-</span>'}</div>
                            `;
                            tooltip.classList.remove('d-none');
                            setTooltipPosition(evt);
                        });
                        eventLink.addEventListener('mousemove', setTooltipPosition);
                        eventLink.addEventListener('mouseleave', () => {
                            if (tooltipEl) tooltipEl.classList.add('d-none');
                        });
                        dayCell.appendChild(eventLink);
                    });

                    gridContainer.appendChild(dayCell);
                }
            }

            module.querySelectorAll('[data-calendar-nav]').forEach((button) => {
                button.addEventListener('click', () => {
                    const delta = button.dataset.calendarNav === 'next' ? 1 : -1;
                    currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + delta, 1);
                    renderCalendar();
                });
            });

            switchView('table');
            renderWeekdays();
            renderCalendar();
        })();
    </script>
@endsection
