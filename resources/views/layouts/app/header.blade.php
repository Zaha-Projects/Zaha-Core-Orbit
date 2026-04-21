@php
    $theme = session('ui.theme', 'light');
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
    $isArabic = app()->getLocale() === 'ar';
    $unreadNotifications = auth()->check() ? auth()->user()->inAppNotifications()->whereNull('read_at')->latest()->take(5)->get() : collect();
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
                <a href="javascript:void(0);" id="menu-expend-button" class="nxl-nav-toggle-btn" style="display: none" aria-label="{{ $isArabic ? 'توسيع القائمة' : 'Expand navigation' }}" title="{{ $isArabic ? 'توسيع القائمة' : 'Expand navigation' }}"><i class="feather-{{ $isArabic ? 'arrow-right' : 'arrow-left' }} rtl-flip"></i></a>
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

                <div class="dropdown nxl-h-item">
                    <a class="nxl-head-link me-0" data-bs-toggle="dropdown" href="#" aria-label="{{ __('app.layout.notifications') }}">
                        <i class="feather-bell"></i><span class="badge bg-danger nxl-h-badge">{{ $unreadNotifications->count() }}</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end nxl-h-dropdown notification-chat-menu">
                        <div class="notification-chat-head">
                            <div class="fw-semibold">{{ __('app.layout.notifications') }}</div>
                            <div class="small text-muted">{{ __('app.layout.new_notifications_count', ['count' => $unreadNotifications->count()]) }}</div>
                        </div>
                        <div class="notification-chat-list">
                            @forelse($unreadNotifications as $notification)
                                <div class="notification-chat-item">
                                    <div class="notification-chat-bubble">
                                        <div class="fw-semibold mb-1">{{ $notification->title }}</div>
                                        <div class="text-muted small">{{ $notification->message }}</div>
                                        <div class="d-flex align-items-center gap-3 mt-2 flex-wrap">
                                            @if($notification->action_url)
                                                <a class="small text-decoration-none" href="{{ $notification->action_url }}">{{ __('app.layout.open_notification') }}</a>
                                            @endif
                                            <form method="POST" action="{{ route('role.notifications.read', $notification) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button class="btn btn-link p-0 small text-decoration-none" type="submit">{{ __('app.common.mark_as_read') }}</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="notification-chat-empty">{{ __('app.common.no_new_notifications') }}</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                @auth
                    <div class="dropdown nxl-h-item nxl-user-menu-item">
                        <a href="javascript:void(0);" data-bs-toggle="dropdown" aria-expanded="false"><img src="{{ asset('assets/images/avatar/1.png') }}" alt="{{ __('app.layout.user_avatar') }}" class="img-fluid user-avtar me-0" /></a>
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

<style>
    .notification-chat-menu {
        min-width: 360px;
        max-width: 420px;
        padding: 0;
        overflow: hidden;
        border-radius: 18px;
        border: 1px solid #d9e4ef;
    }
    .notification-chat-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: .9rem 1rem;
        border-bottom: 1px solid #e8eef5;
        background: linear-gradient(180deg, #f8fbff 0%, #eef5fb 100%);
    }
    .notification-chat-list {
        max-height: 420px;
        overflow-y: auto;
        padding: .85rem;
        background: #f7fafc;
    }
    .notification-chat-item + .notification-chat-item {
        margin-top: .75rem;
    }
    .notification-chat-bubble {
        position: relative;
        padding: .85rem .95rem;
        border-radius: 16px 16px 16px 6px;
        background: #ffffff;
        border: 1px solid #dde7f0;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .05);
    }
    .notification-chat-bubble::after {
        content: '';
        position: absolute;
        inset-inline-start: 14px;
        bottom: -8px;
        width: 14px;
        height: 14px;
        background: #ffffff;
        border-inline-start: 1px solid #dde7f0;
        border-bottom: 1px solid #dde7f0;
        transform: rotate(-45deg);
    }
    .notification-chat-empty {
        padding: 1.25rem 1rem;
        text-align: center;
        color: #64748b;
    }

    .nxl-navigation-toggle .nxl-nav-toggle-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        color: #334155;
        transition: background-color .2s ease, color .2s ease;
    }
    .nxl-navigation-toggle .nxl-nav-toggle-btn:hover {
        background: #eef4fb;
        color: #0f172a;
    }
    .nxl-navigation-toggle.is-rtl {
        direction: rtl;
    }
    .nxl-user-menu-item .nxl-user-dropdown {
        min-width: 220px;
        max-width: min(92vw, 320px);
    }
    .nxl-h-item > .dropdown-menu {
        max-width: min(94vw, 420px);
    }
    html[dir="rtl"] .nxl-user-menu-item .nxl-user-dropdown {
        text-align: right;
    }
    html[dir="rtl"] .nxl-header.is-rtl-header .header-wrapper {
        direction: rtl;
    }
    html[dir="rtl"] .nxl-header.is-rtl-header .header-left {
        order: 1;
        margin-left: 0;
    }
    html[dir="rtl"] .nxl-header.is-rtl-header .header-right {
        order: 2;
        margin-right: 0 !important;
        margin-left: auto !important;
    }
    html[dir="rtl"] .nxl-header.is-rtl-header .header-right .d-flex {
        direction: rtl;
    }
    html[dir="rtl"] .nxl-header.is-rtl-header .header-right .dropdown-menu {
        text-align: right;
    }
    html[dir="rtl"] .nxl-header.is-rtl-header .header-right .notification-chat-menu {
        inset-inline-start: auto !important;
        inset-inline-end: 0 !important;
    }
    @media (max-width: 767.98px) {
        .nxl-user-menu-item {
            position: static;
        }
        .nxl-h-item > .dropdown-menu {
            min-width: min(92vw, 380px);
        }
        html[dir="rtl"] .nxl-header.is-rtl-header .header-left {
            margin-left: 0;
        }
        html[dir="rtl"] .nxl-header.is-rtl-header .header-right .notification-chat-menu {
            position: fixed;
            top: 72px !important;
            inset-inline-start: 12px !important;
            inset-inline-end: 12px !important;
            width: auto;
            transform: none !important;
            max-height: calc(100vh - 90px);
            overflow-y: auto;
        }
        .nxl-user-menu-item .nxl-user-dropdown {
            position: fixed;
            top: 72px !important;
            inset-inline-start: 12px !important;
            inset-inline-end: 12px !important;
            width: auto;
            transform: none !important;
            max-height: calc(100vh - 90px);
            overflow-y: auto;
        }
    }

</style>
