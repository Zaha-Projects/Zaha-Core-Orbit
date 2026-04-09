@extends('layouts.app')

@php
    $title = __('app.roles.programs.monthly_activities.create_title');
    $subtitle = __('app.roles.programs.monthly_activities.subtitle');
    $oldPartners = old('partners', []);
    $oldSupplies = old('supplies', []);
    $oldTeamGroups = old('team_groups', []);
    $partnersCount = max(1, count($oldPartners));
    $suppliesCount = max(1, count($oldSupplies));
    $teamGroupsCount = max(1, count($oldTeamGroups));
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
                    <input class="form-control @error('title') is-invalid @enderror" name="title" value="{{ old('title') }}" >
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">التاريخ المتوقع للنشاط</label>
                    <input class="form-control" type="date" name="proposed_date" value="{{ old('proposed_date') }}" >
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">تاريخ التنفيذ</label>
                    <input class="form-control" type="date" name="activity_date" value="{{ old('activity_date') }}" >
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الحالة</label>
                    <select class="form-select" name="status" >
                        <option value="draft" @selected(old('status', 'draft') === 'draft')>{{ __('app.roles.programs.monthly_activities.statuses.draft') }}</option>
                        <option value="submitted" @selected(old('status') === 'submitted')>{{ __('app.roles.programs.monthly_activities.statuses.submitted') }}</option>
                        <option value="postponed" @selected(old('status') === 'postponed')>{{ __('app.roles.programs.monthly_activities.statuses.postponed') }}</option>
                        <option value="cancelled" @selected(old('status') === 'cancelled')>{{ __('app.roles.programs.monthly_activities.statuses.cancelled') }}</option>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">الفرع</label>
                    <select class="form-select" name="branch_id" >
                        <option value="">{{ __('app.roles.programs.monthly_activities.fields.branch_placeholder') }}</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center mt-2">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="is_in_agenda" value="1" id="is_in_agenda" @checked(old('is_in_agenda'))>
                        <label class="form-check-label" for="is_in_agenda">النشاط الشهري ضمن الأجندة السنوية</label>
                    </div>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">المركز</label>
