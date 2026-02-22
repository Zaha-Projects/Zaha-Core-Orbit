<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.transport.vehicles.*') ? 'active' : '' }}" href="{{ route('role.transport.vehicles.index') }}">
    {{ __('app.roles.transport.vehicles.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.transport.drivers.*') ? 'active' : '' }}" href="{{ route('role.transport.drivers.index') }}">
    {{ __('app.roles.transport.drivers.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.transport.trips.*') ? 'active' : '' }}" href="{{ route('role.transport.trips.index') }}">
    {{ __('app.roles.transport.trips.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.transport.requests.*') ? 'active' : '' }}" href="{{ route('role.transport.requests.index') }}">
    {{ __('app.roles.transport.requests.title') }}
</a>
</li>
