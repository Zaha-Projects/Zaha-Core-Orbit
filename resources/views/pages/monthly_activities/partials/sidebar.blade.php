@canany(['monthly_activities.view','monthly_plan.view'])
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.activities.*') && request('scope') !== 'all_branches' ? 'active' : '' }}" href="{{ route('role.relations.activities.index') }}">
        <span class="nxl-micon"><i class="feather-layers"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.programs.monthly_activities.title') }}</span>
    </a>
</li>
@endcanany
@can('monthly_activities.view_other_branches')
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.relations.activities.*') && request('scope') === 'all_branches' ? 'active' : '' }}" href="{{ route('role.relations.activities.index', ['scope' => 'all_branches']) }}">
        <span class="nxl-micon"><i class="feather-grid"></i></span>
        <span class="nxl-mtext">الخطط الشهرية للفروع الأخرى</span>
    </a>
</li>
@endcan
@canany(['monthly_activities.approve','monthly_plan.approve'])
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.programs.approvals.*') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index') }}">
        <span class="nxl-micon"><i class="feather-check-square"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.programs.monthly_activities.approvals.title') }}</span>
    </a>
</li>
@endcanany
