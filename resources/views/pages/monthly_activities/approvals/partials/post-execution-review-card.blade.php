@php
    $payload = $activity->post_execution_payload ?? [];
    $needs = collect($activity->enabledExecutionNeeds())->map(function ($definition, $key) use ($activity) {
        $row = collect($activity->execution_needs_followup ?? [])->firstWhere('key', $key) ?? [];
        $implemented = ($row['post_status'] ?? null) === 'provided';

        return [
            'key' => $key,
            'label' => $definition['label'] ?? $key,
            'implemented' => $implemented,
            'status_label' => $implemented ? 'تم التنفيذ' : 'لم يتم التنفيذ',
            'feedback' => $row['post_feedback'] ?? $row['evaluation_reason'] ?? $row['notes'] ?? $row['reason'] ?? null,
        ];
    })->values();
    $implementedNeeds = $needs->where('implemented', true)->count();
    $notImplementedNeeds = max($needs->count() - $implementedNeeds, 0);
    $teams = collect($payload['teams'] ?? []);
    $paragraphs = collect($payload['ceremony_items'] ?? []);
    $statusMap = [
        'post_execution_submitted' => ['label' => 'Pending Approval', 'class' => 'wf-status-pending'],
        'closed' => ['label' => 'Approved', 'class' => 'wf-status-approved'],
        'rejected' => ['label' => 'Rejected', 'class' => 'wf-status-rejected'],
    ];
    $statusMeta = $statusMap[$activity->status] ?? ['label' => $activity->status, 'class' => 'wf-status-pending'];
@endphp

