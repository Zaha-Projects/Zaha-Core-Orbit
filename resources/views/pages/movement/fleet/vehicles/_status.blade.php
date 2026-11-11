@php($badge = ['operational' => 'success', 'maintenance' => 'warning', 'out_of_service' => 'danger', 'inactive' => 'secondary', 'pending_verification' => 'info'][$vehicle->operational_status] ?? 'secondary')
<span class="badge bg-{{ $badge }}">{{ __('movement.statuses.'.$vehicle->operational_status) }}</span>
@if($vehicle->trashed())<span class="badge bg-danger">{{ __('movement.common.deleted') }}</span>@endif
