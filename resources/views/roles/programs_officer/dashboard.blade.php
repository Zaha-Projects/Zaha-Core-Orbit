@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.programs_officer.title');
    $subtitle = __('app.roles.programs_officer.subtitle');
    $actions = [
        [
            'title' => __('app.roles.programs_officer.actions.add_activity.title'),
            'description' => __('app.roles.programs_officer.actions.add_activity.description'),
            'link' => route('role.relations.activities.index'),
        ],
        [
            'title' => __('app.roles.programs_officer.actions.attachments.title'),
            'description' => __('app.roles.programs_officer.actions.attachments.description'),
            'link' => route('role.relations.activities.index'),
        ],
        [
            'title' => __('app.roles.programs_officer.actions.approval_followup.title'),
            'description' => __('app.roles.programs_officer.actions.approval_followup.description'),
            'link' => route('role.programs.approvals.index'),
        ],
    ];
@endphp

@section('page_title', $title)
@section('breadcrumb_current', $title)

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
