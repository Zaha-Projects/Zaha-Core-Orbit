<?php

namespace App\Http\Requests;

use App\Models\MonthlyActivity;
use App\Support\PostExecutionVerificationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPostExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('verify', $this->route('monthlyActivity'));
    }

    public function rules(): array
    {
        return ['items' => ['required', 'array', 'min:1'], 'items.*.status' => ['required', Rule::in([PostExecutionVerificationStatus::CORRECT, PostExecutionVerificationStatus::INCORRECT])], 'items.*.corrected_value' => ['nullable'], 'items.*.note' => ['nullable', 'string', 'max:2000']];
    }
}
