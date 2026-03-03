@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp

@section('content')
    <div class="event-module">
        <div class="event-header">
            <div>
                <h1 class="h4 mb-1">{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
            <a class="btn btn-primary" href="{{ route('role.relations.agenda.create') }}">{{ __('app.roles.relations.agenda.actions.create') }}</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="event-kpi-grid">
            <div class="event-kpi-card">
                <div class="text-muted small">{{ __('app.roles.relations.agenda.title') }}</div>
                <div class="event-kpi-value">{{ $events->count() }}</div>
            </div>
        </div>

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
                                @php
                                    $participantCount = $event->participations->where('entity_type', 'branch')->where('participation_status', 'participant')->count();
                                @endphp
                                <tr>
                                    <td>{{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}</td>
                                    <td>{{ $event->event_name }}</td>
                                    <td>{{ $event->department?->name ?? '-' }}</td>
                                    <td>{{ $event->eventCategory?->name ?? $event->event_category ?? '-' }}</td>
                                    <td>{{ __('app.roles.relations.agenda.types.' . $event->event_type) }} / {{ __('app.roles.relations.agenda.plans.' . $event->plan_type) }}</td>
                                    <td>
                                        <span class="event-status status-{{ $event->relations_approval_status }}">{{ $event->relations_approval_status }}</span>
                                        <span class="event-status status-{{ $event->executive_approval_status }}">{{ $event->executive_approval_status }}</span>
                                    </td>
                                    <td>{{ $participantCount }}</td>
                                    <td class="text-end">
                                        <div class="event-actions">
                                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.agenda.edit', $event) }}">{{ __('app.roles.relations.agenda.actions.edit') }}</a>
                                            <form method="POST" action="{{ route('role.relations.agenda.submit', $event) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('app.roles.relations.agenda.actions.submit') }}</button>
                                            </form>
                                        </div>
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
                            <div class="event-mobile-row"><span class="text-muted">{{ __('app.roles.relations.agenda.fields_ext.review_status') }}</span><span class="event-status status-{{ $event->status }}">{{ $event->status }}</span></div>
                            <div class="event-actions mt-2">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.agenda.edit', $event) }}">{{ __('app.roles.relations.agenda.actions.edit') }}</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
