@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
@endphp

@section('sidebar')
    @include('pages.monthly_activities.partials.sidebar')
@endsection

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
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.sync.title') }}</h2>
            <form method="POST" action="{{ route('role.programs.activities.sync_from_agenda') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">--</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">--</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.sync.month') }}</label>
                    <input type="number" min="1" max="12" class="form-control" name="month" value="{{ now()->month }}" required>
                </div>
                <div class="col-6 col-md-2">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.sync.year') }}</label>
                    <input type="number" min="2020" max="2100" class="form-control" name="year" value="{{ now()->year }}" required>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" type="submit">{{ __('app.roles.programs.monthly_activities.sync.run') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.create_title') }}</h2>
            <form method="POST" action="{{ route('role.programs.activities.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.title') }}</label>
                    <input class="form-control" name="title" value="{{ old('title') }}" required>
                    @error('title')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.activity_date') }}</label>
                    <input class="form-control" type="date" name="activity_date" value="{{ old('activity_date') }}" required>
                    @error('activity_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.proposed_date') }}</label>
                    <input class="form-control" type="date" name="proposed_date" value="{{ old('proposed_date') }}" required>
                    @error('proposed_date')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
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

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.list_title') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.programs.monthly_activities.table.title') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.table.date') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.table.branch') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.table.status') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td>{{ $activity->title }}</td>
                                <td>{{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</td>
                                <td>{{ $activity->branch?->name ?? '-' }}</td>
                                <td>{{ $activity->status }}</td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.programs.activities.edit', $activity) }}">
                                        {{ __('app.roles.programs.monthly_activities.actions.edit') }}
                                    </a>
                                    <form class="d-inline" method="POST" action="{{ route('role.programs.activities.submit', $activity) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-sm btn-outline-primary" type="submit">
                                            {{ __('app.roles.programs.monthly_activities.actions.submit') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.programs.monthly_activities.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
