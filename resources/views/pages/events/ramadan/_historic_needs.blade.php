@if($ramadanIftar)
@php
    $historicCollections = [
        'gifts' => $ramadanIftar->gifts->toArray(),
        'supplies' => $ramadanIftar->supplies->toArray(),
        'execution_teams' => $ramadanIftar->executionTeams->toArray(),
        'volunteer_requirements' => $ramadanIftar->volunteerRequirements->map(function ($requirement) {
            return array_merge($requirement->toArray(), ['beneficiary_segment' => $requirement->beneficiarySegment?->toArray()]);
        })->toArray(),
    ];
@endphp
@foreach(\App\Modules\Events\Models\ExecutionNeedType::IFTAR_DETAIL_FIELDS as $code => $field)
    @if(! $executionNeedTypes->contains('code', $code) && ! empty($historicCollections[$field]))
        <section class="alert alert-secondary" data-historic-need="{{ $code }}">
            <h6>{{ \App\Modules\Events\Models\ExecutionNeedType::CANONICAL_DEFINITIONS[$code]['name'] }} — للقراءة فقط</h6>
            <p>هذا الاحتياج غير متاح حاليًا. بياناته السابقة محفوظة ولن تتغير عند حفظ النموذج.</p>
            <p>{{ $ramadanIftar->executionNeeds->first(fn ($need) => $need->executionNeedType?->code === $code)?->planned_details }}</p>
            @foreach($historicCollections[$field] as $row)
                <div class="border-top py-2">
                    @if($field === 'execution_teams')
                        <strong>{{ $row['name'] ?? '' }}</strong>
                        <span>العدد المخطط: {{ $row['planned_members_count'] ?? '—' }}</span>
                        @foreach($row['members'] ?? [] as $member)
                            <div>{{ $member['member_name'] ?? '' }} · {{ $member['role_name'] ?? '' }} · {{ $member['task_description'] ?? '' }}</div>
                        @endforeach
                    @elseif($field === 'volunteer_requirements')
                        <div>العدد المخطط: {{ $row['planned_count'] ?? '—' }} · {{ $row['gender'] ?? '' }}</div>
                        @if(($row['beneficiary_segment']['minimum_age'] ?? null) !== null || ($row['beneficiary_segment']['maximum_age'] ?? null) !== null)<div>الفئة العمرية: {{ $row['beneficiary_segment']['minimum_age'] ?? '—' }} — {{ $row['beneficiary_segment']['maximum_age'] ?? '—' }}</div>@endif
                        <div>{{ $row['tasks_summary'] ?? '' }}</div>
                    @else
                        <strong>{{ $row['description'] ?? $row['item_name'] ?? '' }}</strong>
                        <span>الكمية: {{ $row['planned_quantity'] ?? '—' }}</span>
                        @if($field === 'gifts')<div>النوع: {{ $row['gift_type'] ?? '—' }} · قيمة الوحدة: {{ $row['unit_value'] ?? '—' }}</div><div>الجهة الداعمة: {{ $row['supporting_entity_name'] ?? '—' }}</div>@else<div>{{ $row['provider_name'] ?? $row['supporting_entity_name'] ?? '' }}</div>@endif
                        <div>{{ $row['notes'] ?? '' }}</div>
                    @endif
                </div>
            @endforeach
        </section>
    @endif
@endforeach
@foreach($ramadanIftar->executionNeeds as $historicNeed)
    @php($detailField = \App\Modules\Events\Models\ExecutionNeedType::IFTAR_DETAIL_FIELDS[$historicNeed->executionNeedType?->code] ?? null)
    @if(! $executionNeedTypes->contains('id', $historicNeed->execution_need_type_id) && (! $detailField || empty($historicCollections[$detailField])))
        <section class="alert alert-secondary">
            <h6>{{ $historicNeed->executionNeedType?->name }} — للقراءة فقط</h6>
            <div>{{ $historicNeed->planned_details ?: 'محدد ضمن الخطة السابقة.' }}</div>
        </section>
    @endif
@endforeach
@endif
