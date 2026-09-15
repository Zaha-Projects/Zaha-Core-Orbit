@extends('layouts.app')
@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('events.ramadan.iftars.index') }}">{{ __('ramadan_iftars.navigation.title') }}</a></li><li class="breadcrumb-item"><a href="{{ route('events.ramadan.approvals.index') }}">{{ __('ramadan_iftars.navigation.approvals') }}</a></li><li class="breadcrumb-item active" aria-current="page">{{ __('ramadan_iftars.navigation.workspace',['id'=>$ramadanIftar->id]) }}</li></ol></nav>
    <div class="d-flex flex-wrap justify-content-between gap-2 mb-4"><div><h1 class="h3 mb-1">{{ __('ramadan_iftars.approval.review_title') }}</h1><p class="text-muted mb-0">{{ $ramadanIftar->title }} · {{ optional($ramadanIftar->branch)->name }}</p></div><a class="btn btn-outline-secondary" href="{{ route('events.ramadan.approvals.index') }}">{{ __('ramadan_iftars.actions.back') }}</a></div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card shadow-sm mb-3"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.core') }}</div><div class="card-body"><dl class="row mb-0">
        <dt class="col-sm-3">{{ __('ramadan_iftars.sections.guidance') }}</dt><dd class="col-sm-9">{{ optional($ramadanIftar->guidanceVersion)->title }} ({{ __('ramadan_iftars.hints.version',['number'=>optional($ramadanIftar->guidanceVersion)->version_number]) }})</dd>
        <dt class="col-sm-3">{{ __('ramadan_iftars.fields.relations_officer') }}</dt><dd class="col-sm-9">{{ optional($ramadanIftar->relationsOfficer)->name }}</dd>
        <dt class="col-sm-3">{{ __('ramadan_iftars.sections.attendance') }}</dt><dd class="col-sm-9">{{ $ramadanIftar->expected_attendance }}</dd>
        <dt class="col-sm-3">{{ __('ramadan_iftars.sections.meals') }}</dt><dd class="col-sm-9">{{ $ramadanIftar->planned_meals_count }}</dd>
        <dt class="col-sm-3">{{ __('ramadan_iftars.labels.description') }}</dt><dd class="col-sm-9">{{ $ramadanIftar->description ?: '—' }}</dd>
    </dl></div></div>
    @php $sections=[
        'targeting'=>$ramadanIftar->targetGroupSelections->map(fn($r)=>optional($r->targetGroup)->name.' — '.$r->planned_count),
        'execution_needs'=>$ramadanIftar->executionNeeds->map(function($r) use ($ramadanIftar) { $label=optional($r->executionNeedType)->name; $code=optional($r->executionNeedType)->code; if($code==='execution_team') $label.=' — '.$ramadanIftar->executionTeams->pluck('name')->join('، '); if($code==='supplies') $label.=' — '.$ramadanIftar->supplies->pluck('item_name')->join('، '); if($code==='gifts_shields') $label.=' — '.$ramadanIftar->gifts->pluck('description')->join('، '); return $label; }),
        'meals'=>$ramadanIftar->meals->map(fn($r)=>$r->description.' — '.$r->planned_quantity),
        'programs'=>$ramadanIftar->programSegments->pluck('name'),
        'volunteers'=>$ramadanIftar->volunteerRequirements->map(fn($r)=>__('ramadan_iftars.units.volunteers',['count'=>$r->planned_count])),
    ]; @endphp
    <div class="row g-3 mb-3">@foreach($sections as $heading=>$rows)<div class="col-md-6"><div class="card h-100"><div class="card-header fw-semibold">{{ __('ramadan_iftars.sections.'.$heading) }}</div><ul class="list-group list-group-flush">@forelse($rows as $row)<li class="list-group-item">{{ $row }}</li>@empty<li class="list-group-item text-muted">{{ __('ramadan_iftars.empty.'.($heading==='targeting'?'target_groups':$heading)) }}</li>@endforelse</ul></div></div>@endforeach</div>
    <div class="card shadow-sm"><div class="card-header fw-semibold">{{ __('ramadan_iftars.approval.decision',['step'=>(app()->getLocale()==='ar' ? optional($workflowInstance->currentStep)->name_ar : optional($workflowInstance->currentStep)->name_en) ?: '—']) }}</div><div class="card-body"><form method="POST" action="{{ route('events.ramadan.approvals.decision',$ramadanIftar) }}">@csrf<input type="hidden" name="workflow_step_id" value="{{ $workflowInstance->current_step_id }}"><label class="form-label">{{ __('ramadan_iftars.fields.comment') }}</label><textarea class="form-control mb-3" name="comment" rows="3">{{ old('comment') }}</textarea><div class="d-flex flex-wrap gap-2"><button class="btn btn-success" name="decision" value="approved">{{ __('ramadan_iftars.actions.approve') }}</button><button class="btn btn-warning" name="decision" value="changes_requested">{{ __('ramadan_iftars.approval.request_changes') }}</button></div></form></div></div>
</div>
@endsection
