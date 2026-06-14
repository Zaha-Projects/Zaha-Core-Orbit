@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h1 class="h4 mb-1">سلة محذوفات الأنشطة الشهرية</h1>
            <p class="text-muted mb-0">تعرض هذه الصفحة الأنشطة الشهرية المحذوفة حذفًا ناعمًا فقط.</p>
        </div>
        <a class="btn btn-outline-secondary" href="{{ route('role.relations.activities.index') }}">العودة للأنشطة</a>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label">الفرع</label>
                    <select class="form-select" name="branch_id">
                        <option value="">كل الفروع</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) ($filters['branch_id'] ?? '') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100" type="submit">تصفية</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>عنوان النشاط</th>
                        <th>الفرع</th>
                        <th>أنشئ بواسطة / المسؤول</th>
                        <th>حذف بواسطة</th>
                        <th>تاريخ الحذف</th>
                        <th>سبب الحذف</th>
                        <th>حالة/مرجع طلب الحذف</th>
                        <th>حالة الاعتماد الأصلية</th>
                        <th class="text-end">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($activities as $activity)
                        @php
                            $deleteRequest = $activity->deleteRequests->sortByDesc('requested_at')->first();
                            $deleteLog = $deletedBy->get($activity->id);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $activity->title }}</td>
                            <td>{{ $activity->branch?->name ?? '-' }}</td>
                            <td>{{ $activity->creator?->name ?? $activity->responsible_party ?? '-' }}</td>
                            <td>{{ $deleteLog?->performer?->name ?? $deleteRequest?->currentApprover?->name ?? '-' }}</td>
                            <td>{{ optional($activity->deleted_at)->format('Y-m-d H:i') }}</td>
                            <td>{{ $deleteRequest?->reason ?? $deleteLog?->notes ?? '-' }}</td>
                            <td>{{ $deleteRequest ? ('#'.$deleteRequest->id.' / '.$deleteRequest->status) : '-' }}</td>
                            <td>{{ $activity->executive_approval_status ?: $activity->status ?: '-' }}</td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-2">
                                    <button class="btn btn-sm btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#deletedMonthlyActivityModal{{ $activity->id }}">عرض</button>
                                    @if(auth()->user()?->hasAnyRole(['super_admin', 'admin']))
                                        <form method="POST" action="{{ route('role.relations.activities.trash.restore', $activity->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="btn btn-sm btn-outline-success" type="submit">استعادة</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-muted text-center py-4">لا توجد أنشطة شهرية محذوفة.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $activities->links('pagination::bootstrap-5') }}</div>
    </div>

    @foreach($activities as $activity)
        @php
            $deleteRequest = $activity->deleteRequests->sortByDesc('requested_at')->first();
            $deleteLog = $deletedBy->get($activity->id);
        @endphp
        <div class="modal fade" id="deletedMonthlyActivityModal{{ $activity->id }}" tabindex="-1" aria-labelledby="deletedMonthlyActivityModalLabel{{ $activity->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <h2 class="modal-title h5" id="deletedMonthlyActivityModalLabel{{ $activity->id }}">{{ $activity->title }}</h2>
                            <p class="text-muted small mb-0">تفاصيل مختصرة للنشاط المحذوف دون فتح صفحة النشاط الكاملة.</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><strong>الفرع:</strong> {{ $activity->branch?->name ?? '-' }}</div>
                            <div class="col-md-6"><strong>أنشئ بواسطة / المسؤول:</strong> {{ $activity->creator?->name ?? $activity->responsible_party ?? '-' }}</div>
                            <div class="col-md-6"><strong>تاريخ النشاط:</strong> {{ $activity->activity_date ? optional($activity->activity_date)->format('Y-m-d') : sprintf('%02d/%02d/%04d', $activity->day, $activity->month, $activity->year) }}</div>
                            <div class="col-md-6"><strong>تاريخ الحذف:</strong> {{ optional($activity->deleted_at)->format('Y-m-d H:i') ?? '-' }}</div>
                            <div class="col-md-6"><strong>حذف بواسطة:</strong> {{ $deleteLog?->performer?->name ?? $deleteRequest?->currentApprover?->name ?? '-' }}</div>
                            <div class="col-md-6"><strong>حالة/مرجع طلب الحذف:</strong> {{ $deleteRequest ? ('#'.$deleteRequest->id.' / '.$deleteRequest->status) : '-' }}</div>
                            <div class="col-md-6"><strong>حالة الاعتماد الأصلية:</strong> {{ $activity->executive_approval_status ?: $activity->status ?: '-' }}</div>
                            <div class="col-md-6"><strong>نسخة الخطة:</strong> {{ (int) ($activity->plan_version ?: 1) }}</div>
                            <div class="col-12">
                                <strong>سبب الحذف:</strong>
                                <div class="border rounded bg-light p-3 mt-2">{{ $deleteRequest?->reason ?? $deleteLog?->notes ?? '-' }}</div>
                            </div>
                            <div class="col-12">
                                <strong>الوصف:</strong>
                                <div class="border rounded bg-light p-3 mt-2">{{ $activity->short_description ?: $activity->description ?: 'لا يوجد وصف.' }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

</div>
@endsection
