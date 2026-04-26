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
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="mb-3 text-end">
        <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.reports.kpis.index') }}">{{ __('app.roles.reports.actions.open_kpis') }}</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.reports.filters_title') }}</h2>
            <form method="POST" action="{{ route('role.reports.export') }}" class="row g-3">
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
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.reports.fields.branch') }}</label>
                    <select class="form-select" name="branch_id">
                        <option value="">{{ __('app.roles.reports.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    
</div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.reports.actions.export') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
