document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('[data-tables]');
    const count = document.querySelector('[data-table-count]');
    const template = document.querySelector('#bazaar-table-template');
    if (!container || !count || !template) return;
    const updateCardSummary = card => {
        const organization = card.querySelector('.rental-type')?.value === 'organization';
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
        const organization = card.querySelector('.rental-type')?.value === 'organization';
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
        const name = window.prompt('اسم المؤسسة / الجمعية / المجتمع المحلي');
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
    document.querySelectorAll('.bazaar-need-card').forEach(card => {
        const toggle = card.querySelector('.need-toggle');
        const details = card.querySelector('.need-details');
        const sync = () => {
            const enabled = toggle.checked;
            details.classList.toggle('opacity-50', !enabled);
            details.querySelectorAll('input,select,textarea').forEach(field => field.disabled = !enabled);
        };
        toggle.addEventListener('change', sync);
        sync();
    });
    const locationType = document.querySelector('[data-location-type]');
    const locationLabel = document.querySelector('[data-location-label]');
    const syncLocation = () => { if (locationLabel) locationLabel.textContent = locationType?.value === 'outside' ? 'اسم الموقع الخارجي' : 'المكان داخل المركز'; };
    locationType?.addEventListener('change', syncLocation);
    syncLocation();
    [...container.children].forEach(toggleTenantFields);
    const firstInvalid = document.querySelector('.bazaar-form-shell .is-invalid, .bazaar-validation-summary');
    if (firstInvalid) window.requestAnimationFrame(() => firstInvalid.focus({preventScroll: true}));
});
