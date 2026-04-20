@extends('layouts.app')

@push('styles')
@php($workflowUiCssPath = public_path('assets/css/workflow-ui.css'))
@if (file_exists($workflowUiCssPath))
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
@endif
<link rel="stylesheet" href="{{ asset('assets/css/monthly-approvals.css') }}">
@endpush

@section('content')
@php($viewer = $viewer ?? auth()->user())
<div class="workflow-ui">
    <div class="wf-card card mb-4">
        <div class="card-body">
            <h1 class="wf-page-title mb-1">{{ __('workflow_ui.approvals.title') }}</h1>
            <p class="wf-muted mb-0">{{ __('workflow_ui.approvals.subtitle') }}</p>
            <div class="approvals-kpi-row mt-3">
                <div class="approvals-kpi-card">
                    <div class="approvals-kpi-label">إجمالي المعروض</div>
                    <div class="approvals-kpi-value">{{ method_exists($activities, 'total') ? $activities->total() : $activities->count() }}</div>
                </div>
                <div class="approvals-kpi-card">
                    <div class="approvals-kpi-label">قيد المراجعة</div>
                    <div class="approvals-kpi-value">{{ $activities->where('status', 'in_review')->count() }}</div>
                </div>
                <div class="approvals-kpi-card">
                    <div class="approvals-kpi-label">بانتظار قراري</div>
                    <div class="approvals-kpi-value">{{ $activities->where('can_current_user_decide', true)->count() }}</div>
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
        @forelse($activities as $activity)
            @php
                $viewer = $viewer ?? auth()->user();
                $wf = $activity->workflowInstance;
                $workflowSummary = $activity->workflow_summary ?? [];
                $logs = collect($workflowSummary['timeline'] ?? []);
                $statusClass = 'wf-status-' . (($workflowSummary['status_key'] ?? '') ?: (($workflowSummary['workflow_state'] ?? '') ?: ($wf?->status ?? 'pending')));
                $officialCorrespondenceAttachments = $activity->attachments->where('file_type', 'official_correspondence');
                $canUploadOfficialCorrespondence = $viewer?->hasRole('relations_manager')
                    && method_exists($viewer, 'isKheldaUser')
                    && $viewer->isKheldaUser()
                    && $activity->needs_official_correspondence;
                $canDecide = (bool) ($workflowSummary['can_current_user_decide'] ?? $activity->can_current_user_decide ?? false);
                $canAddDepartmentNote = (bool) ($activity->can_add_department_note ?? false);
                $currentStepLabel = $workflowSummary['current_step_label'] ?? __('workflow_ui.common.unknown_step');
                $currentRoleLabel = $workflowSummary['current_role_label'] ?? __('workflow_ui.common.none_option');
                $requirements = [];
                $latestChangeRequest = $workflowSummary['latest_change_request'] ?? null;
                $workflowSteps = collect($workflowSummary['steps'] ?? []);
                $approvedStepsCount = $workflowSteps->where('state', 'approved')->count();
                $totalStepsCount = max($workflowSteps->count(), 1);

                if ($activity->requires_programs) {
                    $requirements[] = __('workflow_ui.approvals.requirements.programs');
                }

                if ($activity->requires_workshops) {
                    $requirements[] = __('workflow_ui.approvals.requirements.workshops');
                }

                if ($activity->requires_communications) {
                    $requirements[] = __('workflow_ui.approvals.requirements.communications');
                }
            @endphp
            <div class="wf-card card approvals-activity-card">
                <div class="card-body">
                    <div class="wf-summary mb-3">
                        <div class="w-100">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <h3 class="h6 mb-1">{{ $activity->title }}</h3>
                                    <div class="wf-kv">{{ optional($activity->branch)->name ?? '-' }} | {{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</div>
                                    <div class="wf-kv">
                                        {{ __('workflow_ui.common.submitted_by') }}: {{ $workflowSummary['submitted_by_name'] ?? '-' }}
                                        @if(!empty($workflowSummary['submitted_at']))
                                            | {{ __('workflow_ui.common.submitted_at') }}: {{ $workflowSummary['submitted_at'] }}
                                        @endif
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="wf-status-badge {{ $statusClass }}">
                                        {{ $workflowSummary['status_label'] ?? __('workflow_ui.common.none_option') }}
                                    </span>
                                </div>
                            </div>

                            <div class="wf-chip-row mt-3">
                                <span class="wf-chip wf-chip-primary">{{ __('workflow_ui.common.current_step') }}: {{ $currentStepLabel }}</span>
                                <span class="wf-chip">{{ __('workflow_ui.common.assignee') }}: {{ $currentRoleLabel }}</span>
                                <span class="wf-chip wf-chip-soft">التقدم: {{ $workflowSummary['completed_steps_count'] ?? 0 }}/{{ $workflowSummary['total_steps_count'] ?? 0 }}</span>
                                @foreach($requirements as $requirement)
                                    <span class="wf-chip wf-chip-soft">{{ $requirement }}</span>
                                @endforeach
                            </div>

                            <div class="approvals-status-panel mt-3">
                                <div class="approvals-status-panel-header">
                                    <h4 class="approvals-status-title mb-0">حالات الاعتماد</h4>
                                    <span class="wf-chip wf-chip-soft">المعتمد: {{ $approvedStepsCount }}/{{ $workflowSteps->count() }}</span>
                                </div>

                                <div class="approvals-status-progress mt-2" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $workflowSteps->count() }}" aria-valuenow="{{ $approvedStepsCount }}">
                                    <span style="width: {{ round(($approvedStepsCount / $totalStepsCount) * 100, 2) }}%"></span>
                                </div>

                                <div class="approvals-status-grid mt-3">
                                    @forelse($workflowSteps as $step)
                                        <div class="approvals-status-item {{ !empty($step['is_current']) ? 'is-current' : '' }}">
                                            <div class="approvals-status-role">{{ $step['role_label'] }}</div>
                                            <span class="wf-status-badge wf-status-{{ $step['state'] }}">
                                                {{ $step['state_label'] }}
                                            </span>
                                        </div>
                                    @empty
                                        <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div>
                                    @endforelse
                                </div>
                            </div>
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
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <h4 class="h6 mb-0">خارطة المسار</h4>
                                                    <span class="wf-kv">{{ $currentRoleLabel }}</span>
                                                </div>
                                                <div class="d-flex flex-column gap-2">
                                                    @foreach($workflowSteps as $step)
                                                        <div class="border rounded-3 p-3 {{ !empty($step['is_current']) ? 'border-primary-subtle bg-light-subtle' : '' }}">
                                                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                                <div>
                                                                    <div class="fw-semibold">{{ $step['label'] }}</div>
                                                                    <div class="wf-kv">{{ $step['role_label'] }}</div>
                                                                    @if(!empty($step['actor_name']) || !empty($step['acted_at']))
                                                                        <div class="wf-kv">{{ $step['actor_name'] ?? '-' }} @if(!empty($step['acted_at'])) | {{ $step['acted_at'] }} @endif</div>
                                                                    @endif
                                                                    @if(!empty($step['comment']))
                                                                        <div class="wf-kv mt-1">{{ $step['comment'] }}</div>
                                                                    @endif
                                                                </div>
                                                                <span class="wf-status-badge wf-status-{{ $step['state'] }}">
                                                                    {{ $step['state_label'] }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-lg-5">
                                            <div class="border rounded-3 p-3 mb-3">
                                                    <h4 class="h6 mb-2">{{ __('workflow_ui.approvals.change_request_title') }}</h4>
                                                @if($latestChangeRequest)
                                                    <div class="wf-kv">{{ __('workflow_ui.approvals.requested_by') }}: {{ $latestChangeRequest['actor_name'] }}</div>
                                                    <div class="wf-kv">{{ __('workflow_ui.approvals.requested_at') }}: {{ $latestChangeRequest['acted_at'] ?? '-' }}</div>
                                                    <div class="wf-kv">{{ __('workflow_ui.common.current_step') }}: {{ $latestChangeRequest['step_label'] }}</div>
                                                    <div class="wf-kv">{{ __('workflow_ui.common.assignee') }}: {{ $latestChangeRequest['role_label'] }}</div>
                                                    <div class="wf-kv mt-2">{{ $latestChangeRequest['comment'] ?: '-' }}</div>
                                                @else
                                                    <div class="wf-kv">{{ __('workflow_ui.approvals.change_request_empty') }}</div>
                                                @endif
                                            </div>

                                            <div class="border rounded-3 p-3 mb-3">
                                                <details>
                                                    <summary class="fw-semibold" style="cursor:pointer;">{{ __('workflow_ui.approvals.workflow_history') }}</summary>
                                                    <div class="d-flex flex-column gap-2 mt-3">
                                                        @forelse($logs as $entry)
                                                            <div class="border rounded p-2">
                                                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                                    <div>
                                                                        <div class="fw-semibold">{{ $entry['step_label'] }}</div>
                                                                        <div class="wf-kv">{{ $entry['role_label'] }}</div>
                                                                        <div class="wf-kv">{{ $entry['actor_name'] }} | {{ $entry['acted_at'] ?? '-' }}</div>
                                                                        <div class="wf-kv">{{ $entry['comment'] ?: '-' }}</div>
                                                                    </div>
                                                                    <span class="wf-status-badge wf-status-{{ $entry['action'] }}">
                                                                        {{ $entry['action_label'] }}
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        @empty
                                                            <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div>
                                                        @endforelse
                                                    </div>
                                                </details>
                                            </div>

                                            @if($activity->needs_official_correspondence)
                                                <div class="border rounded-3 p-3 mb-3">
                                                    <h4 class="h6 mb-2">{{ __('workflow_ui.approvals.official.title') }}</h4>
                                                    <div class="wf-kv mb-2">{{ __('workflow_ui.approvals.official.target') }}: {{ $activity->official_correspondence_target ?: '-' }}</div>
                                                    <div class="wf-kv mb-2">{{ __('workflow_ui.approvals.official.reason') }}: {{ $activity->official_correspondence_reason ?: '-' }}</div>
                                                    <div class="wf-kv mb-2">{{ __('workflow_ui.approvals.official.brief') }}: {{ $activity->official_correspondence_brief ?: '-' }}</div>
                                                    <div class="d-flex flex-column gap-2">
                                                        @forelse($officialCorrespondenceAttachments as $attachment)
                                                            @php($isExternal = filter_var($attachment->file_path, FILTER_VALIDATE_URL))
                                                            <a class="btn btn-sm btn-outline-secondary text-start" href="{{ $isExternal ? $attachment->file_path : asset('storage/'.$attachment->file_path) }}" target="_blank" rel="noopener">
                                                                {{ $attachment->title ?: __('workflow_ui.approvals.official.view_attachment') }}
                                                            </a>
                                                        @empty
                                                            <div class="wf-kv">{{ __('workflow_ui.approvals.official.empty') }}</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            @endif

                                            @if($canDecide || $canAddDepartmentNote)
                                                <form method="POST" action="{{ route('role.programs.approvals.update', $activity) }}" enctype="multipart/form-data" class="decision-form" data-confirm-title="{{ __('workflow_ui.approvals.confirm_action') }}" data-confirm-body="{{ __('workflow_ui.approvals.confirm_action_body') }}" data-comment-required="{{ __('workflow_ui.approvals.comment_required') }}">
                                                    @csrf
                                                    @method('PUT')

                                                    @if($canDecide)
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

                                                    @if($canAddDepartmentNote)
                                                        <div class="mb-2">
                                                            <label class="form-label">{{ __('workflow_ui.approvals.department_note') }}</label>
                                                            <textarea class="form-control" name="note" rows="3"></textarea>
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
                                                    @endif

                                                    @if($canUploadOfficialCorrespondence && $canDecide)
                                                        <div class="border rounded-3 p-3 mb-2 bg-light-subtle">
                                                            <div class="fw-semibold mb-2">{{ __('workflow_ui.approvals.official.upload_title') }}</div>
                                                            <div class="mb-2">
                                                                <label class="form-label">{{ __('workflow_ui.approvals.official.attachment_title') }}</label>
                                                                <input class="form-control" name="official_correspondence_title" value="{{ old('official_correspondence_title', __('workflow_ui.approvals.official.default_attachment_title')) }}">
                                                            </div>
                                                            <div>
                                                                <label class="form-label">{{ __('workflow_ui.approvals.official.upload_field') }}</label>
                                                                <input class="form-control" type="file" name="official_correspondence_file" accept=".pdf,.doc,.docx">
                                                                <small class="text-muted d-block mt-1">{{ __('workflow_ui.approvals.official.upload_help') }}</small>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    <div class="d-flex justify-content-end">
                                                        <button class="btn btn-primary btn-sm">{{ $canDecide ? __('workflow_ui.approvals.submit_decision') : __('workflow_ui.approvals.submit_note') }}</button>
                                                    </div>
                                                </form>
                                            @else
                                                <div class="border rounded-3 p-3 wf-panel-soft">
                                                    <div class="fw-semibold mb-1">{{ __('workflow_ui.approvals.waiting_title') }}</div>
                                                    <div class="wf-kv">{{ __('workflow_ui.approvals.waiting_body', ['role' => $currentRoleLabel]) }}</div>
                                                </div>
                                            @endif
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
