{{--
    Shared user-performance panel (الأداء). Used by My Profile, the admin user page and
    /dashboard/kpi/users/{user}. Params: $performance (KpiUserPerformanceService::performance),
    $actionsRoute + $actionsParameters (per-user action report route), $filters, $years, $months.
--}}
@once
    @push('styles')
        <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
    @endpush
@endonce
<section class="kpi-dashboard kpi-user-performance" dir="rtl" data-kpi-dashboard aria-labelledby="kpi-performance-title">
    <div class="kpi-section__head">
        <h2 id="kpi-performance-title"><i class="fas fa-chart-line" aria-hidden="true"></i> الأداء</h2>
        <p>{{ $performance['user']->name }} — {{ $filters['period']->label() }} (مقارنة بـ {{ $filters['period']->previous()->label() }})</p>
    </div>

    <form method="GET" action="{{ url()->current() }}" class="kpi-filters" aria-label="فترة الأداء">
        <div class="kpi-filters__field">
            <label for="kpi-perf-year" class="form-label">السنة</label>
            <select id="kpi-perf-year" name="year" class="form-select">
                @foreach ($years as $yearOption)
                    <option value="{{ $yearOption }}" @if($filters['year'] === $yearOption) selected @endif>{{ $yearOption }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="kpi-perf-month" class="form-label">الشهر</label>
            <select id="kpi-perf-month" name="month" class="form-select">
                <option value="">كامل السنة</option>
                @foreach ($months as $monthNumber => $monthLabel)
                    <option value="{{ $monthNumber }}" @if($filters['month'] === $monthNumber) selected @endif>{{ $monthLabel }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__actions">
            <button type="submit" class="btn btn-primary"><i class="fas fa-filter" aria-hidden="true"></i> عرض</button>
        </div>
    </form>

    <p class="kpi-card__note"><i class="fas fa-circle-info" aria-hidden="true"></i> تُعرض المؤشرات مؤشراً مؤشراً حسب الدور؛ لا توجد درجة أو تقييم إجمالي للموظف حتى اعتماد أوزان رسمية.</p>
    @if ($performance['scope_note'])
        <p class="kpi-card__note"><i class="fas fa-code-branch" aria-hidden="true"></i> {{ $performance['scope_note'] }}</p>
    @endif

    @if ($performance['sections'] === [])
        <div class="kpi-empty"><i class="fas fa-user" aria-hidden="true"></i> لا توجد مؤشرات أداء مرتبطة بأدوار هذا المستخدم حالياً.</div>
    @endif

    @foreach ($performance['sections'] as $section)
        <section class="kpi-section" aria-labelledby="kpi-family-{{ $section['key'] }}">
            <div class="kpi-section__head">
                <h3 id="kpi-family-{{ $section['key'] }}"><i class="fas {{ $section['icon'] }}" aria-hidden="true"></i> {{ $section['label'] }}</h3>
            </div>
            <div class="kpi-grid">
                @foreach ($section['results'] as $result)
                    @include('pages.kpi.partials.kpi-card', ['result' => $result])
                @endforeach
            </div>
            <nav class="kpi-next mt-3" aria-label="تقارير إجراءات {{ $section['label'] }}">
                @foreach ($section['reports'] as $action)
                    <a class="kpi-next__link" href="{{ route($actionsRoute, $actionsParameters + ['action' => $action] + \Illuminate\Support\Arr::except($filters['query'], ['module', 'branch_id'])) }}">
                        <i class="fas {{ \App\Services\Kpi\KpiReportService::USER_ACTIONS[$action]['icon'] }}" aria-hidden="true"></i> {{ \App\Services\Kpi\KpiReportService::USER_ACTIONS[$action]['title'] }}
                    </a>
                @endforeach
            </nav>
            @include('pages.kpi.partials.unavailable-list', ['unavailable' => $section['unavailable'], 'unavailableId' => 'kpi-unavailable-'.$section['key']])
        </section>
    @endforeach

    @if ($performance['charts'] !== [])
        <section class="kpi-section" aria-label="تحليلات الأداء">
            @include('pages.kpi.partials.charts', ['charts' => $performance['charts']])
        </section>
    @endif
</section>
@once
    @push('scripts')
        @include('layouts.app.partials.chartjs')
        <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/kpi-dashboard.js') }}"></script>
    @endpush
@endonce
