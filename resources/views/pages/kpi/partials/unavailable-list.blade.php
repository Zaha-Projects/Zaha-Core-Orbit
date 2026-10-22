{{-- KPIs that cannot be computed from current data. Param: $unavailable (KpiResult[]) --}}
@if ($unavailable !== [])
    <section class="kpi-section" aria-labelledby="{{ $unavailableId ?? 'kpi-unavailable-title' }}">
        <div class="kpi-section__head">
            <h2 id="{{ $unavailableId ?? 'kpi-unavailable-title' }}"><i class="fas fa-ban" aria-hidden="true"></i> مؤشرات غير متاحة حالياً</h2>
            <p>لا تُعرض لها قيم لأن البيانات اللازمة غير مسجلة أو بانتظار قرار إداري.</p>
        </div>
        <ul class="kpi-unavailable">
            @foreach ($unavailable as $result)
                @php($kpiStatusMeta = config('kpis.statuses.'.$result->dataStatus, ['label_ar' => $result->dataStatus, 'icon' => 'fa-ban']))
                <li class="kpi-unavailable__item">
                    <div class="kpi-unavailable__head">
                        <strong>{{ $result->name }}</strong> <small class="kpi-card__code">{{ $result->code }}</small>
                        <span class="kpi-status kpi-status--{{ strtolower($result->dataStatus) }}"><i class="fas {{ $kpiStatusMeta['icon'] }}" aria-hidden="true"></i> {{ $kpiStatusMeta['label_ar'] }}</span>
                    </div>
                    <p>{{ $result->missingReason ?? 'لا تتوفر بيانات.' }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endif
