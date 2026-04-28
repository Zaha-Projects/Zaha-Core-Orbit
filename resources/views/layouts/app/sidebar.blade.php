<nav class="nxl-navigation nxl-navigation-clean">
    <div class="navbar-wrapper">
        <div class="m-header">
            <a href="{{ route('dashboard') }}" class="b-brand">
                <img src="{{ asset('assets/images/zaha-core-orbit-logo.svg') }}" alt="{{ __('app.common.app_name') }}" class="logo logo-lg" />
                <img src="{{ asset('assets/images/zaha-core-orbit-mark.svg') }}" alt="{{ __('app.common.app_name') }}" class="logo logo-sm" />
            </a>
        </div>

        <div class="navbar-content">
            <ul class="nxl-navbar" id="sidebarnav">
                <li class="nxl-item nxl-caption"><label>{{ __('app.layout.sidebar_title') }}</label></li>
                <li class="nxl-item">
                    <a class="nxl-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="nxl-micon"><i class="feather-home"></i></span>
                        <span class="nxl-mtext">{{ __('app.common.dashboard') }}</span>
                    </a>
                </li>

                @php
                    $canAccessAdminSidebar = auth()->check() && (
                        auth()->user()->hasRole('super_admin')
                        || auth()->user()->canAny(['users.view', 'roles.view', 'workflows.manage'])
                    );
                @endphp

                @if ($canAccessAdminSidebar)
                    @include('pages.access.partials.sidebar')
                @endif

                @include('pages.agenda.partials.sidebar')
                @include('pages.monthly_activities.partials.sidebar')
                @include('pages.reports.partials.sidebar')

                @if (request()->routeIs('role.finance.*') || request()->routeIs('role.finance_officer.*'))
                    @include('pages.finance.partials.sidebar')
                @elseif (request()->routeIs('role.maintenance.*') || request()->routeIs('role.maintenance_officer.*'))
                    @include('pages.maintenance.partials.sidebar')
                @elseif (request()->routeIs('role.transport.*') || request()->routeIs('role.transport_officer.*'))
                    @include('pages.transport.partials.sidebar')
                @endif

                @include('layouts.app.partials.workflow-auto-approval-toggle')
            </ul>
        </div>
    </div>
</nav>

<style>
    .nxl-navigation-clean .nxl-link {
        border-radius: 12px;
        margin-inline: .35rem;
        margin-bottom: .2rem;
        transition: background-color .2s ease, color .2s ease, box-shadow .2s ease;
    }
    .nxl-navigation-clean .nxl-link .nxl-mtext {
        font-weight: 500;
    }
    .nxl-navigation-clean .nxl-link.active {
        background: #eef4ff;
        box-shadow: inset 0 0 0 1px #cddcf9;
    }
    .nxl-navigation-clean .nxl-caption label {
        letter-spacing: .2px;
        font-weight: 700;
    }
    html[dir="rtl"] .nxl-navigation-clean .nxl-link {
        text-align: right;
    }
    html[dir="ltr"] .nxl-navigation-clean .nxl-link {
        text-align: left;
    }
    .workflow-auto-approval-item {
        margin-top: .75rem;
        padding-top: .75rem;
        border-top: 1px solid #e5eaf3;
    }
    .workflow-auto-approval-form {
        margin: .35rem .5rem;
    }
    .workflow-auto-approval-toggle {
        align-items: center;
        background: #f8fbff;
        border: 1px solid #dbe7fb;
        border-radius: 12px;
        box-shadow: 0 6px 18px rgba(15, 23, 42, .06);
        cursor: pointer;
        display: flex;
        gap: .65rem;
        margin: 0;
        min-height: 58px;
        padding: .7rem .75rem;
        width: 100%;
    }
    .workflow-auto-approval-toggle:hover {
        background: #eef6ff;
    }
    .workflow-auto-approval-toggle input {
        height: 1px;
        opacity: 0;
        position: absolute;
        width: 1px;
    }
    .workflow-auto-approval-icon {
        color: #2563eb;
        flex: 0 0 auto;
    }
    .workflow-auto-approval-copy {
        display: flex;
        flex: 1;
        flex-direction: column;
        gap: .1rem;
        min-width: 0;
    }
    .workflow-auto-approval-title {
        color: #1f2937;
        font-size: .9rem;
        flex: 1;
        font-weight: 700;
        line-height: 1.25;
    }
    .workflow-auto-approval-help {
        color: #64748b;
        font-size: .72rem;
        font-weight: 500;
        line-height: 1.25;
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
