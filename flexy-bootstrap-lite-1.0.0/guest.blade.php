{{-- resources/views/layouts/guest.blade.php --}}
@include('layouts.header')

<main>
  {{-- Guest pages --}}
  @hasSection('content')
    @yield('content')
  @else
    <div class="container py-4">
      {{ $slot ?? '' }}
    </div>
  @endif
</main>

@include('layouts.footer')
