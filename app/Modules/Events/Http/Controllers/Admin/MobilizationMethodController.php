<?php

namespace App\Modules\Events\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\MobilizationMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MobilizationMethodController extends Controller
{
    public function store(Request $request)
    {
        MobilizationMethod::query()->create($this->validated($request));
        return back()->with('status', 'تمت إضافة طريقة الحشد والاستقطاب.');
    }

    public function update(Request $request, MobilizationMethod $method)
    {
        $method->update($this->validated($request, $method));
        return back()->with('status', 'تم تحديث طريقة الحشد والاستقطاب.');
    }

    public function toggle(MobilizationMethod $method)
    {
        $method->update(['is_active' => ! $method->is_active]);
        return back()->with('status', 'تم تحديث حالة طريقة الحشد والاستقطاب.');
    }

    private function validated(Request $request, ?MobilizationMethod $method = null): array
    {
        $rules = [
            'name_ar' => ['required','string','max:255',Rule::unique('mobilization_methods','name_ar')->ignore($method?->id)],
            'name_en' => ['required','string','max:255'], 'sort_order' => ['required','integer','min:0'], 'is_other' => ['nullable','boolean'],
        ];
        if (! $method) $rules['code'] = ['required','alpha_dash','max:100',Rule::unique('mobilization_methods','code')];
        return $request->validate($rules) + ['is_active' => $request->boolean('is_active'), 'is_other' => $request->boolean('is_other')];
    }
}
