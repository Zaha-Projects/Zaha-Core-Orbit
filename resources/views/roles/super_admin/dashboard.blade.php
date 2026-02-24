@extends('layouts.app')

@php
    $title = __('app.roles.super_admin.title');
    $subtitle = __('app.roles.super_admin.subtitle');
    $actions = [
        [
            'title' => __('app.roles.super_admin.actions.users.title'),
            'description' => __('app.roles.super_admin.actions.users.description'),
            'link' => route('role.super_admin.users'),
        ],
        [
            'title' => __('app.roles.super_admin.actions.branches.title'),
            'description' => __('app.roles.super_admin.actions.branches.description'),
            'link' => route('role.super_admin.branches'),
        ],
        [
            'title' => __('app.roles.super_admin.actions.centers.title'),
            'description' => __('app.roles.super_admin.actions.centers.description'),
            'link' => route('role.super_admin.centers'),
        ],
        [
            'title' => __('app.roles.super_admin.actions.approvals.title'),
            'description' => __('app.roles.super_admin.actions.approvals.description'),
            'link' => route('role.super_admin.approvals'),
        ],
        [
            'title' => __('app.roles.super_admin.actions.roles.title'),
            'description' => __('app.roles.super_admin.actions.roles.description'),
            'link' => route('role.super_admin.roles'),
        ],
        [
            'title' => __('app.roles.super_admin.actions.reports.title'),
            'description' => __('app.roles.super_admin.actions.reports.description'),
            'link' => route('role.super_admin.reports'),
        ],
    ];
@endphp

@section('page_title', $title)
@section('breadcrumb_current', $title)

@section('content')
    @include('roles.partials.dashboard-actions')
@endsection
