@php
    $locale = app()->getLocale();
    $isRtl = $locale === 'ar';
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', __('app.common.app_name')) }}</title>
    <link rel="shortcut icon" type="image/png" href="{{ asset('assets/images/logos/favicon.png') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/styles.min.css') }}" />
</head>
<body class="bg-light">
<div class="min-vh-100 d-flex align-items-center justify-content-center py-5 px-3">
    <div class="w-100" style="max-width: 460px;">
        <div class="text-center mb-4">
            <img src="{{ asset('assets/images/logos/logo.svg') }}" alt="{{ __('app.common.app_name') }}" style="max-height: 48px;">
            <h1 class="h4 mt-3 mb-1">{{ __('app.common.app_name') }}</h1>
            <p class="text-muted mb-0">{{ __('app.welcome.subtitle') }}</p>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4 p-md-5">
                @yield('content')
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
