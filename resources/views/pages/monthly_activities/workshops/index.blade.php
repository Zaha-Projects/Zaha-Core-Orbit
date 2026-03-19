@extends('layouts.app')

@section('content')
<div class="event-module">
    <div class="card event-card mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">متابعة طلبات المشاغل</h1>
            <p class="text-muted mb-0">اعرض جميع الفعاليات، فلتر الفعاليات التي تحتاج مشاغل، ثم أضف ملاحظاتك وحدّث حالة التنفيذ.</p>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card event-card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">فلتر الحالة</label>
                    <select class="form-select" name="status">
                        <option value="">الكل</option>
                        @foreach($statuses as $key => $label)
                            <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">نوع الفعاليات</label>
                    <select class="form-select" name="requires_workshops">
                        <option value="1" @selected($requiresWorkshops === '1')>الفعاليات التي تحتاج مشاغل فقط</option>
                        <option value="0" @selected($requiresWorkshops === '0')>كل الفعاليات المرتبطة بطلب مشاغل</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                    <button class="btn btn-outline-primary w-100" type="submit">تطبيق الفلتر</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card event-card">
        <div class="card-body">
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>الفعالية</th>
                            <th>التاريخ</th>
                            <th>الفرع</th>
                            <th>احتياج المشاغل</th>
                            <th>الحالة</th>
                            <th>الملاحظات</th>
                            <th class="text-end">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($requests as $requestItem)
                            @php $event = $requestItem->event; @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $requestItem->event_id, 'mode' => 'post']) }}">
                                        {{ $event->title ?? ('فعالية #'.$requestItem->event_id) }}
                                    </a>
                                    <div class="small text-muted mt-1">{{ $event?->short_description ?: 'لا يوجد وصف مختصر.' }}</div>
                                </td>
                                <td>{{ optional($event?->proposed_date)->format('Y-m-d') ?: '-' }}</td>
                                <td>{{ $event?->branch?->name ?? '-' }}</td>
                                <td>
                                    <div class="small">تحتاج مشاغل: {{ $event?->requires_workshops ? 'نعم' : 'لا' }}</div>
                                    <div class="small text-muted">احتياج تنفيذي: {{ $event?->volunteer_need ?: 'غير محدد' }}</div>
                                </td>
                                <td>
                                    <span class="event-status status-{{ $requestItem->status }}">{{ $statuses[$requestItem->status] ?? $requestItem->status }}</span>
                                </td>
                                <td class="text-muted">{{ $requestItem->notes ?: '—' }}</td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#workshop-request-{{ $requestItem->id }}">
                                        تحديث
                                    </button>
                                </td>
                            </tr>
                            <tr class="collapse" id="workshop-request-{{ $requestItem->id }}">
                                <td colspan="7">
                                    <form method="POST" action="{{ route('role.programs.workshops_requests.update', $requestItem) }}" class="row g-3">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-12 col-md-4">
                                            <label class="form-label">الحالة</label>
                                            <select class="form-select" name="status">
                                                @foreach($statuses as $statusKey => $statusLabel)
                                                    <option value="{{ $statusKey }}" @selected($requestItem->status === $statusKey)>{{ $statusLabel }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-8">
                                            <label class="form-label">ملاحظات للمسؤولين (طلب تعديل / توضيح)</label>
                                            <input class="form-control" type="text" name="notes" value="{{ $requestItem->notes }}" placeholder="اكتب الملاحظات أو المطلوب تعديله...">
                                        </div>
                                        <div class="col-12 d-flex justify-content-end">
                                            <button class="btn btn-outline-primary btn-sm" type="submit">حفظ التحديث</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-muted">لا توجد طلبات مشاغل مطابقة للفلاتر.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
