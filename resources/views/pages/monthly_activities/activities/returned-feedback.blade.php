@extends('layouts.app')

@section('content')
<div class="container-fluid" dir="rtl">
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h1 class="h4 mb-1">الطلبات الراجعة لمسؤول العلاقات</h1>
                <p class="text-muted mb-0">كل طلبات التعديل والرفض التي رجعت للفرع من رئيس الفرع أو منسق الفروع أو جهات الاعتماد.</p>
            </div>
            <a class="btn btn-outline-secondary btn-sm" href="{{ route('role.relations.activities.index') }}">العودة للفرص</a>
        </div>
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 mb-3">
                @foreach([
                    'all' => 'الكل',
                    'approval' => 'اعتماد الخطة',
                    'delete' => 'طلبات الحذف المرفوضة',
                    'edit' => 'طلبات الاعتماد/التعديل المرفوضة',
                    'execution_need' => 'احتياجات التنفيذ المرفوضة',
                    'post_execution' => 'اعتمادات ما بعد التنفيذ المرفوضة',
                ] as $filterKey => $filterLabel)
                    <a class="btn btn-sm {{ $type === $filterKey ? 'btn-primary' : 'btn-outline-primary' }}"
                       href="{{ route('role.relations.activities.returned_feedback', array_filter(['type' => $filterKey === 'all' ? null : $filterKey, 'activity_id' => $activityId])) }}">
                        {{ $filterLabel }}
                        <span class="badge bg-light text-dark ms-1">{{ $counts[$filterKey] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            @if($activityId)
                <div class="alert alert-info">تم فتح هذه الصفحة من الإشعار لعرض كل الملاحظات الراجعة المرتبطة بالفرصة المحددة.</div>
            @endif

            <div class="row g-3">
                @forelse($items as $item)
                    <div class="col-12">
                        <article class="card border {{ request('activity_id') && str_contains($item['url'], (string) request('activity_id')) ? 'border-primary' : '' }}">
                            <div class="card-body">
                                <div class="d-flex justify-content-between gap-3 flex-wrap">
                                    <div>
                                        <div class="small text-muted mb-1">{{ $item['type_label'] }} @if(!empty($item['branch'])) • {{ $item['branch'] }} @endif</div>
                                        <h2 class="h5 mb-2">{{ $item['title'] }}</h2>
                                    </div>
                                    <span class="badge bg-warning text-dark align-self-start">{{ $item['status'] }}</span>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-md-4"><strong>صاحب القرار:</strong> {{ $item['actor'] ?: '-' }}</div>
                                    <div class="col-md-4"><strong>تاريخ القرار:</strong> {{ $item['date'] instanceof \Carbon\CarbonInterface ? $item['date']->format('Y-m-d H:i') : ($item['date'] ?: '-') }}</div>
                                </div>

                                <div class="border rounded-3 p-3 bg-light">
                                    <strong class="d-block mb-2">السبب / التعديل المطلوب</strong>
                                    <div style="white-space: pre-wrap;">{{ $item['reason'] ?: 'لم يتم تسجيل سبب تفصيلي.' }}</div>
                                </div>
                            </div>
                            <div class="card-footer bg-white d-flex justify-content-end">
                                @if($item['url'] !== '#')
                                    <a class="btn btn-sm btn-primary" href="{{ $item['url'] }}">فتح الفرصة</a>
                                @endif
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-light border text-center mb-0">لا توجد طلبات تعديل أو رفض راجعة ضمن هذا النطاق.</div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
