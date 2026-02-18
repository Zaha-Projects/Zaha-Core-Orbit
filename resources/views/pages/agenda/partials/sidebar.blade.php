<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.relations.agenda.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.relations.agenda.index') }}">
    {{ __('app.roles.relations.agenda.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.relations.approvals.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.relations.approvals.index') }}">
    {{ __('app.roles.relations.approvals.title') }}
</a>
