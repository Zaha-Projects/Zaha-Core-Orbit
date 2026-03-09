<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.transport.vehicles.*') ? 'active' : '' }}" href="{{ route('role.transport.vehicles.index') }}">
        <span class="nxl-micon"><i class="feather-truck"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.transport.vehicles.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.transport.drivers.*') ? 'active' : '' }}" href="{{ route('role.transport.drivers.index') }}">
        <span class="nxl-micon"><i class="feather-user"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.transport.drivers.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.transport.trips.*') ? 'active' : '' }}" href="{{ route('role.transport.trips.index') }}">
        <span class="nxl-micon"><i class="feather-navigation"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.transport.trips.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.transport.requests.*') ? 'active' : '' }}" href="{{ route('role.transport.requests.index') }}">
        <span class="nxl-micon"><i class="feather-file-text"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.transport.requests.title') }}</span>
    </a>
</li>

<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.transport.movements.*') ? 'active' : '' }}" href="{{ route('role.transport.movements.index') }}">
        <span class="nxl-micon"><i class="feather-map-pin"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.transport.movements.title') }}</span>
    </a>
</li>
