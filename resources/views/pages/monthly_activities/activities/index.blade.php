@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.title');
    $subtitle = ($viewScope ?? 'default') === 'all_branches'
        ? 'عرض أنشطة جميع الفروع ضمن الخطط الشهرية.'
        : __('app.roles.programs.monthly_activities.subtitle');
    $monthlyStatusLabels = collect($monthlyStatusOptions ?? [])->pluck('name', 'code')->all();
    $workflowStatusLabel = function (?string $status) use ($monthlyStatusLabels): string {
        if (! $status) {
            return '-';
        }

        return $monthlyStatusLabels[$status]
            ?? \App\Models\EventStatusLookup::labelFor('monthly_activities', $status);
    };

    $roleLabel = function (?string $roleKey): ?string {
        if (! $roleKey) {
            return null;
        }

        $translated = __('app.acl.roles.' . $roleKey);
        if ($translated !== 'app.acl.roles.' . $roleKey) {
            return $translated;
        }

        return (string) \Illuminate\Support\Str::of($roleKey)->replace('_', ' ')->title();
    };

    $calendarStatusLabels = [
        'draft' => $workflowStatusLabel('draft'),
        'submitted' => $workflowStatusLabel('submitted'),
        'in_review' => $workflowStatusLabel('in_review'),
        'approved' => $workflowStatusLabel('approved'),
        'changes_requested' => $workflowStatusLabel('changes_requested'),
        'rejected' => $workflowStatusLabel('rejected'),
        'postponed' => $workflowStatusLabel('postponed'),
        'cancelled' => $workflowStatusLabel('cancelled'),
        'closed' => $workflowStatusLabel('closed'),
        'completed' => $workflowStatusLabel('completed'),
    ];
@endphp

