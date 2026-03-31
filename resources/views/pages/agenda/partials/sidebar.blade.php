@can('agenda.view')
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.agenda.*') ? 'active' : '' }}" href="{{ route('role.relations.agenda.index') }}">
        <span class="nxl-micon"><i class="feather-calendar"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.relations.agenda.title') }}</span>
    </a>
</li>
@endcan
@can('agenda.approve')
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.approvals.*') ? 'active' : '' }}" href="{{ route('role.relations.approvals.index') }}">
        <span class="nxl-micon"><i class="feather-check-square"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.relations.approvals.title') }}</span>
    </a>
</li>
@endcan
