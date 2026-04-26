@php
    $existingMonthlyActivity = $monthlyActivity ?? null;
    $formUser = auth()->user();
    $isBranchScopedUser = $formUser
        && method_exists($formUser, 'hasBranchScopedMonthlyVisibility')
        && $formUser->hasBranchScopedMonthlyVisibility()
        && ! empty($formUser->branch_id);
    $selectedBranch = $branches->firstWhere('id', old('branch_id', $existingMonthlyActivity?->branch_id ?? $formUser?->branch_id));
    $linkedAgendaEventId = old('agenda_event_id', $existingMonthlyActivity?->agenda_event_id);
    $needsVolunteersChecked = (bool) old('needs_volunteers', $existingMonthlyActivity?->needs_volunteers ?? false);
    $needsOfficialCorrespondenceChecked = (bool) old('needs_official_correspondence', $existingMonthlyActivity?->needs_official_correspondence ?? false);
    $needsMediaCoverageChecked = (bool) old('needs_media_coverage', $existingMonthlyActivity?->needs_media_coverage ?? false);
    $requiresSuppliesChecked = (bool) old('requires_supplies', $existingMonthlyActivity?->supplies?->isNotEmpty() ?? false);
    $hasSponsorChecked = (bool) old('has_sponsor', $existingMonthlyActivity?->has_sponsor ?? false);
    $hasPartnersChecked = (bool) old('has_partners', $existingMonthlyActivity?->has_partners ?? false);
    $selectedExecutionStatus = old('execution_status', $existingMonthlyActivity?->execution_status ?? 'executed');
    $selectedTargetGroupIds = array_map('intval', old('target_group_ids', $existingMonthlyActivity?->targetGroups?->pluck('id')->all() ?? []));
    $oldSponsors = old('sponsors', $existingMonthlyActivity
        ? $existingMonthlyActivity->sponsors->map(fn ($sponsor) => [
            'name' => $sponsor->name,
            'title' => $sponsor->title,
        ])->values()->all()
        : []);
    $oldSponsors = $oldSponsors === [] ? [['name' => null, 'title' => null]] : $oldSponsors;
    $oldPartners = old('partners', $existingMonthlyActivity
        ? $existingMonthlyActivity->partners->map(fn ($partner) => [
            'name' => $partner->name,
            'role' => $partner->role,
            'contact_info' => $partner->contact_info,
        ])->values()->all()
        : []);
    $oldSupplies = old('supplies', $existingMonthlyActivity
        ? $existingMonthlyActivity->supplies->map(fn ($supply) => [
            'item_name' => $supply->item_name,
            'available' => (int) $supply->available,
            'insurance_mechanism' => $supply->provider_type,
            'insurance_other_details' => $supply->provider_name,
        ])->values()->all()
        : []);
    $groupedTeam = $existingMonthlyActivity?->team
        ? $existingMonthlyActivity->team->groupBy(fn ($member) => $member->team_name ?: 'فريق')
        : collect();
    $oldTeamGroups = old('team_groups', $groupedTeam->values()->map(function ($group, $index) {
        return [
            'team_name' => $group->first()->team_name ?: 'فريق ' . ($index + 1),
            'members' => $group->map(fn ($member) => [
                'member_name' => $member->member_name,
                'role_desc' => $member->role_desc,
            ])->values()->all(),
        ];
    })->all());
    $partnersCount = max(1, count($oldPartners));
    $suppliesCount = max(1, count($oldSupplies));
    $teamGroupsCount = max(1, count($oldTeamGroups));
    $selectedZahaTimeOptions = old('programs_zaha_time_options', []);
    $oldCeremonyItems = old('ceremony_items', []);
    if ($oldCeremonyItems === [] && (filled(old('ceremony_item_name')) || filled(old('ceremony_item_description')) || filled(old('ceremony_time_from')) || filled(old('ceremony_time_to')))) {
        $oldCeremonyItems = [[
            'name' => old('ceremony_item_name'),
            'description' => old('ceremony_item_description'),
            'time_from' => old('ceremony_time_from'),
            'time_to' => old('ceremony_time_to'),
        ]];
    }
    $oldCeremonyItems = $oldCeremonyItems === [] ? [['name' => null, 'description' => null, 'time_from' => null, 'time_to' => null]] : $oldCeremonyItems;
    $programsNeedTrainerChecked = old('programs_need_trainer', '0') === '1';
    $programsNeedsZahaTimeChecked = old('programs_needs_zaha_time', $selectedZahaTimeOptions === [] ? '0' : '1') === '1';
    $programsNeedsShowChecked = old('programs_needs_show', (filled(old('programs_show_name')) || filled(old('programs_show_description'))) ? '1' : '0') === '1';
    $programsNeedsFunChecked = old('programs_needs_fun', filled(old('programs_fun_note')) ? '1' : '0') === '1';
    $certificatesDetailsChecked = old('needs_certificates_details', (filled(old('certificates_count')) || filled(old('certificates_template')) || filled(old('certificates_for'))) ? '1' : '0') === '1';
    $thanksLettersDetailsChecked = old('needs_thanks_letters_details', (filled(old('thanks_letters_count')) || filled(old('thanks_letters_template')) || filled(old('thanks_letters_for'))) ? '1' : '0') === '1';
    $isUnifiedMandatory = $existingMonthlyActivity
        && (bool) $existingMonthlyActivity->is_from_agenda
        && (string) $existingMonthlyActivity->plan_type === 'unified'
        && (string) optional($existingMonthlyActivity->agendaEvent)->event_type === 'mandatory';
    $unifiedLockedFields = collect(config('monthly_activity.unified_branch_edit.locked_fields', []))
        ->filter(fn ($field) => is_string($field) && $field !== '')
        ->values()
        ->all();
    $isUnifiedBranchEditMode = $isUnifiedMandatory && $isBranchScopedUser && (bool) config('monthly_activity.unified_branch_edit.enabled', true);
    $isLockedField = fn (string $field): bool => $isUnifiedBranchEditMode && in_array($field, $unifiedLockedFields, true);
