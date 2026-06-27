@extends('layouts.app')

@section('content')
<div class="container-fluid py-4" dir="rtl">
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="text-primary fw-bold small mb-1">قرارات قسم الاتصال</div>
                <h1 class="h4 mb-1">طلبات تحتاج قرار رئيس قسم الاتصال</h1>
                <p class="text-muted mb-0">تظهر هنا فقط طلبات التصوير/التغطية/الدعوات التي تحتاج اعتمادًا أو رفضًا أو طلب تعديل.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('role.programs.communications_requests.board') }}">فتح المتابعة Calendar / Kanban</a>
        </div>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['pending' => 'بانتظار القرار', 'changes_requested' => 'يحتاج تعديل', 'rejected' => 'مرفوض', 'all' => 'الكل'] as $key => $label)
            <a class="btn btn-sm {{ $status === $key ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('role.programs.communications_requests.index', ['status' => $key]) }}">
                {{ $label }}
                <span class="badge bg-light text-dark ms-1">{{ $key === 'all' ? $decisionCounts->sum() : ($decisionCounts[$key] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <div class="row g-3">
        @forelse($requests as $request)
            @php($event = $request->event)
            <div class="col-12 col-xl-6">
                <article class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between gap-2 mb-2">
                            <div>
                                <div class="small text-muted">{{ $event?->branch?->name ?? '-' }} • {{ optional($event?->proposed_date)->format('Y-m-d') ?? '-' }}</div>
                                <h2 class="h5 fw-bold mb-0">{{ $event?->title ?? '#'.$request->event_id }}</h2>
                            </div>
                            <span class="badge bg-warning text-dark align-self-start">{{ $statusLabels[$request->status] ?? $request->status }}</span>
                        </div>
                        <div class="mb-3">
                            @foreach(app(\App\Http\Controllers\Web\MonthlyActivities\CommunicationsRequestsController::class)->requirementsFor($event) as $requirement)
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2 me-1 mb-1">{{ $requirement }}</span>
                            @endforeach
                        </div>
                        <div class="bg-light rounded-3 p-3 mb-3" style="white-space: pre-wrap;">{{ $event?->media_coverage_notes ?: ($request->notes ?: 'لا توجد تفاصيل إضافية.') }}</div>
                        <form method="POST" action="{{ route('role.programs.communications_requests.update', $request) }}" enctype="multipart/form-data" class="row g-2">
                            @csrf
                            @method('PUT')
                            <div class="col-12"><textarea class="form-control" name="notes" rows="2" placeholder="سبب الرفض أو التعديل / ملاحظات الاعتماد">{{ old('notes', $request->notes) }}</textarea></div>
                            <div class="col-12"><input class="form-control" type="file" name="media_files[]" multiple></div>
                            <div class="col-12 d-flex flex-wrap gap-2 justify-content-end">
                                <button class="btn btn-success btn-sm" name="decision" value="approved">اعتماد الطلب</button>
                                <button class="btn btn-outline-warning btn-sm" name="decision" value="changes_requested">طلب تعديل</button>
                                <button class="btn btn-outline-danger btn-sm" name="decision" value="rejected">رفض الطلب</button>
                            </div>
                        </form>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="alert alert-light border text-center">لا توجد طلبات تحتاج قرارًا حاليًا.</div></div>
        @endforelse
    </div>
    <div class="mt-3">{{ $requests->links() }}</div>
</div>
@endsection
