@extends('layouts.app')

@php($editing = $location->exists)
@section('title', $editing ? __('movement.locations.edit') : __('movement.locations.add'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-location-dot', 'title' => $editing ? __('movement.locations.edit') : __('movement.locations.add')])
    @include('pages.movement.partials.alerts')
    <div class="card event-card">
        <div class="card-body">
            <form method="POST" action="{{ $editing ? route('movement.locations.update', $location) : route('movement.locations.store') }}" class="row g-3">
                @csrf
                @if($editing) @method('PUT') @endif
                <div class="col-12 col-md-6">
                    <label class="form-label" for="location-name-ar">{{ __('movement.locations.name_ar') }}</label>
                    <input class="form-control" id="location-name-ar" name="name_ar" value="{{ old('name_ar', $location->name_ar) }}" maxlength="255">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="location-name-en">{{ __('movement.locations.name_en') }}</label>
                    <input class="form-control" id="location-name-en" name="name_en" value="{{ old('name_en', $location->name_en) }}" maxlength="255" dir="ltr">
                </div>
                <div class="col-12">
                    <label class="form-label" for="location-address">{{ __('movement.locations.address') }}</label>
                    <input class="form-control" id="location-address" name="address" value="{{ old('address', $location->address) }}" maxlength="255">
                </div>
                <div class="col-12">
                    <label class="form-label" for="location-maps-url">{{ __('movement.locations.maps_url') }}</label>
                    <input class="form-control" id="location-maps-url" type="url" name="maps_url" value="{{ old('maps_url', $location->maps_url) }}" maxlength="2000" dir="ltr">
                    <div class="form-text">{{ __('movement.locations.maps_url_hint') }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="location-lat">{{ __('movement.locations.latitude') }}</label>
                    <input class="form-control" id="location-lat" name="latitude" value="{{ old('latitude', $location->latitude) }}" inputmode="decimal" dir="ltr">
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label" for="location-lng">{{ __('movement.locations.longitude') }}</label>
                    <input class="form-control" id="location-lng" name="longitude" value="{{ old('longitude', $location->longitude) }}" inputmode="decimal" dir="ltr">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="location-place">{{ __('movement.locations.google_place_id') }}</label>
                    <input class="form-control" id="location-place" name="google_place_id" value="{{ old('google_place_id', $location->google_place_id) }}" maxlength="255" dir="ltr">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="location-branch">{{ __('movement.locations.branch') }}</label>
                    <select class="form-select" id="location-branch" name="branch_id">
                        <option value="">{{ __('movement.locations.no_branch') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ ((int) old('branch_id', $location->branch_id) === $branch->id) ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6 d-flex align-items-end">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input class="form-check-input" type="checkbox" id="location-active" name="is_active" value="1" {{ (old('is_active', $location->is_active)) ? 'checked' : '' }}>
                        <label class="form-check-label" for="location-active">{{ __('movement.common.active') }}</label>
                    </div>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button class="btn btn-primary">{{ __('movement.common.save') }}</button>
                    <a class="btn btn-outline-secondary" href="{{ route('movement.locations.index') }}">{{ __('movement.common.cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@include('pages.movement.partials.assets')
