<?php

namespace App\Modules\Events\Http\Requests\Ramadan;

use Illuminate\Foundation\Http\FormRequest;

class DecideRamadanIftarChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) ($this->user() && ($this->user()->hasRole('super_admin') || $this->user()->can('ramadan_iftars.change_request.review')));
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:2000', 'required_if:decision,rejected'],
        ];
    }
}
