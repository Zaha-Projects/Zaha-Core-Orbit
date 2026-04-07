@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.edit_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp


@section('content')
    <div class="event-module"><div class="card event-card">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.relations.agenda.update', $agendaEvent) }}" enctype="multipart/form-data" class="row event-form-grid">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_name') }}</label>
                    <input class="form-control" name="event_name" value="{{ old('event_name', $agendaEvent->event_name) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_date') }}</label>
                    <input class="form-control" type="date" name="event_date" value="{{ old('event_date', optional($agendaEvent->event_date)->format('Y-m-d')) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.primary_department') }}</label>
                    <select class="form-select" name="department_id">
                        <option value="">--</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $agendaEvent->department_id) == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.partner_department') }}</label>
                    <select class="form-select" name="partner_department_ids[]" multiple size="5">
                        <option value="">--</option>
                        @php
                            $selectedPartnerDepartmentIds = array_map('strval', old('partner_department_ids', $agendaEvent->partnerDepartments->pluck('id')->all()));
                        @endphp
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(in_array((string) $department->id, $selectedPartnerDepartmentIds, true))>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_category') }}</label>
                    <select class="form-select" name="event_category_id" id="event_category_id">
                        <option value="">--</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" data-department-id="{{ $category->department_id }}" @selected(old('event_category_id', $agendaEvent->event_category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.event_type') }}</label>
                    <select class="form-select" name="event_type" required>
                        <option value="mandatory" @selected(old('event_type', $agendaEvent->event_type) === 'mandatory')>{{ __('app.roles.relations.agenda.types.mandatory') }}</option>
                        <option value="optional" @selected(old('event_type', $agendaEvent->event_type) === 'optional')>{{ __('app.roles.relations.agenda.types.optional') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.plan_type') }}</label>
                    <select class="form-select js-plan-type" name="plan_type" required>
                        <option value="unified" @selected(old('plan_type', $agendaEvent->plan_type) === 'unified')>{{ __('app.roles.relations.agenda.plans.unified') }}</option>
                        <option value="non_unified" @selected(old('plan_type', $agendaEvent->plan_type) === 'non_unified')>{{ __('app.roles.relations.agenda.plans.non_unified') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 js-agenda-plan-file">
                    <label class="form-label">agenda_plan_file</label>
                    <input class="form-control" type="file" name="agenda_plan_file" accept=".pdf,.doc,.docx,.xls,.xlsx">
                    @if($agendaEvent->agenda_plan_file)
                        <a class="small d-block mt-1" href="{{ asset('storage/'.$agendaEvent->agenda_plan_file) }}" target="_blank">عرض الملف الحالي</a>
                    @endif
                </div>

                <div class="col-12"><div class="event-form-section">
                    <h2 class="event-section-title">{{ __('app.roles.relations.agenda.fields_ext.branch_participation') }}</h2>
                    <div class="row g-2">
                        @foreach ($branches as $branch)
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">{{ $branch->name }}</label>
                                <select class="form-select form-select-sm" name="branch_participation[{{ $branch->id }}]">
                                    <option value="unspecified" @selected(($branchParticipations[$branch->id] ?? 'unspecified') === 'unspecified')>{{ __('app.roles.relations.agenda.participation.unspecified') }}</option>
                                    <option value="participant" @selected(($branchParticipations[$branch->id] ?? 'unspecified') === 'participant')>{{ __('app.roles.relations.agenda.participation.participant') }}</option>
                                    <option value="not_participant" @selected(($branchParticipations[$branch->id] ?? 'unspecified') === 'not_participant')>{{ __('app.roles.relations.agenda.participation.not_participant') }}</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div></div>

                <div class="col-12"><div class="event-form-section">
                    <h2 class="event-section-title">{{ __('app.roles.relations.agenda.fields_ext.unit_participation') }}</h2>
                    <div class="row g-2">
                        @foreach ($departmentUnits as $unit)
                            @php
                                $canEditUnit = auth()->user()->hasRole('relations_manager') || auth()->user()->hasRole($unit->role_name);
                            @endphp
                            <div class="col-12 col-md-6">
                                <form method="POST" action="{{ route('role.relations.agenda.unit_participation.update', $agendaEvent) }}" class="event-approval-panel">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="unit_key" value="{{ $unit->unit_key }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small fw-semibold">{{ $unit->name }}</span>
                                        @if (!$canEditUnit)
                                            <span class="badge text-bg-light">{{ __('app.roles.relations.agenda.participation.view_only') }}</span>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-2">
                                        <select class="form-select form-select-sm" name="status" @disabled(!$canEditUnit)>
                                            <option value="unspecified" @selected(($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'unspecified')>{{ __('app.roles.relations.agenda.participation.unspecified') }}</option>
                                            <option value="participant" @selected(($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'participant')>{{ __('app.roles.relations.agenda.participation.participant') }}</option>
                                            <option value="not_participant" @selected(($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'not_participant')>{{ __('app.roles.relations.agenda.participation.not_participant') }}</option>
                                        </select>
                                        @if ($canEditUnit)
                                            <button class="btn btn-sm btn-outline-primary" type="submit">{{ __('app.roles.relations.agenda.actions.save') }}</button>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div></div>

                <div class="col-12"><div class="event-form-section">
                    <h2 class="event-section-title">{{ __('app.roles.relations.agenda.fields.notes') }}</h2>
                    <textarea class="form-control" name="notes" rows="3">{{ old('notes', $agendaEvent->notes) }}</textarea>
                </div></div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button class="btn btn-primary" type="submit">{{ __('app.roles.relations.agenda.actions.update') }}</button>
                </div>
            </form>
        </div>
    </div></div>

    <script>
        (function () {
            const departmentEl = document.querySelector('select[name="department_id"]');
            const categoryEl = document.getElementById('event_category_id');
            const planTypeEl = document.querySelector('.js-plan-type');
            const planFileRows = document.querySelectorAll('.js-agenda-plan-file');

            function filterCategories() {
                const departmentId = departmentEl.value;

                Array.from(categoryEl.options).forEach((option) => {
                    const categoryDepartmentId = option.dataset.departmentId;
                    if (!categoryDepartmentId) {
                        option.hidden = false;
                        return;
                    }
                    option.hidden = departmentId !== '' && categoryDepartmentId !== departmentId;
                });

                if (categoryEl.selectedOptions[0]?.hidden) {
                    categoryEl.value = '';
                }
            }

            departmentEl.addEventListener('change', filterCategories);
            filterCategories();

            function togglePlanFile() {
                const isNonUnified = planTypeEl?.value === 'non_unified';
                planFileRows.forEach((row) => row.style.display = isNonUnified ? 'block' : 'none');
            }

            planTypeEl?.addEventListener('change', togglePlanFile);
            togglePlanFile();
        })();
    </script>
@endsection
