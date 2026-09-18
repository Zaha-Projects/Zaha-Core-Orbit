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

        $acknowledgement = \App\Modules\Events\Models\EventGuidanceAcknowledgement::query()
            ->where('user_id', $request->user()->getKey())
            ->where('event_guidance_version_id', $guidance->getKey())
            ->first();

        return view('pages.events.ramadan.guidance', compact('guidance', 'acknowledgement'));
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
