@extends('layouts.app')

@section('page_title', 'البيانات المرجعية للعلاقات')
@section('content')
@php($titles = ['age_groups' => 'الفئات العمرية', 'segments' => 'الشرائح', 'execution_needs' => 'احتياجات التنفيذ'])
<div class="container py-4" dir="rtl">
    <div class="mb-4"><h1 class="h3 mb-1">البيانات المرجعية للعلاقات</h1><p class="text-muted mb-0">إدارة موحدة للفئات العمرية والشرائح واحتياجات التنفيذ. عطّل القيم المستخدمة تاريخيًا بدل حذفها.</p></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <ul class="nav nav-tabs mb-3" role="tablist">@foreach($titles as $key => $title)<li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#relations-{{ $key }}" type="button">{{ $title }}</button></li>@endforeach</ul>
    <div class="tab-content">@foreach($titles as $resource => $title)
        <section class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="relations-{{ $resource }}">
            <div class="card mb-3"><div class="card-header fw-semibold">إضافة: {{ $title }}</div><div class="card-body"><form method="POST" action="{{ route('role.super_admin.relations_reference_data.store', $resource) }}" class="row g-3">@csrf @include('pages.admin.relations-reference-data._fields', ['record' => null])<div class="col-12"><button class="btn btn-primary">إضافة</button></div></form></div></div>
            @forelse($resources[$resource] as $record)
                <div class="card mb-3"><div class="card-header d-flex justify-content-between"><strong>{{ $record->name ?? $record->name_ar }}</strong><span class="badge {{ $record->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $record->is_active ? 'فعال' : 'غير فعال' }}</span></div><div class="card-body">
                    <form method="POST" action="{{ route('role.super_admin.relations_reference_data.update', [$resource, $record->id]) }}" class="row g-3">@csrf @method('PUT') @include('pages.admin.relations-reference-data._fields', ['record' => $record])<div class="col-12"><button class="btn btn-outline-primary">حفظ التعديل</button></div></form>
                    <div class="d-flex gap-2 mt-3"><form method="POST" action="{{ route('role.super_admin.relations_reference_data.toggle', [$resource, $record->id]) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-secondary">{{ $record->is_active ? 'تعطيل' : 'تفعيل' }}</button></form><form method="POST" action="{{ route('role.super_admin.relations_reference_data.destroy', [$resource, $record->id]) }}" onsubmit="return confirm('حذف القيمة نهائيًا؟')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">حذف آمن</button></form></div>
                </div>
            @empty<div class="alert alert-light border">لا توجد قيم مسجلة.</div>@endforelse
        </section>
    @endforeach</div>
</div>
<script>if(location.hash){var trigger=document.querySelector('[data-bs-target="'+CSS.escape(location.hash)+'"]');if(trigger&&window.bootstrap)bootstrap.Tab.getOrCreateInstance(trigger).show()}</script>
@endsection
