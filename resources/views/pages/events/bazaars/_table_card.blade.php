<details class="bazaar-table-card mb-3" open>
    <summary class="bazaar-table-summary">
        <span class="bazaar-table-number"><i class="fas fa-store" aria-hidden="true"></i> الطاولة رقم <b data-card-number>{{ is_numeric($i) ? $i + 1 : '__NUMBER__' }}</b></span>
        <span class="bazaar-table-summary-meta">
            <span class="badge bazaar-renter-badge" data-renter-badge><i class="fas fa-user"></i> فردي</span>
            <span class="bazaar-summary-tenant" data-summary-tenant>لم يحدد المستأجر</span>
            <span class="badge bazaar-rent-badge" data-summary-rent>0.00 د.أ</span>
            <span class="badge bazaar-completion-badge" data-completion-badge><i class="fas fa-circle-half-stroke"></i> غير مكتملة</span>
            <i class="fas fa-chevron-down bazaar-expand-icon" aria-hidden="true"></i>
        </span>
    </summary>
    <div class="bazaar-table-body">
        <input type="hidden" name="tables[{{ $i }}][table_number]" value="{{ $table['table_number'] ?? (is_numeric($i) ? $i + 1 : '__NUMBER__') }}" data-table-number>
        <div class="bazaar-field-group">
            <h3><i class="fas fa-handshake"></i> بيانات المستأجر</h3>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label">نوع التأجير</label><select class="form-select rental-type" name="tables[{{ $i }}][rental_type]"><option value="individual" @selected(($table['rental_type'] ?? 'individual') === 'individual')>فردي</option><option value="organization" @selected(($table['rental_type'] ?? '') === 'organization')>مؤسسة / جمعية / مجتمع محلي</option></select></div>
                <div class="col-md-4 individual-field"><label class="form-label"><i class="fas fa-user"></i> اسم المستأجر</label><input class="form-control tenant-name" name="tables[{{ $i }}][tenant_name]" value="{{ $table['tenant_name'] ?? '' }}" placeholder="مثال: أحمد محمد"></div>
                <div class="col-md-4 individual-field"><label class="form-label"><i class="fas fa-phone"></i> رقم هاتف المستأجر</label><input class="form-control" name="tables[{{ $i }}][tenant_phone]" value="{{ $table['tenant_phone'] ?? '' }}" placeholder="مثال: 07XXXXXXXX"></div>
                <div class="col-12 organization-field"><label class="form-label"><i class="fas fa-building"></i> المؤسسة / الجمعية / المجتمع المحلي</label><div class="organization-picker" data-search-url="{{ route('events.ramadan.iftars.references.organizations.index') }}" data-create-url="{{ route('events.ramadan.iftars.references.organizations.store') }}"><input type="hidden" name="tables[{{ $i }}][community_organization_id]" value="{{ $table['community_organization_id'] ?? '' }}" data-organization-id><div class="input-group"><input type="search" class="form-control organization-search" value="{{ data_get($table, 'organization.name', '') }}" placeholder="ابحث باسم الجهة المحفوظة..."><button type="button" class="btn btn-outline-success organization-add"><i class="fas fa-plus"></i> إضافة جهة</button></div><div class="list-group organization-results"></div><div class="small text-muted organization-details">{{ data_get($table, 'organization.contact_name') || data_get($table, 'organization.contact_phone') ? collect([data_get($table, 'organization.contact_name'), data_get($table, 'organization.contact_phone')])->filter()->join(' · ') : 'ابحث واختر جهة، أو أضف جهة جديدة إلى السجل المشترك.' }}</div></div></div>
                <div class="col-md-6 organization-field"><label class="form-label">ضابط ارتباط الجهة (اختياري)</label><input class="form-control" name="tables[{{ $i }}][table_liaison_name]" value="{{ $table['table_liaison_name'] ?? '' }}" placeholder="مثال: محمد أحمد"></div>
                <div class="col-md-6 organization-field"><label class="form-label">هاتف ضابط ارتباط الجهة</label><input class="form-control" name="tables[{{ $i }}][table_liaison_phone]" value="{{ $table['table_liaison_phone'] ?? '' }}" placeholder="مثال: 07XXXXXXXX"></div>
            </div>
        </div>
        <div class="bazaar-field-group">
            <h3><i class="fas fa-boxes-stacked"></i> العرض والإيجار</h3>
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label">وصف المادة المعروضة</label><textarea required class="form-control material-description" name="tables[{{ $i }}][planned_material_description]" placeholder="مثال: منتجات يدوية، مأكولات منزلية، إكسسوارات، ملابس، منتجات حرفية">{{ $table['planned_material_description'] ?? '' }}</textarea></div>
                <div class="col-md-4"><label class="form-label"><i class="fas fa-tags"></i> قيمة إيجار الطاولة</label><input required type="number" min="0" step="0.01" class="form-control rental-amount" name="tables[{{ $i }}][planned_rental_amount]" value="{{ $table['planned_rental_amount'] ?? '' }}" placeholder="مثال: 25.00"></div>
            </div>
        </div>
        <div class="bazaar-field-group mb-0">
            <h3><i class="fas fa-user-tie"></i> التنسيق والملاحظات</h3>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">ضابط ارتباط الطاولة</label><select required class="form-select liaison-select" name="tables[{{ $i }}][liaison_user_id]"><option value="">اختر موظفًا مخولًا في الفرع</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected(($table['liaison_user_id'] ?? null) == $user->id)>{{ $user->name }}</option>@endforeach</select></div>
                <div class="col-md-6"><label class="form-label"><i class="fas fa-note-sticky"></i> ملاحظات</label><textarea class="form-control" name="tables[{{ $i }}][notes]" placeholder="اكتب أي ملاحظات خاصة بالطاولة أو المستأجر">{{ $table['notes'] ?? '' }}</textarea></div>
            </div>
        </div>
    </div>
</details>
