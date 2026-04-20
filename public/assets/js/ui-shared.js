(function (window) {

    function readJsonScript(id, fallback) {
        const el = document.getElementById(id);
        if (!el) return fallback;
        try {
            return JSON.parse(el.textContent || '');
        } catch (error) {
            return fallback;
        }
    }

    function initViewToggle(root, initialView = 'table') {
        if (!root) return () => {};

        const buttons = root.querySelectorAll('[data-view-toggle]');
        const panes = root.querySelectorAll('[data-view-pane]');

        function switchView(nextView) {
            panes.forEach((pane) => pane.classList.toggle('d-none', pane.dataset.viewPane !== nextView));
            buttons.forEach((button) => {
                const active = button.dataset.viewToggle === nextView;
                button.classList.toggle('btn-primary', active);
                button.classList.toggle('btn-outline-primary', !active);
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        buttons.forEach((button) => {
            button.addEventListener('click', () => switchView(button.dataset.viewToggle));
        });

        switchView(initialView);
        return switchView;
    }

    window.ZahaUi = window.ZahaUi || {};
    window.ZahaUi.initViewToggle = initViewToggle;
    window.ZahaUi.readJsonScript = readJsonScript;
})(window);
