@extends('layouts.app')

@php
    $title = __('app.roles.transport.vehicles.edit_title');
    $subtitle = __('app.roles.transport.vehicles.subtitle');
@endphp

@section('sidebar')
    @include('pages.transport.partials.sidebar')
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.transport.vehicles.update', $vehicle) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.vehicles.fields.plate_no') }}</label>
                    <input class="form-control" name="plate_no" value="{{ $vehicle->plate_no }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.vehicles.fields.vehicle_no') }}</label>
                    <input class="form-control" name="vehicle_no" value="{{ $vehicle->vehicle_no }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.vehicles.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="available" @selected($vehicle->status === 'available')>{{ __('app.roles.transport.vehicles.statuses.available') }}</option>
                        <option value="in_service" @selected($vehicle->status === 'in_service')>{{ __('app.roles.transport.vehicles.statuses.in_service') }}</option>
                        <option value="maintenance" @selected($vehicle->status === 'maintenance')>{{ __('app.roles.transport.vehicles.statuses.maintenance') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.vehicles.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.transport.vehicles.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($vehicle->branch_id === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.transport.vehicles.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
