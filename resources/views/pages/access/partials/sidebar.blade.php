<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.super_admin.users*') ? 'active' : '' }}" href="{{ route('role.super_admin.users') }}">
    {{ __('app.roles.super_admin.sidebar.users') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.super_admin.branches*') ? 'active' : '' }}" href="{{ route('role.super_admin.branches') }}">
    {{ __('app.roles.super_admin.sidebar.branches') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.super_admin.centers*') ? 'active' : '' }}" href="{{ route('role.super_admin.centers') }}">
    {{ __('app.roles.super_admin.sidebar.centers') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.super_admin.roles*') ? 'active' : '' }}" href="{{ route('role.super_admin.roles') }}">
    {{ __('app.roles.super_admin.sidebar.roles') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.super_admin.approvals*') ? 'active' : '' }}" href="{{ route('role.super_admin.approvals') }}">
    {{ __('app.roles.super_admin.sidebar.approvals') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.super_admin.reports*') ? 'active' : '' }}" href="{{ route('role.super_admin.reports') }}">
    {{ __('app.roles.super_admin.sidebar.reports') }}
</a>
</li>
