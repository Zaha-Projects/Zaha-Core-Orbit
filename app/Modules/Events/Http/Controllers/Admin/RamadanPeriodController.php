<?php

namespace App\Modules\Events\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\RamadanPeriod;
use App\Modules\Events\Services\RamadanPeriodSyncService;
use Illuminate\Http\Request;

class RamadanPeriodController extends Controller
{
    public function store(Request $request)
    {
        $period = RamadanPeriod::query()->create($request->validate(RamadanPeriod::rules()) + ['is_confirmed' => false, 'is_active' => false, 'calculation_source' => 'manual']);
        return back()->with('status', 'تمت إضافة فترة رمضان للمراجعة.');
    }

    public function update(Request $request, RamadanPeriod $period)
    {
        $period->fill($request->validate(RamadanPeriod::rules('', $period->id)));
        if ($period->isDirty(['hijri_year','start_date','end_date'])) $period->forceFill(['is_confirmed' => false, 'is_active' => false]);
        $period->save();
        return back()->with('status', 'تم تحديث الفترة؛ أكدها بعد مراجعة التواريخ.');
    }

    public function sync(Request $request, RamadanPeriodSyncService $sync)
    {
        $data = $request->validate(['year' => ['required','integer','min:2020','max:2100']]);
        $sync->sync((int) $data['year']);
        return back()->with('status', 'تمت مزامنة التواريخ المقترحة دون تفعيلها أو اعتمادها.');
    }

    public function useSuggested(RamadanPeriod $period)
    {
        $period->useSuggestedDates();
        return back()->with('status', 'نُسخت التواريخ المقترحة للمراجعة؛ لم يتم التأكيد أو التفعيل.');
    }

    public function confirm(RamadanPeriod $period)
    {
        $period->confirm();
        return back()->with('status', 'تم تأكيد مراجعة الفترة. يمكنك تفعيلها الآن.');
    }

    public function activate(RamadanPeriod $period)
    {
        abort_unless($period->is_confirmed, 422, 'يجب تأكيد مراجعة الفترة قبل تفعيلها.');
        $period->activate();
        return back()->with('status', 'تم تفعيل فترة رمضان وإلغاء تفعيل الفترة السابقة.');
    }

    public function deactivate(RamadanPeriod $period)
    {
        $period->update(['is_active' => false]);
        return back()->with('status', 'تم إلغاء تفعيل فترة رمضان.');
    }
}
