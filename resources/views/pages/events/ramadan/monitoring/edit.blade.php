@extends('layouts.app')
@section('content')
<div class="container py-4"><div class="d-flex justify-content-between"><h1 class="h3">{{ __('ramadan_iftars.titles.monitoring') }} #{{ $monitoringReport->id }}</h1><a href="{{ route('events.ramadan.iftars.show', $ramadanIftar) }}">{{ __('ramadan_iftars.actions.back') }}</a></div>
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    @include('pages.events.ramadan.monitoring._form', ['formAction'=>route('events.ramadan.iftars.monitoring.update',[$ramadanIftar,$monitoringReport]),'formMethod'=>'PUT'])
    @if($monitoringWritable && in_array($monitoringReport->status,['draft','returned'],true))<form class="mt-3" method="POST" action="{{ route('events.ramadan.iftars.monitoring.submit',[$ramadanIftar,$monitoringReport]) }}">@csrf<button class="btn btn-success">{{ __('ramadan_iftars.actions.submit_monitoring') }}</button></form>@endif
</div>
@endsection
