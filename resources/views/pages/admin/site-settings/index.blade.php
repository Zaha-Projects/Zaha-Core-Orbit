@extends('layouts.app')

@section('page_title', 'إعدادات الموقع والكاش')
@section('page_breadcrumb', 'إعدادات الموقع والكاش')

@section('content')
    <div class="card stretch stretch-full mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between gap-3 align-items-start">
            <div>
                <h1 class="h4 mb-2">إعدادات الموقع والكاش</h1>
                <p class="text-muted mb-0">إدارة كاش التقارير، تفعيل/تعطيل الكاش، ومفاتيح الإعدادات القابلة للتعديل.</p>
            </div>
            <a class="btn btn-outline-primary" href="{{ route('role.super_admin.reports', ['tab' => 'relations', 'report_year' => $reportYear, 'report_month' => $reportMonth]) }}">
                الرجوع للتقارير
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h5 mb-3">إعدادات كاش التقارير</h2>
                    <form method="POST" action="{{ route('role.super_admin.site_settings.update') }}" class="row g-3">
                        @csrf
                        @method('PUT')
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input type="hidden" name="admin_reports_cache_enabled" value="0">
                                <input class="form-check-input" type="checkbox" role="switch" id="admin_reports_cache_enabled" name="admin_reports_cache_enabled" value="1" {{ (string) old('admin_reports_cache_enabled', $cacheConfig['enabled'] ? '1' : '0') === '1' ? 'checked' : '' }}>
                                <label class="form-check-label" for="admin_reports_cache_enabled">تفعيل كاش تقارير الأدمن</label>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">مدة الكاش بالدقائق</label>
                            <input class="form-control" type="number" name="admin_reports_cache_ttl_minutes" value="{{ $cacheConfig['ttl_minutes'] }}" min="1" max="1440">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">بادئة مفاتيح الكاش</label>
                            <input class="form-control" name="admin_reports_cache_prefix" value="{{ $cacheConfig['prefix'] }}" maxlength="120">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">أيام قفل الخطة الشهرية</label>
                            <input class="form-control" type="number" name="monthly_plan_lock_days" value="{{ \App\Models\Setting::valueOf('monthly_plan_lock_days', '5') }}" min="0" max="31">
                        </div>
                        <div class="col-12"><hr><h3 class="h6 mb-0"><i class="fas fa-moon text-warning"></i> فترة رمضان المعتمدة</h3><p class="small text-muted">تحدد نطاق تقويم إفطارات رمضان والتاريخ المسموح به عند الإنشاء والتعديل.</p></div>
                        <div class="col-12"><span class="small text-muted">اختر سنة لعرض أو تعديل فترة رمضان</span>
                            @foreach($ramadanPeriods as $period)
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.super_admin.site_settings.index', ['ramadan_year' => $period->year]) }}">{{ $period->year }}</a>
                            @endforeach
                        </div>
                        <div class="col-12"><div class="alert alert-light border mb-0">المصدر المعتمد لتواريخ رمضان هو سجلات الفترة حسب السنة أعلاه</div></div>
                        <div class="col-12">
                            <label class="form-label" for="ramadan-default-year">السنة الافتراضية للتقويم</label>
                            <select id="ramadan-default-year" name="ramadan_default_year" class="form-select">
                                <option value="">الإبقاء على الاختيار الحالي</option>
                                @foreach($ramadanPeriods as $savedPeriod)
                                    <option value="{{ $savedPeriod->year }}" {{ (int) old('ramadan_default_year', $ramadanDefaultYear) === $savedPeriod->year ? 'selected' : '' }}>{{ $savedPeriod->year }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">تعديل تواريخ سنة أدناه لا يغيّر السنة الافتراضية.</div>
                            @error('ramadan_default_year')<div class="text-danger">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12 col-md-3"><label class="form-label" for="ramadan_period_year">السنة الميلادية</label><input id="ramadan_period_year" class="form-control @error('ramadan_period_year') is-invalid @enderror" type="number" name="ramadan_period_year" value="{{ old('ramadan_period_year', $ramadanPeriod?->year ?? request('ramadan_year', now()->year)) }}" min="2020" max="2100" required>@error('ramadan_period_year')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-md-3"><label class="form-label" for="ramadan_period_start_date">تاريخ البداية</label><input id="ramadan_period_start_date" class="form-control @error('ramadan_period_start_date') is-invalid @enderror" type="date" name="ramadan_period_start_date" value="{{ old('ramadan_period_start_date', $ramadanPeriod?->start_date?->format('Y-m-d')) }}" required>@error('ramadan_period_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-md-3"><label class="form-label" for="ramadan_period_end_date">تاريخ النهاية</label><input id="ramadan_period_end_date" class="form-control @error('ramadan_period_end_date') is-invalid @enderror" type="date" name="ramadan_period_end_date" value="{{ old('ramadan_period_end_date', $ramadanPeriod?->end_date?->format('Y-m-d')) }}" required>@error('ramadan_period_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                        <div class="col-12 col-md-3 d-flex align-items-end"><div class="form-check form-switch mb-2"><input type="hidden" name="ramadan_period_is_active" value="0"><input class="form-check-input" type="checkbox" role="switch" id="ramadan_period_is_active" name="ramadan_period_is_active" value="1" {{ (string) old('ramadan_period_is_active', $ramadanPeriod?->is_active ? '1' : '0') === '1' ? 'checked' : '' }}><label class="form-check-label" for="ramadan_period_is_active">الفترة فعالة</label></div></div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">وزن الرضا الشهري</label>
                            <input class="form-control" type="number" name="branch_monthly_score_weight_satisfaction" value="{{ \App\Models\Setting::valueOf('branch_monthly_score_weight_satisfaction', '40') }}" min="0" max="100">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">وزن الالتزام الشهري</label>
                            <input class="form-control" type="number" name="branch_monthly_score_weight_commitment" value="{{ \App\Models\Setting::valueOf('branch_monthly_score_weight_commitment', '60') }}" min="0" max="100">
                        </div>
                        <div class="col-12"><hr><h3 class="h6">نطاق احتياجات التنفيذ</h3></div>
                        @foreach($executionNeedTypes as $needType)
                            <div class="col-12 col-md-6">
                                @if($needType->code === 'execution_team' && ! $needType->scope_configured_at && ! $needType->is_monthly_activity)
                                    <div class="alert alert-warning">نطاق فريق التنفيذ موروث ولم يوثّق كاختيار إداري. الافتراضي الجديد «كلاهما»؛ راجع الاختيار ثم احفظ لتأكيده.</div>
                                @endif
                                <input type="hidden" name="execution_need_scopes[{{ $loop->index }}][id]" value="{{ $needType->id }}">
                                <label class="form-label" for="need-scope-{{ $needType->id }}">{{ $needType->name }}</label>
                                <select id="need-scope-{{ $needType->id }}" class="form-select" name="execution_need_scopes[{{ $loop->index }}][usage_scope]">
                                    @foreach(['monthly_plans' => 'الخطط الشهرية', 'iftars' => 'الإفطارات', 'both' => 'كلاهما', 'none' => 'غير متاح'] as $scope => $label)
                                        <option value="{{ $scope }}" {{ old('execution_need_scopes.'.$loop->parent->index.'.usage_scope', $needType->usage_scope) === $scope ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <div class="form-check mt-1"><input class="form-check-input" type="checkbox" value="1" id="need-scope-confirm-{{ $needType->id }}" name="execution_need_scopes[{{ $loop->index }}][confirm_scope]" {{ old('execution_need_scopes.'.$loop->index.'.confirm_scope') ? 'checked' : '' }}><label class="form-check-label small" for="need-scope-confirm-{{ $needType->id }}">تأكيد هذا النطاق دون تغييره</label></div>
                                @error('execution_need_scopes.'.$loop->index.'.usage_scope')<div class="text-danger">{{ $message }}</div>@enderror
                            </div>
                        @endforeach
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary" type="submit">حفظ الإعدادات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card h-100 stretch stretch-full">
                <div class="card-body">
                    <h2 class="h5 mb-3">مفاتيح الكاش والتحكم</h2>
                    <form method="GET" action="{{ route('role.super_admin.site_settings.index') }}" class="row g-2 align-items-end mb-3">
                        <div class="col-5">
                            <label class="form-label">السنة</label>
                            <input class="form-control" type="number" name="report_year" value="{{ $reportYear }}" min="2020" max="{{ now()->year + 1 }}">
                        </div>
                        <div class="col-4">
                            <label class="form-label">الشهر</label>
                            <input class="form-control" type="number" name="report_month" value="{{ $reportMonth }}" min="1" max="12">
                        </div>
                        <div class="col-3"><button class="btn btn-outline-primary w-100" type="submit">عرض</button></div>
                    </form>

                    <div class="alert alert-light border">
                        <div class="small text-muted mb-1">مفتاح تقرير العلاقات الحالي</div>
                        <code>{{ $reportCacheKey }}</code>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('role.super_admin.site_settings.cache.refresh') }}">
                            @csrf
                            <input type="hidden" name="report_year" value="{{ $reportYear }}">
                            <input type="hidden" name="report_month" value="{{ $reportMonth }}">
                            <button class="btn btn-success" type="submit">Refresh / إعادة بناء</button>
                        </form>
                        <form method="POST" action="{{ route('role.super_admin.site_settings.cache.delete') }}">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="report_year" value="{{ $reportYear }}">
                            <input type="hidden" name="report_month" value="{{ $reportMonth }}">
                            <button class="btn btn-warning" type="submit">حذف كاش التقرير</button>
                        </form>
                        <form method="POST" action="{{ route('role.super_admin.site_settings.cache.clear_all') }}">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-danger" type="submit">حذف كاش التطبيق</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card stretch stretch-full">
        <div class="card-body">
            <h2 class="h5 mb-3">كل مفاتيح الإعدادات الحالية</h2>
            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Key</th><th>Value</th></tr></thead>
                    <tbody>
                    @forelse($settings->reject(fn ($setting) => in_array($setting->key, ['ramadan_period_year', 'ramadan_period_start_date', 'ramadan_period_end_date', 'ramadan_period_is_active', 'ramadan_default_year'], true)) as $setting)
                        <tr><td><code>{{ $setting->key }}</code></td><td>{{ $setting->value }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-muted">لا توجد إعدادات محفوظة.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
