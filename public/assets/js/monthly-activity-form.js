document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.monthly-plan-form');
    if (!form) return;

    const activityDateInput = form.querySelector('.js-activity-date');
    const proposedDateInput = form.querySelector('.js-proposed-date');
    const locationType = form.querySelector('.js-location-type');
    const insideLocationFields = form.querySelectorAll('.js-inside-location');
    const outsideLocationFields = form.querySelectorAll('.js-outside-location');
    const targetGroupCheckboxes = form.querySelectorAll('.js-target-group-checkbox');
    const targetGroupOtherFields = form.querySelectorAll('.js-target-group-other');
    const needsVolunteers = form.querySelector('.js-needs-volunteers');
    const volunteersFields = form.querySelectorAll('.js-volunteers-fields');
    const needsCorrespondence = form.querySelector('.js-needs-letters');
    const correspondenceFields = form.querySelectorAll('.js-correspondence-fields');
    const needsSupplies = form.querySelector('.js-needs-supplies');
    const suppliesFields = form.querySelectorAll('.js-supplies-fields');
    const hasSponsor = form.querySelector('.js-has-sponsor');
    const sponsorFields = form.querySelectorAll('.js-sponsor-fields');
    const hasPartners = form.querySelector('.js-has-partners');
    const partnersFields = form.querySelectorAll('.js-partners-fields');
    const partnersCount = form.querySelector('.js-partners-count');
    const partnersContainer = form.querySelector('.js-partners-container');
    const needsOfficialLetters = form.querySelector('.js-needs-official-letters');
    const officialLettersFields = form.querySelectorAll('.js-official-letters-fields');
    const needsMedia = form.querySelector('.js-needs-media');
    const mediaFields = form.querySelectorAll('.js-media-fields');
    const suppliesCount = form.querySelector('.js-supplies-count');
    const suppliesContainer = form.querySelector('.js-supplies-container');
    const teamGroupsCount = form.querySelector('.js-team-groups-count');
    const teamGroupsContainer = form.querySelector('.js-team-groups-container');

    const oldPartners = JSON.parse(document.getElementById('monthly-form-old-partners-json')?.textContent ?? '[]');
    const oldSupplies = JSON.parse(document.getElementById('monthly-form-old-supplies-json')?.textContent ?? '[]');
    const oldTeamGroups = JSON.parse(document.getElementById('monthly-form-old-team-groups-json')?.textContent ?? '[]');

    const esc = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    function syncActivityDate() {
        if (activityDateInput && proposedDateInput) {
            activityDateInput.value = proposedDateInput.value || '';
        }
    }

    function toggleElements(elements, isVisible) {
        elements.forEach((element) => {
            element.style.display = isVisible ? '' : 'none';
        });
    }

    function setRequiredState(selectors, isRequired) {
        selectors.forEach((selector) => {
            const input = form.querySelector(selector);
            if (!input) return;
            input.disabled = !isRequired;
            input.required = isRequired;

            if (!isRequired && !['checkbox', 'radio', 'file'].includes(input.type)) {
                input.value = '';
            }
        });
    }

    function toggleLocationFields() {
        const isOutside = locationType?.value === 'outside_center';
        toggleElements(insideLocationFields, !isOutside);
        toggleElements(outsideLocationFields, isOutside);
        setRequiredState([
            '[name="outside_place_name"]',
            '[name="outside_google_maps_url"]',
            '[name="outside_contact_number"]',
            '[name="external_liaison_name"]',
            '[name="external_liaison_phone"]',
            '[name="outside_address"]'
        ], isOutside);
    }

    function toggleTargetGroupOther() {
        const hasOther = Array.from(targetGroupCheckboxes).some((checkbox) => checkbox.checked && checkbox.dataset.isOther === '1');
        toggleElements(targetGroupOtherFields, hasOther);
        setRequiredState(['[name="target_group_other"]'], hasOther);
    }

    function toggleVolunteers() {
        const active = !!needsVolunteers?.checked;
        toggleElements(volunteersFields, active);
        setRequiredState([
            '[name="required_volunteers"]',
            '[name="volunteer_age_range"]',
            '[name="volunteer_gender"]',
            '[name="volunteer_tasks_summary"]'
        ], active);
    }

    function toggleCorrespondence() {
        const active = !!needsCorrespondence?.checked;
        toggleElements(correspondenceFields, active);
        setRequiredState([
            '[name="official_correspondence_reason"]',
            '[name="official_correspondence_target"]',
            '[name="official_correspondence_brief"]'
        ], active);
    }

    function toggleOfficialLetters() {
        toggleElements(officialLettersFields, !!needsOfficialLetters?.checked);
    }

    function toggleMedia() {
        toggleElements(mediaFields, !!needsMedia?.checked);
    }

    function toggleSupplies() {
        const active = !!needsSupplies?.checked;
        toggleElements(suppliesFields, active);
        if (suppliesCount) {
            suppliesCount.disabled = !active;
        }
    }

    function toggleSponsor() {
        toggleElements(sponsorFields, !!hasSponsor?.checked);
    }

    function togglePartners() {
        const active = !!hasPartners?.checked;
        toggleElements(partnersFields, active);
        if (partnersCount) {
            partnersCount.disabled = !active;
        }
    }

    function renderPartners() {
        if (!partnersContainer) return;

        const count = Math.max(1, Math.min(10, parseInt(partnersCount?.value || '1', 10)));
        partnersContainer.innerHTML = '';

        for (let i = 0; i < count; i += 1) {
            partnersContainer.insertAdjacentHTML('beforeend', `
                <div class="col-12 col-md-4">
                    <label class="form-label">اسم الشريك ${i + 1}</label>
                    <input class="form-control" name="partners[${i}][name]" value="${esc(oldPartners?.[i]?.name)}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">دور الشريك ${i + 1}</label>
                    <input class="form-control" name="partners[${i}][role]" value="${esc(oldPartners?.[i]?.role)}">
                </div>
                <div class="col-12 col-md-4">
                    <label class="form-label">بيانات التواصل</label>
                    <input class="form-control" name="partners[${i}][contact_info]" value="${esc(oldPartners?.[i]?.contact_info)}">
                </div>
            `);
        }
    }

    function renderSupplies() {
        if (!suppliesContainer) return;

        const count = Math.max(1, Math.min(20, parseInt(suppliesCount?.value || '1', 10)));
        suppliesContainer.innerHTML = '';

        for (let i = 0; i < count; i += 1) {
            const available = String(oldSupplies?.[i]?.available ?? '1') === '1';
            suppliesContainer.insertAdjacentHTML('beforeend', `
                <div class="col-12 col-md-4">
                    <label class="form-label">اسم المستلزم ${i + 1}</label>
                    <input class="form-control" name="supplies[${i}][item_name]" value="${esc(oldSupplies?.[i]?.item_name)}">
                </div>
                <div class="col-12 col-md-2">
                    <label class="form-label">التوفر</label>
                    <select class="form-select js-supply-available" data-index="${i}" name="supplies[${i}][available]">
                        <option value="1" ${available ? 'selected' : ''}>متوفر</option>
                        <option value="0" ${available ? '' : 'selected'}>غير متوفر</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 js-supply-provider" data-index="${i}" style="${available ? 'display:none' : ''}">
                    <label class="form-label">نوع المسؤول</label>
                    <select class="form-select" name="supplies[${i}][provider_type]">
                        <option value="">اختر</option>
                        <option value="volunteer" ${(oldSupplies?.[i]?.provider_type === 'volunteer') ? 'selected' : ''}>متطوع</option>
                        <option value="person" ${(oldSupplies?.[i]?.provider_type === 'person') ? 'selected' : ''}>شخص</option>
                        <option value="partner" ${(oldSupplies?.[i]?.provider_type === 'partner') ? 'selected' : ''}>شريك</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 js-supply-provider" data-index="${i}" style="${available ? 'display:none' : ''}">
                    <label class="form-label">اسم المسؤول</label>
                    <input class="form-control" name="supplies[${i}][provider_name]" value="${esc(oldSupplies?.[i]?.provider_name)}">
                </div>
            `);
        }

        suppliesContainer.querySelectorAll('.js-supply-available').forEach((select) => {
            select.addEventListener('change', function () {
                const providers = suppliesContainer.querySelectorAll(`.js-supply-provider[data-index="${this.dataset.index}"]`);
                providers.forEach((provider) => {
                    provider.style.display = this.value === '1' ? 'none' : '';
                });
            });
        });
    }

    function renderTeamGroups() {
        if (!teamGroupsContainer) return;

        const count = Math.max(1, Math.min(10, parseInt(teamGroupsCount?.value || '1', 10)));
        teamGroupsContainer.innerHTML = '';

        for (let g = 0; g < count; g += 1) {
            teamGroupsContainer.insertAdjacentHTML('beforeend', `
                <div class="border rounded-3 p-3 mb-3 js-team-group" data-group-index="${g}">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-md-4">
                            <label class="form-label">اسم الفريق ${g + 1}</label>
                            <input class="form-control" name="team_groups[${g}][team_name]" value="${esc(oldTeamGroups?.[g]?.team_name || ('فريق ' + (g + 1)))}">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">عدد الأعضاء</label>
                            <input class="form-control js-team-members-count" type="number" min="1" max="20" value="${Math.max(1, oldTeamGroups?.[g]?.members?.length || 1)}">
                        </div>
                    </div>
                    <div class="row g-3 mt-1 js-team-members-container"></div>
                </div>
            `);
        }

        teamGroupsContainer.querySelectorAll('.js-team-group').forEach((groupEl) => {
            const groupIndex = parseInt(groupEl.dataset.groupIndex || '0', 10);
            const countInput = groupEl.querySelector('.js-team-members-count');
            const membersContainer = groupEl.querySelector('.js-team-members-container');

            const renderMembers = () => {
                const membersCount = Math.max(1, Math.min(20, parseInt(countInput?.value || '1', 10)));
                membersContainer.innerHTML = '';

                for (let m = 0; m < membersCount; m += 1) {
                    membersContainer.insertAdjacentHTML('beforeend', `
                        <div class="col-12 col-md-6">
                            <label class="form-label">اسم العضو ${m + 1}</label>
                            <input class="form-control" name="team_groups[${groupIndex}][members][${m}][member_name]" value="${esc(oldTeamGroups?.[groupIndex]?.members?.[m]?.member_name)}">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">الدور / المهمة</label>
                            <input class="form-control" name="team_groups[${groupIndex}][members][${m}][role_desc]" value="${esc(oldTeamGroups?.[groupIndex]?.members?.[m]?.role_desc)}">
                        </div>
                    `);
                }
            };

            countInput?.addEventListener('input', renderMembers);
            renderMembers();
        });
    }

    proposedDateInput?.addEventListener('change', syncActivityDate);
    locationType?.addEventListener('change', toggleLocationFields);
    targetGroupCheckboxes.forEach((checkbox) => checkbox.addEventListener('change', toggleTargetGroupOther));
    needsVolunteers?.addEventListener('change', toggleVolunteers);
    needsCorrespondence?.addEventListener('change', toggleCorrespondence);
    needsOfficialLetters?.addEventListener('change', toggleOfficialLetters);
    needsMedia?.addEventListener('change', toggleMedia);
    needsSupplies?.addEventListener('change', toggleSupplies);
    hasSponsor?.addEventListener('change', toggleSponsor);
    hasPartners?.addEventListener('change', togglePartners);
    partnersCount?.addEventListener('input', renderPartners);
    suppliesCount?.addEventListener('input', renderSupplies);
    teamGroupsCount?.addEventListener('input', renderTeamGroups);

    syncActivityDate();
    renderPartners();
    renderSupplies();
    renderTeamGroups();
    toggleLocationFields();
    toggleTargetGroupOther();
    toggleVolunteers();
    toggleCorrespondence();
    toggleOfficialLetters();
    toggleMedia();
    toggleSupplies();
    toggleSponsor();
    togglePartners();
});
