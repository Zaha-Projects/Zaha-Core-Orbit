@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.edit_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.relations.agenda.update', $agendaEvent) }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12 col-md-6">
                    <label class="form-label">اسم الفعالية</label>
                    <input class="form-control" name="event_name" value="{{ old('event_name', $agendaEvent->event_name) }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">التاريخ</label>
                    <input class="form-control" type="date" name="event_date" value="{{ old('event_date', optional($agendaEvent->event_date)->toDateString() ?? sprintf('%04d-%02d-%02d', now()->year, $agendaEvent->month, $agendaEvent->day)) }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">القسم المعني</label>
                    <select class="form-select" name="department_id">
                        <option value="">--</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id', $agendaEvent->department_id) == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">صنف الفعالية</label>
                    <select class="form-select" name="event_category_id" id="event_category_id">
                        <option value="">--</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" data-department-id="{{ $category->department_id }}" @selected(old('event_category_id', $agendaEvent->event_category_id) == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">نوع الفعالية</label>
                    <select class="form-select" name="event_type" required>
                        <option value="mandatory" @selected(old('event_type', $agendaEvent->event_type) === 'mandatory')>إجباري</option>
                        <option value="optional" @selected(old('event_type', $agendaEvent->event_type) === 'optional')>اختياري</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">خطة الفعالية</label>
                    <select class="form-select" name="plan_type" required>
                        <option value="unified" @selected(old('plan_type', $agendaEvent->plan_type) === 'unified')>موحد</option>
                        <option value="non_unified" @selected(old('plan_type', $agendaEvent->plan_type) === 'non_unified')>غير موحد</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">مشاركة الفروع</label>
                    <div class="row g-2">
                        @foreach ($branches as $branch)
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">{{ $branch->name }}</label>
                                <select class="form-select form-select-sm" name="branch_participation[{{ $branch->id }}]">
                                    <option value="unspecified" @selected(($branchParticipations[$branch->id] ?? 'unspecified') === 'unspecified')>غير محدد</option>
                                    <option value="participant" @selected(($branchParticipations[$branch->id] ?? 'unspecified') === 'participant')>مشارك</option>
                                    <option value="not_participant" @selected(($branchParticipations[$branch->id] ?? 'unspecified') === 'not_participant')>غير مشارك</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">مشاركة الجهات المركزية</label>
                    <div class="row g-2">
                        @foreach ($departmentUnits as $unit)
                            @php
                                $canEditUnit = auth()->user()->hasRole('relations_manager') || auth()->user()->hasRole($unit->role_name);
                            @endphp
                            <div class="col-12 col-md-6">
                                <form method="POST" action="{{ route('role.relations.agenda.unit_participation.update', $agendaEvent) }}" class="border rounded p-2">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="unit_key" value="{{ $unit->unit_key }}">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="small fw-semibold">{{ $unit->name }}</span>
                                        @if (!$canEditUnit)
                                            <span class="badge text-bg-light">عرض فقط</span>
                                        @endif
                                    </div>
                                    <div class="d-flex gap-2">
                                        <select class="form-select form-select-sm" name="status" @disabled(!$canEditUnit)>
                                            <option value="unspecified" @selected(($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'unspecified')>غير محدد</option>
                                            <option value="participant" @selected(($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'participant')>مشارك</option>
                                            <option value="not_participant" @selected(($unitStatuses[$unit->unit_key] ?? 'unspecified') === 'not_participant')>غير مشارك</option>
                                        </select>
                                        @if ($canEditUnit)
                                            <button class="btn btn-sm btn-outline-primary" type="submit">حفظ</button>
                                        @endif
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea class="form-control" name="notes" rows="3">{{ old('notes', $agendaEvent->notes) }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button class="btn btn-primary" type="submit">تحديث</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const departmentEl = document.querySelector('select[name="department_id"]');
            const categoryEl = document.getElementById('event_category_id');

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
        })();
    </script>
@endsection
