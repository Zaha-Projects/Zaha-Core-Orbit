@extends('layouts.app')

@php
    $title = __('app.roles.transport_officer.title');
    $subtitle = __('app.roles.transport_officer.subtitle');
    $actions = [
        [
            'title' => __('app.roles.transport_officer.actions.scheduling.title'),
            'description' => __('app.roles.transport_officer.actions.scheduling.description'),
            'link' => route('role.transport.trips.index'),
        ],
        [
            'title' => __('app.roles.transport_officer.actions.fleet.title'),
            'description' => __('app.roles.transport_officer.actions.fleet.description'),
            'link' => route('role.transport.vehicles.index'),
        ],
        [
            'title' => __('app.roles.transport_officer.actions.reports.title'),
            'description' => __('app.roles.transport_officer.actions.reports.description'),
            'link' => route('role.transport.drivers.index'),
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
