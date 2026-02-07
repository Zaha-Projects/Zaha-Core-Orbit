@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.create_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.relations.agenda.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_name') }}</label>
                    <input class="form-control" name="event_name" value="{{ old('event_name') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_date') }}</label>
                    <input class="form-control" type="date" name="event_date" value="{{ old('event_date') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_category') }}</label>
                    <input class="form-control" name="event_category" value="{{ old('event_category') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.target_type') }}</label>
                    <select class="form-select" name="target_type">
                        <option value="">{{ __('app.roles.relations.agenda.fields.target_type_placeholder') }}</option>
                        <option value="branch">{{ __('app.roles.relations.agenda.target_types.branch') }}</option>
                        <option value="center">{{ __('app.roles.relations.agenda.target_types.center') }}</option>
                        <option value="department">{{ __('app.roles.relations.agenda.target_types.department') }}</option>
                        <option value="committee">{{ __('app.roles.relations.agenda.target_types.committee') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.target_id') }}</label>
                    <input class="form-control" name="target_id" value="{{ old('target_id') }}">
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_participant" value="1" id="create-target-participant">
                        <label class="form-check-label" for="create-target-participant">
                            {{ __('app.roles.relations.agenda.fields.is_participant') }}
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.notes') }}</label>
                    <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.relations.agenda.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
