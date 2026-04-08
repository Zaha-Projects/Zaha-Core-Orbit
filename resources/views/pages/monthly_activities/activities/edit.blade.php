@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.edit_title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
    $isPostMode = request('mode') === 'post';
    $canManageEvaluation = auth()->user()?->hasAnyRole(['followup_officer', 'super_admin', 'relations_manager', 'executive_manager']);
    $evaluationEnabled = $isPostMode && $canManageEvaluation && (
        in_array($monthlyActivity->status, ['executed', 'completed', 'closed'], true)
        || ! empty($monthlyActivity->actual_date)
        || in_array((string) $monthlyActivity->lifecycle_status, ['Executed', 'Evaluated', 'Closed'], true)
    );
    $evaluationByQuestion = $monthlyActivity->evaluationResponses->keyBy('evaluation_question_id');
@endphp


@section('content')
    <div class="event-module">
    <div class="card event-card mb-4">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-0">{{ $subtitle }}</p>

            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <div class="fw-semibold mb-2">يرجى تصحيح الأخطاء التالية:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (request('mode') === 'post')
        <div class="alert alert-info">أنت الآن في وضع <strong>إكمال التعبئة بعد التنفيذ</strong>. أكمل الحقول التنفيذية في أسفل الصفحة.</div>
    @endif

    @if (! $isPostMode)
    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.edit_details') }}</h2>
            <div class="alert alert-light border mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <span>هذا النموذج مخصص لتعديل بيانات التخطيط قبل التنفيذ.</span>
                    <a class="btn btn-sm btn-outline-success" href="{{ route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'mode' => 'post']) }}">الانتقال إلى إكمال بعد التنفيذ</a>
                </div>
            </div>
            <form method="POST" action="{{ route('role.relations.activities.update', $monthlyActivity) }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.title') }}</label>
                    <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title', $monthlyActivity->title) }}" >
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.activity_date') }}</label>
                    <input class="form-control" type="date" name="activity_date" value="{{ sprintf('%04d-%02d-%02d', now()->year, $monthlyActivity->month, $monthlyActivity->day) }}" >
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.proposed_date') }}</label>
                    <input class="form-control" type="date" name="proposed_date" value="{{ optional($monthlyActivity->proposed_date)->format('Y-m-d') }}" >
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" >
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($monthlyActivity->branch_id === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    
</div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.agenda_event') }}</label>
                    <select class="form-select" name="agenda_event_id">
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.agenda_event_placeholder') }}</option>
                        @foreach ($agendaEvents as $event)
                            <option value="{{ $event->id }}" @selected($monthlyActivity->agenda_event_id === $event->id)>
                                {{ $event->event_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label>
                    <select class="form-select" name="status" >
                        <option value="draft" @selected($monthlyActivity->status === 'draft')>{{ __('app.roles.programs.monthly_activities.statuses.draft') }}</option>
                        <option value="submitted" @selected($monthlyActivity->status === 'submitted')>{{ __('app.roles.programs.monthly_activities.statuses.submitted') }}</option>
                        <option value="changes_requested" @selected($monthlyActivity->status === 'changes_requested')>{{ __('app.roles.programs.monthly_activities.statuses.changes_requested') }}</option>
                        <option value="postponed" @selected($monthlyActivity->status === 'postponed')>{{ __('app.roles.programs.monthly_activities.statuses.postponed') }}</option>
                        <option value="cancelled" @selected($monthlyActivity->status === 'cancelled')>{{ __('app.roles.programs.monthly_activities.statuses.cancelled') }}</option>
                        <option value="closed" @selected($monthlyActivity->status === 'closed')>{{ __('app.roles.programs.monthly_activities.statuses.closed') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">نوع المكان</label>
                    <select class="form-select js-location-type @error('location_type') is-invalid @enderror" name="location_type" >
                        <option value="inside_center" @selected(old('location_type', $monthlyActivity->location_type) === 'inside_center')>داخل المركز</option>
                        <option value="outside_center" @selected(old('location_type', $monthlyActivity->location_type) === 'outside_center')>خارج المركز</option>
                    </select>
                    @error('location_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-inside-location">
                    <label class="form-label">أي قاعة</label>
                    <input class="form-control @error('internal_location') is-invalid @enderror" name="internal_location" value="{{ old('internal_location', $monthlyActivity->internal_location) }}">
                    @error('internal_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">اسم الموقع</label>
                    <input class="form-control @error('outside_place_name') is-invalid @enderror" name="outside_place_name" value="{{ old('outside_place_name', $monthlyActivity->outside_place_name) }}">
                    @error('outside_place_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رابط الموقع من Google Maps</label>
                    <input class="form-control @error('outside_google_maps_url') is-invalid @enderror" name="outside_google_maps_url" value="{{ old('outside_google_maps_url', $monthlyActivity->outside_google_maps_url) }}">
                    @error('outside_google_maps_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <hr class="my-2">
                <div class="col-12"><h2 class="h6 mb-1">بيانات التنفيذ الأساسية</h2></div>
                <div class="col-12 col-md-4">
                    <label class="form-label">الجهة المسؤولة</label>
                    <input class="form-control" name="responsible_party" value="{{ $monthlyActivity->responsible_party }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">وقت التنفيذ</label>
                    <input class="form-control" name="execution_time" value="{{ $monthlyActivity->execution_time }}">
                </div>
                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رقم تواصل المكان الخارجي</label>
                    <input class="form-control" name="outside_contact_number" value="{{ old('outside_contact_number', $monthlyActivity->outside_contact_number) }}">
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الفئة المستهدفة</label>
                    <select class="form-select js-target-group" name="target_group_id">
                        <option value="">-- اختر --</option>
                        @foreach($targetGroups as $group)
                            <option value="{{ $group->id }}" data-is-other="{{ $group->is_other ? 1 : 0 }}" @selected((string) old('target_group_id', $monthlyActivity->target_group_id) === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 js-target-group-other">
                    <label class="form-label">أخرى (توضيح)</label>
                    <input class="form-control" name="target_group_other" value="{{ old('target_group_other', $monthlyActivity->target_group_other ) }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">وصف مختصر</label>
                    <input class="form-control" name="short_description" value="{{ $monthlyActivity->short_description }}">
                </div>

                <hr class="my-2">
                <div class="col-12"><h2 class="h6 mb-1">الداعمين والشركاء</h2></div>
                <div class="col-12 col-md-6">
                    <label class="form-label">الاحتياج للمتطوعين</label>
                    <input class="form-control" name="volunteer_need" value="{{ $monthlyActivity->volunteer_need }}">
                </div>
                <div class="col-12">
                    <label class="form-label">الرعاة (عدد مفتوح)</label>
                    <div class="row g-2">
                        @for ($i = 0; $i < 5; $i++)
                            @php $sponsor = $monthlyActivity->sponsors[$i] ?? null; @endphp
                            <div class="col-12 col-md-5">
                                <input class="form-control" name="sponsors[{{ $i }}][name]" value="{{ old("sponsors.$i.name", $sponsor->name ?? null) }}" placeholder="اسم الراعي">
                            </div>
                            <div class="col-12 col-md-5">
                                <input class="form-control" name="sponsors[{{ $i }}][title]" value="{{ old("sponsors.$i.title", $sponsor->title ?? null) }}" placeholder="الصفة/المسمى">
                            </div>
                            <div class="col-12 col-md-2 d-flex align-items-center">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="sponsors[{{ $i }}][is_official]" value="1" id="sponsor-edit-official-{{ $i }}" @checked(old("sponsors.$i.is_official", $sponsor?->is_official ?? true))>
                                    <label class="form-check-label" for="sponsor-edit-official-{{ $i }}">راعي رسمي</label>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label">الشركاء (عدد مفتوح)</label>
                    <div class="row g-2">
                        @for ($i = 0; $i < 7; $i++)
                            @php $partner = $monthlyActivity->partners[$i] ?? null; @endphp
                            <div class="col-12 col-md-6">
                                <input class="form-control" name="partners[{{ $i }}][name]" value="{{ old("partners.$i.name", $partner->name ?? null) }}" placeholder="اسم الشريك">
                            </div>
                            <div class="col-12 col-md-6">
                                <input class="form-control" name="partners[{{ $i }}][role]" value="{{ old("partners.$i.role", $partner->role ?? null) }}" placeholder="الدور">
                            </div>
                        @endfor
                    </div>
                </div>

                <hr class="my-2">
                <div class="col-12"><h2 class="h6 mb-1">المراسلات والتواريخ</h2></div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="needs_official_letters" value="1" id="needs_letters_edit" @checked($monthlyActivity->needs_official_letters)>
                        <label class="form-check-label" for="needs_letters_edit">بحاجة إلى خطابات</label>
                    </div>
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label">سبب الخطابات</label>
                    <input class="form-control" name="letter_purpose" value="{{ $monthlyActivity->letter_purpose }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">التاريخ المقترح الجديد</label>
                    <input class="form-control" type="date" name="rescheduled_date" value="{{ optional($monthlyActivity->rescheduled_date)->format('Y-m-d') }}">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">سبب التعديل</label>
                    <input class="form-control" name="reschedule_reason" value="{{ $monthlyActivity->reschedule_reason }}">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="relations_approval_on_reschedule" value="1" id="relations_reschedule_approve_edit" @checked($monthlyActivity->relations_approval_on_reschedule)>
                        <label class="form-check-label" for="relations_reschedule_approve_edit">اعتماد العلاقات على التعديل</label>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">رضا الجمهور %</label>
                    <input class="form-control" type="number" min="0" max="100" step="0.01" name="audience_satisfaction_percent" value="{{ $monthlyActivity->audience_satisfaction_percent }}">
                </div>

                <hr class="my-2">
                <div class="col-12"><h2 class="h6 mb-1">الحضور والمتطوعون</h2></div>
                <div class="col-12 col-md-3"><label class="form-label">الحضور المتوقع</label><input class="form-control" type="number" min="0" name="expected_attendance" value="{{ old('expected_attendance', $monthlyActivity->expected_attendance ) }}"></div>
                <div class="col-12 col-md-3"><label class="form-label">الحضور الفعلي</label><input class="form-control" type="number" min="0" name="actual_attendance" value="{{ old('actual_attendance', $monthlyActivity->actual_attendance ) }}"></div>
                <div class="col-12 col-md-3 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_volunteers" value="1" id="needs_volunteers" @checked(old('needs_volunteers', $monthlyActivity->needs_volunteers))><label class="form-check-label" for="needs_volunteers">نحتاج متطوعين</label></div></div>
                <div class="col-12 col-md-3"><label class="form-label">عدد المتطوعين المطلوب</label><input class="form-control" type="number" min="0" name="required_volunteers" value="{{ old('required_volunteers', $monthlyActivity->required_volunteers ) }}"></div>
                <div class="col-12"><label class="form-label">ملاحظات الحضور</label><textarea class="form-control" name="attendance_notes" rows="2">{{ old('attendance_notes', $monthlyActivity->attendance_notes ) }}</textarea></div>

                <hr class="my-2">
                <div class="col-12"><h2 class="h6 mb-1">المخاطبات والتغطية الإعلامية</h2></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_official_correspondence" value="1" id="needs_official_correspondence" @checked(old('needs_official_correspondence', $monthlyActivity->needs_official_correspondence))><label class="form-check-label" for="needs_official_correspondence">بحاجة إلى مخاطبات رسمية</label></div></div>
                <div class="col-12 col-md-4"><label class="form-label">سبب المخاطبة</label><input class="form-control" name="official_correspondence_reason" value="{{ old('official_correspondence_reason', $monthlyActivity->official_correspondence_reason ) }}"></div>
                <div class="col-12 col-md-4"><label class="form-label">الجهة المطلوب مخاطبتها</label><input class="form-control" name="official_correspondence_target" value="{{ old('official_correspondence_target', $monthlyActivity->official_correspondence_target ) }}"></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_media_coverage" value="1" id="needs_media_coverage" @checked(old('needs_media_coverage', $monthlyActivity->needs_media_coverage))><label class="form-check-label" for="needs_media_coverage">بحاجة إلى تغطية إعلامية</label></div></div>
                <div class="col-12 col-md-8"><label class="form-label">ملاحظات التغطية الإعلامية</label><input class="form-control" name="media_coverage_notes" value="{{ old('media_coverage_notes', $monthlyActivity->media_coverage_notes ) }}"></div>

                <hr class="my-2">
                <div class="col-12"><h2 class="h6 mb-1">خطة الفرع ومسارات التحويل</h2></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="is_program_related" value="1" id="is_program_related" @checked(old('is_program_related', $monthlyActivity->is_program_related))><label class="form-check-label" for="is_program_related">نشاط مرتبط بالبرامج</label></div></div>
                <div class="col-12 col-md-4"><label class="form-label">حالة المشاركة</label><select class="form-select" name="participation_status"><option value="unspecified" @selected(old('participation_status',$monthlyActivity->participation_status ?? 'unspecified')==='unspecified')>غير محدد</option><option value="participant" @selected(old('participation_status',$monthlyActivity->participation_status)==='participant')>مشارك</option><option value="not_participant" @selected(old('participation_status',$monthlyActivity->participation_status)==='not_participant')>غير مشارك</option></select></div>
                <div class="col-12 col-md-4"><label class="form-label">مرفق خطة الفرع (اختياري)</label><input class="form-control" type="file" name="branch_plan_file" accept=".pdf,.doc,.docx,.xls,.xlsx">@if($monthlyActivity->branch_plan_file)<a class="small d-block mt-1" href="{{ asset('storage/'.$monthlyActivity->branch_plan_file) }}" target="_blank">عرض الملف الحالي</a>@endif</div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_programs" value="1" id="requires_programs" @checked(old('requires_programs', $monthlyActivity->requires_programs))><label class="form-check-label" for="requires_programs">تحويل للبرامج</label></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_workshops" value="1" id="requires_workshops" @checked(old('requires_workshops', $monthlyActivity->requires_workshops))><label class="form-check-label" for="requires_workshops">تحويل للمشاغل</label></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_communications" value="1" id="requires_communications" @checked(old('requires_communications', $monthlyActivity->requires_communications))><label class="form-check-label" for="requires_communications">تحويل للعلاقات</label></div></div>

                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ $monthlyActivity->description }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.programs.monthly_activities.actions.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @if (! $isPostMode)
    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.supplies.title') }}</h2>
            <form method="POST" action="{{ route('role.programs.supplies.store', $monthlyActivity) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.supplies.fields.item_name') }}</label>
                    <input class="form-control" name="item_name" >
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="available" value="1" id="supply-available">
                        <label class="form-check-label" for="supply-available">
                            {{ __('app.roles.programs.monthly_activities.supplies.fields.available') }}
                        </label>
                    </div>
                </div>
                <div class="col-12 col-md-3 d-flex justify-content-end align-items-center">
                    <button class="btn btn-outline-primary btn-sm mt-4" type="submit">
                        {{ __('app.roles.programs.monthly_activities.supplies.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.programs.monthly_activities.supplies.table.item_name') }}</th>
                            <th>{{ __('app.roles.programs.monthly_activities.supplies.table.available') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.supplies.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($monthlyActivity->supplies as $supply)
                            <tr>
                                <td>{{ $supply->item_name }}</td>
                                <td>{{ $supply->available ? __('app.roles.programs.monthly_activities.supplies.available_yes') : __('app.roles.programs.monthly_activities.supplies.available_no') }}</td>
                                <td class="text-end">
                                    <form class="d-inline" method="POST" action="{{ route('role.programs.supplies.destroy', $supply) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.programs.monthly_activities.supplies.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.programs.monthly_activities.supplies.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if (! $isPostMode)
    <div class="card event-card mb-4">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.team.title') }}</h2>
            <form method="POST" action="{{ route('role.programs.team.store', $monthlyActivity) }}" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-3">
                    <label class="form-label">اسم الفريق</label>
                    <input class="form-control" name="team_name">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.team.fields.member_name') }}</label>
                    <input class="form-control" name="member_name" >
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">البريد الإلكتروني</label>
                    <input class="form-control" type="email" name="member_email">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.team.fields.role_desc') }}</label>
                    <input class="form-control" name="role_desc">
                </div>
                <div class="col-12 col-md-2 d-flex justify-content-end align-items-center">
                    <button class="btn btn-outline-primary btn-sm mt-4" type="submit">
                        {{ __('app.roles.programs.monthly_activities.team.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>الفريق</th>
                            <th>{{ __('app.roles.programs.monthly_activities.team.table.member_name') }}</th>
                            <th>البريد</th>
                            <th>{{ __('app.roles.programs.monthly_activities.team.table.role_desc') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.team.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($monthlyActivity->team as $member)
                            <tr>
                                <td>{{ $member->team_name ?? '-' }}</td>
                                <td>{{ $member->member_name }}</td>
                                <td>{{ $member->member_email ?? '-' }}</td>
                                <td>{{ $member->role_desc ?? '-' }}</td>
                                <td class="text-end">
                                    <form class="d-inline" method="POST" action="{{ route('role.programs.team.destroy', $member) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.programs.monthly_activities.team.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.programs.monthly_activities.team.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @if ($isPostMode)
    @if ($canManageEvaluation)
    <div class="card event-card mb-4" id="post-execution-evaluation">
        <div class="card-body">
            <h2 class="h6 mb-3">المتابعة والتقييم</h2>
            @if (! $evaluationEnabled)
                <div class="alert alert-warning mb-0">يظهر نموذج التقييم بعد تنفيذ الفعالية وإدخال تاريخ التنفيذ أو تحويل الحالة إلى (مكتملة/مغلقة).</div>
            @else
                <form method="POST" action="{{ route('role.relations.activities.update', $monthlyActivity) }}" class="row g-3 mb-0">
                    @csrf
                    @method('PUT')
                    @forelse($evaluationQuestions as $question)
                        @php $response = $evaluationByQuestion->get($question->id); @endphp
                        <div class="col-12 border rounded-3 p-3">
                            <div class="fw-semibold mb-2">{{ $question->question }}</div>
                            <input type="hidden" name="evaluations[{{ $question->id }}][answer_type]" value="{{ $question->answer_type }}">
                            @if($question->answer_type === 'score_5')
                                <div class="row g-2 align-items-end">
                                    <div class="col-12 col-md-3">
                                        <label class="form-label mb-1">العلامة من 5</label>
                                        <input class="form-control" type="number" min="0" max="5" step="0.5" name="evaluations[{{ $question->id }}][score]" value="{{ old("evaluations.{$question->id}.score", $response?->score) }}">
                                    </div>
                                    <div class="col-12 col-md-9">
                                        <label class="form-label mb-1">ملاحظة</label>
                                        <input class="form-control" name="evaluations[{{ $question->id }}][note]" value="{{ old("evaluations.{$question->id}.note", $response?->note) }}">
                                    </div>
                                </div>
                            @else
                                <div class="row g-2 align-items-end">
                                    <div class="col-12 col-md-8">
                                        <label class="form-label mb-1">الإجابة</label>
                                        <input class="form-control" name="evaluations[{{ $question->id }}][answer_value]" value="{{ old("evaluations.{$question->id}.answer_value", $response?->answer_value) }}">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label mb-1">ملاحظة</label>
                                        <input class="form-control" name="evaluations[{{ $question->id }}][note]" value="{{ old("evaluations.{$question->id}.note", $response?->note) }}">
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-warning mb-0">لا توجد أسئلة تقييم مفعّلة. يمكن للإدمن إضافتها من شاشة القوائم المرجعية للفعاليات.</div>
                        </div>
                    @endforelse

                    <div class="col-12">
                        <label class="form-label">ملاحظات المتابعة العامة</label>
                        <textarea class="form-control" name="followup_remarks" rows="3">{{ old('followup_remarks', optional($monthlyActivity->followups->last())->remarks) }}</textarea>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-outline-primary" type="submit">حفظ التقييم</button>
                    </div>
                </form>
            @endif
        </div>
    </div>
    @endif

    <div class="card event-card mb-4" id="post-execution-attachments">
        <div class="card-body">
            <div class="alert alert-light border mb-3">
                <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap">
                    <span>هذه الصفحة مخصصة فقط لمدخلات ما بعد التنفيذ.</span>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('role.relations.activities.edit', $monthlyActivity) }}">الرجوع لتعديل التخطيط</a>
                </div>
            </div>
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.attachments.title') }}</h2>
            <form method="POST" action="{{ route('role.programs.attachments.store', $monthlyActivity) }}" enctype="multipart/form-data" class="row g-3 mb-3">
                @csrf
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.attachments.fields.file_type') }}</label>
                    <select class="form-select" name="file_type" >
                        <option value="">-- اختر نوع الملف --</option>
                        <option value="image" @selected(old('file_type') === 'image')>صورة</option>
                        <option value="document" @selected(old('file_type') === 'document')>مستند</option>
                        <option value="report" @selected(old('file_type') === 'report')>تقرير</option>
                        <option value="other" @selected(old('file_type') === 'other')>أخرى</option>
                        <option value="link" @selected(old('file_type') === 'link')>رابط</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">عنوان المرفق / الرابط</label>
                    <input class="form-control" name="title" value="{{ old('title') }}" placeholder="مثال: صور الفعالية على درايف">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">ملف التغطية (اختياري)</label>
                    <input class="form-control" type="file" name="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xlsx,.xls" >
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">رابط خارجي (اختياري)</label>
                    <input class="form-control" type="url" name="external_url" value="{{ old('external_url') }}" placeholder="https://drive.google.com/...">
                    <small class="text-muted">يمكنك رفع ملف أو إضافة رابط خارجي، ويكفي أحدهما.</small>
                </div>
                <div class="col-12 col-md-4 d-flex justify-content-end align-items-center">
                    <button class="btn btn-outline-primary btn-sm mt-4" type="submit">
                        {{ __('app.roles.programs.monthly_activities.attachments.actions.add') }}
                    </button>
                </div>
            </form>
            <div class="event-table-wrap table-responsive">
                <table class="table table-sm align-middle event-table">
                    <thead>
                        <tr>
                            <th>{{ __('app.roles.programs.monthly_activities.attachments.table.file_type') }}</th>
                            <th>العنوان</th>
                            <th>{{ __('app.roles.programs.monthly_activities.attachments.table.file_path') }}</th>
                            <th class="text-end">{{ __('app.roles.programs.monthly_activities.attachments.table.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($monthlyActivity->attachments as $attachment)
                            <tr>
                                <td>{{ $attachment->file_type }}</td>
                                <td>{{ $attachment->title ?: '--' }}</td>
                                <td>
                                    @php $isExternalUrl = filter_var($attachment->file_path, FILTER_VALIDATE_URL); @endphp
                                    <a href="{{ $isExternalUrl ? $attachment->file_path : asset('storage/'.$attachment->file_path) }}" target="_blank" rel="noopener">
                                        {{ $isExternalUrl ? $attachment->file_path : 'عرض المرفق' }}
                                    </a>
                                </td>
                                <td class="text-end">
                                    <form class="d-inline" method="POST" action="{{ route('role.programs.attachments.destroy', $attachment) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            {{ __('app.roles.programs.monthly_activities.attachments.actions.delete') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted">{{ __('app.roles.programs.monthly_activities.attachments.table.empty') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card event-card" id="post-execution-close">
        <div class="card-body">
            <h2 class="h6 mb-3">{{ __('app.roles.programs.monthly_activities.close_title') }}</h2>
            <form method="POST" action="{{ route('role.relations.activities.close', $monthlyActivity) }}" class="row g-3">
                @csrf
                @method('PATCH')
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.actual_date') }}</label>
                    <input class="form-control" type="date" name="actual_date" value="{{ optional($monthlyActivity->actual_date)->format('Y-m-d') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">عدد الحضور الفعلي</label>
                    <input class="form-control" type="number" min="0" name="actual_attendance" value="{{ old('actual_attendance', $monthlyActivity->actual_attendance) }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label>
                    <select class="form-select" name="status" >
                        <option value="closed">{{ __('app.roles.programs.monthly_activities.statuses.closed') }}</option>
                        <option value="completed">{{ __('app.roles.programs.monthly_activities.statuses.completed') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 d-flex justify-content-end align-items-end">
                    <button class="btn btn-outline-primary" type="submit">
                        {{ __('app.roles.programs.monthly_activities.actions.close') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
    </div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const locType = document.querySelector('.js-location-type');
  const inside = document.querySelectorAll('.js-inside-location');
  const outside = document.querySelectorAll('.js-outside-location');
  const tg = document.querySelector('.js-target-group');
  const tgOther = document.querySelectorAll('.js-target-group-other');
  const toggle = () => {
    const outsideSelected = locType && locType.value === 'outside_center';
    inside.forEach(el => el.style.display = outsideSelected ? 'none' : 'block');
    outside.forEach(el => el.style.display = outsideSelected ? 'block' : 'none');
    const selected = tg?.selectedOptions?.[0];
    const isOther = selected && selected.dataset.isOther === '1';
    tgOther.forEach(el => el.style.display = isOther ? 'block' : 'none');
  };
  locType?.addEventListener('change', toggle);
  tg?.addEventListener('change', toggle);
  toggle();

  const params = new URLSearchParams(window.location.search);
  if (params.get('mode') === 'post') {
    document.getElementById('post-execution-close')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
});
</script>
@endpush

@endsection
