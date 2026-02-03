@extends('layouts.app')

@php
    $title = __('app.roles.maintenance.requests.title');
    $subtitle = __('app.roles.maintenance.requests.subtitle');
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
            <h2 class="h6 mb-3">{{ __('app.roles.maintenance.requests.create_title') }}</h2>
            <form method="POST" action="{{ route('role.maintenance.requests.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.logged_at') }}</label>
                    <input class="form-control" type="datetime-local" name="logged_at" value="{{ old('logged_at') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.type') }}</label>
                    <select class="form-select" name="type" required>
                        <option value="preventive">{{ __('app.roles.maintenance.requests.types.preventive') }}</option>
                        <option value="emergency">{{ __('app.roles.maintenance.requests.types.emergency') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.category') }}</label>
                    <input class="form-control" name="category" value="{{ old('category') }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.description') }}</label>
                    <textarea class="form-control" name="description" rows="3" required>{{ old('description') }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.priority') }}</label>
                    <select class="form-select" name="priority" required>
                        <option value="low">{{ __('app.roles.maintenance.requests.priorities.low') }}</option>
                        <option value="medium">{{ __('app.roles.maintenance.requests.priorities.medium') }}</option>
                        <option value="high">{{ __('app.roles.maintenance.requests.priorities.high') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="logged">{{ __('app.roles.maintenance.requests.statuses.logged') }}</option>
                        <option value="assigned">{{ __('app.roles.maintenance.requests.statuses.assigned') }}</option>
                        <option value="in_progress">{{ __('app.roles.maintenance.requests.statuses.in_progress') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.maintenance.requests.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.maintenance.requests.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.maintenance.requests.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.maintenance.requests.list_title') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.maintenance.requests.table.id') }}</th>
                            <th>{{ __('app.roles.maintenance.requests.table.category') }}</th>
                            <th>{{ __('app.roles.maintenance.requests.table.priority') }}</th>
                            <th>{{ __('app.roles.maintenance.requests.table.status') }}</th>
                            <th class="text-end">{{ __('app.roles.maintenance.requests.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($requests as $request)
                            <tr>
                                <td>#{{ $request->id }}</td>
                                <td>{{ $request->category }}</td>
                                <td>{{ $request->priority }}</td>
                                <td>{{ $request->status }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.maintenance.requests.edit', $request) }}">
                                        {{ __('app.roles.maintenance.requests.actions.edit') }}
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.maintenance.requests.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
