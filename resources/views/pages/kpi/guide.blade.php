@extends('layouts.app')

@section('title', 'دليل مؤشرات الأداء')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-dashboard.css') }}">
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/kpi-guide.css') }}">
@endpush

@section('content')
@php
    $statusMeta = config('kpis.statuses', []);
@endphp
<div class="kpi-dashboard kpi-guide" dir="rtl" data-kpi-guide>
    <header class="kpi-hero">
        <div class="kpi-hero__content">
            <span class="kpi-hero__eyebrow"><i class="fas fa-book-open" aria-hidden="true"></i> مؤشرات الأداء والتقارير</span>
            <h1 class="kpi-hero__title"><i class="fas fa-book-open" aria-hidden="true"></i> دليل مؤشرات الأداء</h1>
            <p class="kpi-hero__subtitle">كل مؤشر في النظام: ماذا يقيس، كيف يُحسب، من أين تأتي بياناته، ولماذا قد يظهر غير متاح. الدليل مولَّد من تعريفات المؤشرات نفسها التي تستخدمها الشاشات.</p>
            <div class="kpi-hero__chips">
                <span class="kpi-chip"><i class="fas fa-list" aria-hidden="true"></i> {{ count($guide['entries']) }} مؤشراً</span>
                @foreach ($guide['filters']['statuses'] as $statusKey => $statusLabel)
                    <span class="kpi-chip"><i class="fas {{ $statusMeta[$statusKey]['icon'] ?? 'fa-circle' }}" aria-hidden="true"></i> {{ $statusLabel }}: {{ $guide['counts'][$statusKey] ?? 0 }}</span>
                @endforeach
            </div>
        </div>
    </header>

    <form class="kpi-filters kpi-guide__filters" role="search" aria-label="البحث في الدليل" data-guide-filters onsubmit="return false;">
        <div class="kpi-filters__field kpi-guide__search">
            <label for="guide-search" class="form-label">بحث</label>
            <input id="guide-search" type="search" class="form-control" placeholder="اسم المؤشر أو رمزه أو وصفه…" data-guide-search autocomplete="off">
        </div>
        <div class="kpi-filters__field">
            <label for="guide-module" class="form-label">نوع الفعالية</label>
            <select id="guide-module" class="form-select" data-guide-filter="modules">
                <option value="">الكل</option>
                @foreach ($guide['filters']['modules'] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="guide-page" class="form-label">المجموعة</label>
            <select id="guide-page" class="form-select" data-guide-filter="page">
                <option value="">الكل</option>
                @foreach ($guide['filters']['pages'] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="guide-family" class="form-label">الدور</label>
            <select id="guide-family" class="form-select" data-guide-filter="family">
                <option value="">الكل</option>
                @foreach ($guide['filters']['families'] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="guide-category" class="form-label">الفئة</label>
            <select id="guide-category" class="form-select" data-guide-filter="category">
                <option value="">الكل</option>
                @foreach ($guide['filters']['categories'] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__field">
            <label for="guide-status" class="form-label">حالة البيانات</label>
            <select id="guide-status" class="form-select" data-guide-filter="status">
                <option value="">الكل</option>
                @foreach ($guide['filters']['statuses'] as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="kpi-filters__actions">
            <button type="button" class="btn btn-outline-secondary" data-guide-reset>إعادة ضبط</button>
            <button type="button" class="btn btn-outline-primary" data-guide-expand aria-pressed="false">توسيع الكل</button>
        </div>
    </form>
    <p class="kpi-guide__count" role="status" aria-live="polite" data-guide-count></p>

    <div class="kpi-guide__list">
        @foreach ($guide['entries'] as $entry)
            <details class="kpi-guide-card kpi-guide-card--{{ strtolower($entry['status']) }}" id="kpi-guide-{{ \Illuminate\Support\Str::slug($entry['code']) }}"
                data-guide-card
                data-modules="{{ implode(' ', $entry['module_keys']) }}"
                data-page="{{ $entry['page'] }}"
                data-family="{{ $entry['family'] }}"
                data-category="{{ $entry['category'] }}"
                data-status="{{ $entry['status'] }}"
                data-search="{{ mb_strtolower($entry['code'].' '.implode(' ', $entry['aliases']).' '.$entry['name'].' '.$entry['description']) }}">
                <summary class="kpi-guide-card__summary">
                    <span class="kpi-card__icon" aria-hidden="true"><i class="fas {{ $entry['icon'] }}"></i></span>
                    <span class="kpi-guide-card__title">
                        <strong>{{ $entry['name'] }}</strong>
                        <small class="kpi-card__code">{{ $entry['code'] }}{{ $entry['aliases'] ? ' · '.implode('، ', $entry['aliases']) : '' }}</small>
                    </span>
                    <span class="kpi-status kpi-status--{{ strtolower($entry['status']) }}"><i class="fas {{ $statusMeta[$entry['status']]['icon'] ?? 'fa-circle' }}" aria-hidden="true"></i> {{ $statusMeta[$entry['status']]['label_ar'] ?? $entry['status'] }}</span>
                </summary>

                <div class="kpi-guide-card__body">
                    <p class="kpi-guide-card__description">{{ $entry['description'] }}</p>
                    <dl class="kpi-guide-card__facts">
                        <div><dt>المجموعة</dt><dd>{{ $entry['page_label'] }}</dd></div>
                        <div><dt>الفئة</dt><dd>{{ $entry['category_label'] }}</dd></div>
                        @if ($entry['family_label'])<div><dt>الدور</dt><dd>{{ $entry['family_label'] }}</dd></div>@endif
                        <div><dt>لمن يظهر؟</dt><dd>{{ implode('، ', $entry['roles']) }}</dd></div>
                        <div><dt>الوحدة</dt><dd>{{ $entry['unit'] }}</dd></div>
                        <div><dt>المستهدف</dt><dd>{{ $entry['target'] }}</dd></div>
                        @if ($entry['proposed_target'])<div><dt>مستهدف مقترح</dt><dd>{{ $entry['proposed_target'] }}</dd></div>@endif
                        @if ($entry['drilldown'])<div><dt>التفاصيل</dt><dd>{{ $entry['drilldown'] }}</dd></div>@endif
                    </dl>

                    @if ($entry['formula'])
                        <section class="kpi-guide-formula" aria-label="طريقة الحساب">
                            <h3><i class="fas fa-square-root-variable" aria-hidden="true"></i> طريقة الحساب</h3>
                            <p class="kpi-guide-formula__expr">{{ $entry['formula'] }}</p>
                            @if ($entry['numerator'] || $entry['denominator'])
                                <div class="kpi-guide-formula__parts">
                                    @if ($entry['numerator'])<span><b>البسط:</b> {{ $entry['numerator'] }}</span>@endif
                                    @if ($entry['denominator'])<span><b>المقام:</b> {{ $entry['denominator'] }}</span>@endif
                                </div>
                            @endif
                            @if ($entry['example'])
                                <div class="kpi-guide-example">
                                    <strong>مثال توضيحي (أرقام افتراضية):</strong>
                                    @foreach ($entry['example']['inputs'] as $label => $number)
                                        <span>{{ $label }} = {{ $number }}</span>
                                    @endforeach
                                    <span class="kpi-guide-example__result">النتيجة: {{ $entry['example']['result'] }}</span>
                                </div>
                            @endif
                        </section>
                    @endif

                    <section class="kpi-guide-section" aria-label="كيف أقرأ هذا المؤشر">
                        <h3><i class="fas fa-eye" aria-hidden="true"></i> كيف أقرأ هذا المؤشر؟</h3>
                        <p>{{ $entry['how_to_read'] }}</p>
                    </section>

                    <section class="kpi-guide-section" aria-label="مصدر البيانات">
                        <h3><i class="fas fa-database" aria-hidden="true"></i> مصدر البيانات</h3>
                        <ul class="kpi-guide-modules">
                            @foreach ($entry['modules'] as $module)
                                <li>
                                    <span class="kpi-status kpi-status--{{ strtolower($module['status']) }}">{{ $statusMeta[$module['status']]['label_ar'] ?? $module['status'] }}</span>
                                    <strong>{{ $module['label'] }}</strong>
                                    @if ($module['formula'])<div class="kpi-guide-modules__formula">{{ $module['formula'] }}</div>@endif
                                    @foreach ($module['sources'] as $source)
                                        <div class="kpi-guide-modules__source">
                                            <span><b>الجداول:</b> <code dir="ltr">{{ $source['tables'] }}</code></span>
                                            <span><b>التاريخ المعتمد:</b> <code dir="ltr">{{ $source['timestamp'] }}</code></span>
                                            @if ($source['actor'])<span><b>منفّذ الإجراء:</b> <code dir="ltr">{{ $source['actor'] }}</code></span>@endif
                                        </div>
                                    @endforeach
                                    @if ($module['reason'])<p class="kpi-guide-modules__reason">{{ $module['reason'] }}</p>@endif
                                </li>
                            @endforeach
                        </ul>
                    </section>

                    @if ($entry['limitations'] !== [])
                        <section class="kpi-guide-section" aria-label="حدود المؤشر">
                            <h3><i class="fas fa-circle-exclamation" aria-hidden="true"></i> حدود المؤشر</h3>
                            <ul>@foreach ($entry['limitations'] as $limitation)<li>{{ $limitation }}</li>@endforeach</ul>
                        </section>
                    @endif

                    <section class="kpi-guide-section" aria-label="لماذا قد يظهر غير متاح">
                        <h3><i class="fas fa-circle-question" aria-hidden="true"></i> لماذا قد يظهر غير متاح؟</h3>
                        <ul>@foreach ($entry['why_unavailable'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
                    </section>
                </div>
            </details>
        @endforeach
    </div>
    <div class="kpi-empty" data-guide-empty hidden><i class="fas fa-magnifying-glass" aria-hidden="true"></i> لا توجد مؤشرات تطابق البحث أو التصفية.</div>
</div>
@endsection

@push('scripts')
    <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/kpi-guide.js') }}"></script>
@endpush
