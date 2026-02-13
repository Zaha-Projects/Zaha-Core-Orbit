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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex flex-column min-vh-100">
    @include('layouts.app.header')

    <div class="container-fluid flex-grow-1">
        <div class="row g-0 h-100">
            <div class="col-12 col-lg-3 col-xl-2">
                @include('layouts.app.sidebar')
            </div>

            <main class="col-12 col-lg-9 col-xl-10 p-3 p-lg-4">
                @yield('content')
            </main>
        </div>
    </div>

    @include('layouts.app.footer')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
