@extends('layouts.app')

@php
    $title = __('app.roles.programs_manager.title');
    $subtitle = __('app.roles.programs_manager.subtitle');
    $actions = [
        [
            'title' => __('app.roles.programs_manager.actions.approvals.title'),
            'description' => __('app.roles.programs_manager.actions.approvals.description'),
            'link' => route('role.programs.approvals.index'),
        ],
        [
            'title' => __('app.roles.programs_manager.actions.tracking.title'),
            'description' => __('app.roles.programs_manager.actions.tracking.description'),
            'link' => route('role.relations.activities.index'),
        ],
        [
            'title' => __('app.roles.programs_manager.actions.reports.title'),
            'description' => __('app.roles.programs_manager.actions.reports.description'),
        ],
    ];
@endphp

@section('page_title', $title)
@section('breadcrumb_current', $title)

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
