<?php

namespace App\Modules\Events\Http\Requests\Ramadan;

use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarProgramSegment;
use App\Modules\Events\Models\SubjectExecutionNeed;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRamadanIftarExecutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $iftar = $this->route('ramadanIftar');

        return $user && $iftar instanceof RamadanIftar
            && ($user->hasRole('super_admin') || $user->can('ramadan_iftars.execute'))
            && ($user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch((int) $iftar->branch_id));
    }

    protected function prepareForValidation(): void
    {
        foreach (['attendees', 'meals', 'gifts', 'program_segments', 'execution_teams', 'volunteer_requirements', 'supplies', 'execution_needs'] as $key) {
            $this->merge([$key => $this->input($key, [])]);
        }
    }

    public function rules(): array
    {
        return [
            'actual_date' => ['nullable', 'date'],
            'attendees' => ['present', 'array'],
            'attendees.*.id' => ['nullable', 'integer', 'distinct'],
            'attendees.*.full_name' => ['nullable', 'string', 'max:255'],
            'attendees.*.phone' => ['nullable', 'string', 'max:50'],
            'attendees.*.age' => ['nullable', 'integer', 'min:0', 'max:150'],
            'attendees.*.target_group_id' => ['nullable', 'integer', 'exists:target_groups,id'],
            'attendees.*.beneficiary_segment_id' => ['nullable', 'integer', 'exists:beneficiary_segments,id'],
            'attendees.*.attended' => ['required', 'boolean'],
            'attendees.*.notes' => ['nullable', 'string', 'max:2000'],
            'attendees.*._delete' => ['nullable', 'boolean'],
            'meals' => ['present', 'array'],
            'meals.*.id' => ['required', 'integer', 'distinct'],
            'meals.*.actual_quantity' => ['nullable', 'integer', 'min:0'],
            'meals.*.rating' => ['nullable', 'integer', 'between:1,5'],
            'meals.*.rating_notes' => ['nullable', 'string', 'max:2000'],
            'gifts' => ['present', 'array'],
            'gifts.*.id' => ['required', 'integer', 'distinct'],
            'gifts.*.actual_quantity' => ['nullable', 'integer', 'min:0'],
            'program_segments' => ['present', 'array'],
            'program_segments.*.id' => ['required', 'integer', 'distinct'],
            'program_segments.*.execution_status' => ['required', Rule::in(RamadanIftarProgramSegment::statuses())],
            'program_segments.*.actual_notes' => ['nullable', 'string', 'max:2000'],
            'execution_teams' => ['present', 'array'],
            'execution_teams.*.id' => ['required', 'integer', 'distinct'],
            'execution_teams.*.actual_members_count' => ['nullable', 'integer', 'min:0'],
            'execution_teams.*.members' => ['present', 'array'],
            'execution_teams.*.members.*.id' => ['required', 'integer', 'distinct'],
            'execution_teams.*.members.*.task_completed' => ['nullable', 'boolean'],
            'execution_teams.*.members.*.actual_task_note' => ['nullable', 'string', 'max:2000'],
            'volunteer_requirements' => ['present', 'array'],
            'volunteer_requirements.*.id' => ['required', 'integer', 'distinct'],
            'volunteer_requirements.*.actual_count' => ['nullable', 'integer', 'min:0'],
            'supplies' => ['present', 'array'],
            'supplies.*.id' => ['required', 'integer', 'distinct'],
            'supplies.*.actual_quantity' => ['nullable', 'integer', 'min:0'],
            'supplies.*.is_available' => ['nullable', 'boolean'],
            'execution_needs' => ['present', 'array'],
            'execution_needs.*.id' => ['required', 'integer', 'distinct'],
            'execution_needs.*.status' => ['required', Rule::in(SubjectExecutionNeed::executionStatuses())],
            'execution_needs.*.actual_details' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) return;
            foreach ($this->input('attendees', []) as $index => $row) {
                if (! empty($row['target_group_id']) && ! \App\Models\TargetGroup::query()->active()->forRamadanIftars()->whereKey($row['target_group_id'])->exists()) {
                    $validator->errors()->add("attendees.$index.target_group_id", 'The selected target group is not available for Ramadan Iftars.');
                }
                if (! empty($row['beneficiary_segment_id']) && ! \App\Modules\Events\Models\BeneficiarySegment::query()->active()->whereKey($row['beneficiary_segment_id'])->exists()) {
                    $validator->errors()->add("attendees.$index.beneficiary_segment_id", 'The selected beneficiary segment is inactive.');
                }
            }
        });
    }
}
