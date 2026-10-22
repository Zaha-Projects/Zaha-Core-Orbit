@php
$stages = [
    'planning' => ['التخطيط', 'fa-pen-ruler', ['draft', 'returned']],
    'approval' => ['الاعتماد', 'fa-stamp', ['submitted', 'rejected']],
    'execution' => ['التنفيذ', 'fa-gears', ['approved', 'executing']],
    'post' => ['ما بعد التنفيذ', 'fa-clipboard-check', ['post_execution']],
    'followup' => ['المتابعة', 'fa-magnifying-glass-chart', ['verified']],
    'closed' => ['الإغلاق', 'fa-circle-check', ['completed']],
];
$currentIndex = collect($stages)->search(fn ($stage) => in_array($status, $stage[2], true));
$currentIndex = $currentIndex === false ? 0 : array_search($currentIndex, array_keys($stages), true);
@endphp
<nav class="bazaar-lifecycle" aria-label="مراحل دورة البازار">
    @foreach($stages as $stage)
        <div @class(['bazaar-lifecycle-step', 'is-current' => $loop->index === $currentIndex, 'is-complete' => $loop->index < $currentIndex])>
            <span><i class="fas {{ $stage[1] }}"></i></span><small>{{ $stage[0] }}</small>
        </div>
    @endforeach
</nav>
