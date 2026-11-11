<?php

namespace App\Modules\Movement\Http\Requests\Fleet;

use App\Modules\Movement\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create/edit a vehicle (plan §7.2, screen 2). Type and capacity are required here even though the
 * columns are nullable for imported rows (Revision 4). The status is set on create only; later changes
 * go through VehicleStatusRequest with a reason.
 */
class VehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $vehicle = $this->route('vehicle');

        return $vehicle ? $this->user()->can('update', $vehicle) : $this->user()->can('create', Vehicle::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fleet_number' => trim((string) $this->input('fleet_number')),
            'plate_number' => filled($this->input('plate_number')) ? trim((string) $this->input('plate_number')) : null,
        ]);
    }

    public function rules(): array
    {
        $vehicle = $this->route('vehicle');

        $rules = [
            // No deleted_at condition: soft-deleted vehicles keep their identifiers (Revision 4).
            'fleet_number' => ['required', 'string', 'max:50', Rule::unique('movement_vehicles', 'fleet_number')->ignore($vehicle?->id)],
            'plate_number' => ['nullable', 'string', 'max:50', Rule::unique('movement_vehicles', 'plate_number')->ignore($vehicle?->id)],
            'vehicle_type_id' => ['required', 'integer', Rule::exists('movement_vehicle_types', 'id')->whereNull('deleted_at')],
            'manufacturer' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'model_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'passenger_capacity' => ['required', 'integer', 'min:1', 'max:1000'],
            'home_location_id' => ['nullable', 'integer', Rule::exists('movement_locations', 'id')->whereNull('deleted_at')],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];

        if (! $vehicle) {
            $rules['operational_status'] = ['required', Rule::in(self::manualStatuses())];
        }

        return $rules;
    }

    /** pending_verification is reserved for imported records (§18.4). */
    public static function manualStatuses(): array
    {
        return array_values(array_diff(Vehicle::STATUSES, [Vehicle::STATUS_PENDING_VERIFICATION]));
    }

    public function messages(): array
    {
        return [
            'fleet_number.unique' => __('movement.validation.unique_including_deleted'),
            'plate_number.unique' => __('movement.validation.unique_including_deleted'),
        ];
    }
}
