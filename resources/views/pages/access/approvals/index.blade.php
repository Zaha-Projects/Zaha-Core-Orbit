@extends('layouts.app')


@php
    $title = __('app.roles.super_admin.approvals.title');
    $subtitle = __('app.roles.super_admin.approvals.subtitle');
@endphp


@section('content')
    <div class="row g-4">
        <div class="col-12">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="h4 mb-2">{{ $title }}</h1>
                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                </div>
            </div>

            <div class="row g-3">
                @foreach ($steps as $step)
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h2 class="h6 mb-3">{{ $step['title'] }}</h2>
                                <ul class="list-group list-group-flush">
                                    @foreach ($step['items'] as $item)
                                        <li class="list-group-item d-flex align-items-start">
                                            <span class="badge bg-primary me-3">{{ $loop->iteration }}</span>
                                            <div>
                                                <div class="fw-semibold">{{ $item['label'] }}</div>
                                                <div class="text-muted small">{{ $item['owner'] }}</div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
