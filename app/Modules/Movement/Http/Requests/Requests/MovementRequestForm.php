<?php

namespace App\Modules\Movement\Http\Requests\Requests;

use App\Modules\Movement\Models\MovementRequest;
use App\Modules\Movement\Models\MovementRequestLeg;
use App\Modules\Movement\Support\MovementTime;
use App\Support\GoogleMaps;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create / edit a movement request (plan §7.6–7.7, §8.2, Revision 5). Local wall-clock input
 * ("Y-m-d H:i", Asia/Amman) is converted to UTC through MovementTime; travel_date is derived.
 */
class MovementRequestForm extends FormRequest
{
    private const LOCAL_FORMAT = 'Y-m-d H:i';

    public function authorize(): bool
    {
        $request = $this->route('movementRequest');

        return $request ? $this->user()->can('update', $request) : $this->user()->can('create', MovementRequest::class);
    }

    protected function prepareForValidation(): void
    {
        $legs = is_array($this->input('legs')) ? $this->input('legs') : [];
        // A one-way request carries no return leg; drop an untouched (all-empty) return block from the form.
        if (isset($legs['return']) && is_array($legs['return']) && collect($legs['return'])->filter(fn ($v) => filled($v))->isEmpty()) {
            unset($legs['return']);
        }

        $this->merge(['legs' => $legs]);
    }

    public function rules(): array
    {
        $location = Rule::exists('movement_locations', 'id')->whereNull('deleted_at')->where('is_active', true);
        $rules = [
            'trip_direction' => ['required', Rule::in(MovementRequest::DIRECTIONS)],
            'purpose' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'context_label' => ['nullable', 'string', 'max:255'],
            'client_token' => ['nullable', 'uuid'],
            'submit_action' => ['nullable', Rule::in(['draft', 'submit'])],
            'legs' => ['required', 'array'],
            'legs.return' => [Rule::requiredIf($this->input('trip_direction') === MovementRequest::DIRECTION_ROUND_TRIP), 'prohibited_if:trip_direction,'.MovementRequest::DIRECTION_ONE_WAY, 'array'],
        ];

        foreach (MovementRequestLeg::TYPES as $type) {
            $p = "legs.{$type}";
            $required = $type === MovementRequestLeg::TYPE_OUTBOUND ? 'required' : "required_with:{$p}";
            foreach (['origin', 'destination'] as $end) {
                $rules["{$p}.{$end}_location_id"] = ['nullable', 'integer', $location];
                $rules["{$p}.{$end}_text"] = ['nullable', "required_without:{$p}.{$end}_location_id", 'string', 'max:255'];
                $rules["{$p}.{$end}_maps_url"] = ['nullable', 'url', 'max:2000'];
            }
            $rules["{$p}.passenger_count"] = [$required, 'integer', 'min:1', 'max:1000'];
            $rules["{$p}.required_arrival_at"] = [$required, 'date_format:'.self::LOCAL_FORMAT];
            $rules["{$p}.preferred_departure_at"] = ['nullable', 'date_format:'.self::LOCAL_FORMAT];
            $rules["{$p}.notes"] = ['nullable', 'string', 'max:2000'];
        }

        // Without a return block these rules simply never apply.
        if (! is_array($this->input('legs.return'))) {
            foreach (array_keys($rules) as $key) {
                if (str_starts_with($key, 'legs.return.')) {
                    unset($rules[$key]);
                }
            }
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $now = CarbonImmutable::now('UTC');
            $arrivals = [];
            foreach ($this->input('legs', []) as $type => $leg) {
                $arrival = MovementTime::toUtc($leg['required_arrival_at']);
                $arrivals[$type] = $arrival;
                if (! $arrival->gt($now)) {
                    $validator->errors()->add("legs.{$type}.required_arrival_at", __('movement.requests.errors.deadline_future'));
                }
                if (filled($leg['preferred_departure_at'] ?? null) && ! MovementTime::toUtc($leg['preferred_departure_at'])->lt($arrival)) {
                    $validator->errors()->add("legs.{$type}.preferred_departure_at", __('movement.requests.errors.departure_before_arrival'));
                }
                foreach (['origin', 'destination'] as $end) {
                    $url = $leg["{$end}_maps_url"] ?? null;
                    if (blank($leg["{$end}_location_id"] ?? null) && filled($url) && GoogleMaps::locationQuery($url) === null) {
                        $validator->errors()->add("legs.{$type}.{$end}_maps_url", __('movement.requests.errors.maps_url'));
                    }
                }
            }

            if (isset($arrivals['return']) && ! $arrivals['return']->gt($arrivals['outbound'])) {
                $validator->errors()->add('legs.return.required_arrival_at', __('movement.requests.errors.return_after_outbound'));
            }
        });
    }

    /**
     * Normalised data for MovementRequestWorkflow.
     *
     * @return array{header: array, legs: array<string, array>}
     */
    public function payload(): array
    {
        $data = $this->validated();
        $legs = [];

        foreach ($data['legs'] as $type => $leg) {
            $arrival = MovementTime::toUtc($leg['required_arrival_at']);
            $row = [
                'passenger_count' => (int) $leg['passenger_count'],
                'required_arrival_at' => $arrival,
                // Revision 5: travel_date is the local date of the deadline, never separate input.
                'travel_date' => MovementTime::toLocal($arrival)->format('Y-m-d'),
                'preferred_departure_at' => filled($leg['preferred_departure_at'] ?? null) ? MovementTime::toUtc($leg['preferred_departure_at']) : null,
                'notes' => $leg['notes'] ?? null,
            ];
            foreach (['origin', 'destination'] as $end) {
                $locationId = $leg["{$end}_location_id"] ?? null;
                // A saved location wins; custom text and its Maps link are kept only without one.
                $row["{$end}_location_id"] = $locationId ?: null;
                $row["{$end}_text"] = $locationId ? null : ($leg["{$end}_text"] ?? null);
                $row["{$end}_maps_url"] = $locationId ? null : ($leg["{$end}_maps_url"] ?? null);
            }
            $legs[$type] = $row;
        }

        return [
            'header' => [
                'trip_direction' => $data['trip_direction'],
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'department_id' => $data['department_id'] ?? null,
                'branch_id' => array_key_exists('branch_id', $data) ? $data['branch_id'] : $this->user()->branch_id,
                'context_label' => $data['context_label'] ?? null,
            ],
            'legs' => $legs,
        ];
    }
}
