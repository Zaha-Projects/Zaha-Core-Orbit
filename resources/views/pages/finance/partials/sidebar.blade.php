<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.finance.donations.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.finance.donations.index') }}">
    {{ __('app.roles.finance.donations.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.finance.bookings.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.finance.bookings.index') }}">
    {{ __('app.roles.finance.bookings.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.finance.zaha_time.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.finance.zaha_time.index') }}">
    {{ __('app.roles.finance.zaha_time.title') }}
</a>
<a class="nav-link rounded px-3 py-2 {{ request()->routeIs('role.finance.payments.*') ? 'active bg-light fw-semibold' : 'text-secondary' }}" href="{{ route('role.finance.payments.index') }}">
    {{ __('app.roles.finance.payments.title') }}
</a>
