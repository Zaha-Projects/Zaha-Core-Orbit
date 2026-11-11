@php($state = $driver->licenceState())
@php($badge = ['ok' => 'success', 'expiring' => 'warning', 'expired' => 'danger', 'unknown' => 'secondary'][$state])
<span class="badge bg-{{ $badge }}" data-licence-state="{{ $state }}">{{ __('movement.licence_states.'.$state) }}{{ $driver->licence_expires_on ? ' · '.$driver->licence_expires_on->format('Y-m-d') : '' }}</span>
