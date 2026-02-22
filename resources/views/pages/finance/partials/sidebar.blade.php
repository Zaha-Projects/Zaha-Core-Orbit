<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.finance.donations.*') ? 'active' : '' }}" href="{{ route('role.finance.donations.index') }}">
    {{ __('app.roles.finance.donations.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.finance.bookings.*') ? 'active' : '' }}" href="{{ route('role.finance.bookings.index') }}">
    {{ __('app.roles.finance.bookings.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.finance.zaha_time.*') ? 'active' : '' }}" href="{{ route('role.finance.zaha_time.index') }}">
    {{ __('app.roles.finance.zaha_time.title') }}
</a>
</li>
<li class="sidebar-item">
    <a class="sidebar-link {{ request()->routeIs('role.finance.payments.*') ? 'active' : '' }}" href="{{ route('role.finance.payments.index') }}">
    {{ __('app.roles.finance.payments.title') }}
</a>
</li>
