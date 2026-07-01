@extends('layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/event-ui-shared.css') }}">
<link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/workflow-ui.css') }}">
<link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/agenda-approvals.css') }}">
@endpush


@section('content')
    @php
        $approvalStats = [
            [
                'label' => __('workflow_ui.approvals.filters.my_pending'),
                'value' => $events->filter(fn ($event) => (bool) data_get($event, 'workflow_summary.can_current_user_decide', $event->can_current_user_decide ?? false))->count(),
                'tone' => 'blue',
            ],
            [
                'label' => __('app.roles.relations.agenda.status_labels.published'),
                'value' => $events->filter(fn ($event) => in_array((string) data_get($event, 'workflow_summary.status_key'), ['published', 'approved', 'relations_approved'], true))->count(),
                'tone' => 'green',
            ],
            [
                'label' => __('app.roles.relations.approvals.filters.status'),
                'value' => $events->filter(fn ($event) => ! in_array((string) data_get($event, 'workflow_summary.status_key'), ['draft', 'published', 'approved', 'relations_approved'], true))->count(),
                'tone' => 'amber',
            ],
        ];
    @endphp

    <div class="workflow-ui agenda-approvals-page">
        <section class="agenda-approvals-hero mb-4">
            <div>
                <div class="agenda-approvals-eyebrow">
                    <i class="feather-check-circle"></i>
                    <span>{{ __('app.roles.relations.approvals.title') }}</span>
                </div>
                <h1>{{ __('app.roles.relations.approvals.title') }}</h1>
                <p>{{ __('app.roles.relations.approvals.subtitle') }}</p>
            </div>
            <div class="agenda-approvals-stats">
                @foreach($approvalStats as $stat)
                    <div class="agenda-approval-stat agenda-approval-stat--{{ $stat['tone'] }}">
                        <span>{{ $stat['label'] }}</span>
                        <strong>{{ $stat['value'] }}</strong>
                    </div>
                @endforeach
            </div>
        </section>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif


    @php($activeApprovalTab = request('tab', 'approval'))
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link {{ $activeApprovalTab === 'approval' ? 'active' : '' }}" href="{{ route('role.relations.approvals.index', array_merge(request()->except('page'), ['tab' => 'approval'])) }}">طلبات الاعتماد</a></li>
        <li class="nav-item"><a class="nav-link {{ $activeApprovalTab === 'delete' ? 'active' : '' }}" href="{{ route('role.relations.approvals.index', array_merge(request()->except('page'), ['tab' => 'delete'])) }}">طلبات الحذف</a></li>
        <li class="nav-item"><a class="nav-link {{ $activeApprovalTab === 'edit' ? 'active' : '' }}" href="{{ route('role.relations.approvals.index', array_merge(request()->except('page'), ['tab' => 'edit'])) }}">طلبات التعديل</a></li>
    </ul>

    @if($activeApprovalTab === 'delete')
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5 mb-3">طلبات حذف الأجندة السنوية</h2>
            <div class="table-responsive"><table class="table table-sm align-middle">
                <thead><tr><th>الأجندة</th><th>الفرع</th><th>طالب الحذف</th><th>سبب الحذف</th><th>الحالة</th><th>الإجراء</th></tr></thead>
                <tbody>
                @forelse($deleteRequests ?? [] as $deleteRequest)
                    <tr>
                        <td>{{ $deleteRequest->agendaEvent?->event_name ?? '#' . $deleteRequest->entity_id }}</td>
                        <td>{{ $deleteRequest->agendaEvent?->department?->name ?? '-' }}</td>
                        <td>{{ $deleteRequest->requester?->name ?? '-' }}<br><small>{{ optional($deleteRequest->requested_at)->format('Y-m-d H:i') }}</small></td>
                        <td>{{ $deleteRequest->reason }}</td>
                        <td><span class="badge bg-secondary">{{ $deleteRequest->status }}</span><br><small>{{ $deleteRequest->workflowInstance?->currentStep?->name_ar }}</small></td>
                        <td>@if(in_array($deleteRequest->status, ['pending','in_progress','changes_requested'], true))
                            <form method="POST" action="{{ route('role.relations.approvals.delete_requests.update', $deleteRequest) }}" class="d-flex gap-1 flex-wrap">@csrf @method('PUT')
                                <input name="comment" class="form-control form-control-sm" placeholder="ملاحظة اختيارية">
                                <button name="decision" value="approved" class="btn btn-sm btn-success">اعتماد</button>
                                <button name="decision" value="rejected" class="btn btn-sm btn-danger">رفض</button>
                            </form>
                        @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">لا توجد طلبات حذف.</td></tr>
                @endforelse
                </tbody>
            </table></div>{{ ($deleteRequests ?? null)?->links() }}</div></div>
    @endif

    @if($activeApprovalTab === 'edit')
        <div class="card mb-4"><div class="card-body">
            <h2 class="h5 mb-3">طلبات تعديل الأجندة السنوية</h2>
            <div class="table-responsive"><table class="table table-sm align-middle">
                <thead><tr><th>الأجندة</th><th>طالب التعديل</th><th>التغييرات</th><th>الحالة</th><th>الإجراء</th></tr></thead>
                <tbody>
                @forelse($editRequests ?? [] as $editRequest)
                    <tr>
                        <td>{{ $editRequest->agendaEvent?->event_name ?? '#' . $editRequest->entity_id }}</td>
                        <td>{{ $editRequest->requester?->name ?? '-' }}<br><small>{{ optional($editRequest->requested_at)->format('Y-m-d H:i') }}</small></td>
                        <td><div class="table-responsive"><table class="table table-bordered table-xs mb-0"><thead><tr><th>الحقل</th><th>القيمة القديمة</th><th>القيمة الجديدة</th></tr></thead><tbody>@foreach(($editRequest->changed_values ?? []) as $field => $change)<tr><td>{{ $field }}</td><td>{{ is_array($change['old'] ?? null) ? json_encode($change['old'], JSON_UNESCAPED_UNICODE) : ($change['old'] ?? '-') }}</td><td>{{ is_array($change['new'] ?? null) ? json_encode($change['new'], JSON_UNESCAPED_UNICODE) : ($change['new'] ?? '-') }}</td></tr>@endforeach</tbody></table></div></td>
                        <td><span class="badge bg-secondary">{{ $editRequest->status }}</span><br><small>{{ $editRequest->workflowInstance?->currentStep?->name_ar }}</small></td>
                        <td>@if(in_array($editRequest->status, ['pending','in_progress','changes_requested'], true))
                            <form method="POST" action="{{ route('role.relations.approvals.edit_requests.update', $editRequest) }}" class="d-flex gap-1 flex-wrap">@csrf @method('PUT')
                                <input name="comment" class="form-control form-control-sm" placeholder="ملاحظة اختيارية">
                                <button name="decision" value="approved" class="btn btn-sm btn-success">اعتماد</button>
                                <button name="decision" value="rejected" class="btn btn-sm btn-danger">رفض</button>
                            </form>
                        @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted">لا توجد طلبات تعديل.</td></tr>
                @endforelse
                </tbody>
            </table></div>{{ ($editRequests ?? null)?->links() }}</div></div>
    @endif

    @if($activeApprovalTab === 'approval')
        <div class="wf-card card agenda-approvals-filter mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('role.relations.approvals.index') }}" class="row g-3 align-items-end">
                    @include('pages.shared.filters.workflow-status-and-step', [
                        'statusFieldName' => 'approval_status',
                        'statusFieldId' => 'approval_status',
                        'statusLabel' => __('app.roles.relations.approvals.filters.status'),
                        'statusPlaceholder' => __('app.roles.relations.approvals.filters.all'),
                        'statusOptions' => $statusOptions,
                        'selectedStatus' => $filters['approval_status'] ?? '',
                        'stepFieldName' => 'current_step',
                        'stepFieldId' => 'current_step',
                        'stepLabel' => __('workflow_ui.common.current_step'),
                        'stepPlaceholder' => __('workflow_ui.common.none_option'),
                        'currentStepOptions' => $currentStepOptions,
                        'selectedStep' => $filters['current_step'] ?? '',
                    ])
                    <div class="col-auto d-flex gap-2">
                        <button class="btn btn-primary" type="submit">{{ __('app.roles.relations.approvals.filters.apply') }}</button>
                        @if(!empty($filters['approval_status']) || !empty($filters['current_step']))
                            <a class="btn btn-outline-secondary" href="{{ route('role.relations.approvals.index') }}">{{ __('app.roles.relations.approvals.filters.reset') }}</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex flex-column gap-3">
            @forelse ($events as $event)
                @php
                    $workflowSummary = $event->workflow_summary ?? [];
                    $statusKey = (($workflowSummary['status_key'] ?? '') ?: (($workflowSummary['workflow_state'] ?? '') ?: 'pending'));
                    $statusClass = 'wf-status-' . $statusKey;
                    $canDecide = (bool) ($workflowSummary['can_current_user_decide'] ?? $event->can_current_user_decide ?? false);
                    $currentStepLabel = $workflowSummary['current_step_label'] ?? $event->current_step_label ?? __('app.common.na');
                    $currentRoleLabel = $workflowSummary['current_role_label'] ?? $event->current_role_label ?? __('app.common.na');
                    $timeline = collect($workflowSummary['timeline'] ?? []);
                    $latestChangeRequest = $workflowSummary['latest_change_request'] ?? null;
                    $progressCurrent = (int) ($workflowSummary['completed_steps_count'] ?? 0);
                    $progressTotal = max((int) ($workflowSummary['total_steps_count'] ?? 0), 1);
                    $progressPercent = min(100, round(($progressCurrent / $progressTotal) * 100));
                @endphp

                <div class="wf-card card agenda-approval-card agenda-approval-card--{{ $statusKey }}">
                    <div class="card-body">
                        <div class="wf-summary agenda-approval-summary mb-3">
                            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                                <div>
                                    <div class="agenda-approval-card__eyebrow">{{ $currentRoleLabel }}</div>
                                    <h3 class="agenda-approval-card__title">{{ $event->event_name }}</h3>
                                    <div class="wf-kv">
                                        {{ optional($event->event_date)->format('d/m/Y') ?? sprintf('%02d/%02d', $event->day, $event->month) }}
                                        @if($event->ownerDepartment?->name)
                                            | {{ $event->ownerDepartment->name }}
                                        @endif
                                    </div>
                                    <div class="wf-kv">
                                        {{ __('workflow_ui.common.submitted_by') }}: {{ $workflowSummary['submitted_by_name'] ?? '-' }}
                                        @if(!empty($workflowSummary['submitted_at']))
                                            | {{ __('workflow_ui.common.submitted_at') }}: {{ $workflowSummary['submitted_at'] }}
                                        @endif
                                    </div>
                                </div>
                                <span class="wf-status-badge {{ $statusClass }}">
                                    {{ $workflowSummary['status_label'] ?? __('app.common.na') }}
                                </span>
                            </div>

                            <div class="agenda-approval-progress mt-3" style="--approval-progress: {{ $progressPercent }}%;">
                                <div class="agenda-approval-progress__head">
                                    <span>{{ __('workflow_ui.common.current_step') }}: {{ $currentStepLabel }}</span>
                                    <strong>{{ $progressCurrent }}/{{ $progressTotal }}</strong>
                                </div>
                                <div class="agenda-approval-progress__bar"><span></span></div>
                            </div>
                        </div>

                        <div class="accordion" id="agenda-approval-accordion-{{ $event->id }}">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header" id="agenda-heading-{{ $event->id }}">
                                    <button class="accordion-button collapsed agenda-approval-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#agenda-body-{{ $event->id }}">
                                        {{ __('app.roles.relations.approvals.actions.review') }}
                                    </button>
                                </h2>
                                <div id="agenda-body-{{ $event->id }}" class="accordion-collapse collapse" data-bs-parent="#agenda-approval-accordion-{{ $event->id }}">
                                    <div class="accordion-body px-0 pt-3">
                                        <div class="row g-3">
                                            <div class="col-lg-7">
                                                <div class="agenda-approval-panel agenda-approval-panel--map h-100">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h4 class="h6 mb-0">{{ __('workflow_ui.approvals.workflow_map') }}</h4>
                                                        <span class="wf-kv">{{ $currentRoleLabel }}</span>
                                                    </div>
                                                    <div class="wf-state-stack">
                                                        @foreach($workflowSummary['steps'] ?? [] as $step)
                                                            <div class="wf-state-card wf-state-card--{{ $step['state'] }} {{ !empty($step['is_current']) ? 'is-current' : '' }}">
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
                                                <div class="agenda-approval-panel agenda-approval-panel--changes mb-3">
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

                                                <div class="agenda-approval-panel agenda-approval-panel--history mb-3">
                                                    <details>
                                                        <summary class="fw-semibold" style="cursor:pointer;">{{ __('workflow_ui.approvals.workflow_history') }}</summary>
                                                        <div class="wf-state-stack mt-3">
                                                            @forelse($timeline as $entry)
                                                                <div class="wf-state-card wf-state-card--{{ $entry['action'] }}">
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

                                                @if($canDecide)
                                                    <form method="POST" action="{{ route('role.relations.approvals.update', $event) }}" class="agenda-approval-panel agenda-approval-panel--decision">
                                                        @csrf
                                                        @method('PUT')
                                                        <div class="mb-2">
                                                            <label class="form-label">{{ __('app.roles.relations.approvals.fields.decision') }}</label>
                                                            <select class="form-select" name="decision" required>
                                                                <option value="approved">{{ __('app.roles.relations.approvals.decisions.approved') }}</option>
                                                                <option value="changes_requested">{{ __('app.roles.relations.approvals.decisions.changes_requested') }}</option>
                                                                <option value="rejected">{{ __('workflow_ui.approvals.status_labels.rejected') }}</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-2">
                                                            <label class="form-label">{{ __('app.roles.relations.approvals.fields.comment') }}</label>
                                                            <textarea class="form-control" name="comment" rows="3"></textarea>
                                                        </div>
                                                        <div class="d-flex justify-content-end">
                                                            <button class="btn btn-primary btn-sm" type="submit">{{ __('app.roles.relations.approvals.actions.submit') }}</button>
                                                        </div>
                                                    </form>
                                                @else
                                                    <div class="agenda-approval-panel agenda-approval-panel--waiting">
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
                <div class="wf-card card">
                    <div class="card-body">
                        <p class="wf-muted mb-0">{{ __('app.roles.relations.approvals.table.empty') }}</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
    @endif

@endsection
