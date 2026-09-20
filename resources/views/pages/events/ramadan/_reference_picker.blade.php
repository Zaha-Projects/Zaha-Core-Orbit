@php($pickerId = str_replace('_', '-', $field))
<div class="ramadan-reference-picker" data-reference-picker data-search-url="{{ $searchUrl }}" data-create-url="{{ $createUrl }}">
    <input type="hidden" name="{{ $field }}" value="{{ old($field, $selected?->id) }}" data-reference-id>
    <label class="form-label" for="{{ $pickerId }}-search">{{ $label }} <span class="text-danger">*</span></label>
    <input id="{{ $pickerId }}-search" class="form-control @error($field) is-invalid @enderror" type="search" autocomplete="off" placeholder="ابحث بالاسم..." value="{{ $selected?->name }}" data-reference-search>
    @error($field)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if($selected && ! $selected->is_active)<div class="form-text text-warning">القيمة الحالية غير فعالة ومحفوظة للسجل التاريخي. اختر قيمة أخرى فقط إذا أردت تغييرها.</div>@endif
    <div class="list-group position-absolute shadow-sm d-none" style="z-index:1050;max-height:16rem;overflow:auto" data-reference-results></div>
    <button type="button" class="btn btn-sm btn-outline-success mt-2 d-none" data-reference-create>إضافة <span data-reference-name></span> كجهة جديدة</button>
    <div class="ramadan-quick-create p-3 mt-2 d-none" data-reference-create-form aria-live="polite">
        <p class="small text-muted mb-3">أدخل الاسم ورقم التواصل فقط. يمكنك إضافة التفاصيل الاختيارية عند الحاجة.</p>
        <div class="row g-2">
            <div class="col-md-6"><label class="form-label">الاسم <span class="text-danger">*</span></label><input class="form-control" data-create-field="name" required></div>
            <div class="col-md-6"><label class="form-label">رقم التواصل <span class="text-danger">*</span></label><input class="form-control" inputmode="tel" dir="ltr" data-create-field="contact_phone" required></div>
            <div class="col-12"><button class="btn btn-sm btn-outline-secondary" type="button" data-reference-more aria-expanded="false">إضافة تفاصيل إضافية</button></div>
            <div class="col-12 d-none" data-reference-optional>
                <div class="row g-2">
                    <div class="col-md-4"><label class="form-label">جهة الاتصال <span class="text-muted">(اختياري)</span></label><input class="form-control" data-create-field="contact_name"></div>
                    <div class="col-md-4"><label class="form-label">اسم الموقع <span class="text-muted">(اختياري)</span></label><input class="form-control" data-create-field="location_name"></div>
                    <div class="col-md-4"><label class="form-label">العنوان <span class="text-muted">(اختياري)</span></label><input class="form-control" data-create-field="address"></div>
                </div>
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-2"><button type="button" class="btn btn-success" data-reference-save>حفظ واختيار</button><button type="button" class="btn btn-light" data-reference-cancel>إلغاء</button><span class="text-danger small" role="alert" data-reference-error></span></div>
        </div>
    </div>
</div>
