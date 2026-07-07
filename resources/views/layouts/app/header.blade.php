@php
    $theme = session('ui.theme', 'light');
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
    $isArabic = app()->getLocale() === 'ar';
    $showHeaderSearch = trim($__env->yieldContent('enable_header_search', '0')) === '1';
@endphp
<header class="nxl-header nxl-header-clean {{ $isArabic ? 'is-rtl-header' : 'is-ltr-header' }}">
    <div class="header-wrapper">
        <div class="header-left d-flex align-items-center gap-3">
            <a href="javascript:void(0);" class="nxl-head-mobile-toggler" id="mobile-collapse">
                <div class="hamburger hamburger--arrowturn">
                    <div class="hamburger-box"><div class="hamburger-inner"></div></div>
                </div>
            </a>
            <div class="nxl-navigation-toggle {{ $isArabic ? 'is-rtl' : '' }}">
                <a href="javascript:void(0);" id="menu-mini-button" class="nxl-nav-toggle-btn" aria-label="{{ $isArabic ? 'تصغير القائمة' : 'Collapse navigation' }}" title="{{ $isArabic ? 'تصغير القائمة' : 'Collapse navigation' }}"><i class="feather-{{ $isArabic ? 'align-right' : 'align-left' }}"></i></a>
                <a href="javascript:void(0);" id="menu-expend-button" class="nxl-nav-toggle-btn d-none" aria-label="{{ $isArabic ? 'توسيع القائمة' : 'Expand navigation' }}" title="{{ $isArabic ? 'توسيع القائمة' : 'Expand navigation' }}"><i class="feather-{{ $isArabic ? 'arrow-right' : 'arrow-left' }} rtl-flip"></i></a>
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
                    <div class="lang-toggle" role="group" aria-label="{{ __('app.layout.language_switch') }}">
                        @if($isArabic)
                            <form method="POST" action="{{ route('ui.locale', 'en') }}" class="js-locale-switch" data-locale="en">@csrf<button class="lang-toggle__btn" type="submit">EN</button></form>
                            <form method="POST" action="{{ route('ui.locale', 'ar') }}" class="js-locale-switch" data-locale="ar">@csrf<button class="lang-toggle__btn is-active" type="submit">AR</button></form>
                        @else
                            <form method="POST" action="{{ route('ui.locale', 'ar') }}" class="js-locale-switch" data-locale="ar">@csrf<button class="lang-toggle__btn" type="submit">AR</button></form>
                            <form method="POST" action="{{ route('ui.locale', 'en') }}" class="js-locale-switch" data-locale="en">@csrf<button class="lang-toggle__btn is-active" type="submit">EN</button></form>
                        @endif
                    </div>
                </div>

                <div class="nxl-h-item dark-light-theme">
                    <form method="POST" action="{{ route('ui.theme', $nextTheme) }}">@csrf
                        <button type="submit" class="nxl-head-link me-0 border-0 bg-transparent {{ $theme === 'dark' ? 'light-button' : 'dark-button' }}" title="{{ __('app.layout.theme_toggle') }}"><i class="feather-{{ $theme === 'dark' ? 'sun' : 'moon' }}"></i></button>
                    </form>
                </div>

                @include('layouts.app.partials.notifications-menu')

                @auth
                    <div class="dropdown nxl-h-item nxl-user-menu-item">
                        <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false" class="user-avatar-trigger" aria-label="{{ __('app.layout.user_avatar') }}">
                            <span class="user-avatar-icon" aria-hidden="true"><i class="feather-user"></i></span>
                        </a>
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

<link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/pages/layouts-app-header.min.css') }}">
