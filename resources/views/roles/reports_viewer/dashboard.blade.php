@extends('layouts.app')

@php
    $title = __('app.roles.reports_viewer.title');
    $subtitle = __('app.roles.reports_viewer.subtitle');
    $actions = [
        [
            'title' => __('app.roles.reports_viewer.actions.agenda.title'),
            'description' => __('app.roles.reports_viewer.actions.agenda.description'),
            'link' => route('role.reports.agenda.index'),
        ],
        [
            'title' => __('app.roles.reports_viewer.actions.revenue.title'),
            'description' => __('app.roles.reports_viewer.actions.revenue.description'),
            'link' => route('role.reports.finance.index'),
        ],
        [
            'title' => __('app.roles.reports_viewer.actions.ops.title'),
            'description' => __('app.roles.reports_viewer.actions.ops.description'),
            'link' => route('role.reports.index'),
        ],
        [
            'title' => __('app.roles.reports_viewer.actions.kpis.title'),
            'description' => __('app.roles.reports_viewer.actions.kpis.description'),
            'link' => route('role.reports.kpis.index'),
        ],
    ];
@endphp

@section('page_title', $title)
@section('breadcrumb_current', $title)

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
