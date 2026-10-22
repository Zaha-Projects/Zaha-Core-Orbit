{{-- KPI page header. Params: $title, $subtitle, $icon, $filters (optional), $accent (optional) --}}
<header class="kpi-hero {{ isset($accent) ? 'kpi-hero--'.$accent : '' }}">
    <div class="kpi-hero__content">
        <span class="kpi-hero__eyebrow"><i class="fas fa-chart-line" aria-hidden="true"></i> مؤشرات الأداء والتقارير</span>
        <h1 class="kpi-hero__title"><i class="fas {{ $icon }}" aria-hidden="true"></i> {{ $title }}</h1>
        <p class="kpi-hero__subtitle">{{ $subtitle }}</p>
        @isset($filters)
            <div class="kpi-hero__chips">
                <span class="kpi-chip"><i class="fas fa-calendar" aria-hidden="true"></i> {{ $filters['period']->label() }}</span>
                <span class="kpi-chip"><i class="fas fa-code-branch" aria-hidden="true"></i> {{ $filters['scope']->label() }}</span>
                <span class="kpi-chip"><i class="fas fa-code-compare" aria-hidden="true"></i> مقارنة بـ {{ $filters['period']->previous()->label() }}</span>
            </div>
        @endisset
    </div>
</header>
