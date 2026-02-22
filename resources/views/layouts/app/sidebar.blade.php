<aside class="left-sidebar">
    <div>
        <div class="brand-logo d-flex align-items-center justify-content-between">
            <a href="{{ url('/') }}" class="text-nowrap logo-img">
                <img src="{{ asset('assets/images/logos/logo.svg') }}" alt="logo" />
            </a>
            <div class="close-btn d-xl-none d-block sidebartoggler cursor-pointer" id="sidebarCollapse">
                <i class="ti ti-x fs-6"></i>
            </div>
        </div>

        <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
            <div class="px-3 py-2 d-grid gap-1">
                <a class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                    <span><i class="ti ti-layout-dashboard"></i></span>
                    <span class="hide-menu">{{ __('app.common.dashboard') }}</span>
                </a>

                @yield('sidebar')
            </div>
        </nav>
    </div>
</aside>
