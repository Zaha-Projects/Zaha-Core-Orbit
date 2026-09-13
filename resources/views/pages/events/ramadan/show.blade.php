@extends('layouts.app')
@section('content')
@php
$planningLabel = __('ramadan_iftars.statuses.planning.'.$ramadanIftar->status);
$executionLabel = __('ramadan_iftars.statuses.execution.'.$ramadanIftar->execution_status);
$latestMonitoring = $ramadanIftar->monitoringReports->sortByDesc('updated_at')->first();
$monitoringApproved = $ramadanIftar->approvedMonitoringReportForClosure() !== null;
$stages = [
    ['planning', in_array($ramadanIftar->status, ['submitted','approved'], true)],
    ['approval', $ramadanIftar->status === 'approved'],
    ['execution', $ramadanIftar->execution_status === 'completed'],
    ['monitoring', $monitoringApproved],
    ['closure', $ramadanIftar->closed_at !== null],
];
@endphp
<div class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('events.ramadan.iftars.index') }}">{{ __('ramadan_iftars.navigation.title') }}</a></li><li class="breadcrumb-item active" aria-current="page">{{ __('ramadan_iftars.navigation.workspace',['id'=>$ramadanIftar->id]) }}</li></ol></nav>
    @if($ramadanIftar->closed_at)<div class="alert alert-dark d-flex flex-wrap justify-content-between align-items-center gap-2"><span><i class="fas fa-lock me-1" aria-hidden="true"></i>{{ __('ramadan_iftars.closure.historical') }}</span><strong>{{ __('ramadan_iftars.closure.closed_at') }}: {{ $ramadanIftar->closed_at->format('Y-m-d H:i') }}</strong></div>@endif
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
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
                @if($canClose)<button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#closeRamadanIftarModal"><i class="fas fa-lock me-1" aria-hidden="true"></i>{{ __('ramadan_iftars.actions.close') }}</button>@endif
                @if($canRequestChange)<a class="btn btn-outline-primary" href="{{ route('events.ramadan.iftars.change-request.create',$ramadanIftar) }}"><i class="fas fa-code-branch me-1" aria-hidden="true"></i>{{ __('ramadan_iftars.change_requests.request') }}</a>@endif
            </div>
        </div>
        <hr><div class="row g-3"><div class="col-6 col-lg-3"><small class="text-muted">{{ __('ramadan_iftars.fields.branch') }}</small><div>{{ optional($ramadanIftar->branch)->name ?: '—' }}</div></div><div class="col-6 col-lg-3"><small class="text-muted">{{ __('ramadan_iftars.fields.planned_date') }}</small><div>{{ optional($ramadanIftar->planned_date)->format('Y-m-d') ?: '—' }}</div></div><div class="col-6 col-lg-3"><small class="text-muted">{{ __('ramadan_iftars.fields.actual_date') }}</small><div>{{ optional($ramadanIftar->actual_date)->format('Y-m-d') ?: '—' }}</div></div><div class="col-6 col-lg-3"><small class="text-muted">{{ __('ramadan_iftars.fields.current_step') }}</small><div>{{ (app()->getLocale()==='ar' ? optional(optional($ramadanIftar->workflowInstance)->currentStep)->name_ar : optional(optional($ramadanIftar->workflowInstance)->currentStep)->name_en) ?: '—' }}</div></div></div>
    </div></div>

    <div class="card shadow-sm mb-4"><div class="card-body"><div class="row g-2 text-center">
        @foreach($stages as [$stage,$complete])<div class="col-6 col-md"><div class="border rounded p-3 h-100 {{ $complete ? 'bg-success-subtle border-success' : 'bg-light' }}"><i class="fas {{ $complete ? 'fa-circle-check text-success' : 'fa-clock text-muted' }} mb-2" aria-hidden="true"></i><div class="fw-semibold">{{ __('ramadan_iftars.lifecycle.'.$stage) }}</div><small class="text-muted">{{ __('ramadan_iftars.lifecycle.'.($complete ? 'complete' : 'pending')) }}</small></div></div>@endforeach
    </div></div></div>

    <div class="card shadow-sm mb-4"><div class="card-header fw-semibold">{{ __('ramadan_iftars.change_requests.version_history') }}</div><div class="list-group list-group-flush">
        @foreach($versionHistory as $version)<a class="list-group-item list-group-item-action d-flex flex-wrap justify-content-between align-items-center gap-2 {{ $version->id === $ramadanIftar->id ? 'active' : '' }}" href="{{ route('events.ramadan.iftars.show',$version) }}"><span>{{ __('ramadan_iftars.change_requests.version',['number'=>$version->version_number]) }} · {{ __('ramadan_iftars.statuses.planning.'.$version->status) }}</span><span class="badge {{ $version->id === $latestVersion->id ? 'bg-success' : 'bg-secondary' }}">{{ __('ramadan_iftars.change_requests.'.($version->id === $latestVersion->id ? 'current' : 'historical')) }}</span></a>@endforeach
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
        @forelse($rows as $row)<tr><td>{{ $row->description ?? $row->name ?? $row->item_name ?? (app()->getLocale()==='ar' ? optional($row->beneficiarySegment)->name_ar : (optional($row->beneficiarySegment)->name_en ?: optional($row->beneficiarySegment)->name_ar)) ?? '#'.$row->id }}</td><td>{{ $row->planned_quantity ?? $row->planned_members_count ?? $row->planned_count ?? '—' }}</td><td>{{ $row->actual_quantity ?? $row->actual_members_count ?? $row->actual_count ?? ($row->execution_status ? __('ramadan_iftars.statuses.execution.'.$row->execution_status) : '—') }}</td></tr>@empty<tr><td colspan="3" class="text-center text-muted">{{ __('ramadan_iftars.'.$empty) }}</td></tr>@endforelse
        </tbody></table></div></div></div>
        @endforeach
        <div class="col-12"><div class="card"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.execution_needs') }}</div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('ramadan_iftars.fields.title') }}</th><th>{{ __('ramadan_iftars.fields.expected') }}</th><th>{{ __('ramadan_iftars.fields.actual') }}</th><th>{{ __('ramadan_iftars.fields.execution_status') }}</th></tr></thead><tbody>@forelse($ramadanIftar->executionNeeds as $row)<tr><td>{{ optional($row->executionNeedType)->name }} <span class="badge bg-light text-dark">{{ __('ramadan_iftars.statuses.requirement.'.($row->is_required ? 'required' : 'not_required')) }}</span></td><td>{{ $row->planned_details ?: '—' }}</td><td>{{ $row->actual_details ?: '—' }}</td><td>{{ __('ramadan_iftars.statuses.execution.'.$row->status) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-muted">{{ __('ramadan_iftars.empty.execution_needs') }}</td></tr>@endforelse</tbody></table></div></div></div>
        <div class="col-12"><div class="card"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.workflow') }}</div><ul class="list-group list-group-flush">@forelse(optional($ramadanIftar->workflowInstance)->logs ?? [] as $log)<li class="list-group-item">{{ (app()->getLocale()==='ar' ? optional($log->step)->name_ar : optional($log->step)->name_en) }} · {{ optional($log->actor)->name }} · {{ $log->comment }}</li>@empty<li class="list-group-item text-muted">{{ __('ramadan_iftars.empty.workflow') }}</li>@endforelse</ul></div></div>
        <div class="col-12"><div class="card"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.monitoring') }}</div><ul class="list-group list-group-flush">@forelse($ramadanIftar->monitoringReports as $report)<li class="list-group-item d-flex justify-content-between"><span>@if($canMonitor)<a href="{{ route('events.ramadan.iftars.monitoring.edit', [$ramadanIftar,$report]) }}">{{ app()->getLocale()==='ar' ? optional($report->monitoringMethod)->name_ar : (optional($report->monitoringMethod)->name_en ?: optional($report->monitoringMethod)->name_ar) }}</a>@else{{ app()->getLocale()==='ar' ? optional($report->monitoringMethod)->name_ar : (optional($report->monitoringMethod)->name_en ?: optional($report->monitoringMethod)->name_ar) }}@endif</span><span class="badge bg-secondary">{{ __('ramadan_iftars.statuses.monitoring.'.$report->status) }}</span></li>@empty<li class="list-group-item text-muted">{{ __('ramadan_iftars.empty.monitoring') }}</li>@endforelse</ul></div></div>
    </div>
</div>
@if($canClose)
<div class="modal fade" id="closeRamadanIftarModal" tabindex="-1" aria-labelledby="closeRamadanIftarLabel" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h2 class="modal-title h5" id="closeRamadanIftarLabel">{{ __('ramadan_iftars.closure.title') }}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('ramadan_iftars.actions.back') }}"></button></div><div class="modal-body"><p class="fw-semibold">{{ __('ramadan_iftars.closure.ready') }}</p><div class="alert alert-warning mb-0">{{ __('ramadan_iftars.closure.warning') }}</div></div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('ramadan_iftars.actions.back') }}</button><form method="POST" action="{{ route('events.ramadan.iftars.close',$ramadanIftar) }}">@csrf<button class="btn btn-danger"><i class="fas fa-lock me-1" aria-hidden="true"></i>{{ __('ramadan_iftars.actions.confirm_close') }}</button></form></div></div></div></div>
@endif
@endsection
