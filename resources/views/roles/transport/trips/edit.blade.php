@extends('layouts.app')

@php
    $title = __('app.roles.transport.trips.edit_title');
    $subtitle = __('app.roles.transport.trips.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.trips.edit_details') }}</h2>
            <form method="POST" action="{{ route('role.transport.trips.update', $trip) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.trip_date') }}</label>
                    <input class="form-control" type="date" name="trip_date" value="{{ optional($trip->trip_date)->format('Y-m-d') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.day_name') }}</label>
                    <input class="form-control" name="day_name" value="{{ $trip->day_name }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="scheduled" @selected($trip->status === 'scheduled')>{{ __('app.roles.transport.trips.statuses.scheduled') }}</option>
                        <option value="driver_view" @selected($trip->status === 'driver_view')>{{ __('app.roles.transport.trips.statuses.driver_view') }}</option>
                        <option value="closed" @selected($trip->status === 'closed')>{{ __('app.roles.transport.trips.statuses.closed') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.driver') }}</label>
                    <select class="form-select" name="driver_id" required>
                        <option value="">{{ __('app.roles.transport.trips.fields.driver_placeholder') }}</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}" @selected($trip->driver_id === $driver->id)>
                                {{ $driver->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.vehicle') }}</label>
                    <select class="form-select" name="vehicle_id" required>
                        <option value="">{{ __('app.roles.transport.trips.fields.vehicle_placeholder') }}</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}" @selected($trip->vehicle_id === $vehicle->id)>
                                {{ $vehicle->vehicle_no ?? $vehicle->plate_no }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.notes') }}</label>
                    <textarea class="form-control" name="notes" rows="2">{{ $trip->notes }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.transport.trips.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.segments.title') }}</h2>
            <form method="POST" action="{{ route('role.transport.segments.store', $trip) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.transport.segments.fields.segment_no') }}</label>
                    <input class="form-control" type="number" name="segment_no" min="1" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.segments.fields.location') }}</label>
                    <input class="form-control" name="location" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.transport.segments.fields.depart_time') }}</label>
                    <input class="form-control" type="time" name="depart_time">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.transport.segments.fields.return_time') }}</label>
                    <input class="form-control" type="time" name="return_time">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.segments.fields.team_companion') }}</label>
                    <input class="form-control" name="team_companion">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.segments.fields.notes') }}</label>
                    <input class="form-control" name="notes">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary btn-sm" type="submit">
                        {{ __('app.roles.transport.segments.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.transport.segments.table.segment_no') }}</th>
                            <th>{{ __('app.roles.transport.segments.table.location') }}</th>
                            <th class="text-end">{{ __('app.roles.transport.segments.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trip->segments as $segment)
                            <tr>
                                <td>{{ $segment->segment_no }}</td>
                                <td>{{ $segment->location }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('role.transport.segments.destroy', $segment) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.transport.segments.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">{{ __('app.roles.transport.segments.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.rounds.title') }}</h2>
            <form method="POST" action="{{ route('role.transport.rounds.store', $trip) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.transport.rounds.fields.round_no') }}</label>
                    <input class="form-control" type="number" name="round_no" min="1" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.rounds.fields.location') }}</label>
                    <input class="form-control" name="location" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.transport.rounds.fields.start_time') }}</label>
                    <input class="form-control" type="time" name="start_time">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.transport.rounds.fields.end_time') }}</label>
                    <input class="form-control" type="time" name="end_time">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.rounds.fields.team') }}</label>
                    <input class="form-control" name="team">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.rounds.fields.notes') }}</label>
                    <input class="form-control" name="notes">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary btn-sm" type="submit">
                        {{ __('app.roles.transport.rounds.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.transport.rounds.table.round_no') }}</th>
                            <th>{{ __('app.roles.transport.rounds.table.location') }}</th>
                            <th class="text-end">{{ __('app.roles.transport.rounds.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trip->rounds as $round)
                            <tr>
                                <td>{{ $round->round_no }}</td>
                                <td>{{ $round->location }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('role.transport.rounds.destroy', $round) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.transport.rounds.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">{{ __('app.roles.transport.rounds.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.trips.close_title') }}</h2>
            <form method="POST" action="{{ route('role.transport.trips.close', $trip) }}" class="row g-3">
                @csrf
                @method('PATCH')
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="closed">{{ __('app.roles.transport.trips.statuses.closed') }}</option>
                        <option value="completed">{{ __('app.roles.transport.trips.statuses.completed') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.notes') }}</label>
                    <input class="form-control" name="notes" value="{{ $trip->notes }}">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.transport.trips.actions.close') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
