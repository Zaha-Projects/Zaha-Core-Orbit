@extends('layouts.app')

@section('title', 'مؤشرات '.$module['label_ar'])

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
<div class="kpi-dashboard" dir="rtl" data-kpi-dashboard>
    @include('pages.kpi.partials.header', [
        'title' => 'مؤشرات '.$module['label_ar'],
        'subtitle' => 'مؤشرات دورة حياة '.$module['label_ar'].' محسوبة من بيانات سير العمل الفعلية ضمن الفترة والنطاق المحددين.',
        'icon' => $module['icon'],
        'accent' => $module['accent'],
    ])
    @include('pages.kpi.partials.section-nav')
    @include('pages.kpi.partials.filters', ['action' => route('kpi.events.show', $module['key']), 'showModule' => false])

    <section class="kpi-section" aria-labelledby="kpi-module-title">
        <div class="kpi-section__head">
            <h2 id="kpi-module-title">المراحل</h2>
            <p>{{ $filters['period']->label() }}</p>
        </div>
        <div class="kpi-modules kpi-modules--single">
            @foreach ($modules as $summary)
                @include('pages.kpi.partials.module-card', ['summary' => $summary, 'linked' => false])
            @endforeach
        </div>
    </section>

    @foreach ($sections as $sectionKey => $section)
        <section class="kpi-section" aria-labelledby="kpi-section-{{ $sectionKey }}">
            <div class="kpi-section__head">
                <h2 id="kpi-section-{{ $sectionKey }}">{{ $section['label'] }}</h2>
            </div>
            <div class="kpi-grid">
                @foreach ($section['results'] as $result)
                    @include('pages.kpi.partials.kpi-card', ['result' => $result])
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="kpi-section" aria-labelledby="kpi-charts-title">
        <div class="kpi-section__head">
            <h2 id="kpi-charts-title">التحليلات</h2>
        </div>
        @include('pages.kpi.partials.charts', ['charts' => $charts])
    </section>

    @include('pages.kpi.partials.unavailable-list', ['unavailable' => $unavailable])
</div>
@endsection

@push('scripts')
    @include('layouts.app.partials.chartjs')
    <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/kpi-dashboard.js') }}"></script>
@endpush
