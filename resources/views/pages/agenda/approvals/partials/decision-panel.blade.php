@if($canDecide)
    <form method="POST" action="{{ route('role.relations.approvals.update', $event) }}" class="agenda-approval-panel agenda-approval-panel--decision">
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
    <div class="agenda-approval-panel agenda-approval-panel--waiting">
        <div class="fw-semibold mb-1">{{ __('workflow_ui.approvals.waiting_title') }}</div>
        <div class="wf-kv">{{ __('workflow_ui.approvals.waiting_body', ['role' => $currentRoleLabel]) }}</div>
    </div>
@endif
