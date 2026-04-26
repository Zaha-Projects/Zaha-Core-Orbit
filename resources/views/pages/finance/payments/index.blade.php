@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.finance.payments.title');
    $subtitle = __('app.roles.finance.payments.subtitle');
    $payableTypes = [
        \App\Models\DonationCash::class => __('app.roles.finance.payments.payable_types.donations'),
        \App\Models\Booking::class => __('app.roles.finance.payments.payable_types.bookings'),
        \App\Models\ZahaTimeBooking::class => __('app.roles.finance.payments.payable_types.zaha_time'),
    ];
@endphp

@section('title', $title)


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
        <ul class="nav nav-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#payments-create" type="button" role="tab">
                    {{ __('app.roles.finance.payments.create_title') }}
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#payments-list" type="button" role="tab">
                    {{ __('app.roles.finance.payments.list_title') }}
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="payments-create" role="tabpanel">
                <form method="POST" action="{{ route('role.finance.payments.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.finance.payments.fields.payable_type') }}</label>
                        <select class="form-select" name="payable_type" required>
                            <option value="">{{ __('app.roles.finance.payments.fields.payable_type_placeholder') }}</option>
                            @foreach ($payableTypes as $type => $label)
                                <option value="{{ $type }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.finance.payments.fields.payable_id') }}</label>
                        <input class="form-control" name="payable_id" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.finance.payments.fields.method') }}</label>
                        <input class="form-control" name="method" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.finance.payments.fields.amount') }}</label>
                        <input class="form-control" type="number" step="0.01" name="amount" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.finance.payments.fields.reference') }}</label>
                        <input class="form-control" name="reference">
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">{{ __('app.roles.finance.payments.fields.paid_at') }}</label>
                        <input class="form-control" type="datetime-local" name="paid_at" required>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-theme" type="submit">{{ __('app.roles.finance.payments.actions.create') }}</button>
                    </div>
                </form>
            </div>

            <div class="tab-pane fade" id="payments-list" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                        <tr>
                            <th>{{ __('app.roles.finance.payments.table.payable_type') }}</th>
                            <th>{{ __('app.roles.finance.payments.table.amount') }}</th>
                            <th>{{ __('app.roles.finance.payments.table.paid_at') }}</th>
                            <th class="text-end">{{ __('app.roles.finance.payments.table.actions') }}</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($payments ?? [] as $payment)
                            <tr>
                                <td>{{ class_basename($payment->payable_type) }} #{{ $payment->payable_id }}</td>
                                <td>{{ $payment->amount }}</td>
                                <td>{{ $payment->paid_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#payment-{{ $payment->id }}">
                                        {{ __('app.roles.finance.payments.actions.edit') }}
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="payment-{{ $payment->id }}">
                                <td colspan="4">
                                    <div class="card p-3 my-2">
                                        <form method="POST" action="{{ route('role.finance.payments.update', $payment) }}" class="row g-3">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-12 col-md-4">
                                                <label class="form-label">{{ __('app.roles.finance.payments.fields.method') }}</label>
                                                <input class="form-control" name="method" value="{{ $payment->method }}" required>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label">{{ __('app.roles.finance.payments.fields.amount') }}</label>
                                                <input class="form-control" type="number" step="0.01" name="amount" value="{{ $payment->amount }}" required>
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label">{{ __('app.roles.finance.payments.fields.reference') }}</label>
                                                <input class="form-control" name="reference" value="{{ $payment->reference }}">
                                            </div>
                                            <div class="col-12 col-md-4">
                                                <label class="form-label">{{ __('app.roles.finance.payments.fields.paid_at') }}</label>
                                                <input class="form-control" type="datetime-local" name="paid_at" value="{{ $payment->paid_at?->format('Y-m-d\\TH:i') }}" required>
                                            </div>
                                            <div class="col-12 d-flex justify-content-end">
                                                <button class="btn btn-outline-primary btn-sm" type="submit">{{ __('app.roles.finance.payments.actions.save') }}</button>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted">{{ __('app.roles.finance.payments.table.empty') }}</td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <nav aria-label="Payments pagination" class="mt-3">
                    <ul class="pagination mb-0">
                        <li class="page-item disabled"><span class="page-link">«</span></li>
                        <li class="page-item active"><span class="page-link">1</span></li>
                        <li class="page-item disabled"><span class="page-link">»</span></li>
                    </ul>
                </nav>
            </div>
        </div>
    </section>
@endsection
