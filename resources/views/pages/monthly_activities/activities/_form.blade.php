@php
    $existingMonthlyActivity = $monthlyActivity ?? null;
    $formUser = auth()->user();
    $isBranchScopedUser = $formUser
        && method_exists($formUser, 'hasBranchScopedMonthlyVisibility')
        && $formUser->hasBranchScopedMonthlyVisibility()
        && ! empty($formUser->branch_id);
    $selectedBranch = $branches->firstWhere('id', old('branch_id', $existingMonthlyActivity?->branch_id ?? $formUser?->branch_id));
    $departmentsFromForm = $departments ?? \App\Models\Department::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    $selectedPartnerDepartmentIds = collect(old('partner_department_ids', []))->map(fn ($id) => (string) $id)->all();
    $linkedAgendaEventId = old('agenda_event_id', $existingMonthlyActivity?->agenda_event_id);
    $needsVolunteersChecked = (bool) old('needs_volunteers', $existingMonthlyActivity?->needs_volunteers ?? false);
    $needsOfficialCorrespondenceChecked = (bool) old('needs_official_correspondence', $existingMonthlyActivity?->needs_official_correspondence ?? false);
    $needsOfficialLettersChecked = (bool) old('needs_official_letters', $existingMonthlyActivity?->needs_official_letters ?? false);
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
            'provider_type' => $supply->provider_type,
            'provider_name' => $supply->provider_name,
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
                    <div class="monthly-form-section-head">
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
                    <label class="form-label d-block mb-2">الجهات الشركاء</label>
                    <div class="partner-departments-box mb-3">
                        @foreach($departmentsFromForm as $department)
                            <label class="partner-department-item">
                                <input class="form-check-input m-0" type="checkbox" name="partner_department_ids[]" value="{{ $department->id }}" {{ in_array((string) $department->id, $selectedPartnerDepartmentIds, true) ? 'checked' : '' }} {{ $isLockedField('partner_department_ids') ? 'disabled' : '' }}>
                                <span>{{ $department->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <div class="monthly-form-section-head">
                        <h2 class="h6 mb-1">المكان والوقت</h2>
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
                    <label class="form-label">رقم الجهة الشريكة</label>
                    <input class="form-control" name="outside_contact_number" value="{{ old('outside_contact_number', $existingMonthlyActivity?->outside_contact_number) }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">اسم ضابط الارتباط</label>
                    <input class="form-control" name="external_liaison_name" value="{{ old('external_liaison_name', $existingMonthlyActivity?->external_liaison_name) }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رقم ضابط الارتباط</label>
                    <input class="form-control" name="external_liaison_phone" value="{{ old('external_liaison_phone', $existingMonthlyActivity?->external_liaison_phone) }}">
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

                <div class="col-12 col-md-6">
                    <label class="form-label">وصف مختصر</label>
                    <textarea class="form-control" name="short_description" rows="2">{{ old('short_description', $existingMonthlyActivity?->short_description) }}</textarea>
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">الوصف التفصيلي</label>
                    <textarea class="form-control" name="description" rows="2">{{ old('description', $existingMonthlyActivity?->description) }}</textarea>
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
                    <div class="monthly-form-section-head">
                        <h2 class="h6 mb-1">خيارات التفعيل</h2>
                    </div>
                </div>

                <div class="col-12">
                    <div class="monthly-activation-grid">
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0 js-needs-volunteers" type="checkbox" name="needs_volunteers" value="1" {{ $needsVolunteersChecked ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>الحاجة للمتطوعين</span>
                        </label>
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0 js-needs-letters" type="checkbox" name="needs_official_correspondence" value="1" {{ $needsOfficialCorrespondenceChecked ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>الحاجة للمخاطبة الرسمية</span>
                        </label>
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0 js-needs-official-letters" type="checkbox" name="needs_official_letters" value="1" {{ $needsOfficialLettersChecked ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>الحاجة للكتب الرسمية</span>
                        </label>
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0 js-needs-media" type="checkbox" name="needs_media_coverage" value="1" {{ $needsMediaCoverageChecked ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>الحاجة لتغطية إعلامية</span>
                        </label>
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0 js-needs-supplies" type="checkbox" name="requires_supplies" value="1" {{ $requiresSuppliesChecked ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>الحاجة للمستلزمات</span>
                        </label>
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0 js-has-sponsor" type="checkbox" name="has_sponsor" value="1" {{ $hasSponsorChecked ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>يوجد راعٍ</span>
                        </label>
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0 js-has-partners" type="checkbox" name="has_partners" value="1" {{ $hasPartnersChecked ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>يوجد شركاء</span>
                        </label>
                    </div>
                </div>

                <div class="col-12 js-volunteers-fields">
                    <div class="monthly-subsection-card">
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
                    <div class="monthly-subsection-card">
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

                <div class="col-12 js-official-letters-fields">
                    <div class="monthly-subsection-card">
                        <h3 class="h6 mb-3">الكتب الرسمية</h3>
                        <label class="form-label">سبب الكتب الرسمية</label>
                        <input class="form-control @error('letter_purpose') is-invalid @enderror" name="letter_purpose" value="{{ old('letter_purpose', $existingMonthlyActivity?->letter_purpose) }}">
                        @error('letter_purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-12 js-media-fields">
                    <div class="monthly-subsection-card">
                        <h3 class="h6 mb-3">التغطية الإعلامية</h3>
                        <label class="form-label">ملاحظات التغطية الإعلامية</label>
                        <textarea class="form-control @error('media_coverage_notes') is-invalid @enderror" name="media_coverage_notes" rows="2">{{ old('media_coverage_notes', $existingMonthlyActivity?->media_coverage_notes) }}</textarea>
                        @error('media_coverage_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-12 js-sponsor-fields">
                    <div class="monthly-subsection-card">
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
                    <div class="monthly-subsection-card">
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

                <div class="col-12 js-supplies-fields">
                    <div class="monthly-subsection-card">
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
                    <div class="monthly-subsection-card">
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
    <script type="application/json" id="monthly-form-old-partners-json">@json($oldPartners)</script>
    <script type="application/json" id="monthly-form-old-supplies-json">@json($oldSupplies)</script>
    <script type="application/json" id="monthly-form-old-team-groups-json">@json($oldTeamGroups)</script>
    <script src="{{ asset('assets/js/monthly-activity-form.js') }}"></script>
@endpush
