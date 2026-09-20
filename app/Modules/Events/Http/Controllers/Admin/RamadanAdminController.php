<?php

namespace App\Modules\Events\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\RamadanPeriod;
use App\Modules\Events\Models\RamadanIftarGiftType;

class RamadanAdminController extends Controller
{
    public function index()
    {
        return view('pages.events.ramadan.admin.index', [
            'periods' => RamadanPeriod::query()->orderByDesc('year')->get(),
            'activePeriod' => RamadanPeriod::current(),
            'guidanceVersions' => EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->orderByDesc('version_number')->get(),
            'currentGuidance' => EventGuidanceVersion::currentForRamadan(),
            'mobilizationMethods' => MobilizationMethod::query()->ordered()->get(),
            'giftTypes' => RamadanIftarGiftType::query()->orderBy('sort_order')->orderBy('id')->get(),
        ]);
    }
}
