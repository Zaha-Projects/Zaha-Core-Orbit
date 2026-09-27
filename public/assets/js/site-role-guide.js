(() => {
    const search = document.getElementById('guide-search');
    const roleFilter = document.getElementById('guide-role-filter');
    const cards = Array.from(document.querySelectorAll('.guide-role-wrap'));
    const empty = document.getElementById('guide-empty');
    if (!search || !roleFilter || !cards.length) return;
    const normalize = value => String(value || '').trim().toLocaleLowerCase('ar');
    const apply = () => {
        const term = normalize(search.value);
        const role = roleFilter.value;
        let visible = 0;
        cards.forEach(card => {
            const matches = (!role || card.dataset.role === role) && (!term || normalize(card.dataset.search).includes(term));
            card.hidden = !matches;
            if (matches) visible += 1;
        });
        empty.hidden = visible !== 0;
    };
    search.addEventListener('input', apply);
    roleFilter.addEventListener('change', apply);
})();
