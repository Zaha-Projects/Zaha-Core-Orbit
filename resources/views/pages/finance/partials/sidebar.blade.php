<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.finance.donations.*') ? 'active' : '' }}" href="{{ route('role.finance.donations.index') }}">
        <span class="nxl-micon"><i class="feather-heart"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.finance.donations.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.finance.bookings.*') ? 'active' : '' }}" href="{{ route('role.finance.bookings.index') }}">
        <span class="nxl-micon"><i class="feather-book-open"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.finance.bookings.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.finance.zaha_time.*') ? 'active' : '' }}" href="{{ route('role.finance.zaha_time.index') }}">
        <span class="nxl-micon"><i class="feather-clock"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.finance.zaha_time.title') }}</span>
    </a>
</li>
<li class="nxl-item">
    <a class="nxl-link {{ request()->routeIs('role.finance.payments.*') ? 'active' : '' }}" href="{{ route('role.finance.payments.index') }}">
        <span class="nxl-micon"><i class="feather-credit-card"></i></span>
        <span class="nxl-mtext">{{ __('app.roles.finance.payments.title') }}</span>
    </a>
</li>
