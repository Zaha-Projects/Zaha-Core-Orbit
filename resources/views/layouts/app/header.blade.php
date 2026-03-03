@php
    $theme = session('ui.theme', 'light');
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
    $isArabic = app()->getLocale() === 'ar';
    $unreadNotifications = auth()->check() ? auth()->user()->inAppNotifications()->whereNull('read_at')->latest()->take(5)->get() : collect();
    $showHeaderSearch = trim($__env->yieldContent('enable_header_search', '0')) === '1';
@endphp
<header class="nxl-header nxl-header-clean">
    <div class="header-wrapper">
        <div class="header-left d-flex align-items-center gap-3">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box"><div class="hamburger-inner"></div></div>
                </div>
            </a>
            <div class="nxl-navigation-toggle">
                <a href="javascript:void(0);" id="menu-mini-button"><i class="feather-align-left"></i></a>
                <a href="javascript:void(0);" id="menu-expend-button" style="display: none"><i class="feather-{{ $isArabic ? 'arrow-left' : 'arrow-right' }} rtl-flip"></i></a>
            </div>
        </div>

        <div class="header-right ms-auto">
            <div class="d-flex align-items-center gap-1 gap-sm-2">
                @if ($showHeaderSearch)
                    <div class="dropdown nxl-h-item nxl-header-search d-none d-md-block">
                        <a href="javascript:void(0);" class="nxl-head-link me-0" data-bs-toggle="dropdown" data-bs-auto-close="outside"><i class="feather-search"></i></a>
                        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-search-dropdown">
                            <div class="input-group search-form">
                                <span class="input-group-text"><i class="feather-search fs-6 text-muted"></i></span>
                                <input type="text" class="form-control search-input-field" placeholder="{{ __('app.common.filter') }}" />
                                <span class="input-group-text"><button type="button" class="btn-close"></button></span>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="nxl-h-item nxl-header-language">
                    <div class="lang-toggle" role="group" aria-label="Language switch">
                        <form method="POST" action="{{ route('ui.locale', 'ar') }}" class="js-locale-switch" data-locale="ar">@csrf<button class="lang-toggle__btn {{ $isArabic ? 'is-active' : '' }}" type="submit">AR</button></form>
                        <form method="POST" action="{{ route('ui.locale', 'en') }}" class="js-locale-switch" data-locale="en">@csrf<button class="lang-toggle__btn {{ $isArabic ? '' : 'is-active' }}" type="submit">EN</button></form>
                    </div>
                </div>

                <div class="nxl-h-item dark-light-theme">
                    <form method="POST" action="{{ route('ui.theme', $nextTheme) }}">@csrf
                        <button type="submit" class="nxl-head-link me-0 border-0 bg-transparent {{ $theme === 'dark' ? 'light-button' : 'dark-button' }}" title="{{ __('app.layout.theme_toggle') }}"><i class="feather-{{ $theme === 'dark' ? 'sun' : 'moon' }}"></i></button>
                    </form>
                </div>

                <div class="dropdown nxl-h-item">
                    <a class="nxl-head-link me-0" data-bs-toggle="dropdown" href="#"><i class="feather-bell"></i><span class="badge bg-danger nxl-h-badge">{{ $unreadNotifications->count() }}</span></a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown" style="min-width: 320px;">
                        @forelse($unreadNotifications as $notification)
                            <div class="dropdown-item small">
                                <div class="fw-semibold">{{ $notification->title }}</div>
                                <div class="text-muted">{{ $notification->message }}</div>
                                <form method="POST" action="{{ route('role.notifications.read', $notification) }}">@csrf @method('PATCH')<button class="btn btn-link p-0 small" type="submit">Mark as read</button></form>
                            </div>
                        @empty
                            <div class="dropdown-item text-muted">No new notifications</div>
                        @endforelse
                    </div>
                </div>

                @auth
                    <div class="dropdown nxl-h-item">
                        <a href="javascript:void(0);" data-bs-toggle="dropdown"><img src="{{ asset('assets/images/avatar/1.png') }}" alt="user-image" class="img-fluid user-avtar me-0" /></a>
                        <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown nxl-user-dropdown">
                            <div class="dropdown-header"><h6 class="text-dark mb-0">{{ auth()->user()->name }}</h6></div>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="dropdown-item" type="submit"><i class="feather-log-out"></i><span>{{ __('app.common.logout') }}</span></button></form>
                        </div>
                    </div>
                @endauth
            </div>
        </div>
    </div>
</header>
