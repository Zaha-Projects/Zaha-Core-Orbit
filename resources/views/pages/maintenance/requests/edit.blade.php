@extends('layouts.new-theme-dashboard')

@php
    $title = __('app.roles.maintenance.requests.edit_title');
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
            <h2 class="h6 mb-3">{{ __('app.roles.maintenance.requests.edit_details') }}</h2>
            <form method="POST" action="{{ route('role.maintenance.requests.update', $maintenanceRequest) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.logged_at') }}</label>
                    <input class="form-control" type="datetime-local" name="logged_at" value="{{ $maintenanceRequest->logged_at?->format('Y-m-d\\TH:i') }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.type') }}</label>
                    <select class="form-select" name="type" >
                        <option value="preventive" @selected($maintenanceRequest->type === 'preventive')>{{ __('app.roles.maintenance.requests.types.preventive') }}</option>
                        <option value="emergency" @selected($maintenanceRequest->type === 'emergency')>{{ __('app.roles.maintenance.requests.types.emergency') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.category') }}</label>
                    <input class="form-control" name="category" value="{{ $maintenanceRequest->category }}" >
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.description') }}</label>
                    <textarea class="form-control" name="description" rows="3" >{{ $maintenanceRequest->description }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.priority') }}</label>
                    <select class="form-select" name="priority" >
                        <option value="low" @selected($maintenanceRequest->priority === 'low')>{{ __('app.roles.maintenance.requests.priorities.low') }}</option>
                        <option value="medium" @selected($maintenanceRequest->priority === 'medium')>{{ __('app.roles.maintenance.requests.priorities.medium') }}</option>
                        <option value="high" @selected($maintenanceRequest->priority === 'high')>{{ __('app.roles.maintenance.requests.priorities.high') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.status') }}</label>
                    <select class="form-select" name="status" >
                        <option value="logged" @selected($maintenanceRequest->status === 'logged')>{{ __('app.roles.maintenance.requests.statuses.logged') }}</option>
                        <option value="assigned" @selected($maintenanceRequest->status === 'assigned')>{{ __('app.roles.maintenance.requests.statuses.assigned') }}</option>
                        <option value="in_progress" @selected($maintenanceRequest->status === 'in_progress')>{{ __('app.roles.maintenance.requests.statuses.in_progress') }}</option>
                        <option value="completed" @selected($maintenanceRequest->status === 'completed')>{{ __('app.roles.maintenance.requests.statuses.completed') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" >
                        <option value="">{{ __('app.roles.maintenance.requests.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($maintenanceRequest->branch_id === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    
</div>
                <div class="col-12">
                    <h3 class="h6 mt-2">{{ __('app.roles.maintenance.requests.fields_ext.processing_tracks') }}</h3>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.branch_head_status') }}</label>
                    <input class="form-control" name="branch_head_status" value="{{ $maintenanceRequest->branch_head_status }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.maintenance_track_status') }}</label>
                    <input class="form-control" name="maintenance_track_status" value="{{ $maintenanceRequest->maintenance_track_status }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.it_track_status') }}</label>
                    <input class="form-control" name="it_track_status" value="{{ $maintenanceRequest->it_track_status }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.branch_head_note') }}</label>
                    <textarea class="form-control" rows="2" name="branch_head_note">{{ $maintenanceRequest->branch_head_note }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.maintenance_track_note') }}</label>
                    <textarea class="form-control" rows="2" name="maintenance_track_note">{{ $maintenanceRequest->maintenance_track_note }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.it_track_note') }}</label>
                    <textarea class="form-control" rows="2" name="it_track_note">{{ $maintenanceRequest->it_track_note }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.support_resources') }}</label>
                    <textarea class="form-control" rows="2" name="support_resources">{{ $maintenanceRequest->support_resources }}</textarea>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.support_party') }}</label>
                    <input class="form-control" name="support_party" value="{{ $maintenanceRequest->support_party }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.root_cause_branch') }}</label>
                    <textarea class="form-control" rows="2" name="root_cause_branch">{{ $maintenanceRequest->root_cause_branch }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.root_cause_maintenance') }}</label>
                    <textarea class="form-control" rows="2" name="root_cause_maintenance">{{ $maintenanceRequest->root_cause_maintenance }}</textarea>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.root_cause_it') }}</label>
                    <textarea class="form-control" rows="2" name="root_cause_it">{{ $maintenanceRequest->root_cause_it }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.maintenance.requests.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.maintenance.work_details.title') }}</h2>
            <form method="POST" action="{{ route('role.maintenance.work_details.store', $maintenanceRequest) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.work_details.fields.start_from') }}</label>
                    <input class="form-control" type="datetime-local" name="start_from">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.work_details.fields.end_to') }}</label>
                    <input class="form-control" type="datetime-local" name="end_to">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.work_details.fields.estimated_cost') }}</label>
                    <input class="form-control" type="number" step="0.01" name="estimated_cost">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.maintenance.work_details.fields.team_desc') }}</label>
                    <input class="form-control" name="team_desc">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.maintenance.work_details.fields.resources_type') }}</label>
                    <input class="form-control" name="resources_type">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.maintenance.work_details.fields.support_party') }}</label>
                    <input class="form-control" name="support_party">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.maintenance.work_details.fields.root_cause_analysis') }}</label>
                    <textarea class="form-control" name="root_cause_analysis" rows="2"></textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.maintenance.work_details.fields.notes') }}</label>
                    <textarea class="form-control" name="notes" rows="2"></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary btn-sm" type="submit">
                        {{ __('app.roles.maintenance.work_details.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.maintenance.work_details.table.team_desc') }}</th>
                            <th>{{ __('app.roles.maintenance.work_details.table.estimated_cost') }}</th>
                            <th class="text-end">{{ __('app.roles.maintenance.work_details.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($maintenanceRequest->workDetails as $detail)
                            <tr>
                                <td>{{ $detail->team_desc ?? '-' }}</td>
                                <td>{{ $detail->estimated_cost ?? '-' }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('role.maintenance.work_details.update', $detail) }}" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="team_desc" value="{{ $detail->team_desc }}">
                                        <input type="hidden" name="estimated_cost" value="{{ $detail->estimated_cost }}">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">
                                            {{ __('app.roles.maintenance.work_details.actions.refresh') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">{{ __('app.roles.maintenance.work_details.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.maintenance.attachments.title') }}</h2>
            <form method="POST" action="{{ route('role.maintenance.attachments.store', $maintenanceRequest) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.attachments.fields.file_type') }}</label>
                    <input class="form-control" name="file_type" >
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.maintenance.attachments.fields.file_path') }}</label>
                    <input class="form-control" name="file_path" >
                </div>
                <div class="col-12 col-md-2 d-flex justify-content-end align-items-center">
                    <button class="btn btn-outline-primary btn-sm mt-4" type="submit">
                        {{ __('app.roles.maintenance.attachments.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.maintenance.attachments.table.file_type') }}</th>
                            <th>{{ __('app.roles.maintenance.attachments.table.file_path') }}</th>
                            <th class="text-end">{{ __('app.roles.maintenance.attachments.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($maintenanceRequest->attachments as $attachment)
                            <tr>
                                <td>{{ $attachment->file_type }}</td>
                                <td>{{ $attachment->file_path }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('role.maintenance.attachments.destroy', $attachment) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.maintenance.attachments.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted">{{ __('app.roles.maintenance.attachments.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.maintenance.requests.close_title') }}</h2>
            <form method="POST" action="{{ route('role.maintenance.requests.close', $maintenanceRequest) }}" class="row g-3">
                @csrf
                @method('PATCH')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.closed_at') }}</label>
                    <input class="form-control" type="datetime-local" name="closed_at" value="{{ $maintenanceRequest->closed_at?->format('Y-m-d\\TH:i') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields.status') }}</label>
                    <select class="form-select" name="status" >
                        <option value="closed">{{ __('app.roles.maintenance.requests.statuses.closed') }}</option>
                        <option value="completed">{{ __('app.roles.maintenance.requests.statuses.completed') }}</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.maintenance.requests.fields_ext.closure_summary') }}</label>
                    <textarea class="form-control" rows="3" name="closure_summary">{{ $maintenanceRequest->closure_summary }}</textarea>
                </div>
                <div class="col-12 col-md-4 d-flex justify-content-end align-items-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.maintenance.requests.actions.close') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
