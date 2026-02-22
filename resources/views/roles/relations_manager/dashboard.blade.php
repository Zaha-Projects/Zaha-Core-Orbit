@extends('layouts.app')

@php
    $title = __('app.roles.relations_manager.title');
    $subtitle = __('app.roles.relations_manager.subtitle');
    $actions = [
        [
            'title' => __('app.roles.relations_manager.actions.agenda.title'),
            'description' => __('app.roles.relations_manager.actions.agenda.description'),
            'link' => route('role.relations.agenda.index'),
        ],
        [
            'title' => __('app.roles.relations_manager.actions.approvals.title'),
            'description' => __('app.roles.relations_manager.actions.approvals.description'),
            'link' => route('role.relations.approvals.index'),
        ],
        [
            'title' => __('app.roles.relations_manager.actions.reports.title'),
            'description' => __('app.roles.relations_manager.actions.reports.description'),
        ],
    ];
@endphp

@section('page_title', $title)
@section('breadcrumb_current', $title)

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
