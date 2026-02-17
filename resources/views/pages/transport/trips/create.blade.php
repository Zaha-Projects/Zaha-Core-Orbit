@extends('layouts.app')

@php
    $title = __('app.roles.transport.trips.create_title');
    $subtitle = __('app.roles.transport.trips.subtitle');
@endphp

@section('sidebar')
    @include('pages.transport.partials.sidebar')
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
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
@endsection
