@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

    $t = [
        'page_title' => $isArabic ? 'منشئ سير الاعتماد' : 'Workflow Builder',
        'page_help' => $isArabic
            ? 'قم بترتيب مراحل الاعتماد حسب التسلسل. كل مرحلة تمثل جهة مسؤولة عن الموافقة على الطلب.'
            : 'Arrange approval steps in order. Each step represents a responsible role for approving the request.',
        'create_workflow' => $isArabic ? 'إنشاء سير اعتماد' : 'Create Workflow',
        'workflow_module' => $isArabic ? 'الوحدة' : 'Module',
        'workflow_code' => $isArabic ? 'رمز سير الاعتماد' : 'Workflow Code',
        'name_ar' => $isArabic ? 'الاسم بالعربية' : 'Name (Arabic)',
        'name_en' => $isArabic ? 'الاسم بالإنجليزية' : 'Name (English)',
        'create' => $isArabic ? 'إنشاء' : 'Create',
        'save_all' => $isArabic ? 'حفظ جميع التعديلات' : 'Save All Changes',
        'saving' => $isArabic ? 'جاري الحفظ...' : 'Saving...',
        'delete_workflow' => $isArabic ? 'حذف سير الاعتماد' : 'Delete Workflow',
        'steps' => $isArabic ? 'مراحل الموافقة' : 'Approval Steps',
        'add_step' => $isArabic ? 'إضافة مرحلة' : 'Add Step',
        'step_order' => $isArabic ? 'ترتيب المرحلة' : 'Step Order',
        'approval_type' => $isArabic ? 'نوع الموافقة' : 'Approval Type',
        'preliminary_approval' => $isArabic ? 'موافقة مبدئية' : 'Preliminary Approval',
        'final_approval' => $isArabic ? 'موافقة نهائية' : 'Final Approval',
        'role' => $isArabic ? 'المسؤول' : 'Responsible Role',
        'choose_role' => $isArabic ? 'اختر الدور' : 'Select role',
        'drag_hint' => $isArabic ? 'اسحب وأفلت المرحلة لتغيير ترتيبها.' : 'Drag and drop a step to change its order.',
        'delete_step' => $isArabic ? 'حذف المرحلة' : 'Delete Step',
        'timeline_title' => $isArabic ? 'تسلسل الاعتماد' : 'Approval Timeline',
        'step' => $isArabic ? 'المرحلة' : 'Step',
    ];

    $translateRole = static function ($role) use ($isArabic) {
        if (!$role) {
            return null;
        }

        if ($isArabic) {
            return $role->name_ar ?: ($role->name_en ?: $role->name);
        }

        return $role->name_en ?: ($role->name_ar ?: $role->name);
    };

@endphp

