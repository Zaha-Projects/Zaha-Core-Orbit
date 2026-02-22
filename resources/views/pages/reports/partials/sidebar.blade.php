<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.reports.index') ? 'active' : '' }}" href="{{ route('role.reports.index') }}">
        <span class="nxl-micon"><i class="feather-grid"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.reports.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.reports.agenda.*') ? 'active' : '' }}" href="{{ route('role.reports.agenda.index') }}">
        <span class="nxl-micon"><i class="feather-calendar"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.reports.agenda.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.reports.monthly.*') ? 'active' : '' }}" href="{{ route('role.reports.monthly.index') }}">
        <span class="nxl-micon"><i class="feather-layers"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.reports.monthly.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.reports.finance.*') ? 'active' : '' }}" href="{{ route('role.reports.finance.index') }}">
        <span class="nxl-micon"><i class="feather-dollar-sign"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.reports.finance.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.reports.maintenance.*') ? 'active' : '' }}" href="{{ route('role.reports.maintenance.index') }}">
        <span class="nxl-micon"><i class="feather-tool"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.reports.maintenance.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.reports.transport.*') ? 'active' : '' }}" href="{{ route('role.reports.transport.index') }}">
        <span class="nxl-micon"><i class="feather-truck"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.reports.transport.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.reports.kpis.*') ? 'active' : '' }}" href="{{ route('role.reports.kpis.index') }}">
        <span class="nxl-micon"><i class="feather-bar-chart-2"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.reports.kpis.title') }}</span>
    </a>
</li>
