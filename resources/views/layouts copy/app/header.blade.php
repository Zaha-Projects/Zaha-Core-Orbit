<header class="app-header">
    <nav class="navbar navbar-expand-lg navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item d-block d-xl-none">
                <a class="nav-link sidebartoggler" id="headerCollapse" href="javascript:void(0)">
                    <i class="ti ti-menu-2"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="{{ route('dashboard') }}">
                    <i class="ti ti-layout-dashboard"></i>
                    <span class="ms-1">{{ __('app.common.dashboard') }}</span>
                </a>
            </li>
        </ul>

        <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
            <ul class="navbar-nav flex-row ms-auto align-items-center justify-content-end gap-2">
                @auth
                    <li class="nav-item text-muted small d-none d-md-inline">{{ auth()->user()->name }}</li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm" type="submit">{{ __('app.common.logout') }}</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="btn btn-outline-primary btn-sm" href="{{ route('login') }}">{{ __('app.common.login') }}</a>
                    </li>
                @endauth
            </ul>
        </div>
    </nav>
</header>
