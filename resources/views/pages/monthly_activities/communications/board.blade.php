@extends('layouts.app')

@push('styles')
@php
    $communicationsBoardCss = public_path('assets/css/communications-board.min.css');
    $communicationsBoardCssVersion = is_file($communicationsBoardCss) ? filemtime($communicationsBoardCss) : time();
@endphp
<link rel="stylesheet" href="{{ asset('assets/css/communications-board.min.css') }}?v={{ $communicationsBoardCssVersion }}">
@endpush

@section('content')
<div class="container-fluid py-4" dir="rtl">
    <section class="comm-board-hero p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div><div class="small text-info fw-bold mb-1">متابعة أعمال قسم الاتصال</div><h1 class="h4 mb-1">Calendar / Kanban للطلبات المؤكدة</h1><p class="mb-0 text-white-50">تعرض فقط الطلبات المعتمدة لقسم الاتصال، لمتابعة التحضير والتجهيز والإغلاق.</p></div>
            @if(auth()->user()?->hasAnyRole(['communication_head', 'super_admin']))<a class="btn btn-light" href="{{ route('role.programs.communications_requests.index') }}">قرارات قسم الاتصال</a>@endif
        </div>
    </section>

    <form method="GET" class="comm-filter-card p-3 mb-4 row g-2 align-items-end">
        <div class="col-md-3"><label class="form-label">اختر الفرع</label><select class="form-select" name="branch_id"><option value="">كل الفروع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected($filters['branch_id']===$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">الشهر</label><select class="form-select" name="month">@for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($filters['month']===$m)>{{ \Carbon\Carbon::create(null,$m,1)->locale('ar')->monthName }}</option>@endfor</select></div>
        <div class="col-md-2"><label class="form-label">السنة</label><input class="form-control" type="number" name="year" value="{{ $filters['year'] }}"></div>
        <div class="col-md-3"><label class="form-label">الحالة</label><select class="form-select" name="status"><option value="all">كل الحالات</option>@foreach(['approved','preparing','ready','in_progress','completed','closed'] as $st)<option value="{{ $st }}" @selected($filters['status']===$st)>{{ $statusLabels[$st] }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">تطبيق</button></div>
    </form>

    <ul class="nav nav-pills mb-3" role="tablist"><li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#comm-kanban" type="button">Kanban</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#comm-calendar" type="button">Calendar</button></li></ul>
    <div class="tab-content">
        <div class="tab-pane fade show active" id="comm-kanban">
            <div class="comm-kanban">
                @foreach($columns as $columnStatus => $columnItems)
                    <section class="comm-column"><div class="comm-column__head"><span>{{ $statusLabels[$columnStatus] }}</span><span>{{ $columnItems->count() }}</span></div>
                        @forelse($columnItems as $item)
                            @include('pages.monthly_activities.communications.partials.board-card', ['item' => $item, 'statusLabels' => $statusLabels])
                        @empty <div class="text-muted small p-3">لا توجد طلبات.</div> @endforelse
                    </section>
                @endforeach
            </div>
        </div>
        <div class="tab-pane fade" id="comm-calendar">
            <div class="comm-panel p-3"><div class="comm-calendar">
                @for($day=1;$day<=$daysInMonth;$day++)
                    @php($dateKey = $monthStart->copy()->day($day)->format('Y-m-d'))
                    <div class="comm-day"><div class="comm-day__num">{{ $day }}</div>
                        @foreach($calendarItems->get($dateKey, collect()) as $item)
                            <div class="comm-day__item"><strong>{{ $item['title'] }}</strong><br><span>{{ $item['branch'] }} • {{ $item['time'] }}</span></div>
                        @endforeach
                    </div>
                @endfor
            </div></div>
        </div>
    </div>
</div>
@endsection
