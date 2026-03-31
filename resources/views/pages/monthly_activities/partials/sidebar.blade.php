@can('monthly_plan.view')
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.activities.*') ? 'active' : '' }}" href="{{ route('role.relations.activities.index') }}">
        <span class="nxl-micon"><i class="feather-layers"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.programs.monthly_activities.title') }}</span>
    </a>
</li>
@endcan
@can('monthly_plan.approve')
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.programs.approvals.*') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index') }}">
        <span class="nxl-micon"><i class="feather-check-square"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.programs.monthly_activities.approvals.title') }}</span>
    </a>
</li>
@endcan
