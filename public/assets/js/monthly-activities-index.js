(function () {
    const module = document.querySelector('.monthly-activities-module');
    if (!module) return;

    const isRtl = module.dataset.rtl === '1';
    const initialView = module.dataset.initialView || 'table';
    const switchView = window.ZahaUi?.initViewToggle ? window.ZahaUi.initViewToggle(module, initialView) : (() => {});
    const statusLabels = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('monthly-status-labels-json', {}) : JSON.parse(document.getElementById('monthly-status-labels-json')?.textContent ?? '{}');
    const createCalendarDayHeader = window.ZahaUi?.createCalendarDayHeader;
    const renderCalendarWeekdays = window.ZahaUi?.renderCalendarWeekdays;
    const createUrl = module.dataset.createUrl || '';
    const defaultBranchId = module.dataset.defaultBranchId || '';

    const weekdaysContainer = module.querySelector('[data-calendar-weekdays]');
    const gridContainer = module.querySelector('[data-calendar-grid]');
    const titleContainer = module.querySelector('[data-calendar-title]');
    const endpoint = module.dataset.calendarEndpoint;
    if (!endpoint) return;
    const emptyState = document.createElement('div');
    emptyState.className = 'monthly-calendar-empty d-none';
    emptyState.textContent = 'لا توجد أنشطة لهذا الشهر حسب الفلاتر المحددة.';
    gridContainer.parentNode.insertBefore(emptyState, gridContainer);

    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    if (renderCalendarWeekdays) {
        renderCalendarWeekdays(weekdaysContainer, weekdays);
    } else {
        weekdaysContainer.innerHTML = weekdays
            .map((label, jsDayIndex) => `<div class="agenda-weekday ${jsDayIndex === 5 ? 'agenda-weekday--friday' : ''} ${jsDayIndex === 6 ? 'agenda-weekday--saturday' : ''}">${label}</div>`)
            .join('');
    }

    const now = new Date();
    const searchParams = new URLSearchParams(window.location.search);
    const preservedParams = new URLSearchParams(window.location.search);
    preservedParams.delete('page');
    preservedParams.delete('year');
    preservedParams.delete('month');

    let currentYear = Number.parseInt(searchParams.get('year') || '', 10) || now.getFullYear();
    let currentMonth = Number.parseInt(searchParams.get('month') || '', 10) || (now.getMonth() + 1);

    function mapPos(day) { return isRtl ? 6 - day : day; }

    function createDayHeader(day, dateStr) {
        if (createCalendarDayHeader) {
            return createCalendarDayHeader(day, dateStr, {
                createUrl,
                createLabel: `إضافة نشاط جديد بتاريخ ${dateStr}`,
                extraParams: { branch_id: defaultBranchId },
            });
        }

        const fallback = document.createElement('div');
        fallback.className = 'agenda-calendar-day-head';
        fallback.innerHTML = `<div class="agenda-calendar-day-number">${day}</div>`;
        return fallback;
    }

    function decorateCalendarDayCell(cell, year, month, day) {
        const jsDayIndex = new Date(year, month - 1, day).getDay();
        cell.classList.add(`agenda-calendar-day--weekday-${jsDayIndex}`);
        if (jsDayIndex === 5) {
            cell.classList.add('agenda-calendar-day--friday');
        } else if (jsDayIndex === 6) {
            cell.classList.add('agenda-calendar-day--saturday');
        }
    }

    async function loadCalendar() {
        const requestParams = new URLSearchParams(preservedParams.toString());
        requestParams.set('year', String(currentYear));
        requestParams.set('month', String(currentMonth));

        const firstDay = new Date(currentYear, currentMonth - 1, 1);
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
        const firstOffset = mapPos(firstDay.getDay());
        const today = new Date();
        let items = [];

        titleContainer.textContent = `${firstDay.toLocaleString(undefined, { month: 'long' })} ${currentYear}`;

        try {
            const res = await fetch(`${endpoint}?${requestParams.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            if (!res.ok) {
                throw new Error(`Calendar request failed with status ${res.status}`);
            }
            const payload = await res.json();
            items = payload.items || [];
        } catch (error) {
            console.error('Monthly calendar load failed:', error);
        }

        emptyState.classList.toggle('d-none', items.length > 0);

        gridContainer.innerHTML = '';
        for (let i = 0; i < firstOffset; i++) {
            const pad = document.createElement('div');
            pad.className = 'agenda-calendar-day agenda-calendar-day--empty';
            gridContainer.appendChild(pad);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayItems = items.filter((it) => it.date === dateStr);
            const cell = document.createElement('div');
            cell.className = 'agenda-calendar-day';
            decorateCalendarDayCell(cell, currentYear, currentMonth, day);
            const weekIndex = Math.floor((firstOffset + day - 1) / 7);
            cell.classList.add(`agenda-calendar-day--week-${weekIndex}`);
            if (today.getFullYear() === currentYear && (today.getMonth() + 1) === currentMonth && today.getDate() === day) {
                cell.classList.add('agenda-calendar-day--today');
            }

            cell.appendChild(createDayHeader(day, dateStr));

            dayItems.forEach((item) => {
                const activityLink = document.createElement('a');
                activityLink.href = item.open_url || item.edit_url || '#';
                activityLink.className = `agenda-event-chip status-${item.status}`;
                const badgeClass = item.status === 'approved'
                    ? 'monthly-calendar-badge--approved'
                    : (item.status === 'rejected'
                        ? 'monthly-calendar-badge--rejected'
                        : (item.status === 'in_review'
                            ? 'monthly-calendar-badge--in-review'
                            : 'monthly-calendar-badge--draft'));
                activityLink.innerHTML = `
                    <span class="agenda-event-chip-title">${item.title}</span>
                    <span class="monthly-calendar-branch">${item.branch || ''}</span>
                    <span class="calendar-chip-flags">
                        ${item.event_type_label ? `<span class="calendar-chip-flag calendar-chip-flag--event-${item.event_type || 'default'}">${item.event_type_label}</span>` : ''}
                        ${item.plan_type_label ? `<span class="calendar-chip-flag calendar-chip-flag--plan-${item.plan_type || 'default'}">${item.plan_type_label}</span>` : ''}
                        ${item.source_label ? `<span class="calendar-chip-flag calendar-chip-flag--source">${item.source_label}</span>` : ''}
                        <span class="calendar-chip-flag calendar-chip-flag--version">V${item.plan_version || 1}</span>
                    </span>
                    <div class="monthly-calendar-meta">
                        <span class="monthly-calendar-badge ${badgeClass}">${statusLabels[item.status] || item.status}</span>
                        <span class="monthly-calendar-icons">${item.requires_workshops ? '🛠️' : ''}${item.requires_communications ? '📣' : ''}</span>
                    </div>
                `;
                cell.appendChild(activityLink);
            });

            gridContainer.appendChild(cell);
        }
    }

    module.querySelectorAll('[data-calendar-nav]').forEach((button) => {
        button.addEventListener('click', async () => {
            currentMonth += button.dataset.calendarNav === 'next' ? 1 : -1;
            if (currentMonth > 12) { currentMonth = 1; currentYear++; }
            if (currentMonth < 1) { currentMonth = 12; currentYear--; }
            await loadCalendar();
        });
    });

    switchView(initialView);
    loadCalendar();
})();