@section('content')
<div class="row g-4">
    <div class="col-12">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h1 class="h4 mb-2">{{ $t['page_title'] }}</h1>
                <div class="alert alert-info mb-0">{{ $t['page_help'] }}</div>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h6 mb-3">{{ $t['create_workflow'] }}</h2>
                <form method="POST" action="{{ route('role.super_admin.workflows.store') }}" class="row g-3">
                    @csrf
                    <div class="col-md-3">
                        <label class="form-label">{{ $t['workflow_module'] }}</label>
                        <input class="form-control" name="module" placeholder="monthly_activities" required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">{{ $t['workflow_code'] }}</label>
                        <input class="form-control" name="code" placeholder="monthly_plan_approval" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ $t['name_ar'] }}</label>
                        <input class="form-control" name="name_ar" placeholder="{{ $isArabic ? 'مثال: اعتماد الخطة الشهرية' : 'Example: Monthly Plan Approval' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ $t['name_en'] }}</label>
                        <input class="form-control" name="name_en" placeholder="{{ $isArabic ? 'مثال: الموافقة على الخطة الشهرية' : 'Example: Monthly Plan Approval' }}">
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button class="btn btn-primary w-100">{{ $t['create'] }}</button>
                    </div>
                </form>
            </div>
        </div>

        @foreach($workflows as $workflow)
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form method="POST" action="{{ route('role.super_admin.workflows.update', $workflow) }}" class="row g-3 mb-3">
                    @csrf
                    @method('PUT')
                    <div class="col-md-2"><label class="form-label">{{ $t['workflow_module'] }}</label><input class="form-control form-control-sm" name="module" value="{{ $workflow->module }}" required></div>
                    <div class="col-md-2"><label class="form-label">{{ $t['workflow_code'] }}</label><input class="form-control form-control-sm" name="code" value="{{ $workflow->code }}" required></div>
                    <div class="col-md-3"><label class="form-label">{{ $t['name_ar'] }}</label><input class="form-control form-control-sm" name="name_ar" value="{{ $workflow->name_ar }}"></div>
                    <div class="col-md-3"><label class="form-label">{{ $t['name_en'] }}</label><input class="form-control form-control-sm" name="name_en" value="{{ $workflow->name_en }}"></div>
                    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary btn-sm w-100">{{ $t['save_all'] }}</button></div>
                </form>
                <form method="POST" action="{{ route('role.super_admin.workflows.destroy', $workflow) }}" class="text-end mb-3">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm">{{ $t['delete_workflow'] }}</button>
                </form>

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h3 class="h6 mb-0">{{ $t['steps'] }}</h3>
                    <small class="text-muted">{{ $t['drag_hint'] }}</small>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn btn-primary btn-sm js-save-all-steps" data-workflow-id="{{ $workflow->id }}">{{ $t['save_all'] }}</button>
                </div>

                <form method="POST" action="{{ route('role.super_admin.workflow_steps.reorder', $workflow) }}" class="js-reorder-form mb-3" data-workflow-id="{{ $workflow->id }}">
                    @csrf
                    <input type="hidden" name="ordered_ids" value="">
                </form>

                <div class="row g-3">
                    <div class="col-lg-8">
                        <div class="workflow-steps-list" data-workflow-id="{{ $workflow->id }}">
                            @foreach($workflow->steps as $step)
                                @php
                                    $stageLabel = $step->step_type === 'main' ? $t['final_approval'] : $t['preliminary_approval'];
                                    $roleLabel = $translateRole($step->role) ?: ($step->role?->name ?? '-');
                                @endphp
                                <div class="workflow-step-card border rounded p-3 mb-2" data-step-id="{{ $step->id }}" data-workflow-id="{{ $workflow->id }}">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h4 class="h6 mb-0 text-success">{{ $t['step'] }} <span class="js-order-label">{{ $step->step_order }}</span></h4>
                                        <span class="badge bg-light text-dark">⇅</span>
                                    </div>

                                    <div class="small lh-lg mb-3">
                                        <div>📌 {{ $t['approval_type'] }}: <strong class="js-stage-label">{{ $stageLabel }}</strong></div>
                                        <div>👤 {{ $t['role'] }}: <strong>{{ $roleLabel }}</strong></div>
                                    </div>

                                    <form method="POST" action="{{ route('role.super_admin.workflow_steps.update', $step) }}" class="row g-2 js-step-form" data-workflow-id="{{ $workflow->id }}">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="step_key" value="{{ $step->step_key }}" class="js-step-key">
                                        <input type="hidden" name="step_order" value="{{ $step->step_order }}" class="js-step-order-input">
                                        <input type="hidden" name="approval_level" value="{{ $step->step_order }}" class="js-step-level-input">
                                        <div class="col-md-12">
                                            <label class="form-label form-label-sm">{{ $t['approval_type'] }}</label>
                                            <div class="d-flex flex-wrap gap-3">
                                                <div class="form-check">
                                                    <input class="form-check-input js-step-type" type="radio" name="step_type" value="sub" id="step-type-sub-{{ $step->id }}" @checked($step->step_type==='sub')>
                                                    <label class="form-check-label" for="step-type-sub-{{ $step->id }}">{{ $t['preliminary_approval'] }}</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input js-step-type" type="radio" name="step_type" value="main" id="step-type-main-{{ $step->id }}" @checked($step->step_type==='main')>
                                                    <label class="form-check-label" for="step-type-main-{{ $step->id }}">{{ $t['final_approval'] }}</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">{{ $t['role'] }}</label>
                                            <select class="form-select form-select-sm" name="role_id" required>
                                                <option value="">{{ $t['choose_role'] }}</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->id }}" @selected($step->role_id===$role->id)>{{ $translateRole($role) ?: $role->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">{{ $t['name_ar'] }}</label>
                                            <input class="form-control form-control-sm" name="name_ar" value="{{ $step->name_ar }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label form-label-sm">{{ $t['name_en'] }}</label>
                                            <input class="form-control form-control-sm" name="name_en" value="{{ $step->name_en }}">
                                        </div>
                                    </form>

                                    <form method="POST" action="{{ route('role.super_admin.workflow_steps.destroy', $step) }}" class="text-end mt-2">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm">{{ $t['delete_step'] }}</button>
                                    </form>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="border rounded p-3 bg-light h-100">
                            <h4 class="h6 mb-3">{{ $t['timeline_title'] }}</h4>
                            <div class="js-timeline" data-workflow-id="{{ $workflow->id }}">
                                @foreach($workflow->steps as $step)
                                    <div class="timeline-item small mb-2" data-step-id="{{ $step->id }}">
                                        [<span class="js-order-label">{{ $step->step_order }}</span>] {{ $translateRole($step->role) ?: ($step->role?->name ?? '-') }}
                                    </div>
                                    @if(!$loop->last)
                                        <div class="text-center text-muted mb-2">↓</div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border rounded p-3 mt-3 bg-light-subtle">
                    <h4 class="h6">{{ $t['add_step'] }}</h4>
                    <form method="POST" action="{{ route('role.super_admin.workflow_steps.store', $workflow) }}" class="row g-2 js-step-form" data-workflow-id="{{ $workflow->id }}">
                        @csrf
                        <input type="hidden" name="step_key" class="js-step-key" value="">
                        <input type="hidden" name="approval_level" class="js-step-level-input" value="{{ max(1, ((int) $workflow->steps->max('step_order')) + 1) }}">
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">{{ $t['step_order'] }}</label>
                            <input class="form-control form-control-sm js-step-order-input" name="step_order" type="number" min="1" value="{{ max(1, ((int) $workflow->steps->max('step_order')) + 1) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">{{ $t['approval_type'] }}</label>
                            <div class="d-flex flex-wrap gap-3 mt-1">
                                <div class="form-check">
                                    <input class="form-check-input js-step-type" type="radio" name="step_type" value="sub" id="new-step-type-sub-{{ $workflow->id }}" checked>
                                    <label class="form-check-label" for="new-step-type-sub-{{ $workflow->id }}">{{ $t['preliminary_approval'] }}</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input js-step-type" type="radio" name="step_type" value="main" id="new-step-type-main-{{ $workflow->id }}">
                                    <label class="form-check-label" for="new-step-type-main-{{ $workflow->id }}">{{ $t['final_approval'] }}</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ $t['role'] }}</label>
                            <select class="form-select form-select-sm" name="role_id" required>
                                <option value="">{{ $t['choose_role'] }}</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $translateRole($role) ?: $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">{{ $t['name_ar'] }}</label>
                            <input class="form-control form-control-sm" name="name_ar">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">{{ $t['name_en'] }}</label>
                            <input class="form-control form-control-sm" name="name_en">
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button class="btn btn-success btn-sm w-100">{{ $t['add_step'] }}</button>
                        </div>
                    </form>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-primary btn-sm js-save-all-steps" data-workflow-id="{{ $workflow->id }}">{{ $t['save_all'] }}</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('styles')