@section('content')
    <div class="event-module monthly-activities-module" data-calendar-endpoint="{{ route('role.relations.activities.calendar') }}" data-rtl="{{ app()->getLocale() === 'ar' ? '1' : '0' }}">
        <div class="card event-card mb-4">
            <div class="card-body">
                <h1 class="h4 mb-2">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if (session('warning'))
            <div class="alert alert-warning">{{ session('warning') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <div class="fw-semibold mb-2">يرجى تصحيح الأخطاء التالية:</div>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="event-kpi-grid">
            <div class="event-kpi-card"><div class="text-muted small">{{ __('app.roles.programs.monthly_activities.list_title') }}</div><div class="event-kpi-value">{{ $activities->count() }}</div></div>
            <div class="event-kpi-card"><div class="text-muted small">{{ __('app.roles.programs.monthly_activities.statuses.approved') }}</div><div class="event-kpi-value">{{ $activities->where('status','approved')->count() }}</div></div>
        </div>

        <div class="card event-card mb-4">
            <div class="card-body">
                <h2 class="event-section-title">{{ __('app.common.filter') }}</h2>
                <form method="GET" action="{{ route('role.relations.activities.index') }}" class="row event-form-grid">
                    @if (($viewScope ?? 'default') === 'all_branches')
                        <input type="hidden" name="scope" value="all_branches">
                    @endif
                    @if ($canFilterBranches)
                        <div class="col-12 col-md-6 col-xl-3">
                            <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.branch') }}</label>
                            <select class="form-select" name="branch_id">
                                <option value="">{{ __('app.roles.programs.monthly_activities.fields.branch_placeholder') }}</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-6 col-xl-2"><label class="form-label">{{ __('app.roles.programs.monthly_activities.sync.month') }}</label><input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" name="month" value="{{ $filters['month'] ?? '' }}" placeholder="اكتب رقم الشهر"></div>
                    <div class="col-6 col-xl-2"><label class="form-label">{{ __('app.roles.programs.monthly_activities.sync.year') }}</label><input type="text" inputmode="numeric" pattern="[0-9]*" class="form-control" name="year" value="{{ $filters['year'] ?? '' }}" placeholder="اكتب السنة"></div>
                    <div class="col-12 col-md-6 col-xl-3">
                        <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label>
                        <select class="form-select" name="status">
                            <option value="">كل الحالات</option>
                            @foreach ($monthlyStatusOptions as $statusOption)
                                <option value="{{ $statusOption->code }}" @selected(($filters['status'] ?? '') === $statusOption->code)>{{ $workflowStatusLabel($statusOption->code) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-xl-2 event-actions"><button class="btn btn-outline-primary" type="submit">{{ __('app.common.filter') }}</button></div>
                </form>
            </div>
        </div>

        <div class="card event-card mb-4">
            <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div>
                    <h2 class="event-section-title mb-1">{{ __('app.roles.programs.monthly_activities.create_title') }}</h2>
                    <p class="text-muted mb-0">استخدم نموذج الإدخال لإضافة نشاط جديد مع الحفاظ على نفس مسار الاعتمادات.</p>
                </div>
                <a href="{{ route('role.relations.activities.create') }}" class="btn btn-primary">{{ __('app.roles.programs.monthly_activities.actions.create') }}</a>
            </div>
        </div>

        <div class="agenda-view-switch mb-3" role="tablist">
            <button type="button" class="btn btn-sm btn-primary active" data-view-toggle="table" aria-pressed="true">جدول</button>
            <button type="button" class="btn btn-sm btn-outline-primary" data-view-toggle="calendar" aria-pressed="false">تقويم</button>
        </div>

        <div class="agenda-view-pane" data-view-pane="table">
            <div class="card event-card">
                <div class="card-body">
                    <h2 class="event-section-title">{{ __('app.roles.programs.monthly_activities.list_title') }}</h2>
                    <div class="event-table-wrap table-responsive">
                        <table class="table table-sm align-middle event-table">
                            <thead><tr><th>{{ __('app.roles.programs.monthly_activities.table.title') }}</th><th>{{ __('app.roles.programs.monthly_activities.table.date') }}</th><th>المصدر</th><th>{{ __('app.roles.programs.monthly_activities.table.branch') }}</th><th>الإصدار</th><th>{{ __('app.roles.programs.monthly_activities.table.status') }}</th><th class="text-end">{{ __('app.roles.programs.monthly_activities.table.actions') }}</th></tr></thead>
                            <tbody>
                                @forelse ($activities as $activity)
                                    @php $isOldVersion = (int) ($activity->newer_versions_count ?? 0) > 0; @endphp
                                    <tr>
                                        <td>{{ $activity->title }}</td><td>{{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</td><td>@if($activity->is_in_agenda)<span class='badge bg-success-subtle text-success'>من الأجندة</span>@else<span class='badge bg-warning-subtle text-warning'>إدخال يدوي</span>@endif</td><td>{{ $activity->branch?->name ?? '-' }}</td><td><div class="d-flex flex-column gap-1"><span class="badge bg-light text-dark border monthly-version-badge">نسخة {{ (int) ($activity->plan_version ?: 1) }}</span>@if($isOldVersion)<span class="badge bg-secondary-subtle text-secondary">نسخة قديمة</span>@endif</div></td><td>
                                            @php
                                                $wf = $activity->workflowInstance;
                                                $steps = $wf?->workflow?->steps?->sortBy([['step_order', 'asc'], ['approval_level', 'asc']]) ?? collect();
                                                $latestLogsByStep = $wf?->logs?->sortByDesc('acted_at')->groupBy('workflow_step_id') ?? collect();
                                            @endphp
                                            @if($steps->isNotEmpty())
                                                <div class="approval-sequence-list">
                                                    @foreach($steps as $step)
                                                        @php
                                                            $latestStepLog = $latestLogsByStep->get($step->id)?->first();
                                                            $stepStatus = $latestStepLog?->action ?? 'pending';
                                                            $stepRole = $step->role?->display_name
                                                                ?? $roleLabel($step->role?->name);
                                                        @endphp
                                                        <div class="approval-sequence-item">
                                                            <div class="approval-sequence-role">{{ $stepRole ?: ($step->name_ar ?? $step->name_en ?? '-') }}</div>
                                                            <span class="event-status status-{{ $stepStatus }}">{{ $workflowStatusLabel($stepStatus) }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="event-status status-{{ $activity->status }}">{{ $workflowStatusLabel($activity->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end"><div class="event-actions justify-content-end"><a class="btn btn-sm btn-outline-dark" href="{{ route('role.relations.activities.show', $activity) }}">عرض</a>@unless($isOldVersion)<a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'form' => 1]) }}">{{ __('app.roles.programs.monthly_activities.actions.edit') }}</a><a class="btn btn-sm btn-outline-success" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post']) }}">إكمال بعد التنفيذ</a><form method="POST" action="{{ route('role.relations.activities.submit', $activity) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-primary" type="submit">{{ __('app.roles.programs.monthly_activities.actions.submit') }}</button></form>@endunless</div></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-muted">{{ __('app.roles.programs.monthly_activities.table.empty') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="mt-3">{{ $activities->links() }}</div>
        </div>

        <div class="agenda-view-pane d-none" data-view-pane="calendar">
            <div class="card event-card">
                <div class="card-body">
                    <div class="agenda-calendar-toolbar mb-3 d-flex justify-content-between align-items-center">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-nav="prev">السابق</button>
                        <h2 class="h6 mb-0" data-calendar-title></h2>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-calendar-nav="next">التالي</button>
                    </div>
                    <div class="agenda-calendar-weekdays" data-calendar-weekdays></div>
                    <div class="agenda-calendar-grid" data-calendar-grid></div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .approval-sequence-list { display: flex; flex-direction: column; gap: .35rem; }
        .approval-sequence-item { display: flex; flex-direction: column; gap: .15rem; }
        .approval-sequence-role { font-size: .75rem; color: #64748b; font-weight: 600; line-height: 1.2; }
        .monthly-version-badge { width: fit-content; }
        .monthly-calendar-badge { border-radius: 999px; padding: 2px 8px; font-size: 11px; font-weight: 600; width: fit-content; }
        .monthly-calendar-badge--draft { background: #e5e7eb; color: #374151; }
        .monthly-calendar-badge--in-review { background: #fff4e5; color: #a16207; }
        .monthly-calendar-badge--approved { background: #e8f7ef; color: #166534; }
        .monthly-calendar-badge--rejected { background: #fee2e2; color: #991b1b; }
        .monthly-calendar-branch { font-size: 12px; color: #6b7280; margin-bottom: 4px; display: block; }
        .monthly-calendar-meta { display: flex; align-items: center; justify-content: space-between; gap: 6px; margin-top: 6px; }
        .monthly-calendar-icons { display: inline-flex; gap: 4px; font-size: 13px; }
    </style>

    <script>
        (function () {
            const module = document.querySelector('.monthly-activities-module');
            if (!module) return;

            const toggleButtons = module.querySelectorAll('[data-view-toggle]');
            const panes = module.querySelectorAll('[data-view-pane]');
            const isRtl = module.dataset.rtl === '1';
            const statusLabels = @json($calendarStatusLabels);

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
            toggleButtons.forEach((button) => button.addEventListener('click', () => switchView(button.dataset.viewToggle)));

            const weekdaysContainer = module.querySelector('[data-calendar-weekdays]');
            const gridContainer = module.querySelector('[data-calendar-grid]');
            const titleContainer = module.querySelector('[data-calendar-title]');
            const endpoint = module.dataset.calendarEndpoint;

            const weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            weekdaysContainer.innerHTML = weekdays.map((label) => `<div class="agenda-weekday">${label}</div>`).join('');

            const now = new Date();
            let currentYear = now.getFullYear();
            let currentMonth = now.getMonth() + 1;

            function mapPos(day) { return isRtl ? 6 - day : day; }

            async function loadCalendar() {
                const res = await fetch(`${endpoint}?year=${currentYear}&month=${currentMonth}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const payload = await res.json();
                const items = payload.items || [];

                const firstDay = new Date(currentYear, currentMonth - 1, 1);
                const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
                const firstOffset = mapPos(firstDay.getDay());
                const today = new Date();

                titleContainer.textContent = `${firstDay.toLocaleString(undefined, { month: 'long' })} ${currentYear}`;

                gridContainer.innerHTML = '';
                for (let i = 0; i < firstOffset; i++) {
                    const pad = document.createElement('div');
                    pad.className = 'agenda-calendar-day agenda-calendar-day--empty';
                    gridContainer.appendChild(pad);
                }

                for (let day = 1; day <= daysInMonth; day++) {
                    const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    const dayItems = items.filter((it) => it.date === dateStr);
                    const cell = document.createElement('div');
                    cell.className = 'agenda-calendar-day';
                    if (today.getFullYear() === currentYear && (today.getMonth() + 1) === currentMonth && today.getDate() === day) {
                        cell.classList.add('agenda-calendar-day--today');
                    }
                    cell.innerHTML = `<div class="agenda-calendar-day-number">${day}</div>`;

                    dayItems.forEach((item) => {
                        const a = document.createElement('a');
                        a.href = item.edit_url;
                        a.className = `agenda-event-chip status-${item.status}`;
                        const badgeClass = item.status === 'approved'
                            ? 'monthly-calendar-badge--approved'
                            : (item.status === 'rejected'
                                ? 'monthly-calendar-badge--rejected'
                                : (item.status === 'in_review'
                                    ? 'monthly-calendar-badge--in-review'
                                    : 'monthly-calendar-badge--draft'));
                        a.innerHTML = `
                            <span class="agenda-event-chip-title">${item.title}</span>
                            <span class="monthly-calendar-branch">${item.branch || ''}</span>
                            <div class="monthly-calendar-meta">
                                <span class="monthly-calendar-badge ${badgeClass}">${statusLabels[item.status] || item.status}</span>
                                <span class="monthly-calendar-icons">${item.requires_workshops ? '🛠️' : ''}${item.requires_communications ? '📣' : ''}</span>
                            </div>
                        `;
                        cell.appendChild(a);
                    });

                    gridContainer.appendChild(cell);
                }
            }

            module.querySelectorAll('[data-calendar-nav]').forEach((button) => {
                button.addEventListener('click', async () => {
                    currentMonth += button.dataset.calendarNav === 'next' ? 1 : -1;
                    if (currentMonth > 12) { currentMonth = 1; currentYear++; }
                    if (currentMonth < 1) { currentMonth = 12; currentYear--; }
                    await loadCalendar();
                });
            });

            loadCalendar();
        })();
    </script>
@endsection
