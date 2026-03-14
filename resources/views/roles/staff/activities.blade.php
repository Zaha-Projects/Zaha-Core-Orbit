@extends('layouts.app')

@php
    $title = __('app.roles.staff.activities.title');
    $subtitle = __('app.roles.staff.activities.subtitle');
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
            <form method="GET" action="{{ route('role.staff.activities.index') }}" class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.staff.filters.date') }}</label>
                    <input class="form-control" type="date" name="date" value="{{ request('date') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.staff.filters.branch') }}</label>
                    <select class="form-select" name="branch_id">
                        <option value="">{{ __('app.roles.staff.filters.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) $branch->id === request('branch_id'))>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
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
                            <th>{{ __('app.roles.staff.activities.table.activity') }}</th>
                            <th>{{ __('app.roles.staff.activities.table.date') }}</th>
                            <th>{{ __('app.roles.staff.activities.table.branch') }}</th>
                            <th>{{ __('app.roles.staff.activities.table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td>{{ $activity->title }}</td>
                                <td>{{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</td>
                                <td>{{ $activity->branch?->name ?? '-' }}</td>
                                <td>
                                    @php($statusLabel = __('app.roles.staff.statuses.' . $activity->status))
                                    <span class="event-status status-{{ $activity->status }}">{{ $statusLabel !== 'app.roles.staff.statuses.' . $activity->status ? $statusLabel : $activity->status }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">{{ __('app.roles.staff.activities.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
@endsection
