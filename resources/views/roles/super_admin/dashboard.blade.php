@extends('layouts.app')

@php
    $title = __('app.roles.super_admin.title');
    $subtitle = __('app.roles.super_admin.subtitle');
    $actions = [
        [
            'title' => __('app.roles.super_admin.actions.users.title'),
            'description' => __('app.roles.super_admin.actions.users.description'),
        ],
        [
            'title' => __('app.roles.super_admin.actions.approvals.title'),
            'description' => __('app.roles.super_admin.actions.approvals.description'),
        ],
        [
            'title' => __('app.roles.super_admin.actions.reports.title'),
            'description' => __('app.roles.super_admin.actions.reports.description'),
            'link' => route('role.super_admin.reports'),
        ],
    ];
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <div class="row g-3">
                @foreach ($actions as $action)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <h2 class="h6 mb-2">{{ $action['title'] }}</h2>
                            <p class="text-muted mb-0">{{ $action['description'] }}</p>
                            @if (!empty($action['link']))
                                <a class="btn btn-sm btn-outline-primary mt-3" href="{{ $action['link'] }}">
                                    {{ __('app.common.open_reports') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
