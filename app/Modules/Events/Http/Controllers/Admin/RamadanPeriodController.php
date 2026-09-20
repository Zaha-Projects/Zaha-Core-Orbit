<?php

namespace App\Modules\Events\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\RamadanPeriod;
use Illuminate\Http\Request;

class RamadanPeriodController extends Controller
{
    public function store(Request $request)
    {
        $period = RamadanPeriod::query()->create($request->validate(RamadanPeriod::rules()) + ['is_active' => false]);
        if ($request->boolean('is_active')) $period->activate();
        return back()->with('status', 'تمت إضافة فترة رمضان.');
    }

    public function update(Request $request, RamadanPeriod $period)
    {
        $period->update($request->validate(RamadanPeriod::rules('', $period->id)));
        if ($request->boolean('is_active')) $period->activate();
        return back()->with('status', 'تم تحديث فترة رمضان.');
    }

    public function activate(RamadanPeriod $period)
    {
        $period->activate();
        return back()->with('status', 'تم تفعيل فترة رمضان وإلغاء تفعيل الفترة السابقة.');
    }

    public function deactivate(RamadanPeriod $period)
    {
        $period->update(['is_active' => false]);
        return back()->with('status', 'تم إلغاء تفعيل فترة رمضان.');
    }
}
