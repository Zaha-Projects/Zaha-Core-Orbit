<aside class="left-sidebar with-vertical">
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between px-3 py-3 border-bottom">
            <a href="{{ url('/') }}" class="text-nowrap logo-img">
                <img src="{{ asset('assets/images/logos/logo.svg') }}" alt="{{ __('app.common.app_name') }}" />
            </a>
            <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
                <i class="ti ti-x fs-6"></i>
            </div>
        </div>

        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
            <ul id="sidebarnav" class="pt-2">
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span><i class="ti ti-layout-dashboard"></i></span>
                        <span class="hide-menu">{{ __('app.common.dashboard') }}</span>
                    </a>
                </li>

                @if (request()->routeIs('role.super_admin.*'))
                    @include('pages.access.partials.sidebar')
                @elseif (request()->routeIs('role.relations.*') || request()->routeIs('role.relations_manager.*') || request()->routeIs('role.relations_officer.*'))
                    @include('pages.agenda.partials.sidebar')
                @elseif (request()->routeIs('role.programs.*') || request()->routeIs('role.programs_manager.*') || request()->routeIs('role.programs_officer.*'))
                    @include('pages.monthly_activities.partials.sidebar')
                @elseif (request()->routeIs('role.finance.*') || request()->routeIs('role.finance_officer.*'))
                    @include('pages.finance.partials.sidebar')
                @elseif (request()->routeIs('role.maintenance.*') || request()->routeIs('role.maintenance_officer.*'))
                    @include('pages.maintenance.partials.sidebar')
                @elseif (request()->routeIs('role.transport.*') || request()->routeIs('role.transport_officer.*'))
                    @include('pages.transport.partials.sidebar')
                @elseif (request()->routeIs('role.reports.*') || request()->routeIs('role.reports_viewer.*'))
                    @include('pages.reports.partials.sidebar')
                @endif
            </ul>
        </nav>
    </div>
</aside>
