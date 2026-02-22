@extends('layouts.app')

@php
    $title = __('app.roles.staff.title');
    $subtitle = __('app.roles.staff.subtitle');
    $actions = [
        [
            'title' => __('app.roles.staff.actions.agenda.title'),
            'description' => __('app.roles.staff.actions.agenda.description'),
            'link' => route('role.staff.agenda.index'),
        ],
        [
            'title' => __('app.roles.staff.actions.activities.title'),
            'description' => __('app.roles.staff.actions.activities.description'),
            'link' => route('role.staff.activities.index'),
        ],
        [
            'title' => __('app.roles.staff.actions.summary.title'),
            'description' => __('app.roles.staff.actions.summary.description'),
        ],
    ];
@endphp

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
