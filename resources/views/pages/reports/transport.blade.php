@extends('layouts.app')

@php
    $title = __('app.roles.reports.transport.title');
    $subtitle = __('app.roles.reports.transport.subtitle');
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
                            <th>{{ __('app.roles.reports.transport.table.trip_date') }}</th>
                            <th>{{ __('app.roles.reports.transport.table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($trips as $trip)
                            <tr>
                                <td>{{ optional($trip->trip_date)->format('Y-m-d') }}</td>
                                <td>{{ $trip->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted">{{ __('app.roles.reports.transport.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
