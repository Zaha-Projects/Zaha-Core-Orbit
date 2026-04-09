@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.create_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp


@section('content')
    <div class="event-module"><div class="card event-card">
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
            <form method="POST" action="{{ route('role.relations.agenda.store') }}" enctype="multipart/form-data" class="row event-form-grid">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_name') }}</label>
                    <input class="form-control" name="event_name" value="{{ old('event_name') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_date') }}</label>
                    <input class="form-control" type="date" name="event_date" value="{{ old('event_date') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.primary_department') }}</label>
                    <select class="form-select" name="department_id">
                        <option value="">--</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.partner_department') }}</label>
                    @php
                        $selectedPartnerDepartmentIds = array_map('strval', old('partner_department_ids', []));
                    @endphp
                    <div class="partner-departments-box">
                        @foreach ($departments as $department)
                            <label class="partner-department-item">
                                <input class="form-check-input m-0" type="checkbox" name="partner_department_ids[]" value="{{ $department->id }}" {{ in_array((string) $department->id, $selectedPartnerDepartmentIds, true) ? 'checked' : '' }}>
                                <span>{{ $department->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields.event_category') }}</label>
                    <select class="form-select" name="event_category_id" id="event_category_id">
                        <option value="">--</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" data-department-id="{{ $category->department_id }}" {{ old('event_category_id') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.event_type') }}</label>
                    <select class="form-select" name="event_type" required>
                        <option value="mandatory" {{ old('event_type') === 'mandatory' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.types.mandatory') }}</option>
                        <option value="optional" {{ old('event_type') === 'optional' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.types.optional') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">{{ __('app.roles.relations.agenda.fields_ext.plan_type') }}</label>
                    <select class="form-select js-plan-type" name="plan_type" required>
                        <option value="unified" {{ old('plan_type') === 'unified' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.plans.unified') }}</option>
                        <option value="non_unified" {{ old('plan_type') === 'non_unified' ? 'selected' : '' }}>{{ __('app.roles.relations.agenda.plans.non_unified') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 js-agenda-plan-file">
                    <label class="form-label">agenda_plan_file</label>
                    <input class="form-control" type="file" name="agenda_plan_file" accept=".pdf,.doc,.docx,.xls,.xlsx">
                </div>

                <div class="col-12"><div class="event-form-section">
                    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                        <h2 class="event-section-title mb-0">{{ __('app.roles.relations.agenda.fields_ext.branch_participation') }}</h2>
                        <button type="button" class="btn btn-sm btn-outline-primary js-enable-all-participants">تفعيل الكل كمشارك</button>
                    </div>
                    <div class="row g-2">
                        @foreach ($branches as $branch)
                            @php
                                $branchStatus = old('branch_participation.'.$branch->id, 'not_participant');
                                $isParticipant = $branchStatus === 'participant';
                            @endphp
                            <div class="col-12 col-md-4">
                                <div class="branch-toggle-item">
                                    <div class="small fw-semibold">{{ $branch->name }}</div>
                                    <div class="form-check form-switch m-0">
                                        <input type="hidden" name="branch_participation[{{ $branch->id }}]" value="{{ $isParticipant ? 'participant' : 'not_participant' }}" class="js-branch-status-hidden">
                                        <input class="form-check-input js-branch-toggle" type="checkbox" role="switch" @checked($isParticipant)>
                                        <label class="form-check-label small">{{ $isParticipant ? __('app.roles.relations.agenda.participation.participant') : __('app.roles.relations.agenda.participation.not_participant') }}</label>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div></div>

                <div class="col-12"><div class="event-form-section">
                    <h2 class="event-section-title">{{ __('app.roles.relations.agenda.fields.notes') }}</h2>
                    <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                </div></div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">{{ __('app.roles.relations.agenda.actions.save') }}</button>
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

            const toggleRows = Array.from(document.querySelectorAll('.branch-toggle-item'));
            const enableAllBtn = document.querySelector('.js-enable-all-participants');

            function syncToggleRow(row) {
                const checkbox = row.querySelector('.js-branch-toggle');
                const hiddenInput = row.querySelector('.js-branch-status-hidden');
                const label = row.querySelector('.form-check-label');
                const isOn = !!checkbox?.checked;

                hiddenInput.value = isOn ? 'participant' : 'not_participant';
                label.textContent = isOn
                    ? "{{ __('app.roles.relations.agenda.participation.participant') }}"
                    : "{{ __('app.roles.relations.agenda.participation.not_participant') }}";
            }

            toggleRows.forEach((row) => {
                const checkbox = row.querySelector('.js-branch-toggle');
                checkbox?.addEventListener('change', () => syncToggleRow(row));
                syncToggleRow(row);
            });

            enableAllBtn?.addEventListener('click', () => {
                toggleRows.forEach((row) => {
                    const checkbox = row.querySelector('.js-branch-toggle');
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                    syncToggleRow(row);
                });
            });
        })();
    </script>
    <style>
        .partner-departments-box {
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            padding: .5rem;
            max-height: 180px;
            overflow-y: auto;
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .5rem;
        }
        .partner-department-item {
            display: flex;
            align-items: center;
            gap: .5rem;
            padding: .25rem .4rem;
            border-radius: .35rem;
            background: #f8f9fa;
            margin: 0;
            font-size: .9rem;
        }
        .branch-toggle-item {
            border: 1px solid #dee2e6;
            border-radius: .5rem;
            padding: .55rem .75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
        }
    </style>
@endsection
