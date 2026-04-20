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
  const lettersReasonInput = document.querySelector('.js-official-correspondence-reason');
  const lettersTargetInput = document.querySelector('.js-official-correspondence-target');
  const needsSupplies = document.querySelector('.js-needs-supplies');
  const suppliesWrap = document.querySelectorAll('.js-supplies-wrapper');
  const suppliesCount = document.querySelector('.js-supplies-count');
  const suppliesContainer = document.querySelector('.js-supplies-container');
  const teamGroupsCount = document.querySelector('.js-team-groups-count');
  const teamGroupsContainer = document.querySelector('.js-team-groups-container');
  const needsVolunteers = document.querySelector('.js-needs-volunteers');
  const volunteersRequiredWrap = document.querySelectorAll('.js-volunteers-required-wrapper');
  const requiredVolunteersInput = document.querySelector('.js-required-volunteers');
  const outsideInputs = [
    document.querySelector('[name="outside_place_name"]'),
    document.querySelector('[name="outside_google_maps_url"]'),
    document.querySelector('[name="outside_contact_number"]'),
    document.querySelector('[name="outside_address"]')
  ].filter(Boolean);
  const oldPartners = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('monthly-edit-old-partners-json', []) : JSON.parse(document.getElementById('monthly-edit-old-partners-json')?.textContent ?? '[]');
  const oldSupplies = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('monthly-edit-old-supplies-json', []) : JSON.parse(document.getElementById('monthly-edit-old-supplies-json')?.textContent ?? '[]');
  const oldTeamGroups = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('monthly-edit-old-team-groups-json', []) : JSON.parse(document.getElementById('monthly-edit-old-team-groups-json')?.textContent ?? '[]');
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
      const membersCountId = `edit-team-group-members-count-${g}`;
      teamGroupsContainer.insertAdjacentHTML('beforeend', `
        <div class="card border rounded-3 p-3 mb-3 js-team-group" data-group-index="${g}">
          <div class="row g-2 align-items-end">
            <div class="col-12 col-md-4">
              <label class="form-label">اسم الفريق ${g + 1}</label>
              <input class="form-control" name="team_groups[${g}][team_name]" value="${esc(oldTeamGroups?.[g]?.team_name || ('فريق ' + (g + 1)))}">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">عدد أعضاء الفريق ${g + 1}</label>
              <input class="form-control js-team-members-count" id="${membersCountId}" type="number" min="1" max="30" value="${Math.max(1, oldTeamGroups?.[g]?.members?.length || 1)}">
            </div>
          </div>
          <div class="row g-2 mt-2 js-team-members-container"></div>
        </div>
      `);
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
            <div class="col-12 col-md-6"><input class="form-control" name="team_groups[${groupIndex}][members][${m}][member_name]" value="${esc(oldTeamGroups?.[groupIndex]?.members?.[m]?.member_name)}"></div>
            <div class="col-12 col-md-6"><input class="form-control" name="team_groups[${groupIndex}][members][${m}][role_desc]" value="${esc(oldTeamGroups?.[groupIndex]?.members?.[m]?.role_desc)}"></div>
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
    const outsideSelected = locType && locType.value === 'outside_center';
    inside.forEach(el => el.style.display = outsideSelected ? 'none' : 'block');
    outside.forEach(el => el.style.display = outsideSelected ? 'block' : 'none');
    outsideInputs.forEach((input) => {
      input.required = outsideSelected;
      input.disabled = !outsideSelected;
      if (!outsideSelected) input.value = '';
    });
    const selected = tg?.selectedOptions?.[0];
    const isOther = selected && selected.dataset.isOther === '1';
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
  tg?.addEventListener('change', toggle);
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

  const params = new URLSearchParams(window.location.search);
  if (params.get('mode') === 'post') {
    document.getElementById('post-execution-close')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
});
