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
            <form method="POST" action="{{ route('role.relations.activities.store') }}" enctype="multipart/form-data" class="row event-form-grid">
                @csrf

                <div class="col-12 col-md-6">
                    <label class="form-label">عنوان النشاط</label>
                    <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" required>
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">التاريخ المتوقع للنشاط</label>
                    <input class="form-control" type="date" name="proposed_date" value="{{ old('proposed_date') }}" required>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">تاريخ الخطة</label>
                    <input class="form-control" type="date" name="activity_date" value="{{ old('activity_date') }}" required>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الحالة</label>
                    <select class="form-select" name="status" required>
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>{{ __('app.roles.programs.monthly_activities.statuses.draft') }}</option>
                        <option value="submitted" @selected(old('status') === 'submitted')>{{ __('app.roles.programs.monthly_activities.statuses.submitted') }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الفرع</label>
                    <select class="form-select" name="branch_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">المركز</label>
                    <select class="form-select" name="center_id" required>
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.center_placeholder') }}</option>
                        @foreach ($centers as $center)
                            <option value="{{ $center->id }}" @selected((string) old('center_id') === (string) $center->id)>{{ $center->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">نوع المكان</label>
                    <select class="form-select js-location-type @error('location_type') is-invalid @enderror" name="location_type" required>
                        <option value="inside_center" @selected(old('location_type', 'inside_center') === 'inside_center')>داخل المركز</option>
                        <option value="outside_center" @selected(old('location_type') === 'outside_center')>خارج المركز</option>
                    </select>
                </div>

                <div class="col-12 col-md-4 js-inside-location">
                    <label class="form-label">أي قاعة داخل المركز</label>
                    <input class="form-control" name="internal_location" value="{{ old('internal_location') }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">اسم المكان الخارجي</label>
                    <input class="form-control" name="outside_place_name" value="{{ old('outside_place_name') }}">
                </div>

                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رابط Google Maps</label>
                    <input class="form-control" name="outside_google_maps_url" value="{{ old('outside_google_maps_url') }}">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">وقت التنفيذ من</label>
                    <input class="form-control" type="time" name="time_from" value="{{ old('time_from') }}">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">وقت التنفيذ إلى</label>
                    <input class="form-control" type="time" name="time_to" value="{{ old('time_to') }}">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">وصف مختصر للفعالية</label>
                    <textarea class="form-control" name="short_description" rows="2">{{ old('short_description') }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label d-block mb-2">الجهة المسؤولة (توجيه القبول)</label>
                    <div class="d-flex flex-wrap gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="responsible_entities[]" value="relations" id="entity-relations" @checked(collect(old('responsible_entities', []))->contains('relations'))>
                            <label class="form-check-label" for="entity-relations">العلاقات</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="responsible_entities[]" value="programs" id="entity-programs" @checked(collect(old('responsible_entities', []))->contains('programs'))>
                            <label class="form-check-label" for="entity-programs">البرامج</label>
                        </div>
                    </div>
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

                <div class="col-12 col-md-8 js-target-group-other">
                    <label class="form-label">أخرى (توضيح)</label>
                    <input class="form-control" name="target_group_other" value="{{ old('target_group_other') }}">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">عدد الأشخاص المتوقع</label>
                    <input class="form-control" type="number" min="0" name="expected_attendance" value="{{ old('expected_attendance') }}">
                </div>

                <div class="col-12 col-md-9">
                    <label class="form-label">الوصف التفصيلي</label>
                    <textarea class="form-control" name="description" rows="2">{{ old('description') }}</textarea>
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center mt-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input js-has-sponsor" type="checkbox" name="has_sponsor" value="1" id="has_sponsor" @checked(old('has_sponsor'))>
                        <label class="form-check-label" for="has_sponsor">يوجد راعي رسمي</label>
                    </div>
                </div>

                <div class="col-12 js-sponsor-wrapper">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <input class="form-control" name="sponsors[0][name]" value="{{ old('sponsors.0.name') }}" placeholder="اسم الراعي">
                        </div>
                        <div class="col-12 col-md-6">
                            <input class="form-control" name="sponsors[0][title]" value="{{ old('sponsors.0.title') }}" placeholder="صفة الراعي">
                        </div>
                    </div>
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center mt-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input js-has-partners" type="checkbox" name="has_partners" value="1" id="has_partners" @checked(old('has_partners'))>
                        <label class="form-check-label" for="has_partners">يوجد شركاء</label>
                    </div>
                </div>

                <div class="col-12 col-md-4 js-partners-wrapper">
                    <label class="form-label">عدد الشركاء</label>
                    <input class="form-control js-partners-count" type="number" min="1" max="10" value="{{ old('partners_count', 1) }}">
                </div>

                <div class="col-12 js-partners-wrapper">
                    <div class="row g-2 js-partners-container"></div>
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input js-needs-letters" type="checkbox" name="needs_official_correspondence" value="1" id="needs_official_correspondence" @checked(old('needs_official_correspondence'))>
                        <label class="form-check-label" for="needs_official_correspondence">هل الفعالية بحاجة لمخاطبات رسمية؟</label>
                    </div>
                </div>

                <div class="col-12 col-md-8 js-letters-reason">
                    <label class="form-label">سبب المخاطبة</label>
                    <input class="form-control" name="official_correspondence_reason" value="{{ old('official_correspondence_reason') }}">
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input js-needs-supplies" type="checkbox" name="requires_supplies" value="1" id="requires_supplies" @checked(old('requires_supplies'))>
                        <label class="form-check-label" for="requires_supplies">بحاجة مستلزمات</label>
                    </div>
                </div>

                <div class="col-12 col-md-4 js-supplies-wrapper">
                    <label class="form-label">عدد المستلزمات</label>
                    <input class="form-control js-supplies-count" type="number" min="1" max="20" value="{{ old('supplies_count', 1) }}">
                </div>

                <div class="col-12 js-supplies-wrapper">
                    <div class="row g-2 js-supplies-container"></div>
                </div>

                <div class="col-12">
                    <h2 class="h6 mt-3">فريق العمل (قبل التنفيذ)</h2>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">عدد أعضاء فريق العمل</label>
                    <input class="form-control js-team-count" type="number" min="1" max="20" value="{{ old('team_count', 1) }}">
                </div>

                <div class="col-12">
                    <div class="row g-2 js-team-container"></div>
                </div>

                <div class="col-12 d-flex justify-content-end mt-2">
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
  const hasSponsor = document.querySelector('.js-has-sponsor');
  const sponsorWrap = document.querySelectorAll('.js-sponsor-wrapper');
  const hasPartners = document.querySelector('.js-has-partners');
  const partnersWrap = document.querySelectorAll('.js-partners-wrapper');
  const partnersCount = document.querySelector('.js-partners-count');
  const partnersContainer = document.querySelector('.js-partners-container');
  const needsLetters = document.querySelector('.js-needs-letters');
  const lettersReason = document.querySelectorAll('.js-letters-reason');
  const needsSupplies = document.querySelector('.js-needs-supplies');
  const suppliesWrap = document.querySelectorAll('.js-supplies-wrapper');
  const suppliesCount = document.querySelector('.js-supplies-count');
  const suppliesContainer = document.querySelector('.js-supplies-container');
  const teamCount = document.querySelector('.js-team-count');
  const teamContainer = document.querySelector('.js-team-container');

  function renderPartners() {
    if (!partnersContainer) return;
    const count = Math.max(1, Math.min(10, parseInt(partnersCount?.value || '1', 10)));
    partnersContainer.innerHTML = '';
    for (let i = 0; i < count; i++) {
      partnersContainer.insertAdjacentHTML('beforeend', `
        <div class="col-12 col-md-6"><input class="form-control" name="partners[${i}][name]" placeholder="اسم الشريك ${i + 1}"></div>
        <div class="col-12 col-md-6"><input class="form-control" name="partners[${i}][role]" placeholder="دور الشريك ${i + 1}"></div>
      `);
    }
  }

  function renderTeam() {
    if (!teamContainer) return;
    const count = Math.max(1, Math.min(20, parseInt(teamCount?.value || '1', 10)));
    teamContainer.innerHTML = '';
    for (let i = 0; i < count; i++) {
      teamContainer.insertAdjacentHTML('beforeend', `
        <div class="col-12 col-md-6"><input class="form-control" name="team_members[${i}][member_name]" placeholder="اسم العضو ${i + 1}"></div>
        <div class="col-12 col-md-6"><input class="form-control" name="team_members[${i}][role_desc]" placeholder="مسؤولية العضو ${i + 1}"></div>
      `);
    }
  }

  function renderSupplies() {
    if (!suppliesContainer) return;
    const count = Math.max(1, Math.min(20, parseInt(suppliesCount?.value || '1', 10)));
    suppliesContainer.innerHTML = '';
    for (let i = 0; i < count; i++) {
      suppliesContainer.insertAdjacentHTML('beforeend', `
        <div class="col-12 col-md-6"><input class="form-control" name="supplies[${i}][item_name]" placeholder="اسم المستلزم ${i + 1}"></div>
        <div class="col-12 col-md-6 d-flex align-items-center"><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="supplies[${i}][available]" value="1" id="supply-${i}"><label class="form-check-label" for="supply-${i}">متوفر</label></div></div>
      `);
    }
  }

  const toggle = () => {
    const outsideSelected = locType?.value === 'outside_center';
    inside.forEach(el => el.style.display = outsideSelected ? 'none' : 'block');
    outside.forEach(el => el.style.display = outsideSelected ? 'block' : 'none');

    const selected = tg?.selectedOptions?.[0];
    const isOther = selected && selected.dataset.isOther === '1';
    tgOther.forEach(el => el.style.display = isOther ? 'block' : 'none');

    sponsorWrap.forEach(el => el.style.display = hasSponsor?.checked ? 'block' : 'none');
    partnersWrap.forEach(el => el.style.display = hasPartners?.checked ? 'block' : 'none');
    lettersReason.forEach(el => el.style.display = needsLetters?.checked ? 'block' : 'none');
    suppliesWrap.forEach(el => el.style.display = needsSupplies?.checked ? 'block' : 'none');
  };

  locType?.addEventListener('change', toggle);
  tg?.addEventListener('change', toggle);
  hasSponsor?.addEventListener('change', toggle);
  hasPartners?.addEventListener('change', toggle);
  needsLetters?.addEventListener('change', toggle);
  needsSupplies?.addEventListener('change', toggle);
  partnersCount?.addEventListener('input', renderPartners);
  teamCount?.addEventListener('input', renderTeam);
  suppliesCount?.addEventListener('input', renderSupplies);

  renderPartners();
  renderTeam();
  renderSupplies();
  toggle();
});
</script>
@endpush

@endsection
