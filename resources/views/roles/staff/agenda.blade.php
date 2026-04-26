@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.staff.agenda.title');
    $subtitle = __('app.roles.staff.agenda.subtitle');
@endphp

@section('content')
    <div class="event-module">
    <div class="card event-card mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="card event-card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('role.staff.agenda.index') }}" class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.staff.filters.date') }}</label>
                    <input class="form-control" type="date" name="date" value="{{ request('date') }}">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.staff.filters.apply') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.staff.agenda.table.event') }}</th>
                            <th>{{ __('app.roles.staff.agenda.table.date') }}</th>
                            <th>{{ __('app.roles.staff.agenda.table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->event_name }}</td>
                                <td>{{ sprintf('%02d-%02d', $event->month, $event->day) }}</td>
                                <td>
                                    @php($statusLabel = __('app.roles.staff.statuses.' . $event->status))
                                    <span class="event-status status-{{ $event->status }}">{{ $statusLabel !== 'app.roles.staff.statuses.' . $event->status ? $statusLabel : $event->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">{{ __('app.roles.staff.agenda.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
@endsection
