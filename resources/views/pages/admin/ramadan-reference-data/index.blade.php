@extends('layouts.app')

@section('page_title', 'البيانات المرجعية للإفطارات')
@section('content')
@php
$titles = [
    'community_organizations' => 'المؤسسات والمراكز',
    'local_communities' => 'المجتمعات المحلية',
    'target_groups' => 'الفئات المستهدفة',
    'beneficiary_segments' => 'شرائح المستفيدين',
    'execution_need_types' => 'أنواع احتياجات التنفيذ',
    'monitoring_methods' => 'طرق المتابعة',
];
@endphp
<div class="container py-4" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 mb-1">البيانات المرجعية للإفطارات</h1><p class="text-muted mb-0">يمكن التعطيل بدل الحذف للحفاظ على السجلات التاريخية.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('events.ramadan.admin.index') }}">إدارة إفطارات رمضان</a>
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach($titles as $key => $title)<li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#ref-{{ $key }}" type="button">{{ $title }}</button></li>@endforeach
    </ul>
    <div class="mb-3"><a class="btn btn-outline-success" href="{{ route('events.ramadan.admin.mobilization-methods.index') }}">طرق الحشد والاستقطاب</a></div>
    <div class="tab-content">
        @foreach($titles as $resource => $title)
        <section class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="ref-{{ $resource }}">
            <div class="row g-2 mb-3"><div class="col-md-8"><input class="form-control" type="search" placeholder="بحث في {{ $title }}" data-reference-filter-search></div><div class="col-md-4"><select class="form-select" data-reference-filter-status><option value="all">الكل</option><option value="active">فعال</option><option value="inactive">غير فعال</option></select></div></div>
            <div class="card mb-3"><div class="card-header fw-semibold">إضافة: {{ $title }}</div><div class="card-body">
                <form method="POST" action="{{ route('role.super_admin.ramadan_reference_data.store', $resource) }}" class="row g-2">@csrf
                    @include('pages.admin.ramadan-reference-data._fields', ['record' => null])
                    <div class="col-12"><button class="btn btn-primary">إضافة</button></div>
                </form>
            </div></div>
            @forelse($resources[$resource] as $record)
            <div class="card mb-2" data-reference-row data-reference-status="{{ $record->is_active ? 'active' : 'inactive' }}" data-reference-text="{{ mb_strtolower($record->name ?? $record->name_ar ?? $record->code ?? '') }}"><div class="card-header d-flex justify-content-between"><strong>{{ $record->name ?? $record->name_ar ?? $record->code }}</strong><span class="badge {{ $record->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $record->is_active ? 'فعال' : 'غير فعال' }}</span></div><div class="card-body">
                <form method="POST" action="{{ route('role.super_admin.ramadan_reference_data.update', [$resource, $record->id]) }}" class="row g-2">@csrf @method('PUT')
                    @include('pages.admin.ramadan-reference-data._fields', ['record' => $record])
                    <div class="col-12"><button class="btn btn-outline-primary">حفظ التعديل</button></div>
                </form>
            </div></div>
            @empty<div class="alert alert-light border text-muted">لا توجد قيم مسجلة ضمن {{ $title }}.</div>@endforelse
            <div class="alert alert-light border text-muted d-none" data-reference-filter-empty>لا توجد نتائج مطابقة.</div>
        </section>
        @endforeach
    </div>
</div>
<script>document.querySelectorAll('.tab-pane').forEach(function(pane){var search=pane.querySelector('[data-reference-filter-search]'),status=pane.querySelector('[data-reference-filter-status]');if(!search||!status)return;function filter(){var visible=0,term=search.value.trim().toLocaleLowerCase('ar');pane.querySelectorAll('[data-reference-row]').forEach(function(row){var show=(!term||row.dataset.referenceText.includes(term))&&(status.value==='all'||row.dataset.referenceStatus===status.value);row.classList.toggle('d-none',!show);if(show)visible++});pane.querySelector('[data-reference-filter-empty]').classList.toggle('d-none',visible!==0)}search.addEventListener('input',filter);status.addEventListener('change',filter)});</script>
@endsection
