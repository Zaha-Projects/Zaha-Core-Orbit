<aside class="bg-white border-end h-100">
    <div class="p-3 border-bottom">
        <h2 class="h6 text-uppercase text-muted mb-0">{{ __('app.layout.sidebar_title') }}</h2>
    </div>

    <nav class="nav flex-column p-2">
        <a class="nav-link rounded px-3 py-2 {{ request()->routeIs('dashboard') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('dashboard') }}">
            {{ __('app.common.dashboard') }}
        </a>

        @yield('sidebar')
    </nav>
</aside>
