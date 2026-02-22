<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.relations.agenda.*') ? 'active' : '' }}" href="{{ route('role.relations.agenda.index') }}">
    {{ __('app.roles.relations.agenda.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.relations.approvals.*') ? 'active' : '' }}" href="{{ route('role.relations.approvals.index') }}">
    {{ __('app.roles.relations.approvals.title') }}
</a>
</li>
