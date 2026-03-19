@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.create_title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
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
            <form method="POST" action="{{ route('role.programs.activities.store') }}" class="row event-form-grid">
                @csrf
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.title') }}</label>
                    <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.center') }}</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}" @selected((string) old('center_id') === (string) $center->id)>{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.agenda_event') }}</label>
                    <select class="form-select" name="agenda_event_id">
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.agenda_event_placeholder') }}</option>
                        @foreach ($agendaEvents as $event)
                            <option value="{{ $event->id }}" @selected((string) old('agenda_event_id') === (string) $event->id)>{{ $event->event_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.status') }}</label>
                    <select class="form-select" name="status" required>
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>{{ __('app.roles.programs.monthly_activities.statuses.draft') }}</option>
                        <option value="submitted" @selected(old('status') === 'submitted')>{{ __('app.roles.programs.monthly_activities.statuses.submitted') }}</option>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">نوع المكان</label>
                    <select class="form-select js-location-type @error('location_type') is-invalid @enderror" name="location_type" required>
                        <option value="inside_center" @selected(old('location_type', 'inside_center') === 'inside_center')>داخل المركز</option>
                        <option value="outside_center" @selected(old('location_type', 'inside_center') === 'outside_center')>خارج المركز</option>
                    </select>
                    @error('location_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-inside-location">
                    <label class="form-label">أي قاعة</label>
                    <input class="form-control @error('internal_location') is-invalid @enderror" name="internal_location" value="{{ old('internal_location') }}">
                    @error('internal_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">اسم الموقع</label>
                    <input class="form-control @error('outside_place_name') is-invalid @enderror" name="outside_place_name" value="{{ old('outside_place_name') }}">
                    @error('outside_place_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رابط الموقع من Google Maps</label>
                    <input class="form-control @error('outside_google_maps_url') is-invalid @enderror" name="outside_google_maps_url" value="{{ old('outside_google_maps_url') }}">
                    @error('outside_google_maps_url')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12"><div class="event-form-section">
                    <h2 class="event-section-title">{{ __('app.roles.programs.monthly_activities.edit_details') }}</h2></div></div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.responsible_entity') }}</label>
                    <input class="form-control" name="responsible_party" value="{{ old('responsible_party') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.execution_time') }}</label>
                    <input class="form-control" name="execution_time" value="{{ old('execution_time') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">الفئة المستهدفة</label>
                    <select class="form-select js-target-group" name="target_group_id">
                        <option value="">-- اختر --</option>
                        @foreach($targetGroups as $group)
                            <option value="{{ $group->id }}" data-is-other="{{ $group->is_other ? 1 : 0 }}" @selected((string) old('target_group_id') === (string) $group->id)>{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 js-target-group-other">
                    <label class="form-label">أخرى (توضيح)</label>
                    <input class="form-control" name="target_group_other" value="{{ old('target_group_other') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.short_description') }}</label>
                    <input class="form-control" name="short_description" value="{{ old('short_description') }}">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.need_volunteers') }}</label>
                    <input class="form-control" name="volunteer_need" value="{{ old('volunteer_need') }}">
                </div>
                <div class="col-12"><div class="event-form-section">
                    <h2 class="event-section-title">{{ __('app.roles.programs.monthly_activities.fields_ext.sponsors_open') }}</h2>
                    <div class="row g-2">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="col-12 col-md-4">
                                <input class="form-control" name="sponsors[{{ $i }}][name]" value="{{ old("sponsors.$i.name") }}" placeholder="{{ __('app.roles.programs.monthly_activities.fields_ext.sponsor_name') }}">
                            </div>
                            <div class="col-12 col-md-4">
                                <input class="form-control" name="sponsors[{{ $i }}][title]" value="{{ old("sponsors.$i.title") }}" placeholder="{{ __('app.roles.programs.monthly_activities.fields_ext.sponsor_title') }}">
                            </div>
                            <div class="col-12 col-md-4 d-flex align-items-center">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="sponsors[{{ $i }}][is_official]" value="1" id="sponsor-official-{{ $i }}" checked>
                                    <label class="form-check-label" for="sponsor-official-{{ $i }}">{{ __('app.roles.programs.monthly_activities.fields_ext.official_sponsor') }}</label>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div></div>
                <div class="col-12"><div class="event-form-section">
                    <h2 class="event-section-title">{{ __('app.roles.programs.monthly_activities.fields_ext.partners_open') }}</h2>
                    <div class="row g-2">
                        @for ($i = 0; $i < 5; $i++)
                            <div class="col-12 col-md-6">
                                <input class="form-control" name="partners[{{ $i }}][name]" value="{{ old("partners.$i.name") }}" placeholder="{{ __('app.roles.programs.monthly_activities.fields_ext.partner_name') }}">
                            </div>
                            <div class="col-12 col-md-6">
                                <input class="form-control" name="partners[{{ $i }}][role]" value="{{ old("partners.$i.role") }}" placeholder="{{ __('app.roles.programs.monthly_activities.fields_ext.partner_role') }}">
                            </div>
                        @endfor
                    </div>
                </div></div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="needs_official_letters" value="1" id="needs_letters_create">
                        <label class="form-check-label" for="needs_letters_create">{{ __('app.roles.programs.monthly_activities.fields_ext.needs_letters') }}</label>
                    </div>
                </div>
                <div class="col-12 col-md-9">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.letter_reason') }}</label>
                    <input class="form-control" name="letter_purpose" value="{{ old('letter_purpose') }}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields.proposed_date') }}</label>
                    <input class="form-control" type="date" name="rescheduled_date" value="{{ old('rescheduled_date') }}">
                </div>
                <div class="col-12 col-md-8">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.reschedule_reason') }}</label>
                    <input class="form-control" name="reschedule_reason" value="{{ old('reschedule_reason') }}">
                </div></div>
                <div class="col-12 col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="relations_approval_on_reschedule" value="1" id="relations_reschedule_approve_create">
                        <label class="form-check-label" for="relations_reschedule_approve_create">{{ __('app.roles.programs.monthly_activities.fields_ext.relations_reschedule_approve') }}</label>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label">{{ __('app.roles.programs.monthly_activities.fields_ext.audience_satisfaction') }}</label>
                    <input class="form-control" type="number" min="0" max="100" step="0.01" name="audience_satisfaction_percent" value="{{ old('audience_satisfaction_percent') }}">
                </div>
                <div class="col-12"><div class="event-form-section"><h2 class="event-section-title">الحضور والمتطوعين</h2></div></div>
                <div class="col-12 col-md-3"><label class="form-label">الحضور المتوقع</label><input class="form-control" type="number" min="0" name="expected_attendance" value="{{ old('expected_attendance') }}"></div>
                <div class="col-12 col-md-3"><label class="form-label">الحضور الفعلي</label><input class="form-control" type="number" min="0" name="actual_attendance" value="{{ old('actual_attendance') }}"></div>
                <div class="col-12 col-md-3 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_volunteers" value="1" id="needs_volunteers" @checked(old('needs_volunteers'))><label class="form-check-label" for="needs_volunteers">تحتاج متطوعين</label></div></div>
                <div class="col-12 col-md-3"><label class="form-label">عدد المتطوعين المطلوب</label><input class="form-control" type="number" min="0" name="required_volunteers" value="{{ old('required_volunteers') }}"></div>
                <div class="col-12"><label class="form-label">ملاحظات الحضور</label><textarea class="form-control" name="attendance_notes" rows="2">{{ old('attendance_notes') }}</textarea></div>

                <div class="col-12"><div class="event-form-section"><h2 class="event-section-title">المخاطبات والتغطية الإعلامية</h2></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_official_correspondence" value="1" id="needs_official_correspondence" @checked(old('needs_official_correspondence'))><label class="form-check-label" for="needs_official_correspondence">تحتاج مخاطبات رسمية</label></div></div>
                <div class="col-12 col-md-8"><label class="form-label">سبب المخاطبة</label><input class="form-control" name="official_correspondence_reason" value="{{ old('official_correspondence_reason') }}"></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="needs_media_coverage" value="1" id="needs_media_coverage" @checked(old('needs_media_coverage'))><label class="form-check-label" for="needs_media_coverage">تحتاج تغطية إعلامية</label></div></div>
                <div class="col-12 col-md-8"><label class="form-label">ملاحظات التغطية الإعلامية</label><input class="form-control" name="media_coverage_notes" value="{{ old('media_coverage_notes') }}"></div>


                <div class="col-12"><div class="event-form-section"><h2 class="event-section-title">Workflow Routing</h2></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_programs" value="1" id="requires_programs" @checked(old('requires_programs'))><label class="form-check-label" for="requires_programs">requires_programs</label></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_workshops" value="1" id="requires_workshops" @checked(old('requires_workshops'))><label class="form-check-label" for="requires_workshops">requires_workshops</label></div></div>
                <div class="col-12 col-md-4 d-flex align-items-center"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="requires_communications" value="1" id="requires_communications" @checked(old('requires_communications'))><label class="form-check-label" for="requires_communications">requires_communications</label></div></div>

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
    </div></div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const locType = document.querySelector('.js-location-type');
  const inside = document.querySelectorAll('.js-inside-location');
  const outside = document.querySelectorAll('.js-outside-location');
  const tg = document.querySelector('.js-target-group');
  const tgOther = document.querySelectorAll('.js-target-group-other');
  const toggle = () => {
    const outsideSelected = locType && locType.value === 'outside_center';
    inside.forEach(el => el.style.display = outsideSelected ? 'none' : 'block');
    outside.forEach(el => el.style.display = outsideSelected ? 'block' : 'none');
    const selected = tg?.selectedOptions?.[0];
    const isOther = selected && selected.dataset.isOther === '1';
    tgOther.forEach(el => el.style.display = isOther ? 'block' : 'none');
  };
  locType?.addEventListener('change', toggle);
  tg?.addEventListener('change', toggle);
  toggle();
});
</script>
@endpush

@endsection
