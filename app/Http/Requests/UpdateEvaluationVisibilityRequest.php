<?php

namespace App\Http\Requests;

use App\Support\EvaluationVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEvaluationVisibilityRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()->can('updateVisibility', $this->route('activityEvaluation')); }
    public function rules(): array { return ['visibility' => ['required', Rule::in(EvaluationVisibility::values())]]; }
}
