@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $theme = session('ui.theme', 'light');
    $user = auth()->user();
    $displayName = $user?->name ?? 'ZAHA';
    $currentRoute = request()->route()?->getName();
@endphp
<!doctype html>
<html lang="{{ $locale }}" dir="{{ $isArabic ? 'rtl' : 'ltr' }}" data-theme="{{ $theme }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', __('app.common.app_name')))</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">

    <link id="bootstrapCss" rel="stylesheet" href="{{ $isArabic ? 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css' : 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <link rel="stylesheet" href="{{ asset('assets/new-theme/css/Theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/new-theme/css/Style.css') }}">
    @stack('styles')
</head>
<body class="{{ $isArabic ? 'dir-rtl' : 'dir-ltr' }}">
<div class="layout-shell">
    <aside id="appSidebar" class="sidebar-original">
        <div class="sidebar-brand">
            <img class="brand-logo" src="{{ asset('assets/new-theme/logos/logo2.svg') }}" alt="ZAHA Logo">
        </div>

        <p class="side-comment" data-i18n="quick_menu">{{ __('app.common.dashboard') }}</p>

        <ul class="side-list">
            <li class="side-item {{ $currentRoute === 'dashboard' ? 'selected' : '' }}">
                <a href="{{ route('dashboard') }}"><i class="fas fa-gauge-high"></i><span data-i18n="menu_dashboard">{{ __('app.common.dashboard') }}</span></a>
            </li>
            @hasSection('theme_sidebar_links')
                @yield('theme_sidebar_links')
            @else
                <li class="side-item"><a href="#calendarSection"><i class="fas fa-calendar-days"></i><span data-i18n="menu_calendar_page">التقويم</span></a></li>
                <li class="side-item"><a href="#notificationsSection"><i class="fas fa-bell"></i><span data-i18n="menu_notify_page">{{ __('app.common.notifications') }}</span></a></li>
                <li class="side-item"><a href="#cardsSection"><i class="fas fa-id-card"></i><span data-i18n="menu_cards">البطاقات</span></a></li>
                <li class="side-item"><a href="#paginationSection"><i class="fas fa-list-ol"></i><span data-i18n="menu_pagination">Pagination</span></a></li>
            @endif
        </ul>

        <p class="side-comment" data-i18n="language">{{ __('app.layout.language_switch') }}</p>
        <button id="mobileLocaleToggle" class="btn btn-outline-info w-100 mb-2" type="button">🌐 English (LTR)</button>
        <button id="mobileThemeToggle" class="btn btn-outline-light w-100" type="button">🌙 داكن</button>
    </aside>

    <div class="content-shell">
        <header>
            <nav class="navbar topbar-original topbar-pill">
                <button id="sidebarToggle" class="btn topbar-toggle" type="button"><i class="fas fa-bars"></i></button>

                <ul class="nav ms-auto align-items-center gap-2 topbar-actions">
                    <li class="nav-item dropdown">
                        <button class="btn icon-dd" data-bs-toggle="dropdown" type="button">
                            <i class="far fa-bell"></i><span class="badge bg-warning text-dark rounded-pill">{{ $user?->inAppNotifications()?->whereNull('read_at')->count() ?? 0 }}</span><i class="fas fa-caret-down tiny-caret"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#notificationsSection" data-i18n="notif_count">{{ __('app.layout.notifications') }}</a></li>
                            <li><a class="dropdown-item" href="#" data-i18n="see_all">عرض الكل</a></li>
                        </ul>
                    </li>

                    <li class="nav-item"><span class="top-avatar top-avatar-icon"><i class="fas fa-user-astronaut"></i></span></li>
                    <li class="nav-item dropdown">
                        <button class="btn btn-profile dropdown-toggle" data-bs-toggle="dropdown" type="button">{{ $displayName }}</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button id="themeToggle" class="dropdown-item" type="button">🌙 Dark</button>
                            </li>
                            @auth
                                <li>
                                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                                        @csrf
                                        <button class="dropdown-item" type="submit"><i class="fas fa-right-from-bracket me-2"></i>{{ __('app.common.logout') }}</button>
                                    </form>
                                </li>
                            @endauth
                        </ul>
                    </li>
                    <li class="nav-item"><button class="btn btn-locale-toggle" id="localeToggle" type="button">🌐 English (LTR)</button></li>
                </ul>
            </nav>
        </header>

        <main class="content-main">
            @yield('content')
        </main>
    </div>
</div>

<form id="localeFormAr" method="POST" action="{{ route('ui.locale', 'ar') }}" class="d-none">@csrf</form>
<form id="localeFormEn" method="POST" action="{{ route('ui.locale', 'en') }}" class="d-none">@csrf</form>
<form id="themeFormLight" method="POST" action="{{ route('ui.theme', 'light') }}" class="d-none">@csrf</form>
<form id="themeFormDark" method="POST" action="{{ route('ui.theme', 'dark') }}" class="d-none">@csrf</form>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/locales-all.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
<script src="{{ asset('assets/new-theme/js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
