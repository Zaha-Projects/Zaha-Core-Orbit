@extends('layouts.app')

@section('title', $vehicle->fleet_number)

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-bus', 'title' => __('movement.vehicles.fleet_number').': '.$vehicle->fleet_number])
    @include('pages.movement.partials.alerts')

    <div class="card event-card mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-6 col-md-3">{{ __('movement.vehicles.plate_number') }}</dt><dd class="col-6 col-md-3">{{ $vehicle->plate_number ?? __('movement.common.none') }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.vehicles.type') }}</dt><dd class="col-6 col-md-3">{{ $vehicle->vehicleType?->display_name ?? __('movement.vehicles.unclassified') }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.vehicles.capacity') }}</dt><dd class="col-6 col-md-3">{{ $vehicle->passenger_capacity ?? __('movement.common.none') }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.vehicles.status') }}</dt><dd class="col-6 col-md-3">@include('pages.movement.fleet.vehicles._status', ['vehicle' => $vehicle])</dd>
                <dt class="col-6 col-md-3">{{ __('movement.vehicles.status_reason') }}</dt><dd class="col-6 col-md-3">{{ $vehicle->status_reason ?? __('movement.common.none') }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.vehicles.status_changed') }}</dt><dd class="col-6 col-md-3">{{ $vehicle->status_changed_at ? \App\Modules\Movement\Support\MovementTime::toLocal($vehicle->status_changed_at)->format('Y-m-d H:i') : __('movement.common.none') }}{{ $vehicle->statusChanger ? ' · '.$vehicle->statusChanger->name : '' }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.vehicles.manufacturer') }}</dt><dd class="col-6 col-md-3">{{ trim(($vehicle->manufacturer ?? '').' '.($vehicle->model ?? '').' '.($vehicle->model_year ?? '')) ?: __('movement.common.none') }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.vehicles.home_location') }}</dt><dd class="col-6 col-md-3">{{ $vehicle->homeLocation?->display_name ?? __('movement.common.none') }}</dd>
                @if($vehicle->notes)<dt class="col-12 col-md-3">{{ __('movement.common.notes') }}</dt><dd class="col-12 col-md-9">{{ $vehicle->notes }}</dd>@endif
            </dl>
            <div class="d-flex gap-2 mt-3 flex-wrap">
                <a class="btn btn-outline-secondary" href="{{ route('movement.vehicles.edit', $vehicle) }}">{{ __('movement.common.edit') }}</a>
                <form method="POST" action="{{ route('movement.vehicles.destroy', $vehicle) }}" onsubmit="return confirm({{ json_encode(__('movement.common.confirm_delete')) }})">@csrf @method('DELETE')<button class="btn btn-outline-danger">{{ __('movement.common.delete') }}</button></form>
                <a class="btn btn-link" href="{{ route('movement.vehicles.index') }}">{{ __('movement.common.back') }}</a>
            </div>
        </div>
    </div>

    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="event-section-title"><i class="fas fa-arrows-rotate"></i> {{ __('movement.vehicles.change_status') }}</h2>
            @unless($vehicle->isClassified())<p class="text-warning small">{{ __('movement.validation.classify_first') }}</p>@endunless
            <form method="POST" action="{{ route('movement.vehicles.status', $vehicle) }}" class="row g-3 align-items-end">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label" for="new-status">{{ __('movement.vehicles.status') }}</label>
                    <select class="form-select" id="new-status" name="operational_status" required>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ ($vehicle->operational_status === $status) ? 'selected' : '' }}>{{ __('movement.statuses.'.$status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status-reason">{{ __('movement.vehicles.status_reason') }}</label>
                    <input class="form-control" id="status-reason" name="status_reason" required maxlength="255">
                </div>
                <div class="col-12 col-md-2"><button class="btn btn-primary w-100">{{ __('movement.common.save') }}</button></div>
            </form>
        </div>
    </div>

    @include('pages.movement.partials.unavailability', ['subjectField' => 'vehicle_id', 'subjectId' => $vehicle->id, 'windows' => $windows, 'windowTypes' => $windowTypes, 'canManage' => auth()->user()->can('createFor', [\App\Modules\Movement\Models\Unavailability::class, 'vehicle'])])
</div>
@endsection

@include('pages.movement.partials.assets')
