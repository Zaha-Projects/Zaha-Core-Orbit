@extends('layouts.app')

@php
    $title = 'لوحة النقل والحركة';
    $subtitle = 'إدارة المركبات والسائقين وجدولة الرحلات.';
    $actions = [
        ['title' => 'جدولة الرحلات', 'description' => 'إنشاء الرحلات اليومية وتحديث الحالة.'],
        ['title' => 'مركبات وسائقون', 'description' => 'متابعة جاهزية الأسطول وحالة السائقين.'],
        ['title' => 'تقارير النقل', 'description' => 'عرض ملخص الرحلات حسب التاريخ والفرع.'],
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
