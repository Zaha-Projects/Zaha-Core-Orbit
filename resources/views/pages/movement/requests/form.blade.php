@extends('layouts.app')

@php
    $editing = $movementRequest->exists;
    $direction = old('trip_direction', $movementRequest->trip_direction);
    $outbound = $editing ? $movementRequest->legs->firstWhere('leg_type', 'outbound') : null;
    $returnLeg = $editing ? $movementRequest->legs->firstWhere('leg_type', 'return') : null;
@endphp
@section('title', $editing ? __('movement.requests.edit') : __('movement.requests.create'))

@section('content')
<div class="event-module movement-module" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    @include('pages.movement.partials.header', ['icon' => 'fa-route', 'title' => $editing ? __('movement.requests.edit').' '.$movementRequest->reference_no : __('movement.requests.create')])
    @include('pages.movement.partials.alerts')

    <form method="POST" action="{{ $editing ? route('movement.requests.update', $movementRequest) : route('movement.requests.store') }}" data-movement-request-form>
        @csrf
        @if($editing) @method('PUT') @endif
        @if($clientToken)<input type="hidden" name="client_token" value="{{ $clientToken }}">@endif

        <div class="card event-card mb-4">
            <div class="card-body row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="trip-direction">{{ __('movement.requests.direction') }}</label>
                    <select class="form-select" id="trip-direction" name="trip_direction" data-trip-direction>
                        @foreach(\App\Modules\Movement\Models\MovementRequest::DIRECTIONS as $option)
                            <option value="{{ $option }}" {{ $direction === $option ? 'selected' : '' }}>{{ __('movement.directions.'.$option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label" for="purpose">{{ __('movement.requests.purpose') }}</label>
                    <input class="form-control" id="purpose" name="purpose" value="{{ old('purpose', $movementRequest->purpose) }}" required maxlength="2000">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="department">{{ __('movement.requests.department') }}</label>
                    <select class="form-select" id="department" name="department_id">
                        <option value="">{{ __('movement.requests.none') }}</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ ((int) old('department_id', $movementRequest->department_id) === $department->id) ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="branch">{{ __('movement.requests.branch') }}</label>
                    <select class="form-select" id="branch" name="branch_id">
                        <option value="">{{ __('movement.requests.none') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ ((int) old('branch_id', $movementRequest->branch_id) === $branch->id) ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label" for="context-label">{{ __('movement.requests.context_label') }}</label>
                    <input class="form-control" id="context-label" name="context_label" value="{{ old('context_label', $movementRequest->context_label) }}" maxlength="255">
                </div>
                <div class="col-12">
                    <label class="form-label" for="request-notes">{{ __('movement.requests.notes') }}</label>
                    <textarea class="form-control" id="request-notes" name="notes" rows="2" maxlength="5000">{{ old('notes', $movementRequest->notes) }}</textarea>
                </div>
                <div class="col-12 small text-muted">{{ __('movement.requests.timezone_hint', ['timezone' => \App\Modules\Movement\Support\MovementTime::timezone()]) }}</div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-8">
                @include('pages.movement.requests._leg_fields', ['type' => 'outbound', 'leg' => $outbound, 'locations' => $locations])
                <div data-return-leg class="{{ $direction === 'round_trip' ? '' : 'd-none' }}">
                    @include('pages.movement.requests._leg_fields', ['type' => 'return', 'leg' => $returnLeg, 'locations' => $locations])
                </div>
            </div>
            <div class="col-12 col-lg-4">
                <div class="card event-card" data-availability-panel data-endpoint="{{ route('movement.availability.summary') }}">
                    <div class="card-body">
                        <h2 class="event-section-title"><i class="fas fa-gauge"></i> {{ __('movement.requests.availability.title') }}</h2>
                        <p class="small text-warning mb-2">{{ __('movement.requests.availability.label') }}</p>
                        <div data-availability-body class="small text-muted">{{ __('movement.requests.availability.hint', ['hours' => $indicativeHours]) }}</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap mt-3">
            <button class="btn btn-outline-primary" name="submit_action" value="draft">{{ __('movement.requests.save_draft') }}</button>
            <button class="btn btn-primary" name="submit_action" value="submit">{{ __('movement.requests.save_submit') }}</button>
            <a class="btn btn-link" href="{{ $editing ? route('movement.requests.show', $movementRequest) : route('movement.requests.index') }}">{{ __('movement.common.cancel') }}</a>
        </div>
    </form>
</div>
@endsection

@include('pages.movement.partials.assets')
@push('scripts')
    <script type="application/json" id="movement-request-labels">{!! json_encode([
        'type' => __('movement.requests.availability.type'),
        'free' => __('movement.requests.availability.free'),
        'fit' => __('movement.requests.availability.fit'),
        'drivers' => __('movement.requests.availability.drivers'),
        'loading' => __('movement.requests.availability.loading'),
        'error' => __('movement.requests.availability.error'),
        'empty' => __('movement.requests.availability.empty'),
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
    <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/movement-requests.js') }}"></script>
@endpush
