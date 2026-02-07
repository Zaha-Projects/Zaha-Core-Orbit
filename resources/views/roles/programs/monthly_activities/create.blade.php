@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.create_title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.programs.activities.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.title') }}</label>
                    <input class="form-control" name="title" value="{{ old('title') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.activity_date') }}</label>
                    <input class="form-control" type="date" name="activity_date" value="{{ old('activity_date') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.proposed_date') }}</label>
                    <input class="form-control" type="date" name="proposed_date" value="{{ old('proposed_date') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.agenda_event') }}</label>
                    <select class="form-select" name="agenda_event_id">
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.agenda_event_placeholder') }}</option>
                        @foreach ($agendaEvents as $event)
                            <option value="{{ $event->id }}">{{ $event->event_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="draft">{{ __('app.roles.programs.monthly_activities.statuses.draft') }}</option>
                        <option value="submitted">{{ __('app.roles.programs.monthly_activities.statuses.submitted') }}</option>
                        <option value="approved">{{ __('app.roles.programs.monthly_activities.statuses.approved') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.location_type') }}</label>
                    <input class="form-control" name="location_type" value="{{ old('location_type') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.location_details') }}</label>
                    <input class="form-control" name="location_details" value="{{ old('location_details') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.programs.monthly_activities.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
