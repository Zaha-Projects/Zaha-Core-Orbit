@extends('layouts.app')

@section('title', __('movement.vehicles.title'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-bus', 'title' => __('movement.vehicles.title'), 'subtitle' => __('movement.vehicles.subtitle'), 'actionUrl' => route('movement.vehicles.create'), 'actionLabel' => __('movement.vehicles.add')])
    @include('pages.movement.partials.alerts')

    @if($needsVerification->isNotEmpty())
        <div class="card event-card mb-4 border-warning">
            <div class="card-body">
                <h2 class="event-section-title"><i class="fas fa-triangle-exclamation text-warning"></i> {{ __('movement.common.needs_verification') }} ({{ $needsVerification->count() }})</h2>
                <p class="text-muted small">{{ __('movement.common.needs_verification_hint') }}</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($needsVerification as $item)
                        <a class="btn btn-sm btn-outline-warning" href="{{ route('movement.vehicles.show', $item) }}">{{ $item->fleet_number }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card event-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="vehicle-search">{{ __('movement.common.search') }}</label>
                    <input class="form-control" id="vehicle-search" name="search" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="vehicle-status">{{ __('movement.vehicles.status') }}</label>
                    <select class="form-select" id="vehicle-status" name="status">
                        <option value="">{{ __('movement.common.all') }}</option>
                        @foreach(\App\Modules\Movement\Models\Vehicle::STATUSES as $status)
                            <option value="{{ $status }}" {{ (($filters['status'] ?? '') === $status) ? 'selected' : '' }}>{{ __('movement.statuses.'.$status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="vehicle-type">{{ __('movement.vehicles.type') }}</label>
                    <select class="form-select" id="vehicle-type" name="vehicle_type_id">
                        <option value="">{{ __('movement.common.all') }}</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ ((int) ($filters['vehicle_type_id'] ?? 0) === $type->id) ? 'selected' : '' }}>{{ $type->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex flex-column gap-2">
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="vehicle-trashed" name="trashed" value="1" {{ ($filters['trashed'] ?? false) ? 'checked' : '' }}><label class="form-check-label" for="vehicle-trashed">{{ __('movement.common.show_deleted') }}</label></div>
                    <button class="btn btn-outline-primary">{{ __('movement.common.apply') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            @if($vehicles->isEmpty())
                @include('pages.movement.partials.empty', ['text' => __('movement.vehicles.empty')])
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table align-middle mb-0">
                        <thead><tr>
                            <th>{{ __('movement.vehicles.fleet_number') }}</th>
                            <th>{{ __('movement.vehicles.plate_number') }}</th>
                            <th>{{ __('movement.vehicles.type') }}</th>
                            <th>{{ __('movement.vehicles.capacity') }}</th>
                            <th>{{ __('movement.vehicles.status') }}</th>
                            <th>{{ __('movement.common.actions') }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach($vehicles as $vehicle)
                            <tr>
                                <td>{{ $vehicle->fleet_number }}</td>
                                <td>{{ $vehicle->plate_number ?? __('movement.common.none') }}</td>
                                <td>{{ $vehicle->vehicleType?->display_name ?? __('movement.vehicles.unclassified') }}</td>
                                <td>{{ $vehicle->passenger_capacity ?? __('movement.common.none') }}</td>
                                <td>@include('pages.movement.fleet.vehicles._status', ['vehicle' => $vehicle])</td>
                                <td>@include('pages.movement.fleet.vehicles._actions', ['vehicle' => $vehicle])</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-md-none">
                    @foreach($vehicles as $vehicle)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between gap-2"><strong>{{ $vehicle->fleet_number }}</strong>@include('pages.movement.fleet.vehicles._status', ['vehicle' => $vehicle])</div>
                            <div class="small text-muted">{{ $vehicle->plate_number ?? __('movement.common.none') }} · {{ $vehicle->vehicleType?->display_name ?? __('movement.vehicles.unclassified') }} · {{ $vehicle->passenger_capacity ?? __('movement.common.none') }}</div>
                            <div class="mt-2">@include('pages.movement.fleet.vehicles._actions', ['vehicle' => $vehicle])</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="mt-3">{{ $vehicles->links('pagination::bootstrap-5') }}</div>
</div>
@endsection

@include('pages.movement.partials.assets')
