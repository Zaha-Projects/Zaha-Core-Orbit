@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
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
            <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.create_title') }}</h2>
            <form method="POST" action="{{ route('role.relations.agenda.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_name') }}</label>
                    <input class="form-control" name="event_name" value="{{ old('event_name') }}" required>
                    @error('event_name')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_date') }}</label>
                    <input class="form-control" type="date" name="event_date" value="{{ old('event_date') }}" required>
                    @error('event_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
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
                    @error('target_id')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-6 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_participant" value="1" id="target-participant">
                        <label class="form-check-label" for="target-participant">
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

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.list_title') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.relations.agenda.table.event_name') }}</th>
                            <th>{{ __('app.roles.relations.agenda.table.event_date') }}</th>
                            <th>{{ __('app.roles.relations.agenda.table.status') }}</th>
                            <th>{{ __('app.roles.relations.agenda.table.created_by') }}</th>
                            <th class="text-end">{{ __('app.roles.relations.agenda.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            <tr>
                                <td>{{ $event->event_name }}</td>
                                <td>{{ sprintf('%02d-%02d', $event->month, $event->day) }}</td>
                                <td>{{ $event->status }}</td>
                                <td>{{ $event->creator?->name ?? '-' }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.agenda.edit', $event) }}">
                                        {{ __('app.roles.relations.agenda.actions.edit') }}
                                    </a>
                                    <form class="d-inline" method="POST" action="{{ route('role.relations.agenda.submit', $event) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary" type="submit">
                                            {{ __('app.roles.relations.agenda.actions.submit') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.relations.agenda.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
