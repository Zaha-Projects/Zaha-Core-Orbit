@extends('layouts.app')

@php
    $title = 'لوحة الإدارة العامة';
    $subtitle = 'ملخص شامل للوحدات وإدارة المستخدمين والصلاحيات.';
    $actions = [
        ['title' => 'إدارة المستخدمين', 'description' => 'إضافة المستخدمين وربطهم بالأدوار والفروع والمراكز.'],
        ['title' => 'متابعة الاعتمادات', 'description' => 'مراجعة حالة الأجندة والخطة الشهرية والاعتمادات.'],
        ['title' => 'التقارير', 'description' => 'تصدير التقارير الشهرية وتتبّع الأداء.'],
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
