@extends('layouts.app')
@section('content')
<div class="container py-4">
    <h1 class="h3">{{ $ramadanIftar->title }}</h1>
    <p class="text-muted">{{ optional($ramadanIftar->branch)->name }} · {{ optional($ramadanIftar->planned_date)->format('Y-m-d') }}</p>
    <div class="card mb-3"><div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">Guidance</dt><dd class="col-sm-9">{{ optional($ramadanIftar->guidanceVersion)->title }} (v{{ optional($ramadanIftar->guidanceVersion)->version_number }})</dd>
            <dt class="col-sm-3">Relations officer</dt><dd class="col-sm-9">{{ optional($ramadanIftar->relationsOfficer)->name }}</dd>
            <dt class="col-sm-3">Expected attendance</dt><dd class="col-sm-9">{{ $ramadanIftar->expected_attendance }}</dd>
            <dt class="col-sm-3">Planned meals</dt><dd class="col-sm-9">{{ $ramadanIftar->planned_meals_count }}</dd>
            <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $ramadanIftar->description }}</dd>
        </dl>
    </div></div>

    @php $sections = [
        'Target groups' => $ramadanIftar->targetGroupSelections->map(fn($row) => optional($row->targetGroup)->name.' — '.$row->planned_count),
        'Execution needs' => $ramadanIftar->executionNeeds->map(fn($row) => optional($row->executionNeedType)->name.' — '.($row->is_required ? 'Required' : 'Not required')),
        'Meals' => $ramadanIftar->meals->map(fn($row) => $row->description.' — '.$row->planned_quantity),
        'Gifts' => $ramadanIftar->gifts->map(fn($row) => $row->description.' — '.$row->planned_quantity),
        'Program segments' => $ramadanIftar->programSegments->pluck('name'),
        'Execution teams' => $ramadanIftar->executionTeams->map(fn($row) => $row->name.' — '.$row->members->count().' members'),
        'Volunteer requirements' => $ramadanIftar->volunteerRequirements->map(fn($row) => $row->planned_count.' volunteers'),
        'Supplies' => $ramadanIftar->supplies->map(fn($row) => $row->item_name.' — '.$row->planned_quantity),
    ]; @endphp
    <div class="row">@foreach($sections as $heading => $rows)<div class="col-md-6 mb-3"><div class="card h-100"><div class="card-header">{{ $heading }}</div><ul class="list-group list-group-flush">@forelse($rows as $row)<li class="list-group-item">{{ $row }}</li>@empty<li class="list-group-item text-muted">None</li>@endforelse</ul></div></div>@endforeach</div>

    <div class="card"><div class="card-header">Decision: {{ optional($workflowInstance->currentStep)->name_en }}</div><div class="card-body">
        <form method="POST" action="{{ route('events.ramadan.approvals.decision', $ramadanIftar) }}">@csrf
            <input type="hidden" name="workflow_step_id" value="{{ $workflowInstance->current_step_id }}">
            <div class="mb-3"><label>Comment</label><textarea class="form-control" name="comment" rows="3">{{ old('comment') }}</textarea></div>
            <button class="btn btn-success" name="decision" value="approved">Approve</button>
            <button class="btn btn-warning" name="decision" value="changes_requested">Request changes</button>
        </form>
    </div></div>
</div>
@endsection
