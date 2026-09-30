@php($value = fn ($field, $default = '') => old($field, $record?->{$field} ?? $default))
<div class="col-md-2"><label class="form-label">الرمز *</label><input class="form-control" name="code" value="{{ $value('code') }}" required></div>
<div class="col-md-4"><label class="form-label">الاسم *</label><input class="form-control" name="name" value="{{ $value('name') }}" required></div>
@if($resource === 'execution_needs')
<div class="col-md-4"><label class="form-label">الوصف</label><input class="form-control" name="description" value="{{ $value('description') }}"></div>
<div class="col-12"><div class="table-responsive"><table class="table table-bordered align-middle mb-0"><thead><tr><th>الوحدة</th><th>متاح؟</th><th>عند الإتاحة</th></tr></thead><tbody>
@foreach(['monthly' => ['label' => 'الفعاليات الشهرية', 'available' => 'is_monthly_activity', 'required' => 'mandatory_for_monthly'], 'ramadan' => ['label' => 'إفطارات رمضان', 'available' => 'is_ramadan_iftar', 'required' => 'mandatory_for_ramadan']] as $module)
<tr><td>{{ $module['label'] }}</td><td><input type="hidden" name="{{ $module['available'] }}" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="{{ $module['available'] }}" value="1" {{ $value($module['available'], false) ? 'checked' : '' }}><label class="form-check-label">متاح</label></div></td><td><input type="hidden" name="{{ $module['required'] }}" value="0"><select class="form-select" name="{{ $module['required'] }}"><option value="0" {{ ! $value($module['required'], false) ? 'selected' : '' }}>اختياري</option><option value="1" {{ $value($module['required'], false) ? 'selected' : '' }}>إلزامي</option></select></td></tr>
@endforeach
</tbody></table></div><div class="form-text">يُحفظ الإعداد بصيغة وحدات قابلة للتوسع لإضافة وحدات مستقبلية مثل البازارات.</div></div>
@elseif($resource === 'target_groups')
<input type="hidden" name="is_monthly_activity" value="0"><div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="is_monthly_activity" value="1" {{ $value('is_monthly_activity', true) ? 'checked' : '' }}><label class="form-check-label">الفعاليات الشهرية</label></div>
<input type="hidden" name="is_ramadan_iftar" value="0"><div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="is_ramadan_iftar" value="1" {{ $value('is_ramadan_iftar', true) ? 'checked' : '' }}><label class="form-check-label">إفطارات رمضان</label></div>
@endif
@if($resource === 'target_groups')<input type="hidden" name="is_other" value="0"><div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="is_other" value="1" {{ $value('is_other', false) ? 'checked' : '' }}><label class="form-check-label">أخرى</label></div>@endif
<div class="col-md-2"><label class="form-label">الترتيب *</label><input class="form-control" type="number" min="0" name="sort_order" value="{{ $value('sort_order', 0) }}" required></div>
<input type="hidden" name="is_active" value="0"><div class="col-md-2 form-check mt-4"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $value('is_active', true) ? 'checked' : '' }}><label class="form-check-label">فعال</label></div>
