@extends('layouts.app')

@section('title', 'مؤشرات الأداء والتقارير')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
@php($kpiCarryQuery = \Illuminate\Support\Arr::except($filters['query'], ['module']))
<div class="kpi-dashboard" dir="rtl" data-kpi-dashboard>
    @include('pages.kpi.partials.header', [
        'title' => 'نظرة عامة على مؤشرات الأداء',
        'subtitle' => 'مؤشرات موحدة محسوبة من بيانات سير العمل الفعلية للأجندة السنوية والأنشطة الشهرية والإفطارات الرمضانية والبازارات.',
        'icon' => 'fa-gauge-high',
    ])
    @include('pages.kpi.partials.section-nav')
    @include('pages.kpi.partials.filters', ['action' => route('kpi.overview'), 'showModule' => true])

    <section class="kpi-section" aria-labelledby="kpi-summary-title">
        <div class="kpi-section__head">
            <h2 id="kpi-summary-title">ملخص المؤشرات</h2>
            <p>{{ $filters['module'] ? $moduleOptions[$filters['module']] : 'جميع أنواع الفعاليات' }} — {{ $filters['period']->label() }}</p>
        </div>
        <div class="kpi-grid">
            @foreach ($results as $result)
                @include('pages.kpi.partials.kpi-card', ['result' => $result])
            @endforeach
        </div>
    </section>

    <section class="kpi-section" aria-labelledby="kpi-modules-title">
        <div class="kpi-section__head">
            <h2 id="kpi-modules-title">ملخص أنواع الفعاليات</h2>
            <p>أعداد فعلية لكل مرحلة. «لا ينطبق» تعني أن الوحدة لا تمتلك هذه المرحلة.</p>
        </div>
        <div class="kpi-modules">
            @foreach ($modules as $summary)
                @include('pages.kpi.partials.module-card', ['summary' => $summary, 'query' => $kpiCarryQuery])
            @endforeach
        </div>
    </section>

    <section class="kpi-section" aria-labelledby="kpi-charts-title">
        <div class="kpi-section__head">
            <h2 id="kpi-charts-title">التحليلات</h2>
            <p>رسوم مبنية على البيانات نفسها المستخدمة في المؤشرات أعلاه.</p>
        </div>
        @include('pages.kpi.partials.charts', ['show' => $filters['module'] ? ['statusDistribution', 'monthlyTrend'] : ['eventsByModule', 'statusDistribution', 'monthlyTrend']])
    </section>

    <section class="kpi-section kpi-definitions" aria-labelledby="kpi-definitions-title">
        <div class="kpi-section__head">
            <h2 id="kpi-definitions-title">كيف تُحسب المراحل؟</h2>
        </div>
        <ul class="kpi-definitions__list">
            @foreach ($stages as $stage)
                <li><strong>{{ $stage['label_ar'] }}:</strong> {{ $stage['description_ar'] }}</li>
            @endforeach
            <li><strong>السجل الحالي:</strong> غير محذوف وغير مؤرشف وأحدث نسخة من الخطة؛ النسخ السابقة لا تُحتسب.</li>
            <li><strong>المستهدفات:</strong> لم تُعتمد مستهدفات بعد، لذلك لا تُعرض حالة «محقق / غير محقق».</li>
        </ul>
        <div class="kpi-next">
            <a href="{{ route('kpi.branches.index', $filters['query']) }}" class="kpi-next__link"><i class="fas fa-code-branch" aria-hidden="true"></i> مقارنة الفروع</a>
            <a href="{{ route('kpi.events.index', $kpiCarryQuery) }}" class="kpi-next__link"><i class="fas fa-layer-group" aria-hidden="true"></i> مؤشرات كل نوع فعالية</a>
            <a href="{{ route('kpi.users.index') }}" class="kpi-next__link"><i class="fas fa-user-check" aria-hidden="true"></i> مؤشرات المستخدمين (قريبًا)</a>
        </div>
    </section>
</div>
@endsection

@push('scripts')
    @include('layouts.app.partials.chartjs')
    <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/kpi-dashboard.js') }}"></script>
@endpush
