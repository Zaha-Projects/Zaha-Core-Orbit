@extends('layouts.app')

@push('styles')
@php($workflowUiCssPath = public_path('assets/css/workflow-ui.css'))
@if (file_exists($workflowUiCssPath))
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('assets/css/monthly-approvals.css') }}">
@endpush

@section('content')
<div class="workflow-ui">
    <div class="wf-card card mb-4">
        <div class="card-body">
            <h1 class="wf-page-title mb-1">{{ __('workflow_ui.approvals.title') }}</h1>
            <p class="wf-muted mb-0">{{ __('workflow_ui.approvals.subtitle') }}</p>
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
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="alert alert-info mb-3">
        الفعاليات الإلزامية المرتبطة بالأجندة السنوية لا تُعرض في شاشة اعتماد الخطط الشهرية لأنها لا تحتاج اعتماد الفرع.
    </div>

    <div class="wf-card card mb-3">
        <div class="card-body d-flex flex-column gap-3">
            <div class="wf-tabbar">
                <a class="wf-tab {{ request('my_pending') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['my_pending' => 1, 'status' => null])) }}">{{ __('workflow_ui.approvals.tabs.my_pending') }}</a>
                <a class="wf-tab {{ !request('my_pending') && !request('status') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except(['page','status','my_pending']), [])) }}">{{ __('workflow_ui.approvals.tabs.all') }}</a>
                <a class="wf-tab {{ request('status') === 'approved' ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['status' => 'approved', 'my_pending' => null])) }}">{{ __('workflow_ui.approvals.tabs.approved') }}</a>
                <a class="wf-tab {{ request('status') === 'rejected' ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['status' => 'rejected', 'my_pending' => null])) }}">{{ __('workflow_ui.approvals.tabs.rejected') }}</a>
            </div>

            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.common.status') }}</label><input class="form-control" name="status" value="{{ $filters['status'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.common.current_step') }}</label><input class="form-control" name="current_step" value="{{ $filters['current_step'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.common.assignee') }}</label><input class="form-control" name="assignee" value="{{ $filters['assignee'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.approvals.filters.branch') }}</label><select class="form-select" name="branch_id"><option value="">{{ __('workflow_ui.common.none_option') }}</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" {{ (string) ($filters['branch_id'] ?? '') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.approvals.filters.from') }}</label><input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.approvals.filters.to') }}</label><input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-outline-primary btn-sm">{{ __('workflow_ui.approvals.filters.apply') }}</button></div>
            </form>
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

<div class="modal fade" id="decisionConfirmModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="decisionConfirmTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="decisionConfirmBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('workflow_ui.common.cancel') }}</button>
                <button type="button" class="btn btn-primary" id="decisionConfirmSubmit">{{ __('workflow_ui.common.confirm') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/monthly-approvals.js') }}"></script>
@endpush
