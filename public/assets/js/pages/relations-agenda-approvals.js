document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-approval-progress]').forEach(function (el) {
        var value = parseFloat(el.getAttribute('data-approval-progress') || '0');
        if (!Number.isFinite(value)) { value = 0; }
        value = Math.max(0, Math.min(100, value));
        el.style.setProperty('--approval-progress', value + '%');
    });

    var modal = document.getElementById('agendaDetailsModal');
    if (!modal) { return; }
    var body = modal.querySelector('[data-agenda-details-body]');
    var title = modal.querySelector('#agendaDetailsModalLabel');
    var openLink = modal.querySelector('[data-agenda-details-open]');

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var url = trigger ? trigger.getAttribute('data-agenda-details-url') : '';
        var agendaTitle = trigger ? trigger.getAttribute('data-agenda-title') : '';
        if (!url || !body) { return; }
        if (title) { title.textContent = agendaTitle ? 'تفاصيل الأجندة: ' + agendaTitle : 'تفاصيل الأجندة'; }
        if (openLink) { openLink.href = url; }
        body.innerHTML = '<div class="agenda-details-modal__loading">جاري تحميل تفاصيل الأجندة...</div>';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' } })
            .then(function (response) {
                if (!response.ok) { throw new Error('Unable to load agenda details'); }
                return response.text();
            })
            .then(function (html) {
                var documentFragment = new DOMParser().parseFromString(html, 'text/html');
                var details = documentFragment.querySelector('.agenda-show-page');
                if (!details) { throw new Error('Agenda details were not found'); }
                body.innerHTML = details.outerHTML;
            })
            .catch(function () {
                body.innerHTML = '<div class="alert alert-warning m-3 mb-0">تعذر تحميل تفاصيل الأجندة. يرجى المحاولة مرة أخرى أو فتحها في صفحة مستقلة.</div>';
            });
    });

    modal.addEventListener('hidden.bs.modal', function () {
        if (body) { body.innerHTML = '<div class="agenda-details-modal__loading">جاري تحميل تفاصيل الأجندة...</div>'; }
        if (openLink) { openLink.href = '#'; }
    });
});
