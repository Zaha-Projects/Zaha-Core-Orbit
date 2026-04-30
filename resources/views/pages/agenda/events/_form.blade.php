@php
    $isEditMode = isset($agendaEvent);
    $existingAgendaEvent = $agendaEvent ?? null;
    $minimumEventDate = now()->toDateString();
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
                <input type="hidden" name="monthly_template_title" value="{{ old('monthly_template_title') }}">
                <input type="hidden" name="monthly_template_proposed_date" value="{{ old('monthly_template_proposed_date') }}">
                <input type="hidden" name="monthly_template_description" value="{{ old('monthly_template_description') }}">
                <input type="hidden" name="monthly_template_target_group" value="{{ old('monthly_template_target_group') }}">
                <input type="hidden" name="monthly_template_execution_time" value="{{ old('monthly_template_execution_time') }}">
                <input type="hidden" name="monthly_template_time_from" value="{{ old('monthly_template_time_from') }}">
                <input type="hidden" name="monthly_template_time_to" value="{{ old('monthly_template_time_to') }}">

                <div class="col-12">
                    <div class="agenda-form-section js-unified-template-section d-none">
                        <div class="agenda-form-section__head">
                            <h2 class="agenda-form-section__title">قالب الخطة الشهرية الموحدة</h2>
                            <p class="agenda-form-section__text">عند اختيار خطة موحدة، يجب تعبئة القالب قبل الإرسال.</p>
                        </div>
                        <button class="btn btn-outline-primary" type="button" id="openUnifiedTemplateModal">فتح قالب الخطة الموحدة</button>
                    </div>
                </div>

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
                                <input class="form-control @error('event_date') is-invalid @enderror" type="date" name="event_date" min="{{ $minimumEventDate }}" value="{{ old('event_date', optional($existingAgendaEvent?->event_date)->format('Y-m-d')) }}" required>
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
                                <select class="form-select js-event-type @error('event_type') is-invalid @enderror" name="event_type" required>
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
                            <div class="col-12 col-md-6 col-xl-2">
                                <label class="form-label">حالة التفعيل</label>
                                <select class="form-select @error('is_active') is-invalid @enderror" name="is_active" required>
                                    <option value="1" {{ (string) old('is_active', (int) ($existingAgendaEvent?->is_active ?? true)) === '1' ? 'selected' : '' }}>نشطة</option>
                                    <option value="0" {{ (string) old('is_active', (int) ($existingAgendaEvent?->is_active ?? true)) === '0' ? 'selected' : '' }}>غير نشطة</option>
                                </select>
                                @error('is_active')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12 js-unified-plan-source">
                                <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.unified_plan_source') }}</label>
                                <input type="hidden" class="js-unified-plan-source-select" name="unified_plan_source" value="monthly_auto">
                                <input class="form-control" value="{{ __('app.roles.relations.agenda.unified_plan_sources.monthly_auto') }}" disabled>
                                <div class="form-text">{{ __('app.roles.relations.agenda.hints.unified_plan_source') }}</div>
                                @error('unified_plan_source')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                @if(false)
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

                @endif

                <div class="col-12 js-branch-participation-section">
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
                            <h2 class="agenda-form-section__title">الوصف التفصيلي</h2>
                            <p class="agenda-form-section__text">اكتب وصفًا تفصيليًا للفعالية ليتم اعتماده تلقائيًا في النشاط الشهري المرتبط.</p>
                        </div>
                        <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="4" placeholder="مثال: فكرة الفعالية، الأهداف، الفقرات الرئيسية، الفئة المستهدفة، ومخرجات التنفيذ المتوقعة.">{{ old('notes', $existingAgendaEvent?->notes) }}</textarea>
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

