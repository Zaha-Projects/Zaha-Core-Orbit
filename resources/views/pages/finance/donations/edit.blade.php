@extends('layouts.app')

@php
    $title = __('app.roles.finance.donations.edit_title');
    $subtitle = __('app.roles.finance.donations.subtitle');
@endphp

@section('sidebar')
    @include('pages.finance.partials.sidebar')
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.finance.donations.update', $donationCash) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.donor_type') }}</label>
                    <input class="form-control" name="donor_type" value="{{ $donationCash->donor_type }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.donor_name') }}</label>
                    <input class="form-control" name="donor_name" value="{{ $donationCash->donor_name }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.contact_person') }}</label>
                    <input class="form-control" name="contact_person" value="{{ $donationCash->contact_person }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ $donationCash->phone }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.date') }}</label>
                    <input class="form-control" type="date" name="date" value="{{ optional($donationCash->date)->format('Y-m-d') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.amount') }}</label>
                    <input class="form-control" type="number" step="0.01" name="amount" value="{{ $donationCash->amount }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.payment_method') }}</label>
                    <input class="form-control" name="payment_method" value="{{ $donationCash->payment_method }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.receipt_no') }}</label>
                    <input class="form-control" name="receipt_no" value="{{ $donationCash->receipt_no }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.purpose_type') }}</label>
                    <input class="form-control" name="purpose_type" value="{{ $donationCash->purpose_type }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.monthly_activity') }}</label>
                    <select class="form-select" name="monthly_activity_id">
                        <option value="">{{ __('app.roles.finance.donations.fields.monthly_activity_placeholder') }}</option>
                        @foreach ($activities as $activity)
                            <option value="{{ $activity->id }}" @selected($donationCash->monthly_activity_id === $activity->id)>
                                {{ $activity->title }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.finance_status') }}</label>
                    <select class="form-select" name="finance_status" required>
                        <option value="pending" @selected($donationCash->finance_status === 'pending')>{{ __('app.roles.finance.donations.statuses.pending') }}</option>
                        <option value="received" @selected($donationCash->finance_status === 'received')>{{ __('app.roles.finance.donations.statuses.received') }}</option>
                        <option value="reconciled" @selected($donationCash->finance_status === 'reconciled')>{{ __('app.roles.finance.donations.statuses.reconciled') }}</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.finance.donations.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
