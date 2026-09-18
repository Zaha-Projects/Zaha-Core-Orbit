<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Modules\Events\Models\RamadanPeriod;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Services\AdminReports\AdminReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SiteSettingsController extends Controller
{
    public function index(Request $request, AdminReportsService $reportsService)
    {
        $reportYear = (int) $request->input('report_year', now()->year);
        $reportMonth = (int) $request->input('report_month', now()->month);
        $cacheConfig = $reportsService->cacheConfig();
        $reportCacheKey = $reportsService->cacheKey($reportYear, $reportMonth);
        $settings = Setting::query()->orderBy('key')->get();
        $ramadanPeriods = RamadanPeriod::query()->orderByDesc('year')->get();
        $ramadanDefaultYear = \App\Modules\Events\Support\RamadanPeriod::defaultYear();
        $ramadanPeriod = $ramadanPeriods->firstWhere('year', (int) $request->input('ramadan_year', $ramadanDefaultYear));
        $executionNeedTypes = ExecutionNeedType::query()->orderBy('sort_order')->get();

        return view('pages.admin.site-settings.index', compact('settings', 'cacheConfig', 'reportCacheKey', 'reportYear', 'reportMonth', 'ramadanPeriods', 'ramadanPeriod', 'ramadanDefaultYear', 'executionNeedTypes'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'admin_reports_cache_enabled' => ['nullable', 'boolean'],
            'admin_reports_cache_ttl_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'admin_reports_cache_prefix' => ['required', 'string', 'max:120'],
            'monthly_plan_lock_days' => ['nullable', 'integer', 'min:0', 'max:31'],
            'branch_monthly_score_weight_satisfaction' => ['nullable', 'integer', 'min:0', 'max:100'],
            'branch_monthly_score_weight_commitment' => ['nullable', 'integer', 'min:0', 'max:100'],
            'ramadan_default_year' => ['sometimes', 'nullable', 'integer', Rule::exists('ramadan_periods', 'year')],
            'execution_need_scopes' => ['sometimes', 'array'],
            'execution_need_scopes.*.id' => ['required', 'integer', 'distinct', 'exists:execution_need_types,id'],
            'execution_need_scopes.*.usage_scope' => ['required', Rule::in(ExecutionNeedType::usageScopes())],
            'execution_need_scopes.*.confirm_scope' => ['nullable', 'boolean'],
        ] + ($request->has('ramadan_period_year') ? RamadanPeriod::rules('ramadan_period_') : []));

        $data['admin_reports_cache_enabled'] = $request->boolean('admin_reports_cache_enabled') ? '1' : '0';
        if ($request->has('ramadan_period_year')) {
            $data['ramadan_period_is_active'] = $request->boolean('ramadan_period_is_active') ? '1' : '0';
        }

        DB::transaction(function () use ($data): void {
            $types = ExecutionNeedType::query()->whereIn('id', collect($data['execution_need_scopes'] ?? [])->pluck('id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            foreach ($data['execution_need_scopes'] ?? [] as $row) {
                $type = $types->get($row['id']);
                $scopeChanged = $type->usage_scope !== $row['usage_scope'];
                $confirmed = (bool) ($row['confirm_scope'] ?? false);
                if ($scopeChanged || $confirmed) {
                    $type->update([
                        'usage_scope' => $row['usage_scope'],
                        'scope_configured_at' => now(),
                    ]);
                }
            }
            unset($data['execution_need_scopes']);
            if (isset($data['ramadan_period_year'])) {
                RamadanPeriod::query()->upsert([[
                    'year' => $data['ramadan_period_year'],
                    'start_date' => $data['ramadan_period_start_date'],
                    'end_date' => $data['ramadan_period_end_date'],
                    'is_active' => $data['ramadan_period_is_active'],
                ]], ['year'], ['start_date', 'end_date', 'is_active', 'updated_at']);
            }
            foreach (['year', 'start_date', 'end_date', 'is_active'] as $field) {
                unset($data['ramadan_period_'.$field]);
            }
            foreach ($data as $key => $value) {
                if ($value !== null) {
                    Setting::query()->upsert([['key' => $key, 'value' => (string) $value]], ['key'], ['value', 'updated_at']);
                }
            }
        }, 5);

        return redirect()->route('role.super_admin.site_settings.index', [
            'ramadan_year' => $data['ramadan_period_year'] ?? null,
        ])->with('status', 'تم تحديث إعدادات الموقع.');
    }

    public function refreshReportCache(Request $request, AdminReportsService $reportsService)
    {
        $year = (int) $request->input('report_year', now()->year);
        $month = (int) $request->input('report_month', now()->month);

        $reportsService->forgetRelationsCache($year, $month);
        $reportsService->build($year, $month);

        return redirect()->route('role.super_admin.site_settings.index', ['report_year' => $year, 'report_month' => $month])->with('status', 'تم تحديث كاش تقرير العلاقات للفترة المحددة.');
    }

    public function deleteReportCache(Request $request, AdminReportsService $reportsService)
    {
        $year = (int) $request->input('report_year', now()->year);
        $month = (int) $request->input('report_month', now()->month);

        $reportsService->forgetRelationsCache($year, $month);

        return redirect()->route('role.super_admin.site_settings.index', ['report_year' => $year, 'report_month' => $month])->with('status', 'تم حذف كاش تقرير العلاقات للفترة المحددة.');
    }

    public function clearApplicationCache()
    {
        Cache::flush();
        Artisan::call('config:clear');
        Artisan::call('view:clear');

        return redirect()->route('role.super_admin.site_settings.index')->with('status', 'تم حذف كاش التطبيق والكونفيج والواجهات.');
    }
}
