@extends('layouts.app')
@section('title','تعديل البازار')
@section('content')<div class="container-fluid bazaar-page" dir="rtl"><header class="bazaar-hero mb-4"><span class="bazaar-hero-icon"><i class="fas fa-store"></i></span><div><span class="bazaar-eyebrow">تعديل خطة بازار</span><h1>{{ $bazaar->name }}</h1><p>حدّث بيانات الخطة والطاولات ثم راجع اكتمالها قبل الإرسال.</p></div></header><form method="POST" action="{{ route('events.bazaars.update',$bazaar) }}">@csrf @method('PUT') @include('pages.events.bazaars._form')@include('pages.events.bazaars._actions')</form></div>@endsection
