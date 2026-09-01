(function () {
    'use strict';

    function MonthlyApprovalsPage() {
        this.pendingDecisionForm = null;
        this.isSubmitting = false;
    }

    MonthlyApprovalsPage.prototype.init = function () {
        this.bindDecisionForms();
        this.bindDetailsLazyLoading();
        this.bindActivitySummaryModal();
        this.bindProgressBars();
    };

    MonthlyApprovalsPage.prototype.bindProgressBars = function () {
        document.querySelectorAll('[data-approval-progress]').forEach(function (bar) {
            var value = parseFloat(bar.getAttribute('data-approval-progress') || '0');
            if (!Number.isFinite(value)) {
                value = 0;
            }
            value = Math.max(0, Math.min(100, value));
            bar.style.width = value + '%';
        });
    };

    MonthlyApprovalsPage.prototype.bindDecisionForms = function () {
        var self = this;

        document.addEventListener('submit', function (event) {
            var form = event.target.closest('.decision-form');
            if (!form) {
                return;
            }

            if (!self.validateDecisionComment(form)) {
                event.preventDefault();
                alert(form.dataset.validationError || form.dataset.commentRequired || 'يرجى إدخال التعليق المطلوب.');
                return;
            }

            event.preventDefault();
            self.submitWithConfirmation(form);
        });
    };

    MonthlyApprovalsPage.prototype.validateDecisionComment = function (form) {
        var select = form.querySelector('.decision-select');
        var comment = form.querySelector('.decision-comment');

        if (!select || !comment) {
            return true;
        }

        var requiresComment = select.value === 'changes_requested' || select.value === 'rejected';
        if (requiresComment && comment.value.trim().length === 0) {
            delete form.dataset.validationError;
            return false;
        }
        if (requiresComment && !form.querySelector('input[name="focus_areas[]"]:checked')) {
            form.dataset.validationError = form.dataset.focusAreaRequired || 'يرجى تحديد قسم واحد على الأقل يوضح سبب الرفض أو مكان التعديل.';
            return false;
        }
        delete form.dataset.validationError;
        return true;
    };

    MonthlyApprovalsPage.prototype.submitWithConfirmation = function (form) {
        if (this.isSubmitting) {
            return;
        }

        var confirmTitle = form.dataset.confirmTitle || 'تأكيد الإجراء';
        var confirmBody = form.dataset.confirmBody || 'هل تريد تنفيذ هذا الإجراء؟';
        var message = confirmTitle + '\n\n' + confirmBody;

        if (!window.confirm(message)) {
            return;
        }

        this.pendingDecisionForm = form;
        this.isSubmitting = true;

        var returnUrl = form.querySelector('input[name="return_url"]');
        if (!returnUrl) {
            returnUrl = document.createElement('input');
            returnUrl.type = 'hidden';
            returnUrl.name = 'return_url';
            form.appendChild(returnUrl);
        }
        returnUrl.value = window.location.href;

        var submitButton = form.querySelector('button[type="submit"], .btn[type="submit"], .btn-primary');
        if (submitButton) {
            submitButton.disabled = true;
        }

        form.submit();
    };

    MonthlyApprovalsPage.prototype.bindActivitySummaryModal = function () {
        var modalElement = document.getElementById('approvalActivitySummaryModal');
        var modalTitle = document.getElementById('approvalActivitySummaryTitle');
        var modalBody = document.getElementById('approvalActivitySummaryBody');

        if (!modalElement || !modalBody) {
            return;
        }

        var modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        document.querySelectorAll('.approval-activity-summary-trigger').forEach(function (trigger) {
            trigger.addEventListener('click', function () {
                var detailsUrl = trigger.dataset.detailsUrl;
                if (!detailsUrl) {
                    return;
                }

                if (modalTitle) {
                    modalTitle.textContent = trigger.dataset.activityTitle || 'تفاصيل النشاط';
                }

                modalBody.innerHTML = '<div class="border rounded-3 p-3 wf-panel-soft">جاري تحميل تفاصيل النشاط...</div>';
                modal.show();

                fetch(detailsUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Failed to load activity details');
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        modalBody.innerHTML = payload.html || '<div class="alert alert-warning mb-0">تعذر تحميل تفاصيل النشاط.</div>';
                    })
                    .catch(function () {
                        modalBody.innerHTML = '<div class="alert alert-warning mb-0">تعذر تحميل تفاصيل النشاط. يرجى تحديث الصفحة والمحاولة مرة أخرى.</div>';
                    });
            });
        });
    };

    MonthlyApprovalsPage.prototype.bindDetailsLazyLoading = function () {
        document.querySelectorAll('.approval-details-trigger').forEach(function (trigger) {
            var targetSelector = trigger.getAttribute('data-bs-target');
            var detailsUrl = trigger.dataset.detailsUrl;

            if (!targetSelector || !detailsUrl) {
                return;
            }

            var collapseElement = document.querySelector(targetSelector);
            if (!collapseElement) {
                return;
            }

            collapseElement.addEventListener('show.bs.collapse', function () {
                var content = collapseElement.querySelector('.approval-details-content');
                if (!content || content.dataset.loaded === '1' || content.dataset.loading === '1') {
                    return;
                }

                content.dataset.loading = '1';

                fetch(detailsUrl, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(function (response) {
                        if (!response.ok) {
                            throw new Error('Failed to load details');
                        }

                        return response.json();
                    })
                    .then(function (payload) {
                        content.innerHTML = payload.html || '<div class="alert alert-warning mb-0">تعذر تحميل التفاصيل.</div>';
                        content.dataset.loaded = '1';
                    })
                    .catch(function () {
                        content.innerHTML = '<div class="alert alert-warning mb-0">تعذر تحميل التفاصيل. يرجى تحديث الصفحة والمحاولة مرة أخرى.</div>';
                    })
                    .finally(function () {
                        delete content.dataset.loading;
                    });
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        var page = new MonthlyApprovalsPage();
        page.init();
    });
})();
