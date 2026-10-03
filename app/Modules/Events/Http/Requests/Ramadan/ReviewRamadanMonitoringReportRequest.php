<?php

namespace App\Modules\Events\Http\Requests\Ramadan;

use App\Modules\Events\Models\MonitoringReport;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRamadanMonitoringReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.monitor.review'));
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in([MonitoringReport::STATUS_APPROVED, MonitoringReport::STATUS_RETURNED])],
            'comment' => ['nullable', 'string', 'max:2000', 'required_if:decision,'.MonitoringReport::STATUS_RETURNED],
        ];
    }
}
