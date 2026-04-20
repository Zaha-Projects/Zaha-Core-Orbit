document.addEventListener('DOMContentLoaded', function () {
    var formToSubmit = null;
    var modalElement = document.getElementById('decisionConfirmModal');
    var confirmModal = modalElement ? new bootstrap.Modal(modalElement) : null;

    document.querySelectorAll('.decision-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var select = form.querySelector('.decision-select');
            var comment = form.querySelector('.decision-comment');

            if (select && comment && ['changes_requested', 'rejected'].includes(select.value) && !comment.value.trim()) {
                event.preventDefault();
                alert(form.dataset.commentRequired);
                return;
            }

            if (confirmModal) {
                event.preventDefault();
                formToSubmit = form;
                var title = document.getElementById('decisionConfirmTitle');
                var body = document.getElementById('decisionConfirmBody');
                if (title) title.textContent = form.dataset.confirmTitle;
                if (body) body.textContent = form.dataset.confirmBody;
                confirmModal.show();
            }
        });
    });

    var submitBtn = document.getElementById('decisionConfirmSubmit');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            if (formToSubmit) {
                formToSubmit.submit();
            }
        });
    }
});
