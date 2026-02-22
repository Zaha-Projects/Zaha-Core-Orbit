<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.super_admin.users*') ? 'active' : '' }}" href="{{ route('role.super_admin.users') }}">
        <span class="nxl-micon"><i class="feather-users"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.super_admin.sidebar.users') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.super_admin.branches*') ? 'active' : '' }}" href="{{ route('role.super_admin.branches') }}">
        <span class="nxl-micon"><i class="feather-map-pin"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.super_admin.sidebar.branches') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.super_admin.centers*') ? 'active' : '' }}" href="{{ route('role.super_admin.centers') }}">
        <span class="nxl-micon"><i class="feather-home"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.super_admin.sidebar.centers') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.super_admin.roles*') ? 'active' : '' }}" href="{{ route('role.super_admin.roles') }}">
        <span class="nxl-micon"><i class="feather-shield"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.super_admin.sidebar.roles') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.super_admin.approvals*') ? 'active' : '' }}" href="{{ route('role.super_admin.approvals') }}">
        <span class="nxl-micon"><i class="feather-check-circle"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.super_admin.sidebar.approvals') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.super_admin.reports*') ? 'active' : '' }}" href="{{ route('role.super_admin.reports') }}">
        <span class="nxl-micon"><i class="feather-bar-chart-2"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.super_admin.sidebar.reports') }}</span>
    </a>
</li>
