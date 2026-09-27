@extends('layouts.app')
@section('content')
<div class="container py-4"><nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('events.ramadan.iftars.index') }}">{{ __('ramadan_iftars.navigation.title') }}</a></li><li class="breadcrumb-item"><a href="{{ route('events.ramadan.iftars.show',$ramadanIftar) }}">{{ __('ramadan_iftars.navigation.workspace',['id'=>$ramadanIftar->id]) }}</a></li><li class="breadcrumb-item active" aria-current="page">{{ __('ramadan_iftars.titles.monitoring') }}</li></ol></nav><div class="d-flex justify-content-between"><h1 class="h3">{{ __('ramadan_iftars.titles.monitoring') }} — {{ $ramadanIftar->title }}</h1><a href="{{ route('events.ramadan.iftars.show', $ramadanIftar) }}">{{ __('ramadan_iftars.actions.back') }}</a></div>
    @if(session('success'))<div class="alert alert-success mt-3">{{ session('success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger mt-3"><strong>تعذر إرسال التحقق. راجع البنود التالية:</strong><ul class="mb-0 mt-2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="alert alert-info my-3">{{ __('ramadan_iftars.hints.monitoring_review_only') }}</div>
    @if($monitoringWritable)
        @include('pages.events.ramadan.monitoring._form', [
            'formAction' => $monitoringReport ? route('events.ramadan.iftars.monitoring.update', [$ramadanIftar, $monitoringReport]) : route('events.ramadan.iftars.monitoring.store', $ramadanIftar),
            'formMethod' => $monitoringReport ? 'PUT' : 'POST',
        ])
    @else<div class="alert alert-secondary">{{ __('ramadan_iftars.hints.read_only_monitoring') }}</div>@endif
</div>
@endsection
