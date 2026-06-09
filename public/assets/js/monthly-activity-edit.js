document.addEventListener('DOMContentLoaded', function () {
  const page = document.querySelector('.monthly-activity-edit-page');
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
    document.querySelector('[name="external_liaison_name"]'),
    document.querySelector('[name="external_liaison_phone"]'),
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

  const monthlyMapPreview = document.querySelector('.js-monthly-map-preview');
  const monthlyMapInput = document.querySelector('[name="outside_google_maps_url"]');
  const monthlyMapPlaceInput = document.querySelector('[name="outside_place_name"]');
  const monthlyMapAddressInput = document.querySelector('[name="outside_address"]');

  const filledMapValue = (value) => String(value ?? '').trim();
  const extractMapQuery = (rawUrl) => {
    const value = filledMapValue(rawUrl);
    if (!value) return '';

    const placeCoordinates = value.match(/!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)/);
    if (placeCoordinates) return `${placeCoordinates[1]},${placeCoordinates[2]}`;

    const atCoordinates = value.match(/@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)/);
    if (atCoordinates) return `${atCoordinates[1]},${atCoordinates[2]}`;

    try {
      const url = new URL(value);
      for (const key of ['q', 'query', 'destination', 'daddr', 'll']) {
        const paramValue = filledMapValue(url.searchParams.get(key));
        if (paramValue) return paramValue;
      }

      const placeMatch = url.pathname.match(/\/maps\/place\/([^/?]+)/);
      if (placeMatch) return decodeURIComponent(placeMatch[1].replace(/\+/g, ' '));
    } catch (error) {
      return '';
    }

    return '';
  };
  const fallbackMapQuery = () => [monthlyMapPlaceInput, monthlyMapAddressInput]
    .map((input) => filledMapValue(input?.value))
    .filter(Boolean)
    .join('، ');
  const updateMonthlyMapPreview = () => {
    if (!monthlyMapPreview) return;

    const rawUrl = filledMapValue(monthlyMapInput?.value);
    const query = extractMapQuery(rawUrl) || fallbackMapQuery();
    const openUrl = query
      ? `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(query)}`
      : rawUrl;
    const frame = monthlyMapPreview.querySelector('.js-monthly-map-frame');
    const openLink = monthlyMapPreview.querySelector('.js-monthly-map-open');

    if (openLink) {
      openLink.classList.toggle('d-none', !openUrl);
      if (openUrl) openLink.href = openUrl;
    }

    if (!frame) return;

    if (!query) {
      frame.innerHTML = `
        <div class="monthly-map-preview__empty js-monthly-map-empty">
          <i class="fas fa-map-marked-alt" aria-hidden="true"></i>
          <strong>${esc(monthlyMapPreview.dataset.emptyTitle || 'أدخل رابط Google Maps لعرض الموقع هنا')}</strong>
          <span>${esc(monthlyMapPreview.dataset.emptyMessage || 'سيظهر الموقع على الخريطة تلقائياً، ويمكن فتح الاتجاهات عبر Google Maps.')}</span>
        </div>
      `;
      return;
    }

    frame.innerHTML = `<iframe class="js-monthly-map-iframe" src="https://maps.google.com/maps?q=${encodeURIComponent(query)}&output=embed" title="${esc(monthlyMapPreview.dataset.previewLabel || 'معاينة موقع النشاط')}" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>`;
  };

  const setSectionState = (button, body, open, animate = true) => {
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
    button.classList.toggle('is-collapsed', !open);
    body.classList.toggle('is-open', open);

    if (!animate) {
      body.hidden = !open;
      body.style.maxHeight = open ? 'none' : '0px';
      return;
    }

    if (open) {
      body.hidden = false;
      body.style.maxHeight = '0px';
      window.requestAnimationFrame(() => {
        body.style.maxHeight = `${body.scrollHeight}px`;
      });
      window.setTimeout(() => {
        if (button.getAttribute('aria-expanded') === 'true') {
          body.style.maxHeight = 'none';
        }
      }, 240);
      return;
    }

    body.style.maxHeight = `${body.scrollHeight}px`;
    window.requestAnimationFrame(() => {
      body.style.maxHeight = '0px';
    });
    window.setTimeout(() => {
      if (button.getAttribute('aria-expanded') === 'false') {
        body.hidden = true;
      }
    }, 240);
  };

  const isFieldGroupHeading = (element) => element?.matches('.col-12')
    && !!element.querySelector(':scope > h2.h6, :scope > h3.h6');

  const makeToggleButton = (title, targetId, extraClass = '') => {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = `monthly-edit-section-toggle ${extraClass}`.trim();
    button.setAttribute('aria-controls', targetId);
    button.innerHTML = `
      <span class="monthly-edit-section-title">${esc(title)}</span>
      <span class="monthly-edit-section-icon" aria-hidden="true"></span>
    `;
    return button;
  };

  const isSubmitRow = (element) => element?.matches('.col-12')
    && (
      element.classList.contains('justify-content-end')
      || element.classList.contains('align-items-end')
      || !!element.querySelector(':scope > button[type="submit"], :scope > .btn[type="submit"]')
    );

  const enhanceTopLevelCards = () => {
    if (!page) return;

    let hasOpenedInitialCard = false;

    page.querySelectorAll(':scope > .event-card:not(:first-child) > .card-body').forEach((cardBody, index) => {
      if (cardBody.dataset.monthlyAccordionReady === '1') return;
      if (cardBody.closest('#post-execution-close')) return;

      const heading = Array.from(cardBody.children).find((child) => child.matches('h2.h6'));
      if (!heading) return;

      const body = document.createElement('div');
      body.className = 'monthly-edit-card-section-body';
      body.id = `monthly-edit-card-section-${index + 1}`;

      let cursor = heading.nextElementSibling;
      while (cursor) {
        const next = cursor.nextElementSibling;
        body.appendChild(cursor);
        cursor = next;
      }

      const button = makeToggleButton(heading.textContent.trim(), body.id, 'monthly-edit-card-toggle');
      heading.replaceWith(button);
      cardBody.appendChild(body);
      const shouldOpen = !hasOpenedInitialCard;
      setSectionState(button, body, shouldOpen, false);
      hasOpenedInitialCard = true;
      button.addEventListener('click', () => setSectionState(button, body, button.getAttribute('aria-expanded') !== 'true'));
      cardBody.dataset.monthlyAccordionReady = '1';
    });
  };

  const enhancePostExecutionCloseSections = () => {
    const closeForm = document.querySelector('#post-execution-close form.row');
    if (!closeForm || closeForm.dataset.monthlyPanelsReady === '1') return;

    const createPanel = (title, panelIndex, beforeElement) => {
      const panel = document.createElement('div');
      const body = document.createElement('div');
      const button = makeToggleButton(title, `monthly-post-close-panel-${panelIndex}`, 'monthly-edit-form-toggle');

      panel.className = 'monthly-edit-form-panel monthly-edit-post-panel col-12';
      body.className = 'monthly-edit-form-panel-body row g-3';
      body.id = `monthly-post-close-panel-${panelIndex}`;

      closeForm.insertBefore(panel, beforeElement);
      panel.appendChild(button);
      panel.appendChild(body);
      setSectionState(button, body, panelIndex === 1, false);
      button.addEventListener('click', () => setSectionState(button, body, button.getAttribute('aria-expanded') !== 'true'));

      return body;
    };

    let panelIndex = 1;
    let body = createPanel('بيانات الإغلاق بعد التنفيذ', panelIndex, Array.from(closeForm.children).find((child) => !child.matches('input[type="hidden"]')));
    let cursor = body.parentElement.nextElementSibling;

    while (cursor) {
      const next = cursor.nextElementSibling;

      if (isFieldGroupHeading(cursor)) {
        const heading = cursor.querySelector(':scope > h2.h6, :scope > h3.h6');
        panelIndex += 1;
        body = createPanel(heading.textContent.trim(), panelIndex, cursor);
        cursor.remove();
      } else if (cursor.matches('.col-12') && cursor.querySelector(':scope > hr')) {
        cursor.remove();
      } else if (isSubmitRow(cursor)) {
        cursor.classList.add('monthly-edit-submit-row');
      } else if (!cursor.matches('input[type="hidden"]')) {
        body.appendChild(cursor);
      }

      cursor = next;
    }

    closeForm.dataset.monthlyPanelsReady = '1';
  };

  const enhancePlanningFormSections = () => {
    const planningForm = page?.classList.contains('monthly-planning-edit-page')
      ? page.querySelector(':scope > .event-card:not(:first-child) form.row')
      : null;
    if (!planningForm || planningForm.dataset.monthlyPanelsReady === '1') return;

    const children = Array.from(planningForm.children);
    let panelIndex = 0;

    children.forEach((child) => {
      const heading = isFieldGroupHeading(child) ? child.querySelector(':scope > h2.h6, :scope > h3.h6') : null;
      if (!heading) return;

      const panel = document.createElement('div');
      const body = document.createElement('div');
      const button = makeToggleButton(heading.textContent.trim(), `monthly-edit-form-panel-${panelIndex + 1}`, 'monthly-edit-form-toggle');

      panel.className = 'monthly-edit-form-panel col-12';
      body.className = 'monthly-edit-form-panel-body row g-3';
      body.id = `monthly-edit-form-panel-${panelIndex + 1}`;

      child.replaceWith(panel);
      panel.appendChild(button);
      panel.appendChild(body);

      let cursor = panel.nextElementSibling;
      while (cursor) {
        const next = cursor.nextElementSibling;
        const cursorHeading = isFieldGroupHeading(cursor) ? cursor.querySelector(':scope > h2.h6, :scope > h3.h6') : null;

        if (cursorHeading || isSubmitRow(cursor)) break;

        if (cursor.matches('hr')) {
          cursor.remove();
        } else {
          body.appendChild(cursor);
        }

        cursor = next;
      }

      setSectionState(button, body, panelIndex === 0, false);
      button.addEventListener('click', () => setSectionState(button, body, button.getAttribute('aria-expanded') !== 'true'));
      panelIndex += 1;
    });

    planningForm.dataset.monthlyPanelsReady = '1';
  };

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
      const available = String(oldSupplies?.[i]?.available ?? '1') === '1';
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
    updateMonthlyMapPreview();
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
  [monthlyMapInput, monthlyMapPlaceInput, monthlyMapAddressInput].forEach((input) => {
    input?.addEventListener('input', updateMonthlyMapPreview);
    input?.addEventListener('change', updateMonthlyMapPreview);
  });
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
  enhanceTopLevelCards();
  enhancePostExecutionCloseSections();
  enhancePlanningFormSections();

  const params = new URLSearchParams(window.location.search);
  if (params.get('mode') === 'post') {
    const closeSection = document.getElementById('post-execution-close');
    closeSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
});
