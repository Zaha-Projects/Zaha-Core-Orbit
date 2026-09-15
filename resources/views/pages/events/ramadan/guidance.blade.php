@extends('layouts.app')

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb"><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('events.ramadan.iftars.index') }}">{{ __('ramadan_iftars.navigation.title') }}</a></li><li class="breadcrumb-item active" aria-current="page">{{ __('ramadan_iftars.titles.guidance') }}</li></ol></nav>
    @php $sections=json_decode($guidance->content,true); $structured=is_array($sections); @endphp
    <div class="card ramadan-hero shadow-sm mb-4">
        <div class="card-body p-4">
            <h1 class="h4 mb-1">{{ $guidance->title }}</h1>
            <div class="opacity-75">{{ __('ramadan_iftars.hints.version', ['number' => $guidance->version_number]) }} · {{ optional($guidance->published_at)->format('Y-m-d') }}</div>
        </div></div>
        <div class="alert alert-warning"><i class="fas fa-circle-info"></i> يرجى قراءة التعليمات كاملة قبل البدء بالتخطيط. عنوان المصدر الظاهر هو 2025، بينما مرجع الملف المرفوع يشير إلى 2026.</div>
        @if($structured)<div class="row g-3 mb-4">@foreach($sections as $section)<div class="col-12 col-lg-6"><section class="card guidance-section shadow-sm h-100 {{ !empty($section['emphasis'])?'border-warning':'' }}"><div class="card-body"><div class="d-flex gap-3"><i class="fas {{ $section['icon']??'fa-circle-check' }} text-success fs-4" aria-hidden="true"></i><div><h2 class="h5">{{ $section['title'] }}</h2><ul class="mb-0 ps-3">@foreach($section['items']??[] as $item)<li class="mb-2">{{ $item }}</li>@endforeach</ul></div></div></div></section></div>@endforeach</div>@else<div class="card shadow-sm mb-4"><div class="card-body" style="white-space:pre-wrap">{{ $guidance->content }}</div></div>@endif

            @if ($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <form class="card shadow-sm ramadan-no-print" method="POST" action="{{ route('events.ramadan.guidance.accept') }}"><div class="card-body">
                @csrf
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1" id="accept_guidance" name="accept_guidance" required>
                    <label class="form-check-label" for="accept_guidance">{{ __('ramadan_iftars.hints.guidance_acceptance') }}</label>
                </div>
                <button class="btn btn-primary" type="submit">{{ __('ramadan_iftars.actions.accept_guidance') }}</button>
            </div></form>
</div>
@endsection
