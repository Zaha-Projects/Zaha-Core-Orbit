(function () {
    'use strict';

    const module = document.querySelector('.bazaar-browse-module');
    if (!module) return;

    const labels = JSON.parse(document.getElementById('bazaar-calendar-labels')?.textContent || '{}');
    const panes = module.querySelectorAll('[data-view-pane]');
    module.querySelectorAll('[data-view-toggle]').forEach((button) => button.addEventListener('click', () => {
        const view = button.dataset.viewToggle;
        panes.forEach((pane) => pane.classList.toggle('d-none', pane.dataset.viewPane !== view));
        module.querySelectorAll('[data-view-toggle]').forEach((item) => {
            const active = item === button;
            item.classList.toggle('active', active);
            item.classList.toggle('btn-bazaar', active);
            item.classList.toggle('btn-outline-bazaar', !active);
        });
        if (view === 'calendar') loadCalendar();
    }));

    const initial = (module.dataset.month || '').split('-').map(Number);
    let year = initial[0] || new Date().getFullYear();
    let month = initial[1] || new Date().getMonth() + 1;
    let loadedKey = '';
    const grid = module.querySelector('[data-calendar-grid]');
    const title = module.querySelector('[data-calendar-title]');
    const weekdays = Array.isArray(labels.weekdays) && labels.weekdays.length === 7 ? labels.weekdays : ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    module.querySelector('[data-calendar-weekdays]').innerHTML = weekdays.map((day) => `<div class="agenda-weekday">${day}</div>`).join('');

    function escapeHtml(value) {
        const element = document.createElement('span');
        element.textContent = value || '';
        return element.innerHTML;
    }

    async function loadCalendar(force) {
        const key = `${year}-${month}`;
        if (!force && loadedKey === key) return;
        loadedKey = key;
        title.textContent = new Intl.DateTimeFormat('ar', { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
        grid.innerHTML = `<div class="bazaar-calendar-state">${labels.loading || ''}</div>`;
        const params = new URLSearchParams(window.location.search);
        params.set('month', `${year}-${String(month).padStart(2, '0')}`);
        try {
            const response = await fetch(`${module.dataset.calendarEndpoint}?${params}`, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
            if (!response.ok) throw new Error(String(response.status));
            renderCalendar((await response.json()).items || []);
        } catch (error) {
            grid.innerHTML = `<div class="bazaar-calendar-state text-danger"><i class="fas fa-triangle-exclamation"></i> ${labels.error || ''}</div>`;
        }
    }

    function renderCalendar(items) {
        grid.innerHTML = '';
        const firstDay = new Date(year, month - 1, 1).getDay();
        const days = new Date(year, month, 0).getDate();
        for (let index = 0; index < firstDay; index++) grid.insertAdjacentHTML('beforeend', '<div class="agenda-calendar-day agenda-calendar-day--empty"></div>');
        for (let day = 1; day <= days; day++) {
            const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const cell = document.createElement('div');
            cell.className = 'agenda-calendar-day bazaar-calendar-day';
            const head = document.createElement('div');
            head.className = 'agenda-calendar-day-head';
            head.innerHTML = `<span class="agenda-calendar-day-number">${day}</span>`;
            if (module.dataset.createUrl) {
                const add = document.createElement('a');
                add.className = 'bazaar-calendar-add';
                add.href = `${module.dataset.createUrl}?date=${date}`;
                add.setAttribute('aria-label', (labels.create || '').replace('__DATE__', date));
                add.title = (labels.create || '').replace('__DATE__', date);
                add.innerHTML = '<i class="fas fa-plus"></i>';
                head.appendChild(add);
            }
            cell.appendChild(head);
            items.filter((item) => item.date === date).forEach((item) => {
                const event = document.createElement('a');
                event.className = `bazaar-calendar-event bazaar-status-border--${item.status}`;
                event.href = item.open_url;
                event.innerHTML = `<strong><i class="fas fa-store"></i> ${escapeHtml(item.title)}</strong><small><i class="far fa-clock"></i> ${escapeHtml(item.starts_at)}–${escapeHtml(item.ends_at)}</small><small><i class="fas fa-location-dot"></i> ${escapeHtml(item.location)}</small><small>${escapeHtml(item.branch)}</small><span class="bazaar-status bazaar-status--${item.status}">${escapeHtml(item.status_label)}</span>`;
                cell.appendChild(event);
            });
            grid.appendChild(cell);
        }
    }

    module.querySelectorAll('[data-calendar-nav]').forEach((button) => button.addEventListener('click', () => {
        month += button.dataset.calendarNav === 'next' ? 1 : -1;
        if (month > 12) { month = 1; year++; }
        if (month < 1) { month = 12; year--; }
        loadCalendar(true);
    }));
}());
