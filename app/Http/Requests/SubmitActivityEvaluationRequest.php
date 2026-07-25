<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitActivityEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('submit', $this->route('monthlyActivity'));
    }

    public function rules(): array
    {
        return ['evaluation_form_id' => ['required', 'integer', 'exists:evaluation_forms,id'], 'answers' => ['required', 'array'], 'answers.*.score' => ['nullable', 'numeric', 'between:1,10'], 'answers.*.note' => ['nullable', 'string', 'max:2000'], 'notes' => ['nullable', 'string', 'max:5000']];
    }
}
