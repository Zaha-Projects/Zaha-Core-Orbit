@extends('layouts.app')

@push('styles')
@php
    $communicationsBoardCss = public_path('assets/css/communications-board.min.css');
    $communicationsBoardCssVersion = is_file($communicationsBoardCss) ? filemtime($communicationsBoardCss) : time();
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/communications-board.min.css') }}?v={{ $communicationsBoardCssVersion }}">
@endpush

@section('content')
<div class="container-fluid py-4 comm-page" dir="rtl">
    <section class="comm-decision-hero p-4 mb-4">
        <div class="position-relative d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="comm-hero-kicker mb-2"><i class="fas fa-camera-retro"></i> قرارات قسم الاتصال</div>
                <h1 class="comm-hero-title h3 mb-2">طلبات التصوير والتغطية التي تحتاج قرارًا</h1>
                <p class="mb-0 text-white-75">هذه الصفحة مخصصة لرئيس قسم الاتصال فقط لاتخاذ قرار واضح: اعتماد، رفض، أو طلب تعديل مع شرح السبب.</p>
            </div>
            <a class="comm-hero-action text-decoration-none" href="{{ route('role.programs.communications_requests.board') }}">
                <i class="fas fa-table-columns ms-1"></i> فتح متابعة Calendar / Kanban
            </a>
        </div>
    </section>

    <div class="comm-stats mb-4">
        @foreach(['pending' => 'بانتظار القرار', 'changes_requested' => 'يحتاج تعديل', 'rejected' => 'مرفوض', 'all' => 'إجمالي الطلبات'] as $key => $label)
            <a class="comm-stat-card text-decoration-none" href="{{ route('role.programs.communications_requests.index', ['status' => $key]) }}">
                <span>{{ $label }}</span>
                <strong>{{ $key === 'all' ? $decisionCounts->sum() : ($decisionCounts[$key] ?? 0) }}</strong>
            </a>
        @endforeach
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach(['pending' => 'بانتظار القرار', 'changes_requested' => 'يحتاج تعديل', 'rejected' => 'مرفوض', 'all' => 'الكل'] as $key => $label)
            <a class="comm-filter-pill {{ $status === $key ? 'active' : '' }}" href="{{ route('role.programs.communications_requests.index', ['status' => $key]) }}">
                {{ $label }} <span class="ms-1">{{ $key === 'all' ? $decisionCounts->sum() : ($decisionCounts[$key] ?? 0) }}</span>
            </a>
        @endforeach
    </div>

    <div class="row g-3">
        @forelse($requests as $request)
            @php
                $event = $request->event;
                $requirements = $request->computed_requirements ?? [];
                $statusClass = $request->status === 'rejected' ? 'is-danger' : ($request->status === 'pending' ? 'is-warning' : '');
            @endphp
            <div class="col-12 col-xl-6">
                <article class="comm-request-card bg-white h-100 d-flex">
                    <div class="comm-request-card__accent flex-shrink-0"></div>
                    <div class="p-3 p-lg-4 flex-grow-1">
                        <div class="d-flex justify-content-between gap-3 mb-3">
                            <div>
                                <div class="small text-muted fw-bold mb-1"><i class="fas fa-location-dot ms-1"></i>{{ $event?->branch?->name ?? '-' }} • {{ optional($event?->proposed_date)->format('Y-m-d') ?? '-' }} • {{ trim(($event?->time_from ?: '').($event?->time_to ? ' - '.$event->time_to : '')) ?: '-' }}</div>
                                <h2 class="h5 fw-bold mb-0">{{ $event?->title ?? '#'.$request->event_id }}</h2>
                            </div>
                            <span class="comm-status-badge {{ $statusClass }} align-self-start">{{ $statusLabels[$request->status] ?? $request->status }}</span>
                        </div>

                        <div class="mb-3">
                            @forelse($requirements as $requirement)
                                <span class="comm-badge me-1 mb-1"><i class="fas fa-check-circle ms-1"></i>{{ $requirement }}</span>
                            @empty
                                <span class="comm-badge me-1 mb-1">مطلوب من قسم الاتصال</span>
                            @endforelse
                        </div>

                        <div class="comm-detail-box p-3 mb-3">{{ $event?->media_coverage_notes ?: ($request->notes ?: 'لا توجد تفاصيل إضافية لهذا الطلب.') }}</div>

                        <form method="POST" action="{{ route('role.programs.communications_requests.update', $request) }}" enctype="multipart/form-data" class="row g-2">
                            @csrf
                            @method('PUT')
                            <div class="col-12">
                                <label class="form-label fw-bold small">ملاحظات القرار</label>
                                <textarea class="form-control" name="notes" rows="3" placeholder="اكتب سبب الرفض أو التعديل، أو ملاحظات الاعتماد والتنفيذ">{{ old('notes', $request->notes) }}</textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-bold small">مرفقات داعمة إن وجدت</label>
                                <input class="form-control" type="file" name="media_files[]" multiple>
                            </div>
                            <div class="col-12 d-flex flex-wrap gap-2 justify-content-end pt-2">
                                <button class="btn btn-success" name="decision" value="approved"><i class="fas fa-check ms-1"></i>اعتماد الطلب</button>
                                <button class="btn btn-outline-warning" name="decision" value="changes_requested"><i class="fas fa-pen ms-1"></i>طلب تعديل</button>
                                <button class="btn btn-outline-danger" name="decision" value="rejected"><i class="fas fa-xmark ms-1"></i>رفض الطلب</button>
                            </div>
                        </form>
                    </div>
                </article>
            </div>
        @empty
            <div class="col-12"><div class="comm-panel p-5 text-center text-muted">لا توجد طلبات تحتاج قرارًا حاليًا.</div></div>
        @endforelse
    </div>
    <div class="mt-3">{{ $requests->links() }}</div>
</div>
@endsection
