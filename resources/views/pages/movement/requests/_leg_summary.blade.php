{{-- One leg, read-only. Times are shown in the business timezone. --}}
<div class="small">
    <strong>{{ __('movement.requests.'.$leg->leg_type) }}:</strong>
    {{ $leg->originLabel() }} → {{ $leg->destinationLabel() }}
    · <i class="fas fa-users"></i> {{ $leg->passenger_count }}
    · <i class="far fa-clock"></i> {{ \App\Modules\Movement\Support\MovementTime::toLocal($leg->required_arrival_at)->format('Y-m-d H:i') }}
</div>
