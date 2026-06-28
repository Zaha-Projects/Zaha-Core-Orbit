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
                        <input type="text" class="form-control form-control-sm" style="max-width: 140px;" data-comm-calendar-picker placeholder="اختر شهر" value="{{ $monthStart->format('Y-m') }}">
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
<script>
(function () {
    const board = document.querySelector('[data-comm-board]');
    if (!board) return;

    const form = board.querySelector('form.comm-filter-card');
    const monthInput = form?.querySelector('[data-comm-board-month]');
    const yearInput = form?.querySelector('[data-comm-board-year]');
    const calendarPicker = board.querySelector('[data-comm-calendar-picker]');
    const filterPicker = board.querySelector('[data-comm-board-filter-picker]');
    const title = board.querySelector('[data-comm-calendar-title]');
    const selectedYear = Number.parseInt(board.dataset.selectedYear || '', 10) || new Date().getFullYear();
    const selectedMonth = Number.parseInt(board.dataset.selectedMonth || '', 10) || (new Date().getMonth() + 1);
    let currentDate = new Date(selectedYear, selectedMonth - 1, 1);

    function localizedMonthTitle(date) {
        return date.toLocaleDateString('ar-JO', { month: 'long', year: 'numeric' });
    }

    function setMonthAndSubmit(date) {
        if (!form || !monthInput || !yearInput) return;
        monthInput.value = String(date.getMonth() + 1);
        yearInput.value = String(date.getFullYear());
        form.submit();
    }

    function buildPickerConfig(onChange) {
        const monthSelectPluginFactory = window.monthSelectPlugin || window.flatpickr?.monthSelectPlugin;
        const config = {
            dateFormat: 'Y-m',
            altInput: true,
            altFormat: 'F Y',
            altInputClass: 'form-control form-control-sm calendar-month-input',
            defaultDate: currentDate,
            disableMobile: true,
            onChange(selectedDates) {
                const selectedDate = selectedDates?.[0];
                if (!selectedDate) return;
                onChange(new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1));
            },
        };

        if (typeof monthSelectPluginFactory === 'function') {
            config.plugins = [monthSelectPluginFactory({
                shorthand: false,
                dateFormat: 'Y-m',
                altFormat: 'F Y',
                theme: 'light',
            })];
        }

        return config;
    }

    board.querySelectorAll('[data-comm-calendar-nav]').forEach((button) => {
        button.addEventListener('click', () => {
            const delta = button.dataset.commCalendarNav === 'next' ? 1 : -1;
            currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + delta, 1);
            if (title) title.textContent = localizedMonthTitle(currentDate);
            setMonthAndSubmit(currentDate);
        });
    });

    if (typeof window.flatpickr === 'function') {
        if (calendarPicker) window.flatpickr(calendarPicker, buildPickerConfig(setMonthAndSubmit));
        if (filterPicker) window.flatpickr(filterPicker, buildPickerConfig(setMonthAndSubmit));
    }
})();
</script>
@endpush
