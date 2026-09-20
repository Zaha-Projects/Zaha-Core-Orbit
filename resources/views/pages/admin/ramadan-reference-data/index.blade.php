@extends('layouts.app')

@section('page_title', 'البيانات المرجعية للإفطارات')
@section('content')
@php
$titles = [
    'mobilization_methods' => 'طرق الحشد والاستقطاب', 'target_groups' => 'الفئات المستهدفة',
    'beneficiary_segments' => 'شرائح المستفيدين والفئات العمرية', 'gift_types' => 'أنواع الهدايا والدروع',
    'execution_need_types' => 'أنواع احتياجات التنفيذ', 'community_organizations' => 'الجمعيات والمراكز',
    'local_communities' => 'المجتمعات المحلية', 'monitoring_methods' => 'طرق المتابعة',
];
@endphp
<div class="container py-4" dir="rtl">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div><h1 class="h3 mb-1">البيانات المرجعية للإفطارات</h1><p class="text-muted mb-0">يمكن التعطيل بدل الحذف للحفاظ على السجلات التاريخية.</p></div>
        <a class="btn btn-outline-secondary" href="{{ route('role.super_admin.site_settings.index') }}">إعدادات رمضان</a>
    </div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <ul class="nav nav-tabs mb-3" role="tablist">
        @foreach($titles as $key => $title)<li class="nav-item"><button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#ref-{{ $key }}" type="button">{{ $title }}</button></li>@endforeach
    </ul>
    <div class="tab-content">
        @foreach($titles as $resource => $title)
        <section class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="ref-{{ $resource }}">
            <div class="card mb-3"><div class="card-header fw-semibold">إضافة: {{ $title }}</div><div class="card-body">
                <form method="POST" action="{{ route('role.super_admin.ramadan_reference_data.store', $resource) }}" class="row g-2">@csrf
                    @include('pages.admin.ramadan-reference-data._fields', ['record' => null])
                    <div class="col-12"><button class="btn btn-primary">إضافة</button></div>
                </form>
            </div></div>
            @foreach($resources[$resource] as $record)
            <div class="card mb-2"><div class="card-body">
                <form method="POST" action="{{ route('role.super_admin.ramadan_reference_data.update', [$resource, $record->id]) }}" class="row g-2">@csrf @method('PUT')
                    @include('pages.admin.ramadan-reference-data._fields', ['record' => $record])
                    <div class="col-12"><button class="btn btn-outline-primary">حفظ التعديل</button></div>
                </form>
            </div></div>
            @endforeach
        </section>
        @endforeach
    </div>
</div>
@endsection
