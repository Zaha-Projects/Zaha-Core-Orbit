<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AdminReports\AdminReportsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class SiteSettingsController extends Controller
{
    public function index(Request $request, AdminReportsService $reportsService)
    {
        $reportYear = (int) $request->input('report_year', now()->year);
        $reportMonth = (int) $request->input('report_month', now()->month);
        $cacheConfig = $reportsService->cacheConfig();
        $reportCacheKey = $reportsService->cacheKey($reportYear, $reportMonth);
        $settings = Setting::query()->orderBy('key')->get();

        return view('pages.admin.site-settings.index', compact('settings', 'cacheConfig', 'reportCacheKey', 'reportYear', 'reportMonth'));
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
        ]);

        $data['admin_reports_cache_enabled'] = $request->boolean('admin_reports_cache_enabled') ? '1' : '0';

        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::query()->updateOrCreate(['key' => $key], ['value' => (string) $value]);
            }
        }

        return redirect()->route('role.super_admin.site_settings.index')->with('status', 'تم تحديث إعدادات الموقع.');
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
