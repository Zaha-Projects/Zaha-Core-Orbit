<?php

namespace App\Modules\Movement\Http\Requests\Fleet;

use App\Modules\Movement\Models\Unavailability;
use App\Modules\Movement\Support\MovementTime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Vehicle maintenance / driver leave window (plan §7.4). Times are entered in the business timezone
 * and stored in UTC through MovementTime. Exactly one of vehicle_id / driver_id is required.
 */
class UnavailabilityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('createFor', [Unavailability::class, $this->subject()]);
    }

    public function rules(): array
    {
        return [
            'vehicle_id' => ['nullable', 'integer', Rule::exists('movement_vehicles', 'id')->whereNull('deleted_at')],
            'driver_id' => ['nullable', 'integer', Rule::exists('movement_drivers', 'id')->whereNull('deleted_at')],
            'type' => ['required', Rule::in(Unavailability::TYPES)],
            'starts_at' => ['required', 'date_format:Y-m-d H:i'],
            'ends_at' => ['required', 'date_format:Y-m-d H:i', 'after:starts_at'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (filled($this->input('vehicle_id')) === filled($this->input('driver_id'))) {
                $validator->errors()->add('vehicle_id', __('movement.validation.exactly_one_subject'));
            }
        });
    }

    public function subject(): string
    {
        return filled($this->input('driver_id')) ? 'driver' : 'vehicle';
    }

    /** Validated attributes with times converted to UTC. */
    public function attributesForStorage(): array
    {
        $data = $this->validated();

        return [
            'vehicle_id' => $data['vehicle_id'] ?? null,
            'driver_id' => $data['driver_id'] ?? null,
            'type' => $data['type'],
            'starts_at' => MovementTime::toUtc($data['starts_at']),
            'ends_at' => MovementTime::toUtc($data['ends_at']),
            'reason' => $data['reason'] ?? null,
        ];
    }
}
