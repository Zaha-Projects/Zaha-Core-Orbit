@extends('layouts.app')
@section('content')
@php
$planningLabel = __('ramadan_iftars.statuses.planning.'.$ramadanIftar->status);
$executionLabel = __('ramadan_iftars.statuses.execution.'.$ramadanIftar->execution_status);
$latestMonitoring = $ramadanIftar->monitoringReports->sortByDesc('updated_at')->first();
@endphp
<div class="container py-4">
    <div class="card shadow-sm mb-4"><div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div><h1 class="h3 mb-2">{{ $ramadanIftar->title }}</h1><div class="d-flex flex-wrap gap-2">
                <span class="badge bg-secondary">{{ $planningLabel }}</span>
                <span class="badge bg-info text-dark">{{ $executionLabel }}</span>
                <span class="badge {{ $ramadanIftar->closed_at ? 'bg-dark' : 'bg-light text-dark' }}">{{ __('ramadan_iftars.statuses.closure.'.($ramadanIftar->closed_at ? 'closed' : 'open')) }}</span>
                @if($latestMonitoring)<span class="badge bg-primary">{{ __('ramadan_iftars.statuses.monitoring.'.$latestMonitoring->status) }}</span>@endif
            </div></div>
            <div class="d-flex flex-wrap gap-2">
                @if($canPlan)<a class="btn btn-outline-primary" href="{{ route('events.ramadan.iftars.edit', $ramadanIftar) }}">{{ __('ramadan_iftars.actions.edit') }}</a>@endif
                @if($canSubmit)<form method="POST" action="{{ route('events.ramadan.iftars.submit', $ramadanIftar) }}">@csrf<button class="btn btn-primary">{{ __('ramadan_iftars.actions.'.($ramadanIftar->status === 'changes_requested' ? 'resubmit' : 'submit')) }}</button></form>@endif
                @if($canCurrentUserApprove)<a class="btn btn-warning" href="{{ route('events.ramadan.approvals.show', $ramadanIftar) }}">{{ __('ramadan_iftars.actions.review_approval') }}</a>@endif
                @if($canExecute)<a class="btn btn-success" href="{{ route('events.ramadan.iftars.execution.show', $ramadanIftar) }}">{{ __('ramadan_iftars.actions.'.($ramadanIftar->execution_status === 'planned' ? 'start_execution' : 'view_execution')) }}</a>@endif
                @if($canCompleteExecution)<form method="POST" action="{{ route('events.ramadan.iftars.execution.complete', $ramadanIftar) }}">@csrf<button class="btn btn-outline-success">{{ __('ramadan_iftars.actions.complete_execution') }}</button></form>@endif
                @if($canMonitor)<a class="btn btn-dark" href="{{ route('events.ramadan.iftars.monitoring.index', $ramadanIftar) }}">{{ __('ramadan_iftars.actions.monitoring') }}</a>@endif
                @if($canReviewMonitoring)<a class="btn btn-outline-dark" href="{{ route('events.ramadan.monitoring-reviews.index') }}">{{ __('ramadan_iftars.actions.review_monitoring') }}</a>@endif
            </div>
        </div>
        <hr><div class="row g-3"><div class="col-6 col-lg-3"><small class="text-muted">{{ __('ramadan_iftars.fields.branch') }}</small><div>{{ optional($ramadanIftar->branch)->name ?: '—' }}</div></div><div class="col-6 col-lg-3"><small class="text-muted">{{ __('ramadan_iftars.fields.planned_date') }}</small><div>{{ optional($ramadanIftar->planned_date)->format('Y-m-d') ?: '—' }}</div></div><div class="col-6 col-lg-3"><small class="text-muted">{{ __('ramadan_iftars.fields.actual_date') }}</small><div>{{ optional($ramadanIftar->actual_date)->format('Y-m-d') ?: '—' }}</div></div><div class="col-6 col-lg-3"><small class="text-muted">{{ __('ramadan_iftars.fields.current_step') }}</small><div>{{ optional(optional($ramadanIftar->workflowInstance)->currentStep)->name_en ?: '—' }}</div></div></div>
    </div></div>

    <div class="row g-3">
        @foreach([
            ['meals', $ramadanIftar->meals, 'empty.meals'],
            ['gifts', $ramadanIftar->gifts, 'empty.gifts'],
            ['programs', $ramadanIftar->programSegments, 'empty.programs'],
            ['teams', $ramadanIftar->executionTeams, 'empty.teams'],
            ['volunteers', $ramadanIftar->volunteerRequirements, 'empty.volunteers'],
            ['supplies', $ramadanIftar->supplies, 'empty.supplies']
        ] as [$section,$rows,$empty])
        <div class="col-lg-6"><div class="card h-100"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.'.$section) }}</div><div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>{{ __('ramadan_iftars.fields.title') }}</th><th>{{ __('ramadan_iftars.fields.expected') }}</th><th>{{ __('ramadan_iftars.fields.actual') }}</th></tr></thead><tbody>
        @forelse($rows as $row)<tr><td>{{ $row->description ?? $row->name ?? $row->item_name ?? optional($row->beneficiarySegment)->name_en ?? '#'.$row->id }}</td><td>{{ $row->planned_quantity ?? $row->planned_members_count ?? $row->planned_count ?? '—' }}</td><td>{{ $row->actual_quantity ?? $row->actual_members_count ?? $row->actual_count ?? $row->execution_status ?? '—' }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">{{ __('ramadan_iftars.'.$empty) }}</td></tr>@endforelse
        </tbody></table></div></div></div>
        @endforeach
        <div class="col-12"><div class="card"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.execution_needs') }}</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('ramadan_iftars.fields.title') }}</th><th>{{ __('ramadan_iftars.fields.expected') }}</th><th>{{ __('ramadan_iftars.fields.actual') }}</th><th>{{ __('ramadan_iftars.fields.execution_status') }}</th></tr></thead><tbody>@forelse($ramadanIftar->executionNeeds as $row)<tr><td>{{ optional($row->executionNeedType)->name }} <span class="badge bg-light text-dark">{{ __('ramadan_iftars.statuses.requirement.'.($row->is_required ? 'required' : 'not_required')) }}</span></td><td>{{ $row->planned_details ?: '—' }}</td><td>{{ $row->actual_details ?: '—' }}</td><td>{{ __('ramadan_iftars.statuses.execution.'.$row->status) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">—</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-12"><div class="card"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.workflow') }}</div><ul class="list-group list-group-flush">@forelse(optional($ramadanIftar->workflowInstance)->logs ?? [] as $log)<li class="list-group-item">{{ optional($log->step)->name_en }} · {{ optional($log->actor)->name }} · {{ $log->comment }}</li>@empty<li class="list-group-item text-muted">{{ __('ramadan_iftars.empty.workflow') }}</li>@endforelse</ul></div></div>
        <div class="col-12"><div class="card"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.monitoring') }}</div><ul class="list-group list-group-flush">@forelse($ramadanIftar->monitoringReports as $report)<li class="list-group-item d-flex justify-content-between"><span>@if($canMonitor)<a href="{{ route('events.ramadan.iftars.monitoring.edit', [$ramadanIftar,$report]) }}">{{ optional($report->monitoringMethod)->name_en ?: optional($report->monitoringMethod)->name_ar }}</a>@else{{ optional($report->monitoringMethod)->name_en ?: optional($report->monitoringMethod)->name_ar }}@endif</span><span class="badge bg-secondary">{{ __('ramadan_iftars.statuses.monitoring.'.$report->status) }}</span></li>@empty<li class="list-group-item text-muted">{{ __('ramadan_iftars.empty.monitoring') }}</li>@endforelse</ul></div></div>
    </div>
</div>
@endsection
