{{-- resources/views/layouts/main.blade.php --}}
@include('layouts.header')

<main class="landing-main">
    @hasSection('content')
        @yield('content')
    @else
        {{ $slot ?? '' }}
    @endif
</main>

@include('volunteer.partials.floating-widget')

@include('layouts.footer')
