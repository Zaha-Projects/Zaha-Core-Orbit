@extends('layouts.app')

@section('title', 'دليل الموقع والأدوار')

@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/site-role-guide.css') }}">
@endpush

@section('content')
@php
    $categoryLabels = [
        'leadership' => 'القيادة والاعتماد',
        'operations' => 'التخطيط والتشغيل',
        'review' => 'المتابعة والتقييم',
        'support' => 'الخدمات المساندة',
        'administration' => 'إدارة النظام',
    ];
    $capabilityLabels = [
        'create' => ['إنشاء', 'fa-plus'],
        'edit' => ['تعديل', 'fa-pen'],
        'execute' => ['تنفيذ', 'fa-play'],
        'review' => ['مراجعة', 'fa-magnifying-glass'],
        'approve' => ['اعتماد', 'fa-check-double'],
        'close' => ['إغلاق', 'fa-lock'],
    ];
@endphp
<div class="site-role-guide" dir="rtl">
    <div class="container-fluid py-4 py-lg-5">
        <header class="role-guide-hero mb-4" aria-labelledby="role-guide-title">
            <div class="role-guide-hero__shape role-guide-hero__shape--one" aria-hidden="true"></div>
            <div class="role-guide-hero__shape role-guide-hero__shape--two" aria-hidden="true"></div>
            <div class="role-guide-hero__content">
                <span class="role-guide-eyebrow"><i class="fas fa-compass" aria-hidden="true"></i> مرجعك العملي داخل النظام</span>
                <h1 id="role-guide-title">دليل الموقع والأدوار</h1>
                <p>اعرف مسؤوليات كل دور، ما الذي يستلمه، وما الذي ينجزه، وإلى من تنتقل المهمة بعد ذلك.</p>
                <div class="role-guide-hero__stats" aria-label="ملخص الدليل">
                    <span><strong>{{ $guide->count() }}</strong> دوراً موثقاً</span>
                    <span><strong>{{ collect($categoryLabels)->count() }}</strong> مجموعات وظيفية</span>
                    <span><i class="fas fa-shield-halved" aria-hidden="true"></i> مستمد من الصلاحيات الفعلية</span>
                </div>
            </div>
            <div class="role-guide-hero__visual" aria-hidden="true">
                <div class="role-guide-orbit"><i class="fas fa-users-gear"></i><span></span><span></span><span></span></div>
            </div>
        </header>

        <section class="role-guide-navigator" aria-labelledby="guide-navigation-title">
            <div class="role-guide-navigator__heading">
                <div><span class="section-kicker">وصول سريع</span><h2 id="guide-navigation-title">ابحث أو اختر مجموعة</h2></div>
                <p id="guide-results" class="role-guide-results" aria-live="polite">عرض {{ $guide->count() }} دوراً</p>
            </div>
            <div class="role-guide-search-row">
                <div class="role-guide-search">
                    <i class="fas fa-magnifying-glass" aria-hidden="true"></i>
                    <label class="visually-hidden" for="guide-search">ابحث باسم الدور أو الوحدة أو المسؤولية</label>
                    <input id="guide-search" type="search" autocomplete="off" placeholder="ابحث: رمضان، التقييم، النقل..." aria-describedby="guide-search-help">
                    <button id="guide-search-clear" type="button" aria-label="مسح البحث" hidden><i class="fas fa-xmark" aria-hidden="true"></i></button>
                </div>
                <span id="guide-search-help" class="visually-hidden">تُحدّث النتائج مباشرة أثناء الكتابة</span>
                <div class="role-guide-select-wrap">
                    <i class="fas fa-user-tag" aria-hidden="true"></i>
                    <label class="visually-hidden" for="guide-role-filter">اختر دوراً</label>
                    <select id="guide-role-filter">
                        <option value="">كل الأدوار</option>
                        @foreach($guide as $role)<option value="{{ $role['key'] }}">{{ $role['title'] }}</option>@endforeach
                    </select>
                </div>
            </div>
            <div class="role-guide-tabs" role="group" aria-label="تصفية حسب المجموعة الوظيفية">
                <button class="is-active" type="button" data-category="" aria-pressed="true"><i class="fas fa-grid-2" aria-hidden="true"></i> الكل</button>
                @foreach($categoryLabels as $category => $label)
                    <button type="button" data-category="{{ $category }}" aria-pressed="false">{{ $label }}</button>
                @endforeach
            </div>
            <nav class="role-guide-jumps" aria-label="انتقال سريع إلى دور">
                @foreach($guide as $role)
                    <a href="#role-{{ $role['key'] }}" data-role-jump="{{ $role['key'] }}"><i class="fas {{ $role['presentation']['icon'] }}" aria-hidden="true"></i><span>{{ $role['title'] }}</span></a>
                @endforeach
            </nav>
        </section>

        <main class="role-guide-grid" id="guide-roles">
            @foreach($guide as $role)
                @php
                    $searchText = collect([$role['title'], $role['key'], $role['purpose'], ...$role['modules'], ...$role['responsibilities'], ...$role['monthly'], ...$role['ramadan']])->join(' ');
                    $accent = $role['presentation']['accent'];
                @endphp
                <article id="role-{{ $role['key'] }}" class="role-guide-card role-accent--{{ $accent }}" data-role="{{ $role['key'] }}" data-category="{{ $role['presentation']['category'] }}" data-search="{{ $searchText }}" style="--reveal-order: {{ $loop->index }}" tabindex="-1">
                    <div class="role-guide-card__topline" aria-hidden="true"></div>
                    <header class="role-guide-card__header">
                        <div class="role-guide-card__identity">
                            <span class="role-guide-card__icon"><i class="fas {{ $role['presentation']['icon'] }}" aria-hidden="true"></i></span>
                            <div><span class="role-guide-card__category">{{ $categoryLabels[$role['presentation']['category']] }}</span><h2>{{ $role['title'] }}</h2><code dir="ltr">{{ $role['key'] }}</code></div>
                        </div>
                        <p>{{ $role['purpose'] }}</p>
                        <div class="role-guide-modules" aria-label="الوحدات المستخدمة">
                            @foreach($role['modules'] as $module)<span><i class="fas fa-layer-group" aria-hidden="true"></i>{{ $module }}</span>@endforeach
                        </div>
                    </header>

                    <section class="role-capabilities" aria-label="ملخص القدرات">
                        @foreach($capabilityLabels as $capability => [$label, $icon])
                            <span class="{{ $role['capabilities'][$capability] ? 'is-enabled' : 'is-disabled' }}" title="{{ $role['capabilities'][$capability] ? 'متاح وفق الصلاحيات الحالية' : 'غير متاح وفق الصلاحيات الحالية' }}">
                                <i class="fas {{ $role['capabilities'][$capability] ? $icon : 'fa-minus' }}" aria-hidden="true"></i>{{ $label }}
                            </span>
                        @endforeach
                    </section>

                    <div class="role-guide-card__body">
                        <section class="role-guide-panel role-guide-panel--primary">
                            <h3><span><i class="fas fa-list-check" aria-hidden="true"></i></span>المهام الرئيسية</h3>
                            <ul class="role-checklist">@foreach($role['responsibilities'] as $item)<li><i class="fas fa-circle-check" aria-hidden="true"></i><span>{{ $item }}</span></li>@endforeach</ul>
                        </section>
                        <section class="role-guide-panel role-guide-panel--modules">
                            <h3><span><i class="fas fa-calendar-days" aria-hidden="true"></i></span>المهام حسب الوحدة</h3>
                            <div class="role-module-block"><strong><i class="fas fa-layer-group" aria-hidden="true"></i> الأنشطة الشهرية</strong><ul>@foreach($role['monthly'] as $item)<li>{{ $item }}</li>@endforeach</ul></div>
                            <div class="role-module-block role-module-block--ramadan"><strong><i class="fas fa-moon" aria-hidden="true"></i> إفطارات رمضان</strong><ul>@foreach($role['ramadan'] as $item)<li>{{ $item }}</li>@endforeach</ul></div>
                        </section>
                        <section class="role-guide-panel role-guide-panel--handoff">
                            <h3><span><i class="fas fa-arrow-right-arrow-left" aria-hidden="true"></i></span>مسار استلام وتسليم العمل</h3>
                            <div class="role-handoff">
                                <div><span class="role-handoff__icon"><i class="fas fa-inbox" aria-hidden="true"></i></span><div><strong>ما الذي أستلمه؟</strong>@foreach($role['receives'] as $item)<p>{{ $item }}</p>@endforeach</div></div>
                                <div><span class="role-handoff__icon"><i class="fas fa-gears" aria-hidden="true"></i></span><div><strong>ماذا أنجز؟</strong>@foreach($role['responsibilities'] as $item)<p>{{ $item }}</p>@endforeach</div></div>
                                <div><span class="role-handoff__icon"><i class="fas fa-share" aria-hidden="true"></i></span><div><strong>لمن أسلّم؟</strong>@foreach($role['hands_off'] as $item)<p>{{ $item }}</p>@endforeach</div></div>
                            </div>
                        </section>
                        <section class="role-guide-panel role-guide-panel--limits">
                            <h3><span><i class="fas fa-shield-halved" aria-hidden="true"></i></span>الحدود والتنبيهات</h3>
                            <ul>@foreach($role['restrictions'] as $item)<li>{{ $item }}</li>@endforeach</ul>
                            <div class="role-pages"><strong>الصفحات الرئيسية</strong>@foreach($role['pages'] as $page)<span>{{ $page }}</span>@endforeach</div>
                        </section>
                    </div>
                    <a class="role-guide-card__back" href="#guide-navigation-title"><i class="fas fa-arrow-up" aria-hidden="true"></i> العودة للاختيار</a>
                </article>
            @endforeach
        </main>

        <div id="guide-empty" class="role-guide-empty" role="status" hidden><i class="fas fa-magnifying-glass" aria-hidden="true"></i><h2>لا توجد نتائج مطابقة</h2><p>جرّب اسماً آخر للدور أو الوحدة أو امسح عوامل التصفية.</p><button type="button" data-reset-guide>عرض كل الأدوار</button></div>

        @if($showTechnicalReference)
            <section class="role-tech-reference" aria-labelledby="technical-reference-title">
                <header><span class="role-tech-reference__icon"><i class="fas fa-code" aria-hidden="true"></i></span><div><span>خاص بمدير النظام</span><h2 id="technical-reference-title">المرجع التقني للأدوار</h2><p>يعرض الصلاحيات الفعلية ومجموعات المسارات والمتحكمات لأغراض الدعم التقني.</p></div></header>
                <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>الدور</th><th>المفتاح</th><th>الصلاحيات الفعلية</th><th>مجموعات المسارات</th><th>المتحكمات/الوحدات</th></tr></thead><tbody>@foreach($guide as $role)<tr><td><span class="tech-role-icon"><i class="fas {{ $role['presentation']['icon'] }}" aria-hidden="true"></i></span>{{ $role['title'] }}</td><td><code dir="ltr">{{ $role['key'] }}</code></td><td><div class="tech-token-list" dir="ltr">@forelse($role['permissions'] as $permission)<span>{{ $permission }}</span>@empty<span>—</span>@endforelse</div></td><td><div class="tech-token-list" dir="ltr">@foreach($role['route_groups'] as $route)<span>{{ $route }}</span>@endforeach</div></td><td>{{ implode('، ', $role['controllers']) }}</td></tr>@endforeach</tbody></table></div>
            </section>
        @endif
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ \App\Support\AssetVersion::url('assets/js/site-role-guide.js') }}" defer></script>
@endpush
