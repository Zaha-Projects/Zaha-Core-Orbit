document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('.bazaar-form-shell')?.closest('form') || document.querySelector('#ramadan-planning-form');
    if (!form) return;
    const errorHost = form.querySelector('[data-error-keys]') || form;
    const keys = JSON.parse(errorHost.dataset.errorKeys || '[]');
    const path = field => (field.name || '').replace(/\[([^\]]*)\]/g, '.$1').replace(/\.$/, '');
    const reveal = field => {
        field.closest('details')?.setAttribute('open', '');
        field.classList.add('is-invalid');
        field.setAttribute('aria-invalid', 'true');
        const section = field.closest('.bazaar-section-card, .ramadan-form-section');
        section?.classList.add('border-danger', 'has-validation-error');
    };
    for (const field of form.querySelectorAll('input, select, textarea')) {
        if (keys.some(key => path(field) === key || path(field).startsWith(key + '.') || key.startsWith(path(field) + '.'))) reveal(field);
    }
    // Aggregate validation can address a whole section rather than a named field.
    for (const section of form.querySelectorAll('[data-error-prefixes]')) {
        if (section.dataset.errorPrefixes.split(',').some(prefix => keys.some(key => key === prefix || key.startsWith(prefix + '.')))) {
            section.classList.add('border-danger', 'has-validation-error');
        }
    }
    const first = form.querySelector('.is-invalid:not([type="hidden"]):not(:disabled)') || form.querySelector('.has-validation-error') || document.querySelector('.bazaar-validation-summary, [data-validation-summary]');
    if (first) {
        first.setAttribute('tabindex', '-1');
        first.scrollIntoView({behavior: 'smooth', block: 'center'});
        first.focus({preventScroll: true});
    }
    form.addEventListener('invalid', event => {
        reveal(event.target);
        event.target.closest('.bazaar-section-card, .ramadan-form-section')?.scrollIntoView({behavior: 'smooth', block: 'center'});
    }, true);
    form.addEventListener('input', event => {
        event.target.classList.remove('is-invalid');
        event.target.removeAttribute('aria-invalid');
    });
    form.addEventListener('submit', event => {
        if (form.dataset.submitting === '1') { event.preventDefault(); return; }
        if (event.defaultPrevented) return;
        const submitter = event.submitter;
        // Disabled buttons are not successful controls; preserve the chosen intent.
        if (submitter?.name) {
            const intent = document.createElement('input');
            intent.type = 'hidden'; intent.name = submitter.name; intent.value = submitter.value;
            intent.dataset.submitIntent = '1';
            form.appendChild(intent);
        }
        form.dataset.submitting = '1';
        form.setAttribute('aria-busy', 'true');
        form.querySelectorAll('button[type="submit"]').forEach(button => button.disabled = true);
        submitter?.querySelector('i')?.setAttribute('class', 'fas fa-spinner fa-spin');
    });
    window.addEventListener('pageshow', event => {
        if (!event.persisted) return;
        delete form.dataset.submitting;
        form.removeAttribute('aria-busy');
        form.querySelectorAll('[data-submit-intent]').forEach(input => input.remove());
        form.querySelectorAll('button[type="submit"]').forEach(button => {
            button.disabled = false;
            button.querySelector('i')?.setAttribute('class', button.value === 'submit' ? 'fas fa-paper-plane' : 'fas fa-floppy-disk');
        });
    });
});
