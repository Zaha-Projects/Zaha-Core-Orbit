@extends('layouts.app')

@section('title', $movementRequest->reference_no)

@php
    $user = auth()->user();
    $local = fn ($value) => $value ? \App\Modules\Movement\Support\MovementTime::toLocal($value)->format('Y-m-d H:i') : '—';
    $canEdit = $user->can('update', $movementRequest) && $movementRequest->isEditable();
    $canDecide = $user->can('decide', $movementRequest);
    $canCancel = $user->can('cancel', $movementRequest) && $movementRequest->canTransitionTo('cancelled');
@endphp

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-route', 'title' => __('movement.requests.reference').' '.$movementRequest->reference_no])
    @include('pages.movement.partials.alerts')
    @if(session('info'))<div class="alert alert-info" role="status">{{ session('info') }}</div>@endif

    <div class="card event-card mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between gap-2 flex-wrap mb-3">
                <div>@include('pages.movement.requests._status', ['movementRequest' => $movementRequest]) <span class="badge bg-light text-dark">{{ __('movement.directions.'.$movementRequest->trip_direction) }}</span></div>
                <div class="small text-muted">{{ __('movement.requests.requester') }}: {{ $movementRequest->requester?->name }}</div>
            </div>
            <dl class="row mb-0">
                <dt class="col-12 col-md-3">{{ __('movement.requests.purpose') }}</dt><dd class="col-12 col-md-9">{{ $movementRequest->purpose }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.requests.department') }}</dt><dd class="col-6 col-md-3">{{ $movementRequest->department?->name ?? '—' }}</dd>
                <dt class="col-6 col-md-3">{{ __('movement.requests.branch') }}</dt><dd class="col-6 col-md-3">{{ $movementRequest->branch?->name ?? '—' }}</dd>
                @if($movementRequest->context_label)<dt class="col-6 col-md-3">{{ __('movement.requests.context_label') }}</dt><dd class="col-6 col-md-9">{{ $movementRequest->context_label }}</dd>@endif
                @if($movementRequest->notes)<dt class="col-12 col-md-3">{{ __('movement.requests.notes') }}</dt><dd class="col-12 col-md-9">{{ $movementRequest->notes }}</dd>@endif
                @if($movementRequest->decided_at)<dt class="col-12 col-md-3">{{ __('movement.requests.decided') }}</dt><dd class="col-12 col-md-9">{{ $local($movementRequest->decided_at) }} · {{ $movementRequest->decider?->name }}{{ $movementRequest->decision_comment ? ' — '.$movementRequest->decision_comment : '' }}</dd>@endif
                @if($movementRequest->cancelled_at)<dt class="col-12 col-md-3">{{ __('movement.request_statuses.cancelled') }}</dt><dd class="col-12 col-md-9">{{ __('movement.requests.cancelled_info', ['date' => $local($movementRequest->cancelled_at), 'reason' => $movementRequest->cancellation_reason ?? '—']) }}</dd>@endif
            </dl>
        </div>
    </div>

    @foreach($movementRequest->legs as $leg)
        <div class="card event-card mb-3">
            <div class="card-body">
                <h2 class="event-section-title">{{ __('movement.requests.'.$leg->leg_type) }}</h2>
                <dl class="row mb-0 small">
                    <dt class="col-6 col-md-3">{{ __('movement.requests.origin') }}</dt><dd class="col-6 col-md-3">{{ $leg->originLabel() }}@if($leg->origin_maps_url) <a href="{{ $leg->origin_maps_url }}" target="_blank" rel="noopener"><i class="fas fa-map"></i></a>@endif</dd>
                    <dt class="col-6 col-md-3">{{ __('movement.requests.destination') }}</dt><dd class="col-6 col-md-3">{{ $leg->destinationLabel() }}@if($leg->destination_maps_url) <a href="{{ $leg->destination_maps_url }}" target="_blank" rel="noopener"><i class="fas fa-map"></i></a>@endif</dd>
                    <dt class="col-6 col-md-3">{{ __('movement.requests.passengers') }}</dt><dd class="col-6 col-md-3">{{ $leg->passenger_count }}</dd>
                    <dt class="col-6 col-md-3">{{ __('movement.requests.travel_date') }}</dt><dd class="col-6 col-md-3">{{ $leg->travel_date?->format('Y-m-d') }}</dd>
                    <dt class="col-6 col-md-3">{{ __('movement.requests.required_arrival_at') }}</dt><dd class="col-6 col-md-3">{{ $local($leg->required_arrival_at) }}</dd>
                    <dt class="col-6 col-md-3">{{ __('movement.requests.preferred_departure_at') }}</dt><dd class="col-6 col-md-3">{{ $local($leg->preferred_departure_at) }}</dd>
                    @if($leg->notes)<dt class="col-12 col-md-3">{{ __('movement.requests.notes') }}</dt><dd class="col-12 col-md-9">{{ $leg->notes }}</dd>@endif
                </dl>
            </div>
        </div>
    @endforeach

    <div class="card event-card mb-4">
        <div class="card-body d-flex flex-column gap-3">
            @if($canEdit)
                <div class="d-flex gap-2 flex-wrap">
                    <a class="btn btn-outline-secondary" href="{{ route('movement.requests.edit', $movementRequest) }}">{{ __('movement.common.edit') }}</a>
                    <form method="POST" action="{{ route('movement.requests.submit', $movementRequest) }}">@csrf<button class="btn btn-primary">{{ $movementRequest->status === 'returned' ? __('movement.requests.resubmit') : __('movement.requests.submit') }}</button></form>
                    @if($movementRequest->status === 'draft')
                        <form method="POST" action="{{ route('movement.requests.destroy', $movementRequest) }}" onsubmit="return confirm({{ json_encode(__('movement.common.confirm_delete')) }})">@csrf @method('DELETE')<button class="btn btn-outline-danger">{{ __('movement.requests.delete_draft') }}</button></form>
                    @endif
                </div>
            @endif

            @if($canDecide && in_array($movementRequest->status, ['submitted', 'under_review'], true))
                <div>
                    <h2 class="event-section-title"><i class="fas fa-gavel"></i> {{ __('movement.requests.decision') }}</h2>
                    @if($movementRequest->status === 'submitted')
                        <form method="POST" action="{{ route('movement.requests.review.start', $movementRequest) }}" class="mb-2">@csrf<button class="btn btn-sm btn-outline-info">{{ __('movement.requests.start_review') }}</button></form>
                    @endif
                    <form method="POST" action="{{ route('movement.requests.review.decide', $movementRequest) }}" class="row g-2">
                        @csrf
                        <div class="col-12">
                            <label class="form-label" for="decision-comment">{{ __('movement.requests.comment') }}</label>
                            <textarea class="form-control" id="decision-comment" name="comment" rows="2" maxlength="2000">{{ old('comment') }}</textarea>
                            <div class="form-text">{{ __('movement.requests.comment_hint') }}</div>
                        </div>
                        <div class="col-12 d-flex gap-2 flex-wrap">
                            <button class="btn btn-success" name="decision" value="approve">{{ __('movement.requests.approve') }}</button>
                            <button class="btn btn-warning" name="decision" value="return">{{ __('movement.requests.return_action') }}</button>
                            <button class="btn btn-danger" name="decision" value="reject">{{ __('movement.requests.reject') }}</button>
                        </div>
                    </form>
                </div>
            @endif

            @if($canCancel)
                <form method="POST" action="{{ route('movement.requests.cancel', $movementRequest) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-12 col-md-9">
                        <label class="form-label" for="cancel-reason">{{ __('movement.requests.cancel_reason') }}</label>
                        <input class="form-control" id="cancel-reason" name="reason" maxlength="2000" {{ $movementRequest->status === 'draft' ? '' : 'required' }}>
                    </div>
                    <div class="col-12 col-md-3"><button class="btn btn-outline-danger w-100">{{ __('movement.requests.cancel') }}</button></div>
                </form>
            @endif
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            <h2 class="event-section-title"><i class="fas fa-clock-rotate-left"></i> {{ __('movement.requests.timeline') }}</h2>
            @forelse($timeline as $entry)
                <div class="border-top py-2 small">
                    <strong>{{ __('movement.timeline_actions.'.$entry->action) }}</strong>
                    <span class="text-muted">· {{ $local($entry->created_at) }} · {{ $entry->user?->name ?? '—' }}</span>
                    @if(isset($entry->old_values['status'], $entry->new_values['status']))
                        <div>{{ __('movement.request_statuses.'.$entry->old_values['status']) }} → {{ __('movement.request_statuses.'.$entry->new_values['status']) }}</div>
                    @endif
                    @if(! empty($entry->new_values['comment']))<div class="fst-italic">“{{ $entry->new_values['comment'] }}”</div>@endif
                </div>
            @empty
                @include('pages.movement.partials.empty', ['text' => __('movement.requests.timeline_empty')])
            @endforelse
        </div>
    </div>
</div>
@endsection

@include('pages.movement.partials.assets')
