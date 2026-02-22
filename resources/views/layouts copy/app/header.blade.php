<header class="app-header border-bottom">
    <nav class="navbar navbar-expand-lg navbar-light px-3 px-lg-4 py-3">
        <div class="d-flex align-items-center gap-2">
            <a class="nav-link sidebartoggler d-block d-xl-none p-0" id="headerCollapse" href="javascript:void(0)">
                <i class="ti ti-menu-2 fs-7"></i>
            </a>
            <a class="navbar-brand fw-semibold mb-0" href="{{ route('dashboard') }}">{{ __('app.common.dashboard') }}</a>
        </div>

        <div class="navbar-collapse justify-content-end px-0" id="navbarNav">
            <ul class="navbar-nav flex-row ms-auto align-items-center gap-2 gap-lg-3">
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
                        <a class="btn btn-primary btn-sm" href="{{ route('login') }}">{{ __('app.common.login') }}</a>
                    </li>
                @endauth
            </ul>
        </div>
    </nav>
</header>
