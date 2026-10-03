@extends('layouts.app')
@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('events.ramadan.iftars.index') }}">{{ __('ramadan_iftars.navigation.title') }}</a></li><li class="breadcrumb-item"><a href="{{ route('events.ramadan.iftars.show',$ramadanIftar) }}">{{ __('ramadan_iftars.navigation.workspace',['id'=>$ramadanIftar->id]) }}</a></li><li class="breadcrumb-item active">{{ __('ramadan_iftars.change_requests.title') }}</li></ol></nav>
    <div class="row justify-content-center"><div class="col-lg-8"><div class="card shadow-sm"><div class="card-header"><h1 class="h4 mb-0">{{ __('ramadan_iftars.change_requests.title') }}</h1></div><div class="card-body">
        <div class="alert alert-info">{{ __('ramadan_iftars.change_requests.explanation') }}</div>
        @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <dl class="row"><dt class="col-sm-4">{{ __('ramadan_iftars.fields.title') }}</dt><dd class="col-sm-8">{{ $ramadanIftar->title }}</dd><dt class="col-sm-4">{{ __('ramadan_iftars.change_requests.version_history') }}</dt><dd class="col-sm-8">{{ __('ramadan_iftars.change_requests.version',['number'=>$ramadanIftar->version_number]) }}</dd></dl>
        <form method="POST" action="{{ route('events.ramadan.iftars.change-request.store',$ramadanIftar) }}">@csrf<label class="form-label" for="change-reason">{{ __('ramadan_iftars.change_requests.reason') }} <span class="text-danger">*</span></label><textarea id="change-reason" class="form-control @error('reason') is-invalid @enderror" name="reason" rows="5" required>{{ old('reason') }}</textarea>@error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror<div class="d-flex justify-content-end gap-2 mt-3"><a class="btn btn-outline-secondary" href="{{ route('events.ramadan.iftars.show',$ramadanIftar) }}">{{ __('ramadan_iftars.actions.back') }}</a><button class="btn btn-primary">{{ __('ramadan_iftars.change_requests.request') }}</button></div></form>
    </div></div></div></div>
</div>
@endsection
