@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.approvals.title');
    $subtitle = __('app.roles.programs.monthly_activities.approvals.subtitle');
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
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.status') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.relations_lane') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.programs_lane') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.executive_approval') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.approvals.table.last_decision') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.approvals.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            @php
                                $latestApproval = $activity->approvals->last();
                            @endphp
                            <tr>
                                <td>{{ $activity->title }}</td>
                                <td>{{ sprintf('%02d-%02d', $activity->month, $activity->day) }}</td>
                                <td><span class="event-status status-{{ $activity->status }}">{{ $activity->status }}</span></td>
                                <td>{{ $activity->relations_officer_approval_status }} / {{ $activity->relations_manager_approval_status }}</td>
                                <td>{{ $activity->programs_officer_approval_status }} / {{ $activity->programs_manager_approval_status }}</td>
                                <td>{{ $activity->executive_approval_status }}</td>
                                <td>
                                    {{ $latestApproval?->decision ?? __('app.roles.programs.monthly_activities.approvals.table.none') }}
                                    @php
                                        $changeRequests = $activity->approvals->where('decision', 'changes_requested');
                                        $changeRequestCounts = $changeRequests->groupBy('step')->map->count();
                                    @endphp
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
                                                @foreach($stepLabels as $stepKey => $stepLabel)
                                                    <span class="me-2">{{ $stepLabel }}: {{ $changeRequestCounts->get($stepKey, 0) }}</span>
                                                @endforeach
                                            </div>
                                            <ul class="mb-0 ps-3">
                                                @foreach($changeRequests as $requestItem)
                                                    <li>
                                                        <span class="fw-semibold">{{ $stepLabels[$requestItem->step] ?? $requestItem->step }}</span> -
                                                        {{ optional($requestItem->approved_at)->format('Y-m-d H:i') }} -
                                                        {{ $requestItem->comment ?? 'بدون ملاحظات' }}
                                                        @if($requestItem->is_edit_request_implemented)
                                                            <span class="text-success">(تم تنفيذ التعديل)</span>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <form method="POST" action="{{ route('role.programs.approvals.update', $activity) }}" class="row g-3">
                                        @csrf
                                        @method('PUT')
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
