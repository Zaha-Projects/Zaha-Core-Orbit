@extends('layouts.app')


@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/workflow-ui.css') }}">
@endpush

@php
    $moduleMeta = [
        'agenda' => [
            'title' => 'الأجندة السنوية',
            'description' => 'هذا المسار يحدد كيف تُنشأ الأجندة ومن يراجعها ثم من يعتمدها نهائيًا.',
        ],
        'monthly_activities' => [
            'title' => 'الخطة الشهرية',
            'description' => 'هذا المسار يحدد تسلسل اعتماد الخطة الشهرية، بما في ذلك الخطوات التي تظهر فقط عند الحاجة.',
        ],
    ];

    $stepTypeMeta = [
        'sub' => [
            'label' => 'بداية المسار',
            'note' => 'هذه الخطوة تبدأ منها المعاملة، وغالبًا يمكن إرجاع الطلب إليها للتعديل إذا كانت قابلة للتعديل.',
        ],
        'main' => [
            'label' => 'اعتماد أو مراجعة',
            'note' => 'هذه خطوة مراجعة أو اعتماد داخل المسار بعد الإرسال الأول.',
        ],
    ];

    $conditionMeta = [
        'requires_programs' => 'عند الحاجة لاعتماد البرامج',
        'requires_workshops' => 'عند الحاجة لاعتماد المشاغل',
        'requires_communications' => 'عند الحاجة لاعتماد قسم الاتصال',
    ];

    $workflowTitle = function ($workflow) use ($moduleMeta) {
        return $moduleMeta[$workflow->module]['title'] ?? ($workflow->name_ar ?: $workflow->name_en ?: $workflow->code);
    };

    $workflowDescription = function ($workflow) use ($moduleMeta) {
        return $moduleMeta[$workflow->module]['description'] ?? 'مسار اعتماد قابل للتخصيص حسب ترتيب الخطوات والأدوار.';
    };

    $stepTitle = function ($step) {
        return $step->name_ar ?: $step->name_en ?: 'خطوة بدون اسم';
    };

    $stepRole = function ($step) {
        return $step->role?->display_name ?: 'لم يتم تحديد الدور بعد';
    };

    $conditionTitle = function ($step) use ($conditionMeta) {
        if (! $step->hasCondition()) {
            return 'خطوة دائمة';
        }

        return $conditionMeta[$step->condition_field] ?? 'شرط مخصص';
    };

    $conditionText = function ($step) use ($conditionMeta) {
        if (! $step->hasCondition()) {
            return 'تظهر هذه الخطوة دائمًا داخل المسار، ولا تعتمد على أي اختيار إضافي.';
        }

        $label = $conditionMeta[$step->condition_field] ?? $step->condition_field;

        if ((string) ($step->condition_value ?? '1') === '1') {
            return 'تظهر هذه الخطوة فقط ' . $label . '.';
        }

        return 'تظهر هذه الخطوة فقط عندما تكون قيمة الشرط "' . $label . '" مساوية لـ ' . $step->condition_value . '.';
    };
@endphp

