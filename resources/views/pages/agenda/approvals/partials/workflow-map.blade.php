<div class="agenda-approval-panel agenda-approval-panel--map h-100">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="h6 mb-0">{{ __('workflow_ui.approvals.workflow_map') }}</h4>
        <span class="wf-kv">{{ $currentRoleLabel }}</span>
    </div>
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
            <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div>
        @endforelse
    </div>
</div>
