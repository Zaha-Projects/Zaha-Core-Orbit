@extends('layouts.app')

@php
    $title = __('app.roles.maintenance.requests.create_title');
    $subtitle = __('app.roles.maintenance.requests.subtitle');
@endphp


@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.maintenance.requests.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.logged_at') }}</label>
                    <input class="form-control" type="datetime-local" name="logged_at" value="{{ old('logged_at') }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.type') }}</label>
                    <select class="form-select" name="type" >
                        <option value="preventive">{{ __('app.roles.maintenance.requests.types.preventive') }}</option>
                        <option value="emergency">{{ __('app.roles.maintenance.requests.types.emergency') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.category') }}</label>
                    <input class="form-control" name="category" value="{{ old('category') }}" >
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.description') }}</label>
                    <textarea class="form-control" name="description" rows="3" >{{ old('description') }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.priority') }}</label>
                    <select class="form-select" name="priority" >
                        <option value="low">{{ __('app.roles.maintenance.requests.priorities.low') }}</option>
                        <option value="medium">{{ __('app.roles.maintenance.requests.priorities.medium') }}</option>
                        <option value="high">{{ __('app.roles.maintenance.requests.priorities.high') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.status') }}</label>
                    <select class="form-select" name="status" >
                        <option value="logged">{{ __('app.roles.maintenance.requests.statuses.logged') }}</option>
                        <option value="assigned">{{ __('app.roles.maintenance.requests.statuses.assigned') }}</option>
                        <option value="in_progress">{{ __('app.roles.maintenance.requests.statuses.in_progress') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" >
                        <option value="">{{ __('app.roles.maintenance.requests.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    
</div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.maintenance.requests.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
