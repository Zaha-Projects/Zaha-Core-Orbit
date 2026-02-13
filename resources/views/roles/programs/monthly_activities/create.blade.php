@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.create_title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
@endphp

@section('content')
    <div class="card shadow-sm">
        <div class="card-body">
            <h1 class="h4 mb-2">{{ $title }}</h1>
            <p class="text-muted mb-4">{{ $subtitle }}</p>
            <form method="POST" action="{{ route('role.programs.activities.store') }}" class="row g-3">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.title') }}</label>
                    <input class="form-control" name="title" value="{{ old('title') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.activity_date') }}</label>
                    <input class="form-control" type="date" name="activity_date" value="{{ old('activity_date') }}" required>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.proposed_date') }}</label>
                    <input class="form-control" type="date" name="proposed_date" value="{{ old('proposed_date') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.branch') }}</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}">{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.agenda_event') }}</label>
                    <select class="form-select" name="agenda_event_id">
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.agenda_event_placeholder') }}</option>
                        @foreach ($agendaEvents as $event)
                            <option value="{{ $event->id }}">{{ $event->event_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="draft">{{ __('app.roles.programs.monthly_activities.statuses.draft') }}</option>
                        <option value="submitted">{{ __('app.roles.programs.monthly_activities.statuses.submitted') }}</option>
                        <option value="approved">{{ __('app.roles.programs.monthly_activities.statuses.approved') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.location_type') }}</label>
                    <input class="form-control" name="location_type" value="{{ old('location_type') }}" required>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.location_details') }}</label>
                    <input class="form-control" name="location_details" value="{{ old('location_details') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">الجهة المسؤولة</label>
                    <input class="form-control" name="responsible_party" value="{{ old('responsible_party') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">وقت التنفيذ</label>
                    <input class="form-control" name="execution_time" value="{{ old('execution_time') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">الفئة المستهدفة</label>
                    <input class="form-control" name="target_group" value="{{ old('target_group') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">وصف مختصر</label>
                    <input class="form-control" name="short_description" value="{{ old('short_description') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">الحاجة إلى متطوعين</label>
                    <input class="form-control" name="volunteer_need" value="{{ old('volunteer_need') }}">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="has_sponsor" value="1" id="has_sponsor_create">
                        <label class="form-check-label" for="has_sponsor_create">يوجد راعي رسمي</label>
                    </div>
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label">اسم الراعي الرسمي</label>
                    <input class="form-control" name="sponsor_name_title" value="{{ old('sponsor_name_title') }}">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="has_partners" value="1" id="has_partners_create">
                        <label class="form-check-label" for="has_partners_create">يوجد شركاء</label>
                    </div>
                </div>
                <div class="col-12 col-md-3"><input class="form-control" name="partner_1_name" value="{{ old('partner_1_name') }}" placeholder="اسم الشريك 1"></div>
                <div class="col-12 col-md-3"><input class="form-control" name="partner_1_role" value="{{ old('partner_1_role') }}" placeholder="دور الشريك 1"></div>
                <div class="col-12 col-md-3"><input class="form-control" name="partner_2_name" value="{{ old('partner_2_name') }}" placeholder="اسم الشريك 2"></div>
                <div class="col-12 col-md-3"><input class="form-control" name="partner_2_role" value="{{ old('partner_2_role') }}" placeholder="دور الشريك 2"></div>
                <div class="col-12 col-md-3"><input class="form-control" name="partner_3_name" value="{{ old('partner_3_name') }}" placeholder="اسم الشريك 3"></div>
                <div class="col-12 col-md-3"><input class="form-control" name="partner_3_role" value="{{ old('partner_3_role') }}" placeholder="دور الشريك 3"></div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="needs_official_letters" value="1" id="needs_letters_create">
                        <label class="form-check-label" for="needs_letters_create">بحاجة إلى مخاطبات</label>
                    </div>
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label">سبب المخاطبة</label>
                    <input class="form-control" name="letter_purpose" value="{{ old('letter_purpose') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">تاريخ التعديل المقترح</label>
                    <input class="form-control" type="date" name="rescheduled_date" value="{{ old('rescheduled_date') }}">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">سبب التعديل</label>
                    <input class="form-control" name="reschedule_reason" value="{{ old('reschedule_reason') }}">
                </div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="relations_approval_on_reschedule" value="1" id="relations_reschedule_approve_create">
                        <label class="form-check-label" for="relations_reschedule_approve_create">اعتماد العلاقات على التعديل</label>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">رضى الجمهور %</label>
                    <input class="form-control" type="number" min="0" max="100" step="0.01" name="audience_satisfaction_percent" value="{{ old('audience_satisfaction_percent') }}">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">تقييم الفعالية %</label>
                    <input class="form-control" type="number" min="0" max="100" step="0.01" name="evaluation_score" value="{{ old('evaluation_score') }}">
                </div>
                <div class="col-12">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.description') }}</label>
                    <textarea class="form-control" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        {{ __('app.roles.programs.monthly_activities.actions.create') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
