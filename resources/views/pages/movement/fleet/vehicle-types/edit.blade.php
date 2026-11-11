@extends('layouts.app')

@section('title', __('movement.vehicle_types.edit'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-shapes', 'title' => __('movement.vehicle_types.edit')])
    @include('pages.movement.partials.alerts')
    <div class="card event-card">
        <div class="card-body">
            <form method="POST" action="{{ route('movement.vehicle-types.update', $type) }}" class="row g-3">
                @csrf @method('PUT')
                @include('pages.movement.fleet.vehicle-types._fields', ['type' => $type])
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">{{ __('movement.common.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('movement.vehicle-types.index') }}">{{ __('movement.common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('pages.movement.partials.assets')
