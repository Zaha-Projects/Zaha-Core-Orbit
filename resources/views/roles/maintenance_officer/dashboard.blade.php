@extends('layouts.app')

@php
    $title = __('app.roles.maintenance_officer.title');
    $subtitle = __('app.roles.maintenance_officer.subtitle');
    $actions = [
        [
            'title' => __('app.roles.maintenance_officer.actions.requests.title'),
            'description' => __('app.roles.maintenance_officer.actions.requests.description'),
            'link' => route('role.maintenance.requests.index'),
        ],
        [
            'title' => __('app.roles.maintenance_officer.actions.work_details.title'),
            'description' => __('app.roles.maintenance_officer.actions.work_details.description'),
            'link' => route('role.maintenance.requests.index'),
        ],
        [
            'title' => __('app.roles.maintenance_officer.actions.closures.title'),
            'description' => __('app.roles.maintenance_officer.actions.closures.description'),
            'link' => route('role.maintenance.approvals.index'),
        ],
    ];
@endphp

@section('page_title', $title)
@section('breadcrumb_current', $title)

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
