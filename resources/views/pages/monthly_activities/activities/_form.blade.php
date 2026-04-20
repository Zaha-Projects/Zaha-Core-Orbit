@php
    $existingMonthlyActivity = $monthlyActivity ?? null;
    $formUser = auth()->user();
    $isBranchScopedUser = $formUser
        && method_exists($formUser, 'hasBranchScopedMonthlyVisibility')
        && $formUser->hasBranchScopedMonthlyVisibility()
        && ! empty($formUser->branch_id);
    $selectedBranch = $branches->firstWhere('id', old('branch_id', $existingMonthlyActivity?->branch_id ?? $formUser?->branch_id));
    $selectedResponsibleEntities = collect(old('responsible_entities', array_values(array_filter([
        ($existingMonthlyActivity?->requires_communications ?? false) ? 'relations' : null,
        ($existingMonthlyActivity?->requires_programs ?? false) ? 'programs' : null,
    ]))));
    $departmentsFromForm = $departments ?? \App\Models\Department::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    $selectedOwnerDepartmentId = (string) old('owner_department_id', '');
    $selectedPartnerDepartmentIds = collect(old('partner_department_ids', []))->map(fn ($id) => (string) $id)->all();
    $isInAgendaChecked = (bool) old('is_in_agenda', $existingMonthlyActivity?->is_in_agenda ?? false);
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

                <div class="col-12">
                    <div class="monthly-form-section-head">
                        <h2 class="h6 mb-1">بيانات التخطيط الأساسية</h2>
                        <p class="text-muted small mb-0">هذه الشاشة مخصصة للتخطيط قبل التنفيذ، بينما تحديث حالة التنفيذ يتم في وضع إكمال التعبئة بعد التنفيذ.</p>
                    </div>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">الجهة المالكة</label>
                    <select class="form-select" name="owner_department_id">
                        <option value="">اختر الجهة المالكة</option>
                        @foreach($departmentsFromForm as $department)
                            <option value="{{ $department->id }}" {{ $selectedOwnerDepartmentId === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-lg-6">
                    <label class="form-label">عنوان النشاط</label>
                    <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $existingMonthlyActivity?->title) }}">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">تاريخ النشاط المخطط</label>
                    <input class="form-control js-proposed-date @error('proposed_date') is-invalid @enderror" type="date" name="proposed_date" value="{{ old('proposed_date', optional($existingMonthlyActivity?->proposed_date)->format('Y-m-d')) }}">
                    @error('proposed_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الفرع</label>
                    @if ($isBranchScopedUser && $selectedBranch)
                        <input type="hidden" name="branch_id" value="{{ $selectedBranch->id }}">
                        <input class="form-control" value="{{ $selectedBranch->name }}" readonly>
                    @else
                        <select class="form-select @error('branch_id') is-invalid @enderror" name="branch_id">
                            <option value="">اختر الفرع</option>
                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" {{ (string) old('branch_id', $existingMonthlyActivity?->branch_id) === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @endif
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">فعالية الأجندة السنوية المرتبطة</label>
                    <select class="form-select" name="agenda_event_id">
                        <option value="">اختياري</option>
                        @foreach ($agendaEvents as $event)
                            <option value="{{ $event->id }}" {{ (string) old('agenda_event_id', $existingMonthlyActivity?->agenda_event_id) === (string) $event->id ? 'selected' : '' }}>{{ $event->event_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">أجندة الحفل (إن وجدت)</label>
                    <input class="form-control @error('planning_attachment') is-invalid @enderror @error('branch_plan_file') is-invalid @enderror" type="file" name="planning_attachment" accept=".pdf,.doc,.docx,.xls,.xlsx">
                    @if (old('planning_attachment', $existingMonthlyActivity?->planning_attachment))
                        <a class="small d-inline-block mt-1" href="{{ asset('storage/' . old('planning_attachment', $existingMonthlyActivity?->planning_attachment)) }}" target="_blank">عرض المرفق الحالي</a>
                    @endif
                    @error('planning_attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('branch_plan_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label d-block mb-2">الجهات الشركاء</label>
                    <div class="partner-departments-box mb-3">
                        @foreach($departmentsFromForm as $department)
                            <label class="partner-department-item">
                                <input class="form-check-input m-0" type="checkbox" name="partner_department_ids[]" value="{{ $department->id }}" {{ in_array((string) $department->id, $selectedPartnerDepartmentIds, true) ? 'checked' : '' }}>
                                <span>{{ $department->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <label class="form-label d-block mb-2">خيارات إضافية</label>
                    <div class="monthly-activation-grid">
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0" type="checkbox" name="responsible_entities[]" value="relations" {{ $selectedResponsibleEntities->contains('relations') ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>العلاقات</span>
                        </label>
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0" type="checkbox" name="responsible_entities[]" value="programs" {{ $selectedResponsibleEntities->contains('programs') ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>البرامج</span>
                        </label>
                        <label class="monthly-activation-option">
                            <input class="form-check-input m-0" type="checkbox" name="is_in_agenda" value="1" {{ $isInAgendaChecked ? 'checked' : '' }}>
                            <span class="monthly-activation-icon">✓</span>
                            <span>يظهر ضمن الأجندة السنوية</span>
                        </label>
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
                                <input class="form-check-input m-0 js-target-group-checkbox" type="checkbox" name="target_group_ids[]" value="{{ $group->id }}" data-is-other="{{ $group->is_other ? 1 : 0 }}" {{ in_array((int) $group->id, $selectedTargetGroupIds, true) ? 'checked' : '' }}>
                                <span>{{ $group->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="col-12 col-md-6 js-target-group-other">
                    <label class="form-label">أخرى (توضيح)</label>
                    <input class="form-control" name="target_group_other" value="{{ old('target_group_other', $existingMonthlyActivity?->target_group_other) }}">
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
                                <input class="form-control @error('volunteer_age_range') is-invalid @enderror" name="volunteer_age_range" value="{{ old('volunteer_age_range', $existingMonthlyActivity?->volunteer_age_range) }}">
                                @error('volunteer_age_range')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
    <style>
        .monthly-plan-form-page .monthly-form-section-head {
            padding-bottom: .5rem;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: .5rem;
        }
        .monthly-plan-form-page .monthly-activation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: .75rem;
        }
        .monthly-plan-form-page .monthly-activation-option {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            padding: .85rem 1rem;
            border: 1px solid #d8e1ea;
            border-radius: 14px;
            background: #fbfdff;
            cursor: pointer;
            transition: border-color .2s ease, background-color .2s ease, box-shadow .2s ease;
        }
        .monthly-plan-form-page .monthly-activation-option:hover {
            border-color: #9fb8d4;
            background: #f5f9ff;
        }
        .monthly-plan-form-page .monthly-activation-option:has(input:checked) {
            border-color: #3c78b5;
            background: #eef6ff;
            box-shadow: 0 0 0 2px rgba(60, 120, 181, .08);
        }
        .monthly-plan-form-page .monthly-activation-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border-radius: 999px;
            background: #d9e9f8;
            color: #22598d;
            font-size: .82rem;
            font-weight: 700;
            flex-shrink: 0;
        }
        .monthly-plan-form-page .monthly-subsection-card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            background: #fcfcfd;
            padding: 1rem;
        }
        .monthly-plan-form-page .partner-departments-box {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: .65rem;
        }
        .monthly-plan-form-page .partner-department-item {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .75rem .85rem;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            cursor: pointer;
        }
        .monthly-plan-form-page .monthly-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: .75rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.monthly-plan-form');
            if (!form) return;

            const activityDateInput = form.querySelector('.js-activity-date');
            const proposedDateInput = form.querySelector('.js-proposed-date');
            const locationType = form.querySelector('.js-location-type');
            const insideLocationFields = form.querySelectorAll('.js-inside-location');
            const outsideLocationFields = form.querySelectorAll('.js-outside-location');
            const targetGroupCheckboxes = form.querySelectorAll('.js-target-group-checkbox');
            const targetGroupOtherFields = form.querySelectorAll('.js-target-group-other');
            const needsVolunteers = form.querySelector('.js-needs-volunteers');
            const volunteersFields = form.querySelectorAll('.js-volunteers-fields');
            const needsCorrespondence = form.querySelector('.js-needs-letters');
            const correspondenceFields = form.querySelectorAll('.js-correspondence-fields');
            const needsSupplies = form.querySelector('.js-needs-supplies');
            const suppliesFields = form.querySelectorAll('.js-supplies-fields');
            const hasSponsor = form.querySelector('.js-has-sponsor');
            const sponsorFields = form.querySelectorAll('.js-sponsor-fields');
            const hasPartners = form.querySelector('.js-has-partners');
            const partnersFields = form.querySelectorAll('.js-partners-fields');
            const partnersCount = form.querySelector('.js-partners-count');
            const partnersContainer = form.querySelector('.js-partners-container');
            const needsOfficialLetters = form.querySelector('.js-needs-official-letters');
            const officialLettersFields = form.querySelectorAll('.js-official-letters-fields');
            const needsMedia = form.querySelector('.js-needs-media');
            const mediaFields = form.querySelectorAll('.js-media-fields');
            const suppliesCount = form.querySelector('.js-supplies-count');
            const suppliesContainer = form.querySelector('.js-supplies-container');
            const teamGroupsCount = form.querySelector('.js-team-groups-count');
            const teamGroupsContainer = form.querySelector('.js-team-groups-container');

            const oldPartners = @json($oldPartners);
            const oldSupplies = @json($oldSupplies);
            const oldTeamGroups = @json($oldTeamGroups);

            const esc = (value) => String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            function syncActivityDate() {
                if (activityDateInput && proposedDateInput) {
                    activityDateInput.value = proposedDateInput.value || '';
                }
            }

            function toggleElements(elements, isVisible) {
                elements.forEach((element) => {
                    element.style.display = isVisible ? '' : 'none';
                });
            }

            function setRequiredState(selectors, isRequired) {
                selectors.forEach((selector) => {
                    const input = form.querySelector(selector);
                    if (!input) return;
                    input.disabled = !isRequired;
                    input.required = isRequired;

                    if (!isRequired && !['checkbox', 'radio', 'file'].includes(input.type)) {
                        input.value = '';
                    }
                });
            }

            function toggleLocationFields() {
                const isOutside = locationType?.value === 'outside_center';
                toggleElements(insideLocationFields, !isOutside);
                toggleElements(outsideLocationFields, isOutside);
                setRequiredState([
                    '[name="outside_place_name"]',
                    '[name="outside_google_maps_url"]',
                    '[name="outside_contact_number"]',
                    '[name="external_liaison_name"]',
                    '[name="external_liaison_phone"]',
                    '[name="outside_address"]'
                ], isOutside);
            }

            function toggleTargetGroupOther() {
                const hasOther = Array.from(targetGroupCheckboxes).some((checkbox) => checkbox.checked && checkbox.dataset.isOther === '1');
                toggleElements(targetGroupOtherFields, hasOther);
                setRequiredState(['[name="target_group_other"]'], hasOther);
            }

            function toggleVolunteers() {
                const active = !!needsVolunteers?.checked;
                toggleElements(volunteersFields, active);
                setRequiredState([
                    '[name="required_volunteers"]',
                    '[name="volunteer_age_range"]',
                    '[name="volunteer_gender"]',
                    '[name="volunteer_tasks_summary"]'
                ], active);
            }

            function toggleCorrespondence() {
                const active = !!needsCorrespondence?.checked;
                toggleElements(correspondenceFields, active);
                setRequiredState([
                    '[name="official_correspondence_reason"]',
                    '[name="official_correspondence_target"]',
                    '[name="official_correspondence_brief"]'
                ], active);
            }

            function toggleOfficialLetters() {
                toggleElements(officialLettersFields, !!needsOfficialLetters?.checked);
            }

            function toggleMedia() {
                toggleElements(mediaFields, !!needsMedia?.checked);
            }

            function toggleSupplies() {
                const active = !!needsSupplies?.checked;
                toggleElements(suppliesFields, active);
                if (suppliesCount) {
                    suppliesCount.disabled = !active;
                }
            }

            function toggleSponsor() {
                toggleElements(sponsorFields, !!hasSponsor?.checked);
            }

            function togglePartners() {
                const active = !!hasPartners?.checked;
                toggleElements(partnersFields, active);
                if (partnersCount) {
                    partnersCount.disabled = !active;
                }
            }

            function renderPartners() {
                if (!partnersContainer) return;

                const count = Math.max(1, Math.min(10, parseInt(partnersCount?.value || '1', 10)));
                partnersContainer.innerHTML = '';

                for (let i = 0; i < count; i += 1) {
                    partnersContainer.insertAdjacentHTML('beforeend', `
                        <div class="col-12 col-md-4">
                            <label class="form-label">اسم الشريك ${i + 1}</label>
                            <input class="form-control" name="partners[${i}][name]" value="${esc(oldPartners?.[i]?.name)}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">دور الشريك ${i + 1}</label>
                            <input class="form-control" name="partners[${i}][role]" value="${esc(oldPartners?.[i]?.role)}">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">بيانات التواصل</label>
                            <input class="form-control" name="partners[${i}][contact_info]" value="${esc(oldPartners?.[i]?.contact_info)}">
                        </div>
                    `);
                }
            }

            function renderSupplies() {
                if (!suppliesContainer) return;

                const count = Math.max(1, Math.min(20, parseInt(suppliesCount?.value || '1', 10)));
                suppliesContainer.innerHTML = '';

                for (let i = 0; i < count; i += 1) {
                    const available = String(oldSupplies?.[i]?.available ?? '1') === '1';
                    suppliesContainer.insertAdjacentHTML('beforeend', `
                        <div class="col-12 col-md-4">
                            <label class="form-label">اسم المستلزم ${i + 1}</label>
                            <input class="form-control" name="supplies[${i}][item_name]" value="${esc(oldSupplies?.[i]?.item_name)}">
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label">التوفر</label>
                            <select class="form-select js-supply-available" data-index="${i}" name="supplies[${i}][available]">
                                <option value="1" ${available ? 'selected' : ''}>متوفر</option>
                                <option value="0" ${available ? '' : 'selected'}>غير متوفر</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 js-supply-provider" data-index="${i}" style="${available ? 'display:none' : ''}">
                            <label class="form-label">نوع المسؤول</label>
                            <select class="form-select" name="supplies[${i}][provider_type]">
                                <option value="">اختر</option>
                                <option value="volunteer" ${(oldSupplies?.[i]?.provider_type === 'volunteer') ? 'selected' : ''}>متطوع</option>
                                <option value="person" ${(oldSupplies?.[i]?.provider_type === 'person') ? 'selected' : ''}>شخص</option>
                                <option value="partner" ${(oldSupplies?.[i]?.provider_type === 'partner') ? 'selected' : ''}>شريك</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3 js-supply-provider" data-index="${i}" style="${available ? 'display:none' : ''}">
                            <label class="form-label">اسم المسؤول</label>
                            <input class="form-control" name="supplies[${i}][provider_name]" value="${esc(oldSupplies?.[i]?.provider_name)}">
                        </div>
                    `);
                }

                suppliesContainer.querySelectorAll('.js-supply-available').forEach((select) => {
                    select.addEventListener('change', function () {
                        const providers = suppliesContainer.querySelectorAll(`.js-supply-provider[data-index="${this.dataset.index}"]`);
                        providers.forEach((provider) => {
                            provider.style.display = this.value === '1' ? 'none' : '';
                        });
                    });
                });
            }

            function renderTeamGroups() {
                if (!teamGroupsContainer) return;

                const count = Math.max(1, Math.min(10, parseInt(teamGroupsCount?.value || '1', 10)));
                teamGroupsContainer.innerHTML = '';

                for (let g = 0; g < count; g += 1) {
                    teamGroupsContainer.insertAdjacentHTML('beforeend', `
                        <div class="border rounded-3 p-3 mb-3 js-team-group" data-group-index="${g}">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-md-4">
                                    <label class="form-label">اسم الفريق ${g + 1}</label>
                                    <input class="form-control" name="team_groups[${g}][team_name]" value="${esc(oldTeamGroups?.[g]?.team_name || ('فريق ' + (g + 1)))}">
                                </div>
                                <div class="col-12 col-md-3">
                                    <label class="form-label">عدد الأعضاء</label>
                                    <input class="form-control js-team-members-count" type="number" min="1" max="20" value="${Math.max(1, oldTeamGroups?.[g]?.members?.length || 1)}">
                                </div>
                            </div>
                            <div class="row g-3 mt-1 js-team-members-container"></div>
                        </div>
                    `);
                }

                teamGroupsContainer.querySelectorAll('.js-team-group').forEach((groupEl) => {
                    const groupIndex = parseInt(groupEl.dataset.groupIndex || '0', 10);
                    const countInput = groupEl.querySelector('.js-team-members-count');
                    const membersContainer = groupEl.querySelector('.js-team-members-container');

                    const renderMembers = () => {
                        const membersCount = Math.max(1, Math.min(20, parseInt(countInput?.value || '1', 10)));
                        membersContainer.innerHTML = '';

                        for (let m = 0; m < membersCount; m += 1) {
                            membersContainer.insertAdjacentHTML('beforeend', `
                                <div class="col-12 col-md-6">
                                    <label class="form-label">اسم العضو ${m + 1}</label>
                                    <input class="form-control" name="team_groups[${groupIndex}][members][${m}][member_name]" value="${esc(oldTeamGroups?.[groupIndex]?.members?.[m]?.member_name)}">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label">الدور / المهمة</label>
                                    <input class="form-control" name="team_groups[${groupIndex}][members][${m}][role_desc]" value="${esc(oldTeamGroups?.[groupIndex]?.members?.[m]?.role_desc)}">
                                </div>
                            `);
                        }
                    };

                    countInput?.addEventListener('input', renderMembers);
                    renderMembers();
                });
            }

            proposedDateInput?.addEventListener('change', syncActivityDate);
            locationType?.addEventListener('change', toggleLocationFields);
            targetGroupCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', toggleTargetGroupOther));
            needsVolunteers?.addEventListener('change', toggleVolunteers);
            needsCorrespondence?.addEventListener('change', toggleCorrespondence);
            needsOfficialLetters?.addEventListener('change', toggleOfficialLetters);
            needsMedia?.addEventListener('change', toggleMedia);
            needsSupplies?.addEventListener('change', toggleSupplies);
            hasSponsor?.addEventListener('change', toggleSponsor);
            hasPartners?.addEventListener('change', togglePartners);
            partnersCount?.addEventListener('input', renderPartners);
            suppliesCount?.addEventListener('input', renderSupplies);
            teamGroupsCount?.addEventListener('input', renderTeamGroups);

            syncActivityDate();
            renderPartners();
            renderSupplies();
            renderTeamGroups();
            toggleLocationFields();
            toggleTargetGroupOther();
            toggleVolunteers();
            toggleCorrespondence();
            toggleOfficialLetters();
            toggleMedia();
            toggleSupplies();
            toggleSponsor();
            togglePartners();
        });
    </script>
@endpush
