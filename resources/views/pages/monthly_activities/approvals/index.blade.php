@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
@endpush

@php
    $viewer = auth()->user();
    $isNotesOnlyRole = $viewer?->hasRole('workshops_secretary') || $viewer?->hasRole('communication_head');
    $currentStatus = request('status');
@endphp

@section('content')
<div class="workflow-ui">
    <div class="wf-card card mb-4">
        <div class="card-body">
            <h1 class="wf-page-title mb-1">{{ __('workflow_ui.approvals.title') }}</h1>
            <p class="wf-muted mb-0">{{ __('workflow_ui.approvals.subtitle') }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="wf-card card mb-3">
        <div class="card-body d-flex flex-column gap-3">
            <div class="wf-tabbar">
                <a class="wf-tab {{ request('my_pending') ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['my_pending' => 1, 'status' => null])) }}">{{ __('workflow_ui.approvals.tabs.my_pending') }}</a>
                <a class="wf-tab {{ !request('my_pending') && !$currentStatus ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except(['page','status','my_pending']), [])) }}">{{ __('workflow_ui.approvals.tabs.all') }}</a>
                <a class="wf-tab {{ $currentStatus === 'approved' ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['status' => 'approved', 'my_pending' => null])) }}">{{ __('workflow_ui.approvals.tabs.approved') }}</a>
                <a class="wf-tab {{ $currentStatus === 'rejected' ? 'active' : '' }}" href="{{ route('role.programs.approvals.index', array_merge(request()->except('page'), ['status' => 'rejected', 'my_pending' => null])) }}">{{ __('workflow_ui.approvals.tabs.rejected') }}</a>
            </div>

            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.common.status') }}</label><input class="form-control" name="status" value="{{ $filters['status'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.common.current_step') }}</label><input class="form-control" name="current_step" value="{{ $filters['current_step'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.common.assignee') }}</label><input class="form-control" name="assignee" value="{{ $filters['assignee'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.approvals.filters.branch') }}</label><select class="form-select" name="branch_id"><option value="">-</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null)==$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.approvals.filters.from') }}</label><input class="form-control" type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"></div>
                <div class="col-md-2"><label class="form-label">{{ __('workflow_ui.approvals.filters.to') }}</label><input class="form-control" type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"></div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-outline-primary btn-sm">{{ __('workflow_ui.approvals.filters.apply') }}</button></div>
            </form>
        </div>
    </div>

    <div class="d-flex flex-column gap-3">
        @forelse($activities as $activity)
            @php
                $wf = $activity->workflowInstance;
                $logs = $wf?->logs ?? collect();
                $status = $wf?->status ?? 'pending';
                $statusClass = 'wf-status-' . $status;
                $latestApproval = $activity->approvals->last();
            @endphp
            <div class="wf-card card">
                <div class="card-body">
                    <div class="wf-summary mb-3">
                        <div>
                            <h3 class="h6 mb-1">{{ $activity->title }}</h3>
                            <div class="wf-kv">{{ optional($activity->branch)->name ?? '-' }} · {{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</div>
                            <div class="wf-kv mt-1">{{ __('workflow_ui.common.current_step') }}: {{ $wf?->currentStep?->name_ar ?? $wf?->currentStep?->name_en ?? '-' }}</div>
                        </div>
                        <div class="text-end">
                            <span class="wf-status-badge {{ in_array($status, ['approved','rejected','changes_requested','in_progress','pending']) ? $statusClass : 'wf-status-default' }}">
                                @if($status === 'approved') <i class="feather-check-circle"></i>
                                @elseif($status === 'rejected') <i class="feather-x-circle"></i>
                                @elseif($status === 'changes_requested') <i class="feather-rotate-ccw"></i>
                                @else <i class="feather-clock"></i> @endif
                                {{ __('workflow_ui.approvals.status_labels.' . $status) }}
                            </span>
                        </div>
                    </div>

                    <div class="accordion" id="approval-accordion-{{ $activity->id }}">
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header" id="heading-{{ $activity->id }}">
                                <button class="accordion-button collapsed p-0 bg-transparent shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#body-{{ $activity->id }}">
                                    {{ __('workflow_ui.approvals.details') }}
                                </button>
                            </h2>
                            <div id="body-{{ $activity->id }}" class="accordion-collapse collapse" data-bs-parent="#approval-accordion-{{ $activity->id }}">
                                <div class="accordion-body px-0 pt-3">
                                    <div class="row g-3">
                                        <div class="col-lg-7">
                                            <div class="border rounded-3 p-3 h-100">
                                                <h4 class="h6 mb-3">{{ __('workflow_ui.approvals.timeline.title') }}</h4>
                                                <ol class="wf-stepper">
                                                    @forelse($logs as $wfLog)
                                                        @php
                                                            $action = $wfLog->action;
                                                            $actionClass = in_array($action, ['approved','rejected','changes_requested']) ? 'wf-status-' . $action : 'wf-status-default';
                                                        @endphp
                                                        <li class="wf-step {{ $wf?->current_step_id === $wfLog->workflow_step_id ? 'current' : '' }}">
                                                            <span class="wf-step-dot">
                                                                @if($action === 'approved')<i class="feather-check"></i>
                                                                @elseif($action === 'rejected')<i class="feather-x"></i>
                                                                @elseif($action === 'changes_requested')<i class="feather-rotate-ccw"></i>
                                                                @else<i class="feather-clock"></i>@endif
                                                            </span>
                                                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                                <div>
                                                                    <div class="fw-semibold">{{ $wfLog->step?->name_ar ?? $wfLog->step?->name_en ?? $wfLog->step?->step_key ?? '-' }}</div>
                                                                    <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.actor') }}: {{ $wfLog->actor?->name ?? '-' }}</div>
                                                                    <div class="wf-kv">{{ __('workflow_ui.common.time') }}: {{ $wfLog->acted_at?->format('Y-m-d H:i') ?? '-' }}</div>
                                                                    <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.comment') }}: {{ $wfLog->comment ?: '-' }}</div>
                                                                </div>
                                                                <span class="wf-status-badge {{ $actionClass }}">{{ __('workflow_ui.approvals.status_labels.' . $action) }}</span>
                                                            </div>
                                                        </li>
                                                    @empty
                                                        <li class="wf-step"><span class="wf-step-dot"><i class="feather-clock"></i></span><div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div></li>
                                                    @endforelse
                                                </ol>
                                            </div>
                                        </div>

                                        <div class="col-lg-5">
                                            <div class="border rounded-3 p-3 mb-3">
                                                <h4 class="h6 mb-2">{{ __('workflow_ui.common.status') }}</h4>
                                                <div class="wf-kv">{{ __('workflow_ui.common.assignee') }}: {{ $wf?->currentStep?->role?->name ?? $wf?->currentStep?->permission?->name ?? '-' }}</div>
                                                <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.decision') }}: {{ $latestApproval?->decision ?? '-' }}</div>
                                            </div>

                                            <form method="POST" action="{{ route('role.programs.approvals.update', $activity) }}" class="decision-form" data-confirm-title="{{ __('workflow_ui.approvals.confirm_action') }}" data-confirm-body="{{ __('workflow_ui.approvals.confirm_action_body') }}" data-comment-required="{{ __('workflow_ui.approvals.comment_required') }}">
                                                @csrf
                                                @method('PUT')

                                                @if($isNotesOnlyRole)
                                                    <div class="mb-2">
                                                        <label class="form-label">{{ __('workflow_ui.common.comment') }}</label>
                                                        <textarea class="form-control" name="note" rows="3" required></textarea>
                                                    </div>
                                                    @if($viewer?->hasRole('communication_head'))
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
                                                @else
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
                                                @endif

                                                <div class="d-flex justify-content-end">
                                                    <button class="btn btn-primary btn-sm">{{ __('workflow_ui.approvals.submit_decision') }}</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="wf-card card"><div class="card-body"><p class="wf-muted mb-0">{{ __('workflow_ui.common.no_data') }}</p></div></div>
        @endforelse
    </div>

    <div class="mt-3">{{ $activities->links() }}</div>
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var formToSubmit = null;
        var modalElement = document.getElementById('decisionConfirmModal');
        var confirmModal = modalElement ? new bootstrap.Modal(modalElement) : null;

        document.querySelectorAll('.decision-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var select = form.querySelector('.decision-select');
                var comment = form.querySelector('.decision-comment');

                if (select && comment && ['changes_requested', 'rejected'].includes(select.value) && !comment.value.trim()) {
                    event.preventDefault();
                    alert(form.dataset.commentRequired);
                    return;
                }

                if (confirmModal) {
                    event.preventDefault();
                    formToSubmit = form;
                    document.getElementById('decisionConfirmTitle').textContent = form.dataset.confirmTitle;
                    document.getElementById('decisionConfirmBody').textContent = form.dataset.confirmBody;
                    confirmModal.show();
                }
            });
        });

        var submitBtn = document.getElementById('decisionConfirmSubmit');
        if (submitBtn) {
            submitBtn.addEventListener('click', function () {
                if (formToSubmit) {
                    formToSubmit.submit();
                }
            });
        }
    });
</script>
@endpush
