document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-tables]');
    const count = document.querySelector('[data-table-count]');
    const template = document.querySelector('#bazaar-table-template');
    if (!container || !count || !template) return;
    const updateCardSummary = card => {
        const organization = ['institution', 'association', 'organization'].includes(card.querySelector('.rental-type')?.value);
        const tenant = organization ? card.querySelector('.organization-search')?.value : card.querySelector('.tenant-name')?.value;
        const amount = Number(card.querySelector('.rental-amount')?.value || 0).toFixed(2);
        const complete = Boolean(tenant && card.querySelector('.material-description')?.value && card.querySelector('.liaison-select')?.value);
        const renterBadge = card.querySelector('[data-renter-badge]');
        renterBadge.innerHTML = organization ? '<i class="fas fa-building"></i> جهة' : '<i class="fas fa-user"></i> فردي';
        renterBadge.classList.toggle('is-organization', organization);
        card.querySelector('[data-summary-tenant]').textContent = tenant || 'لم يحدد المستأجر';
        card.querySelector('[data-summary-rent]').textContent = `${amount} د.أ`;
        const completion = card.querySelector('[data-completion-badge]');
        completion.innerHTML = complete ? '<i class="fas fa-circle-check"></i> مكتملة' : '<i class="fas fa-circle-half-stroke"></i> غير مكتملة';
        completion.classList.toggle('is-complete', complete);
    };
    const toggleTenantFields = card => {
        const organization = ['institution', 'association', 'organization'].includes(card.querySelector('.rental-type')?.value);
        card.querySelectorAll('.individual-field').forEach(el => el.hidden = organization);
        card.querySelectorAll('.organization-field').forEach(el => el.hidden = !organization);
        updateCardSummary(card);
    };
    const rebuild = () => {
        const wanted = Math.max(1, Math.min(500, Number(count.value) || 1));
        while (container.children.length < wanted) {
            const index = container.children.length;
            const html = template.innerHTML.replaceAll('__INDEX__', String(index)).replaceAll('__NUMBER__', String(index + 1));
            container.insertAdjacentHTML('beforeend', html);
        }
        while (container.children.length > wanted) container.lastElementChild.remove();
        [...container.children].forEach((card, index) => {
            card.querySelector('[data-card-number]').textContent = String(index + 1);
            card.querySelector('[data-table-number]').value = String(index + 1);
            toggleTenantFields(card);
        });
    };
    count.addEventListener('change', rebuild);
    container.addEventListener('change', event => {
        const card = event.target.closest('.bazaar-table-card');
        if (event.target.matches('.rental-type')) toggleTenantFields(card);
        if (card) updateCardSummary(card);
    });
    container.addEventListener('input', event => {
        const card = event.target.closest('.bazaar-table-card');
        if (card) updateCardSummary(card);
    });
    let searchTimer;
    container.addEventListener('input', event => {
        if (!event.target.matches('.organization-search')) return;
        const picker = event.target.closest('.organization-picker');
        clearTimeout(searchTimer);
        searchTimer = setTimeout(async () => {
            const response = await fetch(`${picker.dataset.searchUrl}?q=${encodeURIComponent(event.target.value)}`, {headers: {Accept: 'application/json'}});
            const body = await response.json();
            const results = picker.querySelector('.organization-results');
            results.innerHTML = (body.data || []).map(item => `<button type="button" class="list-group-item list-group-item-action organization-result" data-item='${JSON.stringify(item).replaceAll("'", '&#39;')}'>${item.name}<small class="d-block">${item.contact_name || ''} ${item.contact_phone || ''}</small></button>`).join('');
        }, 250);
    });
    container.addEventListener('click', async event => {
        const result = event.target.closest('.organization-result');
        if (result) {
            const picker = result.closest('.organization-picker');
            const item = JSON.parse(result.dataset.item);
            picker.querySelector('[data-organization-id]').value = item.id;
            picker.querySelector('.organization-search').value = item.name;
            picker.querySelector('.organization-details').textContent = [item.contact_name, item.contact_phone, item.location_name, item.address].filter(Boolean).join(' · ') || 'لا توجد تفاصيل محفوظة';
            picker.querySelector('.organization-results').innerHTML = '';
            updateCardSummary(picker.closest('.bazaar-table-card'));
        }
        if (!event.target.matches('.organization-add')) return;
        const picker = event.target.closest('.organization-picker');
        const name = window.prompt('اسم المؤسسة / الجمعية');
        if (!name) return;
        const contactPhone = window.prompt('رقم التواصل (مطلوب)');
        if (!contactPhone) return;
        const response = await fetch(picker.dataset.createUrl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}, body: JSON.stringify({name, contact_phone: contactPhone})});
        const body = await response.json();
        if (!response.ok) { window.alert(body.message || 'تعذر إضافة الجهة.'); return; }
        picker.querySelector('[data-organization-id]').value = body.data.id;
        picker.querySelector('.organization-search').value = body.data.name;
        picker.querySelector('.organization-details').textContent = [body.data.contact_name, body.data.contact_phone].filter(Boolean).join(' · ');
        updateCardSummary(picker.closest('.bazaar-table-card'));
    });
    const locationType = document.querySelector('[data-location-type]');
    const locationLabel = document.querySelector('[data-location-label]');
    const syncLocation = () => {
        const outside = ['outside', 'outside_center'].includes(locationType?.value);
        locationLabel.textContent = outside ? 'اسم الموقع الخارجي' : 'المكان داخل المركز';
        document.querySelector('[data-location-details-label]').textContent = outside ? 'العنوان / التفاصيل' : 'تفاصيل الموقع';
        ['[data-location-name-field]', '[data-location-details-field]'].forEach(selector => {
            const wrapper = document.querySelector(selector);
            const show = selector === '[data-location-name-field]' || outside;
            wrapper.hidden = !show;
            wrapper.querySelectorAll('input, textarea').forEach(field => field.disabled = !show);
            wrapper.classList.toggle('col-md-8', outside);
            wrapper.classList.toggle('col-md-4', !outside);
        });
        const map = document.querySelector('[data-location-map-field]');
        map.hidden = !outside;
        map.querySelector('input').disabled = !outside;
    };
    locationType?.addEventListener('change', syncLocation);
    syncLocation();
    [...container.children].forEach(toggleTenantFields);
    const form = container.closest('form');
    const teamContainer = form.querySelector('[data-team-groups]');
    const teamCount = form.querySelector('[data-team-count]');
    const resize = (parent, requested, max, render) => {
        const wanted = Math.max(1, Math.min(max, Number(requested) || 1));
        while (parent.children.length < wanted) parent.insertAdjacentHTML('beforeend', render(parent.children.length));
        while (parent.children.length > wanted) parent.lastElementChild.remove();
    };
    teamCount?.addEventListener('change', () => {
        resize(teamContainer, teamCount.value, 10, index => document.querySelector('#bazaar-team-template').innerHTML.replaceAll('__GROUP__', String(index)));
        teamCount.value = teamContainer.children.length;
    });
    teamContainer?.addEventListener('change', event => {
        if (!event.target.matches('[data-member-count]')) return;
        const group = event.target.closest('[data-team-group]');
        const members = group.querySelector('[data-team-members]');
        resize(members, event.target.value, 20, index => document.querySelector('#bazaar-member-template').innerHTML.replaceAll('__GROUP__', group.dataset.teamGroup).replaceAll('__MEMBER__', String(index)));
        event.target.value = members.children.length;
    });
    // Execution needs (Monthly pattern): activation switches first, detail cards below.
    // Hidden or disabled detail fields are not submitted; the server also drops stale values.
    const setFieldsEnabled = (wrapper, enabled) => {
        wrapper.hidden = !enabled;
        wrapper.querySelectorAll('input, select, textarea').forEach(field => { field.disabled = !enabled; });
    };
    const reviewNeeds = form.querySelector('[data-review-needs]');
    const renderReviewNeeds = () => {
        if (!reviewNeeds) return;
        const items = [];
        form.querySelectorAll('[data-need-toggle]').forEach(toggle => {
            if (!toggle.checked) return;
            const option = toggle.closest('[data-need-option]');
            const details = form.querySelector(`[data-need-details="${toggle.dataset.needToggle}"]`);
            const parts = [];
            const availability = details?.querySelector('[data-need-availability]');
            if (availability) parts.push(`${reviewNeeds.dataset.labelAvailability}: ${availability.selectedOptions[0].textContent.trim()}`);
            const cardType = details?.querySelector('[data-invitation-card-type]');
            if (cardType && cardType.value) {
                parts.push(`${reviewNeeds.dataset.labelCardType}: ${cardType.selectedOptions[0].textContent.trim()}`);
                const channels = [...details.querySelectorAll('[data-invitation-channel]:checked:not(:disabled)')]
                    .map(input => input.closest('label').textContent.trim());
                if (channels.length) parts.push(`${reviewNeeds.dataset.labelChannels}: ${channels.join('، ')}`);
            }
            const li = document.createElement('li');
            const name = document.createElement('strong');
            name.textContent = option.querySelector('[data-need-name]').textContent.trim();
            const badge = document.createElement('span');
            badge.className = 'badge ms-2 ' + (toggle.disabled ? 'bazaar-mandatory-badge' : 'bazaar-enabled-badge');
            badge.textContent = toggle.disabled ? reviewNeeds.dataset.labelMandatory : reviewNeeds.dataset.labelEnabled;
            li.append(name, badge);
            if (parts.length) {
                const meta = document.createElement('small');
                meta.className = 'd-block';
                meta.textContent = parts.join(' · ');
                li.append(meta);
            }
            items.push(li);
        });
        reviewNeeds.replaceChildren(...items);
        if (!items.length) {
            const li = document.createElement('li');
            li.textContent = reviewNeeds.dataset.labelEmpty;
            reviewNeeds.append(li);
        }
    };
    const syncInvitationCards = details => {
        const group = details.querySelector('[data-invitation-cards]');
        if (!group || details.hidden) return;
        const type = group.querySelector('[data-invitation-card-type]').value;
        group.querySelectorAll('[data-invitation-paper]').forEach(wrapper => setFieldsEnabled(wrapper, type === 'paper'));
        group.querySelectorAll('[data-invitation-electronic]').forEach(wrapper => setFieldsEnabled(wrapper, type === 'electronic'));
        const otherChecked = type === 'electronic' && group.querySelector('[data-invitation-channel][value="other"]')?.checked;
        group.querySelectorAll('[data-invitation-other]').forEach(wrapper => {
            setFieldsEnabled(wrapper, Boolean(otherChecked));
            wrapper.querySelector('input').required = Boolean(otherChecked);
        });
    };
    const syncNeed = toggle => {
        const details = form.querySelector(`[data-need-details="${toggle.dataset.needToggle}"]`);
        if (!details) return;
        setFieldsEnabled(details, toggle.checked);
        if (!toggle.disabled) toggle.setAttribute('aria-expanded', toggle.checked ? 'true' : 'false');
        syncInvitationCards(details);
    };
    const syncNeedsEmptyState = () => {
        const empty = form.querySelector('[data-need-details-empty]');
        if (empty) empty.hidden = Boolean(form.querySelector('[data-need-details]:not([hidden])'));
    };
    form.querySelectorAll('[data-need-toggle]').forEach(toggle => {
        toggle.addEventListener('change', () => { syncNeed(toggle); syncNeedsEmptyState(); renderReviewNeeds(); });
        syncNeed(toggle);
    });
    form.querySelectorAll('[data-need-details]').forEach(details => {
        details.addEventListener('change', event => {
            if (event.target.matches('[data-invitation-card-type], [data-invitation-channel]')) syncInvitationCards(details);
            renderReviewNeeds();
        });
    });
    syncNeedsEmptyState();
    renderReviewNeeds();
    const invitation = form.querySelector('[name="invitation_type"]');
    const days = form.querySelector('[name="day_count"]');
    const syncPlanning = () => {
        const privateBox = form.querySelector('[data-private-audience]');
        const privateField = privateBox.querySelector('textarea');
        privateBox.hidden = invitation.value !== 'private';
        privateField.disabled = privateBox.hidden;
        privateField.required = !privateBox.hidden;
        form.querySelector('[data-review-days]').textContent = days.value;
        form.querySelector('[data-review-invitation]').textContent = invitation.selectedOptions[0].textContent;
    };
    invitation.addEventListener('change', syncPlanning);
    days.addEventListener('input', syncPlanning);
    syncPlanning();
    form.querySelectorAll('[data-other-target]').forEach(box => {
        const toggle = form.querySelector(`[data-target-id="${box.dataset.otherTarget}"]`);
        const field = box.querySelector('textarea');
        const sync = () => { box.hidden = !toggle.checked; field.disabled = !toggle.checked; field.required = toggle.checked; };
        toggle.addEventListener('change', sync);
        sync();
    });

});
