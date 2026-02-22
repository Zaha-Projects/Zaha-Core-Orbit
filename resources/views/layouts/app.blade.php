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
    @stack('styles')
</head>
<body class="{{ $skinClass }}">
    @include('layouts.app.sidebar')

    @include('layouts.app.header')

    <main class="nxl-container">
        <div class="nxl-content">
            <div class="page-header">
                <div class="page-header-left d-flex align-items-center">
                    <div class="page-header-title">
                        <h5 class="m-b-10">Dashboard</h5>
                    </div>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                        <li class="breadcrumb-item">Dashboard</li>
                    </ul>
                </div>
                <div class="page-header-right ms-auto">
                    <div class="d-flex align-items-center gap-2 page-header-right-items-wrapper">
                        <div class="reportrange-picker d-flex align-items-center px-3" style="height:40px;">JAN 24, 26 - FEB 22, 26</div>
                        <div class="dropdown filter-dropdown">
                            <a class="btn btn-md btn-light-brand" data-bs-toggle="dropdown" data-bs-offset="0, 10" data-bs-auto-close="outside">
                                <i class="feather-filter me-2"></i>
                                <span>Filter</span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end"><a href="javascript:void(0);" class="dropdown-item">Static Filter</a></div>
                        </div>
                    </div>
                </div>
            </div>

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
