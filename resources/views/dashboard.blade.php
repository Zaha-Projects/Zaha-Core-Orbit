@extends('layouts.app')

@section('title', __('app.common.dashboard'))

@php
    $versionedAsset = static function (string $path): string {
        $absolutePath = public_path($path);
        $version = is_file($absolutePath) ? filemtime($absolutePath) : time();

        return asset($path) . '?v=' . $version;
    };
@endphp

@section('content')
    <section class="mb-4">
        <div class="card p-4">
            <h1 class="h4 mb-0">{{ __('app.common.dashboard') }}</h1>
        </div>
    </section>

    <section id="cardsSection" class="row g-3 mb-4">
        @forelse($cards ?? [] as $card)
            <div class="col-md-6 col-xl-3">
                <a href="{{ $card['url'] }}" class="text-decoration-none text-reset">
                    <div class="kpi-card stat-card-blue h-100">
                        <div class="kpi-head">
                            <p class="kpi-label mb-0">{{ $card['title'] }}</p>
                            <span class="kpi-icon"><i class="{{ $card['icon'] }}"></i></span>
                        </div>
                        <h3 class="kpi-value">{{ $card['value'] ?? $loop->iteration }}</h3>
                        <p class="kpi-delta">{{ $card['description'] }}</p>
                    </div>
                </a>
            </div>
        @empty
        @endforelse
    </section>

    <section class="row g-3">
            <div class="col-12">
                <div class="card dashboard-calendar-card p-3 p-lg-4" id="calendarSection">
                    <div class="dashboard-calendar-header mb-3 mb-lg-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                            <h2 class="h5 mb-0">{{ __('app.roles.relations.agenda.calendar.calendar_view') }}</h2>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <span class="dashboard-calendar-chip">{{ ($calendarEvents ?? collect())->count() }} فعالية سنوية</span>
                                <select id="dashboardCalendarViewFilter" class="form-select form-select-sm dashboard-calendar-filter" aria-label="فلترة عرض التقويم">
                                    <option value="dayGridMonth">عرض شهري</option>
                                    <option value="timeGridWeek">عرض أسبوعي</option>
                                    <option value="timeGridDay">عرض يومي</option>
                                </select>
                            </div>
                        </div>
                        <p class="dashboard-calendar-intro mb-0">تقويم عام (عرض فقط) يجمع الأجندة السنوية والخطط الشهرية لكل الفروع.</p>
                    </div>
                    <div class="dashboard-calendar-legend mb-3" id="dashboardCalendarLegend">
                        <span class="legend-item"><span class="legend-dot legend-dot--agenda"></span> أجندة سنوية</span>
                        <span class="legend-item"><span class="legend-dot legend-dot--monthly"></span> خطة شهرية</span>
                        <span class="legend-item"><span class="legend-dot legend-dot--owner"></span> الفرع المالك</span>
                        <span class="legend-item"><span class="legend-dot legend-dot--participant"></span> الفرع/الوحدة المشاركة</span>
                    </div>
                    <div class="dashboard-calendar-stats mb-3">
                        <div class="stat-pill stat-pill--total">
                            <span class="label"><i class="fas fa-database"></i> إجمالي السجلات</span>
                            <strong>{{ data_get($dashboardCalendarStats ?? [], 'total', 0) }}</strong>
                        </div>
                        <div class="stat-pill stat-pill--agenda">
                            <span class="label"><i class="fas fa-calendar-check"></i> فعاليات الأجندة</span>
                            <strong>{{ data_get($dashboardCalendarStats ?? [], 'agenda', 0) }}</strong>
                        </div>
                        <div class="stat-pill stat-pill--monthly">
                            <span class="label"><i class="fas fa-list-check"></i> الخطط الشهرية</span>
                            <strong>{{ data_get($dashboardCalendarStats ?? [], 'monthly', 0) }}</strong>
                        </div>
                        <div class="stat-pill stat-pill--branch">
                            <span class="label"><i class="fas fa-users"></i> الأكثر مشاركة</span>
                            <strong>{{ data_get($dashboardCalendarStats ?? [], 'top_branch_name', '—') }}</strong>
                            <small><i class="fas fa-hashtag"></i> {{ data_get($dashboardCalendarStats ?? [], 'top_branch_count', 0) }} سجل</small>
                        </div>
                    </div>
                    <div id="calendar"></div>
                    <div id="calendarFallback" class="alert alert-warning mt-3 d-none">
                        {{ app()->getLocale() === 'ar' ? 'تعذر تحميل التقويم.' : 'Calendar failed to load.' }}
                    </div>
                </div>
            </div>
        </section>
    <script type="application/json" id="dashboard-calendar-events-json">@json($calendarEvents ?? [])</script>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ $versionedAsset('assets/css/dashboard-calendar.css') }}">
@endpush
