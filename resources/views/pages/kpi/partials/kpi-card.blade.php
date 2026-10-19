{{-- One KPI result card. Param: $result (App\Services\Kpi\KpiResult) --}}
@php
    $kpiStateMeta = [
        'achieved' => ['fa-circle-check', 'محقق'],
        'not_achieved' => ['fa-circle-xmark', 'غير محقق'],
        'no_target' => ['fa-bullseye', 'لم يتم تحديد المستهدف'],
        'informational' => ['fa-circle-info', 'مؤشر وصفي'],
        'insufficient_data' => ['fa-circle-question', 'بيانات غير متوفرة'],
    ][$result->state];
    $kpiTrendIcon = ['up' => 'fa-arrow-trend-up', 'down' => 'fa-arrow-trend-down', 'flat' => 'fa-equals'];
    $kpiTrendText = ['up' => 'ارتفاع', 'down' => 'انخفاض', 'flat' => 'دون تغيير'];
    $kpiCardId = 'kpi-'.\Illuminate\Support\Str::slug($result->code);
@endphp
<article class="kpi-card kpi-card--{{ str_replace('_', '-', $result->state) }}" aria-labelledby="{{ $kpiCardId }}-name">
    <header class="kpi-card__header">
        <span class="kpi-card__icon" aria-hidden="true"><i class="fas {{ $result->icon }}"></i></span>
        <h3 class="kpi-card__name" id="{{ $kpiCardId }}-name">{{ $result->name }}</h3>
        <span class="kpi-card__help" tabindex="0" title="{{ $result->description }}" aria-label="شرح المؤشر: {{ $result->description }}"><i class="fas fa-circle-question" aria-hidden="true"></i></span>
    </header>

    <p class="kpi-card__value {{ $result->dataAvailable ? '' : 'is-unavailable' }}">{{ $result->formattedValue }}</p>
    @if ($result->dataAvailable && $result->unit === 'percentage')
        <p class="kpi-card__fraction">{{ number_format((int) $result->numerator) }} من {{ number_format((int) $result->denominator) }}</p>
    @endif

    <div class="kpi-card__meta">
        <span class="kpi-state kpi-state--{{ str_replace('_', '-', $result->state) }}"><i class="fas {{ $kpiStateMeta[0] }}" aria-hidden="true"></i> {{ $kpiStateMeta[1] }}</span>
        @if ($result->formattedTarget && $result->state !== 'no_target')
            <span class="kpi-card__target">المستهدف: {{ $result->formattedTarget }}</span>
        @endif
    </div>

    @if ($result->trend)
        <p class="kpi-card__trend kpi-card__trend--{{ $result->trend['direction'] }}">
            <i class="fas {{ $kpiTrendIcon[$result->trend['direction']] }}" aria-hidden="true"></i>
            <span>{{ $kpiTrendText[$result->trend['direction']] }} {{ $result->trend['formatted'] }}</span>
            <small>مقارنة بـ {{ $result->comparisonPeriod }} ({{ $result->trend['previous'] }})</small>
        </p>
    @else
        <p class="kpi-card__trend kpi-card__trend--none"><i class="fas fa-minus" aria-hidden="true"></i> <small>لا تتوفر بيانات مقارنة مع {{ $result->comparisonPeriod }}</small></p>
    @endif

    @if ($result->missingReason)
        <p class="kpi-card__reason">{{ $result->missingReason }}</p>
    @endif
    @foreach ($result->notes as $note)
        <p class="kpi-card__note"><i class="fas fa-circle-info" aria-hidden="true"></i> {{ $note }}</p>
    @endforeach

    @if (count($result->breakdown) > 1)
        <details class="kpi-card__breakdown">
            <summary>حسب نوع الفعالية</summary>
            <ul>
                @foreach ($result->breakdown as $row)
                    <li @if($row['reason']) title="{{ $row['reason'] }}" @endif><span>{{ $row['label'] }}</span><strong>{{ $row['formatted'] }}</strong></li>
                @endforeach
            </ul>
        </details>
    @endif

    @if ($result->drilldown)
        <a class="kpi-card__drilldown" href="{{ route($result->drilldown['route'], $result->drilldown['parameters'] ?? []) }}">عرض التفاصيل <i class="fas fa-arrow-left" aria-hidden="true"></i></a>
    @endif
</article>
