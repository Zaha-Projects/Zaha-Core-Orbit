@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
    $theme = session('ui.theme', 'light');
    $nextTheme = $theme === 'dark' ? 'light' : 'dark';
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
<body class="{{ $theme === 'dark' ? 'app-skin-dark' : 'app-skin-light' }} guest-page-body auth-shell" data-locale="{{ $locale }}">
<main class="auth-minimal-wrapper">
    <div class="container py-3">
        <div class="d-flex justify-content-end align-items-center gap-2">
            <form method="POST" action="{{ route('ui.theme', $nextTheme) }}" class="m-0">
                @csrf
                <button type="submit" class="nxl-head-link border-0 bg-transparent" title="{{ __('app.layout.theme_toggle') }}">
                    <i class="feather-{{ $theme === 'dark' ? 'sun' : 'moon' }}"></i>
                </button>
            </form>
            <form method="POST" action="{{ route('ui.locale', $isRtl ? 'en' : 'ar') }}" class="m-0 js-locale-switch" data-locale="{{ $isRtl ? 'en' : 'ar' }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-light-brand">{{ $isRtl ? 'English' : 'العربية' }}</button>
            </form>
        </div>
    </div>

    <div class="auth-minimal-inner">
        <div class="minimal-card-wrapper">
            <div class="card mb-4 mt-3 mx-4 mx-sm-0 position-relative auth-card">
                <div class="wd-50 bg-white p-2 rounded-circle shadow-lg position-absolute translate-middle top-0 start-50">
                    <img src="{{ asset('assets/images/logo-abbr.png') }}" alt="{{ __('app.common.app_name') }}" class="img-fluid">
                </div>
                <div class="card-body p-sm-5">
                    <div class="text-center mb-4 mt-2">
                        <h1 class="h4 mt-3 mb-1">{{ __('app.common.app_name') }}</h1>
                        <p class="text-muted mb-0">{{ __('app.welcome.subtitle') }}</p>
                    </div>
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
</main>

<script src="{{ asset('assets/vendors/js/vendors.min.js') }}"></script>
<script src="{{ asset('assets/js/common-init.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.js-locale-switch').forEach(function (form) {
            form.addEventListener('submit', function () {
                var locale = form.dataset.locale;
                document.documentElement.setAttribute('lang', locale);
                document.documentElement.setAttribute('dir', locale === 'ar' ? 'rtl' : 'ltr');
            });
        });
    });
</script>
@stack('scripts')
</body>
</html>
