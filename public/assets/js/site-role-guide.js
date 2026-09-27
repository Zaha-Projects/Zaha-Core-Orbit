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
