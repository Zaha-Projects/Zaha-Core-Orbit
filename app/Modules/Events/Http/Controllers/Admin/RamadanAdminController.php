<?php

namespace App\Modules\Events\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\RamadanPeriod;

class RamadanAdminController extends Controller
{
    public function index()
    {
        return view('pages.events.ramadan.admin.index', [
            'periods' => RamadanPeriod::query()->orderByDesc('year')->get(),
            'activePeriod' => RamadanPeriod::current(),
            'guidanceVersions' => EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->orderByDesc('version_number')->get(),
            'currentGuidance' => EventGuidanceVersion::currentForRamadan(),
        ]);
    }
}
