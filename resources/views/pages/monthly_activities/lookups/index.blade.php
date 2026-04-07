@extends('layouts.app')

@section('content')
<div class="event-module">
    <div class="card event-card mb-4"><div class="card-body"><h1 class="h4 mb-0">إعدادات Lookup للفعاليات</h1></div></div>
    @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif

    <div class="card event-card mb-4"><div class="card-body">
        <h2 class="h6">الفئات المستهدفة</h2>
        <form method="POST" action="{{ route('role.super_admin.events_lookups.target_groups.store') }}" class="row g-2 mb-3">@csrf
            <div class="col-12 col-md-6"><input class="form-control" name="name" placeholder="اسم الفئة" required></div>
            <div class="col-12 col-md-2"><input class="form-control" type="number" name="sort_order" placeholder="الترتيب"></div>
            <div class="col-12 col-md-2 d-flex align-items-center"><div class="form-check"><input type="checkbox" name="is_other" value="1" class="form-check-input" id="is_other"><label class="form-check-label" for="is_other">خيار أخرى</label></div></div>
            <div class="col-12 col-md-2"><button class="btn btn-primary w-100">إضافة</button></div>
        </form>
        <ul class="mb-0">@foreach($targetGroups as $g)<li>{{ $g->name }} @if($g->is_other)(أخرى)@endif</li>@endforeach</ul>
    </div></div>

    <div class="card event-card"><div class="card-body">
        <h2 class="h6">أسئلة التقييم</h2>
        <form method="POST" action="{{ route('role.super_admin.events_lookups.evaluation_questions.store') }}" class="row g-2 mb-3">@csrf
            <div class="col-12 col-md-6"><input class="form-control" name="question" placeholder="نص السؤال" required></div>
            <div class="col-12 col-md-2"><select class="form-select" name="answer_type"><option value="score_5">درجة من 5</option><option value="text">نصي</option></select></div>
            <div class="col-12 col-md-2"><input class="form-control" type="number" name="sort_order" placeholder="الترتيب"></div>
            <div class="col-12 col-md-2"><button class="btn btn-primary w-100">إضافة</button></div>
        </form>
        <ul class="mb-0">@foreach($evaluationQuestions as $q)<li>{{ $q->question }} ({{ $q->answer_type }})</li>@endforeach</ul>
    </div></div>

    <div class="card event-card mt-4"><div class="card-body">
        <h2 class="h6">ألوان وأيقونات الأقسام</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>القسم</th><th>اللون</th><th>الأيقونة</th><th></th></tr></thead>
                <tbody>
                @foreach($departments as $department)
                    <tr>
                        <form method="POST" action="{{ route('role.super_admin.events_lookups.departments.visual.update', $department) }}">
                            @csrf @method('PUT')
                            <td>{{ $department->name }}</td>
                            <td><input type="color" class="form-control form-control-color" name="color_hex" value="{{ $department->color_hex ?? '#2563EB' }}"></td>
                            <td><input class="form-control" name="icon" value="{{ $department->icon ?? '🏢' }}" maxlength="32"></td>
                            <td><button class="btn btn-sm btn-outline-primary">حفظ</button></td>
                        </form>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div></div>

    <div class="card event-card mt-4"><div class="card-body">
        <h2 class="h6">ألوان وأيقونات الوحدات</h2>
        <div class="table-responsive">
            <table class="table table-sm align-middle">
                <thead><tr><th>الوحدة</th><th>اللون</th><th>الأيقونة</th><th></th></tr></thead>
                <tbody>
                @foreach($departmentUnits as $unit)
                    <tr>
                        <form method="POST" action="{{ route('role.super_admin.events_lookups.department_units.visual.update', $unit) }}">
                            @csrf @method('PUT')
                            <td>{{ $unit->name }}</td>
                            <td><input type="color" class="form-control form-control-color" name="color_hex" value="{{ $unit->color_hex ?? '#2563EB' }}"></td>
                            <td><input class="form-control" name="icon" value="{{ $unit->icon ?? '🏢' }}" maxlength="32"></td>
                            <td><button class="btn btn-sm btn-outline-primary">حفظ</button></td>
                        </form>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div></div>
</div>
@endsection
