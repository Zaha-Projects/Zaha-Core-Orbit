@extends('layouts.app')

@php
    $title = __('app.roles.reports.title');
    $subtitle = __('app.roles.reports.subtitle');
@endphp


@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-info">{{ session('status') }}</div>
    @endif

    <div class="row g-3">
        @foreach ($reports as $report)
            <div class="col-12 col-md-4">
                <a href="{{ route($report['route']) }}" class="card shadow-sm h-100 text-decoration-none text-reset">
                    <div class="card-body">
                        <h2 class="h6 mb-2"><i class="{{ $report['icon'] }} me-1"></i> {{ $report['title'] }}</h2>
                        <p class="text-muted small mb-0">{{ $report['description'] }}</p>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection
