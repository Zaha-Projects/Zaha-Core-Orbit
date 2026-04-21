document.addEventListener('DOMContentLoaded', function () {
    var formToSubmit = null;
    var isSubmittingDecision = false;
    var modalElement = document.getElementById('decisionConfirmModal');
    var confirmModal = (modalElement && window.bootstrap && bootstrap.Modal) ? new bootstrap.Modal(modalElement) : null;

    function clearModalArtifacts() {
        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.remove();
        });
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');
    }

    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function () {
            clearModalArtifacts();
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('.decision-form');
        if (!form) return;

        var select = form.querySelector('.decision-select');
        var comment = form.querySelector('.decision-comment');

        if (select && comment && ['changes_requested', 'rejected'].includes(select.value) && !comment.value.trim()) {
            event.preventDefault();
            alert(form.dataset.commentRequired);
            return;
        }

        if (confirmModal) {
            event.preventDefault();
            isSubmittingDecision = false;
            formToSubmit = form;
            var title = document.getElementById('decisionConfirmTitle');
            var body = document.getElementById('decisionConfirmBody');
            if (title) title.textContent = form.dataset.confirmTitle;
            if (body) body.textContent = form.dataset.confirmBody;
            confirmModal.show();
        }
    });

    var submitBtn = document.getElementById('decisionConfirmSubmit');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            if (!formToSubmit || isSubmittingDecision) {
                return;
            }

            isSubmittingDecision = true;
            submitBtn.disabled = true;

            if (confirmModal) {
                modalElement.addEventListener('hidden.bs.modal', function handleHidden() {
                    modalElement.removeEventListener('hidden.bs.modal', handleHidden);
                    clearModalArtifacts();
                    formToSubmit.submit();
                });
                confirmModal.hide();
                return;
            }

            formToSubmit.submit();
        });
    }

    document.querySelectorAll('.approval-details-trigger').forEach(function (trigger) {
        var targetSelector = trigger.getAttribute('data-bs-target');
        var detailsUrl = trigger.dataset.detailsUrl;
        if (!targetSelector || !detailsUrl) return;

        var collapseElement = document.querySelector(targetSelector);
        if (!collapseElement) return;

        collapseElement.addEventListener('show.bs.collapse', function () {
            var content = collapseElement.querySelector('.approval-details-content');
            if (!content || content.dataset.loaded === '1') return;

            fetch(detailsUrl, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Failed to load details');
                    return response.json();
                })
                .then(function (payload) {
                    content.innerHTML = payload.html || '<div class="alert alert-warning mb-0">تعذر تحميل التفاصيل.</div>';
                    content.dataset.loaded = '1';
                })
                .catch(function () {
                    content.innerHTML = '<div class="alert alert-warning mb-0">تعذر تحميل التفاصيل. يرجى تحديث الصفحة والمحاولة مرة أخرى.</div>';
                });
        });
    });
});
