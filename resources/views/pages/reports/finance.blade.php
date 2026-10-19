@extends('layouts.app')

@php
    $title = __('app.roles.reports.finance.title');
    $subtitle = __('app.roles.reports.finance.subtitle');
@endphp


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

    <div class="card shadow-sm">
        <div class="card-body">
            <p class="text-muted mb-0">
                {{ __('app.roles.reports.finance.summary', ['donations' => $donationsCount, 'bookings' => $bookingsCount, 'zaha_time' => $zahaTimeBookingsCount]) }}
            </p>
        </div>
    </div>
@endsection
