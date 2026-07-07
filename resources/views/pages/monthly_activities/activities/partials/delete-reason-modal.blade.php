@once
    @push('styles')
        <link rel="stylesheet" href="{{ \App\Support\AssetVersion::url('assets/css/pages/pages-monthly_activities-activities-partials-delete-reason-modal.min.css') }}">
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
        <script src="{{ \App\Support\AssetVersion::url('assets/js/pages/pages-monthly_activities-activities-partials-delete-reason-modal.min.js') }}"></script>
    @endpush
@endonce
