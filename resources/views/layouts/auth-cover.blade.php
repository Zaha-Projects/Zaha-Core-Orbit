@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $theme = session('ui.theme', 'light');
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
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
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/auth-arabic-login.min.css') }}" />
    @stack('styles')
</head>
<body class="{{ $theme === 'dark' ? 'app-skin-dark' : 'app-skin-light' }} guest-page-body auth-shell" data-locale="{{ $locale }}">
<main class="auth-arabic-layout">
    <div class="container py-3">
        <div class="d-flex justify-content-end align-items-center gap-2 mb-3">
            <form method="POST" action="{{ route('ui.theme', $nextTheme) }}" class="m-0">
                @csrf
                <button type="submit" class="nxl-head-link border-0 bg-transparent" title="{{ __('app.layout.theme_toggle') }}">
                    <i class="feather-{{ $theme === 'dark' ? 'sun' : 'moon' }}"></i>
                </button>
            </form>
            <div class="lang-toggle" role="group" aria-label="{{ __('app.layout.language_switch') }}">
                <form method="POST" action="{{ route('ui.locale', 'ar') }}" class="m-0 js-locale-switch" data-locale="ar">@csrf<button type="submit" class="lang-toggle__btn {{ $isRtl ? 'is-active' : '' }}">AR</button></form>
                <form method="POST" action="{{ route('ui.locale', 'en') }}" class="m-0 js-locale-switch" data-locale="en">@csrf<button type="submit" class="lang-toggle__btn {{ $isRtl ? '' : 'is-active' }}">EN</button></form>
            </div>
        </div>
        @yield('content')
    </div>
</main>

<script src="{{ $versionedAsset('assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ $versionedAsset('assets/js/common-init.min.js') }}"></script>
<script src="{{ \App\Support\AssetVersion::url('assets/js/pages/layouts-auth-cover.min.js') }}"></script>
@stack('scripts')
</body>
</html>
