{{--
    Movement navigation group (plan §16, Revision 4). Shows only the items that exist in the current
    phase, each gated by its permission/policy. The legacy transport items stay until Phase 5.
--}}
@php
    $movementUser = auth()->user();
    $movementItems = array_values(array_filter([
        $movementUser?->can('viewOverview', \App\Modules\Movement\Models\Vehicle::class) ? ['route' => 'movement.fleet.overview', 'match' => 'movement.fleet.overview', 'icon' => 'fa-bus', 'label' => __('movement.nav.fleet_overview')] : null,
        $movementUser?->can('viewAny', \App\Modules\Movement\Models\MovementRequest::class) ? ['route' => 'movement.requests.index', 'match' => ['movement.requests.index', 'movement.requests.create', 'movement.requests.edit'], 'icon' => 'fa-paper-plane', 'label' => __('movement.nav.my_requests')] : null,
        $movementUser?->can('review', \App\Modules\Movement\Models\MovementRequest::class) ? ['route' => 'movement.requests.review.index', 'match' => 'movement.requests.review.*', 'icon' => 'fa-clipboard-check', 'label' => __('movement.nav.review_queue')] : null,
        $movementUser?->can('viewAny', \App\Modules\Movement\Models\Vehicle::class) ? ['route' => 'movement.vehicles.index', 'match' => 'movement.vehicles.*', 'icon' => 'fa-truck', 'label' => __('movement.nav.fleet')] : null,
        $movementUser?->can('viewAny', \App\Modules\Movement\Models\VehicleType::class) ? ['route' => 'movement.vehicle-types.index', 'match' => 'movement.vehicle-types.*', 'icon' => 'fa-shapes', 'label' => __('movement.nav.vehicle_types')] : null,
        $movementUser?->can('viewAny', \App\Modules\Movement\Models\Driver::class) ? ['route' => 'movement.drivers.index', 'match' => 'movement.drivers.*', 'icon' => 'fa-id-card', 'label' => __('movement.nav.drivers')] : null,
        $movementUser?->can('viewAny', \App\Modules\Movement\Models\Location::class) ? ['route' => 'movement.locations.index', 'match' => 'movement.locations.*', 'icon' => 'fa-location-dot', 'label' => __('movement.nav.locations')] : null,
    ]));
    $movementMenuOpen = request()->routeIs('movement.*');
    $movementMenuId = $movementMenuId ?? 'movementMenu';
@endphp
@if ($movementItems)
    <li class="side-item {{ $movementMenuOpen ? 'selected' : '' }}">
        <a href="#{{ $movementMenuId }}" data-bs-toggle="collapse" aria-expanded="{{ $movementMenuOpen ? 'true' : 'false' }}" aria-controls="{{ $movementMenuId }}"><i class="fas fa-route"></i><span>{{ __('movement.nav.group') }}</span></a>
    </li>
    <li id="{{ $movementMenuId }}" class="collapse {{ $movementMenuOpen ? 'show' : '' }}">
        <ul class="list-unstyled pe-3 mb-2">
            @foreach ($movementItems as $item)
                <li class="side-item {{ request()->routeIs($item['match']) ? 'selected' : '' }}"><a href="{{ route($item['route']) }}"><i class="fas {{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a></li>
            @endforeach
        </ul>
    </li>
@endif
