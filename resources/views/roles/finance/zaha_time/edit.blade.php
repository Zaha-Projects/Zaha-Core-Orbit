@extends('layouts.app')

@php
    $title = __('app.roles.finance.zaha_time.edit_title');
    $subtitle = __('app.roles.finance.zaha_time.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.finance.zaha_time.update', $zahaTimeBooking) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.received_at') }}</label>
                    <input class="form-control" type="datetime-local" name="received_at" value="{{ $zahaTimeBooking->received_at?->format('Y-m-d\\TH:i') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.booking_date') }}</label>
                    <input class="form-control" type="date" name="booking_date" value="{{ optional($zahaTimeBooking->booking_date)->format('Y-m-d') }}" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.time_from') }}</label>
                    <input class="form-control" type="time" name="time_from" value="{{ $zahaTimeBooking->time_from?->format('H:i') }}" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.time_to') }}</label>
                    <input class="form-control" type="time" name="time_to" value="{{ $zahaTimeBooking->time_to?->format('H:i') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.entity_type') }}</label>
                    <input class="form-control" name="entity_type" value="{{ $zahaTimeBooking->entity_type }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.contact_person') }}</label>
                    <input class="form-control" name="contact_person" value="{{ $zahaTimeBooking->contact_person }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ $zahaTimeBooking->phone }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.children_count') }}</label>
                    <input class="form-control" type="number" min="0" name="children_count" value="{{ $zahaTimeBooking->children_count }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.payment_cash_ref') }}</label>
                    <input class="form-control" name="payment_cash_ref" value="{{ $zahaTimeBooking->payment_cash_ref }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.payment_electronic_ref') }}</label>
                    <input class="form-control" name="payment_electronic_ref" value="{{ $zahaTimeBooking->payment_electronic_ref }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.discount_amount') }}</label>
                    <input class="form-control" type="number" step="0.01" name="discount_amount" value="{{ $zahaTimeBooking->discount_amount }}">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.discount_reason') }}</label>
                    <input class="form-control" name="discount_reason" value="{{ $zahaTimeBooking->discount_reason }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="pending" @selected($zahaTimeBooking->status === 'pending')>{{ __('app.roles.finance.zaha_time.statuses.pending') }}</option>
                        <option value="confirmed" @selected($zahaTimeBooking->status === 'confirmed')>{{ __('app.roles.finance.zaha_time.statuses.confirmed') }}</option>
                        <option value="paid" @selected($zahaTimeBooking->status === 'paid')>{{ __('app.roles.finance.zaha_time.statuses.paid') }}</option>
                        <option value="completed" @selected($zahaTimeBooking->status === 'completed')>{{ __('app.roles.finance.zaha_time.statuses.completed') }}</option>
                        <option value="cancelled" @selected($zahaTimeBooking->status === 'cancelled')>{{ __('app.roles.finance.zaha_time.statuses.cancelled') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.finance.zaha_time.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($zahaTimeBooking->branch_id === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.finance.zaha_time.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}" @selected($zahaTimeBooking->center_id === $center->id)>
                                {{ $center->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.finance.zaha_time.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
