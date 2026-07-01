<div class="wf-card card agenda-approvals-filter mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('role.relations.approvals.index') }}" class="row g-3 align-items-end">
            <input type="hidden" name="tab" value="approval">
            @include('pages.shared.filters.workflow-status-and-step', [
                'statusFieldName' => 'approval_status',
                'statusFieldId' => 'approval_status',
                'statusLabel' => __('app.roles.relations.approvals.filters.status'),
                'statusPlaceholder' => __('app.roles.relations.approvals.filters.all'),
                'statusOptions' => $statusOptions,
                'selectedStatus' => $filters['approval_status'] ?? '',
                'stepFieldName' => 'current_step',
                'stepFieldId' => 'current_step',
                'stepLabel' => __('workflow_ui.common.current_step'),
                'stepPlaceholder' => __('workflow_ui.common.none_option'),
                'currentStepOptions' => $currentStepOptions,
                'selectedStep' => $filters['current_step'] ?? '',
            ])
            <div class="col-auto d-flex gap-2">
                <button class="btn btn-primary" type="submit">{{ __('app.roles.relations.approvals.filters.apply') }}</button>
                @if(!empty($filters['approval_status']) || !empty($filters['current_step']))
                    <a class="btn btn-outline-secondary" href="{{ route('role.relations.approvals.index', ['tab' => 'approval']) }}">{{ __('app.roles.relations.approvals.filters.reset') }}</a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="d-flex flex-column gap-3">
    @forelse ($events as $event)
        @include('pages.agenda.approvals.partials.approval-card', ['event' => $event])
    @empty
        <div class="wf-card card">
            <div class="card-body">
                <p class="wf-muted mb-0">{{ __('app.roles.relations.approvals.table.empty') }}</p>
            </div>
        </div>
    @endforelse
</div>
