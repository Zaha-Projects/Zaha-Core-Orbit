(function () {
    'use strict';
    function syncNeed(toggle) {
        var option = toggle.closest('.ramadan-need-option');
        if (!option) return;
        var active = toggle.checked || toggle.dataset.mandatory === '1';
        option.classList.toggle('is-active', active);
        var details = option.querySelector('.ramadan-need-details');
        if (details) details.hidden = !active;
        option.querySelectorAll('.ramadan-need-details [name]').forEach(function (input) {
            input.disabled = !active;
        });
    }
    document.querySelectorAll('[data-ramadan-need-toggle]').forEach(function (toggle) {
        syncNeed(toggle);
        toggle.addEventListener('change', function () { syncNeed(toggle); });
    });
    var firstInvalid = document.querySelector('.ramadan-module .is-invalid, .ramadan-module [aria-invalid="true"]');
    if (firstInvalid) {
        firstInvalid.closest('.card')?.scrollIntoView({behavior:'smooth', block:'center'});
        firstInvalid.focus({preventScroll:true});
    }
})();

(function () {
    var host = document.querySelector('#ramadan-planning-form [name="host_type"]');
    if (!host) return;
    function attendanceMode() {
        var local = host.value === 'local_community';
        ['local_community_id', 'mobilization_method_id', 'mobilization_method_other'].forEach(function (name) {
            var input = document.querySelector('#ramadan-planning-form [name="' + name + '"]');
            if (!input) return;
            input.closest('[class*="col-"]').hidden = !local;
            input.disabled = !local;
        });
        var organization = document.querySelector('#ramadan-planning-form [name="community_organization_id"]');
        if (organization) {
            organization.closest('[class*="col-"]').hidden = local;
            organization.disabled = local;
        }
        var attendees = document.querySelector('[data-attendance-section]');
        if (attendees) {
            attendees.hidden = !local;
            attendees.querySelectorAll('input, select, textarea, button').forEach(function (input) { input.disabled = !local; });
        }
    }
    host.addEventListener('change', attendanceMode);
    attendanceMode();
}());
