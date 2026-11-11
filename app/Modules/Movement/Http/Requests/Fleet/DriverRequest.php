<?php

namespace App\Modules\Movement\Http\Requests\Fleet;

use App\Modules\Movement\Models\Driver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Create/edit a driver (plan §7.3, screen 3). user_id is changed only through link/unlink. */
class DriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $driver = $this->route('driver');

        return $driver ? $this->user()->can('update', $driver) : $this->user()->can('create', Driver::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['licence_number' => filled($this->input('licence_number')) ? trim((string) $this->input('licence_number')) : null]);
    }

    public function rules(): array
    {
        $driver = $this->route('driver');
        $statuses = $driver && $driver->employment_status === Driver::STATUS_PENDING_VERIFICATION
            ? Driver::STATUSES
            : [Driver::STATUS_ACTIVE, Driver::STATUS_INACTIVE];

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            // No deleted_at condition: soft-deleted drivers keep their licence numbers (Revision 4).
            'licence_number' => ['nullable', 'string', 'max:50', Rule::unique('movement_drivers', 'licence_number')->ignore($driver?->id)],
            'licence_category' => ['nullable', 'string', 'max:50'],
            'licence_expires_on' => ['nullable', 'date_format:Y-m-d'],
            'employment_status' => ['required', Rule::in($statuses)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return ['licence_number.unique' => __('movement.validation.unique_including_deleted')];
    }
}
