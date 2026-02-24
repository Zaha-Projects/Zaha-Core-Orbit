@extends('layouts.app')

@php
    $title = __('app.roles.transport_officer.title');
    $subtitle = __('app.roles.transport_officer.subtitle');
    $actions = [
        [
            'title' => __('app.roles.transport_officer.actions.scheduling.title'),
            'description' => __('app.roles.transport_officer.actions.scheduling.description'),
            'link' => route('role.transport.trips.index'),
        ],
        [
            'title' => __('app.roles.transport_officer.actions.transport_requests.title'),
            'description' => __('app.roles.transport_officer.actions.transport_requests.description'),
            'link' => route('role.transport.requests.index'),
        ],
        [
            'title' => __('app.roles.transport_officer.actions.fleet.title'),
            'description' => __('app.roles.transport_officer.actions.fleet.description'),
            'link' => route('role.transport.vehicles.index'),
        ],
        [
            'title' => __('app.roles.transport_officer.actions.reports.title'),
            'description' => __('app.roles.transport_officer.actions.reports.description'),
            'link' => route('role.transport.drivers.index'),
        ],
    ];
@endphp

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
