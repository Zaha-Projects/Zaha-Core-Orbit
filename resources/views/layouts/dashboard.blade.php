@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $theme = session('ui.theme', 'light');
    $skinClass = $theme === 'dark' ? 'app-skin-dark' : 'app-skin-light';
    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : time();

        return asset($path) . '?v=' . $version;
    };
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="x-ua-compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name', __('app.common.app_name')) }}</title>

    <link rel="shortcut icon" type="image/x-icon" href="{{ $versionedAsset('assets/images/favicon.ico') }}" />
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ $versionedAsset('assets/vendors/css/vendors.min.css') }}" />
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/theme.min.css') }}" />
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/zaha-duralux-overrides.min.css') }}" />
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/zaha-theme.min.css') }}" />
    @stack('styles')
</head>
<body class="{{ $skinClass }} dashboard-shell {{ $isRtl ? 'rtl-active' : '' }}" data-locale="{{ $locale }}">
    @include('layouts.app.sidebar')
    <button type="button" class="sidebar-backdrop border-0 bg-transparent" id="sidebar-backdrop" aria-label="{{ __('app.layout.close_sidebar') }}"></button>

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

    <script src="{{ $versionedAsset('assets/vendors/js/vendors.min.js') }}"></script>
    <script src="{{ $versionedAsset('assets/js/common-init.min.js') }}"></script>
    <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/layouts-dashboard.min.js') }}"></script>
    @stack('scripts')
</body>
</html>
