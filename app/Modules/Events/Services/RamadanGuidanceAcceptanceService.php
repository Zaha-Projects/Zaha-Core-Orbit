<?php

namespace App\Modules\Events\Services;

use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\EventGuidanceAcknowledgement;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RamadanGuidanceAcceptanceService
{
    private const PRESENTED_VERSION_KEY = 'events.ramadan.guidance.presented_version_id';

    public function present(Request $request, EventGuidanceVersion $version): void
    {
        $request->session()->put(self::PRESENTED_VERSION_KEY, (int) $version->getKey());
    }

    public function acceptPresented(Request $request): EventGuidanceVersion
    {
        $current = $this->currentOrFail();

        if ((int) $request->session()->get(self::PRESENTED_VERSION_KEY) !== (int) $current->getKey()) {
            throw ValidationException::withMessages([
                'guidance' => __('ramadan_iftars.business_errors.guidance_changed'),
            ]);
        }

        EventGuidanceAcknowledgement::query()->updateOrCreate(
            ['user_id' => $request->user()->getKey(), 'event_guidance_version_id' => $current->getKey()],
            ['acknowledged_at' => now()]
        );

        return $current;
    }

    public function acceptedCurrentOrFail(Request $request): array
    {
        $current = $this->currentOrFail();
        $acknowledgement = EventGuidanceAcknowledgement::query()
            ->where('user_id', $request->user()->getKey())
            ->where('event_guidance_version_id', $current->getKey())
            ->first();

        if (! $acknowledgement) {
            throw ValidationException::withMessages([
                'guidance' => __('ramadan_iftars.business_errors.guidance_accept_before_create'),
            ]);
        }

        return [$current, $acknowledgement->acknowledged_at->toDateTimeString()];
    }

    public function hasAcceptedCurrent(Request $request): bool
    {
        $current = $this->currentOrFail();

        return EventGuidanceAcknowledgement::query()
                ->where('user_id', $request->user()->getKey())
                ->where('event_guidance_version_id', $current->getKey())
                ->exists();
    }

    public function currentOrFail(): EventGuidanceVersion
    {
        $current = EventGuidanceVersion::currentForRamadan();

        abort_unless($current, 503, __('ramadan_iftars.business_errors.guidance_unavailable'));

        return $current;
    }

    public function forgetAcceptance(Request $request): void
    {
        $request->session()->forget([
            self::PRESENTED_VERSION_KEY,
        ]);
    }
}
