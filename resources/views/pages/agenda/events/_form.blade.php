@php
    $isEditMode = isset($agendaEvent);
    $existingAgendaEvent = $agendaEvent ?? null;
    $selectedPartnerDepartmentIds = array_map('strval', old(
        'partner_department_ids',
        $isEditMode ? $existingAgendaEvent->partnerDepartments->pluck('id')->all() : []
    ));
    $currentPlanFile = $isEditMode ? $existingAgendaEvent->agenda_plan_file : null;
@endphp

<div class="event-module agenda-form-page">
    <div class="card event-card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                <div>
                    <h1 class="h4 mb-1">{{ $title }}</h1>
                    <p class="text-muted mb-0">{{ $subtitle }}</p>
                </div>
                @if (!empty($headerBadge))
                    <span class="badge bg-info-subtle text-info border">{{ $headerBadge }}</span>
                @endif
            </div>

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

            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="row g-4 agenda-form" data-label-participant="{{ __('app.roles.relations.agenda.participation.participant') }}" data-label-not-participant="{{ __('app.roles.relations.agenda.participation.not_participant') }}">
                @csrf
                @if (($formMethod ?? 'POST') !== 'POST')
                    @method($formMethod)
                @endif

                <div class="col-12">
                    <div class="agenda-form-section">
                        <div class="agenda-form-section__head">
                            <h2 class="agenda-form-section__title">البيانات الأساسية</h2>
                            <p class="agenda-form-section__text">ابدأ بتحديد اسم الفعالية وتاريخها والجهة المالكة لها داخل الأجندة السنوية.</p>
                        </div>
                        <div class="row g-3">
                            <div class="col-12 col-lg-6">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_name') }}</label>
                                <input class="form-control @error('event_name') is-invalid @enderror" name="event_name" value="{{ old('event_name', $existingAgendaEvent?->event_name) }}" required>
                                @error('event_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-lg-6">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_date') }}</label>
                                <input class="form-control @error('event_date') is-invalid @enderror" type="date" name="event_date" value="{{ old('event_date', optional($existingAgendaEvent?->event_date)->format('Y-m-d')) }}" required>
                                @error('event_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.owner_department') }}</label>
                                <select class="form-select js-owner-department @error('owner_department_id') is-invalid @enderror" name="owner_department_id" required>
                                    <option value="">{{ __('app.roles.relations.agenda.placeholders.owner_department') }}</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}" {{ (string) old('owner_department_id', $existingAgendaEvent?->owner_department_id) === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                    @endforeach
                                </select>
                                @error('owner_department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6 col-xl-4">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_category') }}</label>
                                <select class="form-select @error('event_category_id') is-invalid @enderror" name="event_category_id" id="event_category_id">
                                    <option value="">{{ __('app.roles.relations.agenda.placeholders.event_category') }}</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}" data-department-id="{{ $category->department_id }}" {{ (string) old('event_category_id', $existingAgendaEvent?->event_category_id) === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('event_category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6 col-xl-2">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.event_type') }}</label>
                                <select class="form-select @error('event_type') is-invalid @enderror" name="event_type" required>
                                    <option value="mandatory" {{ old('event_type', $existingAgendaEvent?->event_type ?? 'mandatory') === 'mandatory' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.types.mandatory') }}</option>
                                    <option value="optional" {{ old('event_type', $existingAgendaEvent?->event_type) === 'optional' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.types.optional') }}</option>
                                </select>
                                @error('event_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 col-md-6 col-xl-2">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.plan_type') }}</label>
                                <select class="form-select js-plan-type @error('plan_type') is-invalid @enderror" name="plan_type" required>
                                    <option value="unified" {{ old('plan_type', $existingAgendaEvent?->plan_type ?? 'unified') === 'unified' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.plans.unified') }}</option>
                                    <option value="non_unified" {{ old('plan_type', $existingAgendaEvent?->plan_type) === 'non_unified' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.plans.non_unified') }}</option>
                                </select>
                                @error('plan_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 js-unified-plan-source">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.unified_plan_source') }}</label>
                                <select class="form-select js-unified-plan-source-select @error('unified_plan_source') is-invalid @enderror" name="unified_plan_source">
                                    <option value="monthly_auto" {{ old('unified_plan_source', 'monthly_auto') === 'monthly_auto' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.unified_plan_sources.monthly_auto') }}</option>
                                    <option value="upload_file" {{ old('unified_plan_source') === 'upload_file' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.unified_plan_sources.upload_file') }}</option>
                                </select>
                                <div class="form-text">{{ __('app.roles.relations.agenda.hints.unified_plan_source') }}</div>
                                @error('unified_plan_source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12 js-agenda-plan-file">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.agenda_plan_file') }}</label>
                                <input class="form-control @error('agenda_plan_file') is-invalid @enderror" type="file" name="agenda_plan_file" accept=".pdf,.doc,.docx,.xls,.xlsx">
                                @if ($currentPlanFile)
                                    <a class="small d-inline-block mt-1" href="{{ asset('storage/' . $currentPlanFile) }}" target="_blank">{{ __('app.roles.relations.agenda.actions.view_current_attachment') }}</a>
                                @endif
                                <div class="form-text">{{ __('app.roles.relations.agenda.hints.agenda_plan_file') }}</div>
                                @error('agenda_plan_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="agenda-form-section">
                        <div class="agenda-form-section__head">
                            <h2 class="agenda-form-section__title">{{ __('app.roles.relations.agenda.fields_ext.partner_department') }}</h2>
                            <p class="agenda-form-section__text">اختر الجهات الشريكة في الفعالية. الجهة المالكة يتم استثناؤها تلقائياً من هذه القائمة.</p>
                        </div>
                        <div class="partner-departments-box">
                            @foreach ($departments as $department)
                                <label class="partner-department-item">
                                    <input class="form-check-input m-0 js-partner-department" type="checkbox" name="partner_department_ids[]" value="{{ $department->id }}" {{ in_array((string) $department->id, $selectedPartnerDepartmentIds, true) ? 'checked' : '' }}>
                                    <span>{{ $department->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="agenda-form-section">
                        <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                            <div>
                                <h2 class="agenda-form-section__title mb-1">{{ __('app.roles.relations.agenda.fields_ext.branch_participation') }}</h2>
                                <p class="agenda-form-section__text mb-0">حدد الفروع المشاركة بسرعة من خلال المفاتيح أدناه.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary js-enable-all-participants">تفعيل الكل كمشارك</button>
                        </div>
                        <div class="row g-3">
                            @foreach ($branches as $branch)
                                @php
                                    $branchStatus = old('branch_participation.' . $branch->id, $branchParticipations[$branch->id] ?? 'not_participant');
                                    $isParticipant = $branchStatus === 'participant';
                                @endphp
                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="branch-toggle-item">
                                        <div>
                                            <div class="fw-semibold">{{ $branch->name }}</div>
                                            <div class="small text-muted">تحديث حالة مشاركة الفرع في هذه الفعالية.</div>
                                        </div>
                                        <div class="form-check form-switch m-0">
                                            <input type="hidden" name="branch_participation[{{ $branch->id }}]" value="{{ $isParticipant ? 'participant' : 'not_participant' }}" class="js-branch-status-hidden">
                                            <input class="form-check-input js-branch-toggle" type="checkbox" role="switch" {{ $isParticipant ? 'checked' : '' }}>
                                            <label class="form-check-label small">{{ $isParticipant ? __('app.roles.relations.agenda.participation.participant') : __('app.roles.relations.agenda.participation.not_participant') }}</label>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="agenda-form-section">
                        <div class="agenda-form-section__head">
                            <h2 class="agenda-form-section__title">{{ __('app.roles.relations.agenda.fields.notes') }}</h2>
                            <p class="agenda-form-section__text">أضف أي ملاحظات تنظيمية أو توضيحات مهمة للفعالية.</p>
                        </div>
                        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="4">{{ old('notes', $existingAgendaEvent?->notes) }}</textarea>
                        @error('notes')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="col-12">
                    <div class="agenda-form-actions">
                        @if ($isEditMode)
                            <a class="btn btn-outline-secondary" href="{{ route('role.relations.agenda.show', $agendaEvent) }}">رجوع للتفاصيل</a>
                        @else
                            <a class="btn btn-outline-secondary" href="{{ route('role.relations.agenda.index') }}">رجوع للقائمة</a>
                        @endif
                        <button class="btn btn-primary" type="submit">{{ $submitLabel }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/agenda-events-form.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/agenda-events-form.js') }}"></script>
@endpush
