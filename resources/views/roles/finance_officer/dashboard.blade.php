@extends('layouts.app')

@php
    $title = __('app.roles.finance_officer.title');
    $subtitle = __('app.roles.finance_officer.subtitle');
    $actions = [
        [
            'title' => __('app.roles.finance_officer.actions.donations.title'),
            'description' => __('app.roles.finance_officer.actions.donations.description'),
            'link' => route('role.finance.donations.index'),
        ],
        [
            'title' => __('app.roles.finance_officer.actions.bookings.title'),
            'description' => __('app.roles.finance_officer.actions.bookings.description'),
            'link' => route('role.finance.bookings.index'),
        ],
        [
            'title' => __('app.roles.finance_officer.actions.zaha_time.title'),
            'description' => __('app.roles.finance_officer.actions.zaha_time.description'),
            'link' => route('role.finance.zaha_time.index'),
        ],
        [
            'title' => __('app.roles.finance_officer.actions.payments.title'),
            'description' => __('app.roles.finance_officer.actions.payments.description'),
            'link' => route('role.finance.payments.index'),
        ],
        [
            'title' => __('app.roles.finance_officer.actions.reports.title'),
            'description' => __('app.roles.finance_officer.actions.reports.description'),
        ],
    ];
@endphp

@section('page_title', $title)
@section('breadcrumb_current', $title)

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
