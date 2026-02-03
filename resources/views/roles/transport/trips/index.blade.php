@extends('layouts.app')

@php
    $title = __('app.roles.transport.trips.title');
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
            <h2 class="h6 mb-3">{{ __('app.roles.transport.trips.create_title') }}</h2>
            <form method="POST" action="{{ route('role.transport.trips.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.trip_date') }}</label>
                    <input class="form-control" type="date" name="trip_date" value="{{ old('trip_date') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.day_name') }}</label>
                    <input class="form-control" name="day_name" value="{{ old('day_name') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="scheduled">{{ __('app.roles.transport.trips.statuses.scheduled') }}</option>
                        <option value="driver_view">{{ __('app.roles.transport.trips.statuses.driver_view') }}</option>
                        <option value="closed">{{ __('app.roles.transport.trips.statuses.closed') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.driver') }}</label>
                    <select class="form-select" name="driver_id" required>
                        <option value="">{{ __('app.roles.transport.trips.fields.driver_placeholder') }}</option>
                        @foreach ($drivers as $driver)
                            <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.vehicle') }}</label>
                    <select class="form-select" name="vehicle_id" required>
                        <option value="">{{ __('app.roles.transport.trips.fields.vehicle_placeholder') }}</option>
                        @foreach ($vehicles as $vehicle)
                            <option value="{{ $vehicle->id }}">{{ $vehicle->vehicle_no ?? $vehicle->plate_no }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.transport.trips.fields.notes') }}</label>
                    <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.transport.trips.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.trips.list_title') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.transport.trips.table.trip_date') }}</th>
                            <th>{{ __('app.roles.transport.trips.table.driver') }}</th>
                            <th>{{ __('app.roles.transport.trips.table.vehicle') }}</th>
                            <th>{{ __('app.roles.transport.trips.table.status') }}</th>
                            <th class="text-end">{{ __('app.roles.transport.trips.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trips as $trip)
                            <tr>
                                <td>{{ optional($trip->trip_date)->format('Y-m-d') }}</td>
                                <td>{{ $trip->driver?->name ?? '-' }}</td>
                                <td>{{ $trip->vehicle?->vehicle_no ?? $trip->vehicle?->plate_no ?? '-' }}</td>
                                <td>{{ $trip->status }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.transport.trips.edit', $trip) }}">
                                        {{ __('app.roles.transport.trips.actions.edit') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.transport.trips.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
