@extends('layouts.app')

@section('title', 'مؤشرات الفعاليات')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
<div class="kpi-dashboard" dir="rtl">
    @include('pages.kpi.partials.header', [
        'title' => 'مؤشرات الفعاليات',
        'subtitle' => 'اختر نوع الفعالية لعرض مؤشراته. المؤشرات التفصيلية لكل نوع تُضاف في المراحل القادمة.',
        'icon' => 'fa-layer-group',
    ])
    @include('pages.kpi.partials.section-nav')
    @include('pages.kpi.partials.filters', ['action' => route('kpi.events.index'), 'showModule' => false])

    <section class="kpi-section" aria-label="أنواع الفعاليات">
        <div class="kpi-modules">
            @foreach ($modules as $summary)
                @include('pages.kpi.partials.module-card', ['summary' => $summary, 'query' => $filters['query']])
            @endforeach
        </div>
    </section>
</div>
@endsection
