<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.maintenance.requests.*') ? 'active' : '' }}" href="{{ route('role.maintenance.requests.index') }}">
        <span class="nxl-micon"><i class="feather-tool"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.maintenance.requests.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.maintenance.approvals.*') ? 'active' : '' }}" href="{{ route('role.maintenance.approvals.index') }}">
        <span class="nxl-micon"><i class="feather-check-circle"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.maintenance.approvals.title') }}</span>
    </a>
</li>
