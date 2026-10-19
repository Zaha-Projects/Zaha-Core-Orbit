{{-- Module summary card. Params: $summary, $query (filters to carry), $linked (bool) --}}
<article class="kpi-module kpi-module--{{ $summary['accent'] }}" aria-labelledby="kpi-module-{{ $summary['key'] }}">
    <header class="kpi-module__header">
        <span class="kpi-module__icon" aria-hidden="true"><i class="fas {{ $summary['icon'] }}"></i></span>
        <div>
            <h3 class="kpi-module__title" id="kpi-module-{{ $summary['key'] }}">{{ $summary['label'] }}</h3>
            <p class="kpi-module__records">
                السجلات الحالية في الفترة (تشمل المسودات):
                <strong>{{ $summary['records'] === null ? \App\Services\Kpi\KpiFormatter::UNAVAILABLE : number_format($summary['records']) }}</strong>
            </p>
        </div>
    </header>
    <dl class="kpi-module__stages">
        @foreach ($summary['stages'] as $stage)
            <div class="kpi-module__stage {{ $stage['value'] === null ? 'is-unavailable' : '' }}" @if($stage['reason']) title="{{ $stage['reason'] }}" @endif>
                <dt>{{ $stage['label'] }}</dt>
                <dd>{{ $stage['formatted'] }}</dd>
            </div>
        @endforeach
    </dl>
    @foreach ($summary['notes'] as $note)
        <p class="kpi-module__note"><i class="fas fa-circle-info" aria-hidden="true"></i> {{ $note }}</p>
    @endforeach
    @if ($linked ?? true)
        <a class="kpi-module__link" href="{{ route('kpi.events.show', [$summary['key']] + ($query ?? [])) }}">مؤشرات {{ $summary['label'] }} <i class="fas fa-arrow-left" aria-hidden="true"></i></a>
    @endif
</article>
