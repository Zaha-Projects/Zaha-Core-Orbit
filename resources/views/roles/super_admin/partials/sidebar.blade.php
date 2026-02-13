<aside class="bg-white border rounded p-3 shadow-sm">
    <h2 class="h6 text-uppercase text-muted mb-3">{{ __('app.roles.super_admin.sidebar.title') }}</h2>
    <nav class="nav flex-column gap-2">
        <a class="btn btn-sm btn-outline-primary text-start" href="{{ route('role.super_admin.dashboard') }}">
            {{ __('app.roles.super_admin.sidebar.dashboard') }}
        </a>
        <a class="btn btn-sm btn-outline-primary text-start" href="{{ route('role.super_admin.users') }}">
            {{ __('app.roles.super_admin.sidebar.users') }}
        </a>
        <a class="btn btn-sm btn-outline-primary text-start" href="{{ route('role.super_admin.branches') }}">
            {{ __('app.roles.super_admin.sidebar.branches') }}
        </a>
        <a class="btn btn-sm btn-outline-primary text-start" href="{{ route('role.super_admin.centers') }}">
            {{ __('app.roles.super_admin.sidebar.centers') }}
        </a>
        <a class="btn btn-sm btn-outline-primary text-start" href="{{ route('role.super_admin.departments') }}">
            {{ __('app.roles.super_admin.sidebar.departments') }}
        </a>
        <a class="btn btn-sm btn-outline-primary text-start" href="{{ route('role.super_admin.roles') }}">
            {{ __('app.roles.super_admin.sidebar.roles') }}
        </a>
        <a class="btn btn-sm btn-outline-primary text-start" href="{{ route('role.super_admin.approvals') }}">
            {{ __('app.roles.super_admin.sidebar.approvals') }}
        </a>
        <a class="btn btn-sm btn-outline-primary text-start" href="{{ route('role.super_admin.reports') }}">
            {{ __('app.roles.super_admin.sidebar.reports') }}
        </a>
    </nav>
</aside>
