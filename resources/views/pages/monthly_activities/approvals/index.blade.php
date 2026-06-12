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


    @php($activeApprovalTab = request('tab', 'approval'))
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link {{ $activeApprovalTab === 'approval' ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['tab' => 'approval'])) }}">طلبات الاعتماد</a></li>
        <li class="nav-item"><a class="nav-link {{ $activeApprovalTab === 'delete' ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['tab' => 'delete'])) }}">طلبات الحذف</a></li>
        <li class="nav-item"><a class="nav-link {{ $activeApprovalTab === 'edit' ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['tab' => 'edit'])) }}">طلبات التعديل</a></li>
    </ul>

    @if($activeApprovalTab === 'delete')
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5 mb-3">طلبات حذف الخطط الشهرية</h2>
            <div class="table-responsive"><table class="table table-sm align-middle">
                <thead><tr><th>الخطة</th><th>الفرع</th><th>طالب الحذف</th><th>سبب الحذف</th><th>الحالة</th><th>الإجراء</th></tr></thead>
                <tbody>
                @forelse($deleteRequests ?? [] as $deleteRequest)
                    <tr>
                        <td>{{ $deleteRequest->monthlyActivity?->title ?? '#' . $deleteRequest->entity_id }}</td>
                        <td>{{ $deleteRequest->monthlyActivity?->branch?->name ?? '-' }}</td>
                        <td>{{ $deleteRequest->requester?->name ?? '-' }}<br><small>{{ optional($deleteRequest->requested_at)->format('Y-m-d H:i') }}</small></td>
                        <td>{{ $deleteRequest->reason }}</td>
                        <td><span class="badge bg-secondary">{{ $deleteRequest->status }}</span><br><small>{{ $deleteRequest->workflowInstance?->currentStep?->name_ar }}</small></td>
                        <td>@if(in_array($deleteRequest->status, ['pending','in_progress','changes_requested'], true))
                            <form method="POST" action="{{ route('role.programs.approvals.delete_requests.update', $deleteRequest) }}" class="d-flex gap-1 flex-wrap">@csrf @method('PUT')
                                <input name="comment" class="form-control form-control-sm" placeholder="ملاحظة اختيارية">
                                <button name="decision" value="approved" class="btn btn-sm btn-success">اعتماد</button>
                                <button name="decision" value="rejected" class="btn btn-sm btn-danger">رفض</button>
                            </form>
                        @endif</td>
                    </tr>
                @empty<tr><td colspan="6" class="text-center text-muted">لا توجد طلبات حذف.</td></tr>@endforelse
                </tbody>
            </table></div>{{ ($deleteRequests ?? null)?->links() }}</div></div>
    @endif

    @if($activeApprovalTab === 'edit')
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5 mb-3">طلبات تعديل الخطط الشهرية</h2>
            <div class="table-responsive"><table class="table table-sm align-middle">
                <thead><tr><th>الخطة</th><th>طالب التعديل</th><th>التغييرات</th><th>الحالة</th><th>الإجراء</th></tr></thead>
                <tbody>
                @forelse($editRequests ?? [] as $editRequest)
                    <tr>
                        <td>{{ $editRequest->monthlyActivity?->title ?? '#' . $editRequest->entity_id }}</td>
                        <td>{{ $editRequest->requester?->name ?? '-' }}<br><small>{{ optional($editRequest->requested_at)->format('Y-m-d H:i') }}</small></td>
                        <td><div class="table-responsive"><table class="table table-bordered table-xs mb-0"><thead><tr><th>الحقل</th><th>القيمة القديمة</th><th>القيمة الجديدة</th></tr></thead><tbody>@foreach(($editRequest->changed_values ?? []) as $field => $change)<tr><td>{{ $field }}</td><td>{{ is_array($change['old'] ?? null) ? json_encode($change['old'], JSON_UNESCAPED_UNICODE) : ($change['old'] ?? '-') }}</td><td>{{ is_array($change['new'] ?? null) ? json_encode($change['new'], JSON_UNESCAPED_UNICODE) : ($change['new'] ?? '-') }}</td></tr>@endforeach</tbody></table></div></td>
                        <td><span class="badge bg-secondary">{{ $editRequest->status }}</span><br><small>{{ $editRequest->workflowInstance?->currentStep?->name_ar }}</small></td>
                        <td>@if(in_array($editRequest->status, ['pending','in_progress','changes_requested'], true))
                            <form method="POST" action="{{ route('role.programs.approvals.edit_requests.update', $editRequest) }}" class="d-flex gap-1 flex-wrap">@csrf @method('PUT')
                                <input name="comment" class="form-control form-control-sm" placeholder="ملاحظة اختيارية">
                                <button name="decision" value="approved" class="btn btn-sm btn-success">اعتماد</button>
                                <button name="decision" value="rejected" class="btn btn-sm btn-danger">رفض</button>
                            </form>
                        @endif</td>
                    </tr>
                @empty<tr><td colspan="5" class="text-center text-muted">لا توجد طلبات تعديل.</td></tr>@endforelse
                </tbody>
            </table></div>{{ ($editRequests ?? null)?->links() }}</div></div>
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

    @endif

@endsection

@push('scripts')
<script src="{{ $versionedAsset('assets/js/monthly-approvals.js') }}"></script>
@endpush
