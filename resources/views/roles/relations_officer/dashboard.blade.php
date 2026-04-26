@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.relations_officer.title');
    $subtitle = __('app.roles.relations_officer.subtitle');
    $actions = [
        [
            'title' => __('app.roles.relations_officer.actions.create_event.title'),
            'description' => __('app.roles.relations_officer.actions.create_event.description'),
            'link' => route('role.relations.agenda.index'),
        ],
        [
            'title' => __('app.roles.relations_officer.actions.notes.title'),
            'description' => __('app.roles.relations_officer.actions.notes.description'),
            'link' => route('role.relations.approvals.index'),
        ],
        [
            'title' => __('app.roles.relations_officer.actions.readiness.title'),
            'description' => __('app.roles.relations_officer.actions.readiness.description'),
        ],
    ];
@endphp

@section('page_title', $title)
@section('breadcrumb_current', $title)

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
