@extends('layouts.app')

@php
    $title = __('app.roles.finance.bookings.edit_title');
    $subtitle = __('app.roles.finance.bookings.subtitle');
@endphp


@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.finance.bookings.update', $booking) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.received_at') }}</label>
                    <input class="form-control" type="datetime-local" name="received_at" value="{{ $booking->received_at?->format('Y-m-d\\TH:i') }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.booking_date') }}</label>
                    <input class="form-control" type="date" name="booking_date" value="{{ optional($booking->booking_date)->format('Y-m-d') }}" >
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.time_from') }}</label>
                    <input class="form-control" type="time" name="time_from" value="{{ $booking->time_from?->format('H:i') }}" >
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.time_to') }}</label>
                    <input class="form-control" type="time" name="time_to" value="{{ $booking->time_to?->format('H:i') }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.received_by') }}</label>
                    <input class="form-control" name="received_by" value="{{ $booking->received_by }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.customer_name') }}</label>
                    <input class="form-control" name="customer_name" value="{{ $booking->customer_name }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.facility_name') }}</label>
                    <input class="form-control" name="facility_name" value="{{ $booking->facility_name }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.payment_type') }}</label>
                    <input class="form-control" name="payment_type" value="{{ $booking->payment_type }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.receipt_ref') }}</label>
                    <input class="form-control" name="receipt_ref" value="{{ $booking->receipt_ref }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.paid_at') }}</label>
                    <input class="form-control" type="datetime-local" name="paid_at" value="{{ $booking->paid_at?->format('Y-m-d\\TH:i') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.discount_amount') }}</label>
                    <input class="form-control" type="number" step="0.01" name="discount_amount" value="{{ $booking->discount_amount }}">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.discount_reason') }}</label>
                    <input class="form-control" name="discount_reason" value="{{ $booking->discount_reason }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.status') }}</label>
                    <select class="form-select" name="status" >
                        <option value="pending" @selected($booking->status === 'pending')>{{ __('app.roles.finance.bookings.statuses.pending') }}</option>
                        <option value="confirmed" @selected($booking->status === 'confirmed')>{{ __('app.roles.finance.bookings.statuses.confirmed') }}</option>
                        <option value="paid" @selected($booking->status === 'paid')>{{ __('app.roles.finance.bookings.statuses.paid') }}</option>
                        <option value="completed" @selected($booking->status === 'completed')>{{ __('app.roles.finance.bookings.statuses.completed') }}</option>
                        <option value="cancelled" @selected($booking->status === 'cancelled')>{{ __('app.roles.finance.bookings.statuses.cancelled') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" >
                        <option value="">{{ __('app.roles.finance.bookings.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($booking->branch_id === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    
</div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.finance.bookings.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
