@extends('layouts.app')

@php($editing = $vehicle->exists)
@section('title', $editing ? __('movement.vehicles.edit') : __('movement.vehicles.add'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-bus', 'title' => $editing ? __('movement.vehicles.edit') : __('movement.vehicles.add')])
    @include('pages.movement.partials.alerts')
    <div class="card event-card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('movement.vehicles.update', $vehicle) : route('movement.vehicles.store') }}" class="row g-3">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="col-6 col-md-3">
                    <label class="form-label" for="fleet-number">{{ __('movement.vehicles.fleet_number') }}</label>
                    <input class="form-control" id="fleet-number" name="fleet_number" value="{{ old('fleet_number', $vehicle->fleet_number) }}" required maxlength="50">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="plate-number">{{ __('movement.vehicles.plate_number') }}</label>
                    <input class="form-control" id="plate-number" name="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" maxlength="50">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="vehicle-type-id">{{ __('movement.vehicles.type') }}</label>
                    <select class="form-select" id="vehicle-type-id" name="vehicle_type_id" required>
                        <option value="">—</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" data-default-capacity="{{ $type->default_capacity }}" {{ ((int) old('vehicle_type_id', $vehicle->vehicle_type_id) === $type->id) ? 'selected' : '' }}>{{ $type->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="capacity">{{ __('movement.vehicles.capacity') }}</label>
                    <input class="form-control" id="capacity" type="number" min="1" max="1000" name="passenger_capacity" value="{{ old('passenger_capacity', $vehicle->passenger_capacity) }}" required data-movement-capacity>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="manufacturer">{{ __('movement.vehicles.manufacturer') }}</label>
                    <input class="form-control" id="manufacturer" name="manufacturer" value="{{ old('manufacturer', $vehicle->manufacturer) }}" maxlength="100">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="model">{{ __('movement.vehicles.model') }}</label>
                    <input class="form-control" id="model" name="model" value="{{ old('model', $vehicle->model) }}" maxlength="100">
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label" for="model-year">{{ __('movement.vehicles.model_year') }}</label>
                    <input class="form-control" id="model-year" type="number" min="1950" max="2100" name="model_year" value="{{ old('model_year', $vehicle->model_year) }}">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="home-location">{{ __('movement.vehicles.home_location') }}</label>
                    <select class="form-select" id="home-location" name="home_location_id">
                        <option value="">—</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}" {{ ((int) old('home_location_id', $vehicle->home_location_id) === $location->id) ? 'selected' : '' }}>{{ $location->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                @unless($editing)
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="status">{{ __('movement.vehicles.status') }}</label>
                        <select class="form-select" id="status" name="operational_status" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" {{ (old('operational_status', 'operational') === $status) ? 'selected' : '' }}>{{ __('movement.statuses.'.$status) }}</option>
                            @endforeach
                        </select>
                    </div>
                @endunless
                <div class="col-12">
                    <label class="form-label" for="notes">{{ __('movement.common.notes') }}</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" maxlength="5000">{{ old('notes', $vehicle->notes) }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">{{ __('movement.common.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ $editing ? route('movement.vehicles.show', $vehicle) : route('movement.vehicles.index') }}">{{ __('movement.common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('pages.movement.partials.assets')
