<?php

namespace App\Modules\Events\Http\Controllers\Ramadan;

use App\Http\Controllers\Controller;
use App\Modules\Events\Services\RamadanGuidanceAcceptanceService;
use Illuminate\Http\Request;
use App\Modules\Events\Models\EventGuidanceVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function edit(Request $request)
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        $guidance = $this->acceptance->currentOrFail();
        $sections = json_decode($guidance->content, true);
        $content = is_array($sections) ? collect($sections)->map(function ($section) {
            return ($section['title'] ?? '')."\n".implode("\n", $section['items'] ?? []);
        })->implode("\n\n") : $guidance->content;

        return view('pages.events.ramadan.guidance_edit', compact('guidance', 'content'));
    }

    public function update(Request $request)
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'source_version_id' => ['required', 'integer'],
        ], [], ['title' => 'عنوان التعليمات', 'content' => 'محتوى التعليمات', 'source_version_id' => 'نسخة التعليمات']);
        DB::transaction(function () use ($data, $request): void {
            $versions = EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->orderBy('id')->lockForUpdate()->get();
            $current = EventGuidanceVersion::currentForRamadan();
            if (! $current || $current->id !== (int) $data['source_version_id']) {
                throw ValidationException::withMessages(['content' => 'تغيرت التعليمات أثناء التعديل. افتح النسخة الحالية مجددًا قبل الحفظ.']);
            }
            // Published content and acknowledgements remain attached to their original version.
            EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->where('is_active', true)->update(['is_active' => false]);
            EventGuidanceVersion::query()->create([
                'code' => EventGuidanceVersion::RAMADAN_IFTAR,
                'title' => $data['title'], 'content' => $data['content'],
                'version_number' => ((int) $versions->max('version_number')) + 1,
                'is_active' => true, 'published_at' => now(), 'created_by' => $request->user()->id,
            ]);
        }, 5);

        return redirect()->route('events.ramadan.guidance.show')->with('status', 'تم حفظ التعليمات ونشر نسخة جديدة. يلزم إقرار النسخة الجديدة قبل إنشاء خطة جديدة.');
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
