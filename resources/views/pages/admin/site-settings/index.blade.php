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
                                <input class="form-check-input" type="checkbox" role="switch" id="admin_reports_cache_enabled" name="admin_reports_cache_enabled" value="1" @checked($cacheConfig['enabled'])>
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
                        <div class="col-12 col-md-4">
                            <label class="form-label">وزن الرضا الشهري</label>
                            <input class="form-control" type="number" name="branch_monthly_score_weight_satisfaction" value="{{ \App\Models\Setting::valueOf('branch_monthly_score_weight_satisfaction', '40') }}" min="0" max="100">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">وزن الالتزام الشهري</label>
                            <input class="form-control" type="number" name="branch_monthly_score_weight_commitment" value="{{ \App\Models\Setting::valueOf('branch_monthly_score_weight_commitment', '60') }}" min="0" max="100">
                        </div>
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
                    @forelse($settings as $setting)
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
