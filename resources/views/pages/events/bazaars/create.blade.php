@extends('layouts.app')
@section('title','إضافة بازار')
@section('content')<div class="container-fluid bazaar-page" dir="rtl"><header class="bazaar-hero mb-4"><span class="bazaar-hero-icon"><i class="fas fa-store"></i></span><div><span class="bazaar-eyebrow">فعاليات زها · سوق مجتمعي</span><h1>إنشاء خطة بازار</h1><p>خطط الموقع والفئات واحتياجات التنفيذ والطاولات ضمن تجربة واضحة ومتدرجة.</p></div></header><form method="POST" action="{{ route('events.bazaars.store') }}">@csrf @include('pages.events.bazaars._form')@include('pages.events.bazaars._actions')</form></div>@endsection
