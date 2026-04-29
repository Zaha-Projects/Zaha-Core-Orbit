@extends('layouts.app')


@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/event-ui-shared.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/agenda-event-show.css') }}">
@endpush

@php
    $title = __('app.roles.relations.agenda.show_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
    $workflowSummary = $agendaEvent->workflow_summary ?? [];
    $viewer = auth()->user();
    $resolvedEventDate = optional($agendaEvent->event_date)->format('Y-m-d')
        ?? sprintf('%04d-%02d-%02d', now()->year, $agendaEvent->month, $agendaEvent->day);
    $canDeleteAgendaEvent = (($viewer?->can('agenda.delete') ?? false) || ($viewer?->hasRole('super_admin') ?? false))
        && \Carbon\Carbon::parse($resolvedEventDate)->isAfter(today());
    $canViewApprovalStates = (bool) ($workflowSummary['can_current_user_decide'] ?? false)
        || ($viewer?->can('agenda.approve') ?? false)
        || ($viewer?->hasRole('super_admin') ?? false);
    $statusLabel = function (?string $status): string {
        if (! $status) {
            return '-';
        }

        return \App\Models\EventStatusLookup::labelFor('agenda', $status);
    };
    $ownerDepartment = $agendaEvent->ownerDepartment ?? $agendaEvent->department;
    $eventDateLabel = optional($agendaEvent->event_date)->format('Y-m-d') ?? '-';
    $reviewStatusLabel = $workflowSummary['status_label'] ?? $statusLabel($agendaEvent->status);
    $currentStepLabel = $workflowSummary['current_step_label'] ?? __('app.common.na');
    $completedStepsCount = (int) ($workflowSummary['completed_steps_count'] ?? 0);
    $totalStepsCount = (int) ($workflowSummary['total_steps_count'] ?? 0);
    $branchParticipantCount = collect($branchParticipations)->where('status', 'participant')->count();
    $branchesTotalCount = max((int) ($branchesTotalCount ?? 0), count($branchParticipations));
    $agendaFacts = [
        ['icon' => 'feather-grid', 'label' => __('app.roles.relations.agenda.fields.event_category'), 'value' => $agendaEvent->eventCategory?->name ?? $agendaEvent->event_category ?? '-'],
        ['icon' => 'feather-flag', 'label' => __('app.roles.relations.agenda.fields_ext.event_type'), 'value' => __('app.roles.relations.agenda.types.' . $agendaEvent->event_type)],
        ['icon' => 'feather-map', 'label' => __('app.roles.relations.agenda.fields_ext.plan_type'), 'value' => __('app.roles.relations.agenda.plans.' . $agendaEvent->plan_type)],
        ['icon' => 'feather-power', 'label' => 'حالة التفعيل', 'value' => ($agendaEvent->is_active ?? true) ? 'نشطة' : 'غير نشطة'],
    ];
    if ($canViewApprovalStates) {
        $agendaFacts[] = ['icon' => 'feather-user-check', 'label' => __('workflow_ui.common.submitted_by'), 'value' => $workflowSummary['submitted_by_name'] ?? '-'];
        $agendaFacts[] = ['icon' => 'feather-check-circle', 'label' => __('app.roles.relations.agenda.fields_ext.review_status'), 'value' => $reviewStatusLabel];
    }
    $agendaStats = [
        ['label' => __('workflow_ui.common.current_step'), 'value' => $currentStepLabel, 'tone' => 'primary'],
        ['label' => 'تقدم الاعتماد', 'value' => $completedStepsCount . '/' . $totalStepsCount, 'tone' => 'cyan'],
        ['label' => __('app.roles.relations.agenda.fields_ext.branch_participation'), 'value' => $branchParticipantCount . '/' . $branchesTotalCount, 'tone' => 'amber'],
    ];
@endphp

@section('content')
    <div class="event-module agenda-show-page">
        <section class="agenda-show-hero mb-4">
            <div class="agenda-show-hero__content">
                <div class="agenda-show-eyebrow">
                    <i class="feather-calendar"></i>
                    <span>{{ $title }}</span>
                </div>
                <h1>{{ $agendaEvent->event_name }}</h1>
                <p>{{ $agendaEvent->notes ?: $subtitle }}</p>
                <div class="agenda-show-tags">
                    <span>{{ __('app.roles.relations.agenda.types.' . $agendaEvent->event_type) }}</span>
                    <span>{{ __('app.roles.relations.agenda.plans.' . $agendaEvent->plan_type) }}</span>
                    <span>{{ ($agendaEvent->is_active ?? true) ? 'نشطة' : 'غير نشطة' }}</span>
                </div>
            </div>
            <div class="agenda-show-hero__panel">
                @if($canViewApprovalStates)
                    <span class="wf-status-badge wf-status-{{ $workflowSummary['status_key'] ?? 'draft' }}">
                        {{ $reviewStatusLabel }}
                    </span>
                @endif
                <div class="agenda-show-date-card">
                    <span>{{ __('app.roles.relations.agenda.fields.event_date') }}</span>
                    <strong>{{ $eventDateLabel }}</strong>
                </div>
                <div class="agenda-show-date-card">
                    <span>{{ __('app.roles.relations.agenda.fields_ext.department') }}</span>
                    <strong>
                        @if($ownerDepartment?->color_hex)
                            <span class="agenda-show-color-dot" style="background:{{ $ownerDepartment->color_hex }}"></span>
                        @endif
                        {{ $ownerDepartment?->icon ?? '' }} {{ $ownerDepartment?->name ?? '-' }}
                    </strong>
                </div>
            </div>
        </section>

        <div class="agenda-show-actions mb-3">
            <p class="text-muted mb-0">{{ $subtitle }}</p>
            <div class="d-flex gap-2 flex-wrap">
                @if($canDeleteAgendaEvent)
                    <form method="POST" action="{{ route('role.relations.agenda.destroy', $agendaEvent) }}" onsubmit="return confirm('{{ __('app.roles.relations.agenda.confirm_delete') }}')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-outline-danger" type="submit">{{ __('app.roles.relations.agenda.actions.delete') }}</button>
                    </form>
                @endif
                <a class="btn btn-outline-secondary" href="{{ route('role.relations.agenda.index') }}">{{ __('app.common.back') }}</a>
            </div>
        </div>

        @if($canViewApprovalStates)
            <div class="agenda-show-stats mb-4">
                @foreach($agendaStats as $stat)
                    <div class="agenda-show-stat agenda-show-stat--{{ $stat['tone'] }}">
                        <span>{{ $stat['label'] }}</span>
                        <strong>{{ $stat['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        @endif

        @if($canViewApprovalStates)
            <div class="workflow-ui mb-3">
                <div class="wf-card card">
                    <div class="card-body">
                        <div class="wf-summary">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <h2 class="h6 mb-1"><i class="feather-star me-1"></i>{{ $agendaEvent->event_name }}</h2>
                                    <div class="wf-kv">
                                        {{ optional($agendaEvent->event_date)->format('Y-m-d') ?? '-' }}
                                        | {{ $agendaEvent->ownerDepartment?->name ?? $agendaEvent->department?->name ?? '-' }}
                                    </div>
                                    <div class="wf-kv">
                                        {{ __('workflow_ui.common.submitted_by') }}: {{ $workflowSummary['submitted_by_name'] ?? '-' }}
                                        @if(!empty($workflowSummary['submitted_at']))
                                            | {{ __('workflow_ui.common.submitted_at') }}: {{ $workflowSummary['submitted_at'] }}
                                        @endif
                                    </div>
                                </div>
                                <span class="wf-status-badge wf-status-{{ $workflowSummary['status_key'] ?? 'draft' }}">
                                    {{ $workflowSummary['status_label'] ?? $statusLabel($agendaEvent->status) }}
                                </span>
                            </div>

                            <div class="wf-chip-row mt-3">
                                <span class="wf-chip wf-chip-primary">{{ __('workflow_ui.common.current_step') }}: {{ $workflowSummary['current_step_label'] ?? __('app.common.na') }}</span>
                                <span class="wf-chip wf-chip-soft">التقدم: {{ $workflowSummary['completed_steps_count'] ?? 0 }}/{{ $workflowSummary['total_steps_count'] ?? 0 }}</span>
                            </div>
                        </div>

                        <details class="wf-advanced-box mt-3" open>
                            <summary>عرض حالات الاعتماد</summary>
                            <div class="row g-3 mt-1 wf-approval-board">
                                <div class="col-lg-7">
                                    <div class="wf-state-stack">
                                        @forelse($workflowSummary['steps'] ?? [] as $step)
                                            <div class="wf-state-card wf-state-card--{{ $step['state'] }} {{ !empty($step['is_current']) ? 'is-current' : '' }}">
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
                                                    <span class="wf-status-badge wf-status-{{ $step['state'] }}">{{ $step['state_label'] }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="wf-kv">-</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="wf-side-note-card mb-3">
                                        <h3 class="h6 mb-2">{{ __('workflow_ui.approvals.change_request_title') }}</h3>
                                        @if(!empty($workflowSummary['latest_change_request']))
                                            <div class="wf-kv">{{ __('workflow_ui.approvals.requested_by') }}: {{ $workflowSummary['latest_change_request']['actor_name'] }}</div>
                                            <div class="wf-kv">{{ __('workflow_ui.approvals.requested_at') }}: {{ $workflowSummary['latest_change_request']['acted_at'] ?? '-' }}</div>
                                            <div class="wf-kv">{{ __('workflow_ui.common.current_step') }}: {{ $workflowSummary['latest_change_request']['step_label'] }}</div>
                                            <div class="wf-kv">{{ __('workflow_ui.common.assignee') }}: {{ $workflowSummary['latest_change_request']['role_label'] }}</div>
                                            <div class="wf-kv mt-2">{{ $workflowSummary['latest_change_request']['comment'] ?: '-' }}</div>
                                        @else
                                            <div class="wf-kv">{{ __('workflow_ui.approvals.change_request_empty') }}</div>
                                        @endif
                                    </div>

                                    <details class="wf-advanced-box">
                                        <summary>{{ __('workflow_ui.approvals.workflow_history') }}</summary>
                                        <div class="wf-state-stack mt-3">
                                            @forelse($workflowSummary['timeline'] ?? [] as $entry)
                                                <div class="wf-state-card wf-state-card--{{ $entry['action'] }}">
                                                    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                        <div>
                                                            <div class="fw-semibold">{{ $entry['step_label'] }}</div>
                                                            <div class="wf-kv">{{ $entry['role_label'] }}</div>
                                                            <div class="wf-kv">{{ $entry['actor_name'] }} | {{ $entry['acted_at'] ?? '-' }}</div>
                                                            <div class="wf-kv">{{ $entry['comment'] ?: '-' }}</div>
                                                        </div>
                                                        <span class="wf-status-badge wf-status-{{ $entry['action'] }}">{{ $entry['action_label'] }}</span>
                                                    </div>
                                                </div>
                                            @empty
                                                <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div>
                                            @endforelse
                                        </div>
                                    </details>
                                </div>
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        @endif

        <div class="agenda-show-identity mb-4">
            <div class="agenda-show-section-head">
                <h2>هوية الفعالية</h2>
                <span>{{ $ownerDepartment?->name ?? '-' }}</span>
            </div>
            <div class="agenda-show-facts">
                @foreach($agendaFacts as $fact)
                    <div class="agenda-show-fact">
                        <i class="{{ $fact['icon'] }}"></i>
                        <span>{{ $fact['label'] }}</span>
                        <strong>{{ $fact['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card event-card agenda-show-notes mb-3">
            <div class="card-body">
                <div class="agenda-show-section-head">
                    <h2>{{ __('app.roles.relations.agenda.fields.notes') }}</h2>
                    <span>{{ __('app.roles.relations.agenda.fields.event_name') }}</span>
                </div>
                <p class="mb-0">{{ $agendaEvent->notes ?: '-' }}</p>
            </div>
        </div>

        <div class="row g-3">
            @if($canViewApprovalStates)
                <div class="col-12">
                    <div class="card event-card">
                        <div class="card-body">
                            <div class="agenda-show-section-head">
                                <h2>{{ __('app.roles.relations.agenda.fields_ext.branch_participation') }}</h2>
                                <span>{{ $branchParticipantCount }} / {{ $branchesTotalCount }}</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm align-middle agenda-show-table">
                                    <thead>
                                        <tr>
                                            <th>{{ __('app.roles.relations.agenda.target_types.branch') }}</th>
                                            <th>{{ __('app.roles.relations.agenda.fields_ext.review_status') }}</th>
                                            <th>{{ __('app.roles.relations.agenda.fields.event_date') }} (مقترح)</th>
                                            <th>{{ __('app.roles.relations.agenda.fields.event_date') }} (تنفيذ)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($branchParticipations as $branch)
                                            <tr>
                                                <td>
                                                    <span class="agenda-show-color-dot" style="background:{{ $branch['color_hex'] ?? '#94a3b8' }}"></span>
                                                    {{ $branch['icon'] ?? '' }} {{ $branch['name'] }}
                                                </td>
                                                <td><span class="event-status status-{{ $branch['status'] ?? 'unspecified' }}">{{ __('app.roles.relations.agenda.participation.' . ($branch['status'] ?? 'unspecified')) }}</span></td>
                                                <td>{{ optional($branch['proposed_date'])->format('Y-m-d') ?? '-' }}</td>
                                                <td>{{ optional($branch['actual_execution_date'])->format('Y-m-d') ?? '-' }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-muted">-</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
