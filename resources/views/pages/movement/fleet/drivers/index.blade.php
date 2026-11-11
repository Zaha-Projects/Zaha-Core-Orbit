@extends('layouts.app')

@section('title', __('movement.drivers.title'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-id-card', 'title' => __('movement.drivers.title'), 'subtitle' => __('movement.drivers.subtitle'), 'actionUrl' => auth()->user()->can('create', \App\Modules\Movement\Models\Driver::class) ? route('movement.drivers.create') : null, 'actionLabel' => __('movement.drivers.add')])
    @include('pages.movement.partials.alerts')

    @if($needsVerification->isNotEmpty())
        <div class="card event-card mb-4 border-warning">
            <div class="card-body">
                <h2 class="event-section-title"><i class="fas fa-triangle-exclamation text-warning"></i> {{ __('movement.common.needs_verification') }} ({{ $needsVerification->count() }})</h2>
                <p class="text-muted small">{{ __('movement.common.needs_verification_hint') }}</p>
                <div class="d-flex flex-wrap gap-2">
                    @foreach($needsVerification as $item)
                        <a class="btn btn-sm btn-outline-warning" href="{{ route('movement.drivers.show', $item) }}">{{ $item->name }}</a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="card event-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-5">
                    <label class="form-label" for="driver-search">{{ __('movement.common.search') }}</label>
                    <input class="form-control" id="driver-search" name="search" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="driver-status">{{ __('movement.drivers.status') }}</label>
                    <select class="form-select" id="driver-status" name="status">
                        <option value="">{{ __('movement.common.all') }}</option>
                        @foreach(\App\Modules\Movement\Models\Driver::STATUSES as $status)
                            <option value="{{ $status }}" {{ (($filters['status'] ?? '') === $status) ? 'selected' : '' }}>{{ __('movement.driver_statuses.'.$status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 d-flex flex-column gap-2">
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="driver-trashed" name="trashed" value="1" {{ ($filters['trashed'] ?? false) ? 'checked' : '' }}><label class="form-check-label" for="driver-trashed">{{ __('movement.common.show_deleted') }}</label></div>
                    <button class="btn btn-outline-primary">{{ __('movement.common.apply') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            @if($drivers->isEmpty())
                @include('pages.movement.partials.empty', ['text' => __('movement.drivers.empty')])
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table align-middle mb-0">
                        <thead><tr>
                            <th>{{ __('movement.drivers.name') }}</th>
                            <th>{{ __('movement.drivers.phone') }}</th>
                            <th>{{ __('movement.drivers.licence') }}</th>
                            <th>{{ __('movement.drivers.status') }}</th>
                            <th>{{ __('movement.drivers.linked_user') }}</th>
                            <th>{{ __('movement.common.actions') }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach($drivers as $driver)
                            <tr>
                                <td>{{ $driver->name }}</td>
                                <td dir="ltr">{{ $driver->phone ?? __('movement.common.none') }}</td>
                                <td>@include('pages.movement.fleet.drivers._licence', ['driver' => $driver])</td>
                                <td><span class="badge bg-light text-dark">{{ __('movement.driver_statuses.'.$driver->employment_status) }}</span>@if($driver->trashed()) <span class="badge bg-danger">{{ __('movement.common.deleted') }}</span>@endif</td>
                                <td>{{ $driver->user?->name ?? __('movement.drivers.not_linked') }}</td>
                                <td>@include('pages.movement.fleet.drivers._actions', ['driver' => $driver])</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-md-none">
                    @foreach($drivers as $driver)
                        <div class="border rounded p-3 mb-2">
                            <div class="d-flex justify-content-between gap-2"><strong>{{ $driver->name }}</strong><span class="badge bg-light text-dark">{{ __('movement.driver_statuses.'.$driver->employment_status) }}</span></div>
                            <div class="small text-muted" dir="ltr">{{ $driver->phone ?? __('movement.common.none') }}</div>
                            <div class="mt-1">@include('pages.movement.fleet.drivers._licence', ['driver' => $driver])</div>
                            <div class="mt-2">@include('pages.movement.fleet.drivers._actions', ['driver' => $driver])</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="mt-3">{{ $drivers->links('pagination::bootstrap-5') }}</div>
</div>
@endsection

@include('pages.movement.partials.assets')
