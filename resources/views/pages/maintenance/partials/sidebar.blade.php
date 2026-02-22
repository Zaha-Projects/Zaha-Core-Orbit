<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.maintenance.requests.*') ? 'active' : '' }}" href="{{ route('role.maintenance.requests.index') }}">
    {{ __('app.roles.maintenance.requests.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.maintenance.approvals.*') ? 'active' : '' }}" href="{{ route('role.maintenance.approvals.index') }}">
    {{ __('app.roles.maintenance.approvals.title') }}
</a>
</li>
