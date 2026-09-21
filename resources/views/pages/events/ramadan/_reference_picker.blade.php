@php($pickerId = str_replace('_', '-', $field))
<div class="ramadan-reference-picker" data-reference-picker data-search-url="{{ $searchUrl }}" data-create-url="{{ $createUrl }}">
    <input type="hidden" name="{{ $field }}" value="{{ old($field, $selected?->id) }}" data-reference-id>
    <label class="form-label" for="{{ $pickerId }}-search">{{ $label }} <span class="text-danger">*</span></label>

    <div data-reference-search-state @class(['d-none' => $selected])>
        <div class="input-group">
            <input id="{{ $pickerId }}-search" class="form-control @error($field) is-invalid @enderror" type="search" autocomplete="off" placeholder="بحث عن الجهة..." role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="{{ $pickerId }}-results" data-reference-search>
            <button class="btn btn-outline-secondary" type="button" data-reference-clear>مسح</button>
        </div>
        <div id="{{ $pickerId }}-results" class="ramadan-picker-results list-group position-absolute shadow-sm d-none" role="listbox" data-reference-results></div>
        <div class="small text-muted mt-2 d-none" aria-live="polite" data-reference-loading><span class="spinner-border spinner-border-sm" aria-hidden="true"></span> جارٍ البحث...</div>
        <button type="button" class="btn btn-sm btn-outline-success mt-2 d-none" data-reference-create>إضافة <span data-reference-name></span> كجهة جديدة</button>
    </div>

    <div class="ramadan-reference-selected @unless($selected) d-none @endunless" data-reference-selected-state>
        <div><strong><i class="fas fa-check-circle" aria-hidden="true"></i> <span data-reference-selected-name>{{ $selected?->name }}</span></strong><small dir="ltr" data-reference-selected-phone>{{ $selected?->contact_phone }}</small><small class="text-success" data-reference-success></small></div>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-reference-change>تغيير</button>
    </div>
    @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    @if($selected && ! $selected->is_active)<div class="form-text text-warning">القيمة الحالية غير فعالة ومحفوظة للسجل التاريخي. اختر قيمة أخرى فقط إذا أردت تغييرها.</div>@endif

    <div class="ramadan-quick-create p-3 mt-2 d-none" data-reference-create-form aria-live="polite">
        <h3 class="h6 mb-1">إضافة جهة جديدة</h3>
        <p class="small text-muted mb-3">الاسم ورقم التواصل مطلوبان للحفظ.</p>
        <div class="row g-3">
            <div class="col-md-6"><label class="form-label" for="{{ $pickerId }}-create-name">الاسم <span class="text-danger">*</span></label><input id="{{ $pickerId }}-create-name" class="form-control" autocomplete="organization" data-create-field="name"><div class="invalid-feedback" data-create-error="name"></div></div>
            <div class="col-md-6"><label class="form-label" for="{{ $pickerId }}-create-phone">رقم التواصل <span class="text-danger">*</span></label><input id="{{ $pickerId }}-create-phone" class="form-control" type="tel" inputmode="tel" dir="ltr" autocomplete="tel" data-create-field="contact_phone"><div class="invalid-feedback" data-create-error="contact_phone"></div></div>
            <div class="col-12"><button class="btn btn-sm btn-outline-secondary" type="button" data-reference-more aria-expanded="false">إضافة تفاصيل إضافية</button></div>
            <div class="col-12 d-none" data-reference-optional><div class="row g-2">
                <div class="col-md-4"><label class="form-label">اسم ضابط الارتباط <span class="text-muted">(اختياري)</span></label><input class="form-control" data-create-field="contact_name"></div>
                <div class="col-md-4"><label class="form-label">اسم الموقع <span class="text-muted">(اختياري)</span></label><input class="form-control" data-create-field="location_name"></div>
                <div class="col-md-4"><label class="form-label">العنوان <span class="text-muted">(اختياري)</span></label><input class="form-control" data-create-field="address"></div>
            </div></div>
            <div class="col-12 d-flex flex-wrap align-items-center gap-2"><button type="button" class="btn btn-success" data-reference-save>حفظ واختيار</button><button type="button" class="btn btn-light" data-reference-cancel>إلغاء</button><span class="small" role="status" data-reference-message></span></div>
        </div>
    </div>
</div>
