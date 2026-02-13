@extends('layouts.app')

@section('content')
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">مؤشرات الأداء الشهرية (KPIs)</h1>
            <p class="text-muted mb-0">متابعة الالتزام الشهري، نسبة التعديلات، كفاءة الحشد، وتقييم المتابعة.</p>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">احتساب KPI من الخطة الشهرية</h2>
            <form method="GET" action="{{ route('role.reports.kpis.index') }}" class="row g-3 mb-3">
                <div class="col-12 col-md-2"><label class="form-label">السنة</label><input class="form-control" type="number" name="year" value="{{ $year }}" required></div>
                <div class="col-12 col-md-2"><label class="form-label">الشهر</label><input class="form-control" type="number" min="1" max="12" name="month" value="{{ $month }}" required></div>
                <div class="col-12 col-md-4"><label class="form-label">الفرع</label><select class="form-select" name="branch_id"><option value="">كل الفروع</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)$branchId === (string)$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                <div class="col-12 col-md-4"><label class="form-label">المركز</label><select class="form-select" name="center_id"><option value="">كل المراكز</option>@foreach($centers as $center)<option value="{{ $center->id }}" @selected((string)$centerId === (string)$center->id)>{{ $center->name }}</option>@endforeach</select></div>
                <div class="col-12 d-flex justify-content-end"><button class="btn btn-outline-secondary" type="submit">تحديث الاحتساب</button></div>
            </form>

            <div class="row g-2">
                <div class="col-6 col-md-3"><div class="border rounded p-2">داخل الخطة: <strong>{{ $computedKpi['planned_activities_count'] }}</strong></div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-2">خارج الخطة: <strong>{{ $computedKpi['unplanned_activities_count'] }}</strong></div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-2">نسبة التعديل: <strong>{{ $computedKpi['modification_rate_percent'] ?? '-' }}%</strong></div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-2">التزام الخطة: <strong>{{ $computedKpi['plan_commitment_percent'] ?? '-' }}%</strong></div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-2">كفاءة الحشد: <strong>{{ $computedKpi['mobilization_efficiency_percent'] ?? '-' }}%</strong></div></div>
                <div class="col-6 col-md-3"><div class="border rounded p-2">تقييم الفرع: <strong>{{ $computedKpi['branch_monthly_score'] ?? '-' }}%</strong></div></div>
            </div>
        </div>
    </div>

    @if (auth()->user()->hasRole('followup_officer'))
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">إدخال/تحديث KPI شهري</h2>
                <form method="POST" action="{{ route('role.reports.kpis.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12 col-md-2"><label class="form-label">السنة</label><input class="form-control" type="number" name="year" value="{{ $year }}" required></div>
                    <div class="col-12 col-md-2"><label class="form-label">الشهر</label><input class="form-control" type="number" min="1" max="12" name="month" value="{{ $month }}" required></div>
                    <div class="col-12 col-md-4"><label class="form-label">الفرع</label><select class="form-select" name="branch_id"><option value="">عام</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" @selected((string)$branchId === (string)$branch->id)>{{ $branch->name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-4"><label class="form-label">المركز</label><select class="form-select" name="center_id"><option value="">عام</option>@foreach($centers as $center)<option value="{{ $center->id }}" @selected((string)$centerId === (string)$center->id)>{{ $center->name }}</option>@endforeach</select></div>
                    <div class="col-6 col-md-2"><label class="form-label">داخل الخطة</label><input class="form-control" type="number" min="0" name="planned_activities_count" value="{{ $computedKpi['planned_activities_count'] }}" required></div>
                    <div class="col-6 col-md-2"><label class="form-label">خارج الخطة</label><input class="form-control" type="number" min="0" name="unplanned_activities_count" value="{{ $computedKpi['unplanned_activities_count'] }}" required></div>
                    <div class="col-6 col-md-2"><label class="form-label">نسبة التعديل %</label><input class="form-control" type="number" min="0" max="100" name="modification_rate_percent" value="{{ $computedKpi['modification_rate_percent'] }}"></div>
                    <div class="col-6 col-md-2"><label class="form-label">التزام الخطة %</label><input class="form-control" type="number" min="0" max="100" name="plan_commitment_percent" value="{{ $computedKpi['plan_commitment_percent'] }}"></div>
                    <div class="col-6 col-md-2"><label class="form-label">كفاءة الحشد %</label><input class="form-control" type="number" min="0" max="100" name="mobilization_efficiency_percent" value="{{ $computedKpi['mobilization_efficiency_percent'] }}"></div>
                    <div class="col-6 col-md-2"><label class="form-label">تقييم الفرع %</label><input class="form-control" type="number" min="0" max="100" name="branch_monthly_score" value="{{ $computedKpi['branch_monthly_score'] }}"></div>
                    <div class="col-12 col-md-2"><label class="form-label">تقييم المتابعة %</label><input class="form-control" type="number" min="0" max="100" name="followup_commitment_score"></div>
                    <div class="col-12 col-md-10"><label class="form-label">ملاحظات</label><input class="form-control" name="notes"></div>
                    <div class="col-12 d-flex justify-content-end"><button class="btn btn-primary" type="submit">حفظ KPI</button></div>
                </form>
            </div>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>الفترة</th><th>الفرع/المركز</th><th>داخل/خارج الخطة</th><th>نسبة التعديل</th><th>التزام الخطة</th><th>كفاءة الحشد</th><th>تقييم الفرع</th><th>تقييم المتابعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($kpis as $kpi)
                            <tr>
                                <td>{{ $kpi->year }}-{{ str_pad($kpi->month, 2, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $kpi->branch?->name ?? 'عام' }} / {{ $kpi->center?->name ?? 'عام' }}</td>
                                <td>{{ $kpi->planned_activities_count }} / {{ $kpi->unplanned_activities_count }}</td>
                                <td>{{ $kpi->modification_rate_percent ?? '-' }}%</td>
                                <td>{{ $kpi->plan_commitment_percent ?? '-' }}%</td>
                                <td>{{ $kpi->mobilization_efficiency_percent ?? '-' }}%</td>
                                <td>{{ $kpi->branch_monthly_score ?? '-' }}%</td>
                                <td>{{ $kpi->followup_commitment_score ?? '-' }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-muted">لا توجد بيانات KPI بعد.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
