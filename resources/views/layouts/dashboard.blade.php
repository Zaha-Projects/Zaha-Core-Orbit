@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $theme = session('ui.theme', 'light');
    $skinClass = $theme === 'dark' ? 'app-skin-dark' : 'app-skin-light';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', __('app.common.app_name')) }}</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('assets/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/zaha-duralux-overrides.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/zaha-theme.css') }}" />
    @stack('styles')
</head>
<body class="{{ $skinClass }} dashboard-shell" data-locale="{{ $locale }}">
    @include('layouts.app.sidebar')
    <button type="button" class="sidebar-backdrop border-0 bg-transparent" id="sidebar-backdrop" aria-label="Close sidebar"></button>

    @include('layouts.app.header')

    <main class="nxl-container">
        <div class="nxl-content">
            @hasSection('hide_page_header')
            @else
                @include('layouts.app.page-header')
            @endif

            <div class="main-content">
                <div class="container-fluid py-4">
                    @yield('content')
                </div>
            </div>
        </div>

        @include('layouts.app.footer')
    </main>

    <script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ asset('assets/js/common-init.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.js-locale-switch').forEach(function (form) {
                form.addEventListener('submit', function () {
                    var locale = form.dataset.locale;
                    var isRtl = locale === 'ar';
                    document.documentElement.setAttribute('dir', isRtl ? 'rtl' : 'ltr');
                    document.documentElement.setAttribute('lang', locale);
                    document.body.classList.toggle('rtl-active', isRtl);
                });
            });

            var mobileToggle = document.getElementById('mobile-collapse');
            var sidebarBackdrop = document.getElementById('sidebar-backdrop');
            var pageHeaderActionOpen = document.querySelector('.page-header-right-open-toggle');
            var pageHeaderActions = document.querySelector('.page-header-right-items');

            var closeSidebar = function () {
                document.body.classList.remove('dashboard-sidebar-open');
            };

            if (mobileToggle) {
                mobileToggle.addEventListener('click', function () {
                    document.body.classList.toggle('dashboard-sidebar-open');
                });
            }

            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', closeSidebar);
            }

            window.addEventListener('resize', function () {
                if (window.innerWidth > 991) {
                    closeSidebar();
                    if (pageHeaderActions) {
                        pageHeaderActions.classList.remove('page-header-right-open');
                    }
                }
            });

            if (pageHeaderActionOpen && pageHeaderActions) {
                document.addEventListener('click', function (event) {
                    if (!pageHeaderActions.contains(event.target) && !pageHeaderActionOpen.contains(event.target)) {
                        pageHeaderActions.classList.remove('page-header-right-open');
                    }
                });
            }
        });
    </script>
    @stack('scripts')
</body>
</html>