<div class="modal fade" id="unifiedTemplateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">إدخال قالب الخطة الشهرية الموحدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6"><label class="form-label">عنوان النشاط الشهري *</label><input class="form-control" id="tpl_title" readonly></div>
                    <div class="col-12 col-md-6"><label class="form-label">التاريخ المقترح *</label><input class="form-control" type="date" id="tpl_proposed_date" readonly></div>
                    <div class="col-12"><label class="form-label">الوصف *</label><textarea class="form-control" rows="3" id="tpl_description" readonly></textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label">الفئة المستهدفة *</label><select class="form-select" id="tpl_target_group"><option value="">اختر الفئة المستهدفة</option>@foreach(($targetGroups ?? collect()) as $group)<option value="{{ $group->id }}">{{ $group->name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-3"><label class="form-label">الوقت الفعلي للتنفيذ من *</label><input class="form-control" type="time" id="tpl_time_from"></div>
                    <div class="col-12 col-md-3"><label class="form-label">الوقت الفعلي للتنفيذ إلى *</label><input class="form-control" type="time" id="tpl_time_to"></div>
                </div>
                <small class="text-danger d-none" id="tpl_required_error">الحقول الإلزامية غير مكتملة.</small>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">إغلاق</button>
                <button class="btn btn-primary" type="button" id="saveUnifiedTemplate">حفظ القالب</button>
            </div>
        </div>
    </div>
</div>


@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/css/event-ui-shared.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/agenda-events-form.css') }}">
@endpush

@push('scripts')
    <script src="{{ asset('assets/js/agenda-events-form.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form.agenda-form');
            if (!form) return;
            const planType = form.querySelector('.js-plan-type');
            const unifiedSection = document.querySelector('.js-unified-template-section');
            const modalEl = document.getElementById('unifiedTemplateModal');
            const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
            const requiredError = document.getElementById('tpl_required_error');
            const map = ['title','proposed_date','description','target_group','execution_time','time_from','time_to'];
            const hidden = (k) => form.querySelector(`input[name="monthly_template_${k}"]`);
            const field = (k) => document.getElementById(`tpl_${k}`);
            const syncUnified = () => unifiedSection?.classList.toggle('d-none', planType?.value !== 'unified');
            const syncFromEvent = () => {
                const eventName = form.querySelector('input[name="event_name"]')?.value || '';
                const eventDate = form.querySelector('input[name="event_date"]')?.value || '';
                const notes = form.querySelector('textarea[name="notes"]')?.value || '';
                if (field('title')) field('title').value = eventName;
                if (field('proposed_date')) field('proposed_date').value = eventDate;
                if (field('description')) field('description').value = notes;
                if (hidden('title')) hidden('title').value = eventName;
                if (hidden('proposed_date')) hidden('proposed_date').value = eventDate;
                if (hidden('description')) hidden('description').value = notes;
            };
            syncUnified();
            planType?.addEventListener('change', syncUnified);
            map.forEach((k) => { if (field(k) && hidden(k)) field(k).value = hidden(k).value || ''; });
            syncFromEvent();
            form.querySelector('input[name="event_name"]')?.addEventListener('input', syncFromEvent);
            form.querySelector('input[name="event_date"]')?.addEventListener('input', syncFromEvent);
            form.querySelector('textarea[name="notes"]')?.addEventListener('input', syncFromEvent);
            document.getElementById('openUnifiedTemplateModal')?.addEventListener('click', () => modal?.show());
            document.getElementById('saveUnifiedTemplate')?.addEventListener('click', () => {
                const timeFrom = field('time_from')?.value || '';
                const timeTo = field('time_to')?.value || '';
                const ok = ['title','proposed_date','description','target_group'].every((k) => (field(k)?.value || '').trim() !== '') && timeFrom !== '' && timeTo !== '';
                requiredError?.classList.toggle('d-none', ok);
                if (!ok) return;
                if (hidden('execution_time')) hidden('execution_time').value = `${timeFrom} - ${timeTo}`;
                if (hidden('time_from')) hidden('time_from').value = timeFrom;
                if (hidden('time_to')) hidden('time_to').value = timeTo;
                map.forEach((k) => { if (['execution_time','time_from','time_to'].includes(k)) return; if (hidden(k)) hidden(k).value = field(k)?.value || ''; });
                modal?.hide();
            });
            form.addEventListener('submit', function (e) {
                if (planType?.value !== 'unified') return;
                const ok = ['title','proposed_date','description','target_group','execution_time'].every((k) => (hidden(k)?.value || '').trim() !== '');
                if (!ok) {
                    e.preventDefault();
                    modal?.show();
                }
            });
        });
    </script>
@endpush
