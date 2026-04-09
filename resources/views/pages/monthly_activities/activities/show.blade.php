@extends('layouts.app')

@php
    $title = 'عرض تفاصيل النشاط';
@endphp

@section('content')
    <div class="event-module">
        <div class="card event-card mb-4">
            <div class="card-body d-flex justify-content-between align-items-center gap-2 flex-wrap">
                <div>
                    <h1 class="h4 mb-1">{{ $title }}</h1>
                    <p class="text-muted mb-0">{{ $monthlyActivity->title }}</p>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary" href="{{ route('role.relations.activities.index') }}">رجوع</a>
                    <a class="btn btn-primary" href="{{ route('role.relations.activities.edit', $monthlyActivity) }}">تعديل</a>
                </div>
            </div>
        </div>

        <div class="card event-card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6"><strong>عنوان النشاط:</strong> {{ $monthlyActivity->title }}</div>
                    <div class="col-12 col-md-3"><strong>التاريخ المقترح:</strong> {{ optional($monthlyActivity->proposed_date)->format('Y-m-d') ?? '-' }}</div>
                    <div class="col-12 col-md-3"><strong>الحالة:</strong> {{ $monthlyActivity->status }}</div>
                    <div class="col-12 col-md-4"><strong>الفرع:</strong> {{ $monthlyActivity->branch?->name ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>ضمن الأجندة السنوية:</strong> {{ $monthlyActivity->is_in_agenda ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>مصدر النشاط:</strong> {{ $monthlyActivity->is_from_agenda ? 'من الأجندة' : 'إدخال يدوي' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>نوع المكان:</strong> {{ $monthlyActivity->location_type === 'outside_center' ? 'خارج المركز' : 'داخل المركز' }}</div>
                    <div class="col-12 col-md-4"><strong>القاعة/المكان:</strong> {{ $monthlyActivity->internal_location ?? $monthlyActivity->outside_place_name ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>رابط الموقع:</strong> {{ $monthlyActivity->outside_google_maps_url ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>بحاجة لمتطوعين:</strong> {{ $monthlyActivity->needs_volunteers ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>عدد المتطوعين:</strong> {{ $monthlyActivity->required_volunteers ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الحضور المتوقع:</strong> {{ $monthlyActivity->expected_attendance ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12 col-md-4"><strong>مخاطبات رسمية:</strong> {{ $monthlyActivity->needs_official_correspondence ? 'نعم' : 'لا' }}</div>
                    <div class="col-12 col-md-4"><strong>سبب المخاطبة:</strong> {{ $monthlyActivity->official_correspondence_reason ?? '-' }}</div>
                    <div class="col-12 col-md-4"><strong>الجهة المخاطبة:</strong> {{ $monthlyActivity->official_correspondence_target ?? '-' }}</div>

                    <div class="col-12"><hr></div>
                    <div class="col-12"><strong>الوصف:</strong><br>{{ $monthlyActivity->description ?? '-' }}</div>
                    <div class="col-12"><strong>الوصف المختصر:</strong><br>{{ $monthlyActivity->short_description ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
