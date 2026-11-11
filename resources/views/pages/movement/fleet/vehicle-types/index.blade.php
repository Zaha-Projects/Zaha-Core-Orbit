@extends('layouts.app')

@section('title', __('movement.vehicle_types.title'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-shapes', 'title' => __('movement.vehicle_types.title')])
    @include('pages.movement.partials.alerts')

    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="event-section-title"><i class="fas fa-plus"></i> {{ __('movement.vehicle_types.add') }}</h2>
            <form method="POST" action="{{ route('movement.vehicle-types.store') }}" class="row g-3">
                @csrf
                @include('pages.movement.fleet.vehicle-types._fields', ['type' => new \App\Modules\Movement\Models\VehicleType(['is_active' => true])])
                <div class="col-12"><button class="btn btn-primary">{{ __('movement.common.save') }}</button></div>
            </form>
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            @forelse($types as $type)
                <div class="d-flex justify-content-between align-items-center gap-2 border-bottom py-2 flex-wrap {{ $type->trashed() ? 'opacity-50' : '' }}">
                    <div>
                        <strong>{{ $type->display_name }}</strong>
                        <span class="text-muted small">({{ $type->code }} · {{ __('movement.size_classes.'.$type->size_class) }}{{ $type->default_capacity ? ' · '.$type->default_capacity : '' }})</span>
                        <span class="badge bg-light text-dark">{{ __('movement.vehicle_types.vehicles_count') }}: {{ $type->vehicles_count }}</span>
                        @unless($type->is_active)<span class="badge bg-secondary">{{ __('movement.common.inactive') }}</span>@endunless
                        @if($type->trashed())<span class="badge bg-danger">{{ __('movement.common.deleted') }}</span>@endif
                    </div>
                    <div class="d-flex gap-2">
                        @if($type->trashed())
                            <form method="POST" action="{{ route('movement.vehicle-types.restore', $type) }}">@csrf<button class="btn btn-sm btn-outline-success">{{ __('movement.common.restore') }}</button></form>
                        @else
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('movement.vehicle-types.edit', $type) }}">{{ __('movement.common.edit') }}</a>
                            <form method="POST" action="{{ route('movement.vehicle-types.destroy', $type) }}" onsubmit="return confirm({{ json_encode(__('movement.common.confirm_delete')) }})">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">{{ __('movement.common.delete') }}</button></form>
                        @endif
                    </div>
                </div>
            @empty
                @include('pages.movement.partials.empty', ['text' => __('movement.vehicle_types.empty')])
            @endforelse
        </div>
    </div>
</div>
@endsection

@include('pages.movement.partials.assets')
