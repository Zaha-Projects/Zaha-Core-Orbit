@extends('layouts.app')

@push('styles')
@php
    $returnedFeedbackCss = public_path('assets/css/monthly-returned-feedback.min.css');
    $returnedFeedbackCssVersion = is_file($returnedFeedbackCss) ? filemtime($returnedFeedbackCss) : time();
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/monthly-returned-feedback.min.css') }}?v={{ $returnedFeedbackCssVersion }}">
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
                    $isHighlighted = request('activity_id') && str_contains($item['url'], (string) request('activity_id'));
                    $itemDate = $item['date'] instanceof \Carbon\CarbonInterface ? $item['date']->format('Y-m-d H:i') : ($item['date'] ?: '-');
                @endphp
                <article class="returned-feedback-item returned-feedback-item--{{ $item['type'] ?? 'approval' }} {{ $isHighlighted ? 'is-highlighted' : '' }}">
                    <div class="returned-feedback-item__icon"><i class="{{ $typeIcons[$item['type'] ?? 'approval'] ?? 'fas fa-message' }}" aria-hidden="true"></i></div>
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
