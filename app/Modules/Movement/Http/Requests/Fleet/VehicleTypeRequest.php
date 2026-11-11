<?php

namespace App\Modules\Movement\Http\Requests\Fleet;

use App\Modules\Movement\Models\VehicleType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Plan §7.1. Authorized through VehicleTypePolicy. */
class VehicleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $type = $this->route('vehicleType');

        return $type ? $this->user()->can('update', $type) : $this->user()->can('create', VehicleType::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['code' => strtolower(trim((string) $this->input('code'))), 'is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $type = $this->route('vehicleType');

        return [
            // The unique rule has no deleted_at condition, so soft-deleted rows count too (Revision 4).
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', Rule::unique('movement_vehicle_types', 'code')->ignore($type?->id)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'size_class' => ['required', Rule::in(VehicleType::SIZE_CLASSES)],
            'default_capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return ['code.unique' => __('movement.validation.unique_including_deleted')];
    }
}
