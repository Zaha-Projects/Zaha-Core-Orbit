<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Services\RamadanGuidanceAcceptanceService;
use Illuminate\Http\Request;

class RamadanGuidanceController extends Controller
{
    private RamadanGuidanceAcceptanceService $acceptance;

    public function __construct(RamadanGuidanceAcceptanceService $acceptance)
    {
        $this->acceptance = $acceptance;
    }

    public function show(Request $request)
    {
        $guidance = $this->acceptance->currentOrFail();
        $this->acceptance->present($request, $guidance);

        return view('pages.events.ramadan.guidance', compact('guidance'));
    }

    public function accept(Request $request)
    {
        $request->validate([
            'accept_guidance' => ['required', 'accepted'],
        ]);

        $this->acceptance->acceptPresented($request);

        return redirect()->route('events.ramadan.iftars.create');
    }
}
