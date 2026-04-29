(function () {
    const module = document.querySelector('.agenda-module');
    if (!module) return;

    const isRtl = module.dataset.rtl === '1';
    const createUrl = module.dataset.createUrl || '';
    const canBranchInteract = module.dataset.branchInteract === '1';
    const initialView = module.dataset.initialView || 'table';
    const switchView = window.ZahaUi?.initViewToggle ? window.ZahaUi.initViewToggle(module, initialView) : (() => {});
    const events = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('agenda-events-json', []) : JSON.parse(document.getElementById('agenda-events-json')?.textContent ?? '[]');
    const weekDayLabels = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('agenda-weekdays-json', []) : JSON.parse(document.getElementById('agenda-weekdays-json')?.textContent ?? '[]');
    const monthNames = window.ZahaUi?.readJsonScript ? window.ZahaUi.readJsonScript('agenda-months-json', []) : JSON.parse(document.getElementById('agenda-months-json')?.textContent ?? '[]');
    const createCalendarDayHeader = window.ZahaUi?.createCalendarDayHeader;
    const renderCalendarWeekdays = window.ZahaUi?.renderCalendarWeekdays;

    const selectedYear = Number(module.dataset.selectedYear || 0);
    const selectedMonth = Number(module.dataset.selectedMonth || 0);
    let currentDate = new Date();
    if (selectedYear > 0 && selectedMonth >= 1 && selectedMonth <= 12) {
        currentDate = new Date(selectedYear, selectedMonth - 1, 1);
    } else if (events.length > 0) {
        currentDate = parseDate(events[0].date);
        currentDate.setDate(1);
    } else {
        currentDate.setDate(1);
    }

    const weekdaysContainer = module.querySelector('[data-calendar-weekdays]');
    const gridContainer = module.querySelector('[data-calendar-grid]');
    const titleContainer = module.querySelector('[data-calendar-title]');
    const legendTopContainer = module.querySelector('[data-calendar-legend-top]');
    const legendBottomContainer = module.querySelector('[data-calendar-legend-bottom]');
    const quickSubscribeForm = document.querySelector('[data-quick-subscribe-form]');
    const quickSubscribeModalEl = document.getElementById('agendaQuickSubscribeModal');
    const quickSubscribeTitleEl = quickSubscribeModalEl?.querySelector('#agendaQuickSubscribeModalLabel');
    const quickSubscribeEventNameEl = quickSubscribeModalEl?.querySelector('[data-quick-subscribe-event-name]');
    const quickSubscribeDateEl = quickSubscribeModalEl?.querySelector('[data-quick-subscribe-date]');
    const quickSubscribeMessageEl = quickSubscribeModalEl?.querySelector('[data-quick-subscribe-message]');
    const quickSubscribeConfirmButton = quickSubscribeModalEl?.querySelector('[data-quick-subscribe-confirm]');
    const quickSubscribeViewButton = quickSubscribeModalEl?.querySelector('[data-quick-subscribe-view]');
    const quickSubscribeModal = quickSubscribeModalEl && window.bootstrap?.Modal
        ? new window.bootstrap.Modal(quickSubscribeModalEl)
        : null;
    const palette = ['#E11D48', '#0EA5E9', '#22C55E', '#F59E0B', '#8B5CF6', '#14B8A6', '#F97316', '#3B82F6', '#84CC16', '#EC4899', '#06B6D4', '#A855F7'];
    const icons = ['🏢', '📍', '⭐', '🧭', '🎯', '🛰️', '🪄', '🛡️', '🔷', '🔶'];
    let tooltipEl = null;
    let quickSubscribeAction = null;

    function parseDate(value) {
        if (typeof value === 'string') {
            const match = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if (match) {
                return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
            }
        }

        return new Date(value);
    }

    function mapDayPosition(jsDayIndex) {
        return isRtl ? 6 - jsDayIndex : jsDayIndex;
    }

    function createDayHeader(day, dateStr) {
        if (createCalendarDayHeader) {
            return createCalendarDayHeader(day, dateStr, {
                createUrl,
                createLabel: `إضافة فعالية جديدة بتاريخ ${dateStr}`,
            });
        }

        const fallback = document.createElement('div');
        fallback.className = 'agenda-calendar-day-head';
        fallback.innerHTML = `<div class="agenda-calendar-day-number">${day}</div>`;
        return fallback;
    }

    function decorateCalendarDayCell(cell, year, month, day) {
        const jsDayIndex = new Date(year, month, day).getDay();
        cell.classList.add(`agenda-calendar-day--weekday-${jsDayIndex}`);
        if (jsDayIndex === 5) {
            cell.classList.add('agenda-calendar-day--friday');
        } else if (jsDayIndex === 6) {
            cell.classList.add('agenda-calendar-day--saturday');
        }
    }

    function colorForEntity(entity, id = null) {
        if (entity?.color_hex) return entity.color_hex;
        const key = Math.abs(Number(id || entity?.id || 0));
        return palette[key % palette.length];
    }

    function softenColor(hex) {
        if (!hex || typeof hex !== 'string' || !hex.startsWith('#')) return hex;
        const normalized = hex.length === 4
            ? `#${hex[1]}${hex[1]}${hex[2]}${hex[2]}${hex[3]}${hex[3]}`
            : hex;
        const r = parseInt(normalized.slice(1, 3), 16);
        const g = parseInt(normalized.slice(3, 5), 16);
        const b = parseInt(normalized.slice(5, 7), 16);
        const mix = (channel) => Math.round(channel + ((255 - channel) * 0.55));
        return `rgb(${mix(r)}, ${mix(g)}, ${mix(b)})`;
    }

    function iconForEntity(entity, id = null) {
        if (entity?.icon) return entity.icon;
        const key = Math.abs(Number(id || entity?.id || 0));
        return icons[key % icons.length];
    }

    function ensureTooltip() {
        if (tooltipEl) return tooltipEl;
        tooltipEl = document.createElement('div');
        tooltipEl.className = 'agenda-event-tooltip d-none';
        document.body.appendChild(tooltipEl);
        return tooltipEl;
    }

    function setTooltipPosition(evt) {
        if (!tooltipEl) return;
        const offset = 16;
        const maxX = window.innerWidth - tooltipEl.offsetWidth - 12;
        const maxY = window.innerHeight - tooltipEl.offsetHeight - 12;
        const left = Math.min(maxX, Math.max(12, evt.clientX + offset));
        const top = Math.min(maxY, Math.max(12, evt.clientY + offset));
        tooltipEl.style.left = `${left}px`;
        tooltipEl.style.top = `${top}px`;
    }

    function openLink(url) {
        if (!url) return;
        window.location.href = url;
    }

    function resolveQuickSubscribePresentation(event) {
        if (event.branch_monthly_activity_edit_url) {
            return {
                title: 'فتح الخطة المرتبطة',
                message: 'هذه الفعالية مرتبطة أصلًا بخطة فرعك الشهرية. يمكنك فتحها مباشرة ومتابعة تعبئتها أو مراجعتها.',
                confirmLabel: 'فتح الخطة',
                action: { type: 'open', url: event.branch_monthly_activity_edit_url },
            };
        }

        if (event.branch_participation_status === 'participant') {
            return {
                title: 'إكمال الخطة الشهرية',
                message: 'تم تسجيل مشاركة فرعك في هذه الفعالية. سنفتح الآن الخطة الشهرية المرتبطة بها لتكملي تعبئتها.',
                confirmLabel: 'إكمال الخطة',
                action: { type: 'submit', url: event.quick_subscribe_url },
            };
        }

        return {
            title: 'اشتراك وإضافة للخطة',
            message: 'هل تريدين اشتراك الفرع في هذه الفعالية وإضافتها مباشرة إلى الخطة الشهرية؟',
            confirmLabel: 'اشتراك وإضافة للخطة',
            action: { type: 'submit', url: event.quick_subscribe_url },
        };
    }

    function promptQuickSubscribe(event) {
        const presentation = resolveQuickSubscribePresentation(event);
        quickSubscribeAction = presentation.action;

        if (quickSubscribeModal && quickSubscribeTitleEl && quickSubscribeEventNameEl && quickSubscribeDateEl && quickSubscribeMessageEl && quickSubscribeConfirmButton && quickSubscribeViewButton) {
            quickSubscribeTitleEl.textContent = presentation.title;
            quickSubscribeEventNameEl.textContent = event.name || '';
            quickSubscribeDateEl.textContent = event.date || '';
            quickSubscribeMessageEl.textContent = presentation.message;
            quickSubscribeConfirmButton.textContent = presentation.confirmLabel;
            quickSubscribeViewButton.href = event.view_url || '#';
            quickSubscribeModal.show();
            return;
        }

        if (presentation.action.type === 'open') {
            openLink(presentation.action.url);
            return;
        }

        if (window.confirm(`${event.name}\n\n${presentation.message}`) && quickSubscribeForm) {
            quickSubscribeForm.action = presentation.action.url;
            quickSubscribeForm.submit();
        }
    }

    function renderLegend(monthEvents) {
        if (!legendTopContainer || !legendBottomContainer) return;
        const branches = new Map();
        const departments = new Map();
        const units = new Map();

        monthEvents.forEach((event) => {
            (event.participant_branches ?? []).forEach((branch) => branches.set(branch.id, branch));
            if (event.department_id && event.department && event.department !== '-') {
                departments.set(event.department_id, {
                    id: event.department_id,
                    name: event.department,
                    color_hex: event.department_color_hex,
                    icon: event.department_icon,
                });
            }
            (event.participant_units ?? []).forEach((unit) => units.set(unit.id, unit));
        });

        const renderItems = (entries, prefix, shapeClass) => Array.from(entries.entries()).map(([id, value]) => {
            const entity = typeof value === 'object' ? value : { name: value };
            return `
                <span class="legend-item">
                    <span>${iconForEntity(entity, id)}</span>
                    <span class="legend-badge ${shapeClass}" style="background:${softenColor(colorForEntity(entity, id))}"></span>
                    <span>${prefix}${entity.name}</span>
                </span>
            `;
        }).join('');
        const renderBranchItems = (entries) => Array.from(entries.entries()).map(([id, branch]) => `
            <span class="legend-item legend-item--branch">
                <span class="legend-emoji">${iconForEntity(branch, id)}</span>
                <span>${branch.name}</span>
            </span>
        `).join('');

        legendTopContainer.innerHTML = `
            ${renderItems(departments, '🏢 ', 'legend-badge--square')}
            ${renderItems(units, '🧩 ', 'legend-badge--square')}
        `;
        legendBottomContainer.innerHTML = renderBranchItems(branches);
    }

    function renderWeekdays() {
        if (renderCalendarWeekdays) {
            renderCalendarWeekdays(weekdaysContainer, weekDayLabels);
            return;
        }

        weekdaysContainer.innerHTML = '';
        weekDayLabels.forEach((label, jsDayIndex) => {
            const item = document.createElement('div');
            item.className = 'agenda-weekday';
            if (jsDayIndex === 5) item.classList.add('agenda-weekday--friday');
            if (jsDayIndex === 6) item.classList.add('agenda-weekday--saturday');
            item.textContent = label;
            weekdaysContainer.appendChild(item);
        });
    }

    function renderCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const today = new Date();

        titleContainer.textContent = `${monthNames[month]} ${year}`;

        const monthEvents = events.filter((event) => {
            const dateObj = parseDate(event.date);
            return dateObj.getFullYear() === year && dateObj.getMonth() === month;
        });
        renderLegend(monthEvents);

        const eventsByDay = new Map();
        monthEvents.forEach((event) => {
            const day = parseDate(event.date).getDate();
            if (!eventsByDay.has(day)) eventsByDay.set(day, []);
            eventsByDay.get(day).push(event);
        });

        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDayPosition = mapDayPosition(new Date(year, month, 1).getDay());

        gridContainer.innerHTML = '';

        for (let i = 0; i < firstDayPosition; i += 1) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'agenda-calendar-day agenda-calendar-day--empty';
            gridContainer.appendChild(emptyCell);
        }

        for (let day = 1; day <= daysInMonth; day += 1) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const dayCell = document.createElement('div');
            dayCell.className = 'agenda-calendar-day';
            decorateCalendarDayCell(dayCell, year, month, day);
            const weekIndex = Math.floor((firstDayPosition + day - 1) / 7);
            dayCell.classList.add(`agenda-calendar-day--week-${weekIndex}`);

            const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === day;
            if (isToday) dayCell.classList.add('agenda-calendar-day--today');

            dayCell.appendChild(createDayHeader(day, dateStr));

            const dayEvents = eventsByDay.get(day) ?? [];
            dayEvents.forEach((event) => {
                const eventLink = document.createElement(event.view_url ? 'a' : 'div');
                if (event.view_url) {
                    eventLink.href = event.view_url;
                }
                eventLink.className = `agenda-event-chip status-${event.status}`;
                const branchIcons = (event.participant_branches ?? []).slice(0, 5).map((branch) => (
                    `<span class="agenda-event-chip-emoji" title="${branch.name}">${iconForEntity(branch)}</span>`
                )).join('');
                const unitSquares = [
                    ...(event.department && event.department !== '-' ? [{
                        id: event.department_id,
                        name: event.department,
                        color_hex: event.department_color_hex,
                        icon: event.department_icon,
                    }] : []),
                    ...(event.participant_units ?? []),
                ].slice(0, 5).map((entity) => (
                    `<span class="agenda-event-chip-square" style="background:${softenColor(colorForEntity(entity))}" title="${entity.name}"></span>`
                )).join('');
                eventLink.innerHTML = `
                    <span class="agenda-event-chip-title">${event.name}</span>
                    <span class="calendar-chip-flags">
                        <span class="calendar-chip-flag calendar-chip-flag--event-${event.event_type || 'default'}">${event.event_type_label || event.event_type || ''}</span>
                        <span class="calendar-chip-flag calendar-chip-flag--plan-${event.plan_type || 'default'}">${event.plan_type_label || event.plan_type || ''}</span>
                    </span>
                    <span class="event-status status-${event.status}">${event.status_label ?? event.status}</span>
                    <span class="agenda-event-chip-units">${unitSquares}</span>
                    <span class="agenda-event-chip-branches">${branchIcons}</span>
                `;

                if (canBranchInteract && event.can_quick_subscribe) {
                    eventLink.addEventListener('click', (evt) => {
                        evt.preventDefault();
                        promptQuickSubscribe(event);
                    });
                }

                eventLink.addEventListener('mouseenter', (evt) => {
                    const tooltip = ensureTooltip();
                    const branchPills = (event.participant_branches ?? []).map((branch) => (
                        `<span class="tooltip-pill"><span>${iconForEntity(branch)}</span><span>${branch.name}</span></span>`
                    )).join('');
                    const unitPills = (event.participant_units ?? []).map((unit) => (
                        `<span class="tooltip-pill"><span>${iconForEntity(unit)}</span><span class="legend-badge legend-badge--square" style="background:${softenColor(colorForEntity(unit))}"></span><span>${unit.name}</span></span>`
                    )).join('');

                    tooltip.innerHTML = `
                        <div class="tooltip-title">${event.name}</div>
                        <div class="tooltip-row">📅 ${event.date}</div>
                        <div class="tooltip-row">🏢 ${event.department ?? '-'}</div>
                        <div class="tooltip-row">🏷️ ${event.category ?? '-'}</div>
                        <div class="tooltip-row">📝 ${(event.event_type_label ?? event.event_type ?? '-') + ' / ' + (event.plan_type_label ?? event.plan_type ?? '-')}</div>
                        <div class="tooltip-row">✅ ${event.status_label ?? event.status}</div>
                        <div class="tooltip-row"><strong>الفروع المشاركة:</strong></div>
                        <div class="tooltip-list">${branchPills || '<span class="text-muted">-</span>'}</div>
                        <div class="tooltip-row mt-1"><strong>الأقسام الشريكة:</strong></div>
                        <div class="tooltip-row mt-1"><strong>الوحدات المشاركة:</strong></div>
                        <div class="tooltip-list">${unitPills || '<span class="text-muted">-</span>'}</div>
                    `;
                    tooltip.innerHTML = tooltip.innerHTML.replace(
                        /<div class="tooltip-row mt-1"><strong>.*?<\/strong><\/div>\s*(<div class="tooltip-row mt-1"><strong>)/,
                        '$1'
                    );
                    tooltip.classList.remove('d-none');
                    setTooltipPosition(evt);
                });
                eventLink.addEventListener('mousemove', setTooltipPosition);
                eventLink.addEventListener('mouseleave', () => {
                    if (tooltipEl) tooltipEl.classList.add('d-none');
                });
                dayCell.appendChild(eventLink);
            });

            gridContainer.appendChild(dayCell);
        }
    }

    module.querySelectorAll('[data-calendar-nav]').forEach((button) => {
        button.addEventListener('click', () => {
            const delta = button.dataset.calendarNav === 'next' ? 1 : -1;
            currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + delta, 1);
            renderCalendar();
        });
    });

    if (quickSubscribeConfirmButton) {
        quickSubscribeConfirmButton.addEventListener('click', () => {
            if (!quickSubscribeAction) return;

            if (quickSubscribeAction.type === 'open') {
                openLink(quickSubscribeAction.url);
                return;
            }

            if (quickSubscribeAction.type === 'submit' && quickSubscribeForm) {
                quickSubscribeForm.action = quickSubscribeAction.url;
                quickSubscribeForm.submit();
            }
        });
    }

    switchView(initialView);
    renderWeekdays();
    renderCalendar();
})();
