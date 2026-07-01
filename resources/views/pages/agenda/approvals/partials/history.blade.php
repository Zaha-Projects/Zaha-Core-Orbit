<div class="agenda-approval-panel agenda-approval-panel--history mb-3">
    <details>
        <summary class="fw-semibold" style="cursor:pointer;">{{ __('workflow_ui.approvals.workflow_history') }}</summary>
        <div class="wf-state-stack mt-3">
            @forelse($timeline as $entry)
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
