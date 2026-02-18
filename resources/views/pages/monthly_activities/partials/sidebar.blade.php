<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.programs.activities.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.programs.activities.index') }}">
    {{ __('app.roles.programs.monthly_activities.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.programs.approvals.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.programs.approvals.index') }}">
    {{ __('app.roles.programs.monthly_activities.approvals.title') }}
</a>
