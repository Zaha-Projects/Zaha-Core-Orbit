document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-approval-progress]').forEach(function (el) {
        var value = parseFloat(el.getAttribute('data-approval-progress') || '0');
        if (!Number.isFinite(value)) { value = 0; }
        value = Math.max(0, Math.min(100, value));
        el.style.setProperty('--approval-progress', value + '%');
    });

    var modal = document.getElementById('agendaDetailsModal');
    if (!modal) { return; }
    var frame = modal.querySelector('[data-agenda-details-frame]');
    var loading = modal.querySelector('[data-agenda-details-loading]');
    var title = modal.querySelector('#agendaDetailsModalLabel');
    var openLink = modal.querySelector('[data-agenda-details-open]');

    modal.addEventListener('show.bs.modal', function (event) {
        var trigger = event.relatedTarget;
        var url = trigger ? trigger.getAttribute('data-agenda-details-url') : '';
        var agendaTitle = trigger ? trigger.getAttribute('data-agenda-title') : '';
        if (!url || !frame) { return; }
        if (title) { title.textContent = agendaTitle ? 'تفاصيل الأجندة: ' + agendaTitle : 'تفاصيل الأجندة'; }
        if (openLink) { openLink.href = url; }
        if (loading) { loading.hidden = false; }
        frame.hidden = true;
        frame.src = url;
    });

    if (frame) {
        frame.addEventListener('load', function () {
            if (loading) { loading.hidden = true; }
            frame.hidden = false;
        });
    }

    modal.addEventListener('hidden.bs.modal', function () {
        if (frame) { frame.removeAttribute('src'); frame.hidden = true; }
        if (loading) { loading.hidden = false; }
        if (openLink) { openLink.href = '#'; }
    });
});
