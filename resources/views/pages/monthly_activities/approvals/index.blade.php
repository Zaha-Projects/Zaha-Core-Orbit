@extends('layouts.new-theme-dashboard')


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
</div>

@endsection

@push('scripts')
<script src="{{ $versionedAsset('assets/js/monthly-approvals.js') }}"></script>
@endpush
