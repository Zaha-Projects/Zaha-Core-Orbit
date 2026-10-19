/*
 * KPI Guide: client-side search and filters over server-rendered cards (no KPI logic here).
 * Native <details> keeps cards keyboard-accessible (Enter/Space toggles).
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-kpi-guide]');
    if (!root) {
        return;
    }

    var cards = Array.prototype.slice.call(root.querySelectorAll('[data-guide-card]'));
    var search = root.querySelector('[data-guide-search]');
    var selects = Array.prototype.slice.call(root.querySelectorAll('[data-guide-filter]'));
    var count = root.querySelector('[data-guide-count]');
    var empty = root.querySelector('[data-guide-empty]');
    var expand = root.querySelector('[data-guide-expand]');
    var reset = root.querySelector('[data-guide-reset]');

    function matches(card) {
        var term = (search.value || '').trim().toLowerCase();
        if (term && card.getAttribute('data-search').indexOf(term) === -1) {
            return false;
        }

        return selects.every(function (select) {
            var value = select.value;
            if (!value) {
                return true;
            }
            var attribute = card.getAttribute('data-' + select.getAttribute('data-guide-filter')) || '';

            return select.getAttribute('data-guide-filter') === 'modules'
                ? attribute.split(' ').indexOf(value) !== -1
                : attribute === value;
        });
    }

    function apply() {
        var visible = 0;
        cards.forEach(function (card) {
            var show = matches(card);
            card.hidden = !show;
            visible += show ? 1 : 0;
        });
        count.textContent = 'عدد المؤشرات المعروضة: ' + visible + ' من ' + cards.length;
        empty.hidden = visible !== 0;
    }

    search.addEventListener('input', apply);
    selects.forEach(function (select) { select.addEventListener('change', apply); });

    reset.addEventListener('click', function () {
        search.value = '';
        selects.forEach(function (select) { select.value = ''; });
        apply();
        search.focus();
    });

    expand.addEventListener('click', function () {
        var open = expand.getAttribute('aria-pressed') !== 'true';
        cards.forEach(function (card) {
            if (!card.hidden) {
                card.open = open;
            }
        });
        expand.setAttribute('aria-pressed', open ? 'true' : 'false');
        expand.textContent = open ? 'طي الكل' : 'توسيع الكل';
    });

    if (window.location.hash) {
        var target = document.getElementById(window.location.hash.slice(1));
        if (target && target.matches('[data-guide-card]')) {
            target.open = true;
        }
    }

    apply();
})();
