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
<div class="min-vh-100 d-flex align-items-center justify-content-center px-3">
    <div class="card border-0 shadow-sm" style="max-width: 560px; width: 100%;">
        <div class="card-body p-4 p-md-5 text-center">
            <h1 class="h3 mb-3">{{ __('app.common.app_name') }}</h1>
            <p class="text-muted mb-4">{{ $message ?? __('app.common.database_unavailable') }}</p>
            <a href="{{ url()->current() }}" class="btn btn-primary px-4">{{ __('app.common.open_section') }}</a>
        </div>
    </div>
</div>
<script src="{{ asset('assets/libs/bootstrap/dist/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>
