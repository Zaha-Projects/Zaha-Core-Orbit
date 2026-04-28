@php
    $workflowAutoApprovalUser = auth()->user();
    $workflowAutoApprovalService = app(\App\Services\DynamicWorkflowService::class);
    $canUseWorkflowAutoApproval = $workflowAutoApprovalUser && (
        $workflowAutoApprovalService->userMayAutoApproveWorkflow('agenda', $workflowAutoApprovalUser)
        || $workflowAutoApprovalService->userMayAutoApproveWorkflow('monthly_activities', $workflowAutoApprovalUser)
    );
    $workflowAutoApprovalVariant = $variant ?? 'nxl';
@endphp

@if ($canUseWorkflowAutoApproval)
    @if ($workflowAutoApprovalVariant === 'original')
        <li class="side-item workflow-auto-approval-original">
            <form method="POST" action="{{ route('workflow_auto_approval.update') }}" class="workflow-auto-approval-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="auto_approve_workflow_steps" value="0">
                <label class="workflow-auto-approval-toggle">
                    <span class="workflow-auto-approval-icon"><i class="fas fa-bolt"></i></span>
                    <span class="workflow-auto-approval-copy">
                        <span class="workflow-auto-approval-title">{{ __('app.workflow_auto_approval.sidebar_label') }}</span>
                        <span class="workflow-auto-approval-help">{{ __('app.workflow_auto_approval.sidebar_hint') }}</span>
                    </span>
                    <input
                        type="checkbox"
                        name="auto_approve_workflow_steps"
                        value="1"
                        {{ $workflowAutoApprovalUser->auto_approve_workflow_steps ? 'checked' : '' }}
                        onchange="this.form.submit()"
                        aria-label="{{ __('app.workflow_auto_approval.sidebar_label') }}"
                    >
                    <span class="workflow-auto-approval-switch" aria-hidden="true"></span>
                </label>
            </form>
        </li>
    @else
        <li class="nxl-item workflow-auto-approval-item">
            <form method="POST" action="{{ route('workflow_auto_approval.update') }}" class="workflow-auto-approval-form">
                @csrf
                @method('PATCH')
                <input type="hidden" name="auto_approve_workflow_steps" value="0">
                <label class="workflow-auto-approval-toggle">
                    <span class="nxl-micon workflow-auto-approval-icon"><i class="feather-zap"></i></span>
                    <span class="nxl-mtext workflow-auto-approval-copy">
                        <span class="workflow-auto-approval-title">{{ __('app.workflow_auto_approval.sidebar_label') }}</span>
                        <span class="workflow-auto-approval-help">{{ __('app.workflow_auto_approval.sidebar_hint') }}</span>
                    </span>
                    <input
                        type="checkbox"
                        name="auto_approve_workflow_steps"
                        value="1"
                        {{ $workflowAutoApprovalUser->auto_approve_workflow_steps ? 'checked' : '' }}
                        onchange="this.form.submit()"
                        aria-label="{{ __('app.workflow_auto_approval.sidebar_label') }}"
                    >
                    <span class="workflow-auto-approval-switch" aria-hidden="true"></span>
                </label>
            </form>
        </li>
    @endif
@endif
