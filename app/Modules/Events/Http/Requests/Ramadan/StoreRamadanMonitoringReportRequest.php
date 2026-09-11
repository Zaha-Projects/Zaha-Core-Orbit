<?php

namespace App\Modules\Events\Http\Requests\Ramadan;

use App\Modules\Events\Models\FieldVerification;
use App\Modules\Events\Models\RamadanIftar;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRamadanMonitoringReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $iftar = $this->route('ramadanIftar');

        return $user && $iftar instanceof RamadanIftar
            && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.monitor'))
            && ($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id));
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['verifications' => $this->input('verifications', [])]);
    }

    public function rules(): array
    {
        return [
            'monitoring_method_id' => ['required', 'integer', 'exists:monitoring_methods,id'],
            'observed_at' => ['nullable', 'date'],
            'general_notes' => ['nullable', 'string', 'max:5000'],
            'verifications' => ['present', 'array'],
            'verifications.*.id' => ['nullable', 'integer', 'distinct'],
            'verifications.*.detail_type' => ['nullable', Rule::in(['meal', 'gift', 'program_segment', 'execution_team', 'volunteer_requirement', 'supply', 'execution_need'])],
            'verifications.*.detail_id' => ['nullable', 'integer'],
            'verifications.*.field_key' => ['required', 'string', 'max:100'],
            'verifications.*.field_label' => ['required', 'string', 'max:255'],
            'verifications.*.match_status' => ['required', Rule::in(FieldVerification::matchStatuses())],
            'verifications.*.note' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
