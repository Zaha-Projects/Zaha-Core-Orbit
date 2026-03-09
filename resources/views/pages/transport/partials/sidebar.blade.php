@php
    $transportRoutesActive = request()->routeIs('role.transport.*');
@endphp

<li class="nxl-item nxl-hasmenu {{ $transportRoutesActive ? 'active' : '' }}">
    <a href="javascript:void(0);" class="nxl-link {{ $transportRoutesActive ? 'active' : '' }}">
        <span class="nxl-micon"><i class="feather-truck"></i></span>
        <span class="nxl-mtext">{{ __('app.acl.permissions.transport_view') }}</span><span class="nxl-arrow"><i class="feather-chevron-right"></i></span>
    </a>
    <ul class="nxl-submenu">
        @role('transport_officer')
            <li class="nxl-item">
                <a class="nxl-link {{ request()->routeIs('role.transport.vehicles.*') ? 'active' : '' }}" href="{{ route('role.transport.vehicles.index') }}">{{ __('app.roles.transport.vehicles.title') }}</a>
            </li>
            <li class="nxl-item">
                <a class="nxl-link {{ request()->routeIs('role.transport.drivers.*') ? 'active' : '' }}" href="{{ route('role.transport.drivers.index') }}">{{ __('app.roles.transport.drivers.title') }}</a>
            </li>
            <li class="nxl-item">
                <a class="nxl-link {{ request()->routeIs('role.transport.trips.*') ? 'active' : '' }}" href="{{ route('role.transport.trips.index') }}">{{ __('app.roles.transport.trips.title') }}</a>
            </li>
        @endrole
        @role('super_admin')
            <li class="nxl-item">
                <a class="nxl-link {{ request()->routeIs('role.transport.requests.*') ? 'active' : '' }}" href="{{ route('role.transport.requests.index') }}">{{ __('app.roles.transport.requests.title') }}</a>
            </li>
            <li class="nxl-item">
                <a class="nxl-link {{ request()->routeIs('role.transport.movements.*') ? 'active' : '' }}" href="{{ route('role.transport.movements.index') }}">{{ __('app.roles.transport.movements.title') }}</a>
            </li>
        @endrole
    </ul>
</li>
