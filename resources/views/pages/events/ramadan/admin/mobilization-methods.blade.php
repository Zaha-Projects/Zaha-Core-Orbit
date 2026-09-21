@extends('layouts.app')
@section('page_title', 'طرق الحشد والاستقطاب')
@section('content')
<div class="container py-4" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 mb-1">طرق الحشد والاستقطاب</h1><p class="text-muted mb-0">عطّل القيمة بدل حذفها حتى تبقى السجلات التاريخية مقروءة.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('role.super_admin.ramadan_reference_data.index') }}">البيانات المرجعية</a>
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <div class="card shadow-sm mb-4"><div class="card-header fw-semibold">إضافة طريقة</div><div class="card-body">
        <form method="POST" action="{{ route('events.ramadan.admin.mobilization-methods.store') }}" class="row g-3">@csrf
            <div class="col-md-2"><label class="form-label">الكود</label><input class="form-control" name="code" required></div>
            <div class="col-md-3"><label class="form-label">الاسم العربي</label><input class="form-control" name="name_ar" required></div>
            <div class="col-md-3"><label class="form-label">الاسم الإنجليزي</label><input class="form-control" name="name_en" required></div>
            <div class="col-md-2"><label class="form-label">الترتيب</label><input class="form-control" type="number" min="0" name="sort_order" value="0" required></div>
            <input type="hidden" name="is_active" value="1"><div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">إضافة</button></div>
        </form>
    </div></div>
    @forelse($mobilizationMethods as $method)
    <div class="card shadow-sm mb-3"><div class="card-body"><div class="row g-2 align-items-end">
        <form id="method-{{ $method->id }}" method="POST" action="{{ route('events.ramadan.admin.mobilization-methods.update',$method) }}">@csrf @method('PUT')<input type="hidden" name="is_active" value="{{ $method->is_active ? 1 : 0 }}"><input type="hidden" name="is_other" value="{{ $method->is_other ? 1 : 0 }}"></form>
        <div class="col-md-2"><label class="form-label">الكود</label><input class="form-control" value="{{ $method->code }}" disabled></div>
        <div class="col-md-3"><label class="form-label">العربي</label><input form="method-{{ $method->id }}" class="form-control" name="name_ar" value="{{ $method->name_ar }}" required></div>
        <div class="col-md-3"><label class="form-label">الإنجليزي</label><input form="method-{{ $method->id }}" class="form-control" name="name_en" value="{{ $method->name_en }}" required></div>
        <div class="col-md-1"><label class="form-label">الترتيب</label><input form="method-{{ $method->id }}" class="form-control" name="sort_order" value="{{ $method->sort_order }}" required></div>
        <div class="col-md-3 d-flex gap-2"><button form="method-{{ $method->id }}" class="btn btn-outline-primary">حفظ</button><form method="POST" action="{{ route('events.ramadan.admin.mobilization-methods.toggle',$method) }}">@csrf @method('PATCH')<button class="btn {{ $method->is_active ? 'btn-outline-secondary' : 'btn-success' }}">{{ $method->is_active ? 'تعطيل' : 'تفعيل' }}</button></form></div>
    </div></div></div>
    @empty<div class="alert alert-light border text-muted">لا توجد طرق حشد مسجلة.</div>@endforelse
</div>
@endsection
