@extends('layouts.app')

@php
    $title = __('app.roles.finance.donations.title');
    $subtitle = __('app.roles.finance.donations.subtitle');
@endphp

@section('sidebar')
    @include('pages.finance.partials.sidebar')
@endsection

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.finance.donations.create_title') }}</h2>
            <form method="POST" action="{{ route('role.finance.donations.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.donor_type') }}</label>
                    <input class="form-control" name="donor_type" value="{{ old('donor_type') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.donor_name') }}</label>
                    <input class="form-control" name="donor_name" value="{{ old('donor_name') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.contact_person') }}</label>
                    <input class="form-control" name="contact_person" value="{{ old('contact_person') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.phone') }}</label>
                    <input class="form-control" name="phone" value="{{ old('phone') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.date') }}</label>
                    <input class="form-control" type="date" name="date" value="{{ old('date') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.amount') }}</label>
                    <input class="form-control" type="number" step="0.01" name="amount" value="{{ old('amount') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.payment_method') }}</label>
                    <input class="form-control" name="payment_method" value="{{ old('payment_method') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.receipt_no') }}</label>
                    <input class="form-control" name="receipt_no" value="{{ old('receipt_no') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.purpose_type') }}</label>
                    <input class="form-control" name="purpose_type" value="{{ old('purpose_type') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.monthly_activity') }}</label>
                    <select class="form-select" name="monthly_activity_id">
                        <option value="">{{ __('app.roles.finance.donations.fields.monthly_activity_placeholder') }}</option>
                        @foreach ($activities as $activity)
                            <option value="{{ $activity->id }}">{{ $activity->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.finance.donations.fields.finance_status') }}</label>
                    <select class="form-select" name="finance_status" required>
                        <option value="pending">{{ __('app.roles.finance.donations.statuses.pending') }}</option>
                        <option value="received">{{ __('app.roles.finance.donations.statuses.received') }}</option>
                        <option value="reconciled">{{ __('app.roles.finance.donations.statuses.reconciled') }}</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.finance.donations.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.finance.donations.list_title') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.finance.donations.table.donor_name') }}</th>
                            <th>{{ __('app.roles.finance.donations.table.amount') }}</th>
                            <th>{{ __('app.roles.finance.donations.table.date') }}</th>
                            <th>{{ __('app.roles.finance.donations.table.status') }}</th>
                            <th class="text-end">{{ __('app.roles.finance.donations.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($donations as $donation)
                            <tr>
                                <td>{{ $donation->donor_name }}</td>
                                <td>{{ $donation->amount }}</td>
                                <td>{{ optional($donation->date)->format('Y-m-d') }}</td>
                                <td>{{ $donation->finance_status }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.finance.donations.edit', $donation) }}">
                                        {{ __('app.roles.finance.donations.actions.edit') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.finance.donations.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
