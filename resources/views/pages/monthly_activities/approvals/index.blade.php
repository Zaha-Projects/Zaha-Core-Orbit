@extends('layouts.app')


@push('styles')
@php
    $workflowUiCssPath = public_path('assets/css/workflow-ui.css');
    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : time();

        return asset($path) . '?v=' . $version;
    };
@endphp
@if (file_exists($workflowUiCssPath))
<link rel="stylesheet" href="{{ $versionedAsset('assets/css/workflow-ui.css') }}">
@endif
<link rel="stylesheet" href="{{ $versionedAsset('assets/css/monthly-approvals.css') }}">
@endpush

@section('content')
<div class="workflow-ui">
    <div class="wf-card card mb-4">
        <div class="card-header approvals-card-header">
            <h1 class="wf-page-title mb-1">{{ __('workflow_ui.approvals.title') }}</h1>
            <p class="wf-muted mb-0">{{ __('workflow_ui.approvals.subtitle') }}</p>
        </div>
        <div class="card-body">
            <div class="approvals-kpi-row mt-3">
                <div class="approvals-kpi-card">
                    <div class="approvals-kpi-label">إجمالي المعروض</div>
                    <div class="approvals-kpi-value">{{ $kpis['total'] }}</div>
                </div>
                <div class="approvals-kpi-card">
                    <div class="approvals-kpi-label">قيد المراجعة</div>
                    <div class="approvals-kpi-value">{{ $kpis['in_review'] }}</div>
                </div>
                <div class="approvals-kpi-card">
                    <div class="approvals-kpi-label">بانتظار قراري</div>
                    <div class="approvals-kpi-value">{{ $kpis['my_pending'] }}</div>
                </div>
            </div>
        </div>
        <div class="card-footer approvals-card-footer small text-muted">
            ملخص سريع لحالة الاعتمادات الحالية.
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="alert alert-info mb-3">
        الفعاليات الإلزامية المرتبطة بالأجندة السنوية لا تُعرض في شاشة اعتماد الخطط الشهرية لأنها لا تحتاج اعتماد الفرع.
    </div>


    @php
        $activeApprovalTab = request('tab', 'approval');
        $approvalTabItems = [
            [
                'key' => 'approval',
                'label' => 'طلبات الاعتماد',
                'icon' => 'fas fa-check-circle',
                'tone' => 'blue',
                'count' => method_exists($activities, 'total') ? $activities->total() : $activities->count(),
            ],
            [
                'key' => 'delete',
                'label' => 'طلبات الحذف',
                'icon' => 'fas fa-trash-alt',
                'tone' => 'red',
                'count' => isset($deleteRequests) && method_exists($deleteRequests, 'total') ? $deleteRequests->total() : 0,
            ],
            [
                'key' => 'edit',
                'label' => 'طلبات التعديل',
                'icon' => 'fas fa-edit',
                'tone' => 'amber',
                'count' => isset($editRequests) && method_exists($editRequests, 'total') ? $editRequests->total() : 0,
            ],
        ];
    @endphp
    <nav class="approval-dashboard-tabs mb-4" aria-label="تبويبات الاعتماد">
        @foreach($approvalTabItems as $tab)
            <a class="approval-dashboard-tab approval-dashboard-tab--{{ $tab['tone'] }} {{ $activeApprovalTab === $tab['key'] ? 'is-active' : '' }}"
               href="{{ route('role.programs.approvals.index', array_merge(request()->except(['page', 'delete_page', 'edit_page']), ['tab' => $tab['key']])) }}">
                <span class="approval-dashboard-tab__icon"><i class="{{ $tab['icon'] }}" aria-hidden="true"></i></span>
                <span class="approval-dashboard-tab__label">{{ $tab['label'] }}</span>
                <span class="approval-dashboard-tab__count">{{ $tab['count'] }}</span>
            </a>
        @endforeach
    </nav>

    @if($activeApprovalTab === 'delete')
        <div class="approval-request-list">
            @forelse($deleteRequests ?? [] as $deleteRequest)
                @php
                    $activity = $deleteRequest->monthlyActivity;
                    $instance = $deleteRequest->workflowInstance;
                    $currentStep = $instance?->currentStep;
                    $history = collect($deleteRequest->approval_history ?? []);
                    $approvedCount = $history->where('decision', 'approved')->count();
                    $totalSteps = max(($instance?->workflow?->steps?->count() ?? 0), 1);
                    $progress = min(100, round(($approvedCount / $totalSteps) * 100));
                @endphp
                <article class="approval-request-card approval-request-card--delete">
                    <header class="approval-request-card__header">
                        <div>
                            <div class="approval-request-card__eyebrow"><i class="fas fa-trash-alt" aria-hidden="true"></i> طلب حذف</div>
                            <h2 class="approval-request-card__title">{{ $activity?->title ?? '#' . $deleteRequest->entity_id }}</h2>
                        </div>
                        <div class="approval-request-card__badges">
                            <span class="wf-status-badge wf-status-{{ $deleteRequest->status }}">{{ $deleteRequest->status }}</span>
                            <span class="approval-version-badge">نسخة {{ (int) ($activity?->version_number ?? $activity?->plan_version ?? 1) }}</span>
                        </div>
                    </header>

                    <div class="approval-request-card__grid">
                        <div class="approval-info-item"><i class="fas fa-building" aria-hidden="true"></i><span>الفرع</span><strong>{{ $activity?->branch?->name ?? '-' }}</strong></div>
                        <div class="approval-info-item"><i class="fas fa-user" aria-hidden="true"></i><span>طالب الحذف</span><strong>{{ $deleteRequest->requester?->name ?? '-' }}</strong></div>
                        <div class="approval-info-item"><i class="fas fa-calendar-day" aria-hidden="true"></i><span>تاريخ النشاط</span><strong>{{ optional($activity?->proposed_date)->format('Y-m-d') ?? '-' }}</strong></div>
                        <div class="approval-info-item"><i class="fas fa-clock" aria-hidden="true"></i><span>تاريخ الطلب</span><strong>{{ optional($deleteRequest->requested_at)->format('Y-m-d H:i') ?? '-' }}</strong></div>
                    </div>

                    <section class="approval-request-section approval-request-section--danger">
                        <h3><i class="fas fa-comment-alt" aria-hidden="true"></i> سبب الحذف</h3>
                        <p>{{ $deleteRequest->reason }}</p>
                    </section>

                    <section class="approval-request-workflow">
                        <div class="approval-request-workflow__head">
                            <span><i class="fas fa-user-check" aria-hidden="true"></i> المعتمد الحالي: {{ $currentStep?->name_ar ?? $currentStep?->name_en ?? '-' }}</span>
                            <strong>{{ $approvedCount }}/{{ $totalSteps }}</strong>
                        </div>
                        <div class="approvals-status-progress"><span style="width: {{ $progress }}%"></span></div>
                    </section>

                    @if(!empty($deleteRequest->workflow_timeline))
                        <section class="approval-request-workflow approval-request-workflow--timeline">
                            <h3><i class="fas fa-route" aria-hidden="true"></i> تتبع مسار الاعتماد</h3>
                            <div class="approval-workflow-timeline">
                                @foreach($deleteRequest->workflow_timeline as $step)
                                    <div class="approval-workflow-timeline__item {{ !empty($step['is_current']) ? 'is-current' : '' }}">
                                        <div><strong>{{ $step['step_name'] ?? '-' }}</strong><span>{{ $step['role_name'] ?? '-' }}</span></div>
                                        <div><span>{{ $step['approver_name'] ?? '-' }}</span><strong>{{ $step['status'] ?? '-' }}</strong><small>{{ $step['decided_at'] ?? '-' }}</small></div>
                                        @if(!empty($step['comment']))<p>{{ $step['comment'] }}</p>@endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <footer class="approval-request-card__footer">
                        <button class="btn btn-sm btn-outline-primary approval-activity-summary-trigger" type="button" data-activity-title="{{ e($activity?->title ?? 'تفاصيل النشاط') }}" data-details-url="{{ $activity ? route('role.programs.approvals.details', ['monthlyActivity' => $activity, 'view' => 'activity']) : '' }}"><i class="fas fa-eye me-1" aria-hidden="true"></i> عرض التفاصيل</button>
                        @if($deleteRequest->can_current_user_decide ?? false)
                            <div class="approval-decision-form">
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#approve-delete-request-{{ $deleteRequest->id }}"><i class="fas fa-check me-1" aria-hidden="true"></i> اعتماد طلب الحذف</button>
                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject-delete-request-{{ $deleteRequest->id }}"><i class="fas fa-times me-1" aria-hidden="true"></i> رفض طلب الحذف</button>
                            </div>

                            <div class="modal fade" id="approve-delete-request-{{ $deleteRequest->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="POST" action="{{ route('role.programs.approvals.delete_requests.update', $deleteRequest) }}" class="modal-content monthly-delete-modal">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="decision" value="approved">
                                        <div class="modal-header border-0 pb-0">
                                            <div>
                                                <span class="monthly-delete-modal__icon"><i class="fas fa-check" aria-hidden="true"></i></span>
                                                <h2 class="modal-title h5 mt-3">اعتماد طلب الحذف</h2>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row g-2 mb-3">
                                                <dt class="col-4">النشاط</dt><dd class="col-8">{{ $activity?->title ?? '-' }}</dd>
                                                <dt class="col-4">طالب الحذف</dt><dd class="col-8">{{ $deleteRequest->requester?->name ?? '-' }}</dd>
                                                <dt class="col-4">الخطوة الحالية</dt><dd class="col-8">{{ $currentStep?->name_ar ?? $currentStep?->name_en ?? '-' }}</dd>
                                            </dl>
                                            <div class="approval-request-section approval-request-section--danger">
                                                <h3 class="h6">سبب الحذف</h3>
                                                <p>{{ $deleteRequest->reason }}</p>
                                            </div>
                                            <label class="form-label mt-3">ملاحظة اختيارية</label>
                                            <textarea name="comment" class="form-control" rows="3"></textarea>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-success">تأكيد الاعتماد</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div class="modal fade" id="reject-delete-request-{{ $deleteRequest->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form method="POST" action="{{ route('role.programs.approvals.delete_requests.update', $deleteRequest) }}" class="modal-content monthly-delete-modal">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="decision" value="rejected">
                                        <div class="modal-header border-0 pb-0">
                                            <div>
                                                <span class="monthly-delete-modal__icon"><i class="fas fa-times" aria-hidden="true"></i></span>
                                                <h2 class="modal-title h5 mt-3">رفض طلب الحذف</h2>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row g-2 mb-3">
                                                <dt class="col-4">النشاط</dt><dd class="col-8">{{ $activity?->title ?? '-' }}</dd>
                                                <dt class="col-4">طالب الحذف</dt><dd class="col-8">{{ $deleteRequest->requester?->name ?? '-' }}</dd>
                                                <dt class="col-4">الخطوة الحالية</dt><dd class="col-8">{{ $currentStep?->name_ar ?? $currentStep?->name_en ?? '-' }}</dd>
                                            </dl>
                                            <label class="form-label fw-semibold">سبب الرفض <span class="text-danger">*</span></label>
                                            <textarea name="comment" class="form-control" rows="4" required></textarea>
                                        </div>
                                        <div class="modal-footer border-0">
                                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                            <button type="submit" class="btn btn-danger">تأكيد الرفض</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="approval-pending-box">
                                <strong>القرار بانتظار: {{ $deleteRequest->currentApprover?->name ?? ($currentStep?->role?->display_name ?? $currentStep?->role?->name ?? '-') }}</strong>
                                <span>الخطوة الحالية: {{ $currentStep?->name_ar ?? $currentStep?->name_en ?? '-' }}</span>
                                <span>حالة الطلب: {{ $deleteRequest->status }}</span>
                                <span>طالب الحذف: {{ $deleteRequest->requester?->name ?? '-' }} — {{ optional($deleteRequest->requested_at)->format('Y-m-d H:i') ?? '-' }}</span>
                            </div>
                        @endif
                    </footer>
                </article>
            @empty
                <div class="wf-card card"><div class="card-body text-center text-muted">لا توجد طلبات حذف.</div></div>
            @endforelse
        </div>
        <div class="mt-3 approvals-pagination-wrap">{{ ($deleteRequests ?? null)?->links() }}</div>
    @endif

    @if($activeApprovalTab === 'edit')
        <div class="approval-request-list">
            @forelse($editRequests ?? [] as $editRequest)
                @php
                    $activity = $editRequest->monthlyActivity;
                    $instance = $editRequest->workflowInstance;
                    $currentStep = $instance?->currentStep;
                    $history = collect($editRequest->approval_history ?? []);
                    $approvedCount = $history->where('decision', 'approved')->count();
                    $totalSteps = max(($instance?->workflow?->steps?->count() ?? 0), 1);
                    $progress = min(100, round(($approvedCount / $totalSteps) * 100));
                @endphp
                <article class="approval-request-card approval-request-card--edit">
                    <header class="approval-request-card__header">
                        <div>
                            <div class="approval-request-card__eyebrow"><i class="fas fa-edit" aria-hidden="true"></i> طلب تعديل</div>
                            <h2 class="approval-request-card__title">{{ $activity?->title ?? '#' . $editRequest->entity_id }}</h2>
                        </div>
                        <div class="approval-request-card__badges">
                            <span class="wf-status-badge wf-status-{{ $editRequest->status }}">{{ $editRequest->status }}</span>
                            <span class="approval-version-badge">نسخة {{ (int) ($activity?->version_number ?? $activity?->plan_version ?? 1) }}</span>
                        </div>
                    </header>

                    <div class="approval-request-card__grid">
                        <div class="approval-info-item"><i class="fas fa-building" aria-hidden="true"></i><span>الفرع</span><strong>{{ $activity?->branch?->name ?? '-' }}</strong></div>
                        <div class="approval-info-item"><i class="fas fa-user" aria-hidden="true"></i><span>طالب التعديل</span><strong>{{ $editRequest->requester?->name ?? '-' }}</strong></div>
                        <div class="approval-info-item"><i class="fas fa-calendar-day" aria-hidden="true"></i><span>تاريخ النشاط</span><strong>{{ optional($activity?->proposed_date)->format('Y-m-d') ?? '-' }}</strong></div>
                        <div class="approval-info-item"><i class="fas fa-clock" aria-hidden="true"></i><span>تاريخ الطلب</span><strong>{{ optional($editRequest->requested_at)->format('Y-m-d H:i') ?? '-' }}</strong></div>
                    </div>

                    <section class="approval-request-workflow">
                        <div class="approval-request-workflow__head">
                            <span><i class="fas fa-user-check" aria-hidden="true"></i> المعتمد الحالي: {{ $currentStep?->name_ar ?? $currentStep?->name_en ?? '-' }}</span>
                            <strong>{{ $approvedCount }}/{{ $totalSteps }}</strong>
                        </div>
                        <div class="approvals-status-progress"><span style="width: {{ $progress }}%"></span></div>
                    </section>

                    @if(!empty($editRequest->workflow_timeline))
                        <section class="approval-request-workflow approval-request-workflow--timeline">
                            <h3><i class="fas fa-route" aria-hidden="true"></i> تتبع مسار الاعتماد</h3>
                            <div class="approval-workflow-timeline">
                                @foreach($editRequest->workflow_timeline as $step)
                                    <div class="approval-workflow-timeline__item {{ !empty($step['is_current']) ? 'is-current' : '' }}">
                                        <div><strong>{{ $step['step_name'] ?? '-' }}</strong><span>{{ $step['role_name'] ?? '-' }}</span></div>
                                        <div><span>{{ $step['approver_name'] ?? '-' }}</span><strong>{{ $step['status'] ?? '-' }}</strong><small>{{ $step['decided_at'] ?? '-' }}</small></div>
                                        @if(!empty($step['comment']))<p>{{ $step['comment'] }}</p>@endif
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    <section class="approval-changes-summary">
                        <h3><i class="fas fa-exchange-alt" aria-hidden="true"></i> ملخص التغييرات</h3>
                        <div class="approval-changes-grid">
                            @foreach(($editRequest->changed_values ?? []) as $field => $change)
                                <div class="approval-change-row">
                                    <div class="approval-change-row__field">{{ $field }}</div>
                                    <div class="approval-change-row__values">
                                        <div><span>القيمة القديمة</span><strong>{{ is_array($change['old'] ?? null) ? json_encode($change['old'], JSON_UNESCAPED_UNICODE) : ($change['old'] ?? '-') }}</strong></div>
                                        <div><span>القيمة الجديدة</span><strong>{{ is_array($change['new'] ?? null) ? json_encode($change['new'], JSON_UNESCAPED_UNICODE) : ($change['new'] ?? '-') }}</strong></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <footer class="approval-request-card__footer">
                        <button class="btn btn-sm btn-outline-primary approval-activity-summary-trigger" type="button" data-activity-title="{{ e($activity?->title ?? 'تفاصيل النشاط') }}" data-details-url="{{ $activity ? route('role.programs.approvals.details', ['monthlyActivity' => $activity, 'view' => 'activity']) : '' }}"><i class="fas fa-eye me-1" aria-hidden="true"></i> عرض التفاصيل</button>
                        @if($editRequest->can_current_user_decide ?? false)
                            <form method="POST" action="{{ route('role.programs.approvals.edit_requests.update', $editRequest) }}" class="approval-decision-form">
                                @csrf @method('PUT')
                                <input name="comment" class="form-control form-control-sm" placeholder="ملاحظة اختيارية">
                                <button name="decision" value="approved" class="btn btn-sm btn-success"><i class="fas fa-check me-1" aria-hidden="true"></i> اعتماد طلب التعديل</button>
                                <button name="decision" value="rejected" class="btn btn-sm btn-outline-danger"><i class="fas fa-times me-1" aria-hidden="true"></i> رفض طلب التعديل</button>
                            </form>
                        @else
                            <div class="approval-pending-box">
                                <strong>القرار بانتظار: {{ $editRequest->currentApprover?->name ?? ($currentStep?->role?->display_name ?? $currentStep?->role?->name ?? '-') }}</strong>
                                <span>الخطوة الحالية: {{ $currentStep?->name_ar ?? $currentStep?->name_en ?? '-' }}</span>
                                <span>حالة الطلب: {{ $editRequest->status }}</span>
                                <span>طالب التعديل: {{ $editRequest->requester?->name ?? '-' }} — {{ optional($editRequest->requested_at)->format('Y-m-d H:i') ?? '-' }}</span>
                            </div>
                        @endif
                    </footer>
                </article>
            @empty
                <div class="wf-card card"><div class="card-body text-center text-muted">لا توجد طلبات تعديل.</div></div>
            @endforelse
        </div>
        <div class="mt-3 approvals-pagination-wrap">{{ ($editRequests ?? null)?->links() }}</div>
    @endif

    @if($activeApprovalTab === 'approval')
    <div class="wf-card card mb-3">
        <div class="card-header approvals-card-header">
            <h2 class="h6 mb-0">التصفية وعرض الاعتمادات</h2>
        </div>
        <div class="card-body d-flex flex-column gap-3">
            <div class="wf-tabbar">
                <a class="wf-tab {{ request('my_pending') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['my_pending' => 1])) }}">{{ __('workflow_ui.approvals.tabs.my_pending') }}</a>
                <a class="wf-tab {{ !request('my_pending') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except(['page','my_pending']), ['my_pending' => null])) }}">{{ __('workflow_ui.approvals.tabs.all') }}</a>
            </div>

            <form method="GET" class="row g-2 align-items-end">
                @include('pages.shared.filters.workflow-status-and-step', [
                    'statusFieldName' => 'approval_status',
                    'statusLabel' => __('workflow_ui.approvals.filters.status_type'),
                    'statusPlaceholder' => __('workflow_ui.approvals.filters.all_statuses'),
                    'statusOptions' => $statusOptions,
                    'selectedStatus' => $filters['approval_status'] ?? '',
                    'statusColumnClass' => 'col-md-3',
                    'stepFieldName' => 'current_step',
                    'stepLabel' => __('workflow_ui.common.current_step'),
                    'stepPlaceholder' => __('workflow_ui.common.none_option'),
                    'currentStepOptions' => $currentStepOptions,
                    'selectedStep' => $filters['current_step'] ?? '',
                    'stepColumnClass' => 'col-md-2',
                ])
                @include('pages.shared.filters.select-field', [
                    'columnClass' => 'col-md-2',
                    'fieldName' => 'branch_id',
                    'label' => __('workflow_ui.approvals.filters.branch'),
                    'placeholder' => __('workflow_ui.common.none_option'),
                    'options' => $branches,
                    'selectedValue' => $filters['branch_id'] ?? '',
                    'optionValueKey' => 'id',
                    'optionLabelKey' => 'name',
                ])
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.approvals.filters.from') }}</label><input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.approvals.filters.to') }}</label><input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button class="btn btn-outline-primary btn-sm">{{ __('workflow_ui.approvals.filters.apply') }}</button>
                    @if(!empty($filters['approval_status']) || !empty($filters['branch_id']) || !empty($filters['current_step']) || !empty($filters['date_from']) || !empty($filters['date_to']) || !empty($filters['my_pending']))
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('role.programs.approvals.index') }}">{{ __('workflow_ui.approvals.filters.reset') }}</a>
                    @endif
                </div>
            </form>
        </div>
        <div class="card-footer approvals-card-footer small text-muted">
            استخدم الفلاتر للحصول على النتائج المطلوبة بسرعة.
        </div>
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse($activityCards as $card)
            @include('pages.monthly_activities.approvals.partials.activity-card', ['card' => $card])
        @empty
            <div class="wf-card card"><div class="card-body"><p class="wf-muted mb-0">{{ __('workflow_ui.common.no_data') }}</p></div></div>
        @endforelse
    </div>

    <div class="mt-3 approvals-pagination-wrap">{{ $activities->links() }}</div>



    @endif

    <div class="modal fade" id="approvalActivitySummaryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content approvals-activity-summary-modal">
                <div class="modal-header">
                    <div>
                        <div class="small text-muted">تفاصيل النشاط</div>
                        <h2 class="modal-title h5" id="approvalActivitySummaryTitle">تفاصيل النشاط</h2>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="approvalActivitySummaryBody">
                    <div class="border rounded-3 p-3 wf-panel-soft">جاري تحميل تفاصيل النشاط...</div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ $versionedAsset('assets/js/monthly-approvals.js') }}"></script>
@endpush
