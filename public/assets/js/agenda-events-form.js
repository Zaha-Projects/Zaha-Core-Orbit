(function () {
    const form = document.querySelector('.agenda-form');
    if (!form) return;

    const participantLabel = form.dataset.labelParticipant || '';
    const notParticipantLabel = form.dataset.labelNotParticipant || '';

    const categoryEl = form.querySelector('#event_category_id');
    const planTypeEl = form.querySelector('.js-plan-type');
    const unifiedPlanSourceEl = form.querySelector('.js-unified-plan-source-select');
    const unifiedPlanSourceRows = form.querySelectorAll('.js-unified-plan-source');
    const planFileRows = form.querySelectorAll('.js-agenda-plan-file');
    const ownerDepartmentEl = form.querySelector('.js-owner-department');
    const partnerDepartmentEls = Array.from(form.querySelectorAll('.js-partner-department'));
    const toggleRows = Array.from(form.querySelectorAll('.branch-toggle-item'));
    const enableAllBtn = form.querySelector('.js-enable-all-participants');

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
        const wantsFileUpload = unifiedPlanSourceEl?.value === 'upload_file';

        unifiedPlanSourceRows.forEach((row) => {
            row.style.display = isUnified ? '' : 'none';
        });

        planFileRows.forEach((row) => {
            row.style.display = (isUnified && wantsFileUpload) ? '' : 'none';
        });
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
    unifiedPlanSourceEl?.addEventListener('change', togglePlanFile);

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

    syncOwnerVsPartners();
    filterCategories();
    togglePlanFile();
})();
