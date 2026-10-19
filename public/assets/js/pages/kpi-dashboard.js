/*
 * KPI dashboard charts. Renders server-prepared payloads only: no business formulas here.
 * Theme-aware (re-renders when <html data-theme> changes), RTL, and honours prefers-reduced-motion.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-kpi-dashboard]');
    var dataNode = document.getElementById('kpi-chart-data');
    if (!root || !dataNode || typeof window.Chart === 'undefined') {
        return;
    }

    var payload;
    try {
        payload = JSON.parse(dataNode.textContent || '{}') || {};
    } catch (error) {
        return;
    }

    var isRtl = (document.documentElement.getAttribute('dir') || root.getAttribute('dir')) === 'rtl';
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
    var charts = [];

    function token(name) {
        return window.getComputedStyle(root).getPropertyValue(name).trim();
    }

    function colorFor(prefix, key) {
        return token('--kpi-' + prefix + key.replace(/_/g, '-')) || token('--kpi-primary');
    }

    function baseOptions(extra) {
        var text = token('--kpi-text');
        var grid = token('--kpi-grid');
        var options = {
            responsive: true,
            maintainAspectRatio: false,
            animation: reducedMotion && reducedMotion.matches ? false : { duration: 600 },
            locale: 'ar',
            plugins: {
                legend: { rtl: isRtl, textDirection: isRtl ? 'rtl' : 'ltr', labels: { color: text, usePointStyle: true } },
                tooltip: { rtl: isRtl, textDirection: isRtl ? 'rtl' : 'ltr' }
            },
            scales: {
                x: { ticks: { color: text }, grid: { color: grid } },
                y: { ticks: { color: text, precision: 0 }, grid: { color: grid }, beginAtZero: true }
            }
        };

        return Object.assign(options, extra || {});
    }

    var builders = {
        eventsByModule: function (data) {
            var options = baseOptions();
            options.scales.x.reverse = isRtl;

            return {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(function (dataset) {
                        return { label: dataset.label, data: dataset.data, backgroundColor: colorFor('stage-', dataset.key), borderRadius: 6 };
                    })
                },
                options: options
            };
        },
        statusDistribution: function (data) {
            var options = baseOptions({ indexAxis: 'y' });
            options.scales.x.stacked = true;
            options.scales.x.reverse = isRtl;
            options.scales.x.beginAtZero = true;
            options.scales.x.ticks.precision = 0;
            options.scales.y.stacked = true;
            options.scales.y.position = isRtl ? 'right' : 'left';

            return {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(function (dataset) {
                        return { label: dataset.label, data: dataset.data, backgroundColor: colorFor('bucket-', dataset.key), borderRadius: 4 };
                    })
                },
                options: options
            };
        },
        monthlyTrend: function (data) {
            var options = baseOptions();
            options.scales.x.reverse = isRtl;
            options.scales.y.position = isRtl ? 'right' : 'left';

            return {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(function (dataset) {
                        var color = token('--kpi-' + dataset.key);

                        return { label: dataset.label, data: dataset.data, borderColor: color, backgroundColor: color, tension: 0.3, pointRadius: 3, fill: false };
                    })
                },
                options: options
            };
        }
    };

    function render() {
        charts.forEach(function (chart) { chart.destroy(); });
        charts = [];
        window.Chart.defaults.font.family = window.getComputedStyle(document.body).fontFamily;

        root.querySelectorAll('canvas[data-kpi-chart]').forEach(function (canvas) {
            var key = canvas.getAttribute('data-kpi-chart');
            if (!builders[key] || !payload[key]) {
                return;
            }
            charts.push(new window.Chart(canvas, builders[key](payload[key])));
        });
    }

    render();

    if (window.MutationObserver) {
        new MutationObserver(render).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
    }
    if (reducedMotion && reducedMotion.addEventListener) {
        reducedMotion.addEventListener('change', render);
    }
})();
