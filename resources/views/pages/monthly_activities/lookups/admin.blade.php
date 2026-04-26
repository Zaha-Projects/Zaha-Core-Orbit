@extends('layouts.app')


@php
    $moduleLabels = [
        'agenda' => 'حالات الأجندة',
        'monthly_activities' => 'حالات الخطة الشهرية',
    ];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/lookups-admin.css') }}">
@endpush

@section('content')
    <div class="event-module">
        <div class="card event-card mb-4">
            <div class="card-body">
                <h1 class="h4 mb-2">إدارة القوائم المرجعية</h1>
                <p class="text-muted mb-0">كل الخيارات التالية قابلة للإضافة والتعديل والتفعيل أو التعطيل من السوبر أدمن، وتُستخدم في الأجندة والخطة الشهرية.</p>
                <div class="lookup-kpis mt-3">
                    <span class="lookup-kpi">الأقسام: {{ $departments->count() }}</span>
                    <span class="lookup-kpi">الوحدات: {{ $departmentUnits->count() }}</span>
                    <span class="lookup-kpi">التصنيفات: {{ $eventCategories->count() }}</span>
                    <span class="lookup-kpi">الحالات: {{ $statusLookups->flatten(1)->count() }}</span>
                    <span class="lookup-kpi">أسئلة التقييم: {{ $evaluationQuestions->count() }}</span>
                </div>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

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

        <div class="row g-4">
            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                            <div>
                                <h2 class="h5 mb-1">الوحدات/الأقسام المالكة والشريكة</h2>
                                <p class="text-muted mb-0">تُستخدم هذه الخيارات في القسم المالك والشركاء الداخليين في الأجندة والفعاليات.</p>
                            </div>
                        </div>

                        <form method="POST" action="{{ route('role.super_admin.events_lookups.departments.store') }}" class="row g-2 mb-4 lookup-create-form">
                            @csrf
                            <div class="col-12 col-lg-4">
                                <input class="form-control" name="name" placeholder="اسم الوحدة/القسم" required>
                            </div>
                            <div class="col-6 col-lg-2">
                                <input class="form-control" type="number" min="0" name="sort_order" placeholder="الترتيب">
                            </div>
                            <div class="col-6 col-lg-2">
                                <input class="form-control" type="color" name="color_hex" value="#2563EB">
                            </div>
                            <div class="col-6 col-lg-2">
                                <input class="form-control" name="icon" placeholder="اختصار/أيقونة" maxlength="32">
                            </div>
                            <div class="col-6 col-lg-1 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="department_is_active" checked>
                                    <label class="form-check-label" for="department_is_active">مفعل</label>
                                </div>
                            </div>
                            <div class="col-12 col-lg-1">
                                <button class="btn btn-primary w-100" type="submit">إضافة</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>الترتيب</th>
                                        <th>اللون</th>
                                        <th>الأيقونة</th>
                                        <th>الحالة</th>
                                        <th class="text-end">حفظ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($departments as $department)
                                        <tr>
                                            <form method="POST" action="{{ route('role.super_admin.events_lookups.departments.update', $department) }}">
                                                @csrf
                                                @method('PUT')
                                                <td><input class="form-control" name="name" value="{{ $department->name }}" required></td>
                                                <td><input class="form-control" type="number" min="0" name="sort_order" value="{{ $department->sort_order ?? 0 }}"></td>
                                                <td><input class="form-control form-control-color" type="color" name="color_hex" value="{{ $department->color_hex ?? '#2563EB' }}"></td>
                                                <td><input class="form-control" name="icon" value="{{ $department->icon }}" maxlength="32"></td>
                                                <td>
                                                    <label class="form-check form-switch lookup-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $department->is_active ? 'checked' : '' }}>
                                                        <span class="form-check-label">{{ $department->is_active ? 'مفعّل' : 'غير مفعّل' }}</span>
                                                    </label>
                                                </td>
                                                <td class="text-end"><button class="btn btn-outline-primary btn-sm" type="submit">حفظ</button></td>
                                            </form>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">وحدات/أقسام الشركاء</h2>

                        <form method="POST" action="{{ route('role.super_admin.events_lookups.department_units.store') }}" class="row g-2 mb-4 lookup-create-form">
                            @csrf
                            <div class="col-12 col-lg-2">
                                <input class="form-control" name="unit_key" placeholder="المفتاح البرمجي" required>
                            </div>
                            <div class="col-12 col-lg-3">
                                <input class="form-control" name="name" placeholder="اسم الوحدة/القسم" required>
                            </div>
                            <div class="col-12 col-lg-2">
                                <input class="form-control" name="role_name" placeholder="الرول المرتبط">
                            </div>
                            <div class="col-6 col-lg-1">
                                <input class="form-control" type="number" min="0" name="sort_order" placeholder="ترتيب">
                            </div>
                            <div class="col-6 col-lg-1">
                                <input class="form-control" type="color" name="color_hex" value="#2563EB">
                            </div>
                            <div class="col-6 col-lg-1">
                                <input class="form-control" name="icon" placeholder="أيقونة" maxlength="32">
                            </div>
                            <div class="col-6 col-lg-1 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="unit_is_active" checked>
                                    <label class="form-check-label" for="unit_is_active">مفعل</label>
                                </div>
                            </div>
                            <div class="col-12 col-lg-1">
                                <button class="btn btn-primary w-100" type="submit">إضافة</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>المفتاح</th>
                                        <th>الرول</th>
                                        <th>الترتيب</th>
                                        <th>اللون</th>
                                        <th>الأيقونة</th>
                                        <th>الحالة</th>
                                        <th class="text-end">حفظ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($departmentUnits as $unit)
                                        <tr>
                                            <form method="POST" action="{{ route('role.super_admin.events_lookups.department_units.update', $unit) }}">
                                                @csrf
                                                @method('PUT')
                                                <td><input class="form-control" name="name" value="{{ $unit->name }}" required></td>
                                                <td><input class="form-control" name="unit_key" value="{{ $unit->unit_key }}" required></td>
                                                <td><input class="form-control" name="role_name" value="{{ $unit->role_name }}"></td>
                                                <td><input class="form-control" type="number" min="0" name="sort_order" value="{{ $unit->sort_order ?? 0 }}"></td>
                                                <td><input class="form-control form-control-color" type="color" name="color_hex" value="{{ $unit->color_hex ?? '#2563EB' }}"></td>
                                                <td><input class="form-control" name="icon" value="{{ $unit->icon }}" maxlength="32"></td>
                                                <td>
                                                    <label class="form-check form-switch lookup-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $unit->is_active ? 'checked' : '' }}>
                                                        <span class="form-check-label">{{ $unit->is_active ? 'مفعّل' : 'غير مفعّل' }}</span>
                                                    </label>
                                                </td>
                                                <td class="text-end"><button class="btn btn-outline-primary btn-sm" type="submit">حفظ</button></td>
                                            </form>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">التصنيفات وربطها بالقسم</h2>

                        <form method="POST" action="{{ route('role.super_admin.events_lookups.event_categories.store') }}" class="row g-2 mb-4 lookup-create-form">
                            @csrf
                            <div class="col-12 col-lg-4">
                                <input class="form-control" name="name" placeholder="اسم التصنيف" required>
                            </div>
                            <div class="col-12 col-lg-3">
                                <select class="form-select" name="department_id" required>
                                    <option value="">اختر القسم</option>
                                    @foreach ($departments as $department)
                                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6 col-lg-2">
                                <input class="form-control" type="number" min="0" name="sort_order" placeholder="الترتيب">
                            </div>
                            <div class="col-6 col-lg-2 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="active" value="1" id="category_active" checked>
                                    <label class="form-check-label" for="category_active">مفعل</label>
                                </div>
                            </div>
                            <div class="col-12 col-lg-1">
                                <button class="btn btn-primary w-100" type="submit">إضافة</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>التصنيف</th>
                                        <th>القسم المرتبط</th>
                                        <th>الترتيب</th>
                                        <th>الحالة</th>
                                        <th class="text-end">حفظ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($eventCategories as $category)
                                        <tr>
                                            <form method="POST" action="{{ route('role.super_admin.events_lookups.event_categories.update', $category) }}">
                                                @csrf
                                                @method('PUT')
                                                <td><input class="form-control" name="name" value="{{ $category->name }}" required></td>
                                                <td>
                                                    <select class="form-select" name="department_id" required>
                                                        @foreach ($departments as $department)
                                                            <option value="{{ $department->id }}" {{ (int) $category->department_id === (int) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input class="form-control" type="number" min="0" name="sort_order" value="{{ $category->sort_order ?? 0 }}"></td>
                                                <td>
                                                    <label class="form-check form-switch lookup-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="active" value="1" {{ $category->active ? 'checked' : '' }}>
                                                        <span class="form-check-label">{{ $category->active ? 'مفعّل' : 'غير مفعّل' }}</span>
                                                    </label>
                                                </td>
                                                <td class="text-end"><button class="btn btn-outline-primary btn-sm" type="submit">حفظ</button></td>
                                            </form>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">الفئات المستهدفة</h2>

                        <form method="POST" action="{{ route('role.super_admin.events_lookups.target_groups.store') }}" class="row g-2 mb-4 lookup-create-form">
                            @csrf
                            <div class="col-12 col-lg-5">
                                <input class="form-control" name="name" placeholder="اسم الفئة المستهدفة" required>
                            </div>
                            <div class="col-6 col-lg-2">
                                <input class="form-control" type="number" min="0" name="sort_order" placeholder="الترتيب">
                            </div>
                            <div class="col-6 col-lg-2 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_other" value="1" id="target_group_other">
                                    <label class="form-check-label" for="target_group_other">خيار أخرى</label>
                                </div>
                            </div>
                            <div class="col-12 col-lg-1">
                                <button class="btn btn-primary w-100" type="submit">إضافة</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>الاسم</th>
                                        <th>الترتيب</th>
                                        <th>خيار أخرى</th>
                                        <th>الحالة</th>
                                        <th class="text-end">حفظ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($targetGroups as $group)
                                        <tr>
                                            <form method="POST" action="{{ route('role.super_admin.events_lookups.target_groups.update', $group) }}">
                                                @csrf
                                                @method('PUT')
                                                <td><input class="form-control" name="name" value="{{ $group->name }}" required></td>
                                                <td><input class="form-control" type="number" min="0" name="sort_order" value="{{ $group->sort_order ?? 0 }}"></td>
                                                <td>
                                                    <label class="form-check form-switch lookup-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="is_other" value="1" {{ $group->is_other ? 'checked' : '' }}>
                                                        <span class="form-check-label">{{ $group->is_other ? 'نعم' : 'لا' }}</span>
                                                    </label>
                                                </td>
                                                <td>
                                                    <label class="form-check form-switch lookup-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $group->is_active ? 'checked' : '' }}>
                                                        <span class="form-check-label">{{ $group->is_active ? 'مفعّل' : 'غير مفعّل' }}</span>
                                                    </label>
                                                </td>
                                                <td class="text-end"><button class="btn btn-outline-primary btn-sm" type="submit">حفظ</button></td>
                                            </form>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">الحالات</h2>

                        <form method="POST" action="{{ route('role.super_admin.events_lookups.status_lookups.store') }}" class="row g-2 mb-4 lookup-create-form">
                            @csrf
                            <div class="col-12 col-lg-2">
                                <select class="form-select" name="module" required>
                                    <option value="">نوع الحالة</option>
                                    @foreach ($moduleLabels as $moduleKey => $moduleLabel)
                                        <option value="{{ $moduleKey }}">{{ $moduleLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 col-lg-2">
                                <input class="form-control" name="code" placeholder="code" required>
                            </div>
                            <div class="col-12 col-lg-4">
                                <input class="form-control" name="name" placeholder="اسم الحالة بالعربي" required>
                            </div>
                            <div class="col-6 col-lg-2">
                                <input class="form-control" type="number" min="0" name="sort_order" placeholder="الترتيب">
                            </div>
                            <div class="col-6 col-lg-1 d-flex align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="status_lookup_active" checked>
                                    <label class="form-check-label" for="status_lookup_active">مفعل</label>
                                </div>
                            </div>
                            <div class="col-12 col-lg-1">
                                <button class="btn btn-primary w-100" type="submit">إضافة</button>
                            </div>
                        </form>

                        <div class="row g-3">
                            @foreach ($moduleLabels as $moduleKey => $moduleLabel)
                                <div class="col-12 col-xl-6">
                                    <div class="border rounded-3 p-3 h-100">
                                        <h3 class="h6 mb-3">{{ $moduleLabel }}</h3>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>الكود</th>
                                                        <th>الاسم</th>
                                                        <th>الترتيب</th>
                                                        <th>الحالة</th>
                                                        <th class="text-end">حفظ</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @forelse (($statusLookups[$moduleKey] ?? collect()) as $statusLookup)
                                                        <tr>
                                                            <form method="POST" action="{{ route('role.super_admin.events_lookups.status_lookups.update', $statusLookup) }}">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="module" value="{{ $statusLookup->module }}">
                                                                <td><input class="form-control" name="code" value="{{ $statusLookup->code }}" required></td>
                                                                <td><input class="form-control" name="name" value="{{ $statusLookup->name }}" required></td>
                                                                <td><input class="form-control" type="number" min="0" name="sort_order" value="{{ $statusLookup->sort_order ?? 0 }}"></td>
                                                                <td>
                                                                    <label class="form-check form-switch lookup-switch mb-0">
                                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $statusLookup->is_active ? 'checked' : '' }}>
                                                                        <span class="form-check-label">{{ $statusLookup->is_active ? 'مفعّل' : 'غير مفعّل' }}</span>
                                                                    </label>
                                                                </td>
                                                                <td class="text-end"><button class="btn btn-outline-primary btn-sm" type="submit">حفظ</button></td>
                                                            </form>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="5" class="text-muted">لا توجد حالات مضافة بعد.</td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card event-card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">أسئلة التقييم</h2>

                        <form method="POST" action="{{ route('role.super_admin.events_lookups.evaluation_questions.store') }}" class="row g-2 mb-4 lookup-create-form">
                            @csrf
                            <div class="col-12 col-lg-6">
                                <input class="form-control" name="question" placeholder="نص السؤال" required>
                            </div>
                            <div class="col-6 col-lg-2">
                                <select class="form-select" name="answer_type" required>
                                    <option value="score_5">درجة من 5</option>
                                    <option value="text">نصي</option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-2">
                                <input class="form-control" type="number" min="0" name="sort_order" placeholder="الترتيب">
                            </div>
                            <div class="col-12 col-lg-2">
                                <button class="btn btn-primary w-100" type="submit">إضافة</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>السؤال</th>
                                        <th>نوع الإجابة</th>
                                        <th>الترتيب</th>
                                        <th>الحالة</th>
                                        <th class="text-end">حفظ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($evaluationQuestions as $question)
                                        <tr>
                                            <form method="POST" action="{{ route('role.super_admin.events_lookups.evaluation_questions.update', $question) }}">
                                                @csrf
                                                @method('PUT')
                                                <td><input class="form-control" name="question" value="{{ $question->question }}" required></td>
                                                <td>
                                                    <select class="form-select" name="answer_type" required>
                                                        <option value="score_5" {{ $question->answer_type === 'score_5' ? 'selected' : '' }}>درجة من 5</option>
                                                        <option value="text" {{ $question->answer_type === 'text' ? 'selected' : '' }}>نصي</option>
                                                    </select>
                                                </td>
                                                <td><input class="form-control" type="number" min="0" name="sort_order" value="{{ $question->sort_order ?? 0 }}"></td>
                                                <td>
                                                    <label class="form-check form-switch lookup-switch mb-0">
                                                        <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $question->is_active ? 'checked' : '' }}>
                                                        <span class="form-check-label">{{ $question->is_active ? 'مفعّل' : 'غير مفعّل' }}</span>
                                                    </label>
                                                </td>
                                                <td class="text-end"><button class="btn btn-outline-primary btn-sm" type="submit">حفظ</button></td>
                                            </form>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
