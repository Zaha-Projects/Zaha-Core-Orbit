<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.reports.index') ? 'active' : '' }}" href="{{ route('role.reports.index') }}">{{ __('app.roles.reports.title') }}</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.reports.agenda.*') ? 'active' : '' }}" href="{{ route('role.reports.agenda.index') }}">{{ __('app.roles.reports.agenda.title') }}</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.reports.monthly.*') ? 'active' : '' }}" href="{{ route('role.reports.monthly.index') }}">{{ __('app.roles.reports.monthly.title') }}</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.reports.finance.*') ? 'active' : '' }}" href="{{ route('role.reports.finance.index') }}">{{ __('app.roles.reports.finance.title') }}</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.reports.maintenance.*') ? 'active' : '' }}" href="{{ route('role.reports.maintenance.index') }}">{{ __('app.roles.reports.maintenance.title') }}</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.reports.transport.*') ? 'active' : '' }}" href="{{ route('role.reports.transport.index') }}">{{ __('app.roles.reports.transport.title') }}</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.reports.kpis.*') ? 'active' : '' }}" href="{{ route('role.reports.kpis.index') }}">{{ __('app.roles.reports.kpis.title') }}</a>
</li>
