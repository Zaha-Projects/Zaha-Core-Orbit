@extends('layouts.app')

@php
    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';

    $t = [
        'page_title' => $isArabic ? 'منشئ سير الاعتماد' : 'Workflow Builder',
        'page_help' => $isArabic
            ? 'قم بتحديد مراحل الاعتماد بالترتيب. كل مرحلة تمثل جهة مسؤولة عن الموافقة.'
            : 'Define the approval steps in order. Each step represents a responsible role for approval.',
        'create_workflow' => $isArabic ? 'إنشاء سير اعتماد' : 'Create Workflow',
        'workflow_name' => $isArabic ? 'اسم سير الاعتماد' : 'Workflow Name',
        'workflow_module' => $isArabic ? 'الوحدة' : 'Module',
        'workflow_code' => $isArabic ? 'رمز سير الاعتماد' : 'Workflow Code',
        'name_ar' => $isArabic ? 'الاسم بالعربية' : 'Name (Arabic)',
        'name_en' => $isArabic ? 'الاسم بالإنجليزية' : 'Name (English)',
        'save' => $isArabic ? 'حفظ' : 'Save',
        'create' => $isArabic ? 'إنشاء' : 'Create',
        'delete_workflow' => $isArabic ? 'حذف سير الاعتماد' : 'Delete Workflow',
        'steps' => $isArabic ? 'مراحل الاعتماد' : 'Approval Steps',
        'add_step' => $isArabic ? 'إضافة مرحلة' : 'Add Step',
        'approval_level' => $isArabic ? 'مستوى الاعتماد' : 'Approval Level',
        'order' => $isArabic ? 'الترتيب' : 'Order',
        'step_name_ar' => $isArabic ? 'اسم المرحلة (AR)' : 'Step Name (AR)',
        'step_name_en' => $isArabic ? 'اسم المرحلة (EN)' : 'Step Name (EN)',
        'approval_stage' => $isArabic ? 'نوع المرحلة' : 'Approval Stage',
        'preliminary_approval' => $isArabic ? 'موافقة مبدئية' : 'Preliminary Approval',
        'final_approval' => $isArabic ? 'موافقة نهائية' : 'Final Approval',
        'role' => $isArabic ? 'الدور المسؤول' : 'Responsible Role',
        'permission' => $isArabic ? 'الصلاحية المطلوبة' : 'Required Permission',
        'choose_role' => $isArabic ? 'اختر الدور' : 'Select role',
        'choose_permission' => $isArabic ? 'اختر الصلاحية' : 'Select permission',
        'drag_hint' => $isArabic ? 'اسحب وأفلت لإعادة ترتيب المراحل.' : 'Drag and drop to reorder steps.',
        'delete_step' => $isArabic ? 'حذف المرحلة' : 'Delete Step',
        'order_updated' => $isArabic ? 'تم تحديث الترتيب.' : 'Order updated.',
    ];

    $translateRole = static function ($role) use ($isArabic) {
        if (!$role) {
            return null;
        }

        $key = 'app.acl.roles.' . $role->name;

        return $isArabic
            ? __($key, [], 'ar')
            : __($key, [], 'en');
    };

    $translatePermission = static function ($permission) use ($isArabic) {
        if (!$permission) {
            return null;
        }

        $key = 'app.acl.permissions.' . str_replace('.', '_', $permission->name);

        return $isArabic
            ? ($permission->name_ar ?: __($key, [], 'ar'))
            : ($permission->name_en ?: __($key, [], 'en'));
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
                        <input class="form-control" name="name_en" placeholder="{{ $isArabic ? 'Example: Monthly Plan Approval' : 'Example: Monthly Plan Approval' }}">
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
                    <div class="col-md-2 d-flex align-items-end"><button class="btn btn-outline-primary btn-sm w-100">{{ $t['save'] }}</button></div>
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

                <form method="POST" action="{{ route('role.super_admin.workflow_steps.reorder', $workflow) }}" class="js-reorder-form mb-3">
                    @csrf
                    <input type="hidden" name="ordered_ids" value="">
                </form>

                <div class="workflow-steps-list" data-workflow-id="{{ $workflow->id }}">
                    @foreach($workflow->steps as $step)
                        @php
                            $stageLabel = $step->step_type === 'main' ? $t['final_approval'] : $t['preliminary_approval'];
                            $roleLabel = $translateRole($step->role) ?: ($step->role?->name ?? '-');
                            $permissionLabel = $translatePermission($step->permission) ?: ($step->permission?->name ?? '-');
                        @endphp
                        <div class="border rounded p-3 mb-2 workflow-step-card" data-step-id="{{ $step->id }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>{{ $stageLabel }}</strong>
                                    <span class="text-muted ms-2">{{ $t['order'] }}: <span class="js-order-label">{{ $step->step_order }}</span></span>
                                </div>
                                <span class="badge bg-light text-dark">⇅</span>
                            </div>

                            <div class="small text-muted mb-2">
                                {{ $t['role'] }}: <strong>{{ $roleLabel }}</strong> · {{ $t['permission'] }}: <strong>{{ $permissionLabel }}</strong>
                            </div>

                            <form method="POST" action="{{ route('role.super_admin.workflow_steps.update', $step) }}" class="row g-2 js-step-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="step_key" value="{{ $step->step_key }}" class="js-step-key">
                                <input type="hidden" name="step_order" value="{{ $step->step_order }}" class="js-step-order-input">
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm">{{ $t['approval_stage'] }}</label>
                                    <select class="form-select form-select-sm js-step-type" name="step_type">
                                        <option value="sub" @selected($step->step_type==='sub')>{{ $t['preliminary_approval'] }}</option>
                                        <option value="main" @selected($step->step_type==='main')>{{ $t['final_approval'] }}</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label form-label-sm">{{ $t['approval_level'] }}</label>
                                    <input class="form-control form-control-sm" name="approval_level" type="number" min="1" value="{{ $step->approval_level }}" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm">{{ $t['role'] }}</label>
                                    <select class="form-select form-select-sm" name="role_id">
                                        <option value="">{{ $t['choose_role'] }}</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}" @selected($step->role_id===$role->id)>{{ $translateRole($role) ?: $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label form-label-sm">{{ $t['permission'] }}</label>
                                    <select class="form-select form-select-sm" name="permission_id">
                                        <option value="">{{ $t['choose_permission'] }}</option>
                                        @foreach($permissions as $permission)
                                            <option value="{{ $permission->id }}" @selected($step->permission_id===$permission->id)>{{ $translatePermission($permission) ?: $permission->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm">{{ $t['step_name_ar'] }}</label>
                                    <input class="form-control form-control-sm" name="name_ar" value="{{ $step->name_ar }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm">{{ $t['step_name_en'] }}</label>
                                    <input class="form-control form-control-sm" name="name_en" value="{{ $step->name_en }}">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button class="btn btn-outline-primary btn-sm w-100">{{ $t['save'] }}</button>
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

                <div class="border rounded p-3 mt-3 bg-light-subtle">
                    <h4 class="h6">{{ $t['add_step'] }}</h4>
                    <form method="POST" action="{{ route('role.super_admin.workflow_steps.store', $workflow) }}" class="row g-2 js-step-form">
                        @csrf
                        <input type="hidden" name="step_key" class="js-step-key" value="">
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">{{ $t['order'] }}</label>
                            <input class="form-control form-control-sm js-step-order-input" name="step_order" type="number" min="1" value="{{ max(1, ((int) $workflow->steps->max('step_order')) + 1) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">{{ $t['approval_level'] }}</label>
                            <input class="form-control form-control-sm" name="approval_level" type="number" min="1" placeholder="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ $t['approval_stage'] }}</label>
                            <select class="form-select form-select-sm js-step-type" name="step_type">
                                <option value="sub">{{ $t['preliminary_approval'] }}</option>
                                <option value="main">{{ $t['final_approval'] }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">{{ $t['role'] }}</label>
                            <select class="form-select form-select-sm" name="role_id">
                                <option value="">{{ $t['choose_role'] }}</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}">{{ $translateRole($role) ?: $role->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ $t['permission'] }}</label>
                            <select class="form-select form-select-sm" name="permission_id">
                                <option value="">{{ $t['choose_permission'] }}</option>
                                @foreach($permissions as $permission)
                                    <option value="{{ $permission->id }}">{{ $translatePermission($permission) ?: $permission->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ $t['step_name_ar'] }}</label>
                            <input class="form-control form-control-sm" name="name_ar">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">{{ $t['step_name_en'] }}</label>
                            <input class="form-control form-control-sm" name="name_en">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button class="btn btn-success btn-sm w-100">{{ $t['add_step'] }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var lists = document.querySelectorAll('.workflow-steps-list');

        function refreshOrderInputs(list) {
            list.querySelectorAll('.workflow-step-card').forEach(function (card, index) {
                var order = index + 1;
                var orderInput = card.querySelector('.js-step-order-input');
                var orderLabel = card.querySelector('.js-order-label');

                if (orderInput) {
                    orderInput.value = order;
                }

                if (orderLabel) {
                    orderLabel.textContent = order;
                }
            });
        }

        function updateStepKey(form) {
            var typeInput = form.querySelector('.js-step-type');
            var orderInput = form.querySelector('.js-step-order-input');
            var stepKeyInput = form.querySelector('.js-step-key');

            if (!typeInput || !orderInput || !stepKeyInput) {
                return;
            }

            var stepType = typeInput.value === 'main' ? 'main' : 'sub';
            stepKeyInput.value = stepType + '_approval_' + orderInput.value;
        }

        document.querySelectorAll('.js-step-form').forEach(function (form) {
            var typeInput = form.querySelector('.js-step-type');
            var orderInput = form.querySelector('.js-step-order-input');

            if (typeInput) {
                typeInput.addEventListener('change', function () {
                    updateStepKey(form);
                });
            }

            if (orderInput) {
                orderInput.addEventListener('input', function () {
                    updateStepKey(form);
                });
            }

            form.addEventListener('submit', function () {
                updateStepKey(form);
            });

            updateStepKey(form);
        });

        lists.forEach(function (list) {
            refreshOrderInputs(list);

            new Sortable(list, {
                animation: 150,
                ghostClass: 'bg-light',
                onEnd: function () {
                    refreshOrderInputs(list);

                    var form = list.previousElementSibling;
                    if (!form || !form.classList.contains('js-reorder-form')) {
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
    });
</script>
@endpush
