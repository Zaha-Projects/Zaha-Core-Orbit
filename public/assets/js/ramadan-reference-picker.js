(function () {
    'use strict';

    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    async function requestJson(url, options) {
        var response = await fetch(url, Object.assign({
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        }, options || {}));
        var body = await response.json().catch(function () { return {}; });
        if (!response.ok || body.success === false) {
            var error = new Error(body.message || 'تعذر حفظ الجهة. حاول مرة أخرى.');
            error.status = response.status;
            error.errors = body.errors || {};
            throw error;
        }
        return body;
    }

    document.querySelectorAll('[data-reference-picker]').forEach(function (picker) {
        var search = picker.querySelector('[data-reference-search]');
        var id = picker.querySelector('[data-reference-id]');
        var results = picker.querySelector('[data-reference-results]');
        var createButton = picker.querySelector('[data-reference-create]');
        var panel = picker.querySelector('[data-reference-create-form]');
        var searchState = picker.querySelector('[data-reference-search-state]');
        var selectedState = picker.querySelector('[data-reference-selected-state]');
        var loading = picker.querySelector('[data-reference-loading]');
        var message = picker.querySelector('[data-reference-message]');
        var timer;
        var activeIndex = -1;

        function closeResults() {
            results.classList.add('d-none');
            search.setAttribute('aria-expanded', 'false');
            activeIndex = -1;
        }
        function clearErrors() {
            message.textContent = '';
            message.className = 'small';
            picker.querySelectorAll('[data-create-field]').forEach(function (input) { input.classList.remove('is-invalid'); });
            picker.querySelectorAll('[data-create-error]').forEach(function (error) { error.textContent = ''; });
        }
        function resetCreate() {
            clearErrors();
            panel.querySelectorAll('[data-create-field]').forEach(function (input) { input.value = ''; });
            picker.querySelector('[data-reference-optional]').classList.add('d-none');
            picker.querySelector('[data-reference-more]').setAttribute('aria-expanded', 'false');
        }
        function showSearch(clearSelection) {
            panel.classList.add('d-none');
            selectedState.classList.add('d-none');
            searchState.classList.remove('d-none');
            if (clearSelection) {
                id.value = '';
                search.value = '';
                search.classList.remove('is-valid');
            }
            search.focus();
        }
        function select(item) {
            id.value = item.id;
            search.value = '';
            closeResults();
            createButton.classList.add('d-none');
            searchState.classList.add('d-none');
            panel.classList.add('d-none');
            selectedState.classList.remove('d-none');
            picker.querySelector('[data-reference-selected-name]').textContent = item.name;
            picker.querySelector('[data-reference-selected-phone]').textContent = item.contact_phone || '';
            ['contact_name', 'contact_phone', 'location_name', 'address'].forEach(function (field) {
                var target = document.querySelector('#ramadan-planning-form [name="' + field + '"]');
                if (target && item[field]) target.value = item[field];
            });
        }
        function render(items, term) {
            results.innerHTML = '';
            if (!items.length) {
                var empty = document.createElement('div');
                empty.className = 'ramadan-picker-empty';
                empty.textContent = 'لا توجد نتائج مطابقة';
                results.appendChild(empty);
            }
            items.forEach(function (item, index) {
                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'list-group-item list-group-item-action';
                button.setAttribute('role', 'option');
                button.dataset.index = index;
                button.innerHTML = '<strong></strong><small></small>';
                button.querySelector('strong').textContent = item.name;
                button.querySelector('small').textContent = [item.contact_phone, item.location_name].filter(Boolean).join(' · ');
                button.addEventListener('click', function () { select(item); });
                results.appendChild(button);
            });
            results.classList.remove('d-none');
            search.setAttribute('aria-expanded', 'true');
            createButton.classList.remove('d-none');
            createButton.querySelector('[data-reference-name]').textContent = '"' + term + '"';
            picker.querySelector('[data-create-field="name"]').value = term;
        }
        function setFieldError(field, text) {
            var input = picker.querySelector('[data-create-field="' + field + '"]');
            var error = picker.querySelector('[data-create-error="' + field + '"]');
            if (input) input.classList.add('is-invalid');
            if (error) error.textContent = text;
        }

        search.addEventListener('input', function () {
            id.value = '';
            clearTimeout(timer);
            var term = search.value.trim();
            if (!term) { closeResults(); createButton.classList.add('d-none'); return; }
            loading.classList.remove('d-none');
            timer = setTimeout(function () {
                requestJson(picker.dataset.searchUrl + '?q=' + encodeURIComponent(term))
                    .then(function (body) { render(body.data || [], term); })
                    .catch(function () { results.innerHTML = '<div class="ramadan-picker-empty text-danger">تعذر تحميل النتائج. حاول مرة أخرى.</div>'; results.classList.remove('d-none'); })
                    .finally(function () { loading.classList.add('d-none'); });
            }, 250);
        });
        search.addEventListener('keydown', function (event) {
            var options = Array.from(results.querySelectorAll('[role="option"]'));
            if (event.key === 'Escape') { closeResults(); return; }
            if (!options.length) return;
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                activeIndex = (activeIndex + (event.key === 'ArrowDown' ? 1 : -1) + options.length) % options.length;
                options.forEach(function (option, index) { option.classList.toggle('active', index === activeIndex); });
                options[activeIndex].scrollIntoView({ block: 'nearest' });
            } else if (event.key === 'Enter' && activeIndex >= 0) { event.preventDefault(); options[activeIndex].click(); }
        });
        picker.querySelector('[data-reference-clear]').addEventListener('click', function () { showSearch(true); closeResults(); createButton.classList.add('d-none'); });
        picker.querySelector('[data-reference-change]').addEventListener('click', function () { showSearch(true); });
        createButton.addEventListener('click', function () { searchState.classList.add('d-none'); panel.classList.remove('d-none'); closeResults(); picker.querySelector('[data-create-field="name"]').focus(); });
        picker.querySelector('[data-reference-more]').addEventListener('click', function (event) { var optional = picker.querySelector('[data-reference-optional]'); var expand = optional.classList.contains('d-none'); optional.classList.toggle('d-none', !expand); event.currentTarget.setAttribute('aria-expanded', expand ? 'true' : 'false'); });
        picker.querySelector('[data-reference-cancel]').addEventListener('click', function () { resetCreate(); showSearch(false); });
        picker.querySelector('[data-reference-save]').addEventListener('click', function () {
            var payload = {};
            var save = this;
            clearErrors();
            picker.querySelectorAll('[data-create-field]').forEach(function (input) { payload[input.dataset.createField] = input.value.trim(); });
            if (!payload.name) setFieldError('name', 'الاسم مطلوب');
            if (!payload.contact_phone) setFieldError('contact_phone', 'رقم التواصل مطلوب');
            var firstInvalid = picker.querySelector('.is-invalid');
            if (firstInvalid) { firstInvalid.focus(); return; }
            if (!csrfToken) { message.className = 'small text-danger'; message.textContent = 'انتهت صلاحية الجلسة أو رمز الحماية غير صالح. حدّث الصفحة وحاول مرة أخرى.'; return; }
            save.disabled = true;
            requestJson(picker.dataset.createUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(payload)
            }).then(function (body) {
                select(body.data);
                resetCreate();
                picker.querySelector('[data-reference-success]').textContent = 'تمت إضافة الجهة واختيارها بنجاح.';
            }).catch(function (error) {
                Object.keys(error.errors || {}).forEach(function (field) { setFieldError(field, error.errors[field][0]); });
                message.className = 'small text-danger';
                if (error.status === 419) message.textContent = 'انتهت صلاحية الجلسة أو رمز الحماية غير صالح. حدّث الصفحة وحاول مرة أخرى.';
                else if (error.status === 403) message.textContent = 'ليس لديك صلاحية لإضافة هذه الجهة.';
                else if (error.status === 422) message.textContent = error.message;
                else message.textContent = 'تعذر حفظ الجهة. حاول مرة أخرى.';
                picker.querySelector('.is-invalid')?.focus();
            }).finally(function () { save.disabled = false; });
        });
    });

    var form = document.querySelector('#ramadan-planning-form');
    var locationType = form?.querySelector('[name="location_type"]');
    var hostType = form?.querySelector('[name="host_type"]');
    function resetPicker(wrapper) {
        if (!wrapper) return;
        var selectedId = wrapper.querySelector('[data-reference-id]');
        if (selectedId) selectedId.value = '';
        wrapper.querySelector('[data-reference-create-form]')?.classList.add('d-none');
        wrapper.querySelector('[data-reference-selected-state]')?.classList.add('d-none');
        wrapper.querySelector('[data-reference-search-state]')?.classList.remove('d-none');
        var searchInput = wrapper.querySelector('[data-reference-search]');
        if (searchInput) searchInput.value = '';
        wrapper.querySelectorAll('[data-create-field]').forEach(function (input) { input.value = ''; input.classList.remove('is-invalid'); });
    }
    function updateConditionalFields() {
        var inside = locationType?.value === 'inside_center';
        var local = hostType?.value === 'local_community';
        document.querySelector('[data-outside-location]')?.classList.toggle('d-none', inside);
        if (inside) { var map = form?.querySelector('[name="google_maps_url"]'); if (map) map.value = ''; }
        var organization = document.querySelector('[data-organization-picker]');
        var community = document.querySelector('[data-community-picker]');
        organization?.classList.toggle('d-none', local);
        community?.classList.toggle('d-none', !local);
        document.querySelectorAll('[data-community-only]').forEach(function (element) { element.classList.toggle('d-none', !local); });
        resetPicker(local ? organization : community);
    }
    locationType?.addEventListener('change', updateConditionalFields);
    hostType?.addEventListener('change', updateConditionalFields);
    updateConditionalFields();
}());
