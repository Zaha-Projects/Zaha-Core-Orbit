@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $theme = session('ui.theme', 'light');
    $user = auth()->user();
    $displayName = $user?->name ?? config('app.name', __('app.common.app_name'));
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
    $canAccessMaintenanceSidebar = $user && ($user->hasRole('maintenance_officer') || request()->routeIs('role.maintenance.*'));
    $canAccessTransportSidebar = $user && (
        $user->hasAnyRole(['transport_officer', 'movement_manager', 'movement_editor', 'movement_viewer', 'super_admin'])
        || request()->routeIs('role.transport.*')
    );
    $canAccessReportsSidebar = $user && (
        $user->hasAnyRole(['reports_viewer', 'followup_officer', 'super_admin'])
        || $user->canAny(['reports.view', 'kpi.view'])
        || request()->routeIs('role.reports.*')
        || request()->routeIs('role.enterprise.*')
    );
    $canAccessReportPages = $user && (
        $user->hasAnyRole(['reports_viewer', 'followup_officer', 'super_admin'])
        || $user->can('reports.view')
    );
    $canAccessKpiPage = $user && (
        $user->hasAnyRole(['reports_viewer', 'followup_officer', 'super_admin'])
        || $user->can('kpi.view')
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
    <link rel="stylesheet" href="{{ asset('assets/theme/css/Theme.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/theme/css/Style.css') }}">
    <style>
        .workflow-auto-approval-original {
            border-top: 1px solid rgba(148, 163, 184, .25);
            margin-top: .65rem;
            padding-top: .65rem;
        }
        .workflow-auto-approval-original .workflow-auto-approval-form {
            margin: 0 .35rem;
        }
        .workflow-auto-approval-original .workflow-auto-approval-toggle {
            align-items: center;
            background: rgba(37, 99, 235, .08);
            border: 1px solid rgba(37, 99, 235, .18);
            border-radius: 14px;
            cursor: pointer;
            display: flex;
            gap: .7rem;
            margin: 0;
            min-height: 62px;
            padding: .7rem .8rem;
            width: 100%;
        }
        .workflow-auto-approval-original .workflow-auto-approval-toggle:hover {
            background: rgba(37, 99, 235, .13);
        }
        .workflow-auto-approval-icon {
            color: #2563eb;
            flex: 0 0 auto;
        }
        .workflow-auto-approval-copy {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: .12rem;
            min-width: 0;
        }
        .workflow-auto-approval-title {
            color: #1f2937;
            font-size: .9rem;
            font-weight: 700;
            line-height: 1.25;
        }
        .workflow-auto-approval-help {
            color: #64748b;
            font-size: .72rem;
            font-weight: 500;
            line-height: 1.25;
        }
        .workflow-auto-approval-toggle input {
            height: 1px;
            opacity: 0;
            position: absolute;
            width: 1px;
        }
        .workflow-auto-approval-switch {
            background: #cfd6e3;
            border-radius: 999px;
            flex: 0 0 auto;
            height: 22px;
            position: relative;
            transition: background-color .2s ease;
            width: 40px;
        }
        .workflow-auto-approval-switch::after {
            background: #fff;
            border-radius: 50%;
            box-shadow: 0 1px 4px rgba(15, 23, 42, .25);
            content: "";
            height: 18px;
            inset-block-start: 2px;
            inset-inline-start: 2px;
            position: absolute;
            transition: inset-inline-start .2s ease;
            width: 18px;
        }
        .workflow-auto-approval-toggle input:checked + .workflow-auto-approval-switch {
            background: #2563eb;
        }
        .workflow-auto-approval-toggle input:checked + .workflow-auto-approval-switch::after {
            inset-inline-start: 20px;
        }
    </style>
    @stack('styles')
</head>
<body class="{{ $isArabic ? 'dir-rtl' : 'dir-ltr' }}">
<div class="layout-shell">
    <aside id="appSidebar" class="sidebar-original">
        <div class="sidebar-brand">
            <img class="brand-logo" src="{{ asset('assets/theme/logos/logo2.svg') }}" alt="{{ __('app.common.app_name') }}">
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
                @php($otherBranchesLabel = __('app.acl.permissions.monthly_activities_view_other_branches'))
                <li class="side-item {{ request()->routeIs('role.relations.activities.*') && request('scope') === 'all_branches' ? 'selected' : '' }}"><a href="{{ route('role.relations.activities.index', ['scope' => 'all_branches']) }}"><i class="fas fa-table-cells-large"></i><span>{{ $otherBranchesLabel !== 'app.acl.permissions.monthly_activities_view_other_branches' ? $otherBranchesLabel : 'عرض الخطط الشهرية للفروع الأخرى' }}</span></a></li>
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

            @if ($canAccessMaintenanceSidebar)
                <li class="side-item {{ request()->routeIs('role.maintenance.requests.*') ? 'selected' : '' }}"><a href="{{ route('role.maintenance.requests.index') }}"><i class="fas fa-screwdriver-wrench"></i><span>{{ __('app.roles.maintenance.requests.title') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.maintenance.approvals.*') ? 'selected' : '' }}"><a href="{{ route('role.maintenance.approvals.index') }}"><i class="fas fa-clipboard-check"></i><span>{{ __('app.roles.maintenance.approvals.title') }}</span></a></li>
            @endif

            @if ($canAccessTransportSidebar)
                <li class="side-item {{ request()->routeIs('role.transport.vehicles.*') ? 'selected' : '' }}"><a href="{{ route('role.transport.vehicles.index') }}"><i class="fas fa-truck"></i><span>{{ __('app.roles.transport.vehicles.title') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.transport.drivers.*') ? 'selected' : '' }}"><a href="{{ route('role.transport.drivers.index') }}"><i class="fas fa-id-card"></i><span>{{ __('app.roles.transport.drivers.title') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.transport.trips.*') ? 'selected' : '' }}"><a href="{{ route('role.transport.trips.index') }}"><i class="fas fa-route"></i><span>{{ __('app.roles.transport.trips.title') }}</span></a></li>
                <li class="side-item {{ request()->routeIs('role.transport.movements.*') ? 'selected' : '' }}"><a href="{{ route('role.transport.movements.index') }}"><i class="fas fa-map-location-dot"></i><span>{{ __('app.roles.transport.movements.title') }}</span></a></li>
            @endif

            @if ($canAccessReportsSidebar)
                @if($canAccessReportPages)
                    <li class="side-item {{ request()->routeIs('role.reports.index') ? 'selected' : '' }}"><a href="{{ route('role.reports.index') }}"><i class="fas fa-chart-simple"></i><span>{{ __('app.roles.reports.title') }}</span></a></li>
                    <li class="side-item {{ request()->routeIs('role.reports.agenda.*') ? 'selected' : '' }}"><a href="{{ route('role.reports.agenda.index') }}"><i class="fas fa-calendar-check"></i><span>{{ __('app.roles.reports.agenda.title') }}</span></a></li>
                    <li class="side-item {{ request()->routeIs('role.reports.monthly.*') ? 'selected' : '' }}"><a href="{{ route('role.reports.monthly.index') }}"><i class="fas fa-layer-group"></i><span>{{ __('app.roles.reports.monthly.title') }}</span></a></li>
                    <li class="side-item {{ request()->routeIs('role.reports.finance.*') ? 'selected' : '' }}"><a href="{{ route('role.reports.finance.index') }}"><i class="fas fa-money-bill-trend-up"></i><span>{{ __('app.roles.reports.finance.title') }}</span></a></li>
                    <li class="side-item {{ request()->routeIs('role.reports.maintenance.*') ? 'selected' : '' }}"><a href="{{ route('role.reports.maintenance.index') }}"><i class="fas fa-toolbox"></i><span>{{ __('app.roles.reports.maintenance.title') }}</span></a></li>
                    <li class="side-item {{ request()->routeIs('role.reports.transport.*') ? 'selected' : '' }}"><a href="{{ route('role.reports.transport.index') }}"><i class="fas fa-truck-fast"></i><span>{{ __('app.roles.reports.transport.title') }}</span></a></li>
                @endif
                @if($canAccessKpiPage)
                    <li class="side-item {{ request()->routeIs('role.reports.kpis.*') ? 'selected' : '' }}"><a href="{{ route('role.reports.kpis.index') }}"><i class="fas fa-chart-line"></i><span>{{ __('app.roles.reports.kpis.title') }}</span></a></li>
                @endif
                @if($user?->hasAnyRole(['reports_viewer', 'followup_officer', 'super_admin']))
                    <li class="side-item {{ request()->routeIs('role.enterprise.dashboard') ? 'selected' : '' }}"><a href="{{ route('role.enterprise.dashboard') }}"><i class="fas fa-chart-pie"></i><span>{{ __('app.enterprise.analytics_title') }}</span></a></li>
                    <li class="side-item {{ request()->routeIs('role.reports.enterprise.*') ? 'selected' : '' }}"><a href="{{ route('role.reports.enterprise.branch_performance') }}"><i class="fas fa-arrow-trend-up"></i><span>{{ __('app.enterprise.branch_performance.report_title') }}</span></a></li>
                @endif
            @endif

            @include('layouts.app.partials.workflow-auto-approval-toggle', ['variant' => 'original'])
        </ul>

        <p class="side-comment" data-i18n="language">{{ __('app.layout.language_switch') }}</p>
        <button id="mobileLocaleToggle" class="btn btn-sidebar-control w-100 mb-2" type="button">
            {{ $isArabic ? __('app.layout.switch_to_english') : __('app.layout.switch_to_arabic') }}
        </button>
        <button id="mobileThemeToggle" class="btn btn-sidebar-control w-100" type="button">
            {{ $theme === 'dark' ? __('nav.light_mode') : __('nav.dark_mode') }}
        </button>
    </aside>

    <div class="content-shell">
        <header>
            <nav class="navbar topbar-original topbar-pill">
                <button id="sidebarToggle" class="btn topbar-toggle" type="button"><i class="fas fa-bars"></i></button>

                <ul class="nav ms-auto align-items-center gap-2 topbar-actions">
                    <li class="nav-item"><span class="top-avatar top-avatar-icon"><i class="fas fa-user-astronaut"></i></span></li>
                    <li class="nav-item dropdown">
                        <button class="btn btn-profile dropdown-toggle" data-bs-toggle="dropdown" type="button">{{ $displayName }}</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button id="themeToggle" class="dropdown-item" type="button">
                                    {{ $theme === 'dark' ? __('nav.light_mode') : __('nav.dark_mode') }}
                                </button>
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
                    <li class="nav-item">
                        <button class="btn btn-locale-toggle" id="localeToggle" type="button">
                            {{ $isArabic ? __('app.layout.switch_to_english') : __('app.layout.switch_to_arabic') }}
                        </button>
                    </li>
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
<script src="{{ asset('assets/theme/js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
