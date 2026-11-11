@extends('layouts.app')

@section('title', __('movement.locations.title'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-location-dot', 'title' => __('movement.locations.title'), 'subtitle' => __('movement.locations.subtitle'), 'actionUrl' => route('movement.locations.create'), 'actionLabel' => __('movement.locations.add')])
    @include('pages.movement.partials.alerts')

    <div class="card event-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-12 col-md-8">
                    <label class="form-label" for="location-search">{{ __('movement.common.search') }}</label>
                    <input class="form-control" id="location-search" name="search" value="{{ $search }}">
                </div>
                <div class="col-12 col-md-4 d-flex flex-column gap-2">
                    <div class="form-check"><input class="form-check-input" type="checkbox" id="location-trashed" name="trashed" value="1" {{ ($trashed) ? 'checked' : '' }}><label class="form-check-label" for="location-trashed">{{ __('movement.common.show_deleted') }}</label></div>
                    <button class="btn btn-outline-primary">{{ __('movement.common.apply') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            @forelse($locations as $location)
                @php($mapUrl = \App\Support\GoogleMaps::navigationUrl($location->maps_url, $location->latitude !== null ? $location->latitude.','.$location->longitude : $location->display_name, $location->address))
                <div class="d-flex justify-content-between align-items-start gap-2 border-bottom py-2 flex-wrap">
                    <div>
                        <strong>{{ $location->display_name }}</strong>
                        @unless($location->is_active)<span class="badge bg-secondary">{{ __('movement.common.inactive') }}</span>@endunless
                        @if($location->trashed())<span class="badge bg-danger">{{ __('movement.common.deleted') }}</span>@endif
                        <span class="d-block small text-muted">{{ $location->address ?? '' }}{{ $location->branch ? ' · '.$location->branch->name : '' }}</span>
                        @if($location->latitude !== null)<span class="d-block small text-muted" dir="ltr">{{ $location->latitude }}, {{ $location->longitude }}</span>@endif
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        @if($mapUrl)<a class="btn btn-sm btn-outline-secondary" href="{{ $mapUrl }}" target="_blank" rel="noopener">{{ __('movement.locations.open_map') }}</a>@endif
                        @if($location->trashed())
                            <form method="POST" action="{{ route('movement.locations.restore', $location) }}">@csrf<button class="btn btn-sm btn-outline-success">{{ __('movement.common.restore') }}</button></form>
                        @else
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('movement.locations.edit', $location) }}">{{ __('movement.common.edit') }}</a>
                            <form method="POST" action="{{ route('movement.locations.destroy', $location) }}" onsubmit="return confirm({{ json_encode(__('movement.common.confirm_delete')) }})">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('movement.common.delete') }}</button></form>
                        @endif
                    </div>
                </div>
            @empty
                @include('pages.movement.partials.empty', ['text' => __('movement.locations.empty')])
            @endforelse
        </div>
    </div>
    <div class="mt-3">{{ $locations->links('pagination::bootstrap-5') }}</div>
</div>
@endsection

@include('pages.movement.partials.assets')
