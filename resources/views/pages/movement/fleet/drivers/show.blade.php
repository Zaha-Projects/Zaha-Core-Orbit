@extends('layouts.app')

@section('title', $driver->name)

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-id-card', 'title' => $driver->name])
    @include('pages.movement.partials.alerts')
    @php($canManage = auth()->user()->can('update', $driver))

    <div class="card event-card mb-4">
        <div class="card-body">
            <dl class="row mb-0">
                <dt class="col-6 col-md-3">{{ __('movement.drivers.phone') }}</dt><dd class="col-6 col-md-3" dir="ltr">{{ $driver->phone ?? __('movement.common.none') }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.drivers.status') }}</dt><dd class="col-6 col-md-3">{{ __('movement.driver_statuses.'.$driver->employment_status) }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.drivers.licence_number') }}</dt><dd class="col-6 col-md-3">{{ $driver->licence_number ?? __('movement.common.none') }}{{ $driver->licence_category ? ' ('.$driver->licence_category.')' : '' }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.drivers.licence') }}</dt><dd class="col-6 col-md-3">@include('pages.movement.fleet.drivers._licence', ['driver' => $driver])</dd>
                @if($driver->notes)<dt class="col-12 col-md-3">{{ __('movement.common.notes') }}</dt><dd class="col-12 col-md-9">{{ $driver->notes }}</dd>@endif
            </dl>
            @if($canManage)
                <div class="d-flex gap-2 mt-3 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('movement.drivers.edit', $driver) }}">{{ __('movement.common.edit') }}</a>
                    <form method="POST" action="{{ route('movement.drivers.destroy', $driver) }}" onsubmit="return confirm({{ json_encode(__('movement.common.confirm_delete')) }})">@csrf @method('DELETE')<button class="btn btn-outline-danger">{{ __('movement.common.delete') }}</button></form>
                    <a class="btn btn-link" href="{{ route('movement.drivers.index') }}">{{ __('movement.common.back') }}</a>
                </div>
            @endif
        </div>
    </div>

    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="event-section-title"><i class="fas fa-link"></i> {{ __('movement.drivers.linked_user') }}</h2>
            @if($driver->user)
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <span>{{ $driver->user->name }} <span class="text-muted small" dir="ltr">{{ $driver->user->email }}</span></span>
                    @can('link', $driver)
                        <form method="POST" action="{{ route('movement.drivers.unlink', $driver) }}">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('movement.drivers.unlink') }}</button></form>
                    @endcan
                </div>
            @else
                <p class="text-muted">{{ __('movement.drivers.not_linked') }}</p>
                @can('link', $driver)
                    <p class="small text-muted">{{ __('movement.drivers.link_hint') }}</p>
                    <form method="GET" action="{{ route('movement.drivers.show', $driver) }}" class="d-flex gap-2 mb-3">
                        <input class="form-control" name="user_search" value="{{ $userSearch }}" placeholder="{{ __('movement.common.search') }}" aria-label="{{ __('movement.common.search') }}">
                        <button class="btn btn-outline-primary">{{ __('movement.common.search') }}</button>
                    </form>
                    @if($userSearch !== '')
                        @forelse($candidates as $candidate)
                            <form method="POST" action="{{ route('movement.drivers.link', $driver) }}" class="d-flex justify-content-between align-items-center border-top py-2 gap-2">
                                @csrf
                                <input type="hidden" name="user_id" value="{{ $candidate->id }}">
                                <span>{{ $candidate->name }} <span class="text-muted small" dir="ltr">{{ $candidate->email }}</span></span>
                                <button class="btn btn-sm btn-primary">{{ __('movement.drivers.link') }}</button>
                            </form>
                        @empty
                            @include('pages.movement.partials.empty', ['text' => __('movement.drivers.no_candidates')])
                        @endforelse
                    @endif
                @endcan
            @endif
        </div>
    </div>

    @include('pages.movement.partials.unavailability', ['subjectField' => 'driver_id', 'subjectId' => $driver->id, 'windows' => $windows, 'windowTypes' => $windowTypes, 'canManage' => auth()->user()->can('createFor', [\App\Modules\Movement\Models\Unavailability::class, 'driver'])])
</div>
@endsection

@include('pages.movement.partials.assets')
