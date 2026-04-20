<div class="row g-3">
    <div class="col-lg-7">
        <div class="border rounded-3 p-3 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="h6 mb-0">خارطة المسار</h4>
                <span class="wf-kv">{{ $card['current_role_label'] }}</span>
            </div>
            <div class="d-flex flex-column gap-2">
                @foreach($card['workflow_steps'] as $step)
                    <div class="border rounded-3 p-3 {{ $step['is_current'] ? 'border-primary-subtle bg-light-subtle' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div>
                                <div class="fw-semibold">{{ $step['label'] }}</div>
                                <div class="wf-kv">{{ $step['role_label'] }}</div>
                                @if(!empty($step['actor_name']) || !empty($step['acted_at']))
                                    <div class="wf-kv">{{ $step['actor_name'] ?: '-' }} @if(!empty($step['acted_at'])) | {{ $step['acted_at'] }} @endif</div>
                                @endif
                                @if(!empty($step['comment']))
                                    <div class="wf-kv mt-1">{{ $step['comment'] }}</div>
                                @endif
                            </div>
                            <span class="wf-status-badge wf-status-{{ $step['state'] }}">{{ $step['state_label'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="border rounded-3 p-3 mb-3">
            <h4 class="h6 mb-2">{{ __('workflow_ui.approvals.change_request_title') }}</h4>
            @if($card['latest_change_request'])
                <div class="wf-kv">{{ __('workflow_ui.approvals.requested_by') }}: {{ $card['latest_change_request']['actor_name'] }}</div>
                <div class="wf-kv">{{ __('workflow_ui.approvals.requested_at') }}: {{ $card['latest_change_request']['acted_at'] ?? '-' }}</div>
                <div class="wf-kv">{{ __('workflow_ui.common.current_step') }}: {{ $card['latest_change_request']['step_label'] }}</div>
                <div class="wf-kv">{{ __('workflow_ui.common.assignee') }}: {{ $card['latest_change_request']['role_label'] }}</div>
                <div class="wf-kv mt-2">{{ $card['latest_change_request']['comment'] ?: '-' }}</div>
            @else
                <div class="wf-kv">{{ __('workflow_ui.approvals.change_request_empty') }}</div>
            @endif
        </div>

        <div class="border rounded-3 p-3 mb-3">
            <details>
                <summary class="fw-semibold" style="cursor:pointer;">{{ __('workflow_ui.approvals.workflow_history') }}</summary>
                <div class="d-flex flex-column gap-2 mt-3">
                    @forelse($card['logs'] as $entry)
                        <div class="border rounded p-2">
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

        @if($card['needs_official_correspondence'])
            <div class="border rounded-3 p-3 mb-3">
                <h4 class="h6 mb-2">{{ __('workflow_ui.approvals.official.title') }}</h4>
                <div class="wf-kv mb-2">{{ __('workflow_ui.approvals.official.target') }}: {{ $card['official_correspondence']['target'] ?: '-' }}</div>
                <div class="wf-kv mb-2">{{ __('workflow_ui.approvals.official.reason') }}: {{ $card['official_correspondence']['reason'] ?: '-' }}</div>
                <div class="wf-kv mb-2">{{ __('workflow_ui.approvals.official.brief') }}: {{ $card['official_correspondence']['brief'] ?: '-' }}</div>
                <div class="d-flex flex-column gap-2">
                    @forelse($card['official_correspondence']['attachments'] as $attachment)
                        <a class="btn btn-sm btn-outline-secondary text-start" href="{{ $attachment['url'] }}" target="_blank" rel="noopener">
                            {{ $attachment['title'] }}
                        </a>
                    @empty
                        <div class="wf-kv">{{ __('workflow_ui.approvals.official.empty') }}</div>
                    @endforelse
                </div>
            </div>
        @endif

        @if($card['permissions']['can_decide'] || $card['permissions']['can_add_department_note'])
            <form method="POST" action="{{ $card['update_url'] }}" enctype="multipart/form-data" class="decision-form" data-confirm-title="{{ __('workflow_ui.approvals.confirm_action') }}" data-confirm-body="{{ __('workflow_ui.approvals.confirm_action_body') }}" data-comment-required="{{ __('workflow_ui.approvals.comment_required') }}">
                @csrf
                @method('PUT')

                @if($card['permissions']['can_decide'])
                    <div class="mb-2">
                        <label class="form-label">{{ __('workflow_ui.approvals.timeline.decision') }}</label>
                        <select class="form-select decision-select" name="decision" required>
                            <option value="approved">{{ __('workflow_ui.approvals.status_labels.approved') }}</option>
                            <option value="changes_requested">{{ __('workflow_ui.approvals.status_labels.changes_requested') }}</option>
                            <option value="rejected">{{ __('workflow_ui.approvals.status_labels.rejected') }}</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">{{ __('workflow_ui.common.comment') }}</label>
                        <textarea class="form-control decision-comment" name="comment" rows="3"></textarea>
                    </div>
                @else
                    <div class="alert alert-light border mb-2">{{ __('workflow_ui.approvals.note_only_hint') }}</div>
                @endif

                @if($card['permissions']['can_add_department_note'])
                    <div class="mb-2">
                        <label class="form-label">{{ __('workflow_ui.approvals.department_note') }}</label>
                        <textarea class="form-control" name="note" rows="3"></textarea>
                    </div>
                    @if($card['permissions']['show_coverage_status'])
                        <div class="mb-2">
                            <label class="form-label">{{ __('workflow_ui.common.coverage_status') }}</label>
                            <select class="form-select" name="coverage_status">
                                <option value="not_required">{{ __('workflow_ui.common.coverage_not_required') }}</option>
                                <option value="planned">{{ __('workflow_ui.common.coverage_planned') }}</option>
                                <option value="in_progress">{{ __('workflow_ui.common.coverage_in_progress') }}</option>
                                <option value="completed">{{ __('workflow_ui.common.coverage_completed') }}</option>
                            </select>
                        </div>
                    @endif
                @endif

                @if($card['permissions']['can_upload_official_correspondence'] && $card['permissions']['can_decide'])
                    <div class="border rounded-3 p-3 mb-2 bg-light-subtle">
                        <div class="fw-semibold mb-2">{{ __('workflow_ui.approvals.official.upload_title') }}</div>
                        <div class="mb-2">
                            <label class="form-label">{{ __('workflow_ui.approvals.official.attachment_title') }}</label>
                            <input class="form-control" name="official_correspondence_title" value="{{ __('workflow_ui.approvals.official.default_attachment_title') }}">
                        </div>
                        <div>
                            <label class="form-label">{{ __('workflow_ui.approvals.official.upload_field') }}</label>
                            <input class="form-control" type="file" name="official_correspondence_file" accept=".pdf,.doc,.docx">
                            <small class="text-muted d-block mt-1">{{ __('workflow_ui.approvals.official.upload_help') }}</small>
                        </div>
                    </div>
                @endif

                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary btn-sm">{{ $card['permissions']['can_decide'] ? __('workflow_ui.approvals.submit_decision') : __('workflow_ui.approvals.submit_note') }}</button>
                </div>
            </form>
        @else
            <div class="border rounded-3 p-3 wf-panel-soft">
                <div class="fw-semibold mb-1">{{ __('workflow_ui.approvals.waiting_title') }}</div>
                <div class="wf-kv">{{ __('workflow_ui.approvals.waiting_body', ['role' => $card['current_role_label']]) }}</div>
            </div>
        @endif
    </div>
</div>
