<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.maintenance.requests.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.maintenance.requests.index') }}">
    {{ __('app.roles.maintenance.requests.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.maintenance.approvals.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.maintenance.approvals.index') }}">
    {{ __('app.roles.maintenance.approvals.title') }}
</a>
