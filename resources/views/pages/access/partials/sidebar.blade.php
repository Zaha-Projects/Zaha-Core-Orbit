<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.super_admin.users*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.super_admin.users') }}">
    {{ __('app.roles.super_admin.sidebar.users') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.super_admin.branches*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.super_admin.branches') }}">
    {{ __('app.roles.super_admin.sidebar.branches') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.super_admin.centers*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.super_admin.centers') }}">
    {{ __('app.roles.super_admin.sidebar.centers') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.super_admin.roles*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.super_admin.roles') }}">
    {{ __('app.roles.super_admin.sidebar.roles') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.super_admin.approvals*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.super_admin.approvals') }}">
    {{ __('app.roles.super_admin.sidebar.approvals') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.super_admin.reports*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.super_admin.reports') }}">
    {{ __('app.roles.super_admin.sidebar.reports') }}
</a>
