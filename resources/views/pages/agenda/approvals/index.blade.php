@extends('layouts.app')

@section('content')
    @php
        $user = auth()->user();
    @endphp

    <div class="event-module">
        <div class="card event-card mb-4">
            <div class="card-body">
                <h1 class="h4 mb-2">{{ __('app.roles.relations.approvals.title') }}</h1>
                <p class="text-muted mb-0">{{ __('app.roles.relations.approvals.subtitle') }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="card event-card">
            <div class="card-body">
                <div class="event-table-wrap table-responsive">
                    <table class="table table-sm align-middle event-table">
                        <thead>
                            <tr>
                                <th>{{ __('app.roles.relations.approvals.table.event_name') }}</th>
                                <th>{{ __('app.roles.relations.approvals.table.event_date') }}</th>
                                <th>{{ __('app.roles.relations.approvals.table.status') }}</th>
                                <th>{{ __('app.roles.relations.approvals.table.relations_approval') }}</th>
                                <th>{{ __('app.roles.relations.approvals.table.executive_approval') }}</th>
                                <th>{{ __('app.roles.relations.approvals.table.change_requests') }}</th>
                                <th class="text-end">{{ __('app.roles.relations.approvals.table.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($events as $event)
                                @php
                                    $latestApproval = $event->approvals->last();
                                    $requestedChanges = $event->approvals
                                        ->where('decision', 'changes_requested')
                                        ->filter(fn ($approval) => filled($approval->comment));

                                    $isRelationsReviewer = $user?->hasRole('relations_manager');
                                    $isExecutiveReviewer = $user?->hasRole('executive_manager') || $user?->hasRole('super_admin');

                                    $canRelationsReview = $isRelationsReviewer && in_array($event->status, ['submitted', 'changes_requested'], true);
                                    $canExecutiveReview = $isExecutiveReviewer
                                        && $event->relations_approval_status === 'approved'
                                        && $event->status === 'relations_approved';

                                    $reviewLockedMessage = null;
                                    if ($isExecutiveReviewer && ! $canExecutiveReview) {
                                        $reviewLockedMessage = __('app.roles.relations.approvals.workflow.awaiting_relations_approval');
                                    } elseif ($isRelationsReviewer && ! $canRelationsReview) {
                                        $reviewLockedMessage = __('app.roles.relations.approvals.workflow.not_available_for_current_state');
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $event->event_name }}</td>
                                    <td>{{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}</td>
                                    <td>
                                        <span class="event-status status-{{ $event->workflow_state }}">{{ $event->workflow_state }}</span>
                                    </td>
                                    <td><span class="event-status status-{{ $event->relations_approval_status }}">{{ $event->relations_approval_status }}</span></td>
                                    <td><span class="event-status status-{{ $event->executive_approval_status }}">{{ $event->executive_approval_status }}</span></td>
                                    <td>
                                        <div class="small text-muted">
                                            {{ __('app.roles.relations.approvals.table.relations_changes_count', ['count' => $event->relations_changes_requested_count]) }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ __('app.roles.relations.approvals.table.executive_changes_count', ['count' => $event->executive_changes_requested_count]) }}
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#approval-{{ $event->id }}">{{ __('app.roles.relations.approvals.actions.review') }}</button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="approval-{{ $event->id }}">
                                    <td colspan="7">
                                        @if($requestedChanges->isNotEmpty())
                                            <div class="alert alert-warning py-2">
                                                <div class="fw-semibold mb-1">{{ __('app.roles.relations.approvals.change_requests.title') }}</div>
                                                <ul class="mb-0 ps-3">
                                                    @foreach($requestedChanges as $change)
                                                        <li>
                                                            <span class="fw-semibold">{{ __('app.roles.relations.approvals.change_requests.step_' . $change->step) }}:</span>
                                                            {{ $change->comment }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <div class="event-approval-panel">
                                            @if ($canRelationsReview || $canExecutiveReview)
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
                                                        <input class="form-control" name="comment" value="{{ $latestApproval?->comment }}">
                                                    </div>
                                                    <div class="col-12 event-actions">
                                                        <button class="btn btn-outline-primary btn-sm" type="submit">{{ __('app.roles.relations.approvals.actions.submit') }}</button>
                                                    </div>
                                                </form>
                                            @else
                                                <div class="alert alert-secondary py-2 mb-0">
                                                    {{ $reviewLockedMessage ?? __('app.roles.relations.approvals.workflow.not_available_for_current_state') }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-muted">{{ __('app.roles.relations.approvals.table.empty') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
