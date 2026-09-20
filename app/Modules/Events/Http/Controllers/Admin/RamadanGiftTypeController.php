<?php

namespace App\Modules\Events\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\RamadanIftarGiftType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RamadanGiftTypeController extends Controller
{
    public function store(Request $request)
    {
        RamadanIftarGiftType::query()->create($this->validated($request));
        return back()->with('status', 'تمت إضافة نوع الهدية أو الدرع.');
    }

    public function update(Request $request, RamadanIftarGiftType $giftType)
    {
        $giftType->update($this->validated($request, $giftType));
        return back()->with('status', 'تم تحديث نوع الهدية أو الدرع.');
    }

    public function toggle(RamadanIftarGiftType $giftType)
    {
        $giftType->update(['is_active' => ! $giftType->is_active]);
        return back()->with('status', 'تم تحديث حالة نوع الهدية أو الدرع.');
    }

    private function validated(Request $request, ?RamadanIftarGiftType $giftType = null): array
    {
        $rules = ['name_ar'=>['required','string','max:255',Rule::unique('ramadan_iftar_gift_types','name_ar')->ignore($giftType?->id)],'sort_order'=>['required','integer','min:0']];
        if (! $giftType) $rules['code']=['required','alpha_dash','max:30',Rule::unique('ramadan_iftar_gift_types','code')];
        return $request->validate($rules) + ['is_active'=>$request->boolean('is_active')];
    }
}
