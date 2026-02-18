<header class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm sticky-top">
    <div class="container-fluid px-3 px-lg-4">
        <a class="navbar-brand fw-semibold" href="{{ url('/') }}">{{ __('app.common.app_name') }}</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#appHeaderNav" aria-controls="appHeaderNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="appHeaderNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('dashboard') }}">{{ __('app.common.dashboard') }}</a>
                </li>
            </ul>

            <ul class="navbar-nav align-items-lg-center gap-lg-2">
                @auth
                    <li class="nav-item text-muted small">{{ auth()->user()->name }}</li>
                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-outline-secondary btn-sm" type="submit">{{ __('app.common.logout') }}</button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('login') }}">{{ __('app.common.login') }}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('register') }}">{{ __('app.common.register') }}</a>
                    </li>
                @endauth
            </ul>
        </div>
    </div>
</header>
