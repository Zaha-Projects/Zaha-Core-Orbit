{{-- Server-prepared chart payloads. Params: $charts, $show (chart keys to render) --}}
@php
    $kpiChartMeta = [
        'eventsByModule' => ['title' => 'الفعاليات حسب النوع والمرحلة', 'description' => 'عدد السجلات الحالية التي وصلت كل مرحلة ضمن الفترة.', 'icon' => 'fa-chart-column'],
        'statusDistribution' => ['title' => 'توزيع الحالات', 'description' => 'كل سجل يُحتسب مرة واحدة في أعلى مرحلة وصل إليها.', 'icon' => 'fa-bars-progress'],
        'monthlyTrend' => ['title' => 'الاتجاه الشهري للفعاليات المخططة', 'description' => 'عدد الفعاليات المخططة في كل شهر من سنة '.$charts['monthlyTrend']['year'].'.', 'icon' => 'fa-chart-line'],
    ];
@endphp
<div class="kpi-charts">
    @foreach ($show as $chartKey)
        <section class="kpi-chart-card {{ $chartKey === 'monthlyTrend' ? 'kpi-chart-card--wide' : '' }}" aria-labelledby="kpi-chart-{{ $chartKey }}-title">
            <header class="kpi-chart-card__header">
                <h3 id="kpi-chart-{{ $chartKey }}-title"><i class="fas {{ $kpiChartMeta[$chartKey]['icon'] }}" aria-hidden="true"></i> {{ $kpiChartMeta[$chartKey]['title'] }}</h3>
                <p>{{ $kpiChartMeta[$chartKey]['description'] }}</p>
            </header>
            @if ($charts[$chartKey]['available'])
                <div class="kpi-chart">
                    <canvas data-kpi-chart="{{ $chartKey }}" role="img" aria-label="{{ $kpiChartMeta[$chartKey]['title'] }}"></canvas>
                </div>
            @else
                <div class="kpi-empty"><i class="fas fa-chart-simple" aria-hidden="true"></i> {{ \App\Services\Kpi\KpiFormatter::UNAVAILABLE }} للفترة والنطاق المحددين.</div>
            @endif
        </section>
    @endforeach
</div>
@php($kpiChartPayload = \Illuminate\Support\Arr::only($charts, $show))
<script type="application/json" id="kpi-chart-data">@json($kpiChartPayload)</script>
