@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.reports.maintenance.title');
    $subtitle = __('app.roles.reports.maintenance.subtitle');
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

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="POST" action="{{ route('role.reports.maintenance.export') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.reports.fields.date_from') }}</label>
                    <input class="form-control" type="date" name="date_from">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.reports.fields.date_to') }}</label>
                    <input class="form-control" type="date" name="date_to">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.reports.fields.status') }}</label>
                    <input class="form-control" name="status">
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.reports.actions.export') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.reports.maintenance.table.request') }}</th>
                            <th>{{ __('app.roles.reports.maintenance.table.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td>#{{ $request->id }}</td>
                                <td>{{ $request->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-muted">{{ __('app.roles.reports.maintenance.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
