@extends('layouts.app')

@section('page_title', 'ملاحظات ما بعد التنفيذ')
@section('page_breadcrumb', 'ملاحظات ما بعد التنفيذ')

@section('content')
    <div class="card stretch stretch-full mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">ملاحظات ما بعد التنفيذ</h1>
            <p class="text-muted mb-0">تعرض هذه الصفحة الأنشطة التي تم رفض ما بعد التنفيذ لها أو طلب توضيح عليها، حتى يتمكن مسؤول التطوع في الفرع من مراجعة السبب وإعادة تعبئة ما بعد التنفيذ.</p>
        </div>
    </div>

    <div class="row g-3">
        @forelse($activities as $activity)
            @php
                $review = data_get($activity->post_execution_payload ?? [], 'review', []);
                $isClarification = ($review['decision'] ?? null) === 'clarification';
            @endphp
            <div class="col-12 col-lg-6">
                <div class="card h-100 stretch stretch-full">
                    <div class="card-body d-flex flex-column gap-3">
                        <div class="d-flex justify-content-between gap-2 flex-wrap">
                            <div>
                                <h2 class="h6 mb-1">{{ $activity->title }}</h2>
                                <div class="small text-muted">{{ $activity->branch?->name ?? '-' }} / {{ optional($activity->actual_date)->format('Y-m-d') ?? '-' }}</div>
                            </div>
                            <span class="badge {{ $isClarification ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger' }}">
                                {{ $isClarification ? 'طلب توضيح' : 'مرفوض' }}
                            </span>
                        </div>

                        <div class="alert {{ $isClarification ? 'alert-warning' : 'alert-danger' }} mb-0">
                            <strong>السبب:</strong>
                            <div>{{ $review['comment'] ?? '-' }}</div>
                            <small class="d-block mt-2 text-muted">بواسطة: {{ $review['reviewed_by_name'] ?? '-' }} / {{ $review['reviewed_at'] ?? '-' }}</small>
                        </div>

                        <div class="d-flex gap-2 mt-auto flex-wrap">
                            <a class="btn btn-primary" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post']) }}">إعادة تعبئة ما بعد التنفيذ</a>
                            <a class="btn btn-outline-secondary" href="{{ route('role.relations.activities.show', $activity) }}">عرض التفاصيل</a>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card stretch stretch-full"><div class="card-body text-muted">لا توجد ملاحظات ما بعد تنفيذ حالياً.</div></div>
            </div>
        @endforelse
    </div>

    <div class="mt-4">{{ $activities->links() }}</div>
@endsection
