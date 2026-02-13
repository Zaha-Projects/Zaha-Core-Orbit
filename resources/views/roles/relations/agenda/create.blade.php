@extends('layouts.app')

@php
    $title = __('app.roles.relations.agenda.create_title');
    $subtitle = __('app.roles.relations.agenda.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.relations.agenda.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">اسم الفعالية</label>
                    <input class="form-control" name="event_name" value="{{ old('event_name') }}" required>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">التاريخ</label>
                    <input class="form-control" type="date" name="event_date" value="{{ old('event_date') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">القسم المعني</label>
                    <select class="form-select" name="department_id">
                        <option value="">--</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}" @selected(old('department_id') == $department->id)>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">صنف الفعالية</label>
                    <select class="form-select" name="event_category_id">
                        <option value="">--</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('event_category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">نوع الفعالية</label>
                    <select class="form-select" name="event_type" required>
                        <option value="mandatory" @selected(old('event_type') === 'mandatory')>إجباري</option>
                        <option value="optional" @selected(old('event_type') === 'optional')>اختياري</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">خطة الفعالية</label>
                    <select class="form-select" name="plan_type" required>
                        <option value="unified" @selected(old('plan_type') === 'unified')>موحد</option>
                        <option value="non_unified" @selected(old('plan_type') === 'non_unified')>غير موحد</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">مشاركة الفروع</label>
                    <div class="row g-2">
                        @foreach ($branches as $branch)
                            <div class="col-12 col-md-4">
                                <label class="form-label small mb-1">{{ $branch->name }}</label>
                                <select class="form-select form-select-sm" name="branch_participation[{{ $branch->id }}]">
                                    <option value="unspecified">غير محدد</option>
                                    <option value="participant">مشارك</option>
                                    <option value="not_participant">غير مشارك</option>
                                </select>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">ملاحظات</label>
                    <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">حفظ</button>
                </div>
            </form>
        </div>
    </div>
@endsection
