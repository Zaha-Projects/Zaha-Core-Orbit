<?php

namespace App\Modules\Events\Services;

use App\Modules\Events\Models\EventGuidanceVersion;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class RamadanGuidanceAcceptanceService
{
    private const PRESENTED_VERSION_KEY = 'events.ramadan.guidance.presented_version_id';
    private const ACCEPTED_VERSION_KEY = 'events.ramadan.guidance.accepted_version_id';
    private const ACCEPTED_AT_KEY = 'events.ramadan.guidance.accepted_at';
    private const ACCEPTED_BY_KEY = 'events.ramadan.guidance.accepted_by';

    public function present(Request $request, EventGuidanceVersion $version): void
    {
        $request->session()->put(self::PRESENTED_VERSION_KEY, (int) $version->getKey());
    }

    public function acceptPresented(Request $request): EventGuidanceVersion
    {
        $current = $this->currentOrFail();

        if ((int) $request->session()->get(self::PRESENTED_VERSION_KEY) !== (int) $current->getKey()) {
            throw ValidationException::withMessages([
                'guidance' => 'The Ramadan guidance changed. Review the current version before accepting it.',
            ]);
        }

        $request->session()->put([
            self::ACCEPTED_VERSION_KEY => (int) $current->getKey(),
            self::ACCEPTED_AT_KEY => now()->toDateTimeString(),
            self::ACCEPTED_BY_KEY => (int) $request->user()->getKey(),
        ]);

        return $current;
    }

    public function acceptedCurrentOrFail(Request $request): array
    {
        $current = $this->currentOrFail();
        $acceptedVersionId = (int) $request->session()->get(self::ACCEPTED_VERSION_KEY);
        $acceptedAt = $request->session()->get(self::ACCEPTED_AT_KEY);
        $acceptedBy = (int) $request->session()->get(self::ACCEPTED_BY_KEY);

        if ($acceptedVersionId !== (int) $current->getKey()
            || $acceptedBy !== (int) $request->user()->getKey()
            || ! is_string($acceptedAt)
            || $acceptedAt === '') {
            throw ValidationException::withMessages([
                'guidance' => 'Review and accept the current Ramadan guidance before creating a plan.',
            ]);
        }

        return [$current, $acceptedAt];
    }

    public function hasAcceptedCurrent(Request $request): bool
    {
        $current = $this->currentOrFail();

        return (int) $request->session()->get(self::ACCEPTED_VERSION_KEY) === (int) $current->getKey()
            && (int) $request->session()->get(self::ACCEPTED_BY_KEY) === (int) $request->user()->getKey()
            && filled($request->session()->get(self::ACCEPTED_AT_KEY));
    }

    public function currentOrFail(): EventGuidanceVersion
    {
        $current = EventGuidanceVersion::currentForRamadan();

        abort_unless($current, 503, 'No published Ramadan guidance is currently available.');

        return $current;
    }

    public function forgetAcceptance(Request $request): void
    {
        $request->session()->forget([
            self::PRESENTED_VERSION_KEY,
            self::ACCEPTED_VERSION_KEY,
            self::ACCEPTED_AT_KEY,
            self::ACCEPTED_BY_KEY,
        ]);
    }
}
