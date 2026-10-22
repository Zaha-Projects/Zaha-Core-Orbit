@extends('layouts.app')

@section('title', 'مؤشرات فرع '.$branch->name)

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
@endpush

@section('content')
<div class="kpi-dashboard" dir="rtl" data-kpi-dashboard>
    @include('pages.kpi.partials.header', [
        'title' => 'مؤشرات فرع '.$branch->name,
        'subtitle' => 'ملخص أداء الفرع في الفعاليات التي ينفذها (الأنشطة الشهرية، الإفطارات، البازارات) والتزامه بخطة الأجندة.',
        'icon' => 'fa-code-branch',
    ])
    @include('pages.kpi.partials.section-nav')
    @include('pages.kpi.partials.filters', ['action' => route('kpi.branches.show', $branch->id), 'showModule' => false, 'showBranch' => false])

    <section class="kpi-section" aria-labelledby="kpi-branch-summary">
        <div class="kpi-section__head">
            <h2 id="kpi-branch-summary">ملخص الفرع</h2>
            <p>{{ $filters['period']->label() }}</p>
        </div>
        <div class="kpi-modules">
            @foreach ($modules as $summary)
                @include('pages.kpi.partials.module-card', ['summary' => $summary, 'query' => \Illuminate\Support\Arr::except($filters['query'], ['module'])])
            @endforeach
        </div>
    </section>

    <section class="kpi-section" aria-labelledby="kpi-branch-kpis">
        <div class="kpi-section__head">
            <h2 id="kpi-branch-kpis">مؤشرات الفرع</h2>
        </div>
        <div class="kpi-grid">
            @foreach ($results as $result)
                @include('pages.kpi.partials.kpi-card', ['result' => $result])
            @endforeach
        </div>
    </section>

    <section class="kpi-section" aria-labelledby="kpi-branch-volume">
        <div class="kpi-section__head">
            <h2 id="kpi-branch-volume">عدد الفعاليات المدخلة / المخططة شهرياً — سنة {{ $filters['period']->year }}</h2>
            <p>المستهدف المقترح (4 فأكثر شهرياً) غير معتمد بعد ولا يُقيّم.</p>
        </div>
        <div class="table-responsive kpi-table-wrap">
            <table class="table align-middle kpi-table">
                <caption class="visually-hidden">الحجم الشهري للفعاليات</caption>
                <thead>
                    <tr><th scope="col">الشهر</th><th scope="col">مخطط</th><th scope="col">معتمد</th><th scope="col">منفذ</th></tr>
                </thead>
                <tbody>
                    @foreach ($volume as $row)
                        <tr>
                            <th scope="row">{{ $row['month'] }}</th>
                            @foreach (['planned', 'approved', 'executed'] as $stage)
                                <td class="{{ $row[$stage] === null ? 'is-unavailable' : '' }}">{{ $row[$stage] === null ? \App\Services\Kpi\KpiFormatter::UNAVAILABLE : number_format($row[$stage]) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="kpi-section" aria-labelledby="kpi-branch-charts">
        <div class="kpi-section__head">
            <h2 id="kpi-branch-charts">التحليلات</h2>
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
