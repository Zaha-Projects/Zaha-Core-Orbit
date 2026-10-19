@extends('layouts.app')

@php
    $title = __('app.roles.reports.monthly.title');
    $subtitle = __('app.roles.reports.monthly.subtitle');
@endphp


@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.reports.monthly.table.activity') }}</th>
                            <th>{{ __('app.roles.reports.monthly.table.date') }}</th>
                            <th>{{ __('app.roles.reports.monthly.table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td>{{ $activity->title }}</td>
                                <td>{{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</td>
                                <td>{{ $activity->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">{{ __('app.roles.reports.monthly.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
