@extends('layouts.app')
@section('content')
<div class="container py-4">
    <div class="card ramadan-hero shadow-sm mb-4"><div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div><div class="small opacity-75">تقويم التخطيط المعتمد</div><h1 class="h3 mb-1">تقويم إفطارات رمضان</h1>@if($period)<div>{{ $period['start']->format('Y-m-d') }} — {{ $period['end']->format('Y-m-d') }}</div>@endif</div>
        <a class="btn btn-light ramadan-no-print" href="{{ route('events.ramadan.iftars.index') }}"><i class="fas fa-grip"></i> عرض البطاقات</a>
    </div></div>
    <form method="GET" class="mb-4 d-flex gap-2 align-items-end">
        <div>
            <label for="calendar-year" class="form-label">السنة الميلادية</label>
            <select id="calendar-year" name="year" class="form-select">
                @foreach($years as $year)
                    <option value="{{ $year }}" {{ $selectedYear === (int) $year ? 'selected' : '' }}>
                        {{ $year }} {{ $periods->firstWhere('year', $year)?->is_active ? '' : '— أرشيف / غير فعالة' }}
                    </option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">عرض السنة</button>
    </form>
    @if($season && ! $season->is_active)
        <div class="alert alert-info">هذه الفترة غير فعالة للحجوزات الجديدة. الإفطارات السابقة متاحة للعرض.</div>
    @endif
    @if(!$period)
        <div class="alert alert-warning"><i class="fas fa-circle-exclamation"></i> لا توجد فترة محددة أو إفطارات مسجلة لهذه السنة.</div>
    @else
        <div class="ramadan-calendar" aria-label="تقويم فترة رمضان">
        @foreach($period['start']->toPeriod($period['end']) as $day)
            @php($dayRows = $iftars->get($day->format('Y-m-d'), collect()))
            <section class="ramadan-day" aria-label="{{ $day->format('Y-m-d') }}">
                <div class="ramadan-day__date">{{ $day->locale(app()->getLocale())->translatedFormat('D d M') }}</div>
                @forelse($dayRows as $iftar)
                    <a class="ramadan-calendar-event" href="{{ route('events.ramadan.iftars.show',$iftar) }}"><strong>{{ $iftar->title }}</strong><br><span>{{ optional($iftar->branch)->name }} · {{ $iftar->expected_attendance }} مستفيد</span></a>
                @empty <div class="small text-muted">لا توجد إفطارات</div> @endforelse
            </section>
        @endforeach
        </div>
    @endif
</div>
@endsection