</div>

                <div class="col-12 col-md-4">
                    <label class="form-label">مرفق التخطيط (اختياري)</label>
                    <input class="form-control @error('planning_attachment') is-invalid @enderror @error('branch_plan_file') is-invalid @enderror" type="file" name="planning_attachment" accept=".pdf,.doc,.docx,.xls,.xlsx">
                    @error('planning_attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('branch_plan_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">نوع المكان</label>
                    <select class="form-select js-location-type @error('location_type') is-invalid @enderror" name="location_type" >
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
                <div class="col-12 col-md-4 js-outside-location">
                    <label class="form-label">رقم تواصل المكان الخارجي</label>
                    <input class="form-control" name="outside_contact_number" value="{{ old('outside_contact_number') }}">
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
                    <textarea class="form-control" name="short_description" rows="2" placeholder="ملخص سريع عن الفعالية وأهدافها">{{ old('short_description') }}</textarea>
                </div>

                <div class="col-12">
                    <label class="form-label d-block mb-2">الجهة المسؤولة</label>
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

                <div class="col-12 col-md-12">
                    <label class="form-label">الفئة المستهدفة</label>
                    @php $selectedTargetGroupIds = array_map('intval', old('target_group_ids', old('target_group_id') ? [old('target_group_id')] : [])); @endphp
                    <div class="partner-departments-box">
                        @foreach($targetGroups as $group)
                            <label class="partner-department-item">
                                <input class="form-check-input m-0 js-target-group-checkbox" type="checkbox" name="target_group_ids[]" value="{{ $group->id }}" data-is-other="{{ $group->is_other ? 1 : 0 }}" {{ in_array((int) $group->id, $selectedTargetGroupIds, true) ? 'checked' : '' }}>
                                <span>{{ $group->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="col-12 col-md-8 js-target-group-other">
                    <label class="form-label">أخرى (توضيح)</label>
                    <input class="form-control" name="target_group_other" value="{{ old('target_group_other') }}">
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label">عدد الأشخاص المتوقع</label>
                    <input class="form-control" type="number" min="0" name="expected_attendance" value="{{ old('expected_attendance') }}">
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input js-needs-volunteers" type="checkbox" name="needs_volunteers" value="1" id="needs_volunteers" @checked(old('needs_volunteers'))>
                        <label class="form-check-label" for="needs_volunteers">هل النشاط بحاجة لمتطوعين؟</label>
                    </div>
                </div>
                <div class="col-12 col-md-4 js-volunteers-required-wrapper">
                    <label class="form-label">عدد المتطوعين المطلوب</label>
                    <input class="form-control js-required-volunteers" type="number" min="1" name="required_volunteers" value="{{ old('required_volunteers') }}">
                </div>

                <div class="col-12 col-md-6">
                    <label class="form-label">الوصف التفصيلي</label>
                    <textarea class="form-control" name="description" rows="2" placeholder="تفاصيل النشاط (فقرات أو أجندة الفعالية)">{{ old('description') }}</textarea>
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
                    <input class="form-control js-partners-count" type="number" min="1" max="10" value="{{ old('partners_count', $partnersCount) }}">
                </div>

                <div class="col-12 js-partners-wrapper">
                    <div class="row g-2 js-partners-container"></div>
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input js-needs-letters" type="checkbox" name="needs_official_correspondence" value="1" id="needs_official_correspondence" @checked(old('needs_official_correspondence'))>
                        <label class="form-check-label" for="needs_official_correspondence">هل الفعالية بحاجة لمخاطبات رسمية؟</label>
                    </div>
                </div>

                <div class="col-12 col-md-8 js-letters-reason">
                    <label class="form-label">سبب المخاطبة</label>
                    <input class="form-control js-official-correspondence-reason" name="official_correspondence_reason" value="{{ old('official_correspondence_reason') }}">
                </div>
                <div class="col-12 col-md-6 js-letters-reason">
                    <label class="form-label">الجهة المطلوب مخاطبتها</label>
                    <input class="form-control js-official-correspondence-target" name="official_correspondence_target" value="{{ old('official_correspondence_target') }}">
                </div>

                <div class="col-12 col-md-4 d-flex align-items-center">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input js-needs-supplies" type="checkbox" name="requires_supplies" value="1" id="requires_supplies" @checked(old('requires_supplies'))>
                        <label class="form-check-label" for="requires_supplies">بحاجة مستلزمات</label>
                    </div>
                </div>

                <div class="col-12 col-md-4 js-supplies-wrapper">
                    <label class="form-label">عدد المستلزمات</label>
                    <input class="form-control js-supplies-count" type="number" min="1" max="20" value="{{ old('supplies_count', $suppliesCount) }}">
                </div>

                <div class="col-12 js-supplies-wrapper">
                    <div class="row g-2 js-supplies-container"></div>
                </div>

                <div class="col-12">
                    <h2 class="h6 mt-3">فريق العمل (قبل التنفيذ)</h2>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label">عدد فرق العمل</label>
                    <input class="form-control js-team-groups-count" type="number" min="1" max="10" value="{{ old('team_groups_count', $teamGroupsCount) }}">
                </div>

                <div class="col-12">
                    <div class="js-team-groups-container"></div>
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
  const tgChecks = Array.from(document.querySelectorAll('.js-target-group-checkbox'));
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
  const needsVolunteers = document.querySelector('.js-needs-volunteers');
  const volunteersRequiredWrap = document.querySelectorAll('.js-volunteers-required-wrapper');
  const requiredVolunteersInput = document.querySelector('.js-required-volunteers');
  const outsideInputs = [
    document.querySelector('[name="outside_place_name"]'),
    document.querySelector('[name="outside_google_maps_url"]'),
    document.querySelector('[name="outside_contact_number"]'),
    document.querySelector('[name="outside_address"]')
  ].filter(Boolean);
  const lettersReasonInput = document.querySelector('.js-official-correspondence-reason');
  const lettersTargetInput = document.querySelector('.js-official-correspondence-target');
  const suppliesWrap = document.querySelectorAll('.js-supplies-wrapper');
  const suppliesCount = document.querySelector('.js-supplies-count');
  const suppliesContainer = document.querySelector('.js-supplies-container');
  const teamGroupsCount = document.querySelector('.js-team-groups-count');
  const teamGroupsContainer = document.querySelector('.js-team-groups-container');
  const oldPartners = @json($oldPartners);
  const oldSupplies = @json($oldSupplies);
  const oldTeamGroups = @json($oldTeamGroups);
  const esc = (value) => String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\"/g, '&quot;')
    .replace(/'/g, '&#039;');

  function renderPartners() {
    if (!partnersContainer) return;
    const count = Math.max(1, Math.min(10, parseInt(partnersCount?.value || '1', 10)));
    partnersContainer.innerHTML = '';
    for (let i = 0; i < count; i++) {
      partnersContainer.insertAdjacentHTML('beforeend', `
        <div class="col-12 col-md-6"><input class="form-control" name="partners[${i}][name]" placeholder="اسم الشريك ${i + 1}" value="${esc(oldPartners?.[i]?.name)}"></div>
        <div class="col-12 col-md-6"><input class="form-control" name="partners[${i}][role]" placeholder="دور الشريك ${i + 1}" value="${esc(oldPartners?.[i]?.role)}"></div>
      `);
    }
  }

  function renderTeamGroups() {
    if (!teamGroupsContainer) return;
    const groupsCount = Math.max(1, Math.min(10, parseInt(teamGroupsCount?.value || '1', 10)));
    teamGroupsContainer.innerHTML = '';

    for (let g = 0; g < groupsCount; g++) {
      const membersCountId = `team-group-members-count-${g}`;
      const groupHtml = `
        <div class="card border rounded-3 p-3 mb-3 js-team-group" data-group-index="${g}">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
              <label class="form-label">اسم الفريق ${g + 1}</label>
              <input class="form-control" name="team_groups[${g}][team_name]" placeholder="مثال: الفريق ${g + 1}" value="${esc(oldTeamGroups?.[g]?.team_name || ('فريق ' + (g + 1)))}">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">عدد أعضاء الفريق ${g + 1}</label>
              <input class="form-control js-team-members-count" id="${membersCountId}" type="number" min="1" max="30" value="${Math.max(1, oldTeamGroups?.[g]?.members?.length || 1)}">
            </div>
          </div>
          <div class="row g-2 mt-2 js-team-members-container"></div>
        </div>
      `;
      teamGroupsContainer.insertAdjacentHTML('beforeend', groupHtml);
    }

    teamGroupsContainer.querySelectorAll('.js-team-group').forEach((groupEl) => {
      const groupIndex = parseInt(groupEl.dataset.groupIndex || '0', 10);
      const countInput = groupEl.querySelector('.js-team-members-count');
      const membersContainer = groupEl.querySelector('.js-team-members-container');

      const renderMembers = () => {
        const membersCount = Math.max(1, Math.min(30, parseInt(countInput?.value || '1', 10)));
        membersContainer.innerHTML = '';
        for (let m = 0; m < membersCount; m++) {
          membersContainer.insertAdjacentHTML('beforeend', `
            <div class="col-12 col-md-6">
              <input class="form-control" name="team_groups[${groupIndex}][members][${m}][member_name]" placeholder="اسم عضو الفريق ${groupIndex + 1} - ${m + 1}" value="${esc(oldTeamGroups?.[groupIndex]?.members?.[m]?.member_name)}">
            </div>
            <div class="col-12 col-md-6">
              <input class="form-control" name="team_groups[${groupIndex}][members][${m}][role_desc]" placeholder="مسؤولية العضو ${m + 1}" value="${esc(oldTeamGroups?.[groupIndex]?.members?.[m]?.role_desc)}">
            </div>
          `);
        }
      };

      countInput?.addEventListener('input', renderMembers);
      renderMembers();
    });
  }

  function renderSupplies() {
    if (!suppliesContainer) return;
    const count = Math.max(1, Math.min(20, parseInt(suppliesCount?.value || '1', 10)));
    suppliesContainer.innerHTML = '';
    for (let i = 0; i < count; i++) {
      const available = !!oldSupplies?.[i]?.available;
      suppliesContainer.insertAdjacentHTML('beforeend', `
        <div class="col-12 col-md-6"><input class="form-control" name="supplies[${i}][item_name]" placeholder="اسم المستلزم ${i + 1}" value="${esc(oldSupplies?.[i]?.item_name)}"></div>
        <div class="col-12 col-md-3">
          <select class="form-select js-supply-available" data-index="${i}" name="supplies[${i}][available]">
            <option value="1" ${available ? 'selected' : ''}>متوفر</option>
            <option value="0" ${!available ? 'selected' : ''}>غير متوفر</option>
          </select>
        </div>
        <div class="col-12 col-md-3 js-supply-provider" data-index="${i}" style="${available ? 'display:none' : ''}">
          <select class="form-select mb-2" name="supplies[${i}][provider_type]">
            <option value="">نوع المسؤول</option>
            <option value="volunteer" ${(oldSupplies?.[i]?.provider_type === 'volunteer') ? 'selected' : ''}>متطوع</option>
            <option value="person" ${(oldSupplies?.[i]?.provider_type === 'person') ? 'selected' : ''}>شخص</option>
            <option value="partner" ${(oldSupplies?.[i]?.provider_type === 'partner') ? 'selected' : ''}>شريك</option>
          </select>
          <input class="form-control" name="supplies[${i}][provider_name]" placeholder="اسم المسؤول عن التوفير" value="${esc(oldSupplies?.[i]?.provider_name)}">
        </div>
      `);
    }
    suppliesContainer.querySelectorAll('.js-supply-available').forEach((select) => {
      select.addEventListener('change', () => {
        const index = select.dataset.index;
        const provider = suppliesContainer.querySelector(`.js-supply-provider[data-index="${index}"]`);
        if (provider) provider.style.display = select.value === '1' ? 'none' : 'block';
      });
    });
  }

  const toggle = () => {
    const outsideSelected = locType?.value === 'outside_center';
    inside.forEach(el => el.style.display = outsideSelected ? 'none' : 'block');
    outside.forEach(el => el.style.display = outsideSelected ? 'block' : 'none');
    outsideInputs.forEach((input) => {
      input.required = outsideSelected;
      input.disabled = !outsideSelected;
      if (!outsideSelected) input.value = '';
    });

    const isOther = tgChecks.some((input) => input.checked && input.dataset.isOther === '1');
    tgOther.forEach(el => el.style.display = isOther ? 'block' : 'none');

    sponsorWrap.forEach(el => el.style.display = hasSponsor?.checked ? 'block' : 'none');
    partnersWrap.forEach(el => el.style.display = hasPartners?.checked ? 'block' : 'none');
    lettersReason.forEach(el => el.style.display = needsLetters?.checked ? 'block' : 'none');
    if (lettersReasonInput) {
      lettersReasonInput.required = !!needsLetters?.checked;
      lettersReasonInput.disabled = !needsLetters?.checked;
      if (!needsLetters?.checked) lettersReasonInput.value = '';
    }
    if (lettersTargetInput) {
      lettersTargetInput.required = !!needsLetters?.checked;
      lettersTargetInput.disabled = !needsLetters?.checked;
      if (!needsLetters?.checked) lettersTargetInput.value = '';
    }
    suppliesWrap.forEach(el => el.style.display = needsSupplies?.checked ? 'block' : 'none');
    if (suppliesCount) {
      suppliesCount.required = !!needsSupplies?.checked;
      suppliesCount.disabled = !needsSupplies?.checked;
      if (!needsSupplies?.checked) suppliesCount.value = '1';
    }
    if (!needsSupplies?.checked) {
      suppliesContainer.querySelectorAll('input, select, textarea').forEach((input) => {
        if (input.type === 'checkbox' || input.type === 'radio') {
          input.checked = false;
        } else {
          input.value = '';
        }
      });
    }
    volunteersRequiredWrap.forEach(el => el.style.display = needsVolunteers?.checked ? 'block' : 'none');
    if (requiredVolunteersInput) {
      requiredVolunteersInput.required = !!needsVolunteers?.checked;
      requiredVolunteersInput.disabled = !needsVolunteers?.checked;
      if (!needsVolunteers?.checked) requiredVolunteersInput.value = '';
    }
  };

  locType?.addEventListener('change', toggle);
  tgChecks.forEach((check) => check.addEventListener('change', toggle));
  hasSponsor?.addEventListener('change', toggle);
  hasPartners?.addEventListener('change', toggle);
  needsLetters?.addEventListener('change', toggle);
  needsSupplies?.addEventListener('change', toggle);
  needsVolunteers?.addEventListener('change', toggle);
  partnersCount?.addEventListener('input', renderPartners);
  teamGroupsCount?.addEventListener('input', renderTeamGroups);
  suppliesCount?.addEventListener('input', renderSupplies);

  renderPartners();
  renderTeamGroups();
  renderSupplies();
  toggle();
});
</script>
<style>
  .partner-departments-box { border: 1px solid #dee2e6; border-radius: .5rem; padding: .5rem; max-height: 180px; overflow-y: auto; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
  .partner-department-item { display: flex; align-items: center; gap: .5rem; padding: .25rem .4rem; border-radius: .35rem; background: #f8f9fa; margin: 0; font-size: .9rem; }
</style>
@endpush

@endsection
