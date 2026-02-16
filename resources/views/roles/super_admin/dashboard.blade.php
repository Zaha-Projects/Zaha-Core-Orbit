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

@section('content')
    <div class="row g-4">
        <div class="col-12 col-lg-3">
            @include('pages.access.partials.sidebar')
        </div>
        <div class="col-12 col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h1 class="h4 mb-3">{{ $title }}</h1>
                    <p class="text-muted mb-4">{{ $subtitle }}</p>
                    <div class="row g-3">
                        @foreach ($actions as $action)
                            <div class="col-12 col-md-6">
                                <div class="border rounded p-3 h-100">
                                    <h2 class="h6 mb-2">{{ $action['title'] }}</h2>
                                    <p class="text-muted mb-0">{{ $action['description'] }}</p>
                                    @if (!empty($action['link']))
                                        <a class="btn btn-sm btn-outline-primary mt-3" href="{{ $action['link'] }}">
                                            {{ __('app.common.open_section') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
