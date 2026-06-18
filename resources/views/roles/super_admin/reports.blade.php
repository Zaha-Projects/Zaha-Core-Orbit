@extends('layouts.app')

@php
    $title = __('app.reports.title');
    $subtitle = __('app.reports.subtitle');
    $reportStatusLabel = function (?string $value): string {
        if (!$value) {
            return '-';
        }

        $translated = __('app.reports.status.value_labels.' . $value);

        return $translated !== 'app.reports.status.value_labels.' . $value ? $translated : $value;
    };

    $reportDecisionLabel = function (?string $value): string {
        if (!$value) {
            return '-';
        }

        $translated = __('app.reports.status.decision_labels.' . $value);

        return $translated !== 'app.reports.status.decision_labels.' . $value ? $translated : $value;
    };
@endphp

@section('page_title', $title)
@section('page_breadcrumb', $title)
@section('enable_header_search', '1')

@section('content')
    <div class="card stretch stretch-full mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>
        </div>
    </div>

    <div class="card stretch stretch-full mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1">تقارير الأجندة والأنشطة الشهرية</h2>
                    <p class="text-muted mb-0">
                        الفترة: {{ $adminPlanReports['period'] }}
                        <span class="badge bg-soft-success text-success ms-2">Cached</span>
                    </p>
                    <small class="text-muted">مفتاح الكاش: {{ $adminPlanReports['cache_key'] }}</small>
                </div>
                <form class="row g-2 align-items-end" method="GET" action="{{ route('role.super_admin.reports') }}">
                    <div class="col-auto">
                        <label class="form-label">السنة</label>
                        <input class="form-control" type="number" name="report_year" value="{{ request('report_year', now()->year) }}" min="2020" max="{{ now()->year + 1 }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label">الشهر</label>
                        <input class="form-control" type="number" name="report_month" value="{{ request('report_month', now()->month) }}" min="1" max="12">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" type="submit">عرض التقرير</button>
                    </div>
                </form>
            </div>

            <div class="table-responsive mb-4">
                <table class="table table-hover align-middle">
                    <thead>
                    <tr>
                        <th>الفرع</th>
                        <th>إجمالي الأنشطة</th>
                        <th>مكتمل</th>
                        <th>بانتظار موافقة المسؤول</th>
                        <th>بانتظار تعديل</th>
                        <th>بانتظار حذف</th>
                        <th>طلبات تعديل معلقة</th>
                        <th>بانتظار منسق الفروع</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($adminPlanReports['monthly_by_branch'] as $row)
                        <tr>
                            <td>{{ $row['branch'] }}</td>
                            <td><strong>{{ $row['total'] }}</strong></td>
                            <td>{{ $row['completed'] }}</td>
                            <td>{{ $row['pending_approval'] }}</td>
                            <td>{{ $row['pending_changes'] + $row['pending_edit'] }}</td>
                            <td>{{ $row['pending_delete'] }}</td>
                            <td>{{ $row['pending_edit'] }}</td>
                            <td>{{ $row['pending_branch_coordinator'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-muted">لا توجد بيانات للأنشطة الشهرية ضمن الفترة.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="row g-3">
                <div class="col-12 col-lg-5">
                    <h3 class="h6">ملخص الأجندة حسب الحالة</h3>
                    <ul class="list-group list-group-flush">
                        @forelse ($adminPlanReports['agenda_status'] as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $reportStatusLabel($item->status) }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا توجد بيانات أجندة ضمن الفترة.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="col-12 col-lg-7">
                    <h3 class="h6">سرعة الاعتمادات: الفرق بين الطلب والاعتماد</h3>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead>
                            <tr>
                                <th>المسار</th>
                                <th>عدد الطلبات</th>
                                <th>متوسط الساعات</th>
                                <th>أسرع اعتماد</th>
                                <th>أطول اعتماد</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($adminPlanReports['approval_speed'] as $row)
                                <tr>
                                    <td>{{ $row['module'] }}</td>
                                    <td>{{ $row['total'] }}</td>
                                    <td>{{ $row['avg_hours'] }}</td>
                                    <td>{{ $row['min_hours'] }}</td>
                                    <td>{{ $row['max_hours'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-muted">لا توجد اعتمادات مكتملة ضمن الفترة.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.structure.title') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.structure.subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.structure.branches') }}</span>
                            <strong>{{ $overview['branches'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.structure.centers') }}</span>
                            <strong>{{ $overview['centers'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.structure.users') }}</span>
                            <strong>{{ $overview['users'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.structure.vehicles') }}</span>
                            <strong>{{ $overview['vehicles'] }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.operations.title') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.operations.subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.agenda') }}</span>
                            <strong>{{ $operations['agenda_events'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.monthly_activities') }}</span>
                            <strong>{{ $operations['monthly_activities'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.bookings') }}</span>
                            <strong>{{ $operations['bookings'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.maintenance_requests') }}</span>
                            <strong>{{ $operations['maintenance_requests'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.operations.trips') }}</span>
                            <strong>{{ $operations['trips'] }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">مؤشرات احتياجات التنفيذ</h2>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>أنشطة فيها احتياجات تنفيذ محفوظة</span>
                            <strong>{{ $executionNeedsStats['with_payload'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>أنشطة فيها متابعة بعد التنفيذ</span>
                            <strong>{{ $executionNeedsStats['with_followup'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>احتياجات تم تأمينها</span>
                            <strong>{{ $executionNeedsStats['secured_count'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>احتياجات لم يتم تأمينها</span>
                            <strong>{{ $executionNeedsStats['not_secured_count'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>متوسط فعالية التأمين /10</span>
                            <strong>{{ $executionNeedsStats['avg_effectiveness'] ?? '-' }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">خيارات زها تايم (Lookup + Usage)</h2>
                    <p class="text-muted small mb-2">إجمالي الخيارات: {{ $zahaTimeStats['total'] }} | المفعّل: {{ $zahaTimeStats['active'] }}</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($zahaTimeStats['usage'] as $option)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $option['name'] }} <small class="text-muted">({{ $option['code'] }})</small></span>
                                <strong>{{ $option['used'] }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا توجد بيانات خيارات زها تايم.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.financials.title') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.financials.subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.financials.payments') }}</span>
                            <strong>{{ $financials['payments'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.financials.payments_total') }}</span>
                            <strong>{{ number_format($financials['payments_total'], 2) }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.financials.donations') }}</span>
                            <strong>{{ $financials['donations'] }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between">
                            <span>{{ __('app.reports.financials.donations_total') }}</span>
                            <strong>{{ number_format($financials['donations_total'], 2) }}</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.narrative.title') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.narrative.body') }}</p>
                    <ul class="mb-0">
                        <li>{{ __('app.reports.narrative.points.0') }}</li>
                        <li>{{ __('app.reports.narrative.points.1') }}</li>
                        <li>{{ __('app.reports.narrative.points.2') }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">سجل العمليات اليومي (آخر 30 يوم)</h2>
                    <ul class="list-group list-group-flush">
                        @forelse ($dailyOperationLogs as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $item->day }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا توجد بيانات.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">مؤشر الاستجابة حسب المستخدم</h2>
                    <ul class="list-group list-group-flush">
                        @forelse ($userDelayStats as $item)
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <strong>{{ $item->user?->name ?? ('#'.$item->user_id) }}</strong>
                                    <span>{{ $item->total_actions }} عملية</span>
                                </div>
                                <small class="text-muted">من {{ $item->first_action_at }} إلى {{ $item->last_action_at }}</small>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">لا توجد بيانات.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-4">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.status.maintenance') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.status.maintenance_subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($maintenanceStatus as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $reportStatusLabel($item->status) }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.status.agenda_approvals') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.status.agenda_approvals_subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($agendaApprovals as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $reportDecisionLabel($item->decision) }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-4">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h6">{{ __('app.reports.status.bookings') }}</h2>
                    <p class="text-muted small">{{ __('app.reports.status.bookings_subtitle') }}</p>
                    <ul class="list-group list-group-flush">
                        @forelse ($bookingStatus as $item)
                            <li class="list-group-item d-flex justify-content-between">
                                <span>{{ $reportStatusLabel($item->status) }}</span>
                                <strong>{{ $item->total }}</strong>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">{{ __('app.reports.status.no_data') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>



    <div class="card stretch stretch-full mt-4">
        <div class="card-body enterprise-dashboard">
            <h2 class="h5 mb-3">{{ __('app.enterprise.analytics_title') }}</h2>
            <form class="row g-2 align-items-end mb-3" method="GET" action="{{ route('role.super_admin.reports') }}">
                <div class="col-md-3">
                    <label class="form-label">{{ __('app.enterprise.year') }}</label>
                    <select class="form-select" name="year">
                        @foreach ($years as $option)
                            <option value="{{ $option }}" @selected(($enterpriseFilters['year'] ?? now()->year) == $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">{{ __('app.enterprise.apply') }}</button>
                </div>
            </form>

            @include('pages.enterprise.partials.kpis')
            @include('pages.enterprise.partials.charts')
            @include('pages.enterprise.partials.branch-performance')
        </div>
    </div>

    <div class="card stretch stretch-full">
        <div class="card-body">
            <h2 class="h5 mb-3">{{ __('app.reports.flowcharts.title') }}</h2>
            <p class="text-muted">{{ __('app.reports.flowcharts.subtitle') }}</p>
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">{{ __('app.reports.flowcharts.maintenance') }}</h3>
                        <pre class="mermaid">{!! __('app.reports.flowchart_texts.maintenance') !!}</pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">{{ __('app.reports.flowcharts.agenda') }}</h3>
                        <pre class="mermaid">{!! __('app.reports.flowchart_texts.agenda') !!}</pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">{{ __('app.reports.flowcharts.transport') }}</h3>
                        <pre class="mermaid">{!! __('app.reports.flowchart_texts.transport') !!}</pre>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="border rounded p-3 h-100">
                        <h3 class="h6 mb-3">{{ __('app.reports.flowcharts.bookings') }}</h3>
                        <pre class="mermaid">{!! __('app.reports.flowchart_texts.bookings') !!}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script type="module">
        import mermaid from 'https://cdn.jsdelivr.net/npm/mermaid@10/dist/mermaid.esm.min.mjs';
        mermaid.initialize({ startOnLoad: true });
    </script>
@endpush


@push('styles')
    <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/enterprise-dashboard.css') }}">
@endpush

@include('pages.enterprise.partials.charts-scripts')
