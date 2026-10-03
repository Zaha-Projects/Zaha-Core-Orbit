@php
    $savedCustomNeeds = isset($monthlyActivity)
        ? $monthlyActivity->loadMissing('executionNeeds.executionNeedType')->executionNeeds
            ->filter(fn ($need) => $need->executionNeedType && !array_key_exists($need->executionNeedType->code, \App\Modules\Events\Models\ExecutionNeedType::MONTHLY_INPUT_FIELDS))
            ->keyBy('execution_need_type_id')
        : collect();
    $editableCustomTypes = ($customNeedsReadOnly ?? false) ? collect() : ($customNeedTypes ?? collect());
    $historicCustomNeeds = $savedCustomNeeds->except($editableCustomTypes->pluck('id')->all());
@endphp
@if($editableCustomTypes->isNotEmpty() || $historicCustomNeeds->isNotEmpty())
<section class="card mb-3" aria-label="احتياجات التنفيذ المخصصة">
    <div class="card-header fw-bold">احتياجات التنفيذ المخصصة</div>
    <div class="card-body row g-3">
        @foreach($editableCustomTypes as $type)
            @php($saved = $savedCustomNeeds->get($type->id))
            <div class="col-12 col-md-6">
                <div class="border rounded p-3 h-100">
                    <input type="hidden" name="custom_execution_needs[{{ $type->id }}][execution_need_type_id]" value="{{ $type->id }}">
                    <input type="hidden" name="custom_execution_needs[{{ $type->id }}][is_required]" value="0">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="custom-need-{{ $type->id }}" name="custom_execution_needs[{{ $type->id }}][is_required]" value="1" {{ old('custom_execution_needs.'.$type->id.'.is_required', $saved?->is_required ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="custom-need-{{ $type->id }}">{{ $type->name }} — مطلوب</label>
                    </div>
                    <label class="form-label" for="custom-need-details-{{ $type->id }}">التفاصيل العامة</label>
                    <textarea class="form-control" id="custom-need-details-{{ $type->id }}" name="custom_execution_needs[{{ $type->id }}][planned_details]" maxlength="2000" rows="2">{{ old('custom_execution_needs.'.$type->id.'.planned_details', $saved?->planned_details) }}</textarea>
                    <small class="text-muted">عند إلغاء الطلب تُمسح تفاصيل التخطيط لهذا الاحتياج.</small>
                    @foreach(['execution_need_type_id', 'is_required', 'planned_details'] as $field)
                        @error('custom_execution_needs.'.$type->id.'.'.$field)<div class="text-danger">{{ $message }}</div>@enderror
                    @endforeach
                </div>
            </div>
        @endforeach
        @foreach($historicCustomNeeds as $need)
            <div class="col-12 col-md-6">
                <div class="border rounded p-3 h-100">
                    <strong>{{ $need->executionNeedType->name }}</strong>
                    <span class="badge {{ $need->is_required ? 'bg-success' : 'bg-secondary' }}">{{ $need->is_required ? 'مطلوب' : 'غير مطلوب' }}</span>
                    <p class="mb-1">{{ $need->planned_details }}</p>
                    @if($need->actual_details)<p class="mb-1">بيانات التنفيذ: {{ $need->actual_details }}</p>@endif
                    @unless($customNeedsReadOnly ?? false)<small class="text-muted">غير متاح حاليًا. البيانات محفوظة للقراءة ولن تتغير عند حفظ الخطة.</small>@endunless
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