@section('content')
<div class="workflow-ui wf-admin-workflows">
    <div class="wf-card wf-hero-card card mb-4">
        <div class="card-body p-4 p-xl-5">
            <div class="wf-hero-top">
                <div class="flex-grow-1">
                    <div class="wf-eyebrow">إدارة المسارات</div>
                    <h1 class="wf-page-title">بناء مسارات الاعتماد</h1>
                    <p class="wf-hero-text mb-0">
                        رتّب خطوات كل مسار كما ستظهر فعليًا للإدارات. البداية واضحة، الاعتمادات واضحة، والخطوات المشروطة تظهر بشكل منفصل وواضح.
                    </p>
                </div>

                <div class="wf-summary-stats">
                    <div class="wf-stat-card">
                        <div class="wf-stat-label">المسارات المفعلة</div>
                        <div class="wf-stat-value">{{ $workflows->where('is_active', true)->count() }}</div>
                    </div>
                    <div class="wf-stat-card">
                        <div class="wf-stat-label">إجمالي المسارات</div>
                        <div class="wf-stat-value">{{ $workflows->count() }}</div>
                    </div>
                    <div class="wf-stat-card">
                        <div class="wf-stat-label">إجمالي الخطوات</div>
                        <div class="wf-stat-value">{{ $workflows->sum(fn ($workflow) => $workflow->steps->count()) }}</div>
                    </div>
                    <div class="wf-stat-card">
                        <div class="wf-stat-label">الأدوار المتاحة</div>
                        <div class="wf-stat-value">{{ $roles->count() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <details class="wf-card card mb-4">
        <summary class="wf-details-toggle">
            <div>
                <div class="wf-section-title">إضافة مسار جديد</div>
                <span class="wf-help">أنشئ المسار أولًا، ثم أضف له الخطوات من القسم الخاص به.</span>
            </div>
            <span class="wf-toggle-label">فتح النموذج</span>
        </summary>

        <div class="card-body pt-0 px-4 pb-4 px-xl-5">
            <form method="POST" action="{{ route('role.super_admin.workflows.store') }}" class="wf-grid">
                @csrf

                <div class="wf-col-3">
                    <label class="form-label">نوع المسار</label>
                    <select class="form-select" name="module" required>
                        <option value="">اختر نوع المسار</option>
                        <option value="agenda" @selected(old('module') === 'agenda')>الأجندة السنوية</option>
                        <option value="monthly_activities" @selected(old('module') === 'monthly_activities')>الخطة الشهرية</option>
                    </select>
                </div>

                <div class="wf-col-3">
                    <label class="form-label">الاسم بالعربية</label>
                    <input class="form-control" name="name_ar" value="{{ old('name_ar') }}" placeholder="مثال: مسار اعتماد الأجندة">
                </div>

                <div class="wf-col-3">
                    <label class="form-label">الاسم بالإنجليزية</label>
                    <input class="form-control" name="name_en" value="{{ old('name_en') }}" placeholder="Example: Agenda Approval Flow">
                </div>

                <div class="wf-col-3">
                    <label class="form-label">رمز المسار</label>
                    <input class="form-control" name="code" value="{{ old('code') }}" placeholder="agenda_default" required>
                </div>

                <div class="wf-col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="new-workflow-active" @checked(old('is_active'))>
                        <label class="form-check-label" for="new-workflow-active">تفعيل المسار مباشرة بعد الحفظ</label>
                    </div>

                    <button class="btn btn-primary">إضافة المسار</button>
                </div>
            </form>
        </div>
    </details>

    @forelse($workflows as $workflow)
        @php
            $orderedSteps = $workflow->steps
                ->sortBy([['step_order', 'asc'], ['approval_level', 'asc']])
                ->values();
            $conditionalStepsCount = $orderedSteps->filter(fn ($step) => $step->hasCondition())->count();
        @endphp

        <div class="wf-card card mb-4">
            <div class="card-body p-4 p-xl-5">
                <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-4">
                    <div>
                        <div class="wf-chip-row mb-3">
                            <span class="wf-chip wf-chip-primary">{{ $workflowTitle($workflow) }}</span>
                            <span class="wf-badge {{ $workflow->is_active ? 'wf-badge-success' : 'wf-badge-muted' }}">
                                {{ $workflow->is_active ? 'مفعل' : 'غير مفعل' }}
                            </span>
                        </div>

                        <h2 class="wf-workflow-title mb-2">{{ $workflow->name_ar ?: $workflow->name_en ?: $workflow->code }}</h2>
                        <p class="wf-section-subtitle mb-0">{{ $workflowDescription($workflow) }}</p>
                    </div>

                    <div class="wf-chip-row">
                        <span class="wf-chip">عدد الخطوات: {{ $orderedSteps->count() }}</span>
                        <span class="wf-chip">الخطوات المشروطة: {{ $conditionalStepsCount }}</span>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="wf-panel mb-4">
                            <div class="wf-panel-head">
                                <div>
                                    <div class="wf-section-title">ترتيب المسار</div>
                                    <div class="wf-section-subtitle">هذا هو التسلسل الفعلي الذي سيمر فيه الطلب من أول خطوة حتى آخر اعتماد.</div>
                                </div>
                            </div>

                            @if($orderedSteps->isEmpty())
                                <div class="wf-empty-state">لا توجد خطوات مضافة بعد لهذا المسار.</div>
                            @else
                                <div class="wf-roadmap">
                                    @foreach($orderedSteps as $index => $step)
                                        <div class="wf-roadmap-item">
                                            <div class="wf-roadmap-index">{{ $index + 1 }}</div>

                                            <div class="wf-roadmap-body">
                                                <div class="wf-roadmap-head">
                                                    <div>
                                                        <div class="wf-roadmap-title">{{ $stepTitle($step) }}</div>
                                                        <div class="wf-roadmap-role">{{ $stepRole($step) }}</div>
                                                    </div>

                                                    <div class="wf-chip-row">
                                                        <span class="wf-chip wf-chip-primary">{{ $stepTypeMeta[$step->step_type]['label'] ?? 'خطوة' }}</span>
                                                        @if($step->is_editable)
                                                            <span class="wf-chip wf-chip-soft">يمكن إرجاعها للتعديل</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="wf-condition-box {{ $step->hasCondition() ? 'is-conditional' : 'is-always' }} mt-3">
                                                    <div class="wf-condition-label">{{ $conditionTitle($step) }}</div>
                                                    <div class="wf-condition-text">{{ $conditionText($step) }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="wf-panel mb-4">
                            <div class="wf-panel-head">
                                <div>
                                    <div class="wf-section-title">إعدادات المسار</div>
                                    <div class="wf-section-subtitle">عدّل اسم المسار وحالته، واترك الإعدادات التقنية داخل القسم المتقدم فقط.</div>
                                </div>
                            </div>                            <form method="POST" action="{{ route('role.super_admin.workflows.update', $workflow) }}" class="wf-grid">
                                @csrf
                                @method('PUT')

                                <div class="wf-col-6">
                                    <label class="form-label">الاسم بالعربية</label>
                                    <input class="form-control" name="name_ar" value="{{ $workflow->name_ar }}">
                                </div>

                                <div class="wf-col-6">
                                    <label class="form-label">الاسم بالإنجليزية</label>
                                    <input class="form-control" name="name_en" value="{{ $workflow->name_en }}">
                                </div>

                                <div class="wf-col-12">
                                    <details class="wf-advanced-box">
                                        <summary>إعدادات متقدمة</summary>

                                        <div class="wf-grid mt-3">
                                            <div class="wf-col-6">
                                                <label class="form-label">نوع المسار داخل النظام</label>
                                                <input class="form-control" name="module" value="{{ $workflow->module }}" required>
                                            </div>

                                            <div class="wf-col-6">
                                                <label class="form-label">رمز المسار</label>
                                                <input class="form-control" name="code" value="{{ $workflow->code }}" required>
                                            </div>
                                        </div>
                                    </details>
                                </div>

                                <div class="wf-col-12 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="workflow-active-{{ $workflow->id }}" {{ $workflow->is_active ? 'checked' : '' }}>
                                        <label class="form-check-label" for="workflow-active-{{ $workflow->id }}">تفعيل هذا المسار</label>
                                    </div>

                                    <button class="btn btn-outline-primary">حفظ إعدادات المسار</button>
                                </div>
                            </form>
                        </div>

                        <div class="wf-panel mb-4">
                            <div class="wf-panel-head">
                                <div>
                                    <div class="wf-section-title">تحرير الخطوات</div>
                                    <div class="wf-section-subtitle">كل خطوة لها قسم واضح: الاسم، الدور، الترتيب، ثم إعداد ظهور الخطوة.</div>
                                </div>
                            </div>

                            @if($orderedSteps->isEmpty())
                                <div class="wf-empty-state">أضف أول خطوة من القسم التالي ليظهر لك ترتيب المسار هنا.</div>
                            @else
                                <div class="d-flex flex-column gap-3">
                                    @foreach($orderedSteps as $index => $step)
                                        <div class="wf-step-card">
                                            <div class="wf-step-card-head">
                                                <div>
                                                    <div class="wf-chip-row mb-2">
                                                        <span class="wf-chip wf-chip-primary">الخطوة {{ $index + 1 }}</span>
                                                        <span class="wf-chip">{{ $stepTypeMeta[$step->step_type]['label'] ?? 'خطوة' }}</span>
                                                        @if($step->is_editable)
                                                            <span class="wf-chip wf-chip-soft">تعود للتعديل عند الحاجة</span>
                                                        @endif
                                                    </div>

                                                    <div class="wf-step-title">{{ $stepTitle($step) }}</div>
                                                    <div class="wf-step-subtitle">{{ $stepRole($step) }}</div>
                                                </div>

                                                <div class="wf-chip-row">
                                                    <span class="wf-chip">الترتيب {{ $step->step_order }}</span>
                                                    <span class="wf-chip">المستوى {{ $step->approval_level }}</span>
                                                </div>
                                            </div>

                                            <div class="wf-condition-box {{ $step->hasCondition() ? 'is-conditional' : 'is-always' }} mt-3 mb-3">
                                                <div class="wf-condition-label">{{ $step->hasCondition() ? 'تشغيل الخطوة' : 'حالة الخطوة' }}</div>
                                                <div class="wf-chip-row mb-2">
                                                    <span class="wf-badge {{ $step->hasCondition() ? 'wf-badge-warning' : 'wf-badge-success' }}">
                                                        {{ $step->hasCondition() ? 'خطوة مشروطة' : 'خطوة دائمة' }}
                                                    </span>
                                                </div>
                                                <div class="wf-condition-text">{{ $conditionText($step) }}</div>
                                            </div>

                                            <form method="POST" action="{{ route('role.super_admin.workflow_steps.update', $step) }}" class="wf-grid">
                                                @csrf
                                                @method('PUT')

                                                <div class="wf-col-6">
                                                    <label class="form-label">اسم الخطوة بالعربية</label>
                                                    <input class="form-control" name="name_ar" value="{{ $step->name_ar }}" placeholder="مثال: اعتماد مدير علاقات رئيسي">
                                                </div>

                                                <div class="wf-col-6">
                                                    <label class="form-label">اسم الخطوة بالإنجليزية</label>
                                                    <input class="form-control" name="name_en" value="{{ $step->name_en }}" placeholder="Example: Relations Manager Approval">
                                                </div>

                                                <div class="wf-col-6">
                                                    <label class="form-label">الدور المسؤول</label>
                                                    <select class="form-select" name="role_id" required>
                                                        <option value="">اختر الدور</option>
                                                        @foreach($roles as $role)
                                                            <option value="{{ $role->id }}" @selected((int) $step->role_id === (int) $role->id)>{{ $role->display_name ?? $role->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="wf-col-3">
                                                    <label class="form-label">نوع الخطوة</label>
                                                    <select class="form-select" name="step_type" required>
                                                        <option value="sub" @selected($step->step_type === 'sub')>بداية المسار</option>
                                                        <option value="main" @selected($step->step_type === 'main')>اعتماد أو مراجعة</option>
                                                    </select>
                                                </div>

                                                <div class="wf-col-3">
                                                    <label class="form-label">الترتيب داخل المسار</label>
                                                    <input class="form-control" type="number" min="1" name="step_order" value="{{ $step->step_order }}" required>
                                                </div>

                                                <div class="wf-col-3">
                                                    <label class="form-label">مستوى الاعتماد</label>
                                                    <input class="form-control" type="number" min="1" name="approval_level" value="{{ $step->approval_level }}" required>
                                                </div>

                                                <div class="wf-col-9">
                                                    <div class="wf-condition-editor {{ $step->hasCondition() ? 'is-conditional' : 'is-always' }}">
                                                        <div class="wf-condition-editor-title mb-2">إعداد ظهور الخطوة</div>
                                                        <div class="wf-condition-text mb-3">
                                                            اختر "تظهر دائمًا" إذا كانت كل المعاملات تمر من هذه الخطوة. أو اختر القسم المطلوب إذا كانت الخطوة تظهر فقط عند الحاجة.
                                                        </div>

                                                        <div class="wf-grid">
                                                            <div class="wf-col-7">
                                                                <label class="form-label">متى تظهر هذه الخطوة؟</label>
                                                                <select class="form-select" name="condition_field">
                                                                    <option value="">تظهر دائمًا</option>
                                                                    @foreach($conditionMeta as $field => $label)
                                                                        <option value="{{ $field }}" @selected($step->condition_field === $field)>{{ $label }}</option>
                                                                    @endforeach
                                                                    @if($step->condition_field && !array_key_exists($step->condition_field, $conditionMeta))
                                                                        <option value="{{ $step->condition_field }}" selected>{{ $step->condition_field }} (مخصص)</option>
                                                                    @endif
                                                                </select>
                                                            </div>

                                                            <div class="wf-col-5">
                                                                <label class="form-label">قيمة التفعيل</label>
                                                                <select class="form-select" name="condition_value">
                                                                    <option value="1" @selected((string) ($step->condition_value ?? '1') === '1')>مفعّل</option>
                                                                    <option value="0" @selected((string) ($step->condition_value ?? '1') === '0')>غير مفعّل</option>
                                                                    @if(filled($step->condition_value) && !in_array((string) $step->condition_value, ['0', '1'], true))
                                                                        <option value="{{ $step->condition_value }}" selected>{{ $step->condition_value }} (مخصص)</option>
                                                                    @endif
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="wf-col-12">
                                                    <details class="wf-advanced-box">
                                                        <summary>إعدادات متقدمة لهذه الخطوة</summary>

                                                        <div class="wf-grid mt-3">
                                                            <div class="wf-col-6">
                                                                <label class="form-label">المعرف الداخلي للخطوة</label>
                                                                <input class="form-control" name="step_key" value="{{ $step->step_key }}" required>
                                                            </div>

                                                            <div class="wf-col-6 d-flex align-items-end">
                                                                <div class="form-check form-switch mb-2">
                                                                    <input class="form-check-input" type="checkbox" name="is_editable" value="1" id="editable-step-{{ $step->id }}" {{ $step->is_editable ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="editable-step-{{ $step->id }}">يمكن إرجاع الطلب لهذه الخطوة للتعديل</label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </details>
                                                </div>

                                                <div class="wf-col-12 d-flex justify-content-end">
                                                    <button class="btn btn-outline-primary">حفظ الخطوة</button>
                                                </div>
                                            </form>

                                            <form method="POST" action="{{ route('role.super_admin.workflow_steps.destroy', $step) }}" class="mt-3 d-flex justify-content-end" onsubmit="return confirm('هل أنت متأكد من حذف هذه الخطوة؟')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm">حذف الخطوة</button>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="wf-panel">
                            <div class="wf-panel-head">
                                <div>
                                    <div class="wf-section-title">إضافة خطوة جديدة</div>
                                    <div class="wf-section-subtitle">أضف الخطوة الجديدة في مكانها الصحيح، وحدد إن كانت دائمة أو مشروطة.</div>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('role.super_admin.workflow_steps.store', $workflow) }}" class="wf-grid">
                                @csrf

                                <div class="wf-col-6">
                                    <label class="form-label">اسم الخطوة بالعربية</label>
                                    <input class="form-control" name="name_ar" placeholder="مثال: اعتماد المدير التنفيذي">
                                </div>

                                <div class="wf-col-6">
                                    <label class="form-label">اسم الخطوة بالإنجليزية</label>
                                    <input class="form-control" name="name_en" placeholder="Example: Executive Approval">
                                </div>

                                <div class="wf-col-6">
                                    <label class="form-label">الدور المسؤول</label>
                                    <select class="form-select" name="role_id" required>
                                        <option value="">اختر الدور</option>
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="wf-col-3">
                                    <label class="form-label">نوع الخطوة</label>
                                    <select class="form-select" name="step_type" required>
                                        <option value="sub">بداية المسار</option>
                                        <option value="main">اعتماد أو مراجعة</option>
                                    </select>
                                </div>

                                <div class="wf-col-3">
                                    <label class="form-label">الترتيب داخل المسار</label>
                                    <input class="form-control" type="number" min="1" name="step_order" required>
                                </div>

                                <div class="wf-col-3">
                                    <label class="form-label">مستوى الاعتماد</label>
                                    <input class="form-control" type="number" min="1" name="approval_level" required>
                                </div>

                                <div class="wf-col-9">
                                    <div class="wf-condition-editor is-always">
                                        <div class="wf-condition-editor-title mb-2">إعداد ظهور الخطوة</div>
                                        <div class="wf-condition-text mb-3">
                                            إذا كانت هذه الخطوة مطلوبة دائمًا، اتركها على الخيار الأول. وإذا كانت تظهر فقط عند طلب قسم معيّن، اختره من القائمة.
                                        </div>

                                        <div class="wf-grid">
                                            <div class="wf-col-7">
                                                <label class="form-label">متى تظهر هذه الخطوة؟</label>
                                                <select class="form-select" name="condition_field">
                                                    <option value="">تظهر دائمًا</option>
                                                    @foreach($conditionMeta as $field => $label)
                                                        <option value="{{ $field }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="wf-col-5">
                                                <label class="form-label">قيمة التفعيل</label>
                                                <select class="form-select" name="condition_value">
                                                    <option value="1">مفعّل</option>
                                                    <option value="0">غير مفعّل</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="wf-col-12">
                                    <details class="wf-advanced-box">
                                        <summary>إعدادات متقدمة لهذه الخطوة</summary>

                                        <div class="wf-grid mt-3">
                                            <div class="wf-col-6">
                                                <label class="form-label">المعرف الداخلي للخطوة</label>
                                                <input class="form-control" name="step_key" placeholder="monthly_relations_manager_review" required>
                                            </div>

                                            <div class="wf-col-6 d-flex align-items-end">
                                                <div class="form-check form-switch mb-2">
                                                    <input class="form-check-input" type="checkbox" name="is_editable" value="1" id="new-editable-step-{{ $workflow->id }}" checked>
                                                    <label class="form-check-label" for="new-editable-step-{{ $workflow->id }}">يمكن إرجاع الطلب لهذه الخطوة للتعديل</label>
                                                </div>
                                            </div>
                                        </div>
                                    </details>
                                </div>

                                <div class="wf-col-12 d-flex justify-content-end">
                                    <button class="btn btn-primary">إضافة الخطوة</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="wf-panel wf-panel-soft mb-4">
                            <div class="wf-section-title mb-3">معاني البطاقات</div>

                            <div class="wf-example-stack">
                                <div class="wf-example-card is-always">
                                    <div class="wf-chip-row mb-2">
                                        <span class="wf-badge wf-badge-success">خطوة دائمة</span>
                                    </div>
                                    <div class="wf-example-text">تعني أن كل معاملة تمر من هذه الخطوة بدون أي شرط إضافي.</div>
                                </div>

                                <div class="wf-example-card is-conditional">
                                    <div class="wf-chip-row mb-2">
                                        <span class="wf-badge wf-badge-warning">خطوة مشروطة</span>
                                    </div>
                                    <div class="wf-example-text">تعني أن الخطوة لا تظهر إلا إذا تم تفعيل القسم أو الحاجة المرتبطة بها.</div>
                                </div>

                                <div class="wf-example-card is-always">
                                    <div class="wf-chip-row mb-2">
                                        <span class="wf-chip wf-chip-soft">يمكن إرجاعها للتعديل</span>
                                    </div>
                                    <div class="wf-example-text">إذا طُلب تعديل، يمكن إعادة الطلب لهذه الخطوة بدل إيقاف المسار بالكامل.</div>
                                </div>
                            </div>
                        </div>

                        <div class="wf-panel wf-panel-soft mb-4">
                            <div class="wf-section-title mb-3">الخطوات المشروطة في هذا المسار</div>

                            @if($conditionalStepsCount === 0)
                                <div class="wf-empty-state">لا توجد خطوات مشروطة في هذا المسار حاليًا.</div>
                            @else
                                <div class="wf-example-stack">
                                    @foreach($orderedSteps->filter(fn ($step) => $step->hasCondition()) as $step)
                                        <div class="wf-example-card is-conditional">
                                            <div class="wf-example-title mb-1">{{ $stepTitle($step) }}</div>
                                            <div class="wf-example-text">{{ $conditionTitle($step) }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="wf-panel mb-4">
                            <div class="wf-section-title mb-3">ملاحظات سريعة</div>
                            <ul class="wf-info-list">
                                <li>اجعل البداية خطوة واحدة فقط وواضحة حتى لا يتشتت مسار الطلب.</li>
                                <li>استخدم الترتيب والمستوى بشكل متسلسل حتى يبقى المسار مفهومًا للإدارة.</li>
                                <li>لا تجعل الخطوة مشروطة إلا إذا كانت مرتبطة فعلًا بخيار داخل الطلب.</li>
                            </ul>
                        </div>

                        <div class="wf-panel">
                            <div class="wf-section-title mb-2">حذف المسار</div>
                            <div class="wf-section-subtitle mb-3">احذف المسار فقط إذا كنت متأكدًا أنك لم تعد بحاجة إليه.</div>

                            <form method="POST" action="{{ route('role.super_admin.workflows.destroy', $workflow) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المسار بالكامل؟')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-outline-danger w-100">حذف المسار</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="wf-card card">
            <div class="card-body p-4">
                <div class="wf-empty-state">لا توجد مسارات بعد. ابدأ بإضافة أول مسار من الأعلى.</div>
            </div>
        </div>
    @endforelse
</div>
@endsection
