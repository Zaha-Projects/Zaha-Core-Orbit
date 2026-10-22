(() => {
    const page = document.querySelector('.site-role-guide');
    if (!page) return;
    page.classList.add('has-guide-js');

    const search = page.querySelector('#guide-search');
    const clear = page.querySelector('#guide-search-clear');
    const roleFilter = page.querySelector('#guide-role-filter');
    const categoryButtons = Array.from(page.querySelectorAll('.role-guide-tabs [data-category]'));
    const cards = Array.from(page.querySelectorAll('.role-guide-card'));
    const jumps = Array.from(page.querySelectorAll('[data-role-jump]'));
    const empty = page.querySelector('#guide-empty');
    const results = page.querySelector('#guide-results');
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const modal = page.querySelector('#role-guide-modal');
    const dialog = modal?.querySelector('.role-guide-modal__dialog');
    const guideData = JSON.parse(page.querySelector('#role-guide-data')?.textContent || '[]');
    const guides = guideData.flatMap((role, roleIndex) => role.tasks.map((task, taskIndex) => ({ role, roleIndex, task, taskIndex })));
    let activeGuide = -1;
    let guideTrigger = null;
    let category = '';

    const normalize = value => String(value || '').trim().toLocaleLowerCase('ar');

    const update = () => {
        const term = normalize(search.value);
        const role = roleFilter.value;
        let visible = 0;

        cards.forEach(card => {
            const matches = (!role || card.dataset.role === role)
                && (!category || card.dataset.category === category)
                && (!term || normalize(card.dataset.search).includes(term));
            card.hidden = !matches;
            if (matches) visible += 1;
        });

        jumps.forEach(link => {
            const card = cards.find(item => item.dataset.role === link.dataset.roleJump);
            link.hidden = !card || card.hidden;
        });

        clear.hidden = !search.value;
        empty.hidden = visible !== 0;
        results.textContent = visible === 1 ? 'عرض دور واحد' : `عرض ${visible} دوراً`;
    };

    search.addEventListener('input', update);
    clear.addEventListener('click', () => {
        search.value = '';
        search.focus();
        update();
    });
    roleFilter.addEventListener('change', update);

    categoryButtons.forEach(button => button.addEventListener('click', () => {
        category = button.dataset.category;
        categoryButtons.forEach(item => {
            const active = item === button;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-pressed', String(active));
        });
        update();
    }));

    page.querySelector('[data-reset-guide]')?.addEventListener('click', () => {
        search.value = '';
        roleFilter.value = '';
        category = '';
        categoryButtons.forEach(button => {
            const active = button.dataset.category === '';
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', String(active));
        });
        update();
        search.focus();
    });

    jumps.forEach(link => link.addEventListener('click', event => {
        const target = page.querySelector(link.getAttribute('href'));
        if (!target) return;
        event.preventDefault();
        target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'start' });
        target.focus({ preventScroll: true });
        history.replaceState(null, '', link.getAttribute('href'));
    }));

    const fillList = (element, items, tag) => {
        element.replaceChildren(...items.map((item, index) => {
            const node = document.createElement(tag);
            if (tag === 'li') node.style.setProperty('--step-order', index);
            node.textContent = item;
            return node;
        }));
    };

    const showGuide = index => {
        const entry = guides[index];
        if (!entry || !modal || !dialog) return;
        activeGuide = index;
        const { role, task } = entry;
        const detail = task.guide;
        page.querySelector('#role-guide-modal-role').textContent = role.title;
        page.querySelector('#role-guide-modal-title').textContent = task.title;
        page.querySelector('#role-guide-modal-summary').textContent = task.summary;
        page.querySelector('#role-guide-modal-goal').textContent = detail.goal;
        page.querySelector('#role-guide-modal-when').textContent = detail.when;
        page.querySelector('#role-guide-modal-after').textContent = detail.after;
        page.querySelector('#role-guide-modal-returned').textContent = detail.returned_flow;
        fillList(page.querySelector('#role-guide-modal-steps'), detail.steps, 'li');
        fillList(page.querySelector('#role-guide-modal-notes'), detail.notes, 'li');

        const flow = page.querySelector('#role-guide-modal-flow');
        flow.replaceChildren(...detail.timeline.map((step, stepIndex) => {
            const item = document.createElement('span');
            item.className = step.owned ? 'is-owned' : '';
            item.style.setProperty('--step-order', stepIndex);
            item.innerHTML = `<i aria-hidden="true">${stepIndex + 1}</i>${step.label}`;
            return item;
        }));
        const handoff = page.querySelector('#role-guide-modal-handoff');
        handoff.replaceChildren(...['from', 'action', 'to'].map((key, index) => {
            const item = document.createElement('span');
            item.innerHTML = `<i class="fas ${index === 1 ? 'fa-arrow-left' : 'fa-circle-user'}" aria-hidden="true"></i><strong>${detail.handoff[key]}</strong>`;
            return item;
        }));
        page.querySelector('#role-guide-modal-position').textContent = `${index + 1} من ${guides.length}`;
        modal.hidden = false;
        document.body.classList.add('guide-modal-open');
        requestAnimationFrame(() => modal.classList.add('is-open'));
        dialog.focus();
    };

    const closeGuide = () => {
        if (!modal || modal.hidden) return;
        modal.classList.remove('is-open');
        document.body.classList.remove('guide-modal-open');
        const finish = () => { modal.hidden = true; guideTrigger?.focus(); };
        reduceMotion ? finish() : window.setTimeout(finish, 180);
    };

    page.querySelectorAll('[data-guide-open]').forEach(button => button.addEventListener('click', () => {
        guideTrigger = button;
        const index = guides.findIndex(entry => entry.roleIndex === Number(button.dataset.roleIndex) && entry.taskIndex === Number(button.dataset.taskIndex));
        showGuide(index);
    }));
    page.querySelectorAll('[data-guide-close]').forEach(button => button.addEventListener('click', closeGuide));
    page.querySelector('[data-guide-previous]')?.addEventListener('click', () => showGuide((activeGuide - 1 + guides.length) % guides.length));
    page.querySelector('[data-guide-next]')?.addEventListener('click', () => showGuide((activeGuide + 1) % guides.length));
    document.addEventListener('keydown', event => {
        if (!modal || modal.hidden) return;
        if (event.key === 'Escape') closeGuide();
        if (event.key !== 'Tab') return;
        const focusable = Array.from(dialog.querySelectorAll('button:not([disabled]), [href], [tabindex]:not([tabindex="-1"])'));
        if (!focusable.length) return;
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === dialog)) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });

    if (!reduceMotion && 'IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-revealed');
            observer.unobserve(entry.target);
        }), { threshold: 0.08 });
        cards.forEach(card => observer.observe(card));
    } else {
        cards.forEach(card => card.classList.add('is-revealed'));
    }

    update();
})();
