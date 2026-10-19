@extends('layouts.app')

@section('title', 'مؤشرات '.$module['label_ar'])

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
<div class="kpi-dashboard" dir="rtl" data-kpi-dashboard>
    @include('pages.kpi.partials.header', [
        'title' => 'مؤشرات '.$module['label_ar'],
        'subtitle' => 'المؤشرات الأساسية لدورة حياة '.$module['label_ar'].' ضمن الفترة والنطاق المحددين.',
        'icon' => $module['icon'],
        'accent' => $module['accent'],
    ])
    @include('pages.kpi.partials.section-nav')
    @include('pages.kpi.partials.filters', ['action' => route('kpi.events.show', $module['key']), 'showModule' => false])

    <section class="kpi-section" aria-labelledby="kpi-summary-title">
        <div class="kpi-section__head">
            <h2 id="kpi-summary-title">المؤشرات الأساسية</h2>
            <p>{{ $filters['period']->label() }}</p>
        </div>
        <div class="kpi-grid">
            @foreach ($results as $result)
                @include('pages.kpi.partials.kpi-card', ['result' => $result])
            @endforeach
        </div>
    </section>

    <section class="kpi-section" aria-labelledby="kpi-module-title">
        <div class="kpi-section__head">
            <h2 id="kpi-module-title">المراحل</h2>
        </div>
        <div class="kpi-modules kpi-modules--single">
            @foreach ($modules as $summary)
                @include('pages.kpi.partials.module-card', ['summary' => $summary, 'linked' => false])
            @endforeach
        </div>
    </section>

    <section class="kpi-section" aria-labelledby="kpi-charts-title">
        <div class="kpi-section__head">
            <h2 id="kpi-charts-title">التحليلات</h2>
        </div>
        @include('pages.kpi.partials.charts', ['show' => ['statusDistribution', 'monthlyTrend']])
    </section>

    <div class="kpi-empty kpi-empty--soon"><i class="fas fa-hourglass-half" aria-hidden="true"></i> المؤشرات التفصيلية الخاصة بـ{{ $module['label_ar'] }} (التوقيت، الحضور، الاحتياجات، المتابعة…) سيتم تفعيلها في المرحلة التالية.</div>
</div>
@endsection

@push('scripts')
    @include('layouts.app.partials.chartjs')
    <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/kpi-dashboard.js') }}"></script>
@endpush
