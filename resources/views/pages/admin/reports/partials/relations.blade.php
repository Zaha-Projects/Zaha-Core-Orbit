<div class="card stretch stretch-full mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">العلاقات والأجندة السنوية والخطط الشهرية</h2>
                <p class="text-muted mb-0">
                    الفترة: {{ $relationsReport['period'] }}
                    <span class="badge {{ $relationsReport['cache_enabled'] ? 'bg-soft-success text-success' : 'bg-soft-warning text-warning' }} ms-2">
                        {{ $relationsReport['cache_enabled'] ? 'Cached' : 'Cache Disabled' }}
                    </span>
                </p>
                <small class="text-muted">مفتاح الكاش: {{ $relationsReport['cache_key'] }} | مدة الكاش: {{ $relationsReport['cache_ttl_minutes'] }} دقيقة</small>
            </div>
            <form class="row g-2 align-items-end" method="GET" action="{{ route('role.super_admin.reports') }}">
                <input type="hidden" name="tab" value="relations">
                <div class="col-auto">
                    <label class="form-label">السنة</label>
                    <input class="form-control" type="number" name="report_year" value="{{ $reportYear }}" min="2020" max="{{ now()->year + 1 }}">
                </div>
                <div class="col-auto">
                    <label class="form-label">الشهر</label>
                    <input class="form-control" type="number" name="report_month" value="{{ $reportMonth }}" min="1" max="12">
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
                @forelse ($relationsReport['monthly_by_branch'] as $row)
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
                <h3 class="h6">ملخص الأجندة السنوية حسب الحالة</h3>
                <ul class="list-group list-group-flush">
                    @forelse ($relationsReport['agenda_status'] as $item)
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
                        @forelse ($relationsReport['approval_speed'] as $row)
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
