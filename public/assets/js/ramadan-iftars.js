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
