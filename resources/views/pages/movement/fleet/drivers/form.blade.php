@extends('layouts.app')

@php($editing = $driver->exists)
@section('title', $editing ? __('movement.drivers.edit') : __('movement.drivers.add'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-id-card', 'title' => $editing ? __('movement.drivers.edit') : __('movement.drivers.add')])
    @include('pages.movement.partials.alerts')
    @php($statuses = $editing && $driver->employment_status === \App\Modules\Movement\Models\Driver::STATUS_PENDING_VERIFICATION ? \App\Modules\Movement\Models\Driver::STATUSES : [\App\Modules\Movement\Models\Driver::STATUS_ACTIVE, \App\Modules\Movement\Models\Driver::STATUS_INACTIVE])
    <div class="card event-card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('movement.drivers.update', $driver) : route('movement.drivers.store') }}" class="row g-3">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="col-12 col-md-6">
                    <label class="form-label" for="driver-name">{{ __('movement.drivers.name') }}</label>
                    <input class="form-control" id="driver-name" name="name" value="{{ old('name', $driver->name) }}" required maxlength="255">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="driver-phone">{{ __('movement.drivers.phone') }}</label>
                    <input class="form-control" id="driver-phone" name="phone" value="{{ old('phone', $driver->phone) }}" maxlength="30" dir="ltr">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="licence-number">{{ __('movement.drivers.licence_number') }}</label>
                    <input class="form-control" id="licence-number" name="licence_number" value="{{ old('licence_number', $driver->licence_number) }}" maxlength="50">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="licence-category">{{ __('movement.drivers.licence_category') }}</label>
                    <input class="form-control" id="licence-category" name="licence_category" value="{{ old('licence_category', $driver->licence_category) }}" maxlength="50">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="licence-expires">{{ __('movement.drivers.licence_expires_on') }}</label>
                    <input class="form-control" id="licence-expires" name="licence_expires_on" value="{{ old('licence_expires_on', $driver->licence_expires_on?->format('Y-m-d')) }}" data-movement-date autocomplete="off">
                </div>
                <div class="col-6 col-md-4">
                    <label class="form-label" for="employment-status">{{ __('movement.drivers.status') }}</label>
                    <select class="form-select" id="employment-status" name="employment_status" required>
                        @foreach($statuses as $status)
                            <option value="{{ $status }}" {{ (old('employment_status', $driver->employment_status) === $status) ? 'selected' : '' }}>{{ __('movement.driver_statuses.'.$status) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label" for="driver-notes">{{ __('movement.common.notes') }}</label>
                    <textarea class="form-control" id="driver-notes" name="notes" rows="3" maxlength="5000">{{ old('notes', $driver->notes) }}</textarea>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">{{ __('movement.common.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ $editing ? route('movement.drivers.show', $driver) : route('movement.drivers.index') }}">{{ __('movement.common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('pages.movement.partials.assets')
