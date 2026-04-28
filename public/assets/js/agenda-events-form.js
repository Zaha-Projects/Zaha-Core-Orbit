(function () {
    const form = document.querySelector('.agenda-form');
    if (!form) return;

    const participantLabel = form.dataset.labelParticipant || '';
    const notParticipantLabel = form.dataset.labelNotParticipant || '';

    const categoryEl = form.querySelector('#event_category_id');
    const eventTypeEl = form.querySelector('.js-event-type');
    const planTypeEl = form.querySelector('.js-plan-type');
    const unifiedPlanSourceRows = form.querySelectorAll('.js-unified-plan-source');
    const branchParticipationSection = form.querySelector('.js-branch-participation-section');
    const ownerDepartmentEl = form.querySelector('.js-owner-department');
    const partnerDepartmentEls = Array.from(form.querySelectorAll('.js-partner-department'));
    const toggleRows = Array.from(form.querySelectorAll('.branch-toggle-item'));
    const enableAllBtn = form.querySelector('.js-enable-all-participants');
    const openUnifiedModalBtn = form.querySelector('.js-open-unified-monthly-plan-modal');
    const unifiedModalEl = document.getElementById('unifiedMonthlyPlanModal');
    const confirmUnifiedModalBtn = unifiedModalEl?.querySelector('.js-confirm-unified-monthly-plan');
    const unifiedModalError = unifiedModalEl?.querySelector('.js-unified-monthly-plan-errors');
    const hiddenTemplateFields = Array.from(form.querySelectorAll('[data-monthly-plan-template-field]'));
    const modalTemplateFields = Array.from(unifiedModalEl?.querySelectorAll('.js-unified-monthly-plan-input') || []);
    const requiredTemplateFields = ['title', 'proposed_date', 'responsible_party', 'target_group', 'description'];
    let allowSubmitAfterModalConfirm = false;
    const unifiedModal = (window.bootstrap?.Modal && unifiedModalEl)
        ? new window.bootstrap.Modal(unifiedModalEl)
        : null;

    function filterCategories() {
        if (!categoryEl) return;

        const selectedDepartments = new Set(
            partnerDepartmentEls
                .filter((el) => el.checked)
                .map((el) => String(el.value))
        );

        if (ownerDepartmentEl?.value) {
            selectedDepartments.add(String(ownerDepartmentEl.value));
        }

        Array.from(categoryEl.options).forEach((option) => {
            const categoryDepartmentId = option.dataset.departmentId;
            if (!categoryDepartmentId) {
                option.hidden = false;
                return;
            }
            option.hidden = !selectedDepartments.has(String(categoryDepartmentId));
        });

        if (categoryEl.selectedOptions[0]?.hidden) {
            categoryEl.value = '';
        }
    }

    function syncOwnerVsPartners() {
        const ownerId = String(ownerDepartmentEl?.value || '');
        partnerDepartmentEls.forEach((el) => {
            const isOwner = ownerId !== '' && String(el.value) === ownerId;
            if (isOwner) {
                el.checked = false;
            }
            el.disabled = isOwner;
            el.closest('.partner-department-item')?.classList.toggle('opacity-50', isOwner);
        });
    }

    function togglePlanFile() {
        const isUnified = planTypeEl?.value === 'unified';

        unifiedPlanSourceRows.forEach((row) => {
            row.style.display = isUnified ? '' : 'none';
        });

        if (!isUnified) {
            allowSubmitAfterModalConfirm = true;
        }
    }

    function hiddenFieldByKey(fieldKey) {
        return hiddenTemplateFields.find((field) => field.dataset.monthlyPlanTemplateField === fieldKey) || null;
    }

    function modalFieldByKey(fieldKey) {
        return modalTemplateFields.find((field) => field.dataset.targetField === fieldKey) || null;
    }

    function syncModalFromHiddenFields() {
        modalTemplateFields.forEach((field) => {
            const hidden = hiddenFieldByKey(field.dataset.targetField || '');
            if (hidden) {
                field.value = hidden.value || '';
            }
        });
    }

    function syncHiddenFieldsFromModal() {
        modalTemplateFields.forEach((field) => {
            const hidden = hiddenFieldByKey(field.dataset.targetField || '');
            if (hidden) {
                hidden.value = field.value || '';
            }
        });
    }

    function validateModalFields() {
        let valid = true;

        requiredTemplateFields.forEach((key) => {
            const field = modalFieldByKey(key);
            const fieldValid = String(field?.value || '').trim().length > 0;
            field?.classList.toggle('is-invalid', !fieldValid);
            valid = valid && fieldValid;
        });

        unifiedModalError?.classList.toggle('d-none', valid);

        return valid;
    }

    function toggleBranchParticipation() {
        const isOptional = eventTypeEl?.value === 'optional';
        if (branchParticipationSection) {
            branchParticipationSection.style.display = isOptional ? 'none' : '';
        }

        toggleRows.forEach((row) => {
            const hiddenInput = row.querySelector('.js-branch-status-hidden');
            const checkbox = row.querySelector('.js-branch-toggle');
            if (hiddenInput) hiddenInput.disabled = isOptional;
            if (checkbox) checkbox.disabled = isOptional;
        });

        if (enableAllBtn) {
            enableAllBtn.disabled = isOptional;
        }
    }

    function syncToggleRow(row) {
        const checkbox = row.querySelector('.js-branch-toggle');
        const hiddenInput = row.querySelector('.js-branch-status-hidden');
        const label = row.querySelector('.form-check-label');
        const isOn = !!checkbox?.checked;

        if (hiddenInput) {
            hiddenInput.value = isOn ? 'participant' : 'not_participant';
        }
        if (label) {
            label.textContent = isOn
                ? participantLabel
                : notParticipantLabel;
        }
    }

    ownerDepartmentEl?.addEventListener('change', () => {
        syncOwnerVsPartners();
        filterCategories();
    });
    partnerDepartmentEls.forEach((el) => el.addEventListener('change', filterCategories));
    planTypeEl?.addEventListener('change', togglePlanFile);
    eventTypeEl?.addEventListener('change', toggleBranchParticipation);
    planTypeEl?.addEventListener('change', () => {
        allowSubmitAfterModalConfirm = planTypeEl.value !== 'unified';
    });

    toggleRows.forEach((row) => {
        const checkbox = row.querySelector('.js-branch-toggle');
        checkbox?.addEventListener('change', () => syncToggleRow(row));
        syncToggleRow(row);
    });

    enableAllBtn?.addEventListener('click', () => {
        toggleRows.forEach((row) => {
            const checkbox = row.querySelector('.js-branch-toggle');
            if (checkbox) checkbox.checked = true;
            syncToggleRow(row);
        });
    });

    openUnifiedModalBtn?.addEventListener('click', () => {
        if (planTypeEl?.value !== 'unified' || !unifiedModal) return;
        syncModalFromHiddenFields();
        unifiedModalError?.classList.add('d-none');
        modalTemplateFields.forEach((field) => field.classList.remove('is-invalid'));
        unifiedModal.show();
    });

    confirmUnifiedModalBtn?.addEventListener('click', () => {
        if (!validateModalFields()) return;
        syncHiddenFieldsFromModal();
        allowSubmitAfterModalConfirm = true;
        unifiedModal?.hide();
        form.requestSubmit();
    });

    form.addEventListener('submit', (event) => {
        const isUnified = planTypeEl?.value === 'unified';
        if (!isUnified || allowSubmitAfterModalConfirm || !unifiedModal) {
            allowSubmitAfterModalConfirm = false;
            return;
        }

        event.preventDefault();
        syncModalFromHiddenFields();
        unifiedModalError?.classList.add('d-none');
        modalTemplateFields.forEach((field) => field.classList.remove('is-invalid'));
        unifiedModal.show();
    });

    syncOwnerVsPartners();
    filterCategories();
    togglePlanFile();
    toggleBranchParticipation();
    allowSubmitAfterModalConfirm = planTypeEl?.value !== 'unified';
})();
