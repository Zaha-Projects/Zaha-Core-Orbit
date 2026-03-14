@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
    $isRtl = app()->getLocale() === 'ar';
    $canManageAgenda = auth()->user()?->hasAnyRole(['relations_manager', 'relations_officer', 'super_admin']);

    $agendaStatusLabel = function (?string $status): string {
        if (!$status) {
            return '-';
        }

        $translated = __('app.roles.relations.agenda.status_labels.' . $status);

        return $translated !== 'app.roles.relations.agenda.status_labels.' . $status ? $translated : $status;
    };

    $agendaEvents = $events->getCollection()->map(function ($event) use ($canManageAgenda, $agendaStatusLabel) {
        $resolvedDate = optional($event->event_date)->format('Y-m-d')
            ?? sprintf('%04d-%02d-%02d', now()->year, $event->month, $event->day);

        return [
            'id' => $event->id,
            'name' => $event->event_name,
            'date' => $resolvedDate,
            'department' => $event->department?->name ?? '-',
            'category' => $event->eventCategory?->name ?? $event->event_category ?? '-',
            'status' => $event->relations_approval_status ?? $event->status,
            'status_label' => $agendaStatusLabel($event->relations_approval_status ?? $event->status),
            'edit_url' => $canManageAgenda ? route('role.relations.agenda.edit', $event) : null,
            'view_url' => route('role.relations.agenda.index', ['year' => optional($event->event_date)->format('Y') ?? now()->year, 'month' => $event->month]),
            'submit_url' => $canManageAgenda ? route('role.relations.agenda.submit', $event) : null,
            'participant_count' => $event->participations->where('entity_type', 'branch')->where('participation_status', 'participant')->count(),
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
                <div class="col-md-2"><input class="form-control" type="number" name="year" placeholder="{{ __('app.enterprise.year') }}" value="{{ request('year') }}"></div>
                <div class="col-md-2"><input class="form-control" type="number" min="1" max="12" name="month" placeholder="{{ __('app.roles.programs.monthly_activities.sync.month') }}" value="{{ request('month') }}"></div>
                <div class="col-md-2"><input class="form-control" name="status" placeholder="{{ __('app.roles.reports.fields.status') }}" value="{{ request('status') }}"></div>
                <div class="col-md-2"><input class="form-control" name="plan_type" placeholder="{{ __('app.roles.relations.agenda.fields_ext.plan_type') }}" value="{{ request('plan_type') }}"></div>
                <div class="col-md-2"><input class="form-control" name="event_type" placeholder="{{ __('app.roles.relations.agenda.fields_ext.event_type') }}" value="{{ request('event_type') }}"></div>
                <div class="col-md-2"><button class="btn btn-outline-primary w-100">{{ __('app.common.filter') }}</button></div>
            </div>
        </form>

        <div class="event-kpi-grid">
            <div class="event-kpi-card">
                <div class="text-muted small">{{ __('app.roles.relations.agenda.title') }}</div>
                <div class="event-kpi-value">{{ $events->count() }}</div>
            </div>
        </div>

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
                                        <td>{{ $event->department?->name ?? '-' }}</td>
                                        <td>{{ $event->eventCategory?->name ?? $event->event_category ?? '-' }}</td>
                                        <td>{{ __('app.roles.relations.agenda.types.' . $event->event_type) }} / {{ __('app.roles.relations.agenda.plans.' . $event->plan_type) }}</td>
                                        <td>
                                            <span class="event-status status-{{ $event->relations_approval_status }}">{{ $agendaStatusLabel($event->relations_approval_status) }}</span>
                                            <span class="event-status status-{{ $event->executive_approval_status }}">{{ $agendaStatusLabel($event->executive_approval_status) }}</span>
                                        </td>
                                        <td>{{ $event->participations->where('entity_type', 'branch')->where('participation_status', 'participant')->count() }}</td>
                                        <td class="text-end">
                                            @if($canManageAgenda)
                                                <div class="event-actions">
                                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.agenda.edit', $event) }}">{{ __('app.roles.relations.agenda.actions.edit') }}</a>
                                                    <form method="POST" action="{{ route('role.relations.agenda.submit', $event) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('app.roles.relations.agenda.actions.submit') }}</button>
                                                    </form>
                                                </div>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-muted">{{ __('app.roles.relations.agenda.table.empty') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="event-mobile-cards">
                        @foreach ($events as $event)
                            <div class="event-mobile-card">
                                <div class="fw-semibold mb-2">{{ $event->event_name }}</div>
                                <div class="event-mobile-row"><span class="text-muted">{{ __('app.roles.relations.agenda.table.event_date') }}</span><span>{{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}</span></div>
                                <div class="event-mobile-row"><span class="text-muted">{{ __('app.roles.relations.agenda.fields_ext.review_status') }}</span><span class="event-status status-{{ $event->relations_approval_status }}">{{ $agendaStatusLabel($event->relations_approval_status) }}</span></div>
                                @if($canManageAgenda)
                                    <div class="event-actions mt-2">
                                        <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.agenda.edit', $event) }}">{{ __('app.roles.relations.agenda.actions.edit') }}</a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
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

                    <div class="agenda-calendar-weekdays" data-calendar-weekdays></div>
                    <div class="agenda-calendar-grid" data-calendar-grid></div>
                </div>
            </div>
        </div>
    </div>

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

            let currentDate = new Date();
            currentDate.setDate(1);

            const weekdaysContainer = module.querySelector('[data-calendar-weekdays]');
            const gridContainer = module.querySelector('[data-calendar-grid]');
            const titleContainer = module.querySelector('[data-calendar-title]');

            function mapDayPosition(jsDayIndex) {
                return isRtl ? 6 - jsDayIndex : jsDayIndex;
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
                        const eventLink = document.createElement(event.edit_url ? 'a' : 'div');
                        if (event.edit_url) {
                            eventLink.href = event.edit_url;
                        }
                        eventLink.className = `agenda-event-chip status-${event.status}`;
                        eventLink.innerHTML = `
                            <span class="agenda-event-chip-title">${event.name}</span>
                            <span class="event-status status-${event.status}">${event.status_label ?? event.status}</span>
                        `;
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
