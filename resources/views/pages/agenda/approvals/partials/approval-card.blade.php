@php
    $workflowSummary = $event->workflow_summary ?? [];
    $statusKey = (($workflowSummary['status_key'] ?? '') ?: (($workflowSummary['workflow_state'] ?? '') ?: 'pending'));
    $statusClass = 'wf-status-' . $statusKey;
    $canDecide = (bool) ($workflowSummary['can_current_user_decide'] ?? $event->can_current_user_decide ?? false);
    $currentStepLabel = $workflowSummary['current_step_label'] ?? $event->current_step_label ?? __('app.common.na');
    $currentRoleLabel = $workflowSummary['current_role_label'] ?? $event->current_role_label ?? __('app.common.na');
    $timeline = collect($workflowSummary['timeline'] ?? []);
    $latestChangeRequest = $workflowSummary['latest_change_request'] ?? null;
    $progressCurrent = (int) ($workflowSummary['completed_steps_count'] ?? 0);
    $progressTotal = max((int) ($workflowSummary['total_steps_count'] ?? 0), 1);
    $progressPercent = min(100, round(($progressCurrent / $progressTotal) * 100));
@endphp

<div class="wf-card card agenda-approval-card agenda-approval-card--{{ $statusKey }}">
    <div class="card-body">
        <div class="wf-summary agenda-approval-summary mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div>
                    <div class="agenda-approval-card__eyebrow">{{ $currentRoleLabel }}</div>
                    <h3 class="agenda-approval-card__title">{{ $event->event_name }}</h3>
                    <div class="wf-kv">
                        {{ optional($event->event_date)->format('d/m/Y') ?? sprintf('%02d/%02d', $event->day, $event->month) }}
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
                <span class="wf-status-badge {{ $statusClass }}">{{ $workflowSummary['status_label'] ?? __('app.common.na') }}</span>
            </div>

            <div class="agenda-approval-progress mt-3" style="--approval-progress: {{ $progressPercent }}%;">
                <div class="agenda-approval-progress__head">
                    <span>{{ __('workflow_ui.common.current_step') }}: {{ $currentStepLabel }}</span>
                    <strong>{{ $progressCurrent }}/{{ $progressTotal }}</strong>
                </div>
                <div class="agenda-approval-progress__bar"><span></span></div>
            </div>
        </div>

        <div class="accordion" id="agenda-approval-accordion-{{ $event->id }}">
            <div class="accordion-item border-0">
                <h2 class="accordion-header" id="agenda-heading-{{ $event->id }}">
                    <button class="accordion-button collapsed agenda-approval-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#agenda-body-{{ $event->id }}" aria-expanded="false" aria-controls="agenda-body-{{ $event->id }}">
                        {{ __('app.roles.relations.approvals.actions.review') }}
                    </button>
                </h2>
                <div id="agenda-body-{{ $event->id }}" class="accordion-collapse collapse" aria-labelledby="agenda-heading-{{ $event->id }}" data-bs-parent="#agenda-approval-accordion-{{ $event->id }}">
                    <div class="accordion-body px-0 pt-3">
                        <div class="row g-3">
                            <div class="col-lg-7">
                                @include('pages.agenda.approvals.partials.workflow-map', ['workflowSummary' => $workflowSummary, 'currentRoleLabel' => $currentRoleLabel])
                            </div>
                            <div class="col-lg-5">
                                @include('pages.agenda.approvals.partials.change-request', ['latestChangeRequest' => $latestChangeRequest])
                                @include('pages.agenda.approvals.partials.history', ['timeline' => $timeline])
                                @include('pages.agenda.approvals.partials.decision-panel', ['event' => $event, 'canDecide' => $canDecide, 'currentRoleLabel' => $currentRoleLabel])
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
