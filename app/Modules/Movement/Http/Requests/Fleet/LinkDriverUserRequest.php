<?php

namespace App\Modules\Movement\Http\Requests\Fleet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Driver ↔ user linking (plan §13.3): only active users, one driver per user, no account creation. */
class LinkDriverUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('link', $this->route('driver'));
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required', 'integer',
                Rule::exists('users', 'id')->where('status', 'active'),
                // Includes soft-deleted drivers: restore that driver instead (Revision 4).
                Rule::unique('movement_drivers', 'user_id'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->route('driver')?->user_id !== null) {
                $validator->errors()->add('user_id', __('movement.validation.driver_already_linked'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'user_id.exists' => __('movement.validation.user_not_active'),
            'user_id.unique' => __('movement.validation.user_already_linked'),
        ];
    }
}
