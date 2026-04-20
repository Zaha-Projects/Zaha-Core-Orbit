<div class="wf-card card approvals-activity-card" data-activity-id="{{ $card['id'] }}">
    <div class="card-header approvals-card-header">
        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <h3 class="h6 mb-1">{{ $card['title'] }}</h3>
                <div class="wf-kv">{{ $card['branch_name'] }} | {{ $card['date_label'] }}</div>
            </div>
            <div class="text-end">
                <span class="wf-status-badge {{ $card['status_class'] }}">{{ $card['status_label'] }}</span>
            </div>
        </div>
    </div>
    <div class="card-body approvals-card-body">
        <div class="wf-summary mb-3">
            <div class="w-100">
                <div class="wf-kv">
                    {{ __('workflow_ui.common.submitted_by') }}: {{ $card['submitted_by_name'] }}
                    @if(!empty($card['submitted_at']))
                        | {{ __('workflow_ui.common.submitted_at') }}: {{ $card['submitted_at'] }}
                    @endif
                </div>
                <div class="wf-chip-row mt-3">
                    <span class="wf-chip wf-chip-primary">{{ __('workflow_ui.common.current_step') }}: {{ $card['current_step_label'] }}</span>
                    <span class="wf-chip">{{ __('workflow_ui.common.assignee') }}: {{ $card['current_role_label'] }}</span>
                    <span class="wf-chip wf-chip-soft">التقدم: {{ $card['completed_steps_count'] }}/{{ $card['total_steps_count'] }}</span>
                    @foreach($card['requirements'] as $requirement)
                        <span class="wf-chip wf-chip-soft">{{ $requirement }}</span>
                    @endforeach
                </div>

                <div class="approvals-status-panel mt-3">
                    <div class="approvals-status-panel-header">
                        <h4 class="approvals-status-title mb-0">حالات الاعتماد</h4>
                        <span class="wf-chip wf-chip-soft">المعتمد: {{ $card['approved_steps_count'] }}/{{ $card['workflow_steps_count'] }}</span>
                    </div>

                    <div class="approvals-status-progress mt-2" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $card['workflow_steps_count'] }}" aria-valuenow="{{ $card['approved_steps_count'] }}">
                        <span style="width: {{ $card['progress_percentage'] }}%"></span>
                    </div>

                    <div class="approvals-status-grid mt-3">
                        @forelse($card['workflow_steps'] as $step)
                            <div class="approvals-status-item {{ $step['is_current'] ? 'is-current' : '' }}">
                                <div class="approvals-status-role">{{ $step['role_label'] }}</div>
                                <span class="wf-status-badge wf-status-{{ $step['state'] }}">{{ $step['state_label'] }}</span>
                            </div>
                        @empty
                            <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer approvals-card-footer">
        <div class="accordion" id="approval-accordion-{{ $card['id'] }}">
            <div class="accordion-item border-0">
                <h2 class="accordion-header" id="heading-{{ $card['id'] }}">
                    <button class="accordion-button collapsed p-0 bg-transparent shadow-none approval-details-trigger" type="button" data-bs-toggle="collapse" data-bs-target="#body-{{ $card['id'] }}" data-details-url="{{ $card['details_url'] }}">
                        {{ __('workflow_ui.approvals.details') }}
                    </button>
                </h2>
                <div id="body-{{ $card['id'] }}" class="accordion-collapse collapse" data-bs-parent="#approval-accordion-{{ $card['id'] }}">
                    <div class="accordion-body px-0 pt-3 approval-details-content" data-loaded="0">
                        <div class="border rounded-3 p-3 wf-panel-soft">جاري تحميل التفاصيل...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
