@extends('layouts.app')

@push('styles')
<style>
.returned-feedback-page { direction: rtl; }
.returned-feedback-hero {
    background: radial-gradient(circle at top right, rgba(59, 130, 246, .24), transparent 34%), linear-gradient(135deg, #0f172a 0%, #1d4ed8 58%, #0891b2 100%);
    border: 0;
    border-radius: 1.35rem;
    box-shadow: 0 22px 55px rgba(15, 23, 42, .18);
    color: #fff;
    overflow: hidden;
    position: relative;
}
.returned-feedback-hero:after {
    background: rgba(255, 255, 255, .08);
    border-radius: 999px;
    content: "";
    height: 220px;
    left: -75px;
    position: absolute;
    top: -90px;
    width: 220px;
}
.returned-feedback-hero__content { position: relative; z-index: 1; }
.returned-feedback-eyebrow { color: #bfdbfe; font-size: .82rem; font-weight: 800; letter-spacing: .02em; }
.returned-feedback-hero h1 { font-size: clamp(1.45rem, 2.4vw, 2.25rem); font-weight: 900; }
.returned-feedback-hero p { color: #dbeafe; line-height: 1.9; }
.returned-feedback-hero__actions { align-items: center; display: flex; flex-wrap: wrap; gap: .6rem; justify-content: flex-end; }
.returned-feedback-hero__action { background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.28); color: #fff; }
.returned-feedback-hero__action:hover { background: #fff; color: #1d4ed8; }
.returned-feedback-stats { display: grid; gap: .85rem; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); }
.returned-feedback-stat {
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.22);
    border-radius: 1rem;
    padding: .9rem 1rem;
}
.returned-feedback-stat span { color: #bfdbfe; display: block; font-size: .78rem; font-weight: 800; }
.returned-feedback-stat strong { display: block; font-size: 1.6rem; font-weight: 900; margin-top: .2rem; }
.returned-feedback-shell { display: grid; gap: 1.1rem; grid-template-columns: 290px minmax(0, 1fr); }
.returned-feedback-filter-card,
.returned-feedback-list-card {
    background: #fff;
    border: 1px solid #e2e8f0;
    border-radius: 1.2rem;
    box-shadow: 0 16px 40px rgba(15, 23, 42, .06);
}
.returned-feedback-filter-card { align-self: start; overflow: hidden; position: sticky; top: 1rem; }
.returned-feedback-filter-card__head { background: linear-gradient(180deg, #f8fbff 0%, #eff6ff 100%); border-bottom: 1px solid #dbeafe; padding: 1rem; }
.returned-feedback-filter-card__body { display: grid; gap: .6rem; padding: 1rem; }
.returned-feedback-filter {
    align-items: center;
    border: 1px solid #e2e8f0;
    border-radius: .95rem;
    color: #334155;
    display: grid;
    gap: .2rem .65rem;
    grid-template-columns: 38px minmax(0, 1fr) auto;
    padding: .7rem;
    text-decoration: none;
    transition: .18s ease;
}
.returned-feedback-filter:hover,
.returned-feedback-filter.is-active { background: #eff6ff; border-color: #93c5fd; color: #1d4ed8; transform: translateY(-1px); }
.returned-feedback-filter__icon { align-items: center; background: #e0f2fe; border-radius: .8rem; display: inline-flex; height: 38px; justify-content: center; width: 38px; }
.returned-feedback-filter__label { font-weight: 900; }
.returned-feedback-filter__hint { color: #64748b; font-size: .73rem; grid-column: 2 / 4; }
.returned-feedback-filter__count { background: #0f172a; border-radius: 999px; color: #fff; font-size: .78rem; font-weight: 900; min-width: 2rem; padding: .2rem .55rem; text-align: center; }
.returned-feedback-list-card { overflow: hidden; }
.returned-feedback-list-card__head { align-items: center; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; flex-wrap: wrap; gap: .75rem; justify-content: space-between; padding: 1rem; }
.returned-feedback-item {
    border-bottom: 1px solid #e2e8f0;
    display: grid;
    gap: 1rem;
    grid-template-columns: auto minmax(0, 1fr) auto;
    padding: 1rem;
    position: relative;
}
.returned-feedback-item:last-child { border-bottom: 0; }
.returned-feedback-item.is-highlighted { background: linear-gradient(90deg, #eff6ff 0%, #fff 80%); }
.returned-feedback-item__icon {
    align-items: center;
    border-radius: 1rem;
    display: inline-flex;
    font-size: 1.1rem;
    height: 52px;
    justify-content: center;
    width: 52px;
}
.returned-feedback-item--approval .returned-feedback-item__icon { background: #dbeafe; color: #1d4ed8; }
.returned-feedback-item--delete .returned-feedback-item__icon { background: #fee2e2; color: #dc2626; }
.returned-feedback-item--edit .returned-feedback-item__icon { background: #fef3c7; color: #d97706; }
.returned-feedback-item--execution_need .returned-feedback-item__icon { background: #ede9fe; color: #7c3aed; }
.returned-feedback-item--post_execution .returned-feedback-item__icon { background: #dcfce7; color: #16a34a; }
.returned-feedback-item__meta { color: #64748b; display: flex; flex-wrap: wrap; font-size: .82rem; font-weight: 800; gap: .45rem .8rem; margin-bottom: .35rem; }
.returned-feedback-item__title { color: #0f172a; font-size: 1.05rem; font-weight: 900; margin: 0 0 .75rem; }
.returned-feedback-reason { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: .95rem; color: #334155; line-height: 1.85; padding: .8rem .9rem; white-space: pre-wrap; }
.returned-feedback-item__side { align-items: flex-end; display: flex; flex-direction: column; gap: .55rem; min-width: 150px; }
.returned-feedback-status { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 999px; color: #9a3412; font-size: .78rem; font-weight: 900; padding: .28rem .7rem; }
.returned-feedback-date { color: #64748b; font-size: .78rem; font-weight: 800; }
.returned-feedback-empty { display: grid; justify-items: center; padding: 3rem 1rem; text-align: center; }
.returned-feedback-empty__icon { align-items: center; background: #eff6ff; border-radius: 1.25rem; color: #2563eb; display: inline-flex; font-size: 1.7rem; height: 64px; justify-content: center; margin-bottom: 1rem; width: 64px; }
@media (max-width: 991.98px) { .returned-feedback-shell { grid-template-columns: 1fr; } .returned-feedback-filter-card { position: static; } }
@media (max-width: 767.98px) { .returned-feedback-item { grid-template-columns: 1fr; } .returned-feedback-item__side { align-items: stretch; min-width: 0; } .returned-feedback-hero__actions { justify-content: flex-start; } }
[data-theme="dark"] .returned-feedback-filter-card,
[data-theme="dark"] .returned-feedback-list-card,
[data-theme="dark"] .returned-feedback-reason { background: var(--surface-bg); border-color: var(--border-color); color: var(--text-color); }
[data-theme="dark"] .returned-feedback-filter-card__head,
[data-theme="dark"] .returned-feedback-list-card__head { background: var(--surface-soft); border-color: var(--border-color); }
[data-theme="dark"] .returned-feedback-item { border-color: var(--border-color); }
[data-theme="dark"] .returned-feedback-item__title { color: var(--text-color); }
</style>
@endpush

@section('content')
@php
    $filterDefinitions = [
        'all' => ['label' => 'كل الراجع', 'hint' => 'عرض كامل للطلبات الراجعة', 'icon' => 'fas fa-inbox'],
        'approval' => ['label' => 'اعتماد الخطة', 'hint' => 'رفض أو طلب تعديل على الخطة', 'icon' => 'fas fa-clipboard-check'],
        'delete' => ['label' => 'حذف مرفوض', 'hint' => 'طلبات حذف لم يتم اعتمادها', 'icon' => 'fas fa-trash-alt'],
        'edit' => ['label' => 'تعديل مرفوض', 'hint' => 'طلبات تعديل لم يتم اعتمادها', 'icon' => 'fas fa-pen-to-square'],
        'execution_need' => ['label' => 'احتياجات التنفيذ', 'hint' => 'احتياجات لم يتم تأمينها', 'icon' => 'fas fa-list-check'],
        'post_execution' => ['label' => 'ما بعد التنفيذ', 'hint' => 'رفض أو طلب توضيح بعد التنفيذ', 'icon' => 'fas fa-rotate-left'],
    ];
    $typeIcons = collect($filterDefinitions)->mapWithKeys(fn ($definition, $key) => [$key => $definition['icon']]);
    $totalReturned = $counts['all'] ?? $items->count();
@endphp

<div class="container-fluid returned-feedback-page">
    <section class="returned-feedback-hero mb-4 p-4 p-lg-5">
        <div class="returned-feedback-hero__content">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <div class="returned-feedback-eyebrow mb-2"><i class="fas fa-reply-all me-1" aria-hidden="true"></i> مركز متابعة الملاحظات الراجعة</div>
                    <h1 class="mb-3">طلبات التعديل والرفض الراجعة للفرع</h1>
                    <p class="mb-0">واجهة موحدة لمسؤول العلاقات لقراءة السبب، تحديد نوع الطلب، ومعالجة الفرصة بسرعة من نفس المكان.</p>
                </div>
                <div class="col-lg-5">
                    <div class="returned-feedback-stats mb-3">
                        <div class="returned-feedback-stat"><span>إجمالي الراجع</span><strong>{{ $totalReturned }}</strong></div>
                        <div class="returned-feedback-stat"><span>اعتماد الخطة</span><strong>{{ $counts['approval'] ?? 0 }}</strong></div>
                        <div class="returned-feedback-stat"><span>ما بعد التنفيذ</span><strong>{{ $counts['post_execution'] ?? 0 }}</strong></div>
                    </div>
                    <div class="returned-feedback-hero__actions">
                        <a class="btn returned-feedback-hero__action btn-sm" href="{{ route('role.relations.activities.index') }}"><i class="fas fa-layer-group me-1" aria-hidden="true"></i> كل الفرص</a>
                        @if($activityId)
                            <a class="btn returned-feedback-hero__action btn-sm" href="{{ route('role.relations.activities.returned_feedback') }}"><i class="fas fa-filter-circle-xmark me-1" aria-hidden="true"></i> إزالة فلتر الإشعار</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($activityId)
        <div class="alert alert-info border-0 shadow-sm mb-4"><i class="fas fa-bell me-1" aria-hidden="true"></i> تم فتح هذه الصفحة من الإشعار، لذلك يتم إبراز الملاحظات المرتبطة بالفرصة المحددة.</div>
    @endif

    <div class="returned-feedback-shell">
        <aside class="returned-feedback-filter-card">
            <div class="returned-feedback-filter-card__head">
                <h2 class="h6 fw-bold mb-1">تصفية الراجع</h2>
                <div class="small text-muted">اختر نوع الطلب للتركيز على الإجراء المطلوب.</div>
            </div>
            <div class="returned-feedback-filter-card__body">
                @foreach($filterDefinitions as $filterKey => $definition)
                    @php($isActive = $type === $filterKey)
                    <a class="returned-feedback-filter {{ $isActive ? 'is-active' : '' }}"
                       href="{{ route('role.relations.activities.returned_feedback', array_filter(['type' => $filterKey === 'all' ? null : $filterKey, 'activity_id' => $activityId])) }}">
                        <span class="returned-feedback-filter__icon"><i class="{{ $definition['icon'] }}" aria-hidden="true"></i></span>
                        <span class="returned-feedback-filter__label">{{ $definition['label'] }}</span>
                        <span class="returned-feedback-filter__count">{{ $counts[$filterKey] ?? 0 }}</span>
                        <span class="returned-feedback-filter__hint">{{ $definition['hint'] }}</span>
                    </a>
                @endforeach
            </div>
        </aside>

        <section class="returned-feedback-list-card">
            <div class="returned-feedback-list-card__head">
                <div>
                    <h2 class="h5 fw-bold mb-1">{{ $filterDefinitions[$type]['label'] ?? 'كل الراجع' }}</h2>
                    <div class="small text-muted">{{ $items->count() }} نتيجة ضمن الفلتر الحالي</div>
                </div>
                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">الأحدث أولاً</span>
            </div>

            @forelse($items as $item)
                @php
                    $itemType = $item['type'] ?? 'approval';
                    $isHighlighted = request('activity_id') && str_contains($item['url'], (string) request('activity_id'));
                    $itemDate = $item['date'] instanceof \Carbon\CarbonInterface ? $item['date']->format('Y-m-d H:i') : ($item['date'] ?: '-');
                @endphp
                <article class="returned-feedback-item returned-feedback-item--{{ $itemType }} {{ $isHighlighted ? 'is-highlighted' : '' }}">
                    <div class="returned-feedback-item__icon"><i class="{{ $typeIcons[$itemType] ?? 'fas fa-message' }}" aria-hidden="true"></i></div>
                    <div>
                        <div class="returned-feedback-item__meta">
                            <span>{{ $item['type_label'] }}</span>
                            @if(!empty($item['branch']))<span><i class="fas fa-building me-1" aria-hidden="true"></i>{{ $item['branch'] }}</span>@endif
                            <span><i class="fas fa-user-check me-1" aria-hidden="true"></i>{{ $item['actor'] ?: 'غير محدد' }}</span>
                        </div>
                        <h3 class="returned-feedback-item__title">{{ $item['title'] }}</h3>
                        <div class="returned-feedback-reason">{{ $item['reason'] ?: 'لم يتم تسجيل سبب تفصيلي.' }}</div>
                    </div>
                    <div class="returned-feedback-item__side">
                        <span class="returned-feedback-status">{{ $item['status'] }}</span>
                        <span class="returned-feedback-date"><i class="far fa-clock me-1" aria-hidden="true"></i>{{ $itemDate }}</span>
                        @if($item['url'] !== '#')
                            <a class="btn btn-primary btn-sm w-100" href="{{ $item['url'] }}">فتح الفرصة</a>
                        @endif
                    </div>
                </article>
            @empty
                <div class="returned-feedback-empty">
                    <span class="returned-feedback-empty__icon"><i class="fas fa-circle-check" aria-hidden="true"></i></span>
                    <h2 class="h5 fw-bold">لا توجد طلبات راجعة حالياً</h2>
                    <p class="text-muted mb-0">عند رفض طلب أو إرجاع فرصة للتعديل سيظهر هنا مع السبب والإجراء المطلوب.</p>
                </div>
            @endforelse
        </section>
    </div>
</div>
@endsection
