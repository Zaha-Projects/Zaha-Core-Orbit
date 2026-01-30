@extends('layouts.app')

@php
    $title = __('app.roles.relations.approvals.title');
    $subtitle = __('app.roles.relations.approvals.subtitle');
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

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.relations.approvals.table.event_name') }}</th>
                            <th>{{ __('app.roles.relations.approvals.table.event_date') }}</th>
                            <th>{{ __('app.roles.relations.approvals.table.status') }}</th>
                            <th>{{ __('app.roles.relations.approvals.table.last_decision') }}</th>
                            <th class="text-end">{{ __('app.roles.relations.approvals.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($events as $event)
                            @php
                                $latestApproval = $event->approvals->last();
                            @endphp
                            <tr>
                                <td>{{ $event->event_name }}</td>
                                <td>{{ sprintf('%02d-%02d', $event->month, $event->day) }}</td>
                                <td>{{ $event->status }}</td>
                                <td>{{ $latestApproval?->decision ?? __('app.roles.relations.approvals.table.none') }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#approval-{{ $event->id }}">
                                        {{ __('app.roles.relations.approvals.actions.review') }}
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="approval-{{ $event->id }}">
                                <td colspan="5">
                                    <form method="POST" action="{{ route('role.relations.approvals.update', $event) }}" class="row g-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-12 col-md-4">
                                            <label class="form-label">{{ __('app.roles.relations.approvals.fields.decision') }}</label>
                                            <select class="form-select" name="decision" required>
                                                <option value="approved">{{ __('app.roles.relations.approvals.decisions.approved') }}</option>
                                                <option value="changes_requested">{{ __('app.roles.relations.approvals.decisions.changes_requested') }}</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-8">
                                            <label class="form-label">{{ __('app.roles.relations.approvals.fields.comment') }}</label>
                                            <input class="form-control" name="comment">
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <button class="btn btn-outline-primary btn-sm" type="submit">
                                                {{ __('app.roles.relations.approvals.actions.submit') }}
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.relations.approvals.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
