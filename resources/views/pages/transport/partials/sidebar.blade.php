<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.transport.vehicles.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.transport.vehicles.index') }}">
    {{ __('app.roles.transport.vehicles.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.transport.drivers.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.transport.drivers.index') }}">
    {{ __('app.roles.transport.drivers.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.transport.trips.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.transport.trips.index') }}">
    {{ __('app.roles.transport.trips.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.transport.requests.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.transport.requests.index') }}">
    {{ __('app.roles.transport.requests.title') }}
</a>
