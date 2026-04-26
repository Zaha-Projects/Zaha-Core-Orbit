@section('page_title', $title)
@section('page_breadcrumb', $title)

<div class="event-module">
    <div class="event-card card stretch-full">
        <div class="card-body p-4">
            <div class="event-header">
                <div>
                    <h1 class="h4 mb-2">{{ $title }}</h1>
                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                </div>
            </div>

            <div class="event-kpi-grid mb-4">
                <div class="event-kpi-card">
                    <div class="text-muted small">{{ __('app.common.open_section') }}</div>
                    <div class="event-kpi-value">{{ collect($actions)->whereNotNull('link')->count() }}</div>
                </div>
                <div class="event-kpi-card">
                    <div class="text-muted small">{{ __('app.common.total') }}</div>
                    <div class="event-kpi-value">{{ count($actions) }}</div>
                </div>
            </div>

            <div class="row g-3">
                @foreach ($actions as $action)
                    <div class="col-12 col-md-6 col-xl-3">
                        <div class="event-card p-3 h-100 d-flex flex-column">
                            <h2 class="h6 mb-2">{{ $action['title'] }}</h2>
                            <p class="text-muted mb-0 flex-grow-1">{{ $action['description'] }}</p>
                            @if (!empty($action['link']))
                                <a class="btn btn-primary mt-3" href="{{ $action['link'] }}">
                                    {{ __('app.common.open_section') }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="relations-dashboard-yearly mt-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="h6 mb-0">الأجندة السنوية (عرض فقط)</h2>
                    <span class="text-muted small">{{ $year ?? now()->year }}</span>
                </div>
                <div class="relations-dashboard-yearly-grid">
                    @foreach (range(1, 12) as $monthNumber)
                        @php
                            $monthEvents = collect($agendaYearOverview[$monthNumber] ?? []);
                            $monthTitle = \Carbon\Carbon::createFromDate(($year ?? now()->year), $monthNumber, 1)->translatedFormat('F');
                        @endphp
                        <section class="relations-dashboard-month-card">
                            <header class="relations-dashboard-month-head">
                                <span class="fw-semibold">{{ $monthTitle }}</span>
                                <span class="badge bg-light text-dark">{{ $monthEvents->count() }}</span>
                            </header>
                            <div class="relations-dashboard-month-body">
                                @forelse($monthEvents as $event)
                                    @php
                                        $eventDate = optional($event->event_date)->format('Y-m-d')
                                            ?: sprintf('%d-%02d-%02d', (int) ($year ?? now()->year), (int) $monthNumber, (int) $event->day);
                                        $statusLabel = match ((string) $event->status) {
                                            'approved', 'published' => 'معتمد',
                                            'submitted', 'in_review' => 'قيد المراجعة',
                                            'rejected' => 'مرفوض',
                                            default => 'مسودة',
                                        };
                                    @endphp
                                    <article class="relations-dashboard-event-chip" aria-label="تفاصيل فعالية {{ $event->event_name }}">
                                        <span class="relations-dashboard-event-chip__day">{{ optional($event->event_date)->format('d') ?? sprintf('%02d', (int) $event->day) }}</span>
                                        <div class="relations-dashboard-event-chip__content">
                                            <div class="relations-dashboard-event-chip__title">{{ $event->event_name }}</div>
                                            <div class="relations-dashboard-event-chip__meta">التاريخ: {{ $eventDate }}</div>
                                            <div class="relations-dashboard-event-chip__meta">الجهة: {{ $event->department?->name ?? '—' }}</div>
                                            <div class="relations-dashboard-event-chip__meta">الفئة: {{ $event->eventCategory?->name ?? ($event->event_category ?: '—') }}</div>
                                            <div class="relations-dashboard-event-chip__meta">نوع الفعالية: {{ $event->event_type === 'mandatory' ? 'إلزامية' : 'اختيارية' }}</div>
                                            <div class="relations-dashboard-event-chip__meta">الخطة: {{ $event->plan_type === 'unified' ? 'موحدة' : 'خاصة بالفرع' }}</div>
                                            @if (filled($event->notes))
                                                <div class="relations-dashboard-event-chip__meta">ملاحظات: {{ \Illuminate\Support\Str::limit($event->notes, 80) }}</div>
                                            @endif
                                            <span class="relations-dashboard-event-chip__status">{{ $statusLabel }}</span>
                                        </div>
                                    </article>
                                @empty
                                    <div class="text-muted small">لا توجد فعاليات</div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/relations-dashboard.css') }}">
@endpush
