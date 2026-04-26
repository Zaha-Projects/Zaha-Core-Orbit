@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $theme = session('ui.theme', 'light');
    $user = auth()->user();
    $displayName = $user?->name ?? 'ZAHA';
    $currentRoute = request()->route()?->getName();
    $canAccessAgendaApprovals = $user && (
        $user->can('agenda.approve')
        || app(\App\Services\DynamicWorkflowService::class)->userMayParticipateInWorkflow('agenda', $user)
    );
    $canAccessMonthlyApprovals = $user && (
        $user->can('monthly_activities.approve')
        || app(\App\Services\DynamicWorkflowService::class)->userMayParticipateInWorkflow('monthly_activities', $user)
    );
    $canAccessAdminSidebar = $user && (
        $user->hasRole('super_admin')
        || $user->canAny(['users.view', 'roles.view', 'workflows.manage', 'branches.manage'])
    );
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

            @if ($canAccessAdminSidebar)
                <li class="side-item {{ request()->routeIs('role.super_admin.users*') ? 'selected' : '' }}"><a href="{{ route('role.super_admin.users') }}"><i class="fas fa-users"></i><span>{{ __('app.roles.super_admin.sidebar.users') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.super_admin.roles*') ? 'selected' : '' }}"><a href="{{ route('role.super_admin.roles') }}"><i class="fas fa-user-shield"></i><span>{{ __('app.roles.super_admin.sidebar.roles') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.super_admin.workflows*') || request()->routeIs('role.super_admin.workflow_steps*') ? 'selected' : '' }}"><a href="{{ route('role.super_admin.workflows') }}"><i class="fas fa-diagram-project"></i><span>{{ __('app.roles.super_admin.actions.workflows.title') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.super_admin.branches*') ? 'selected' : '' }}"><a href="{{ route('role.super_admin.branches') }}"><i class="fas fa-building"></i><span>{{ __('app.roles.super_admin.sidebar.branches') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.super_admin.approvals*') ? 'selected' : '' }}"><a href="{{ route('role.super_admin.approvals') }}"><i class="fas fa-list-check"></i><span>{{ __('app.roles.super_admin.sidebar.approvals') }}</span></a></li>
            @endif

            @can('agenda.view')
                <li class="side-item {{ request()->routeIs('role.relations.agenda.*') ? 'selected' : '' }}"><a href="{{ route('role.relations.agenda.index') }}"><i class="fas fa-calendar-days"></i><span>{{ __('app.roles.relations.agenda.title') }}</span></a></li>
            @endcan
            @if($canAccessAgendaApprovals)
                <li class="side-item {{ request()->routeIs('role.relations.approvals.*') ? 'selected' : '' }}"><a href="{{ route('role.relations.approvals.index') }}"><i class="fas fa-square-check"></i><span>{{ __('app.roles.relations.approvals.title') }}</span></a></li>
            @endif
            @canany(['monthly_activities.view','monthly_plan.view'])
                <li class="side-item {{ request()->routeIs('role.relations.activities.*') && request('scope') !== 'all_branches' ? 'selected' : '' }}"><a href="{{ route('role.relations.activities.index') }}"><i class="fas fa-layer-group"></i><span>{{ __('app.roles.programs.monthly_activities.title') }}</span></a></li>
            @endcanany
            @can('monthly_activities.view_other_branches')
                <li class="side-item {{ request()->routeIs('role.relations.activities.*') && request('scope') === 'all_branches' ? 'selected' : '' }}"><a href="{{ route('role.relations.activities.index', ['scope' => 'all_branches']) }}"><i class="fas fa-table-cells-large"></i><span>{{ __('app.acl.permissions.monthly_activities_view_other_branches') }}</span></a></li>
            @endcan
            @if($canAccessMonthlyApprovals)
                <li class="side-item {{ request()->routeIs('role.programs.approvals.*') ? 'selected' : '' }}"><a href="{{ route('role.programs.approvals.index') }}"><i class="fas fa-square-check"></i><span>{{ __('app.roles.programs.monthly_activities.approvals.title') }}</span></a></li>
            @endif

            @if ($user?->hasRole('finance_officer') || request()->routeIs('role.finance.*') || request()->routeIs('role.finance_officer.*'))
                <li class="side-item {{ request()->routeIs('role.finance.donations.*') ? 'selected' : '' }}"><a href="{{ route('role.finance.donations.index') }}"><i class="fas fa-hand-holding-heart"></i><span>{{ __('app.roles.finance.donations.title') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.finance.bookings.*') ? 'selected' : '' }}"><a href="{{ route('role.finance.bookings.index') }}"><i class="fas fa-book"></i><span>{{ __('app.roles.finance.bookings.title') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.finance.zaha_time.*') ? 'selected' : '' }}"><a href="{{ route('role.finance.zaha_time.index') }}"><i class="fas fa-clock"></i><span>{{ __('app.roles.finance.zaha_time.title') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.finance.payments.*') ? 'selected' : '' }}"><a href="{{ route('role.finance.payments.index') }}"><i class="fas fa-credit-card"></i><span>{{ __('app.roles.finance.payments.title') }}</span></a></li>
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
