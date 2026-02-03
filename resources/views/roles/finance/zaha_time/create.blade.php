@extends('layouts.app')

@php
    $title = __('app.roles.finance.zaha_time.create_title');
    $subtitle = __('app.roles.finance.zaha_time.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.finance.zaha_time.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.received_at') }}</label>
                    <input class="form-control" type="datetime-local" name="received_at" value="{{ old('received_at') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.booking_date') }}</label>
                    <input class="form-control" type="date" name="booking_date" value="{{ old('booking_date') }}" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.time_from') }}</label>
                    <input class="form-control" type="time" name="time_from" value="{{ old('time_from') }}" required>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.time_to') }}</label>
                    <input class="form-control" type="time" name="time_to" value="{{ old('time_to') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.entity_type') }}</label>
                    <input class="form-control" name="entity_type" value="{{ old('entity_type') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.contact_person') }}</label>
                    <input class="form-control" name="contact_person" value="{{ old('contact_person') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ old('phone') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.children_count') }}</label>
                    <input class="form-control" type="number" min="0" name="children_count" value="{{ old('children_count', 0) }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.payment_cash_ref') }}</label>
                    <input class="form-control" name="payment_cash_ref" value="{{ old('payment_cash_ref') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.payment_electronic_ref') }}</label>
                    <input class="form-control" name="payment_electronic_ref" value="{{ old('payment_electronic_ref') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.discount_amount') }}</label>
                    <input class="form-control" type="number" step="0.01" name="discount_amount" value="{{ old('discount_amount', 0) }}">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.discount_reason') }}</label>
                    <input class="form-control" name="discount_reason" value="{{ old('discount_reason') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="pending">{{ __('app.roles.finance.zaha_time.statuses.pending') }}</option>
                        <option value="confirmed">{{ __('app.roles.finance.zaha_time.statuses.confirmed') }}</option>
                        <option value="paid">{{ __('app.roles.finance.zaha_time.statuses.paid') }}</option>
                        <option value="completed">{{ __('app.roles.finance.zaha_time.statuses.completed') }}</option>
                        <option value="cancelled">{{ __('app.roles.finance.zaha_time.statuses.cancelled') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.finance.zaha_time.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.zaha_time.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.finance.zaha_time.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.finance.zaha_time.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
