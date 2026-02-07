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
                                    {{ __('app.common.open_section') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