<style>
    .workflow-step-card { transition: box-shadow .2s ease; }
    .workflow-step-card:hover { box-shadow: 0 4px 14px rgba(0,0,0,.08); }
    .sortable-ghost { opacity: .35; border: 2px dashed #0d6efd; }
    .sortable-chosen { box-shadow: 0 8px 20px rgba(13,110,253,.20); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var labels = {
            preliminary: @json($t['preliminary_approval']),
            final: @json($t['final_approval']),
            saving: @json($t['saving'])
        };

        function getSelectedType(form) {
            var checked = form.querySelector('.js-step-type:checked');
            return checked ? checked.value : 'sub';
        }

        function updateStepMeta(form) {
            var orderInput = form.querySelector('.js-step-order-input');
            var levelInput = form.querySelector('.js-step-level-input');
            var stepKeyInput = form.querySelector('.js-step-key');
            var stepType = getSelectedType(form);

            if (orderInput && levelInput) {
                levelInput.value = orderInput.value;
            }

            if (stepKeyInput && orderInput) {
                stepKeyInput.value = stepType + '_approval_' + orderInput.value;
            }

            var card = form.closest('.workflow-step-card');
            if (card) {
                var label = card.querySelector('.js-stage-label');
                if (label) {
                    label.textContent = stepType === 'main' ? labels.final : labels.preliminary;
                }
            }
        }

        function refreshTimeline(workflowId) {
            var list = document.querySelector('.workflow-steps-list[data-workflow-id="' + workflowId + '"]');
            var timeline = document.querySelector('.js-timeline[data-workflow-id="' + workflowId + '"]');
            if (!list || !timeline) {
                return;
            }

            timeline.innerHTML = '';
            Array.from(list.querySelectorAll('.workflow-step-card')).forEach(function (card, index, arr) {
                var roleText = card.querySelector('.small strong:nth-of-type(1)');
                var wrapper = document.createElement('div');
                wrapper.className = 'timeline-item small mb-2';
                wrapper.textContent = '[' + (index + 1) + '] ' + (roleText ? roleText.textContent : '-');
                timeline.appendChild(wrapper);

                if (index < arr.length - 1) {
                    var arrow = document.createElement('div');
                    arrow.className = 'text-center text-muted mb-2';
                    arrow.textContent = '↓';
                    timeline.appendChild(arrow);
                }
            });
        }

        function refreshOrderInputs(workflowId) {
            var list = document.querySelector('.workflow-steps-list[data-workflow-id="' + workflowId + '"]');
            if (!list) {
                return;
            }

            list.querySelectorAll('.workflow-step-card').forEach(function (card, index) {
                var order = index + 1;
                var form = card.querySelector('.js-step-form');
                var orderInput = card.querySelector('.js-step-order-input');
                var orderLabel = card.querySelector('.js-order-label');

                if (orderInput) {
                    orderInput.value = order;
                }

                if (orderLabel) {
                    orderLabel.textContent = order;
                }

                if (form) {
                    updateStepMeta(form);
                }
            });

            refreshTimeline(workflowId);
        }

        document.querySelectorAll('.js-step-form').forEach(function (form) {
            form.querySelectorAll('.js-step-type').forEach(function (input) {
                input.addEventListener('change', function () {
                    updateStepMeta(form);
                });
            });

            var orderInput = form.querySelector('.js-step-order-input');
            if (orderInput) {
                orderInput.addEventListener('input', function () {
                    updateStepMeta(form);
                });
            }

            form.addEventListener('submit', function () {
                updateStepMeta(form);
            });

            updateStepMeta(form);
        });

        document.querySelectorAll('.workflow-steps-list').forEach(function (list) {
            var workflowId = list.dataset.workflowId;
            refreshOrderInputs(workflowId);

            new Sortable(list, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                onEnd: function () {
                    refreshOrderInputs(workflowId);

                    var form = document.querySelector('.js-reorder-form[data-workflow-id="' + workflowId + '"]');
                    if (!form) {
                        return;
                    }

                    var orderedIds = Array.from(list.querySelectorAll('.workflow-step-card')).map(function (card) {
                        return card.dataset.stepId;
                    });

                    form.querySelector('input[name="ordered_ids"]').value = orderedIds.join(',');
                    form.submit();
                }
            });
        });

        document.querySelectorAll('.js-save-all-steps').forEach(function (button) {
            button.addEventListener('click', async function () {
                var workflowId = button.dataset.workflowId;
                var forms = Array.from(document.querySelectorAll('.js-step-form[data-workflow-id="' + workflowId + '"]'))
                    .filter(function (form) {
                        return form.getAttribute('method').toUpperCase() === 'POST' && form.action.includes('/workflow-steps/');
                    });

                if (forms.length === 0) {
                    return;
                }

                var original = button.textContent;
                button.disabled = true;
                button.textContent = labels.saving;

                try {
                    for (var i = 0; i < forms.length; i++) {
                        updateStepMeta(forms[i]);
                        var formData = new FormData(forms[i]);
                        await fetch(forms[i].action, {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });
                    }

                    window.location.reload();
                } catch (e) {
                    button.disabled = false;
                    button.textContent = original;
                }
            });
        });
    });
</script>
@endpush
