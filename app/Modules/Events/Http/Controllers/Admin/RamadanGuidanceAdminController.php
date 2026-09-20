<?php

namespace App\Modules\Events\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\EventGuidanceVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RamadanGuidanceAdminController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate(['title' => ['required','string','max:255'], 'content' => ['required','string']]);
        DB::transaction(function () use ($data, $request): void {
            $versions = EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->lockForUpdate()->get();
            EventGuidanceVersion::query()->create($data + ['code' => EventGuidanceVersion::RAMADAN_IFTAR, 'version_number' => ((int) $versions->max('version_number')) + 1, 'is_active' => false, 'created_by' => $request->user()->id]);
        }, 5);
        return back()->with('status', 'تم إنشاء مسودة إرشادات جديدة.');
    }

    public function update(Request $request, EventGuidanceVersion $guidance)
    {
        abort_unless($guidance->code === EventGuidanceVersion::RAMADAN_IFTAR, 404);
        abort_if($guidance->published_at !== null, 422, 'الإصدار المنشور غير قابل للتعديل؛ أنشئ إصدارًا جديدًا.');
        $guidance->update($request->validate(['title' => ['required','string','max:255'], 'content' => ['required','string']]));
        return back()->with('status', 'تم تحديث مسودة الإرشادات.');
    }

    public function publish(EventGuidanceVersion $guidance)
    {
        abort_unless($guidance->code === EventGuidanceVersion::RAMADAN_IFTAR, 404);
        DB::transaction(function () use ($guidance): void {
            EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->lockForUpdate()->get();
            EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->whereKeyNot($guidance->id)->update(['is_active' => false]);
            $guidance->forceFill(['is_active' => true, 'published_at' => $guidance->published_at ?? now()])->save();
        }, 5);
        return back()->with('status', 'تم نشر الإصدار وتعيينه كالإرشاد الحالي.');
    }

    public function deactivate(EventGuidanceVersion $guidance)
    {
        abort_unless($guidance->code === EventGuidanceVersion::RAMADAN_IFTAR, 404);
        $guidance->update(['is_active' => false]);
        return back()->with('status', 'تمت أرشفة الإصدار.');
    }
}
