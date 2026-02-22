<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.programs.activities.*') ? 'active' : '' }}" href="{{ route('role.programs.activities.index') }}">
    {{ __('app.roles.programs.monthly_activities.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.programs.approvals.*') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index') }}">
    {{ __('app.roles.programs.monthly_activities.approvals.title') }}
</a>
</li>
