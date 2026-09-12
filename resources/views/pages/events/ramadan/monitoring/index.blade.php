@extends('layouts.app')
@section('content')
<div class="container py-4"><div class="d-flex justify-content-between"><h1 class="h3">Monitoring — {{ $ramadanIftar->title }}</h1><a href="{{ route('events.ramadan.iftars.show', $ramadanIftar) }}">Back to Iftar</a></div>
    <div class="card my-3"><div class="card-header">Existing reports</div><ul class="list-group list-group-flush">@forelse($ramadanIftar->monitoringReports as $report)<li class="list-group-item"><a href="{{ route('events.ramadan.iftars.monitoring.edit', [$ramadanIftar,$report]) }}">{{ optional($report->monitoringMethod)->name_en ?: 'Report #'.$report->id }}</a> · {{ $report->status }} · {{ optional($report->monitor)->name }}</li>@empty<li class="list-group-item text-muted">No reports yet.</li>@endforelse</ul></div>
    @if($monitoringWritable)<h2 class="h5">Create report</h2>@include('pages.events.ramadan.monitoring._form', ['formAction'=>route('events.ramadan.iftars.monitoring.store',$ramadanIftar),'formMethod'=>'POST'])@else<div class="alert alert-secondary">Execution is completed; monitoring evidence is read-only.</div>@endif
</div>
@endsection
