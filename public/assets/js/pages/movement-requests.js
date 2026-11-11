/* Movement request form (Phase 4): return-leg toggle and the indicative availability panel. */
(function () {
    'use strict';

    var form = document.querySelector('[data-movement-request-form]');
    if (!form) {
        return;
    }

    var direction = form.querySelector('[data-trip-direction]');
    var returnLeg = form.querySelector('[data-return-leg]');
    function toggleReturn() {
        var roundTrip = direction.value === 'round_trip';
        returnLeg.classList.toggle('d-none', !roundTrip);
        // A hidden return leg must not be submitted with a one-way request.
        returnLeg.querySelectorAll('input, select, textarea').forEach(function (field) { field.disabled = !roundTrip; });
    }
    direction.addEventListener('change', toggleReturn);
    toggleReturn();

    var panel = document.querySelector('[data-availability-panel]');
    var body = panel ? panel.querySelector('[data-availability-body]') : null;
    var labelsNode = document.getElementById('movement-request-labels');
    var labels = labelsNode ? JSON.parse(labelsNode.textContent) : {};
    var arrival = form.querySelector('[data-availability-arrival="outbound"]');
    var passengers = form.querySelector('[data-availability-passengers="outbound"]');
    var timer = null;

    function text(value) {
        var span = document.createElement('span');
        span.textContent = value;
        return span.innerHTML;
    }

    function render(data) {
        if (!data.types.length) {
            body.innerHTML = text(labels.empty);
            return;
        }
        var rows = data.types.map(function (row) {
            return '<tr><td>' + text(row.type) + '</td><td>' + row.free + ' / ' + row.total + '</td><td>' + row.free_with_capacity + '</td></tr>';
        }).join('');
        body.innerHTML = '<table class="table table-sm mb-2"><thead><tr><th>' + text(labels.type) + '</th><th>' + text(labels.free) + '</th><th>' + text(labels.fit) + '</th></tr></thead><tbody>' + rows + '</tbody></table>'
            + '<div>' + text(labels.drivers.replace(':free', data.drivers.free).replace(':active', data.drivers.active)) + '</div>';
    }

    function load() {
        if (!panel || !arrival || !/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/.test(arrival.value)) {
            return;
        }
        var url = new URL(panel.getAttribute('data-endpoint'), window.location.origin);
        url.searchParams.set('required_arrival_at', arrival.value);
        if (passengers && passengers.value) {
            url.searchParams.set('passenger_count', passengers.value);
        }
        body.innerHTML = '<span class="spinner-border spinner-border-sm"></span> ' + text(labels.loading);
        fetch(url.toString(), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
            .then(function (response) { if (!response.ok) { throw new Error(response.status); } return response.json(); })
            .then(render)
            .catch(function () { body.innerHTML = text(labels.error); });
    }

    function schedule() {
        window.clearTimeout(timer);
        timer = window.setTimeout(load, 400);
    }

    [arrival, passengers].forEach(function (field) {
        if (field) {
            field.addEventListener('change', schedule);
            field.addEventListener('input', schedule);
        }
    });
    load();
})();
