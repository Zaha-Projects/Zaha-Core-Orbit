<nav class="nxl-navigation nxl-navigation-clean">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('dashboard') }}" class="b-brand">
                <img src="{{ asset('assets/images/zaha-core-orbit-logo.svg') }}" alt="{{ __('app.common.app_name') }}" class="logo logo-lg" />
                <img src="{{ asset('assets/images/zaha-core-orbit-mark.svg') }}" alt="{{ __('app.common.app_name') }}" class="logo logo-sm" />
            </a>
        </div>

        <div class="navbar-content">
            <ul class="nxl-navbar" id="sidebarnav">
                <li class="nxl-item nxl-caption"><label>{{ __('app.layout.sidebar_title') }}</label></li>
                <li class="nxl-item">
                    <a class="nxl-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="nxl-micon"><i class="feather-home"></i></span>
                        <span class="nxl-mtext">{{ __('app.common.dashboard') }}</span>
                    </a>
                </li>

                @php
                    $canAccessAdminSidebar = auth()->check() && (
                        auth()->user()->hasRole('super_admin')
                        || auth()->user()->canAny(['users.view', 'roles.view', 'workflows.manage'])
                    );
                @endphp

                @if ($canAccessAdminSidebar)
                    @include('pages.access.partials.sidebar')
                @endif

                @include('pages.agenda.partials.sidebar')
                @include('pages.monthly_activities.partials.sidebar')
                @include('pages.reports.partials.sidebar')

                @if (request()->routeIs('role.finance.*') || request()->routeIs('role.finance_officer.*'))
                    @include('pages.finance.partials.sidebar')
                @elseif (request()->routeIs('role.maintenance.*') || request()->routeIs('role.maintenance_officer.*'))
                    @include('pages.maintenance.partials.sidebar')
                @elseif (request()->routeIs('role.transport.*') || request()->routeIs('role.transport_officer.*'))
                    @include('pages.transport.partials.sidebar')
                @endif
            </ul>
        </div>
    </div>
</nav>
