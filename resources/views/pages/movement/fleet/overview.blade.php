@extends('layouts.app')

@section('title', __('movement.overview.title'))

{{-- Public fleet overview (D7): aggregates only. Never render fleet numbers, plates, per-vehicle status or driver data here. --}}
@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-bus', 'title' => __('movement.overview.title'), 'subtitle' => __('movement.overview.subtitle')])

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card event-card h-100"><div class="card-body text-center">
                <div class="text-muted small">{{ __('movement.overview.active_drivers') }}</div>
                <div class="h3 mb-0" data-testid="active-drivers">{{ $activeDrivers }}</div>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card event-card h-100"><div class="card-body text-center">
                <div class="text-muted small">{{ __('movement.overview.operational') }}</div>
                <div class="h3 mb-0">{{ $types->sum('operational') }}</div>
            </div></div>
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            @if($types->isEmpty())
                @include('pages.movement.partials.empty', ['text' => __('movement.overview.empty')])
            @else
                <div class="table-responsive d-none d-md-block">
                    <table class="table align-middle mb-0">
                        <thead><tr>
                            <th>{{ __('movement.overview.type') }}</th>
                            <th>{{ __('movement.overview.total') }}</th>
                            <th>{{ __('movement.overview.operational') }}</th>
                            <th>{{ __('movement.overview.unavailable') }}</th>
                            <th>{{ __('movement.overview.capacity') }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach($types as $row)
                            <tr>
                                <td>{{ $row['name'] }} <span class="text-muted small">({{ __('movement.size_classes.'.$row['size_class']) }})</span></td>
                                <td>{{ $row['total'] }}</td>
                                <td>{{ $row['operational'] }}</td>
                                <td>{{ $row['unavailable'] }}</td>
                                <td>@foreach($row['capacities'] as $seats => $count)<span class="badge bg-light text-dark me-1">{{ __('movement.overview.capacity_item', ['count' => $count, 'seats' => $seats]) }}</span>@endforeach</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-md-none">
                    @foreach($types as $row)
                        <div class="border rounded p-3 mb-2">
                            <div class="fw-bold mb-1">{{ $row['name'] }}</div>
                            <div class="small">{{ __('movement.overview.total') }}: {{ $row['total'] }} · {{ __('movement.overview.operational') }}: {{ $row['operational'] }} · {{ __('movement.overview.unavailable') }}: {{ $row['unavailable'] }}</div>
                            <div class="small mt-1">@foreach($row['capacities'] as $seats => $count)<span class="badge bg-light text-dark me-1">{{ __('movement.overview.capacity_item', ['count' => $count, 'seats' => $seats]) }}</span>@endforeach</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/event-ui-shared.css') }}">
@endpush
