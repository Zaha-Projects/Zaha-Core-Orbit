@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.approvals.title');
    $subtitle = __('app.roles.programs.monthly_activities.approvals.subtitle');
    $viewer = auth()->user();
    $isNotesOnlyRole = $viewer?->hasRole('workshops_secretary') || $viewer?->hasRole('communication_head');
@endphp


@section('content')
    <div class="event-module">
    <div class="card event-card mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card event-card">
        <div class="card-body">
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.title') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.date') }}</th>
                            <th>Branch / HQ</th>
                            <th>Dynamic Flags</th>
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.status') }}</th>
                            <th>Workflow / سير الاعتماد</th>
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.last_decision') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.approvals.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            @php
                                $latestApproval = $activity->approvals->last();
                                $changeRequests = $activity->approvals->where('decision', 'changes_requested');
                                $changeRequestCounts = $changeRequests->groupBy('step')->map->count();
                                $changeRequestByUser = $changeRequests
                                    ->groupBy('approved_by')
                                    ->map(function ($items) {
                                        $name = optional(optional($items->first())->approver)->name ?? 'غير معروف';

                                        return [
                                            'name' => $name,
                                            'count' => $items->count(),
                                        ];
                                    })
                                    ->values();
                            @endphp
                            <tr>
                                <td>{{ $activity->title }}</td>
                                <td>{{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</td>
                                <td>{{ optional($activity->branch)->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark">Programs: {{ $activity->requires_programs ? 'Yes' : 'No' }}</span>
                                    <span class="badge bg-light text-dark">Workshops: {{ $activity->requires_workshops ? 'Yes' : 'No' }}</span>
                                    <span class="badge bg-light text-dark">Comms: {{ $activity->requires_communications ? 'Yes' : 'No' }}</span>
                                </td>
                                <td><span class="event-status status-{{ $activity->status }}">{{ $activity->status }}</span></td>
                                <td>
                                    @php($wf = $activity->workflowInstance)
                                    @if($wf)
                                        <div class="small">
                                            <strong>Current Stage / المرحلة الحالية:</strong> {{ $wf->currentStep?->name_ar ?? $wf->currentStep?->name_en ?? '-' }}
                                        </div>
                                        <div class="small">
                                            <strong>Status / الحالة:</strong> {{ $wf->status === 'in_progress' ? 'قيد المراجعة / In Review' : ($wf->status === 'approved' ? 'تم الاعتماد / Approved' : ($wf->status === 'changes_requested' ? 'مطلوب تعديل / Changes Requested' : $wf->status)) }}
                                        </div>
                                        <div class="small">
                                            <strong>Current Responsible / المسؤول الحالي:</strong> {{ $wf->currentStep?->role?->name ?? $wf->currentStep?->permission?->name ?? '-' }}
                                        </div>
                                        <div class="small text-warning">
                                            <strong>Edit Requests / عدد طلبات التعديل:</strong> {{ $wf->edit_request_count }}
                                        </div>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $latestApproval?->decision ?? __('app.roles.programs.monthly_activities.approvals.table.none') }}
                                    @if($changeRequests->isNotEmpty())
                                        <div class="small text-warning">طلبات تعديل: {{ $changeRequests->count() }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#approval-{{ $activity->id }}">
                                        {{ __('app.roles.programs.monthly_activities.approvals.actions.review') }}
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="approval-{{ $activity->id }}">
                                <td colspan="8">
                                    @if($changeRequests->isNotEmpty())
                                        <div class="alert alert-warning py-2">
                                            <div class="fw-semibold mb-1">سجل طلبات التعديل</div>
                                            <div class="small mb-2">
                                                <span class="me-2">إجمالي طلبات التعديل: {{ $changeRequests->count() }}</span>
                                            </div>
                                            <div class="small">
                                                @foreach($changeRequestByUser as $requester)
                                                    <span class="me-2">{{ $requester['name'] }}: {{ $requester['count'] }}</span>
                                                @endforeach
                                            </div>
                                            <div class="table-responsive mt-2">
                                                <table class="table table-sm mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>المرحلة</th>
                                                            <th>طالب التعديل</th>
                                                            <th>التعليق</th>
                                                            <th>التاريخ</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($changeRequests as $changeRequest)
                                                            <tr>
                                                                <td>{{ $changeRequest->step }}</td>
                                                                <td>{{ optional($changeRequest->approver)->name ?? '-' }}</td>
                                                                <td>{{ $changeRequest->comment ?: '-' }}</td>
                                                                <td>{{ $changeRequest->approved_at?->format('Y-m-d H:i') ?: '-' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="mb-3">
                                        <div class="fw-semibold mb-2">ملاحظات المشاغل والاتصالات</div>
                                        @forelse($activity->notes as $note)
                                            <div class="border rounded p-2 mb-2">
                                                <div class="small text-muted">{{ $note->role }} - {{ optional($note->user)->name }} - {{ $note->created_at?->format('Y-m-d H:i') }}</div>
                                                <div>{{ $note->note }}</div>
                                                @if($note->coverage_status)
                                                    <div class="small mt-1">Coverage: {{ $note->coverage_status }}</div>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="text-muted small">لا توجد ملاحظات حتى الآن.</div>
                                        @endforelse
                                    </div>


                                    <div class="mb-3"> 
                                        <div class="fw-semibold mb-2">Workflow Timeline / تسلسل الاعتماد</div>
                                        <div class="table-responsive"> 
                                            <table class="table table-sm mb-0"> 
                                                <thead><tr><th>الخطوة / Step</th><th>القرار / Decision</th><th>المسؤول / Actor</th><th>الوقت / Time</th><th>ملاحظة / Comment</th></tr></thead>
                                                <tbody>
                                                    @forelse(($activity->workflowInstance?->logs ?? collect()) as $wfLog)
                                                        <tr>
                                                            <td>{{ $wfLog->step?->name_ar ?? $wfLog->step?->name_en ?? $wfLog->step?->step_key ?? '-' }}</td>
                                                            <td>{{ $wfLog->action }}</td>
                                                            <td>{{ $wfLog->actor?->name ?? '-' }}</td>
                                                            <td>{{ $wfLog->acted_at?->format('Y-m-d H:i') ?? '-' }}</td>
                                                            <td>{{ $wfLog->comment ?? '-' }}</td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="5" class="text-muted">لا توجد حركات Workflow بعد.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <form method="POST" action="{{ route('role.programs.approvals.update', $activity) }}" class="row g-3">
                                        @csrf
                                        @method('PUT')

                                        @if($isNotesOnlyRole)
                                            <div class="col-12 col-md-8">
                                                <label class="form-label">الملاحظة</label>
                                                <input class="form-control" name="note" required>
                                            </div>
                                            @if($viewer?->hasRole('communication_head'))
                                                <div class="col-12 col-md-4">
                                                    <label class="form-label">حالة التغطية</label>
                                                    <select class="form-select" name="coverage_status">
                                                        <option value="not_required">not_required</option>
                                                        <option value="planned">planned</option>
                                                        <option value="in_progress">in_progress</option>
                                                        <option value="completed">completed</option>
                                                    </select>
                                                </div>
                                            @endif
                                        @else
                                            <div class="col-12 col-md-4">
                                                <label class="form-label">{{ __('app.roles.programs.monthly_activities.approvals.fields.decision') }}</label>
                                                <select class="form-select" name="decision" required>
                                                    <option value="approved">{{ __('app.roles.programs.monthly_activities.approvals.decisions.approved') }}</option>
                                                    <option value="changes_requested">{{ __('app.roles.programs.monthly_activities.approvals.decisions.changes_requested') }}</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label">{{ __('app.roles.programs.monthly_activities.approvals.fields.comment') }}</label>
                                                <input class="form-control" name="comment">
                                            </div>
                                            <div class="col-12 col-md-2 d-flex align-items-center">
                                                <div class="form-check mt-4">
                                                    <input class="form-check-input" type="checkbox" name="is_edit_request_implemented" value="1" id="implemented-{{ $activity->id }}">
                                                    <label class="form-check-label" for="implemented-{{ $activity->id }}">تم تنفيذ التعديل</label>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="col-12 d-flex justify-content-end">
                                            <button class="btn btn-outline-primary btn-sm" type="submit">
                                                {{ __('app.roles.programs.monthly_activities.approvals.actions.submit') }}
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-muted">{{ __('app.roles.programs.monthly_activities.approvals.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>
@endsection
