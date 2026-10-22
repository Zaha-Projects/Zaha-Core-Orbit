<?php

namespace App\Http\Controllers\Web\Kpi;

use App\Http\Controllers\Controller;
use App\Services\Kpi\KpiGuideService;

/**
 * دليل مؤشرات الأداء: generated from config/kpis.php. Contains definitions only (no data),
 * so any authenticated user may read it.
 */
class KpiGuideController extends Controller
{
    public function index(KpiGuideService $guide)
    {
        return view('pages.kpi.guide', ['guide' => $guide->build()]);
    }
}
