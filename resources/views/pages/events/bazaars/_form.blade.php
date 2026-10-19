@php
$existing = $bazaar ?? null;
$tableRows = old('tables', $existing?->tables?->map(fn($t) => $t->toArray())->all() ?: [['table_number'=>1,'rental_type'=>'individual']]);
$selectedTargets = array_map('intval', old('target_group_ids', $existing?->targetGroupSelections?->pluck('target_group_id')->all() ?? []));
$storedNeeds = $existing?->executionNeeds?->filter(fn($n) => $n->executionNeedType && $n->executionNeedType->code !== 'execution_team')->map(fn($n) => \App\Modules\Events\Support\BazaarExecutionNeeds::fromStored($n))->values()->all() ?? [];
$selectedNeeds = collect(old('execution_needs', $storedNeeds))->filter(fn($row) => is_array($row))->keyBy(fn($row) => (int) ($row['execution_need_type_id'] ?? 0));
$teamGroups = old('team_groups', $existing?->executionTeams?->map(fn($team) => ['team_name'=>$team->name, 'notes'=>$team->notes, 'members'=>$team->members->toArray()])->all() ?: [['team_name'=>'فريق 1', 'notes'=>$existing?->executionNeeds?->first(fn($need) => $need->executionNeedType?->code === 'execution_team')?->planned_details, 'members'=>[[]]]]);
$invitationType = old('invitation_type', $existing?->invitation_type ?? 'public');
@endphp
<div class="bazaar-form-shell" data-error-keys='@json($errors->keys())'>
@include('pages.events.bazaars._lifecycle', ['status' => $existing?->status ?? 'draft'])
@if($errors->any())<div class="alert alert-danger bazaar-validation-summary" tabindex="-1"><i class="fas fa-triangle-exclamation"></i> <strong>يرجى تصحيح الحقول التالية:</strong><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<section class="card bazaar-section-card mb-4"><div class="card-header bazaar-section-head"><span class="bazaar-section-icon"><i class="fas fa-store"></i></span><div><strong><span>١</span> {{ __('bazaars.sections.info') }}</strong><small>الهوية الأساسية وموعد فتح السوق وإغلاقه.</small></div></div><div class="card-body row g-3">
<div class="col-md-6"><label class="form-label">اسم البازار</label><input required class="form-control" name="name" value="{{ old('name',$existing?->name) }}" placeholder="مثال: بازار المنتجات المحلية"></div>
<div class="col-md-2"><label class="form-label">تاريخ البازار</label><input required type="date" class="form-control" name="bazaar_date" value="{{ old('bazaar_date',$existing?->bazaar_date?->format('Y-m-d') ?? request('date')) }}"></div>
<div class="col-md-2"><label class="form-label">وقت البداية</label><input required type="time" class="form-control" name="starts_at" value="{{ old('starts_at',substr((string)$existing?->starts_at,0,5)) }}"></div>
<div class="col-md-2"><label class="form-label">وقت النهاية</label><input required type="time" class="form-control" name="ends_at" value="{{ old('ends_at',substr((string)$existing?->ends_at,0,5)) }}"></div>
<div class="col-md-4"><label class="form-label" for="bazaar-days">{{ __('bazaars.day_count') }}</label><input id="bazaar-days" class="form-control" required type="number" min="1" step="1" name="day_count" value="{{ old('day_count',$existing?->day_count ?? 1) }}"><div class="form-text">يبدأ البازار في التاريخ المحدد؛ وقت البداية والنهاية لساعات العمل اليومية.</div></div>
</div></section>
<section class="card bazaar-section-card mb-4"><div class="card-header bazaar-section-head"><span class="bazaar-section-icon"><i class="fas fa-location-dot"></i></span><div><strong><span>٢</span> {{ __('bazaars.sections.location') }}</strong><small>حدد مكان البازار وأضف تفاصيل واضحة للوصول.</small></div></div><div class="card-body row g-3"><div class="col-md-4"><label class="form-label">مكان البازار</label><select class="form-select" name="location_type" data-location-type><option value="inside" {{ (old('location_type',$existing?->location_type)==='inside') ? 'selected' : '' }}>داخل المركز</option><option value="outside" {{ (old('location_type',$existing?->location_type)==='outside') ? 'selected' : '' }}>خارج المركز</option></select></div><div class="col-12 col-md-8" data-location-name-field><label class="form-label" data-location-label>{{ old('location_type',$existing?->location_type)==='outside' ? 'اسم الموقع الخارجي' : 'المكان داخل المركز' }}</label><input required class="form-control" name="location_name" value="{{ old('location_name',$existing?->location_name) }}" placeholder="مثال: الساحة الرئيسية"></div><div class="col-12 col-md-8" data-location-details-field><label class="form-label" data-location-details-label>تفاصيل الموقع</label><textarea class="form-control" name="location_details" placeholder="اكتب تفاصيل الوصول والمكان">{{ old('location_details',$existing?->location_details) }}</textarea></div><div class="col-md-4" data-location-map-field><label class="form-label">رابط الموقع على الخريطة (اختياري)</label><input type="url" class="form-control" name="map_url" value="{{ old('map_url',$existing?->map_url) }}" placeholder="https://maps.google.com/..."></div></div></section>
<section class="card bazaar-section-card mb-4"><div class="card-header bazaar-section-head"><span class="bazaar-section-icon"><i class="fas fa-people-group"></i></span><div><strong><span>٣</span> {{ __('bazaars.sections.targets') }}</strong><small>اختر الفئات من الدليل الموحد للفعاليات.</small></div></div><div class="card-body bazaar-target-grid">@foreach($targetGroups as $group)<label class="form-check"><input class="form-check-input" type="checkbox" name="target_group_ids[]" value="{{ $group->id }}" data-target-id="{{ $group->id }}" {{ (in_array($group->id,$selectedTargets)) ? 'checked' : '' }}><span class="form-check-label">{{ $group->name }}</span></label>@if($group->code==='other' || $group->type==='other' || $group->is_other)<div data-other-target="{{ $group->id }}"><label class="form-label" for="target-notes-{{ $group->id }}">تفاصيل الفئة الأخرى</label><textarea id="target-notes-{{ $group->id }}" class="form-control" name="target_group_notes[{{ $group->id }}]" maxlength="2000" placeholder="مثال: أمهات الأطفال المشاركين أو فئة مجتمعية محددة">{{ old('target_group_notes.'.$group->id, $existing?->targetGroupSelections?->firstWhere('target_group_id',$group->id)?->target_group_custom_text) }}</textarea></div>@endif
@endforeach</div></section>
<section class="card bazaar-section-card mb-4"><div class="card-header bazaar-section-head"><span class="bazaar-section-icon"><i class="fas fa-bullhorn"></i></span><div><strong><span>٤</span> {{ __('bazaars.sections.invitation') }}</strong><small>لمن يُوجَّه البازار: دعوة عامة أو دعوة خاصة لفئة محددة.</small></div></div><div class="card-body row g-3">
<div class="col-md-4"><label class="form-label" for="bazaar-invitation">{{ __('bazaars.invitation_type') }}</label><select id="bazaar-invitation" name="invitation_type" class="form-select">@foreach(__('bazaars.invitation_types') as $value => $label)<option value="{{ $value }}" {{ $invitationType === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach</select></div>
<div class="col-md-8" data-private-audience><label class="form-label" for="bazaar-private">{{ __('bazaars.private_audience') }}</label><textarea id="bazaar-private" name="private_audience" class="form-control @error('private_audience') is-invalid @enderror" maxlength="2000" placeholder="مثال: سيدات المجتمع المحلي، أصحاب المشاريع المنزلية، شركاء المركز">{{ old('private_audience',$existing?->private_audience) }}</textarea>@error('private_audience')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
</div></section>
<section class="card bazaar-section-card mb-4" data-need-activation><div class="card-header bazaar-section-head"><span class="bazaar-section-icon"><i class="fas fa-list-check"></i></span><div><strong><span>٥</span> {{ __('bazaars.sections.needs') }}</strong><small>{{ __('bazaars.needs.activation_help') }}</small></div></div><div class="card-body">
@error('execution_needs')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
@if($executionNeedTypes->isEmpty())<p class="text-muted mb-0">{{ __('bazaars.needs.none_available') }}</p>@else
<div class="bazaar-activation-grid">
@foreach($executionNeedTypes as $i=>$need)
@php($row = $selectedNeeds->get($need->id, []))
@php($mandatory = $need->isMandatoryForBazaar())
@php($active = $mandatory || (bool) ($row['selected'] ?? false))
<label @class(['bazaar-activation-option', 'is-mandatory' => $mandatory, 'has-error' => $errors->has("execution_needs.$i.*")]) data-need-option="{{ $need->id }}">
    <span class="bazaar-activation-icon" aria-hidden="true"><i class="fas {{ \App\Modules\Events\Support\BazaarExecutionNeeds::icon($need->code) }}"></i></span>
    <span class="bazaar-activation-text"><strong data-need-name>{{ $need->name }}</strong>@if($need->description)<small>{{ $need->description }}</small>@endif</span>
    <input type="hidden" name="execution_needs[{{ $i }}][execution_need_type_id]" value="{{ $need->id }}">
    @if($mandatory)
        <input type="hidden" name="execution_needs[{{ $i }}][selected]" value="1">
        <span class="badge bazaar-mandatory-badge">{{ __('bazaars.needs.mandatory') }}</span>
        <span class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" checked disabled data-need-toggle="{{ $need->id }}" aria-controls="bazaar-need-details-{{ $need->id }}" aria-label="{{ $need->name }} — {{ __('bazaars.needs.mandatory') }}"></span>
    @else
        <input type="hidden" name="execution_needs[{{ $i }}][selected]" value="0">
        <span class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" name="execution_needs[{{ $i }}][selected]" value="1" {{ $active ? 'checked' : '' }} data-need-toggle="{{ $need->id }}" aria-controls="bazaar-need-details-{{ $need->id }}" aria-expanded="{{ $active ? 'true' : 'false' }}"></span>
    @endif
</label>
@endforeach
</div>
@endif
</div></section>
<section class="card bazaar-section-card mb-4" data-need-details-section><div class="card-header bazaar-section-head"><span class="bazaar-section-icon"><i class="fas fa-sliders"></i></span><div><strong><span>٦</span> {{ __('bazaars.sections.need_details') }}</strong><small>{{ __('bazaars.needs.details_help') }}</small></div></div><div class="card-body d-grid gap-3">
<p class="text-muted mb-0" data-need-details-empty>{{ __('bazaars.needs.none_active') }}</p>
@foreach($executionNeedTypes as $i=>$need)
@php($row = $selectedNeeds->get($need->id, []))
@php($active = $need->isMandatoryForBazaar() || (bool) ($row['selected'] ?? false))
@php($field = fn (string $key) => "execution_needs[$i][$key]")
<div class="bazaar-subsection-card" id="bazaar-need-details-{{ $need->id }}" data-need-details="{{ $need->id }}" @unless($active) hidden @endunless>
    <h3 class="bazaar-subsection-title"><i class="fas {{ \App\Modules\Events\Support\BazaarExecutionNeeds::icon($need->code) }}" aria-hidden="true"></i> {{ \App\Modules\Events\Support\BazaarExecutionNeeds::title($need) }}@if($need->isMandatoryForBazaar())<span class="badge bazaar-mandatory-badge">{{ __('bazaars.needs.mandatory') }}</span>@endif</h3>
    @if($need->code === \App\Modules\Events\Support\BazaarExecutionNeeds::INVITATIONS)
        @php($cardType = $row['invitation_type'] ?? '')
        @php($channels = (array) ($row['distribution_channels'] ?? []))
        <p class="form-text mt-0">{{ __('bazaars.invitation_cards.help') }}</p>
        <div class="row g-3" data-invitation-cards>
            <div class="col-12 col-md-4">
                <label class="form-label" for="bazaar-card-type-{{ $need->id }}">{{ __('bazaars.invitation_cards.card_type') }}</label>
                <select id="bazaar-card-type-{{ $need->id }}" class="form-select @error("execution_needs.$i.invitation_type") is-invalid @enderror" name="{{ $field('invitation_type') }}" data-invitation-card-type required>
                    <option value="">{{ __('bazaars.invitation_cards.choose') }}</option>
                    @foreach(__('bazaars.invitation_cards.types') as $value => $label)<option value="{{ $value }}" {{ $cardType === $value ? 'selected' : '' }}>{{ $label }}</option>@endforeach
                </select>
                @error("execution_needs.$i.invitation_type")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-5" data-invitation-paper @unless($cardType === 'paper') hidden @endunless>
                <label class="form-label" for="bazaar-paper-template-{{ $need->id }}">{{ __('bazaars.invitation_cards.paper_template') }}</label>
                <input id="bazaar-paper-template-{{ $need->id }}" class="form-control @error("execution_needs.$i.paper_template") is-invalid @enderror" name="{{ $field('paper_template') }}" maxlength="500" value="{{ $row['paper_template'] ?? '' }}">
                @error("execution_needs.$i.paper_template")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-3" data-invitation-paper @unless($cardType === 'paper') hidden @endunless>
                <label class="form-label" for="bazaar-paper-copies-{{ $need->id }}">{{ __('bazaars.invitation_cards.paper_copies') }}</label>
                <input id="bazaar-paper-copies-{{ $need->id }}" type="number" min="1" class="form-control @error("execution_needs.$i.paper_copies") is-invalid @enderror" name="{{ $field('paper_copies') }}" value="{{ $row['paper_copies'] ?? '' }}">
                @error("execution_needs.$i.paper_copies")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12" data-invitation-electronic @unless($cardType === 'electronic') hidden @endunless>
                <fieldset>
                    <legend class="form-label fs-6">{{ __('bazaars.invitation_cards.channels') }} <small class="text-muted">— {{ __('bazaars.invitation_cards.channels_help') }}</small></legend>
                    <div @class(['bazaar-channel-grid', 'is-invalid' => $errors->has("execution_needs.$i.distribution_channels") || $errors->has("execution_needs.$i.distribution_channels.*")])>
                        @foreach(__('bazaars.invitation_cards.channel_options') as $value => $label)
                            <label class="bazaar-channel-option"><input class="form-check-input" type="checkbox" name="{{ $field('distribution_channels') }}[]" value="{{ $value }}" data-invitation-channel {{ in_array($value, $channels, true) ? 'checked' : '' }}><span>{{ $label }}</span></label>
                        @endforeach
                    </div>
                    @error("execution_needs.$i.distribution_channels")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error("execution_needs.$i.distribution_channels.*")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </fieldset>
            </div>
            <div class="col-12 col-md-6" data-invitation-other @unless($cardType === 'electronic' && in_array('other', $channels, true)) hidden @endunless>
                <label class="form-label" for="bazaar-channel-other-{{ $need->id }}">{{ __('bazaars.invitation_cards.distribution_other') }}</label>
                <input id="bazaar-channel-other-{{ $need->id }}" class="form-control @error("execution_needs.$i.distribution_other") is-invalid @enderror" name="{{ $field('distribution_other') }}" maxlength="500" value="{{ $row['distribution_other'] ?? '' }}" placeholder="{{ __('bazaars.invitation_cards.distribution_other_placeholder') }}">
                @error("execution_needs.$i.distribution_other")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="col-12 col-md-6" data-invitation-electronic @unless($cardType === 'electronic') hidden @endunless>
                <label class="form-label" for="bazaar-electronic-template-{{ $need->id }}">{{ __('bazaars.invitation_cards.electronic_template') }}</label>
                <input id="bazaar-electronic-template-{{ $need->id }}" class="form-control @error("execution_needs.$i.electronic_template") is-invalid @enderror" name="{{ $field('electronic_template') }}" maxlength="500" value="{{ $row['electronic_template'] ?? '' }}">
                @error("execution_needs.$i.electronic_template")<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    @else
        <label class="form-label" for="bazaar-need-text-{{ $need->id }}">{{ __('bazaars.needs.planned_details') }}</label>
        <textarea id="bazaar-need-text-{{ $need->id }}" class="form-control @error("execution_needs.$i.planned_details") is-invalid @enderror" name="{{ $field('planned_details') }}" maxlength="2000" rows="2" required placeholder="{{ __('bazaars.needs.planned_details_placeholder') }}">{{ $row['planned_details'] ?? '' }}</textarea>
        @error("execution_needs.$i.planned_details")<div class="invalid-feedback">{{ $message }}</div>@enderror
    @endif
</div>
@endforeach
</div></section>
@include('pages.events.bazaars._team')
<section class="card bazaar-section-card mb-4"><div class="card-header bazaar-section-head bazaar-section-head--tables"><span class="bazaar-section-icon"><i class="fas fa-table-cells-large"></i></span><div class="flex-grow-1"><strong><span>٨</span> {{ __('bazaars.table_reservations') }}</strong><small>أكمل بيانات حجوزات الطاولات المخططة، ويمكن طي بطاقات الحجز الطويلة.</small></div><div class="bazaar-table-count-field"><label class="form-label" for="bazaar-table-count">{{ __('bazaars.attributes.planned_table_count') }}</label><input id="bazaar-table-count" class="form-control @error('planned_table_count') is-invalid @enderror" type="number" min="1" max="500" name="planned_table_count" data-table-count value="{{ old('planned_table_count',$existing?->planned_table_count ?? count($tableRows)) }}"></div></div><div class="card-body" data-tables>@foreach($tableRows as $i=>$table)@include('pages.events.bazaars._table_card',['i'=>$i,'table'=>$table])@endforeach</div></section>
<section class="card bazaar-section-card bazaar-review-card mb-4"><div class="card-header bazaar-section-head"><span class="bazaar-section-icon"><i class="fas fa-clipboard-check"></i></span><div><strong><span>٩</span> {{ __('bazaars.sections.review') }}</strong><small>تأكد من اكتمال الأقسام والطاولات قبل المتابعة.</small></div></div><div class="card-body text-muted">{{ __('bazaars.review.intro') }}
<div class="mt-2">{{ __('bazaars.day_count') }}: <strong data-review-days></strong> · {{ __('bazaars.invitation_type') }}: <strong data-review-invitation></strong></div>
<div class="mt-3"><strong class="d-block mb-1 text-body">{{ __('bazaars.review.needs') }}</strong><ul class="bazaar-review-needs" data-review-needs data-label-mandatory="{{ __('bazaars.needs.mandatory') }}" data-label-enabled="{{ __('bazaars.needs.enabled') }}" data-label-card-type="{{ __('bazaars.invitation_cards.card_type') }}" data-label-channels="{{ __('bazaars.invitation_cards.channels') }}" data-label-empty="{{ __('bazaars.needs.none_saved') }}"></ul></div></div></section>
</div>
<template id="bazaar-table-template">@include('pages.events.bazaars._table_card',['i'=>'__INDEX__','table'=>[]])</template>
@push('styles')<link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/bazaar-form.css') }}">@endpush
@push('scripts')<script src="{{ \App\Support\AssetVersion::url('assets/js/bazaar-form.js') }}" defer></script>@endpush

@push('scripts')<script src="{{ \App\Support\AssetVersion::url('assets/js/event-planning-actions.js') }}" defer></script>@endpush