@endphp

<div class="event-module monthly-plan-form-page">
    <div class="card event-card">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-2">يرجى تصحيح الأخطاء التالية:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="row g-3 event-form-grid monthly-plan-form">
                @csrf
                @if(($formMethod ?? 'POST') !== 'POST')
                    @method($formMethod)
                @endif
                <input type="hidden" name="status" value="draft">
                <input type="hidden" name="execution_status" value="{{ old('execution_status', $existingMonthlyActivity?->execution_status ?? 'executed') }}">
                <input type="hidden" class="js-activity-date" name="activity_date" value="{{ old('activity_date', optional($existingMonthlyActivity?->activity_date)->format('Y-m-d') ?: optional($existingMonthlyActivity?->proposed_date)->format('Y-m-d')) }}">
                @if (filled($linkedAgendaEventId))
                    <input type="hidden" name="agenda_event_id" value="{{ $linkedAgendaEventId }}">
                    <input type="hidden" name="is_in_agenda" value="1">
                @endif
                @if ($isUnifiedBranchEditMode)
                    <div class="col-12">
                        <div class="alert alert-primary py-2 px-3 mb-0">
                            هذه الفعالية موحدة: تبقى بيانات التخطيط الأساسية موحدة من الإدارة العامة، بينما يمكن للفرع تعديل بيانات التنفيذ المحلية.
                        </div>
                    </div>
                @endif

                <div class="col-12">
                    <div class="monthly-form-section-head monthly-form-section-head--planning">
                        <h2 class="h6 mb-1">بيانات التخطيط الأساسية</h2>
                        <p class="text-muted small mb-0">هذه الشاشة مخصصة للتخطيط قبل التنفيذ، بينما تحديث حالة التنفيذ يتم في وضع إكمال التعبئة بعد التنفيذ.</p>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">عنوان النشاط</label>
                    <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $existingMonthlyActivity?->title) }}" {{ $isLockedField('title') ? 'readonly' : '' }}>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">تاريخ النشاط المخطط</label>
                    <input class="form-control js-proposed-date @error('proposed_date') is-invalid @enderror" type="date" name="proposed_date" value="{{ old('proposed_date', optional($existingMonthlyActivity?->proposed_date)->format('Y-m-d')) }}" {{ $isLockedField('proposed_date') ? 'readonly' : '' }}>
                    @error('proposed_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الفرع</label>
                    @if ($isBranchScopedUser && $selectedBranch)
                        <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                        <input class="form-control" value="{{ $selectedBranch->name }}" readonly>
                    @else
                        <select class="form-select @error('branch_id') is-invalid @enderror" name="branch_id" {{ $isLockedField('branch_id') ? 'disabled' : '' }}>
                            <option value="">اختر الفرع</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id', $existingMonthlyActivity?->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @if ($isLockedField('branch_id'))
                            <input type="hidden" name="branch_id" value="{{ old('branch_id', $existingMonthlyActivity?->branch_id) }}">
                        @endif
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-12">
                    <div class="monthly-form-section-head monthly-form-section-head--location">
                        <h2 class="h6 mb-1">المكان</h2>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">نوع المكان</label>
                    <select class="form-select js-location-type @error('location_type') is-invalid @enderror" name="location_type">
                        <option value="inside_center" {{ old('location_type', $existingMonthlyActivity?->location_type ?? 'inside_center') === 'inside_center' ? 'selected' : '' }}>داخل المركز (داخل مرافق زها)</option>
                        <option value="outside_center" {{ old('location_type', $existingMonthlyActivity?->location_type) === 'outside_center' ? 'selected' : '' }}>خارج المركز (موقع خارجي)</option>
                    </select>
                    @error('location_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4 js-inside-location">
                    <label class="form-label">القاعة / الموقع الداخلي</label>
                    <input class="form-control" name="internal_location" value="{{ old('internal_location', $existingMonthlyActivity?->internal_location) }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">اسم الموقع الخارجي</label>
                    <input class="form-control" name="outside_place_name" value="{{ old('outside_place_name', $existingMonthlyActivity?->outside_place_name) }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رابط الموقع</label>
                    <input class="form-control" name="outside_google_maps_url" value="{{ old('outside_google_maps_url', $existingMonthlyActivity?->outside_google_maps_url) }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">اسم ضابط الارتباط</label>
                    <input class="form-control" name="external_liaison_name" value="{{ old('external_liaison_name', $existingMonthlyActivity?->external_liaison_name) }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رقم ضابط الارتباط</label>
                    <input class="form-control" name="external_liaison_phone" value="{{ old('external_liaison_phone', $existingMonthlyActivity?->external_liaison_phone) }}">
                </div>

                <div class="col-12 mt-2">
                    <div class="monthly-form-section-head monthly-form-section-head--time">
                        <h2 class="h6 mb-1">الوقت</h2>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الوقت من</label>
                    <input class="form-control" type="time" name="time_from" value="{{ old('time_from', optional($existingMonthlyActivity?->time_from)->format('H:i')) }}">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الوقت إلى</label>
                    <input class="form-control" type="time" name="time_to" value="{{ old('time_to', optional($existingMonthlyActivity?->time_to)->format('H:i')) }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">العنوان التفصيلي</label>
                    <input class="form-control" name="outside_address" value="{{ old('outside_address', $existingMonthlyActivity?->outside_address) }}">
                </div>

                <div class="col-12">
                    <label class="form-label">الوصف التفصيلي</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" name="description" rows="3" placeholder="اكتب وصفًا تفصيليًا للنشاط (الفكرة، الأهداف، الفقرات، الفئة المستهدفة، المخرجات المتوقعة)">{{ old('description', $existingMonthlyActivity?->description) }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label">الفئة المستهدفة</label>
                    <div class="partner-departments-box">
                        @foreach($targetGroups as $group)
                            <label class="partner-department-item">
                                <input class="form-check-input m-0 js-target-group-checkbox" type="checkbox" name="target_group_ids[]" value="{{ $group->id }}" data-is-other="{{ $group->is_other ? 1 : 0 }}" {{ in_array((int) $group->id, $selectedTargetGroupIds, true) ? 'checked' : '' }} {{ $isLockedField('target_group_ids') ? 'disabled' : '' }}>
                                <span>{{ $group->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="col-12 col-md-6 js-target-group-other">
                    <label class="form-label">أخرى (توضيح)</label>
                    <input class="form-control" name="target_group_other" value="{{ old('target_group_other', $existingMonthlyActivity?->target_group_other) }}" {{ $isLockedField('target_group_ids') ? 'readonly' : '' }}>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">عدد الحضور المتوقع</label>
                    <input class="form-control" type="number" min="0" name="expected_attendance" value="{{ old('expected_attendance', $existingMonthlyActivity?->expected_attendance) }}">
                </div>

                <div class="col-12">
                    <div class="monthly-form-section-head monthly-form-section-head--execution">
                        <h2 class="h6 mb-1">احتياجات التنفيذ</h2>
                    </div>
                </div>

                <div class="col-12">
                    <div class="monthly-activation-grid">
                        <label class="monthly-activation-option">
                            <span>الحاجة للمتطوعين</span>
                            <input type="hidden" name="needs_volunteers" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-volunteers" type="checkbox" role="switch" name="needs_volunteers" value="1" {{ $needsVolunteersChecked ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة للمخاطبة الرسمية</span>
                            <input type="hidden" name="needs_official_correspondence" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-letters" type="checkbox" role="switch" name="needs_official_correspondence" value="1" {{ $needsOfficialCorrespondenceChecked ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لتغطية إعلامية</span>
                            <input type="hidden" name="needs_media_coverage" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-media" type="checkbox" role="switch" name="needs_media_coverage" value="1" {{ $needsMediaCoverageChecked ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة للمستلزمات</span>
                            <input type="hidden" name="requires_supplies" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-supplies" type="checkbox" role="switch" name="requires_supplies" value="1" {{ $requiresSuppliesChecked ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لرعاية رسمية</span>
                            <input type="hidden" name="has_sponsor" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-has-sponsor" type="checkbox" role="switch" name="has_sponsor" value="1" {{ $hasSponsorChecked ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لشركاء خارجيين</span>
                            <input type="hidden" name="has_partners" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-has-partners" type="checkbox" role="switch" name="has_partners" value="1" {{ $hasPartnersChecked ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لوجود أجندة حفل</span>
                            <input type="hidden" name="needs_ceremony_agenda" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-ceremony-agenda" type="checkbox" role="switch" name="needs_ceremony_agenda" value="1" {{ old('needs_ceremony_agenda', '0') === '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لتأمين مواصلات</span>
                            <input type="hidden" name="needs_transport" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-transport" type="checkbox" role="switch" name="needs_transport" value="1" {{ old('needs_transport', '0') === '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لعمال صيانة بالموقع</span>
                            <input type="hidden" name="needs_maintenance_workers" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-maintenance" type="checkbox" role="switch" name="needs_maintenance_workers" value="1" {{ old('needs_maintenance_workers', '0') === '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لهدايا ودروع</span>
                            <input type="hidden" name="needs_gifts" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-gifts" type="checkbox" role="switch" name="needs_gifts" value="1" {{ old('needs_gifts', '0') === '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لمشاركة البرامج</span>
                            <input type="hidden" name="needs_programs_participation" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-programs-participation" type="checkbox" role="switch" name="needs_programs_participation" value="1" {{ old('needs_programs_participation', '0') === '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة لشهادات وكتب شكر</span>
                            <input type="hidden" name="needs_certificates_and_thanks" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-certificates" type="checkbox" role="switch" name="needs_certificates_and_thanks" value="1" {{ old('needs_certificates_and_thanks', '0') === '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                        <label class="monthly-activation-option">
                            <span>الحاجة إلى بطاقات دعوة</span>
                            <input type="hidden" name="needs_invitations" value="0">
                            <div class="form-check form-switch m-0">
                                <input class="form-check-input js-needs-invitations" type="checkbox" role="switch" name="needs_invitations" value="1" {{ old('needs_invitations', '0') === '1' ? 'checked' : '' }}>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="col-12 js-volunteers-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--volunteers">
                        <h3 class="h6 mb-3">احتياج المتطوعين</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">عدد المتطوعين</label>
                                <input class="form-control js-required-volunteers @error('required_volunteers') is-invalid @enderror" type="number" min="1" name="required_volunteers" value="{{ old('required_volunteers', $existingMonthlyActivity?->required_volunteers) }}">
                                @error('required_volunteers')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">الفترة العمرية</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input
                                            class="form-control @error('volunteer_age_from') is-invalid @enderror"
                                            type="number"
                                            min="10"
                                            max="80"
                                            name="volunteer_age_from"
                                            placeholder="من"
                                            value="{{ old('volunteer_age_from') }}"
                                        >
                                        @error('volunteer_age_from')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-6">
                                        <input
                                            class="form-control @error('volunteer_age_to') is-invalid @enderror"
                                            type="number"
                                            min="10"
                                            max="80"
                                            name="volunteer_age_to"
                                            placeholder="إلى"
                                            value="{{ old('volunteer_age_to') }}"
                                        >
                                        @error('volunteer_age_to')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                @error('volunteer_age_range')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">الجنس</label>
                                <input class="form-control @error('volunteer_gender') is-invalid @enderror" name="volunteer_gender" value="{{ old('volunteer_gender', $existingMonthlyActivity?->volunteer_gender) }}">
                                @error('volunteer_gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">الاحتياج المختصر</label>
                                <input class="form-control @error('volunteer_need') is-invalid @enderror" name="volunteer_need" value="{{ old('volunteer_need', $existingMonthlyActivity?->volunteer_need) }}">
                                @error('volunteer_need')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">وصف مختصر عن طبيعة المهام</label>
                                <textarea class="form-control @error('volunteer_tasks_summary') is-invalid @enderror" name="volunteer_tasks_summary" rows="2">{{ old('volunteer_tasks_summary', $existingMonthlyActivity?->volunteer_tasks_summary) }}</textarea>
                                @error('volunteer_tasks_summary')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-correspondence-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--correspondence">
                        <h3 class="h6 mb-3">المخاطبة الرسمية</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">سبب المخاطبة</label>
                                <input class="form-control @error('official_correspondence_reason') is-invalid @enderror" name="official_correspondence_reason" value="{{ old('official_correspondence_reason', $existingMonthlyActivity?->official_correspondence_reason) }}">
                                @error('official_correspondence_reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">الجهة المطلوب مخاطبتها</label>
                                <input class="form-control @error('official_correspondence_target') is-invalid @enderror" name="official_correspondence_target" value="{{ old('official_correspondence_target', $existingMonthlyActivity?->official_correspondence_target) }}">
                                @error('official_correspondence_target')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">بريف مختصر عن المخاطبة</label>
                                <textarea class="form-control @error('official_correspondence_brief') is-invalid @enderror" name="official_correspondence_brief" rows="3">{{ old('official_correspondence_brief', $existingMonthlyActivity?->official_correspondence_brief) }}</textarea>
                                @error('official_correspondence_brief')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-media-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--media">
                        <h3 class="h6 mb-3">التغطية الإعلامية</h3>
                        <label class="form-label">ملاحظات التغطية الإعلامية</label>
                        <textarea class="form-control @error('media_coverage_notes') is-invalid @enderror" name="media_coverage_notes" rows="2">{{ old('media_coverage_notes', $existingMonthlyActivity?->media_coverage_notes) }}</textarea>
                        @error('media_coverage_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-12 js-ceremony-agenda-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--ceremony">
                        <h3 class="h6 mb-3">أجندة الحفل</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">عدد الفقرات</label>
                                <input class="form-control js-ceremony-items-count" type="number" min="1" max="20" name="ceremony_items_count" value="{{ old('ceremony_items_count', count($oldCeremonyItems)) }}">
                            </div>
                            <div class="col-12"><div class="row g-3 js-ceremony-items-container"></div></div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-transport-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--transport">
                        <h3 class="h6 mb-3">تأمين المواصلات</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">عدد المركبات</label>
                                <input class="form-control" type="number" min="1" name="transport_vehicles_count" value="{{ old('transport_vehicles_count') }}">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">نوع المركبة</label>
                                <select class="form-select" name="transport_vehicle_type">
                                    <option value="">اختر</option>
                                    <option value="bus" {{ old('transport_vehicle_type') === 'bus' ? 'selected' : '' }}>باص</option>
                                    <option value="car" {{ old('transport_vehicle_type') === 'car' ? 'selected' : '' }}>سيارة</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label">عدد الركاب</label>
                                <input class="form-control" type="number" min="1" name="transport_passengers_count" value="{{ old('transport_passengers_count') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-maintenance-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--maintenance">
                        <h3 class="h6 mb-3">الصيانة بالموقع</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">عدد العمال</label>
                                <input class="form-control" type="number" min="1" name="maintenance_workers_count" value="{{ old('maintenance_workers_count') }}">
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label">نوع الصيانة</label>
                                <input class="form-control" name="maintenance_type" value="{{ old('maintenance_type') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-gifts-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--gifts">
                        <h3 class="h6 mb-3">الهدايا والدروع</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-2">
                                <label class="form-label">عدد الهدايا</label>
                                <input class="form-control" type="number" min="1" name="gifts_count" value="{{ old('gifts_count') }}">
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label">وصف الهدايا</label>
                                <input class="form-control" name="gifts_description" value="{{ old('gifts_description') }}">
                            </div>
                            <div class="col-12 col-md-5">
                                <label class="form-label">جهة تسليم الهدايا</label>
                                <input class="form-control" name="gifts_delivery_entity" value="{{ old('gifts_delivery_entity') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-sponsor-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--sponsor">
                        <h3 class="h6 mb-3">بيانات الراعي</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">اسم الراعي</label>
                                <input class="form-control" name="sponsors[0][name]" value="{{ old('sponsors.0.name', $oldSponsors[0]['name'] ?? null) }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">صفة الراعي</label>
                                <input class="form-control" name="sponsors[0][title]" value="{{ old('sponsors.0.title', $oldSponsors[0]['title'] ?? null) }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-partners-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--partners">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                            <h3 class="h6 mb-0">الشركاء</h3>
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0">عدد الشركاء</label>
                                <input class="form-control form-control-sm js-partners-count" type="number" min="1" max="10" value="{{ old('partners_count', $partnersCount) }}" style="width: 90px;">
                            </div>
                        </div>
                        <div class="row g-3 js-partners-container"></div>
                    </div>
                </div>

                <div class="col-12 js-programs-participation-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--programs">
                        <h3 class="h6 mb-3">مشاركة البرامج</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">بحاجة محاضر/مدرب؟</label>
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input js-programs-need-trainer-toggle" type="checkbox" role="switch" name="programs_need_trainer" value="1" {{ $programsNeedTrainerChecked ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 js-programs-trainer-fields">
                                <label class="form-label">وصف المحاضر/المدرب</label>
                                <input class="form-control" name="programs_trainer_description" value="{{ old('programs_trainer_description') }}">
                            </div>
                            <div class="col-12 col-md-2 js-programs-trainer-fields">
                                <label class="form-label">العدد</label>
                                <input class="form-control" type="number" min="1" name="programs_trainer_count" value="{{ old('programs_trainer_count') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label d-block">خدمات زها تايم؟</label>
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input js-programs-zaha-toggle" type="checkbox" role="switch" name="programs_needs_zaha_time" value="1" {{ $programsNeedsZahaTimeChecked ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="col-12 col-md-6 js-programs-zaha-fields">
                                <label class="form-label">خدمات زها تايم (اختيار متعدد)</label>
                                <div class="monthly-chip-options">
                                    @foreach (($zahaTimeOptions ?? collect()) as $zahaOption)
                                        <label class="monthly-chip-option">
                                            <input class="form-check-input m-0" type="checkbox" name="programs_zaha_time_options[]" value="{{ $zahaOption->code }}" {{ in_array($zahaOption->code, $selectedZahaTimeOptions, true) ? 'checked' : '' }}>
                                            <span>{{ $zahaOption->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <small class="text-muted">الخيارات تُدار من شاشة القوائم المرجعية للأدمن.</small>
                            </div>
                            <div class="col-12 col-md-6 js-programs-zaha-fields">
                                <label class="form-label">تفاصيل إضافية لزها تايم</label>
                                <input class="form-control" name="programs_zaha_time_other" value="{{ old('programs_zaha_time_other') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label d-block">العرض الفني؟</label>
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input js-programs-show-toggle" type="checkbox" role="switch" name="programs_needs_show" value="1" {{ $programsNeedsShowChecked ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 js-programs-show-fields">
                                <label class="form-label">اسم العرض الفني</label>
                                <input class="form-control" name="programs_show_name" value="{{ old('programs_show_name') }}">
                            </div>
                            <div class="col-12 col-md-4 js-programs-show-fields">
                                <label class="form-label">وصف العرض الفني</label>
                                <input class="form-control" name="programs_show_description" value="{{ old('programs_show_description') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label d-block">بحاجة فان؟</label>
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input js-programs-fun-toggle" type="checkbox" role="switch" name="programs_needs_fun" value="1" {{ $programsNeedsFunChecked ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 js-programs-fun-fields">
                                <label class="form-label">تفاصيل الفان</label>
                                <input class="form-control" name="programs_fun_note" value="{{ old('programs_fun_note') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-certificates-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--certificates">
                        <h3 class="h6 mb-3">الشهادات وكتب الشكر</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label d-block">إصدار شهادات؟</label>
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input js-certificates-detail-toggle" type="checkbox" role="switch" name="needs_certificates_details" value="1" {{ $certificatesDetailsChecked ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="col-12 col-md-3 js-certificates-detail-fields">
                                <label class="form-label">عددها</label>
                                <input class="form-control" type="number" min="1" name="certificates_count" value="{{ old('certificates_count') }}">
                            </div>
                            <div class="col-12 col-md-4 js-certificates-detail-fields">
                                <label class="form-label">صيغة مقترحة</label>
                                <input class="form-control" name="certificates_template" value="{{ old('certificates_template') }}">
                            </div>
                            <div class="col-12 col-md-5 js-certificates-detail-fields">
                                <label class="form-label">لمن</label>
                                <input class="form-control" name="certificates_for" value="{{ old('certificates_for') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label d-block">إصدار كتب شكر؟</label>
                                <div class="form-check form-switch pt-2">
                                    <input class="form-check-input js-thanks-letters-detail-toggle" type="checkbox" role="switch" name="needs_thanks_letters_details" value="1" {{ $thanksLettersDetailsChecked ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="col-12 col-md-3 js-thanks-letters-detail-fields">
                                <label class="form-label">عددها</label>
                                <input class="form-control" type="number" min="1" name="thanks_letters_count" value="{{ old('thanks_letters_count') }}">
                            </div>
                            <div class="col-12 col-md-4 js-thanks-letters-detail-fields">
                                <label class="form-label">صيغة مقترحة</label>
                                <input class="form-control" name="thanks_letters_template" value="{{ old('thanks_letters_template') }}">
                            </div>
                            <div class="col-12 col-md-5 js-thanks-letters-detail-fields">
                                <label class="form-label">لمن</label>
                                <input class="form-control" name="thanks_letters_for" value="{{ old('thanks_letters_for') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-invitations-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--invitations">
                        <h3 class="h6 mb-3">بطاقات الدعوة</h3>
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <label class="form-label">نوع الدعوة</label>
                                <select class="form-select js-invitation-type" name="invitation_type">
                                    <option value="">اختر</option>
                                    <option value="paper" {{ old('invitation_type') === 'paper' ? 'selected' : '' }}>ورقية</option>
                                    <option value="electronic" {{ old('invitation_type') === 'electronic' ? 'selected' : '' }}>إلكترونية</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4 js-invitation-paper-fields">
                                <label class="form-label">الصيغة المقترحة (ورقية)</label>
                                <input class="form-control" name="invitation_paper_template" value="{{ old('invitation_paper_template') }}">
                            </div>
                            <div class="col-12 col-md-2 js-invitation-paper-fields">
                                <label class="form-label">عدد النسخ</label>
                                <input class="form-control" type="number" min="1" name="invitation_paper_copies" value="{{ old('invitation_paper_copies') }}">
                            </div>
                            <div class="col-12 col-md-4 js-invitation-electronic-fields">
                                <label class="form-label">الصيغة المقترحة (إلكترونية)</label>
                                <input class="form-control" name="invitation_electronic_template" value="{{ old('invitation_electronic_template') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 js-supplies-fields">
                    <div class="monthly-subsection-card monthly-subsection-card--supplies">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                            <h3 class="h6 mb-0">المستلزمات</h3>
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0">عدد البنود</label>
                                <input class="form-control form-control-sm js-supplies-count" type="number" min="1" max="20" value="{{ old('supplies_count', $suppliesCount) }}" style="width: 90px;">
                            </div>
                        </div>
                        <div class="row g-3 js-supplies-container"></div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="monthly-subsection-card monthly-subsection-card--team">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                            <h3 class="h6 mb-0">فريق العمل</h3>
                            <div class="d-flex align-items-center gap-2">
                                <label class="form-label mb-0">عدد الفرق</label>
                                <input class="form-control form-control-sm js-team-groups-count" type="number" min="1" max="10" value="{{ old('team_groups_count', $teamGroupsCount) }}" style="width: 90px;">
                            </div>
                        </div>
                        <div class="js-team-groups-container"></div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="monthly-form-actions">
                        <button class="btn btn-outline-secondary" type="submit" name="submit_action" value="draft">مسودة</button>
                        <button class="btn btn-primary" type="submit" name="submit_action" value="submit">إرسال للاعتماد</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/event-ui-shared.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/monthly-activity-form.css') }}">
@endpush

@push('scripts')
    <script type="application/json" id="monthly-form-old-ceremony-items-json">@json($oldCeremonyItems)</script>
    <script type="application/json" id="monthly-form-old-partners-json">@json($oldPartners)</script>
    <script type="application/json" id="monthly-form-old-supplies-json">@json($oldSupplies)</script>
    <script type="application/json" id="monthly-form-old-team-groups-json">@json($oldTeamGroups)</script>
    <script src="{{ asset('assets/js/monthly-activity-form.js') }}"></script>
@endpush
