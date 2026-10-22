<?php

namespace App\Modules\Events\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class RamadanDashboardSettingController extends Controller
{
    public function update(Request $request)
    {
        $request->validate(['ramadan_dashboard_enabled' => ['nullable', 'boolean']]);
        Setting::query()->updateOrCreate(
            ['key' => 'ramadan_dashboard_enabled'],
            ['value' => $request->boolean('ramadan_dashboard_enabled') ? '1' : '0']
        );

        return back()->with('status', 'تم تحديث ظهور قسم رمضان في لوحة التحكم.');
    }
}
