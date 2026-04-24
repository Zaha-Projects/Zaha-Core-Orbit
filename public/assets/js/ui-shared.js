(function (window) {

    function readJsonScript(id, fallback) {
        const el = document.getElementById(id);
        if (!el) return fallback;
        try {
            return JSON.parse(el.textContent || '');
        } catch (error) {
            return fallback;
        }
    }

    function initViewToggle(root, initialView = 'table') {
        if (!root) return () => {};

        const buttons = root.querySelectorAll('[data-view-toggle]');
        const panes = root.querySelectorAll('[data-view-pane]');

        function switchView(nextView) {
            panes.forEach((pane) => pane.classList.toggle('d-none', pane.dataset.viewPane !== nextView));
            buttons.forEach((button) => {
                const active = button.dataset.viewToggle === nextView;
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-primary', !active);
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        buttons.forEach((button) => {
            button.addEventListener('click', () => switchView(button.dataset.viewToggle));
        });

        switchView(initialView);
        return switchView;
    }

    // يبني رابط إنشاء جديد مع تمرير التاريخ وأي باراميترات إضافية من الصفحة الحالية.
    function buildCalendarCreateUrl(baseUrl, dateStr, extraParams = {}) {
        if (!baseUrl) return null;

        const url = new URL(baseUrl, window.location.origin);
        url.searchParams.set('date', dateStr);

        Object.entries(extraParams).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });

        return url.toString();
    }

    // يرسم رأس خلية التقويم بشكل موحد: رقم اليوم + زر الإضافة إن وجد.
    function createCalendarDayHeader(day, dateStr, options = {}) {
        const {
            createUrl = '',
            createLabel = `إضافة عنصر جديد بتاريخ ${dateStr}`,
            extraParams = {},
        } = options;

        const header = document.createElement('div');
        header.className = 'agenda-calendar-day-head';

        const dayNumber = document.createElement('div');
        dayNumber.className = 'agenda-calendar-day-number';
        dayNumber.textContent = String(day);
        header.appendChild(dayNumber);

        const dayCreateUrl = buildCalendarCreateUrl(createUrl, dateStr, extraParams);
        if (dayCreateUrl) {
            const addButton = document.createElement('a');
            addButton.href = dayCreateUrl;
            addButton.className = 'agenda-calendar-add-btn';
            addButton.setAttribute('aria-label', createLabel);
            addButton.innerHTML = '<span aria-hidden="true">+</span>';
            header.appendChild(addButton);
        }

        return header;
    }

    function renderCalendarWeekdays(container, labels) {
        if (!container) return;

        container.innerHTML = (labels || [])
            .map((label, jsDayIndex) => `<div class="agenda-weekday ${jsDayIndex === 5 ? 'agenda-weekday--friday' : ''} ${jsDayIndex === 6 ? 'agenda-weekday--saturday' : ''}">${label}</div>`)
            .join('');
    }

    window.ZahaUi = window.ZahaUi || {};
    window.ZahaUi.initViewToggle = initViewToggle;
    window.ZahaUi.readJsonScript = readJsonScript;
    window.ZahaUi.buildCalendarCreateUrl = buildCalendarCreateUrl;
    window.ZahaUi.createCalendarDayHeader = createCalendarDayHeader;
    window.ZahaUi.renderCalendarWeekdays = renderCalendarWeekdays;
})(window);