<article class="post-execution-review approval-request-card">
    <header class="post-execution-review__hero">
        <div>
            <div class="approval-request-card__eyebrow"><i class="fas fa-clipboard-check" aria-hidden="true"></i> لوحة مراجعة اعتماد ما بعد التنفيذ</div>
            <h2 class="approval-request-card__title">{{ $activity->title }}</h2>
        </div>
        <span class="wf-status-badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span>
    </header>

    <section class="approval-request-card__grid post-execution-summary-card" aria-label="ملخص النشاط">
        <div class="approval-info-item"><i class="fas fa-heading" aria-hidden="true"></i><span>عنوان النشاط</span><strong>{{ $activity->title }}</strong></div>
        <div class="approval-info-item"><i class="fas fa-building" aria-hidden="true"></i><span>الفرع</span><strong>{{ $activity->branch?->name ?? '-' }}</strong></div>
        <div class="approval-info-item"><i class="fas fa-calendar-day" aria-hidden="true"></i><span>تاريخ النشاط</span><strong>{{ optional($activity->actual_date ?? $activity->proposed_date)->format('Y-m-d') ?? '-' }}</strong></div>
        <div class="approval-info-item"><i class="fas fa-user" aria-hidden="true"></i><span>مقدم الطلب</span><strong>{{ $activity->creator?->name ?? '-' }}</strong></div>
        <div class="approval-info-item"><i class="fas fa-paper-plane" aria-hidden="true"></i><span>تاريخ الإرسال</span><strong>{{ optional($activity->updated_at)->format('Y-m-d H:i') ?? '-' }}</strong></div>
        <div class="approval-info-item"><i class="fas fa-users" aria-hidden="true"></i><span>عدد الحضور الفعلي</span><strong>{{ $activity->actual_attendance ?? '-' }}</strong></div>
    </section>

    <div class="post-execution-layout">
        <main class="post-execution-layout__main">
            <section class="approval-card-section">
                <h4><i class="fas fa-chart-pie" aria-hidden="true"></i> ملخص الاحتياجات</h4>
                <div class="post-execution-stats">
                    <div><span>إجمالي الاحتياجات</span><strong>{{ $needs->count() }}</strong></div>
                    <div class="is-success"><span>تم التنفيذ</span><strong>{{ $implementedNeeds }}</strong></div>
                    <div class="is-danger"><span>لم يتم التنفيذ</span><strong>{{ $notImplementedNeeds }}</strong></div>
                </div>
            </section>

            <section class="approval-card-section">
                <h4><i class="fas fa-list-check" aria-hidden="true"></i> احتياجات التنفيذ</h4>
                <div class="post-execution-needs-grid">
                    @forelse($needs as $need)
                        <article class="post-execution-need-card {{ $need['implemented'] ? 'is-implemented' : 'is-not-implemented' }}">
                            <div class="post-execution-need-card__head">
                                <strong>{{ $need['label'] }}</strong>
                                <span class="wf-status-badge {{ $need['implemented'] ? 'wf-status-approved' : 'wf-status-rejected' }}">{{ $need['implemented'] ? '✅' : '❌' }} {{ $need['status_label'] }}</span>
                            </div>
                            <p><span>{{ $need['implemented'] ? 'فيدباك ما بعد التنفيذ:' : 'سبب / فيدباك:' }}</span>{{ $need['feedback'] ?: 'لا توجد ملاحظات مسجلة.' }}</p>
                        </article>
                    @empty
                        <div class="post-execution-empty"><i class="fas fa-inbox" aria-hidden="true"></i><strong>لا توجد احتياجات مفعّلة على هذه الفعالية.</strong></div>
                    @endforelse
                </div>
            </section>

            <section class="approval-card-section">
                <h4><i class="fas fa-paperclip" aria-hidden="true"></i> المرفقات والأدلة</h4>
                <div class="post-execution-attachments">
                    @forelse($activity->attachments as $attachment)
                        @php($isExternalUrl = filter_var($attachment->file_path, FILTER_VALIDATE_URL))
                        @php($isDrive = $isExternalUrl && str_contains(parse_url($attachment->file_path, PHP_URL_HOST) ?? '', 'drive.google'))
                        <article class="post-execution-attachment-card">
                            <div><span>نوع الملف</span><strong>{{ $attachment->file_type }}</strong></div>
                            <div><span>العنوان</span><strong>{{ $attachment->title ?: '-' }}</strong></div>
                            <div><span>تاريخ الرفع</span><strong>{{ optional($attachment->created_at)->format('Y-m-d H:i') ?? '-' }}</strong></div>
                            <div><span>رفع بواسطة</span><strong>{{ $attachment->uploader?->name ?? '-' }}</strong></div>
                            <div class="post-execution-attachment-card__actions">
                                @if($isDrive)<span class="wf-chip wf-chip-primary">Google Drive</span>@endif
                                <a class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener" href="{{ $isExternalUrl ? $attachment->file_path : route('role.programs.attachments.download', $attachment) }}">عرض</a>
                                <a class="btn btn-sm btn-outline-secondary" href="{{ $isExternalUrl ? $attachment->file_path : route('role.programs.attachments.download', $attachment) }}">تحميل</a>
                            </div>
                        </article>
                    @empty
                        <div class="post-execution-empty"><i class="fas fa-folder-open" aria-hidden="true"></i><strong>لا توجد مرفقات مضافة.</strong></div>
                    @endforelse
                </div>
            </section>

            <section class="approval-card-section">
                <h4><i class="fas fa-lock" aria-hidden="true"></i> إغلاق النشاط</h4>
                <div class="approval-request-card__grid">
                    <div class="approval-info-item"><i class="fas fa-calendar-check" aria-hidden="true"></i><span>التاريخ الفعلي</span><strong>{{ optional($activity->actual_date)->format('Y-m-d') ?? '-' }}</strong></div>
                    <div class="approval-info-item"><i class="fas fa-users" aria-hidden="true"></i><span>الحضور الفعلي</span><strong>{{ $activity->actual_attendance ?? '-' }} مشارك</strong></div>
                    <div class="approval-info-item"><i class="fas fa-circle-check" aria-hidden="true"></i><span>الحالة</span><strong>Closed / Pending Approval</strong></div>
                </div>
            </section>

            <section class="approval-card-section">
                <h4><i class="fas fa-people-group" aria-hidden="true"></i> متابعة الفرق</h4>
                <div class="post-execution-needs-grid">
                    @forelse($teams as $team)
                        @php($planned = (int) ($team['planned_members_count'] ?? 0))
                        @php($actual = (int) ($team['actual_attendance_count'] ?? 0))
                        @php($completion = $planned > 0 ? min(100, round(($actual / $planned) * 100)) : (($team['all_members_attended'] ?? false) ? 100 : 0))
                        <article class="post-execution-team-card">
                            <strong>{{ $team['team_name'] ?? '-' }}</strong>
                            <span>المهام المنجزة: {{ $team['accomplished_tasks'] ?? 'لا توجد ملاحظات.' }}</span>
                            <div class="approvals-status-progress"><span style="width: {{ $completion }}%"></span></div>
                            <small>نسبة الإنجاز / الحضور: {{ $completion }}%</small>
                        </article>
                    @empty
                        <div class="post-execution-empty"><i class="fas fa-users-slash" aria-hidden="true"></i><strong>لم يتم إدخال فرق لهذه الفعالية.</strong><span>لا توجد فرق متابعة لعرضها.</span></div>
                    @endforelse
                </div>
            </section>

            <section class="approval-card-section">
                <h4><i class="fas fa-align-right" aria-hidden="true"></i> متابعة فقرات النشاط</h4>
                <div class="post-execution-needs-grid">
                    @forelse($paragraphs as $paragraph)
                        @php($applied = (bool) ($paragraph['was_implemented'] ?? false))
                        <article class="post-execution-need-card {{ $applied ? 'is-implemented' : 'is-not-implemented' }}">
                            <div class="post-execution-need-card__head"><strong>Paragraph #{{ $paragraph['order'] ?? $loop->iteration }} — {{ $paragraph['name'] ?? '-' }}</strong><span class="wf-status-badge {{ $applied ? 'wf-status-approved' : 'wf-status-rejected' }}">{{ $applied ? '✅ Applied' : '❌ Not Applied' }}</span></div>
                            <p><span>Feedback:</span>{{ $paragraph['feedback'] ?? 'لا توجد ملاحظات.' }}</p>
                        </article>
                    @empty
                        <div class="post-execution-empty"><i class="fas fa-align-justify" aria-hidden="true"></i><strong>لا توجد فقرات متابعة مسجلة.</strong></div>
                    @endforelse
                </div>
            </section>
        </main>

        <aside class="post-execution-layout__aside">
            @if(!empty($activity->workflow_timeline))
                <section class="approval-request-workflow approval-request-workflow--timeline">
                    <h3>Workflow Progress</h3>
                    <div class="approval-workflow-timeline">
                        @foreach($activity->workflow_timeline as $step)
                            <div class="approval-workflow-timeline__item">
                                <div><strong>{{ $step['step_name'] }}</strong><span>{{ $step['approver_name'] }}</span></div>
                                <div><strong>{{ $step['status'] }}</strong><small>{{ $step['decided_at'] }}</small></div>
                                @if(!empty($step['comment']))<p>{{ $step['comment'] }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>
    </div>

    <footer class="post-execution-actions approval-request-card__footer">
        <button class="btn btn-outline-primary approval-activity-summary-trigger" type="button" data-activity-title="{{ e($activity->title) }}" data-details-url="{{ route('role.programs.approvals.details', ['monthlyActivity' => $activity, 'view' => 'activity']) }}">عرض التفاصيل الأصلية</button>
        @if($activity->can_current_user_decide)
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <form method="POST" action="{{ route('role.relations.activities.close', $activity) }}" class="approval-decision-form" data-confirm-title="تأكيد اعتماد ما بعد التنفيذ" data-confirm-body="هل تريد اعتماد ما بعد التنفيذ وإغلاق الفرصة؟">
                    @csrf @method('PATCH')
                    <button class="btn btn-lg btn-success">اعتماد ما بعد التنفيذ وإغلاق الفرصة</button>
                </form>
                <form method="POST" action="{{ route('role.programs.approvals.post_execution_decision', $activity) }}" onsubmit="const reason = prompt('يرجى كتابة سبب طلب التوضيح'); if (!reason) return false; this.querySelector('[name=comment]').value = reason; return confirm('هل تريد إرسال طلب التوضيح لمسؤول التطوع في الفرع؟');">
                    @csrf @method('PATCH')
                    <input type="hidden" name="decision" value="clarification">
                    <input type="hidden" name="comment">
                    <button class="btn btn-lg btn-outline-warning" type="submit">طلب توضيح</button>
                </form>
                <form method="POST" action="{{ route('role.programs.approvals.post_execution_decision', $activity) }}" onsubmit="const reason = prompt('يرجى كتابة سبب الرفض'); if (!reason) return false; this.querySelector('[name=comment]').value = reason; return confirm('هل تريد رفض ما بعد التنفيذ وإشعار مسؤول التطوع في الفرع؟');">
                    @csrf @method('PATCH')
                    <input type="hidden" name="decision" value="rejected">
                    <input type="hidden" name="comment">
                    <button class="btn btn-lg btn-outline-danger" type="submit">رفض ما بعد التنفيذ</button>
                </form>
            </div>
        @endif
    </footer>
</article>
