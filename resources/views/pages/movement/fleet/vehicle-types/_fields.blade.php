<div class="col-6 col-md-2">
    <label class="form-label" for="type-code">{{ __('movement.vehicle_types.code') }}</label>
    <input class="form-control" id="type-code" name="code" value="{{ old('code', $type->code) }}" required maxlength="50" pattern="[a-z0-9_]+">
</div>
<div class="col-6 col-md-3">
    <label class="form-label" for="type-name-ar">{{ __('movement.vehicle_types.name_ar') }}</label>
    <input class="form-control" id="type-name-ar" name="name_ar" value="{{ old('name_ar', $type->name_ar) }}" required maxlength="255">
</div>
<div class="col-6 col-md-3">
    <label class="form-label" for="type-name-en">{{ __('movement.vehicle_types.name_en') }}</label>
    <input class="form-control" id="type-name-en" name="name_en" value="{{ old('name_en', $type->name_en) }}" required maxlength="255" dir="ltr">
</div>
<div class="col-6 col-md-2">
    <label class="form-label" for="type-size">{{ __('movement.vehicle_types.size_class') }}</label>
    <select class="form-select" id="type-size" name="size_class" required>
        @foreach(\App\Modules\Movement\Models\VehicleType::SIZE_CLASSES as $size)
            <option value="{{ $size }}" {{ (old('size_class', $type->size_class) === $size) ? 'selected' : '' }}>{{ __('movement.size_classes.'.$size) }}</option>
        @endforeach
    </select>
</div>
<div class="col-6 col-md-2">
    <label class="form-label" for="type-capacity">{{ __('movement.vehicle_types.default_capacity') }}</label>
    <input class="form-control" id="type-capacity" type="number" min="1" max="1000" name="default_capacity" value="{{ old('default_capacity', $type->default_capacity) }}">
</div>
<div class="col-6 col-md-2">
    <label class="form-label" for="type-order">{{ __('movement.vehicle_types.sort_order') }}</label>
    <input class="form-control" id="type-order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $type->sort_order) }}">
</div>
<div class="col-6 col-md-2 d-flex align-items-end">
    <div class="form-check">
        <input type="hidden" name="is_active" value="0">
        <input class="form-check-input" type="checkbox" id="type-active" name="is_active" value="1" {{ (old('is_active', $type->is_active)) ? 'checked' : '' }}>
        <label class="form-check-label" for="type-active">{{ __('movement.common.active') }}</label>
    </div>
</div>
