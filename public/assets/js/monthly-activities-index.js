(function () {
    const module = document.querySelector('.monthly-activities-module');
    if (!module) return;

    const toggleButtons = module.querySelectorAll('[data-view-toggle]');
    const panes = module.querySelectorAll('[data-view-pane]');
    const isRtl = module.dataset.rtl === '1';
    const statusLabels = JSON.parse(document.getElementById('monthly-status-labels-json')?.textContent ?? '{}');

    function switchView(nextView) {
        panes.forEach((pane) => pane.classList.toggle('d-none', pane.dataset.viewPane !== nextView));
        toggleButtons.forEach((button) => {
            const active = button.dataset.viewToggle === nextView;
            button.classList.toggle('btn-primary', active);
            button.classList.toggle('btn-outline-primary', !active);
            button.classList.toggle('active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }
    toggleButtons.forEach((button) => button.addEventListener('click', () => switchView(button.dataset.viewToggle)));

    const weekdaysContainer = module.querySelector('[data-calendar-weekdays]');
    const gridContainer = module.querySelector('[data-calendar-grid]');
    const titleContainer = module.querySelector('[data-calendar-title]');
    const endpoint = module.dataset.calendarEndpoint;

    const weekdays = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
    weekdaysContainer.innerHTML = weekdays.map((label) => `<div class="agenda-weekday">${label}</div>`).join('');

    const now = new Date();
    let currentYear = now.getFullYear();
    let currentMonth = now.getMonth() + 1;

    function mapPos(day) { return isRtl ? 6 - day : day; }

    async function loadCalendar() {
        const res = await fetch(`${endpoint}?year=${currentYear}&month=${currentMonth}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const payload = await res.json();
        const items = payload.items || [];

        const firstDay = new Date(currentYear, currentMonth - 1, 1);
        const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();
        const firstOffset = mapPos(firstDay.getDay());
        const today = new Date();

        titleContainer.textContent = `${firstDay.toLocaleString(undefined, { month: 'long' })} ${currentYear}`;

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
            if (today.getFullYear() === currentYear && (today.getMonth() + 1) === currentMonth && today.getDate() === day) {
                cell.classList.add('agenda-calendar-day--today');
            }
            cell.innerHTML = `<div class="agenda-calendar-day-number">${day}</div>`;

            dayItems.forEach((item) => {
                const a = document.createElement('a');
                a.href = item.edit_url;
                a.className = `agenda-event-chip status-${item.status}`;
                const badgeClass = item.status === 'approved'
                    ? 'monthly-calendar-badge--approved'
                    : (item.status === 'rejected'
                        ? 'monthly-calendar-badge--rejected'
                        : (item.status === 'in_review'
                            ? 'monthly-calendar-badge--in-review'
                            : 'monthly-calendar-badge--draft'));
                a.innerHTML = `
                    <span class="agenda-event-chip-title">${item.title}</span>
                    <span class="monthly-calendar-branch">${item.branch || ''}</span>
                    <div class="monthly-calendar-meta">
                        <span class="monthly-calendar-badge ${badgeClass}">${statusLabels[item.status] || item.status}</span>
                        <span class="monthly-calendar-icons">${item.requires_workshops ? '🛠️' : ''}${item.requires_communications ? '📣' : ''}</span>
                    </div>
                `;
                cell.appendChild(a);
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

    loadCalendar();
})();
