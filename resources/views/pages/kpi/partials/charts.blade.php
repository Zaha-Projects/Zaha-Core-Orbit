{{-- Server-prepared chart specs (KpiChartBuilder). Param: $charts (list). JS renders only. --}}
<div class="kpi-charts">
    @foreach ($charts as $chart)
        <section class="kpi-chart-card {{ count($chart['labels']) >= 12 ? 'kpi-chart-card--wide' : '' }}" aria-labelledby="kpi-chart-{{ $chart['id'] }}-title">
            <header class="kpi-chart-card__header">
                <h3 id="kpi-chart-{{ $chart['id'] }}-title"><i class="fas {{ $chart['icon'] }}" aria-hidden="true"></i> {{ $chart['title'] }}</h3>
                <p>{{ $chart['description'] }}</p>
            </header>
            @if ($chart['available'])
                <div class="kpi-chart">
                    <canvas data-kpi-chart="{{ $chart['id'] }}" role="img" aria-label="{{ $chart['title'] }}"></canvas>
                </div>
            @else
                <div class="kpi-empty"><i class="fas fa-chart-simple" aria-hidden="true"></i> {{ \App\Services\Kpi\KpiFormatter::UNAVAILABLE }} للفترة والنطاق المحددين.</div>
            @endif
        </section>
    @endforeach
</div>
@php($kpiChartPayload = collect($charts)->keyBy('id')->all())
<script type="application/json" id="kpi-chart-data">@json($kpiChartPayload)</script>
