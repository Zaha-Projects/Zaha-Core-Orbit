/* Movement fleet screens (Phase 3): flatpickr pickers and the vehicle-type default capacity. */
(function () {
    'use strict';

    if (window.flatpickr) {
        document.querySelectorAll('[data-movement-datetime]').forEach(function (input) {
            window.flatpickr(input, { enableTime: true, time_24hr: true, dateFormat: 'Y-m-d H:i', allowInput: true });
        });
        document.querySelectorAll('[data-movement-date]').forEach(function (input) {
            window.flatpickr(input, { dateFormat: 'Y-m-d', allowInput: true });
        });
    }

    // Pre-fill the capacity from the chosen vehicle type when the field is still empty.
    var typeSelect = document.getElementById('vehicle-type-id');
    var capacity = document.querySelector('[data-movement-capacity]');
    if (typeSelect && capacity) {
        typeSelect.addEventListener('change', function () {
            var option = typeSelect.options[typeSelect.selectedIndex];
            var fallback = option ? option.getAttribute('data-default-capacity') : '';
            if (capacity.value === '' && fallback) {
                capacity.value = fallback;
            }
        });
    }
})();
