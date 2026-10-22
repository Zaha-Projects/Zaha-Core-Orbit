@php $fieldValue = fn ($field, $default = '') => old($field, $record?->{$field} ?? $default); @endphp
@if(in_array($resource, ['community_organizations', 'local_communities'], true))
    <div class="col-md-3"><label class="form-label">الفرع *</label><select class="form-select" name="branch_id" required>@foreach($branches as $branch)<option value="{{ $branch->id }}" {{ (int) $fieldValue('branch_id') === (int) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach</select></div>
    <div class="col-md-3"><label class="form-label">الاسم *</label><input class="form-control" name="name" value="{{ $fieldValue('name') }}" required></div>
    <div class="col-md-3"><label class="form-label">جهة الاتصال</label><input class="form-control" name="contact_name" value="{{ $fieldValue('contact_name') }}"></div>
    <div class="col-md-3"><label class="form-label">الهاتف</label><input class="form-control" name="contact_phone" value="{{ $fieldValue('contact_phone') }}"></div>
    <div class="col-md-3"><label class="form-label">اسم الموقع</label><input class="form-control" name="location_name" value="{{ $fieldValue('location_name') }}"></div>
    <div class="col-md-3"><label class="form-label">العنوان</label><input class="form-control" name="address" value="{{ $fieldValue('address') }}"></div>
    <div class="col-md-4"><label class="form-label">رابط الخرائط</label><input class="form-control" name="google_maps_url" value="{{ $fieldValue('google_maps_url') }}"></div>
@else
    <div class="col-md-2"><label class="form-label">الرمز *</label><input class="form-control" name="code" value="{{ $fieldValue('code') }}" required></div>
        <div class="col-md-3"><label class="form-label">الاسم العربي *</label><input class="form-control" name="name_ar" value="{{ $fieldValue('name_ar') }}" required></div>
        @if(!in_array($resource, ['gift_types', 'meal_types'], true))<div class="col-md-3"><label class="form-label">الاسم الإنجليزي *</label><input class="form-control" name="name_en" value="{{ $fieldValue('name_en') }}" required></div>@endif
    @if($resource === 'meal_types')
        <div class="col-md-4"><label class="form-label">الوصف</label><input class="form-control" name="description" value="{{ $fieldValue('description') }}"></div>
    @endif
    @if($resource === 'mobilization_methods')<input type="hidden" name="is_other" value="0"><div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="is_other" value="1" {{ $fieldValue('is_other', false) ? 'checked' : '' }}><label class="form-check-label">قيمة أخرى</label></div>@endif
    <div class="col-md-2"><label class="form-label">الترتيب *</label><input type="number" min="0" class="form-control" name="sort_order" value="{{ $fieldValue('sort_order', 0) }}" required></div>
@endif
<input type="hidden" name="is_active" value="0"><div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $fieldValue('is_active', true) ? 'checked' : '' }}><label class="form-check-label">فعال</label></div>
