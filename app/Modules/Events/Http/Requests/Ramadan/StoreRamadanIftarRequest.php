<?php

namespace App\Modules\Events\Http\Requests\Ramadan;

use App\Models\AgendaEvent;
use App\Models\TargetGroup;
use App\Models\User;
use App\Models\ExecutionNeedType;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\LocalCommunity;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarMealItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRamadanIftarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $iftar = $this->route('ramadanIftar');
        $permission = $iftar ? 'ramadan_iftars.edit' : 'ramadan_iftars.create';

        if (! $user || (! $user->hasAnyRole(['relations_manager', 'relations_officer', 'super_admin']) && ! $user->can($permission))) {
            return false;
        }

        return ! $iftar || ($iftar instanceof RamadanIftar && $this->canAccessBranch((int) $iftar->branch_id));
    }

    protected function prepareForValidation(): void
    {
        $collections = ['target_groups', 'meals', 'gifts', 'program_segments', 'execution_teams', 'volunteer_requirements', 'supplies', 'execution_needs'];
        $this->merge(collect($collections)->mapWithKeys(fn (string $key) => [$key => $this->input($key, [])])->all());
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'agenda_event_id' => ['nullable', 'integer', 'exists:agenda_events,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'relations_officer_id' => ['required', 'integer', 'exists:users,id'],
            'planned_date' => ['required', 'date'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_to' => ['nullable', 'date_format:H:i', 'after_or_equal:time_from'],
            'location_type' => ['required', Rule::in(RamadanIftar::locationTypes())],
            'location_name' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'google_maps_url' => ['nullable', 'url', 'max:2048'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'supporting_entity_name' => ['nullable', 'string', 'max:255'],
            'host_type' => ['required', Rule::in(RamadanIftar::hostTypes())],
            'community_organization_id' => ['nullable', 'integer', 'exists:community_organizations,id'],
            'local_community_id' => ['nullable', 'integer', 'exists:local_communities,id'],
            'mobilization_method_id' => ['nullable', 'integer', 'exists:mobilization_methods,id'],
            'mobilization_method_other' => ['nullable', 'string'],
            'target_groups' => ['present', 'array'],
            'target_groups.*.id' => ['nullable', 'integer'],
            'target_groups.*.target_group_id' => ['required', 'integer', 'exists:target_groups,id'],
            'target_groups.*.target_group_custom_text' => ['nullable', 'string'],
            'target_groups.*.beneficiary_segment_id' => ['nullable', 'integer', 'exists:beneficiary_segments,id'],
            'target_groups.*.segment_custom_text' => ['nullable', 'string'],
            'target_groups.*.planned_count' => ['required', 'integer', 'min:0'],
            'target_groups.*.notes' => ['nullable', 'string'],
            'meals' => ['present', 'array'],
            'meals.*.id' => ['nullable', 'integer'],
            'meals.*.description' => ['required', 'string'],
            'meals.*.planned_quantity' => ['required', 'integer', 'min:0'],
            'meals.*.source_type' => ['nullable', 'string', 'max:50'],
            'meals.*.source_name' => ['nullable', 'string', 'max:255'],
            'meals.*.restaurant_name' => ['nullable', 'string', 'max:255'],
            'meals.*.restaurant_contact' => ['nullable', 'string', 'max:50'],
            'meals.*.estimated_value' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'meals.*.items' => ['array'],
            'meals.*.items.*.id' => ['nullable', 'integer'],
            'meals.*.items.*.name' => ['required', 'string', 'max:255'],
            'meals.*.items.*.item_type' => ['required', Rule::in(RamadanIftarMealItem::types())],
            'meals.*.items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'meals.*.items.*.notes' => ['nullable', 'string'],
            'meals.*.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'gifts' => ['present', 'array'],
            'gifts.*.id' => ['nullable', 'integer'],
            'gifts.*.description' => ['required', 'string'],
            'gifts.*.planned_quantity' => ['required', 'integer', 'min:0'],
            'gifts.*.has_supporting_entity' => ['required', 'boolean'],
            'gifts.*.supporting_entity_name' => ['nullable', 'string', 'max:255'],
            'gifts.*.unit_value' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'program_segments' => ['present', 'array'],
            'program_segments.*.id' => ['nullable', 'integer'],
            'program_segments.*.name' => ['required', 'string', 'max:255'],
            'program_segments.*.starts_at' => ['nullable', 'date_format:H:i'],
            'program_segments.*.ends_at' => ['nullable', 'date_format:H:i'],
            'program_segments.*.duration_minutes' => ['nullable', 'integer', 'min:0'],
            'program_segments.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'program_segments.*.executor_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'program_segments.*.external_executor_name' => ['nullable', 'string', 'max:255'],
            'execution_teams' => ['present', 'array'],
            'execution_teams.*.id' => ['nullable', 'integer'],
            'execution_teams.*.name' => ['required', 'string', 'max:255'],
            'execution_teams.*.leader_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'execution_teams.*.planned_members_count' => ['nullable', 'integer', 'min:0'],
            'execution_teams.*.notes' => ['nullable', 'string'],
            'execution_teams.*.members' => ['array'],
            'execution_teams.*.members.*.id' => ['nullable', 'integer'],
            'execution_teams.*.members.*.user_id' => ['nullable', 'integer', 'exists:users,id'],
            'execution_teams.*.members.*.member_name' => ['nullable', 'string', 'max:255'],
            'execution_teams.*.members.*.phone' => ['nullable', 'string', 'max:50'],
            'execution_teams.*.members.*.role_name' => ['nullable', 'string', 'max:255'],
            'execution_teams.*.members.*.task_description' => ['nullable', 'string'],
            'volunteer_requirements' => ['present', 'array'],
            'volunteer_requirements.*.id' => ['nullable', 'integer'],
            'volunteer_requirements.*.beneficiary_segment_id' => ['nullable', 'integer', 'exists:beneficiary_segments,id'],
            'volunteer_requirements.*.gender' => ['nullable', 'string', 'max:50'],
            'volunteer_requirements.*.planned_count' => ['required', 'integer', 'min:0'],
            'volunteer_requirements.*.tasks_summary' => ['nullable', 'string'],
            'supplies' => ['present', 'array'],
            'supplies.*.id' => ['nullable', 'integer'],
            'supplies.*.item_name' => ['required', 'string', 'max:255'],
            'supplies.*.planned_quantity' => ['required', 'integer', 'min:0'],
            'supplies.*.provider_type' => ['nullable', 'string', 'max:50'],
            'supplies.*.provider_name' => ['nullable', 'string', 'max:255'],
            'supplies.*.estimated_value' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'supplies.*.notes' => ['nullable', 'string'],
            'execution_needs' => ['present', 'array'],
            'execution_needs.*.id' => ['nullable', 'integer'],
            'execution_needs.*.execution_need_type_id' => ['required', 'integer', 'distinct', 'exists:execution_need_types,id'],
            'execution_needs.*.is_required' => ['required', 'boolean'],
            'execution_needs.*.planned_details' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) return;
            $branchId = (int) $this->input('branch_id');
            if (! $this->canAccessBranch($branchId)) $validator->errors()->add('branch_id', __('validation.exists', ['attribute' => 'branch']));
            $agendaId = $this->input('agenda_event_id');
            if ($agendaId && ! AgendaEvent::query()->whereKey($agendaId)->forBranchAudience([$branchId])->exists()) {
                $validator->errors()->add('agenda_event_id', __('validation.exists', ['attribute' => 'agenda event']));
            }
            $this->validateBranchReference($validator, CommunityOrganization::class, 'community_organization_id', $branchId);
            $this->validateBranchReference($validator, LocalCommunity::class, 'local_community_id', $branchId);
            $this->validateBranchUsers($validator, $branchId);
            $this->validateConditionalLookups($validator);
            foreach ($this->input('execution_needs', []) as $i => $need) {
                if (! ExecutionNeedType::query()->canonical()->active()->forRamadanIftars()->whereKey($need['execution_need_type_id'])->exists()) {
                    $validator->errors()->add("execution_needs.$i.execution_need_type_id", __('validation.exists', ['attribute' => 'execution need type']));
                }
            }
        });
    }

    private function canAccessBranch(int $branchId): bool
    {
        $user = $this->user();
        return $user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch($branchId);
    }

    private function validateBranchReference(Validator $validator, string $model, string $field, int $branchId): void
    {
        $id = $this->input($field);
        if ($id && ! $model::query()->whereKey($id)->where('branch_id', $branchId)->where('is_active', true)->exists()) $validator->errors()->add($field, __('validation.exists', ['attribute' => $field]));
    }

    private function validateBranchUsers(Validator $validator, int $branchId): void
    {
        $paths = ['relations_officer_id' => $this->input('relations_officer_id')];
        foreach ($this->input('program_segments', []) as $i => $row) if (! empty($row['executor_user_id'])) $paths["program_segments.$i.executor_user_id"] = $row['executor_user_id'];
        foreach ($this->input('execution_teams', []) as $i => $team) {
            if (! empty($team['leader_user_id'])) $paths["execution_teams.$i.leader_user_id"] = $team['leader_user_id'];
            foreach ($team['members'] ?? [] as $j => $member) if (! empty($member['user_id'])) $paths["execution_teams.$i.members.$j.user_id"] = $member['user_id'];
        }
        foreach ($paths as $path => $id) {
            if ($id && ! User::query()->whereKey($id)->where('status', 'active')->where(function ($q) use ($branchId) { $q->where('branch_id', $branchId)->orWhereHas('assignedBranches', fn ($b) => $b->whereKey($branchId)); })->exists()) $validator->errors()->add($path, __('validation.exists', ['attribute' => $path]));
        }
    }

    private function validateConditionalLookups(Validator $validator): void
    {
        $host = $this->input('host_type');
        if (in_array($host, [RamadanIftar::HOST_ASSOCIATION, RamadanIftar::HOST_CENTER], true) && ! $this->input('community_organization_id')) $validator->errors()->add('community_organization_id', __('validation.required', ['attribute' => 'community organization']));
        if ($host === RamadanIftar::HOST_LOCAL_COMMUNITY && ! $this->input('local_community_id')) $validator->errors()->add('local_community_id', __('validation.required', ['attribute' => 'local community']));
        if ($id = $this->input('mobilization_method_id')) {
            $method = MobilizationMethod::query()->active()->find($id);
            if (! $method) $validator->errors()->add('mobilization_method_id', __('validation.exists', ['attribute' => 'mobilization method']));
            elseif ($method->is_other && blank($this->input('mobilization_method_other'))) $validator->errors()->add('mobilization_method_other', __('validation.required', ['attribute' => 'mobilization method other']));
        }
        foreach ($this->input('target_groups', []) as $i => $row) {
            $group = TargetGroup::query()->active()->forRamadanIftars()->find($row['target_group_id']);
            if (! $group) $validator->errors()->add("target_groups.$i.target_group_id", __('validation.exists', ['attribute' => 'target group']));
            elseif ($group->is_other && blank($row['target_group_custom_text'] ?? null)) $validator->errors()->add("target_groups.$i.target_group_custom_text", __('validation.required', ['attribute' => 'target group other']));
            if ($segmentId = ($row['beneficiary_segment_id'] ?? null)) {
                $segment = BeneficiarySegment::query()->active()->find($segmentId);
                if (! $segment) $validator->errors()->add("target_groups.$i.beneficiary_segment_id", __('validation.exists', ['attribute' => 'beneficiary segment']));
                elseif ($segment->is_other && blank($row['segment_custom_text'] ?? null)) $validator->errors()->add("target_groups.$i.segment_custom_text", __('validation.required', ['attribute' => 'segment other']));
            }
        }
        foreach ($this->input('volunteer_requirements', []) as $i => $requirement) {
            $segmentId = $requirement['beneficiary_segment_id'] ?? null;
            if ($segmentId && ! BeneficiarySegment::query()->active()->whereKey($segmentId)->exists()) {
                $validator->errors()->add("volunteer_requirements.$i.beneficiary_segment_id", __('validation.exists', ['attribute' => 'beneficiary segment']));
            }
        }
        foreach ($this->input('gifts', []) as $i => $gift) if (($gift['has_supporting_entity'] ?? false) && blank($gift['supporting_entity_name'] ?? null)) $validator->errors()->add("gifts.$i.supporting_entity_name", __('validation.required', ['attribute' => 'supporting entity']));
        foreach ($this->input('program_segments', []) as $i => $segment) if (! empty($segment['executor_user_id']) && filled($segment['external_executor_name'] ?? null)) $validator->errors()->add("program_segments.$i.external_executor_name", 'Choose an internal or external executor, not both.');
        foreach ($this->input('execution_teams', []) as $i => $team) foreach ($team['members'] ?? [] as $j => $member) if (empty($member['user_id']) && blank($member['member_name'] ?? null)) $validator->errors()->add("execution_teams.$i.members.$j.member_name", 'A user or member name is required.');
    }
}
