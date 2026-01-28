@extends('layouts.app')

@php
    $title = 'لوحة موظف العلاقات العامة';
    $subtitle = 'إعداد الأجندة السنوية وإدارة المتطلبات الأولية.';
    $actions = [
        ['title' => 'إنشاء فعالية', 'description' => 'إضافة فعاليات جديدة وربطها بالجهات المستهدفة.'],
        ['title' => 'متابعة الملاحظات', 'description' => 'استقبال الملاحظات وتحديث البيانات المطلوبة.'],
        ['title' => 'جاهزية الاعتماد', 'description' => 'التحقق من استكمال البيانات قبل الإرسال.'],
    ];
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-3">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <div class="row g-3">
                @foreach ($actions as $action)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="border rounded p-3 h-100">
                            <h2 class="h6 mb-2">{{ $action['title'] }}</h2>
                            <p class="text-muted mb-0">{{ $action['description'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
