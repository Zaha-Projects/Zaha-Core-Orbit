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
    const needsMedia = form.querySelector('.js-needs-media');
    const mediaFields = form.querySelectorAll('.js-media-fields');
    const needsCeremonyAgenda = form.querySelector('.js-needs-ceremony-agenda');
    const ceremonyAgendaFields = form.querySelectorAll('.js-ceremony-agenda-fields');
    const needsTransport = form.querySelector('.js-needs-transport');
    const transportFields = form.querySelectorAll('.js-transport-fields');
    const needsMaintenance = form.querySelector('.js-needs-maintenance');
    const maintenanceFields = form.querySelectorAll('.js-maintenance-fields');
    const needsGifts = form.querySelector('.js-needs-gifts');
    const giftsFields = form.querySelectorAll('.js-gifts-fields');
    const needsProgramsParticipation = form.querySelector('.js-needs-programs-participation');
    const programsParticipationFields = form.querySelectorAll('.js-programs-participation-fields');
    const needsCertificates = form.querySelector('.js-needs-certificates');
    const certificatesFields = form.querySelectorAll('.js-certificates-fields');
    const needsInvitations = form.querySelector('.js-needs-invitations');
    const invitationsFields = form.querySelectorAll('.js-invitations-fields');
    const invitationType = form.querySelector('.js-invitation-type');
    const invitationPaperFields = form.querySelectorAll('.js-invitation-paper-fields');
    const invitationElectronicFields = form.querySelectorAll('.js-invitation-electronic-fields');
    const suppliesCount = form.querySelector('.js-supplies-count');
    const suppliesContainer = form.querySelector('.js-supplies-container');
    const teamGroupsCount = form.querySelector('.js-team-groups-count');
    const teamGroupsContainer = form.querySelector('.js-team-groups-container');

    const oldPartners = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('monthly-form-old-partners-json', []) : JSON.parse(document.getElementById('monthly-form-old-partners-json')?.textContent ?? '[]');
    const oldSupplies = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('monthly-form-old-supplies-json', []) : JSON.parse(document.getElementById('monthly-form-old-supplies-json')?.textContent ?? '[]');
    const oldTeamGroups = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('monthly-form-old-team-groups-json', []) : JSON.parse(document.getElementById('monthly-form-old-team-groups-json')?.textContent ?? '[]');

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

    function setSectionInputsState(elements, isActive) {
        elements.forEach((element) => {
            element.querySelectorAll('input, select, textarea, button').forEach((field) => {
                const shouldSkip = field.classList.contains('js-partners-count')
                    || field.classList.contains('js-supplies-count')
                    || field.classList.contains('js-team-groups-count');
                if (shouldSkip) return;

                field.disabled = !isActive;
            });
        });
    }

    function toggleSection(elements, isActive) {
        toggleElements(elements, isActive);
        setSectionInputsState(elements, isActive);
    }

    function isEnabled(input) {
        if (!input) return false;
        if (input.type === 'checkbox') return !!input.checked;
        return String(input.value || '0') === '1';
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
        const active = isEnabled(needsVolunteers);
        toggleSection(volunteersFields, active);
        setRequiredState([
            '[name="required_volunteers"]',
            '[name="volunteer_age_from"]',
            '[name="volunteer_age_to"]',
            '[name="volunteer_gender"]',
            '[name="volunteer_tasks_summary"]'
        ], active);
    }

    function toggleCorrespondence() {
        const active = isEnabled(needsCorrespondence);
        toggleSection(correspondenceFields, active);
        setRequiredState([
            '[name="official_correspondence_reason"]',
            '[name="official_correspondence_target"]',
            '[name="official_correspondence_brief"]'
        ], active);
    }

    function toggleMedia() {
        toggleSection(mediaFields, isEnabled(needsMedia));
    }

    function toggleSupplies() {
        const active = isEnabled(needsSupplies);
        toggleSection(suppliesFields, active);
        if (suppliesCount) {
            suppliesCount.disabled = !active;
        }
    }

    function toggleSponsor() {
        toggleSection(sponsorFields, isEnabled(hasSponsor));
    }

    function togglePartners() {
        const active = isEnabled(hasPartners);
        toggleSection(partnersFields, active);
        if (partnersCount) {
            partnersCount.disabled = !active;
        }
    }

    function toggleCeremonyAgenda() {
        toggleSection(ceremonyAgendaFields, isEnabled(needsCeremonyAgenda));
    }

    function toggleTransport() {
        toggleSection(transportFields, isEnabled(needsTransport));
    }

    function toggleMaintenance() {
        toggleSection(maintenanceFields, isEnabled(needsMaintenance));
    }

    function toggleGifts() {
        toggleSection(giftsFields, isEnabled(needsGifts));
    }

    function toggleProgramsParticipation() {
        toggleSection(programsParticipationFields, isEnabled(needsProgramsParticipation));
    }

    function toggleCertificates() {
        toggleSection(certificatesFields, isEnabled(needsCertificates));
    }

    function toggleInvitations() {
        toggleSection(invitationsFields, isEnabled(needsInvitations));
        toggleInvitationTypeDetails();
    }

    function toggleInvitationTypeDetails() {
        const value = invitationType?.value || '';
        toggleSection(invitationPaperFields, value === 'paper');
        toggleSection(invitationElectronicFields, value === 'electronic');
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
                    <label class="form-label">آلية التأمين</label>
                    <select class="form-select js-supply-insurance" data-index="${i}" name="supplies[${i}][insurance_mechanism]">
                        <option value="">اختر</option>
                        <option value="purchase" ${(oldSupplies?.[i]?.insurance_mechanism === 'purchase') ? 'selected' : ''}>شراء</option>
                        <option value="support" ${(oldSupplies?.[i]?.insurance_mechanism === 'support') ? 'selected' : ''}>دعم</option>
                        <option value="other" ${(oldSupplies?.[i]?.insurance_mechanism === 'other') ? 'selected' : ''}>أخرى</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 js-supply-provider js-supply-other-details" data-index="${i}" style="${available || oldSupplies?.[i]?.insurance_mechanism !== 'other' ? 'display:none' : ''}">
                    <label class="form-label">تفاصيل أخرى</label>
                    <input class="form-control" name="supplies[${i}][insurance_other_details]" value="${esc(oldSupplies?.[i]?.insurance_other_details)}">
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

        suppliesContainer.querySelectorAll('.js-supply-insurance').forEach((select) => {
            select.addEventListener('change', function () {
                const detailsField = suppliesContainer.querySelector(`.js-supply-other-details[data-index="${this.dataset.index}"]`);
                if (!detailsField) return;
                detailsField.style.display = this.value === 'other' ? '' : 'none';
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
    needsMedia?.addEventListener('change', toggleMedia);
    needsSupplies?.addEventListener('change', toggleSupplies);
    hasSponsor?.addEventListener('change', toggleSponsor);
    hasPartners?.addEventListener('change', togglePartners);
    needsCeremonyAgenda?.addEventListener('change', toggleCeremonyAgenda);
    needsTransport?.addEventListener('change', toggleTransport);
    needsMaintenance?.addEventListener('change', toggleMaintenance);
    needsGifts?.addEventListener('change', toggleGifts);
    needsProgramsParticipation?.addEventListener('change', toggleProgramsParticipation);
    needsCertificates?.addEventListener('change', toggleCertificates);
    needsInvitations?.addEventListener('change', toggleInvitations);
    invitationType?.addEventListener('change', toggleInvitationTypeDetails);
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
    toggleMedia();
    toggleSupplies();
    toggleSponsor();
    togglePartners();
    toggleCeremonyAgenda();
    toggleTransport();
    toggleMaintenance();
    toggleGifts();
    toggleProgramsParticipation();
    toggleCertificates();
    toggleInvitations();
});
