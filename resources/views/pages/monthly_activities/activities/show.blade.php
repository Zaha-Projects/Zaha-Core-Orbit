@extends('layouts.app')


@push('styles')
<link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/workflow-ui.css') }}">
<link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/event-ui-shared.css') }}">
<link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/monthly-activity-show.css') }}">
@endpush

@php
    $title = 'عرض تفاصيل النشاط';
    $editMirrorMode = $editMirrorMode ?? false;
    $workflowSummary = $monthlyActivity->workflow_summary ?? [];
    $monthlyStatusLabels = $monthlyStatusLabels ?? [];
    $executionStatusLabels = $executionStatusLabels ?? [];
    $needAvailabilityLabel = function (string $key) use ($monthlyActivity): string {
        $value = data_get($monthlyActivity->execution_needs_payload ?? [], 'availability.'.$key);

        return match ($value) {
            'available' => 'متوفر داخل المركز',
            'not_available' => 'غير متوفر داخل المركز',
            default => '-',
        };
    };
    $isReadOnlyUnified = (bool) $monthlyActivity->is_from_agenda
        && (string) $monthlyActivity->plan_type === 'unified'
        && (string) optional($monthlyActivity->agendaEvent)->event_type === 'mandatory';
    $viewer = auth()->user();
    $activeDeleteRequest = $activeDeleteRequest ?? null;
    $activeEditRequest = $activeEditRequest ?? null;
    $activeChangeRequest = $activeDeleteRequest ?? $activeEditRequest;
    $hasActiveChangeRequest = (bool) ($hasActiveChangeRequest ?? $activeChangeRequest);
    $canOpenPlanningForm = $viewer?->hasAnyRole([
        'relations_manager',
        'relations_officer',
        'supervisor',
        'followup_officer',
        'evaluation_officer',
        'super_admin',
    ]);
    $canBranchPartialEditUnified = $isReadOnlyUnified
        && (bool) config('monthly_activity.unified_branch_edit.enabled', true)
        && $viewer
        && method_exists($viewer, 'hasBranchScopedMonthlyVisibility')
        && $viewer->hasBranchScopedMonthlyVisibility()
        && ! $viewer->hasRole('super_admin');
    $statusLabel = function (?string $status) use ($monthlyStatusLabels): string {
        if (! $status) {
            return '-';
        }

        return $monthlyStatusLabels[$status]
            ?? \App\Models\EventStatusLookup::labelFor('monthly_activities', $status);
    };
    $executionLabel = function (?string $status) use ($executionStatusLabels): string {
        if (! $status) {
            return '-';
        }

        return $executionStatusLabels[$status] ?? $status;
    };
    $officialCorrespondenceAttachments = $monthlyActivity->attachments->where('file_type', 'official_correspondence');
    $displayExecutionStatus = $monthlyActivity->executionStatusForDisplay();
    $outsideMapNavigationUrl = \App\Support\GoogleMaps::navigationUrl(
        $monthlyActivity->outside_google_maps_url,
        $monthlyActivity->outside_place_name,
        $monthlyActivity->outside_address
    );
@endphp

