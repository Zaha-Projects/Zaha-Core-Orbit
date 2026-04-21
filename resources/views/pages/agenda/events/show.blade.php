@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/agenda-event-show.css') }}">
@endpush

@php
    $title = __('app.roles.relations.agenda.show_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
    $workflowSummary = $agendaEvent->workflow_summary ?? [];
    $viewer = auth()->user();
    $canViewApprovalStates = (bool) ($workflowSummary['can_current_user_decide'] ?? false)
        || ($viewer?->can('agenda.approve') ?? false)
        || ($viewer?->hasRole('super_admin') ?? false);
    $statusLabel = function (?string $status): string {
        if (! $status) {
            return '-';
        }

        return \App\Models\EventStatusLookup::labelFor('agenda', $status);
    };
@endphp

@section('content')
    <div class="event-module">
        <div class="event-header mb-3 d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h1 class="h4 mb-1"><i class="feather-calendar me-1"></i>{{ $title }}</h1>
                <p class="text-muted mb-0">{{ $subtitle }}</p>
            </div>
            <a class="btn btn-outline-secondary" href="{{ route('role.relations.agenda.index') }}">{{ __('app.common.back') }}</a>
        </div>

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
                            <span class="wf-chip">{{ __('workflow_ui.common.assignee') }}: {{ $workflowSummary['current_role_label'] ?? __('app.common.na') }}</span>
                            <span class="wf-chip wf-chip-soft">التقدم: {{ $workflowSummary['completed_steps_count'] ?? 0 }}/{{ $workflowSummary['total_steps_count'] ?? 0 }}</span>
                            <span class="wf-chip wf-chip-soft">{{ __('workflow_ui.common.status') }}: {{ $workflowSummary['workflow_state_label'] ?? __('app.common.na') }}</span>
                        </div>
                    </div>

                    @if($canViewApprovalStates)
                        <details class="wf-advanced-box mt-3" open>
                            <summary>عرض حالات الاعتماد</summary>
                            <div class="row g-3 mt-1">
                                <div class="col-lg-7">
                                    <div class="d-flex flex-column gap-2">
                                        @forelse($workflowSummary['steps'] ?? [] as $step)
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
                                                    <span class="wf-status-badge wf-status-{{ $step['state'] }}">{{ $step['state_label'] }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="wf-kv">-</div>
                                        @endforelse
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="border rounded-3 p-3 mb-3">
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
                                        <div class="d-flex flex-column gap-2 mt-3">
                                            @forelse($workflowSummary['timeline'] ?? [] as $entry)
                                                <div class="border rounded-3 p-3">
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
                    @endif
                </div>
            </div>
        </div>

        <div class="card event-card mb-3">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><strong>{{ __('app.roles.relations.agenda.fields.event_name') }}:</strong> {{ $agendaEvent->event_name }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields.event_date') }}:</strong> {{ optional($agendaEvent->event_date)->format('Y-m-d') ?? '-' }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields_ext.department') }}:</strong> {{ $agendaEvent->ownerDepartment?->icon ?? $agendaEvent->department?->icon }} <span class="d-inline-block rounded-circle align-middle" style="width:10px;height:10px;background:{{ $agendaEvent->ownerDepartment?->color_hex ?? $agendaEvent->department?->color_hex ?? '#94a3b8' }}"></span> {{ $agendaEvent->ownerDepartment?->name ?? $agendaEvent->department?->name ?? '-' }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields.event_category') }}:</strong> {{ $agendaEvent->eventCategory?->name ?? $agendaEvent->event_category ?? '-' }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields_ext.event_type') }}:</strong> {{ __('app.roles.relations.agenda.types.' . $agendaEvent->event_type) }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields_ext.plan_type') }}:</strong> {{ __('app.roles.relations.agenda.plans.' . $agendaEvent->plan_type) }}</div>
                    <div class="col-md-3"><strong>{{ __('app.roles.relations.agenda.fields_ext.review_status') }}:</strong> {{ $workflowSummary['status_label'] ?? $statusLabel($agendaEvent->status) }}</div>
                    <div class="col-md-12"><strong>{{ __('app.roles.relations.agenda.fields.notes') }}:</strong> {{ $agendaEvent->notes ?: '-' }}</div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-lg-6">
                <div class="card event-card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.fields_ext.partner_department') }}</h2>
                        <ul class="mb-0 ps-3">
                            @forelse($agendaEvent->partnerDepartments as $partnerDepartment)
                                <li>{{ $partnerDepartment->icon }} <span class="d-inline-block rounded-circle align-middle" style="width:10px;height:10px;background:{{ $partnerDepartment->color_hex ?? '#94a3b8' }}"></span> {{ $partnerDepartment->name }}</li>
                            @empty
                                <li class="text-muted">لا يوجد شركاء</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12 col-lg-6">
                <div class="card event-card h-100">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.fields_ext.unit_participation') }}</h2>
                        <ul class="mb-0 ps-3">
                            @forelse($unitParticipations as $unit)
                                <li>{{ $unit['icon'] ?? '' }} <span class="d-inline-block rounded-circle align-middle" style="width:10px;height:10px;background:{{ $unit['color_hex'] ?? '#94a3b8' }}"></span> {{ $unit['name'] }} - {{ __('app.roles.relations.agenda.participation.' . ($unit['status'] ?? 'unspecified')) }}</li>
                            @empty
                                <li class="text-muted">-</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('app.roles.relations.agenda.fields_ext.branch_participation') }}</h2>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
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
                                            <td>{{ $branch['icon'] ?? '' }} <span class="d-inline-block rounded-circle align-middle" style="width:10px;height:10px;background:{{ $branch['color_hex'] ?? '#94a3b8' }}"></span> {{ $branch['name'] }}</td>
                                            <td>{{ __('app.roles.relations.agenda.participation.' . ($branch['status'] ?? 'unspecified')) }}</td>
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
            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.title') }}</h2>
                        <ul class="mb-0 ps-3">
                            @forelse($agendaEvent->monthlyActivities as $activity)
                                <li>{{ $activity->title }} ({{ optional($activity->activity_date)->format('Y-m-d') ?? '-' }})</li>
                            @empty
                                <li class="text-muted">-</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
