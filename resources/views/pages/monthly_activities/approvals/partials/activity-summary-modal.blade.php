@php
    $locationType = $monthlyActivity->location_type === 'outside_center' ? 'خارج المركز' : 'داخل المركز';
    $targetGroupNames = $monthlyActivity->targetGroups->pluck('name')->filter()->values();
    $teamGroups = $monthlyActivity->team->groupBy(fn ($member) => $member->team_name ?: 'فريق العمل');
    $needs = collect([
        'متطوعون' => $monthlyActivity->needs_volunteers,
        'مخاطبات رسمية' => $monthlyActivity->needs_official_correspondence,
        'تغطية إعلامية' => $monthlyActivity->needs_media_coverage,
        'مستلزمات' => $monthlyActivity->supplies->isNotEmpty(),
        'ورش' => $monthlyActivity->requires_workshops,
        'اتصالات' => $monthlyActivity->requires_communications,
        'برامج' => $monthlyActivity->requires_programs,
    ])->filter();
@endphp

<div class="approval-activity-summary">
    <div class="approval-activity-summary__hero mb-3">
        <div>
            <div class="small text-muted mb-1">#{{ $monthlyActivity->id }} · {{ $monthlyActivity->branch?->name ?? '-' }}</div>
            <h3 class="h5 mb-2">{{ $monthlyActivity->title }}</h3>
            <div class="d-flex flex-wrap gap-2">
                <span class="wf-chip wf-chip-primary">الحالة: {{ $statusLabel }}</span>
                <span class="wf-chip wf-chip-soft">حالة التنفيذ: {{ $executionStatusLabel }}</span>
                <span class="wf-chip wf-chip-soft">{{ optional($monthlyActivity->proposed_date)->format('Y-m-d') ?? sprintf('%02d-%02d', $monthlyActivity->month, $monthlyActivity->day) }}</span>
            </div>
        </div>
    </div>

    <div class="approval-activity-summary__grid mb-3">
        <div><span>منشئ النشاط</span><strong>{{ $monthlyActivity->creator?->name ?? '-' }}</strong></div>
        <div><span>فعالية الأجندة</span><strong>{{ $monthlyActivity->agendaEvent?->event_name ?? '-' }}</strong></div>
        <div><span>نوع المكان</span><strong>{{ $locationType }}</strong></div>
        <div><span>الموقع</span><strong>{{ $monthlyActivity->location_type === 'outside_center' ? ($monthlyActivity->outside_place_name ?: '-') : ($monthlyActivity->internal_location ?: '-') }}</strong></div>
        <div><span>وقت التنفيذ</span><strong>{{ $monthlyActivity->time_from ?? '-' }} / {{ $monthlyActivity->time_to ?? '-' }}</strong></div>
        <div><span>الحضور المتوقع</span><strong>{{ $monthlyActivity->expected_attendance ?? '-' }}</strong></div>
    </div>

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="approval-summary-box h-100">
                <h4 class="h6 mb-2">وصف مختصر</h4>
                <p class="text-muted mb-0">{{ $monthlyActivity->short_description ?: ($monthlyActivity->description ?: 'لا يوجد وصف مدخل.') }}</p>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="approval-summary-box h-100">
                <h4 class="h6 mb-2">الفئات المستهدفة</h4>
                @forelse($targetGroupNames as $groupName)
                    <span class="badge rounded-pill bg-light text-dark border mb-1">{{ $groupName }}</span>
                @empty
                    <span class="text-muted small">{{ $monthlyActivity->target_group ?: '-' }}</span>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6">
            <div class="approval-summary-box h-100">
                <h4 class="h6 mb-2">الاحتياجات المطلوبة</h4>
                @forelse($needs as $needLabel => $enabled)
                    <span class="badge rounded-pill bg-info-subtle text-info border mb-1">{{ $needLabel }}</span>
                @empty
                    <span class="text-muted small">لا توجد احتياجات محددة.</span>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6">
            <div class="approval-summary-box h-100">
                <h4 class="h6 mb-2">الشركاء والرعاة</h4>
                <div class="small text-muted mb-1">الرعاة: {{ $monthlyActivity->sponsors->pluck('name')->filter()->implode('، ') ?: '-' }}</div>
                <div class="small text-muted">الشركاء: {{ $monthlyActivity->partners->pluck('name')->filter()->implode('، ') ?: '-' }}</div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="approval-summary-box h-100">
                <h4 class="h6 mb-2">المستلزمات</h4>
                @forelse($monthlyActivity->supplies->take(6) as $supply)
                    <div class="small text-muted">• {{ $supply->item_name }} @if(isset($supply->quantity)) ({{ $supply->quantity }}) @endif</div>
                @empty
                    <span class="text-muted small">لا توجد مستلزمات.</span>
                @endforelse
            </div>
        </div>

        <div class="col-lg-6">
            <div class="approval-summary-box h-100">
                <h4 class="h6 mb-2">فريق العمل</h4>
                @forelse($teamGroups->take(4) as $teamName => $members)
                    <div class="small text-muted">• {{ $teamName }}: {{ $members->pluck('member_name')->filter()->take(4)->implode('، ') ?: '-' }}</div>
                @empty
                    <span class="text-muted small">لا يوجد فريق عمل مدخل.</span>
                @endforelse
            </div>
        </div>
    </div>

    @if($monthlyActivity->outside_google_maps_url)
        <div class="approval-summary-box mt-3">
            <h4 class="h6 mb-2">رابط الموقع</h4>
            <a href="{{ \App\Support\GoogleMaps::navigationUrl($monthlyActivity->outside_google_maps_url, $monthlyActivity->outside_place_name, $monthlyActivity->outside_address) }}" target="_blank" rel="noopener">فتح الاتجاهات في Google Maps</a>
        </div>
    @endif
</div>
