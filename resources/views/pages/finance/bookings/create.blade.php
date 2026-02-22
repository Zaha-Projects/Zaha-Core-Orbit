@extends('layouts.app')

@php
    $title = __('app.roles.finance.bookings.create_title');
    $subtitle = __('app.roles.finance.bookings.subtitle');
@endphp


@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.finance.bookings.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.received_at') }}</label>
                    <input class="form-control" type="datetime-local" name="received_at" value="{{ old('received_at') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.booking_date') }}</label>
                    <input class="form-control" type="date" name="booking_date" value="{{ old('booking_date') }}" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.time_from') }}</label>
                    <input class="form-control" type="time" name="time_from" value="{{ old('time_from') }}" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.time_to') }}</label>
                    <input class="form-control" type="time" name="time_to" value="{{ old('time_to') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.received_by') }}</label>
                    <input class="form-control" name="received_by" value="{{ old('received_by') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.customer_name') }}</label>
                    <input class="form-control" name="customer_name" value="{{ old('customer_name') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.facility_name') }}</label>
                    <input class="form-control" name="facility_name" value="{{ old('facility_name') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.payment_type') }}</label>
                    <input class="form-control" name="payment_type" value="{{ old('payment_type') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.receipt_ref') }}</label>
                    <input class="form-control" name="receipt_ref" value="{{ old('receipt_ref') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.paid_at') }}</label>
                    <input class="form-control" type="datetime-local" name="paid_at" value="{{ old('paid_at') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.discount_amount') }}</label>
                    <input class="form-control" type="number" step="0.01" name="discount_amount" value="{{ old('discount_amount', 0) }}">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.discount_reason') }}</label>
                    <input class="form-control" name="discount_reason" value="{{ old('discount_reason') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="pending">{{ __('app.roles.finance.bookings.statuses.pending') }}</option>
                        <option value="confirmed">{{ __('app.roles.finance.bookings.statuses.confirmed') }}</option>
                        <option value="paid">{{ __('app.roles.finance.bookings.statuses.paid') }}</option>
                        <option value="completed">{{ __('app.roles.finance.bookings.statuses.completed') }}</option>
                        <option value="cancelled">{{ __('app.roles.finance.bookings.statuses.cancelled') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.finance.bookings.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.bookings.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.finance.bookings.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.finance.bookings.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
