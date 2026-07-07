@extends('layouts.app')

@push('styles')
@php
    $communicationsBoardCss = public_path('assets/css/communications-board.min.css');
    $communicationsBoardCssVersion = is_file($communicationsBoardCss) ? filemtime($communicationsBoardCss) : time();
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/communications-board.min.css') }}?v={{ $communicationsBoardCssVersion }}">
@endpush

@section('content')
<div class="container-fluid py-4 comm-page" dir="rtl" data-comm-board data-selected-year="{{ $filters['year'] }}" data-selected-month="{{ $filters['month'] }}">
    <section class="comm-board-hero p-4 mb-4">
        <div class="position-relative d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <div class="comm-hero-kicker mb-2"><i class="fas fa-bullhorn"></i> متابعة أعمال قسم الاتصال</div>
                <h1 class="comm-hero-title h3 mb-2">Calendar / Kanban للطلبات المؤكدة</h1>
                <p class="mb-0 text-white-75">تعرض فقط الطلبات المعتمدة لقسم الاتصال لمتابعة التحضير، التجهيز، التنفيذ، والإغلاق.</p>
            </div>
            @if(auth()->user()?->hasAnyRole(['communication_head', 'super_admin']))
                <a class="comm-hero-action text-decoration-none" href="{{ route('role.programs.communications_requests.index') }}"><i class="fas fa-clipboard-check ms-1"></i> قرارات قسم الاتصال</a>
            @endif
        </div>
    </section>

    <form method="GET" class="comm-filter-card p-3 mb-4 row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-bold">اختر الفرع</label>
            <select class="form-select" name="branch_id"><option value="">كل الفروع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($filters['branch_id']===$branch->id)>{{ $branch->name }}</option>@endforeach</select>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-bold">الشهر والسنة</label>
            <input class="form-control" type="text" data-comm-board-filter-picker placeholder="اختر شهر" value="{{ $monthStart->format('Y-m') }}">
            <input type="hidden" name="month" value="{{ $filters['month'] }}" data-comm-board-month>
            <input type="hidden" name="year" value="{{ $filters['year'] }}" data-comm-board-year>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-bold">الحالة</label>
            <select class="form-select" name="status"><option value="all">كل الحالات</option>@foreach(['approved','preparing','ready','in_progress','completed','closed'] as $st)<option value="{{ $st }}" @selected($filters['status']===$st)>{{ $statusLabels[$st] }}</option>@endforeach</select>
        </div>
        <div class="col-md-2"><button class="btn btn-theme w-100 fw-bold">تطبيق الفلاتر</button></div>
    </form>

    <ul class="nav nav-pills comm-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#comm-kanban" type="button"><i class="fas fa-table-columns ms-1"></i>Kanban</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#comm-calendar" type="button"><i class="fas fa-calendar-days ms-1"></i>Calendar</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="comm-kanban">
            <div class="comm-kanban">
                @foreach($columns as $columnStatus => $columnItems)
                    <section class="comm-column">
                        <div class="comm-column__head"><span>{{ $statusLabels[$columnStatus] }}</span><span class="comm-column__count">{{ $columnItems->count() }}</span></div>
                        @forelse($columnItems as $item)
                            @include('pages.monthly_activities.communications.partials.board-card', ['item' => $item, 'statusLabels' => $statusLabels])
                        @empty
                            <div class="text-muted small p-3">لا توجد طلبات في هذه الحالة.</div>
                        @endforelse
                    </section>
                @endforeach
            </div>
        </div>
        <div class="tab-pane fade" id="comm-calendar">
            <div class="comm-panel p-3">
                <div class="agenda-calendar-toolbar comm-calendar-toolbar mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-comm-calendar-nav="prev">السابق</button>
                    <h2 class="h6 mb-0" data-comm-calendar-title>{{ $monthStart->copy()->locale('ar')->translatedFormat('F Y') }}</h2>
                    <div class="d-flex align-items-center gap-2 calendar-picker-wrap">
                        <input type="text" class="form-control form-control-sm calendar-month-input" data-comm-calendar-picker placeholder="اختر شهر" value="{{ $monthStart->format('Y-m') }}">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-comm-calendar-nav="next">التالي</button>
                    </div>
                </div>
                <div class="comm-calendar">
                    @for($day=1;$day<=$daysInMonth;$day++)
                        @php($dateKey = $monthStart->copy()->day($day)->format('Y-m-d'))
                        <div class="comm-day">
                            <div class="comm-day__num">{{ $day }}</div>
                            @foreach($calendarItems->get($dateKey, collect()) as $item)
                                <div class="comm-day__item"><strong>{{ $item['title'] }}</strong><br><span>{{ $item['branch'] }} • {{ $item['time'] }}</span></div>
                            @endforeach
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


@push('scripts')
<script src="{{ \App\Support\AssetVersion::url('assets/js/pages/pages-monthly_activities-communications-board.min.js') }}"></script>
@endpush
