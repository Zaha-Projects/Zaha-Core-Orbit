@once
    @push('styles')
        <style>
            .monthly-delete-modal { border: 0; border-radius: 1.25rem; box-shadow: 0 28px 80px rgba(15, 23, 42, .2); }
            .monthly-delete-modal__icon { align-items: center; background: #fee2e2; border-radius: 1rem; color: #dc2626; display: inline-flex; height: 48px; justify-content: center; width: 48px; }
            [data-theme="dark"] .monthly-delete-modal { background: var(--surface-bg); color: var(--text-color); }
        </style>
    @endpush
@endonce

<div class="modal fade" id="monthlyActivityDeleteReasonModal" tabindex="-1" aria-labelledby="monthlyActivityDeleteReasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content monthly-delete-modal">
            <div class="modal-header border-0 pb-0">
                <div>
                    <span class="monthly-delete-modal__icon"><i class="fas fa-trash-alt" aria-hidden="true"></i></span>
                    <h2 class="modal-title h5 mt-3" id="monthlyActivityDeleteReasonModalLabel">طلب حذف خطة شهرية</h2>
                    <p class="text-muted small mb-0">يرجى توضيح سبب الحذف ليتم إرساله إلى مسار الاعتماد.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold" for="monthlyActivityDeleteReasonInput">سبب الحذف <span class="text-danger">*</span></label>
                <textarea class="form-control" id="monthlyActivityDeleteReasonInput" rows="5" maxlength="2000" placeholder="اكتب سبب الحذف هنا..." required></textarea>
                <div class="invalid-feedback d-block mt-2 d-none" id="monthlyActivityDeleteReasonError">سبب الحذف مطلوب قبل إرسال الطلب.</div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="monthlyActivityDeleteReasonConfirm">
                    <span class="monthly-delete-submit-label">إرسال طلب الحذف</span>
                    <span class="monthly-delete-submit-loading d-none"><span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>جارٍ الإرسال...</span>
                </button>
            </div>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modalElement = document.getElementById('monthlyActivityDeleteReasonModal');
                if (!modalElement || typeof bootstrap === 'undefined') {
                    return;
                }

                const modal = new bootstrap.Modal(modalElement);
                const input = document.getElementById('monthlyActivityDeleteReasonInput');
                const error = document.getElementById('monthlyActivityDeleteReasonError');
                const confirmButton = document.getElementById('monthlyActivityDeleteReasonConfirm');
                const label = confirmButton.querySelector('.monthly-delete-submit-label');
                const loading = confirmButton.querySelector('.monthly-delete-submit-loading');
                let activeForm = null;

                document.querySelectorAll('[data-delete-reason-form="monthly-activity"]').forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        const hiddenInput = form.querySelector('input[name="delete_reason"]');
                        if (hiddenInput && hiddenInput.value.trim() !== '') {
                            return;
                        }

                        event.preventDefault();
                        activeForm = form;
                        input.value = '';
                        input.classList.remove('is-invalid');
                        error.classList.add('d-none');
                        confirmButton.disabled = false;
                        label.classList.remove('d-none');
                        loading.classList.add('d-none');
                        modal.show();
                        setTimeout(function () { input.focus(); }, 250);
                    });
                });

                confirmButton.addEventListener('click', function () {
                    const reason = input.value.trim();
                    if (!reason) {
                        input.classList.add('is-invalid');
                        error.classList.remove('d-none');
                        input.focus();
                        return;
                    }

                    if (!activeForm) {
                        return;
                    }

                    const hiddenInput = activeForm.querySelector('input[name="delete_reason"]');
                    if (hiddenInput) {
                        hiddenInput.value = reason;
                    }

                    confirmButton.disabled = true;
                    label.classList.add('d-none');
                    loading.classList.remove('d-none');
                    activeForm.submit();
                });
            });
        </script>
    @endpush
@endonce
