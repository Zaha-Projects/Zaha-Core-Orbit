@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
@endpush

@section('content')
    <div class="workflow-ui">
        <div class="wf-card card mb-4">
            <div class="card-body">
                <h1 class="wf-page-title mb-1">{{ __('app.roles.relations.approvals.title') }}</h1>
                <p class="wf-muted mb-0">{{ __('app.roles.relations.approvals.subtitle') }}</p>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="d-flex flex-column gap-3">
            @forelse ($events as $event)
                @php
                    $workflowSummary = $event->workflow_summary ?? [];
                    $statusClass = 'wf-status-' . (($workflowSummary['status_key'] ?? '') ?: (($workflowSummary['workflow_state'] ?? '') ?: 'pending'));
                    $canDecide = (bool) ($workflowSummary['can_current_user_decide'] ?? $event->can_current_user_decide ?? false);
                    $currentStepLabel = $workflowSummary['current_step_label'] ?? $event->current_step_label ?? __('app.common.na');
                    $currentRoleLabel = $workflowSummary['current_role_label'] ?? $event->current_role_label ?? __('app.common.na');
                    $timeline = collect($workflowSummary['timeline'] ?? []);
                    $latestChangeRequest = $workflowSummary['latest_change_request'] ?? null;
                @endphp

                <div class="wf-card card">
                    <div class="card-body">
                        <div class="wf-summary mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <h3 class="h6 mb-1">{{ $event->event_name }}</h3>
                                    <div class="wf-kv">
                                        {{ optional($event->event_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $event->month, $event->day) }}
                                        @if($event->ownerDepartment?->name)
                                            | {{ $event->ownerDepartment->name }}
                                        @endif
                                    </div>
                                    <div class="wf-kv">
                                        {{ __('workflow_ui.common.submitted_by') }}: {{ $workflowSummary['submitted_by_name'] ?? '-' }}
                                        @if(!empty($workflowSummary['submitted_at']))
                                            | {{ __('workflow_ui.common.submitted_at') }}: {{ $workflowSummary['submitted_at'] }}
                                        @endif
                                    </div>
                                </div>
                                <span class="wf-status-badge {{ $statusClass }}">
                                    {{ $workflowSummary['status_label'] ?? __('app.common.na') }}
                                </span>
                            </div>

                            <div class="wf-chip-row mt-3">
                                <span class="wf-chip wf-chip-primary">{{ __('workflow_ui.common.current_step') }}: {{ $currentStepLabel }}</span>
                                <span class="wf-chip wf-chip-soft">التقدم: {{ $workflowSummary['completed_steps_count'] ?? 0 }}/{{ $workflowSummary['total_steps_count'] ?? 0 }}</span>
                            </div>
                        </div>

                        <div class="accordion" id="agenda-approval-accordion-{{ $event->id }}">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header" id="agenda-heading-{{ $event->id }}">
                                    <button class="accordion-button collapsed p-0 bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#agenda-body-{{ $event->id }}">
                                        {{ __('app.roles.relations.approvals.actions.review') }}
                                    </button>
                                </h2>
                                <div id="agenda-body-{{ $event->id }}" class="accordion-collapse collapse" data-bs-parent="#agenda-approval-accordion-{{ $event->id }}">
                                    <div class="accordion-body px-0 pt-3">
                                        <div class="row g-3">
                                            <div class="col-lg-7">
                                                <div class="border rounded-3 p-3 h-100">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h4 class="h6 mb-0">{{ __('workflow_ui.approvals.workflow_map') }}</h4>
                                                        <span class="wf-kv">{{ $currentRoleLabel }}</span>
                                                    </div>
                                                    <div class="d-flex flex-column gap-2">
                                                        @foreach($workflowSummary['steps'] ?? [] as $step)
                                                            <div class="border rounded-3 p-3 {{ !empty($step['is_current']) ? 'border-primary-subtle bg-light-subtle' : '' }}">
                                                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                                    <div>
                                                                        <div class="fw-semibold">{{ $step['label'] }}</div>
                                                                        <div class="wf-kv">{{ $step['role_label'] }}</div>
                                                                        @if(!empty($step['actor_name']) || !empty($step['acted_at']))
                                                                            <div class="wf-kv">{{ $step['actor_name'] ?? '-' }} @if(!empty($step['acted_at'])) | {{ $step['acted_at'] }} @endif</div>
                                                                        @endif
                                                                        @if(!empty($step['comment']))
                                                                            <div class="wf-kv mt-1">{{ $step['comment'] }}</div>
                                                                        @endif
                                                                    </div>
                                                                    <span class="wf-status-badge wf-status-{{ $step['state'] }}">
                                                                        {{ $step['state_label'] }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-lg-5">
                                                <div class="border rounded-3 p-3 mb-3">
                                                    <h4 class="h6 mb-2">{{ __('workflow_ui.approvals.change_request_title') }}</h4>
                                                    @if($latestChangeRequest)
                                                        <div class="wf-kv">{{ __('workflow_ui.approvals.requested_by') }}: {{ $latestChangeRequest['actor_name'] }}</div>
                                                        <div class="wf-kv">{{ __('workflow_ui.approvals.requested_at') }}: {{ $latestChangeRequest['acted_at'] ?? '-' }}</div>
                                                        <div class="wf-kv">{{ __('workflow_ui.common.current_step') }}: {{ $latestChangeRequest['step_label'] }}</div>
                                                        <div class="wf-kv">{{ __('workflow_ui.common.assignee') }}: {{ $latestChangeRequest['role_label'] }}</div>
                                                        <div class="wf-kv mt-2">{{ $latestChangeRequest['comment'] ?: '-' }}</div>
                                                    @else
                                                        <div class="wf-kv">{{ __('workflow_ui.approvals.change_request_empty') }}</div>
                                                    @endif
                                                </div>

                                                <div class="border rounded-3 p-3 mb-3">
                                                    <details>
                                                        <summary class="fw-semibold" style="cursor:pointer;">{{ __('workflow_ui.approvals.workflow_history') }}</summary>
                                                        <div class="d-flex flex-column gap-2 mt-3">
                                                            @forelse($timeline as $entry)
                                                                <div class="border rounded p-2">
                                                                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                                        <div>
                                                                            <div class="fw-semibold">{{ $entry['step_label'] }}</div>
                                                                            <div class="wf-kv">{{ $entry['role_label'] }}</div>
                                                                            <div class="wf-kv">{{ $entry['actor_name'] }} | {{ $entry['acted_at'] ?? '-' }}</div>
                                                                            <div class="wf-kv">{{ $entry['comment'] ?: '-' }}</div>
                                                                        </div>
                                                                        <span class="wf-status-badge wf-status-{{ $entry['action'] }}">
                                                                            {{ $entry['action_label'] }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            @empty
                                                                <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div>
                                                            @endforelse
                                                        </div>
                                                    </details>
                                                </div>

                                                @if($canDecide)
                                                    <form method="POST" action="{{ route('role.relations.approvals.update', $event) }}" class="border rounded-3 p-3">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="mb-2">
                                                            <label class="form-label">{{ __('app.roles.relations.approvals.fields.decision') }}</label>
                                                            <select class="form-select" name="decision" required>
                                                                <option value="approved">{{ __('app.roles.relations.approvals.decisions.approved') }}</option>
                                                                <option value="changes_requested">{{ __('app.roles.relations.approvals.decisions.changes_requested') }}</option>
                                                                <option value="rejected">{{ __('workflow_ui.approvals.status_labels.rejected') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">{{ __('app.roles.relations.approvals.fields.comment') }}</label>
                                                            <textarea class="form-control" name="comment" rows="3"></textarea>
                                                        </div>
                                                        <div class="d-flex justify-content-end">
                                                            <button class="btn btn-primary btn-sm" type="submit">{{ __('app.roles.relations.approvals.actions.submit') }}</button>
                                                        </div>
                                                    </form>
                                                @else
                                                    <div class="border rounded-3 p-3 wf-panel-soft">
                                                        <div class="fw-semibold mb-1">{{ __('workflow_ui.approvals.waiting_title') }}</div>
                                                        <div class="wf-kv">{{ __('workflow_ui.approvals.waiting_body', ['role' => $currentRoleLabel]) }}</div>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="wf-card card">
                    <div class="card-body">
                        <p class="wf-muted mb-0">{{ __('app.roles.relations.approvals.table.empty') }}</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
@endsection
