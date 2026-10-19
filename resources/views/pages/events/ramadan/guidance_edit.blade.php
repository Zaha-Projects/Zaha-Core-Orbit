@extends('layouts.app')
@section('content')
<div class="container py-4" dir="rtl">
    <h1 class="h3">تعديل التعليمات</h1>
    <p class="text-muted">الإصدار الحالي: {{ $guidance->version_number }}</p>
    <div class="alert alert-info">عند الحفظ ستُنشر نسخة جديدة من التعليمات. ستبقى النسخ السابقة وإقراراتها محفوظة، ويلزم إقرار النسخة الجديدة عند بدء تخطيط جديد.</div>
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ route('events.ramadan.guidance.update') }}" class="card"><div class="card-body">
        @csrf
        <input type="hidden" name="source_version_id" value="{{ old('source_version_id', $guidance->id) }}">
        <div class="mb-3"><label for="guidance-title" class="form-label">العنوان</label><input id="guidance-title" class="form-control" name="title" maxlength="255" value="{{ old('title', $guidance->title) }}" required></div>
        <div class="mb-3"><label for="guidance-content" class="form-label">المحتوى</label><textarea id="guidance-content" class="form-control" name="content" rows="20" required>{{ old('content', $content) }}</textarea></div>
        <button type="submit" class="btn btn-primary">حفظ ونشر نسخة جديدة</button>
        <a class="btn btn-outline-secondary" href="{{ route('events.ramadan.guidance.show') }}">إلغاء</a>
        <a class="btn btn-link" href="{{ route('events.ramadan.admin.index') }}">سجل الإصدارات</a>
    </div></form>
</div>
@endsection
