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
<body class="{{ $skinClass }}">
    @include('layouts.app.sidebar')

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
    @stack('scripts')
</body>
</html>
