<?php

namespace App\Modules\Events\Http\Requests\Ramadan;

use App\Services\DynamicWorkflowService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideRamadanIftarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && ($this->user()->hasRole('super_admin') || $this->user()->can('ramadan_iftars.approve'));
    }

    public function rules(): array
    {
        return [
            'workflow_step_id' => ['required', 'integer'],
            'decision' => ['required', Rule::in([
                DynamicWorkflowService::DECISION_APPROVED,
                DynamicWorkflowService::DECISION_CHANGES_REQUESTED,
            ])],
            'comment' => ['nullable', 'string', 'max:2000', 'required_if:decision,'.DynamicWorkflowService::DECISION_CHANGES_REQUESTED],
        ];
    }
}
