@extends('layouts.app')

@php
    $title = __('app.roles.transport.vehicles.title');
    $subtitle = __('app.roles.transport.vehicles.subtitle');
@endphp

@section('sidebar')
    @include('pages.transport.partials.sidebar')
@endsection

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
            <h2 class="h6 mb-3">{{ __('app.roles.transport.vehicles.create_title') }}</h2>
            <form method="POST" action="{{ route('role.transport.vehicles.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.vehicles.fields.plate_no') }}</label>
                    <input class="form-control" name="plate_no" value="{{ old('plate_no') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.vehicles.fields.vehicle_no') }}</label>
                    <input class="form-control" name="vehicle_no" value="{{ old('vehicle_no') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.transport.vehicles.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="available">{{ __('app.roles.transport.vehicles.statuses.available') }}</option>
                        <option value="in_service">{{ __('app.roles.transport.vehicles.statuses.in_service') }}</option>
                        <option value="maintenance">{{ __('app.roles.transport.vehicles.statuses.maintenance') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.transport.vehicles.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.transport.vehicles.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.transport.vehicles.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.transport.vehicles.list_title') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.transport.vehicles.table.vehicle') }}</th>
                            <th>{{ __('app.roles.transport.vehicles.table.status') }}</th>
                            <th>{{ __('app.roles.transport.vehicles.table.branch') }}</th>
                            <th class="text-end">{{ __('app.roles.transport.vehicles.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($vehicles as $vehicle)
                            <tr>
                                <td>{{ $vehicle->vehicle_no ?? $vehicle->plate_no }}</td>
                                <td>{{ $vehicle->status }}</td>
                                <td>{{ $vehicle->branch?->name ?? '-' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.transport.vehicles.edit', $vehicle) }}">
                                        {{ __('app.roles.transport.vehicles.actions.edit') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">{{ __('app.roles.transport.vehicles.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
