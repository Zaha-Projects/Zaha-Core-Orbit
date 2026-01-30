@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.edit_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
    $target = $agendaEvent->targets->first();
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.relations.agenda.update', $agendaEvent) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_name') }}</label>
                    <input class="form-control" name="event_name" value="{{ $agendaEvent->event_name }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_date') }}</label>
                    <input class="form-control" type="date" name="event_date" value="{{ sprintf('%04d-%02d-%02d', now()->year, $agendaEvent->month, $agendaEvent->day) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_category') }}</label>
                    <input class="form-control" name="event_category" value="{{ $agendaEvent->event_category }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.target_type') }}</label>
                    <select class="form-select" name="target_type">
                        <option value="">{{ __('app.roles.relations.agenda.fields.target_type_placeholder') }}</option>
                        <option value="branch" @selected(optional($target)->target_type === 'branch')>{{ __('app.roles.relations.agenda.target_types.branch') }}</option>
                        <option value="center" @selected(optional($target)->target_type === 'center')>{{ __('app.roles.relations.agenda.target_types.center') }}</option>
                        <option value="department" @selected(optional($target)->target_type === 'department')>{{ __('app.roles.relations.agenda.target_types.department') }}</option>
                        <option value="committee" @selected(optional($target)->target_type === 'committee')>{{ __('app.roles.relations.agenda.target_types.committee') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.target_id') }}</label>
                    <input class="form-control" name="target_id" value="{{ optional($target)->target_id }}">
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_participant" value="1" id="edit-target-participant" @checked(optional($target)->is_participant)>
                        <label class="form-check-label" for="edit-target-participant">
                            {{ __('app.roles.relations.agenda.fields.is_participant') }}
                        </label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.notes') }}</label>
                    <textarea class="form-control" name="notes" rows="3">{{ $agendaEvent->notes }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.relations.agenda.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
