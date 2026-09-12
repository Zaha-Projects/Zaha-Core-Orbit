@extends('layouts.app')
@section('content')
<div class="container py-4"><div class="d-flex justify-content-between"><h1 class="h3">{{ __('ramadan_iftars.titles.monitoring') }} — {{ $ramadanIftar->title }}</h1><a href="{{ route('events.ramadan.iftars.show', $ramadanIftar) }}">{{ __('ramadan_iftars.actions.back') }}</a></div>
    <div class="card my-3"><div class="card-header">{{ __('ramadan_iftars.sections.reports') }}</div><ul class="list-group list-group-flush">@forelse($ramadanIftar->monitoringReports as $report)<li class="list-group-item"><a href="{{ route('events.ramadan.iftars.monitoring.edit', [$ramadanIftar,$report]) }}">{{ optional($report->monitoringMethod)->name_en ?: 'Report #'.$report->id }}</a> · {{ __('ramadan_iftars.statuses.monitoring.'.$report->status) }} · {{ optional($report->monitor)->name }}</li>@empty<li class="list-group-item text-muted">{{ __('ramadan_iftars.empty.monitoring') }}</li>@endforelse</ul></div>
    @if($monitoringWritable)<h2 class="h5">{{ __('ramadan_iftars.actions.create_report') }}</h2>@include('pages.events.ramadan.monitoring._form', ['formAction'=>route('events.ramadan.iftars.monitoring.store',$ramadanIftar),'formMethod'=>'POST'])@else<div class="alert alert-secondary">{{ __('ramadan_iftars.hints.read_only_monitoring') }}</div>@endif
</div>
@endsection
