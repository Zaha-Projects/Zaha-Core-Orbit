<?php

namespace App\Modules\Movement\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Manual status change with a required reason (plan §13.1, screen 2). */
class VehicleStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('changeStatus', $this->route('vehicle'));
    }

    public function rules(): array
    {
        return [
            'operational_status' => ['required', Rule::in(VehicleRequest::manualStatuses())],
            'status_reason' => ['required', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $vehicle = $this->route('vehicle');
            // A vehicle cannot leave pending_verification until type and capacity are set (Revision 4).
            if ($vehicle && ! $vehicle->isClassified()) {
                $validator->errors()->add('operational_status', __('movement.validation.classify_first'));
            }
        });
    }
}
