/*
 * KPI dashboard charts. Renders server-prepared specs (App\Services\Kpi\KpiChartBuilder) only:
 * no business formulas here. Spec types: bar | line | hbar | hbar-stacked.
 * Theme-aware (re-renders when <html data-theme> changes), RTL, honours prefers-reduced-motion.
 */
(function () {
    'use strict';

    var root = document.querySelector('[data-kpi-dashboard]');
    var dataNode = document.getElementById('kpi-chart-data');
    if (!root || !dataNode || typeof window.Chart === 'undefined') {
        return;
    }

    var specs;
    try {
        specs = JSON.parse(dataNode.textContent || '{}') || {};
    } catch (error) {
        return;
    }

    var isRtl = (document.documentElement.getAttribute('dir') || root.getAttribute('dir')) === 'rtl';
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)');
    var charts = [];

    function token(name) {
        return window.getComputedStyle(root).getPropertyValue('--kpi-' + name).trim() || window.getComputedStyle(root).getPropertyValue('--kpi-primary').trim();
    }

    function datasetColors(dataset) {
        if (Array.isArray(dataset.colors)) {
            return dataset.colors.map(token);
        }

        return token(dataset.color || 'primary');
    }

    function build(spec) {
        var text = token('text');
        var grid = token('grid');
        var horizontal = spec.type === 'hbar' || spec.type === 'hbar-stacked';
        var stacked = spec.type === 'hbar-stacked';
        var valueTicks = { color: text, precision: spec.unit === '%' ? undefined : 0 };
        if (spec.unit === '%') {
            valueTicks.callback = function (value) { return value + '%'; };
        }

        var categoryAxis = { ticks: { color: text }, grid: { color: grid }, stacked: stacked };
        var valueAxis = { ticks: valueTicks, grid: { color: grid }, beginAtZero: true, stacked: stacked };

        var scales = horizontal
            ? { x: Object.assign({ reverse: isRtl }, valueAxis), y: Object.assign({ position: isRtl ? 'right' : 'left' }, categoryAxis) }
            : { x: Object.assign({ reverse: isRtl }, categoryAxis), y: Object.assign({ position: isRtl ? 'right' : 'left' }, valueAxis) };

        return {
            type: spec.type === 'line' ? 'line' : 'bar',
            data: {
                labels: spec.labels,
                datasets: spec.datasets.map(function (dataset) {
                    var color = datasetColors(dataset);

                    return {
                        label: dataset.label,
                        data: dataset.data,
                        backgroundColor: color,
                        borderColor: color,
                        borderRadius: spec.type === 'line' ? 0 : 6,
                        tension: 0.3,
                        pointRadius: 3,
                        spanGaps: false,
                        fill: false
                    };
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                indexAxis: horizontal ? 'y' : 'x',
                animation: reducedMotion && reducedMotion.matches ? false : { duration: 600 },
                locale: 'ar',
                plugins: {
                    legend: { display: spec.datasets.length > 1, rtl: isRtl, textDirection: isRtl ? 'rtl' : 'ltr', labels: { color: text, usePointStyle: true } },
                    tooltip: { rtl: isRtl, textDirection: isRtl ? 'rtl' : 'ltr' }
                },
                scales: scales
            }
        };
    }

    function render() {
        charts.forEach(function (chart) { chart.destroy(); });
        charts = [];
        window.Chart.defaults.font.family = window.getComputedStyle(document.body).fontFamily;

        root.querySelectorAll('canvas[data-kpi-chart]').forEach(function (canvas) {
            var spec = specs[canvas.getAttribute('data-kpi-chart')];
            if (!spec || !Array.isArray(spec.datasets)) {
                return;
            }
            charts.push(new window.Chart(canvas, build(spec)));
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
