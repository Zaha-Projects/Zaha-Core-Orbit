@extends('layouts.app')

@php
    $title = 'لوحة مدير البرامج';
    $subtitle = 'الإشراف على الخطة الشهرية ومسارات الاعتماد.';
    $actions = [
        ['title' => 'اعتماد الأنشطة', 'description' => 'مراجعة الطلبات وإرسالها للتنفيذ.'],
        ['title' => 'متابعة التنفيذ', 'description' => 'تتبّع الأنشطة المغلقة والمرفقات النهائية.'],
        ['title' => 'تقارير البرامج', 'description' => 'عرض أداء الأنشطة حسب الفروع والمراكز.'],
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
