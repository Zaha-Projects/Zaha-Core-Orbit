@php
    $theme = session('ui.theme', 'light');
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
@endphp
<header class="nxl-header">
    <div class="header-wrapper">
        <div class="header-left d-flex align-items-center gap-3">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box">
                        <div class="hamburger-inner"></div>
                    </div>
                </div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button">
                    <i class="feather-align-left"></i>
                </a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display: none">
                    <i class="feather-arrow-right"></i>
                </a>
            </div>
            <a class="navbar-brand fw-semibold mb-0" href="{{ route('dashboard') }}">{{ __('app.common.dashboard') }}</a>
        </div>

        <div class="header-right ms-auto">
            <div class="d-flex align-items-center gap-2">
                <form method="POST" action="{{ route('ui.theme', $nextTheme) }}" class="m-0">
                    @csrf
                    <button class="btn btn-light btn-sm" type="submit" title="{{ __('app.layout.theme_toggle') }}">
                        <i class="feather-{{ $theme === 'dark' ? 'sun' : 'moon' }}"></i>
                    </button>
                </form>

                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        {{ strtoupper(app()->getLocale()) }}
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <form method="POST" action="{{ route('ui.locale', 'ar') }}">
                                @csrf
                                <button class="dropdown-item" type="submit">العربية</button>
                            </form>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('ui.locale', 'en') }}">
                                @csrf
                                <button class="dropdown-item" type="submit">English</button>
                            </form>
                        </li>
                    </ul>
                </div>

                @auth
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="avatar-text avatar-sm" data-bs-toggle="dropdown">
                            {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}
                        </a>
                        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown">
                            <span class="dropdown-item-text small text-muted">{{ auth()->user()->name }}</span>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button class="dropdown-item" type="submit">{{ __('app.common.logout') }}</button>
                            </form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</header>
