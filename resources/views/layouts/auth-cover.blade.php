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
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/zaha-duralux-overrides.css') }}" />
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/zaha-theme.css') }}" />
</head>
<body class="{{ $theme === 'dark' ? 'app-skin-dark' : 'app-skin-light' }} guest-page-body auth-shell" data-locale="{{ $locale }}">
<main class="auth-cover-wrapper">
    <div class="auth-cover-content-inner">
        <div class="auth-cover-content-wrapper">
            <div class="auth-img">
                <img src="{{ $versionedAsset('assets/images/auth/auth-cover-login-bg.svg') }}" alt="Zaha Core Orbit" class="img-fluid">
            </div>
        </div>
    </div>
    <div class="auth-cover-sidebar-inner">
        <div class="container py-3">
            <div class="d-flex justify-content-end align-items-center gap-2">
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
        </div>
        <div class="auth-cover-card-wrapper">
            <div class="auth-cover-card auth-cover-card--zaha p-sm-5">
                <div class="auth-brand mb-4">
                    <img src="{{ asset('assets/images/zaha-core-orbit-logo.svg') }}" alt="Zaha" class="auth-brand__logo">
                    <img src="{{ asset('assets/images/zaha-core-orbit-mark.svg') }}" alt="Zaha - Core Orbit" class="auth-brand__mark">
                </div>
                <h1 class="fs-20 fw-bolder mb-2 auth-cover-card__title">Zaha - Core Orbit</h1>
                <p class="fs-12 fw-medium text-muted mb-4 auth-cover-card__subtitle">منصة موحدة لإدارة العمليات والتقارير والاعتمادات.</p>
                @yield('content')
            </div>
        </div>
    </div>
</main>

<script src="{{ $versionedAsset('assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ $versionedAsset('assets/js/common-init.min.js') }}"></script>
</body>
</html>