@section('content')
    <div class="event-module monthly-activity-show-page">
        <section class="monthly-show-hero mb-4">
            <div class="card-body d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                    <h1 class="h4 mb-1"><i class="feather-clipboard me-1"></i>{{ $title }}</h1>
                    <p class="text-muted mb-0">{{ $monthlyActivity->title }}</p>
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        <span class="badge bg-light text-dark border">نسخة {{ (int) ($monthlyActivity->plan_version ?: 1) }}</span>
                        @if (($monthlyActivity->newer_versions_count ?? 0) > 0)
                            <span class="badge bg-secondary-subtle text-secondary">نسخة قديمة</span>
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('role.relations.activities.index') }}">رجوع</a>
                    @if($hasActiveChangeRequest)
                        <span class="btn btn-outline-warning disabled">يوجد طلب تغيير نشط</span>
                    @elseif($isReadOnlyUnified && ! $canBranchPartialEditUnified)
                        <span class="btn btn-outline-success disabled">عرض فقط (موحد معتمد)</span>
                    @elseif($editMirrorMode && $canOpenPlanningForm)
                        <a class="btn btn-primary" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'form' => 1]) }}"><i class="fas fa-pencil-alt me-1" aria-hidden="true"></i>فتح نموذج التعديل</a>
                    @elseif($canOpenPlanningForm)
                        <a class="btn btn-primary" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'form' => 1]) }}"><i class="fas fa-pencil-alt me-1" aria-hidden="true"></i>تعديل النشاط</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'mode' => 'post', 'need_decision' => 1]) }}#execution-needs-decisions">تحديث قرار الاحتياج</a>
                    @endif
                    @if(! $hasActiveChangeRequest && auth()->user()?->hasAnyRole(['relations_manager','relations_officer','supervisor','branch_coordinator','super_admin']))
                        <form method="POST" action="{{ route('role.relations.activities.destroy', $monthlyActivity) }}" data-delete-reason-form="monthly-activity">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="delete_reason">
                            <button class="btn btn-outline-danger" type="submit"><i class="fas fa-trash-alt me-1" aria-hidden="true"></i>حذف النشاط</button>
                        </form>
                    @endif
                </div>
            </div>
        </section>

        @if($activeDeleteRequest ?? false)
            <div class="alert alert-danger border-0 shadow-sm mb-4">
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <i class="fas fa-trash-alt fs-4 mt-1" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <h2 class="h6 fw-bold mb-2">يوجد طلب حذف نشط لهذا النشاط</h2>
                        <p class="mb-3">لا يمكنك تعديل أو حذف هذا النشاط حتى يتم اعتماد طلب الحذف أو رفضه.</p>
                        <div class="row g-2">
                            <div class="col-md-3"><strong>حالة الطلب:</strong> {{ $activeDeleteRequest->status }}</div>
                            <div class="col-md-3"><strong>المعتمد الحالي:</strong> {{ $activeDeleteRequest->currentApprover?->name ?? '-' }}</div>
                            <div class="col-md-3"><strong>تاريخ الطلب:</strong> {{ optional($activeDeleteRequest->requested_at)->format('Y-m-d H:i') ?? '-' }}</div>
                            <div class="col-12"><strong>سبب الحذف:</strong><div class="mt-2 p-3 bg-white rounded border text-danger">{{ $activeDeleteRequest->reason }}</div></div>
                        </div>
                        @if(!empty($activeDeleteRequest->workflow_timeline))
                            <div class="mt-3 bg-white rounded border p-3">
                                <h3 class="h6 fw-bold mb-2">مسار الاعتماد</h3>
                                <div class="d-grid gap-2">
                                    @foreach($activeDeleteRequest->workflow_timeline as $step)
                                        <div class="d-flex justify-content-between gap-2 flex-wrap border-bottom pb-2">
                                            <span><strong>{{ $step['step_name'] ?? '-' }}</strong> — {{ $step['role_name'] ?? '-' }}</span>
                                            <span>{{ $step['approver_name'] ?? '-' }} | {{ $step['status'] ?? '-' }} | {{ $step['decided_at'] ?? '-' }}</span>
                                            @if(!empty($step['comment']))<small class="text-muted w-100">{{ $step['comment'] }}</small>@endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @elseif($activeEditRequest ?? false)
            <div class="alert alert-warning border-0 shadow-sm mb-4">
                <div class="d-flex align-items-start gap-3 flex-wrap">
                    <i class="fas fa-edit fs-4 mt-1" aria-hidden="true"></i>
                    <div class="flex-grow-1">
                        <h2 class="h6 fw-bold mb-2">يوجد طلب تعديل نشط لهذا النشاط</h2>
                        <p class="mb-3">لا يمكنك تعديل أو حذف هذا النشاط حتى يتم اعتماد طلب التعديل أو رفضه.</p>
                        <div class="row g-2 mb-3">
                            <div class="col-md-3"><strong>حالة الطلب:</strong> {{ $activeEditRequest->status }}</div>
                            <div class="col-md-3"><strong>المعتمد الحالي:</strong> {{ $activeEditRequest->currentApprover?->name ?? '-' }}</div>
                            <div class="col-md-3"><strong>تاريخ الطلب:</strong> {{ optional($activeEditRequest->requested_at)->format('Y-m-d H:i') ?? '-' }}</div>
                        </div>
                        @if(!empty($activeEditRequest->workflow_timeline))
                            <div class="mb-3 bg-white rounded border p-3">
                                <h3 class="h6 fw-bold mb-2">مسار الاعتماد</h3>
                                <div class="d-grid gap-2">
                                    @foreach($activeEditRequest->workflow_timeline as $step)
                                        <div class="d-flex justify-content-between gap-2 flex-wrap border-bottom pb-2">
                                            <span><strong>{{ $step['step_name'] ?? '-' }}</strong> — {{ $step['role_name'] ?? '-' }}</span>
                                            <span>{{ $step['approver_name'] ?? '-' }} | {{ $step['status'] ?? '-' }} | {{ $step['decided_at'] ?? '-' }}</span>
                                            @if(!empty($step['comment']))<small class="text-muted w-100">{{ $step['comment'] }}</small>@endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        @if(!empty($activeEditRequest->changed_values))
                            <div class="table-responsive">
                                <table class="table table-sm bg-white rounded overflow-hidden mb-0">
                                    <thead><tr><th>الحقل</th><th>القيمة القديمة</th><th>القيمة الجديدة</th></tr></thead>
                                    <tbody>
                                        @foreach($activeEditRequest->changed_values as $field => $change)
                                            <tr>
                                                <td>{{ $field }}</td>
                                                <td>{{ is_array($change['old'] ?? null) ? json_encode($change['old'], JSON_UNESCAPED_UNICODE) : ($change['old'] ?? '-') }}</td>
                                                <td>{{ is_array($change['new'] ?? null) ? json_encode($change['new'], JSON_UNESCAPED_UNICODE) : ($change['new'] ?? '-') }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if(($archivedVersions ?? collect())->isNotEmpty())
            <div class="card event-card mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">أرشيف الإصدارات السابقة</h2>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($archivedVersions as $archived)
                            <a class="badge bg-light text-dark border text-decoration-none"
                               href="{{ route('role.relations.activities.show', $archived) }}">
                                نسخة {{ (int) ($archived->plan_version ?: 1) }} — {{ $archived->title }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="workflow-ui mb-4">
            <div class="wf-card card">
                <div class="card-body">
                    <div class="wf-summary">
                        <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                            <div>
                                <h2 class="h6 mb-1">مسار الاعتماد</h2>
                                <div class="wf-kv">
                                    {{ __('workflow_ui.common.submitted_by') }}: {{ $workflowSummary['submitted_by_name'] ?? '-' }}
                                    @if(!empty($workflowSummary['submitted_at']))
                                        | {{ __('workflow_ui.common.submitted_at') }}: {{ $workflowSummary['submitted_at'] }}
                                    @endif
                                </div>
                            </div>
                            <span class="wf-status-badge wf-status-{{ $workflowSummary['status_key'] ?? 'draft' }}">
                                {{ $workflowSummary['status_label'] ?? $statusLabel($monthlyActivity->status) }}
                            </span>
                        </div>

                        <div class="wf-chip-row mt-3">
                            <span class="wf-chip wf-chip-primary">{{ __('workflow_ui.common.current_step') }}: {{ $workflowSummary['current_step_label'] ?? '-' }}</span>
                            <span class="wf-chip wf-chip-soft">التقدم: {{ $workflowSummary['completed_steps_count'] ?? 0 }}/{{ $workflowSummary['total_steps_count'] ?? 0 }}</span>
                        </div>
                    </div>

                    <details class="wf-advanced-box mt-3">
                        <summary>عرض حالات الاعتماد</summary>
                        <div class="row g-3 mt-1">
                            <div class="col-lg-7">
                                <div class="d-flex flex-column gap-2">
                                    @forelse($workflowSummary['steps'] ?? [] as $step)
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
                                                <span class="wf-status-badge wf-status-{{ $step['state'] }}">{{ $step['state_label'] }}</span>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="wf-kv">لا توجد خطوات اعتماد معروضة حالياً.</div>
                                    @endforelse
                                </div>
                            </div>
                            <div class="col-lg-5">
                                <div class="border rounded-3 p-3 mb-3">
                                    <h3 class="h6 mb-2">{{ __('workflow_ui.approvals.change_request_title') }}</h3>
                                    @if(!empty($workflowSummary['latest_change_request']))
                                        <div class="wf-kv">{{ __('workflow_ui.approvals.requested_by') }}: {{ $workflowSummary['latest_change_request']['actor_name'] }}</div>
                                        <div class="wf-kv">{{ __('workflow_ui.approvals.requested_at') }}: {{ $workflowSummary['latest_change_request']['acted_at'] ?? '-' }}</div>
                                        <div class="wf-kv">{{ __('workflow_ui.common.current_step') }}: {{ $workflowSummary['latest_change_request']['step_label'] }}</div>
                                        <div class="wf-kv">{{ __('workflow_ui.common.assignee') }}: {{ $workflowSummary['latest_change_request']['role_label'] }}</div>
                                        <div class="wf-kv mt-2">{{ $workflowSummary['latest_change_request']['comment'] ?: '-' }}</div>
                                    @else
                                        <div class="wf-kv">{{ __('workflow_ui.approvals.change_request_empty') }}</div>
                                    @endif
                                </div>

                                <details class="wf-advanced-box">
                                    <summary>{{ __('workflow_ui.approvals.workflow_history') }}</summary>
                                    <div class="d-flex flex-column gap-2 mt-3">
                                        @forelse($workflowSummary['timeline'] ?? [] as $entry)
                                            <div class="border rounded-3 p-3">
                                                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                                    <div>
                                                        <div class="fw-semibold">{{ $entry['step_label'] }}</div>
                                                        <div class="wf-kv">{{ $entry['role_label'] }}</div>
                                                        <div class="wf-kv">{{ $entry['actor_name'] }} | {{ $entry['acted_at'] ?? '-' }}</div>
                                                        <div class="wf-kv">{{ $entry['comment'] ?: '-' }}</div>
                                                    </div>
                                                    <span class="wf-status-badge wf-status-{{ $entry['action'] }}">{{ $entry['action_label'] }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="wf-kv">{{ __('workflow_ui.approvals.timeline.empty') }}</div>
                                        @endforelse
                                    </div>
                                </details>
                            </div>
                        </div>
                    </details>
                </div>
            </div>
        </div>

        <div class="card event-card monthly-show-details-card">
            <div class="card-body">
                <div class="monthly-summary-grid mb-4">
                    <div class="monthly-summary-item"><span>الحالة</span><strong>{{ $statusLabel($monthlyActivity->status) }}</strong></div>
                    <div class="monthly-summary-item"><span>حالة التنفيذ</span><strong>{{ $executionLabel($displayExecutionStatus) }}</strong></div>
                    <div class="monthly-summary-item"><span>التاريخ</span><strong>{{ sprintf('%02d/%02d', $monthlyActivity->day, $monthlyActivity->month) }}</strong></div>
                    <div class="monthly-summary-item"><span>الفرع</span><strong>{{ $monthlyActivity->branch?->name ?? '-' }}</strong></div>
                </div>

                <details class="monthly-full-details" open>
                    <summary><i class="feather-layers me-1"></i>عرض التفاصيل الكاملة</summary>
                    <div class="row g-3 mt-2 monthly-details-content">
                    <div class="col-12 col-md-4"><strong>عنوان النشاط:</strong> {{ $monthlyActivity->title }}</div>
                    <div class="col-12 col-md-4"><strong>تاريخ النشاط:</strong> {{ sprintf('%02d/%02d', $monthlyActivity->day, $monthlyActivity->month) }}</div>
                    <div class="col-12 col-md-4"><strong>التاريخ المقترح:</strong> {{ optional($monthlyActivity->proposed_date)->format('d/m/Y') ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الحالة:</strong> {{ $statusLabel($monthlyActivity->status) }}</div>
                    <div class="col-12 col-md-4"><strong>حالة التنفيذ:</strong> {{ $executionLabel($displayExecutionStatus) }}</div>
                    <div class="col-12 col-md-4"><strong>الفرع:</strong> {{ $monthlyActivity->branch?->name ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>مرتبط بفعالية أجندة:</strong> {{ $monthlyActivity->agendaEvent?->event_name ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>ضمن الأجندة السنوية:</strong> {{ $monthlyActivity->is_in_agenda ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>مصدر النشاط:</strong> {{ $monthlyActivity->is_from_agenda ? 'من الأجندة' : 'إدخال يدوي' }}</div>
                    <div class="col-12 col-md-4"><strong>نوع الخطة:</strong> {{ $monthlyActivity->plan_type ?? '-' }}</div>
                    @if ($monthlyActivity->execution_status === 'postponed')
                        <div class="col-12 col-md-4"><strong>تاريخ التأجيل:</strong> {{ optional($monthlyActivity->rescheduled_date)->format('d/m/Y') ?? '-' }}</div>
                        <div class="col-12 col-md-8"><strong>سبب التأجيل:</strong> {{ $monthlyActivity->reschedule_reason ?? '-' }}</div>
                    @endif
                    @if ($monthlyActivity->execution_status === 'cancelled')
                        <div class="col-12"><strong>سبب الإلغاء:</strong> {{ $monthlyActivity->cancellation_reason ?? '-' }}</div>
                    @endif

                    @php
                        $enabledExecutionNeeds = $monthlyActivity->enabledExecutionNeeds();
                        $executionNeedsFollowupByKey = collect($monthlyActivity->execution_needs_followup ?? [])
                            ->filter(fn ($row) => is_array($row) && filled($row['key'] ?? null))
                            ->keyBy(fn ($row) => (string) $row['key']);
                        $executionNeedStatusLabel = fn (?string $status) => match ($status) {
                            'secured' => 'سيتم التأمين',
                            'not_secured' => 'لن يتم التأمين',
                            default => 'بانتظار الرد',
                        };
                        $executionNeedStatusClass = fn (?string $status) => match ($status) {
                            'secured' => 'bg-success',
                            'not_secured' => 'bg-danger',
                            default => 'bg-secondary',
                        };
                    @endphp
                    <div class="col-12" id="execution-needs-summary"><strong>احتياجات التنفيذ المفعلة:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0 d-flex flex-column gap-2">
                            @forelse($enabledExecutionNeeds as $needKey => $need)
                                @php
                                    $needDecision = $executionNeedsFollowupByKey->get($needKey, []);
                                @endphp
                                <li>
                                    <div class="d-flex gap-2 align-items-center flex-wrap">
                                        <span>{{ $need['label'] }}</span>
                                        <span class="badge {{ $executionNeedStatusClass($needDecision['status'] ?? null) }}">
                                            {{ $executionNeedStatusLabel($needDecision['status'] ?? null) }}
                                        </span>
                                    </div>
                                    @if(!empty($needDecision['decision_by_name']) || !empty($needDecision['decision_by_role']))
                                        <div class="small text-muted mt-1">
                                            تم الرد بواسطة: {{ $needDecision['decision_by_name'] ?? '-' }}
                                            @if(!empty($needDecision['decision_by_role']))
                                                / {{ $needDecision['decision_by_role'] }}
                                            @endif
                                        </div>
                                    @endif
                                    @if(!empty($needDecision['reason']) || !empty($needDecision['notes']))
                                        <div class="small text-muted mt-1">
                                            سبب عدم التأمين: {{ $needDecision['reason'] ?? $needDecision['notes'] }}
                                        </div>
                                    @endif
                                </li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    @php
                        $needPostStatusLabels = ['provided' => 'تم توفيره', 'not_provided' => 'لم يتم توفيره'];
                        $postExecutionPayload = $monthlyActivity->post_execution_payload ?? [];
                    @endphp
                    @if(collect($monthlyActivity->execution_needs_followup ?? [])->contains(fn ($row) => filled($row['post_status'] ?? null) || filled($row['post_feedback'] ?? null)))
                        <div class="col-12"><strong>متابعة الاحتياجات بعد التنفيذ:</strong></div>
                        <div class="col-12">
                            <ul class="mb-0">
                                @foreach($monthlyActivity->execution_needs_followup ?? [] as $row)
                                    @continue(! filled($row['post_status'] ?? null) && ! filled($row['post_feedback'] ?? null))
                                    @php
                                        $needLabel = data_get($monthlyActivity->enabledExecutionNeeds(), ($row['key'] ?? '').'.label', $row['key'] ?? '-');
                                    @endphp
                                    <li>{{ $needLabel }} - {{ $needPostStatusLabels[$row['post_status'] ?? ''] ?? '-' }}{{ filled($row['post_feedback'] ?? null) ? ' / '.$row['post_feedback'] : '' }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(! empty($postExecutionPayload['teams'] ?? []))
                        <div class="col-12"><strong>متابعة الفرق بعد التنفيذ:</strong></div>
                        <div class="col-12">
                            <ul class="mb-0">
                                @foreach($postExecutionPayload['teams'] as $teamRow)
                                    <li>
                                        {{ $teamRow['team_name'] ?? '-' }}
                                        - الحضور الفعلي: {{ $teamRow['actual_attendance_count'] ?? '-' }} / {{ $teamRow['planned_members_count'] ?? '-' }}
                                        - حضور الجميع: {{ ($teamRow['all_members_attended'] ?? null) === true ? 'نعم' : (($teamRow['all_members_attended'] ?? null) === false ? 'لا' : '-') }}
                                        {{ filled($teamRow['accomplished_tasks'] ?? null) ? ' / '.$teamRow['accomplished_tasks'] : '' }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(! empty($postExecutionPayload['ceremony_items'] ?? []))
                        <div class="col-12"><strong>متابعة فقرات الفعالية:</strong></div>
                        <div class="col-12">
                            <ul class="mb-0">
                                @foreach($postExecutionPayload['ceremony_items'] as $itemRow)
                                    <li>
                                        {{ $itemRow['order'] ?? '-' }}. {{ $itemRow['name'] ?? '-' }}
                                        - {{ ($itemRow['was_implemented'] ?? null) === true ? 'تم تطبيقها' : (($itemRow['was_implemented'] ?? null) === false ? 'لم تطبق' : '-') }}
                                        {{ filled($itemRow['feedback'] ?? null) ? ' / '.$itemRow['feedback'] : '' }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>نوع المكان:</strong> {{ $monthlyActivity->location_type === 'outside_center' ? 'خارج المركز' : 'داخل المركز' }}</div>
                    <div class="col-12 col-md-4"><strong>القاعة الداخلية:</strong> {{ $monthlyActivity->internal_location ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>اسم المكان الخارجي:</strong> {{ $monthlyActivity->outside_place_name ?? '-' }}</div>
                    <div class="col-12 col-md-4">
                        <strong>رابط الموقع:</strong>
                        @if($outsideMapNavigationUrl)
                            <a href="{{ $outsideMapNavigationUrl }}" target="_blank" rel="noopener noreferrer">التنقل عبر Google Maps</a>
                        @else
                            -
                        @endif
                    </div>
                    <div class="col-12 col-md-4"><strong>رقم تواصل المكان:</strong> {{ $monthlyActivity->outside_contact_number ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>اسم ضابط الارتباط:</strong> {{ $monthlyActivity->external_liaison_name ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>رقم ضابط الارتباط:</strong> {{ $monthlyActivity->external_liaison_phone ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>العنوان التفصيلي:</strong> {{ $monthlyActivity->outside_address ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>من - إلى:</strong> {{ $monthlyActivity->time_from ?? '-' }} / {{ $monthlyActivity->time_to ?? '-' }}</div>
                    @if($monthlyActivity->location_type === 'outside_center')
                        @include('pages.monthly_activities.activities.partials.google-map-preview', [
                            'mapUrl' => $monthlyActivity->outside_google_maps_url,
                            'mapPlaceName' => $monthlyActivity->outside_place_name,
                            'mapAddress' => $monthlyActivity->outside_address,
                        ])
                    @endif

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>الوصف المختصر:</strong> {{ $monthlyActivity->short_description ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الوصف التفصيلي:</strong> {{ $monthlyActivity->description ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الفئة المستهدفة (نص):</strong> {{ $monthlyActivity->target_group ?? '-' }}</div>
                    <div class="col-12"><strong>الفئات المستهدفة (قائمة):</strong>
                        @forelse($monthlyActivity->targetGroups as $group)
                            <span class="badge bg-light text-dark border">{{ $group->name }}</span>
                        @empty
                            -
                        @endforelse
                    </div>
                    <div class="col-12 col-md-4"><strong>فئة أخرى:</strong> {{ $monthlyActivity->target_group_other ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>عدد الحضور المتوقع:</strong> {{ $monthlyActivity->expected_attendance_range_label }}</div>
                    <div class="col-12 col-md-4"><strong>عدد الحضور الفعلي:</strong> {{ $monthlyActivity->actual_attendance ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>ملاحظات الحضور:</strong> {{ $monthlyActivity->attendance_notes ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>بحاجة لمتطوعين:</strong> <span class="badge {{ $monthlyActivity->needs_volunteers ? 'bg-success' : 'bg-secondary' }}">{{ $monthlyActivity->needs_volunteers ? '✅ نعم' : '❌ لا' }}</span></div>
                    <div class="col-12 col-md-4"><strong>توفر المتطوعين داخل المركز:</strong> {{ $needAvailabilityLabel('volunteers') }}</div>
                    <div class="col-12 col-md-4"><strong>عدد المتطوعين المطلوب:</strong> {{ $monthlyActivity->required_volunteers ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>احتياج المتطوعين (نصي):</strong> {{ $monthlyActivity->volunteer_need ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الفترة العمرية:</strong> {{ $monthlyActivity->volunteer_age_range ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الجنس:</strong> {{ $monthlyActivity->volunteer_gender ?? '-' }}</div>
                    <div class="col-12"><strong>وصف المهام:</strong> {{ $monthlyActivity->volunteer_tasks_summary ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>بحاجة مخاطبات رسمية:</strong> <span class="badge {{ $monthlyActivity->needs_official_correspondence ? 'bg-success' : 'bg-secondary' }}">{{ $monthlyActivity->needs_official_correspondence ? '✅ نعم' : '❌ لا' }}</span></div>
                    <div class="col-12 col-md-4"><strong>سبب المخاطبة:</strong> {{ $monthlyActivity->official_correspondence_reason ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الجهة المطلوب مخاطبتها:</strong> {{ $monthlyActivity->official_correspondence_target ?? '-' }}</div>
                    <div class="col-12"><strong>بريف المخاطبة:</strong> {{ $monthlyActivity->official_correspondence_brief ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>بحاجة خطابات:</strong> <span class="badge {{ $monthlyActivity->needs_official_letters ? 'bg-success' : 'bg-secondary' }}">{{ $monthlyActivity->needs_official_letters ? '✅ نعم' : '❌ لا' }}</span></div>
                    <div class="col-12 col-md-4"><strong>سبب الخطابات:</strong> {{ $monthlyActivity->letter_purpose ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>تغطية إعلامية:</strong> <span class="badge {{ $monthlyActivity->needs_media_coverage ? 'bg-success' : 'bg-secondary' }}">{{ $monthlyActivity->needs_media_coverage ? '✅ نعم' : '❌ لا' }}</span></div>
                    <div class="col-12 col-md-4"><strong>توفر التغطية داخل المركز:</strong> {{ $needAvailabilityLabel('media_coverage') }}</div>
                    <div class="col-12 col-md-8"><strong>ملاحظات التغطية الإعلامية:</strong> {{ $monthlyActivity->media_coverage_notes ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>يوجد راعي رسمي:</strong> {{ $monthlyActivity->has_sponsor ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>يوجد شركاء:</strong> {{ $monthlyActivity->has_partners ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>توفر الرعاية داخل المركز:</strong> {{ $needAvailabilityLabel('official_sponsorship') }}</div>
                    <div class="col-12 col-md-4"><strong>توفر الشركاء داخل المركز:</strong> {{ $needAvailabilityLabel('external_partners') }}</div>
                    <div class="col-12 col-md-4"><strong>كيان مسؤول:</strong> {{ $monthlyActivity->responsible_party ?? '-' }}</div>

                    <div class="col-12"><strong>الرعاة:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0">
                            @forelse($monthlyActivity->sponsors as $sponsor)
                                <li>{{ $sponsor->name }} - {{ $sponsor->title ?? '-' }} ({{ $sponsor->is_official ? 'رسمي' : 'غير رسمي' }})</li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-12"><strong>الشركاء:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0">
                            @forelse($monthlyActivity->partners as $partner)
                                <li>{{ $partner->name }} - {{ $partner->role ?? '-' }} {{ $partner->contact_info ? ' / '.$partner->contact_info : '' }}</li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-12"><strong>المستلزمات:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0">
                            @forelse($monthlyActivity->supplies as $supply)
                                <li>{{ $supply->item_name }} - {{ $supply->available ? 'متوفر' : 'غير متوفر' }} {{ $supply->provider_name ? ' / ' . $supply->provider_name : '' }}</li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-12"><strong>فريق العمل:</strong></div>
                    <div class="col-12">
                        <ul class="mb-0">
                            @forelse($monthlyActivity->team as $member)
                                <li>{{ $member->team_name ?? '-' }} - {{ $member->member_name }} - {{ $member->role_desc ?? '-' }}</li>
                            @empty
                                <li>-</li>
                            @endforelse
                        </ul>
                    </div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>حالة المشاركة:</strong> {{ $monthlyActivity->participation_status ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>تحويل للبرامج:</strong> {{ $monthlyActivity->requires_programs ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>تحويل للمشاغل:</strong> {{ $monthlyActivity->requires_workshops ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>تحويل للعلاقات:</strong> {{ $monthlyActivity->requires_communications ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>نشاط مرتبط بالبرامج:</strong> {{ $monthlyActivity->is_program_related ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>أجندة الحفل (إن وجدت):</strong>
                        @if($monthlyActivity->planning_attachment)
                            <a href="{{ asset('storage/' . $monthlyActivity->planning_attachment) }}" target="_blank">عرض الملف</a>
                        @else
                            -
                        @endif
                    </div>
                    <div class="col-12">
                        <div class="alert alert-light border mb-0">
                            <div class="fw-semibold mb-2">المخاطبة الرسمية المعتمدة</div>
                            <div class="small text-muted mb-2">أي مرفق يتم تحميله من اعتماد علاقات خلدا يظهر هنا مباشرة لموظف العلاقات في الفرع.</div>
                            <div class="d-flex flex-column gap-2">
                                @forelse($officialCorrespondenceAttachments as $attachment)
                                    @php
                                        $isExternal = filter_var($attachment->file_path, FILTER_VALIDATE_URL);
                                    @endphp
                                    <a class="btn btn-sm btn-outline-primary text-start" href="{{ $isExternal ? $attachment->file_path : route('role.programs.attachments.download', $attachment) }}" target="_blank" rel="noopener">
                                        {{ $attachment->title ?: 'عرض المخاطبة الرسمية' }}
                                    </a>
                                    <div class="small text-muted">
                                        {{ $attachment->uploader?->name ? 'تم الرفع بواسطة: ' . $attachment->uploader->name : 'تم رفع المرفق' }}
                                    </div>
                                @empty
                                    <div class="small text-muted">لا يوجد مرفق مخاطبة رسمية مرفوع حتى الآن.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>
                </details>
            </div>
        </div>
    </div>
    @include('pages.monthly_activities.activities.partials.delete-reason-modal')
@endsection
