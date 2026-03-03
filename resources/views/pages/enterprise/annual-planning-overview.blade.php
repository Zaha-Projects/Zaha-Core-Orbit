@extends('layouts.app')
@section('content')
<div class="card"><div class="card-body"><h4>Annual Planning Overview - {{ $year }}</h4>
<div class="row g-3">
@for($m=1;$m<=12;$m++)
<div class="col-md-6 col-xl-4"><div class="border rounded p-3 h-100"><h6>Month {{ $m }} ({{ $events->get($m, collect())->count() }})</h6><ul class="small">@foreach($events->get($m, collect())->take(5) as $event)<li>{{ $event->event_name }} <span class="badge bg-light text-dark">{{ $event->status }}</span></li>@endforeach</ul><div class="text-muted">Participation: {{ $events->get($m, collect())->sum(fn($e)=>$e->participations->where('entity_type','branch')->where('participation_status','participant')->count()) }}</div></div></div>
@endfor
</div></div></div>
@endsection
