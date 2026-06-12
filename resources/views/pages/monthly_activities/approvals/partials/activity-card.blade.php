<div class="wf-card card approvals-activity-card approvals-activity-card--modern" data-activity-id="{{ $card['id'] }}">
    <div class="approvals-activity-card__header">
        <div class="approvals-activity-card__title-wrap">
            <span class="approvals-activity-card__icon"><i class="fas fa-calendar-check" aria-hidden="true"></i></span>
            <div>
                <div class="approval-request-card__eyebrow">طلب اعتماد خطة شهرية</div>
                <h3 class="approvals-activity-card__title">{{ $card['title'] }}</h3>
            </div>
        </div>
        <div class="approval-request-card__badges">
            <span class="wf-status-badge {{ $card['status_class'] }}">{{ $card['status_label'] }}</span>
            <span class="approval-version-badge">نسخة {{ $card['version_number'] }}</span>
        </div>
    </div>

    <div class="approvals-activity-card__body">
        <section class="approval-card-section">
            <h4><i class="fas fa-info-circle" aria-hidden="true"></i> معلومات النشاط</h4>
            <div class="approval-request-card__grid">
                <div class="approval-info-item"><i class="fas fa-building" aria-hidden="true"></i><span>الفرع</span><strong>{{ $card['branch_name'] }}</strong></div>
                <div class="approval-info-item"><i class="fas fa-user-tie" aria-hidden="true"></i><span>المسؤول</span><strong>{{ $card['responsible_user'] }}</strong></div>
                <div class="approval-info-item"><i class="fas fa-calendar-day" aria-hidden="true"></i><span>تاريخ النشاط</span><strong>{{ $card['activity_date'] ?: $card['date_label'] }}</strong></div>
                <div class="approval-info-item"><i class="fas fa-paper-plane" aria-hidden="true"></i><span>تاريخ الإرسال</span><strong>{{ $card['submitted_at'] ?: '-' }}</strong></div>
            </div>
        </section>

        <section class="approval-card-section approval-card-section--workflow">
            <div class="approval-request-workflow__head">
                <span><i class="fas fa-user-check" aria-hidden="true"></i> المعتمد الحالي: {{ $card['current_role_label'] }} — {{ $card['current_step_label'] }}</span>
                <strong>{{ $card['approved_steps_count'] }}/{{ $card['workflow_steps_count'] }}</strong>
            </div>
            <div class="approvals-status-progress mt-2" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $card['workflow_steps_count'] }}" aria-valuenow="{{ $card['approved_steps_count'] }}">
                <span style="width: {{ $card['progress_percentage'] }}%"></span>
            </div>
            <div class="approvals-status-grid mt-3">
                @forelse($card['workflow_steps'] as $step)
                    <div class="approvals-status-item {{ $step['is_current'] ? 'is-current' : '' }}">
                        <div>
                            <div class="approvals-status-role">{{ $step['role_label'] }}</div>
                            <small class="text-muted">{{ $step['label'] }}</small>
                        </div>
                        <span class="wf-status-badge wf-status-{{ $step['state'] }}">{{ $step['state_label'] }}</span>
                    </div>
                @empty
                    <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div>
                @endforelse
            </div>
        </section>

        <section class="approval-card-section approval-card-section--request">
            <h4><i class="fas fa-clipboard-list" aria-hidden="true"></i> ملخص الطلب</h4>
            <div class="wf-chip-row">
                <span class="wf-chip wf-chip-primary">نوع الطلب: اعتماد جديد</span>
                <span class="wf-chip wf-chip-soft">التقدم: {{ $card['completed_steps_count'] }}/{{ $card['total_steps_count'] }}</span>
                @foreach($card['requirements'] as $requirement)
                    <span class="wf-chip wf-chip-soft">{{ $requirement }}</span>
                @endforeach
            </div>
        </section>
    </div>

    <div class="approvals-activity-card__footer">
        <button
            class="btn btn-sm btn-outline-primary approval-activity-summary-trigger"
            type="button"
            data-activity-title="{{ e($card['title']) }}"
            data-details-url="{{ $card['activity_details_url'] }}"
        >
            <i class="fas fa-eye me-1" aria-hidden="true"></i>
            عرض التفاصيل
        </button>
        <button class="btn btn-sm btn-primary approval-details-trigger" type="button" data-bs-toggle="collapse" data-bs-target="#body-{{ $card['id'] }}" data-details-url="{{ $card['details_url'] }}">
            <i class="fas fa-tasks me-1" aria-hidden="true"></i>
            مراجعة واتخاذ قرار
        </button>
    </div>

    <div class="collapse" id="body-{{ $card['id'] }}">
        <div class="approval-details-content p-3 border-top" data-loaded="0">
            <div class="border rounded-3 p-3 wf-panel-soft">جاري تحميل التفاصيل...</div>
        </div>
    </div>
</div>
