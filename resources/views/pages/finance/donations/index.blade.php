@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.finance.donations.title');
    $subtitle = __('app.roles.finance.donations.subtitle');
@endphp

@section('title', $title)

@section('theme_sidebar_links')
    <li class="side-item {{ request()->routeIs('role.finance.donations.*') ? 'selected' : '' }}">
        <a href="{{ route('role.finance.donations.index') }}"><i class="fas fa-hand-holding-heart"></i><span>{{ __('app.roles.finance.donations.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.finance.bookings.*') ? 'selected' : '' }}">
        <a href="{{ route('role.finance.bookings.index') }}"><i class="fas fa-book"></i><span>{{ __('app.roles.finance.bookings.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.finance.zaha_time.*') ? 'selected' : '' }}">
        <a href="{{ route('role.finance.zaha_time.index') }}"><i class="fas fa-clock"></i><span>{{ __('app.roles.finance.zaha_time.title') }}</span></a>
    </li>
    <li class="side-item {{ request()->routeIs('role.finance.payments.*') ? 'selected' : '' }}">
        <a href="{{ route('role.finance.payments.index') }}"><i class="fas fa-credit-card"></i><span>{{ __('app.roles.finance.payments.title') }}</span></a>
    </li>
@endsection

@section('content')
    <section class="mb-4">
        <div class="card p-4">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </section>

    @if (session('status'))
        <div class="alert alert-success mb-4">{{ session('status') }}</div>
    @endif

    <section class="card p-3 mb-4">
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
                <button class="btn btn-theme" type="submit">{{ __('app.roles.finance.donations.actions.create') }}</button>
            </div>
        </form>
    </section>

    <section class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h6 mb-0">{{ __('app.roles.finance.donations.list_title') }}</h2>
            <span class="badge text-bg-info">{{ __('app.common.notifications') }}</span>
        </div>
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
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.finance.donations.edit', $donation) }}">{{ __('app.roles.finance.donations.actions.edit') }}</a>
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

        <nav aria-label="Donations pagination" class="mt-3">
            <ul class="pagination mb-0">
                <li class="page-item disabled"><span class="page-link">«</span></li>
                <li class="page-item active"><span class="page-link">1</span></li>
                <li class="page-item disabled"><span class="page-link">»</span></li>
            </ul>
        </nav>
    </section>
@endsection
