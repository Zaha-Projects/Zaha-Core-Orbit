@php($badge = ['draft' => 'secondary', 'submitted' => 'primary', 'under_review' => 'info', 'returned' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'dark'][$movementRequest->status] ?? 'secondary')
<span class="badge bg-{{ $badge }}">{{ __('movement.request_statuses.'.$movementRequest->status) }}</span>
