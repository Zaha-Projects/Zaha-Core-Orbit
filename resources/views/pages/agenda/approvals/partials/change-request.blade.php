<div class="agenda-approval-panel agenda-approval-panel--changes mb-3">
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
