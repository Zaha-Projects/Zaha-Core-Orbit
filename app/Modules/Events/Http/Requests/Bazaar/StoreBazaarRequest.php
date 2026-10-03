<?php

namespace App\Modules\Events\Http\Requests\Bazaar;

use App\Models\User;
use App\Modules\Events\Models\Bazaar;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\TargetGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBazaarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $bazaar = $this->route('bazaar');
        return $user && ($user->hasRole('super_admin') || $user->can($bazaar ? 'bazaars.edit' : 'bazaars.create'))
            && (! $bazaar || $user->hasRole('super_admin') || $user->hasAccessToScopedBranch((int) $bazaar->branch_id));
    }

    protected function prepareForValidation(): void
    {
        $user = $this->user();
        $this->merge([
            'branch_id' => $this->route('bazaar')?->branch_id ?? $user?->branch_id ?? collect($user?->scopedBranchIds() ?? [])->first(),
            'relations_officer_id' => $user?->hasRole('relations_officer') ? $user->id : ($this->route('bazaar')?->relations_officer_id ?? $user?->id),
            'tables' => array_values(array_filter($this->input('tables', []), 'is_array')),
            'target_group_ids' => array_values(array_unique(array_filter(array_map('intval', $this->input('target_group_ids', []))))),
        ]);
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'], 'relations_officer_id' => ['required', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'], 'bazaar_date' => ['required', 'date'],
            'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'location_type' => ['required', Rule::in(['inside', 'outside'])], 'location_name' => ['required', 'string', 'max:255'],
            'location_details' => ['nullable', 'string', 'max:2000'], 'map_url' => ['nullable', 'url', 'max:2048'],
            'planned_table_count' => ['required', 'integer', 'min:1', 'max:500'],
            'target_group_ids' => ['required', 'array', 'min:1'], 'target_group_ids.*' => ['integer', 'distinct', 'exists:target_groups,id'],
            'execution_needs' => ['array'], 'execution_needs.*.execution_need_type_id' => ['required', 'integer', 'distinct', 'exists:execution_need_types,id'],
            'execution_needs.*.selected' => ['nullable', 'boolean'], 'execution_needs.*.planned_details' => ['nullable', 'string', 'max:2000'],
            'execution_needs.*.invitation_type' => ['nullable', Rule::in(['paper', 'electronic'])],
            'execution_needs.*.distribution_channel' => ['nullable', Rule::in(['whatsapp', 'sponsored_ad', 'other'])],
            'execution_needs.*.distribution_other' => ['nullable', 'string', 'max:500'],
            'tables' => ['required', 'array', 'min:1'], 'tables.*.table_number' => ['required', 'integer', 'min:1', 'distinct'],
            'tables.*.rental_type' => ['required', Rule::in(['individual', 'organization'])],
            'tables.*.tenant_name' => ['nullable', 'string', 'max:255'], 'tables.*.tenant_phone' => ['nullable', 'string', 'max:25', 'regex:/^[0-9+()\-\s]{7,25}$/'],
            'tables.*.community_organization_id' => ['nullable', 'integer', 'exists:community_organizations,id'],
            'tables.*.table_liaison_name' => ['nullable', 'string', 'max:255'], 'tables.*.table_liaison_phone' => ['nullable', 'string', 'max:25'],
            'tables.*.planned_material_description' => ['required', 'string', 'max:2000'],
            'tables.*.planned_rental_amount' => ['required', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'tables.*.liaison_user_id' => ['required', 'integer', 'exists:users,id'], 'tables.*.notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ((int) $this->input('planned_table_count') !== count($this->input('tables', []))) $validator->errors()->add('planned_table_count', 'يجب أن يساوي عدد الطاولات عدد سجلات الطاولات المخططة.');
            $branchId = (int) $this->input('branch_id');
            if (! $this->user()->hasRole('super_admin') && ! $this->user()->hasAccessToScopedBranch($branchId)) $validator->errors()->add('branch_id', 'الفرع غير متاح.');
            $availableTargets = TargetGroup::query()->active()->forBazaars()->pluck('id');
            foreach ($this->input('target_group_ids', []) as $i => $id) if (! $availableTargets->contains((int) $id)) $validator->errors()->add("target_group_ids.$i", 'الفئة المستهدفة غير متاحة للبازارات.');
            $types = ExecutionNeedType::query()->canonical()->availableFor(EventSubjectTypes::BAZAAR)->get()->keyBy('id');
            $selected = collect($this->input('execution_needs', []))->filter(fn ($row) => (bool) ($row['selected'] ?? false));
            foreach ($types->filter->isMandatoryForBazaar() as $type) if (! $selected->contains(fn ($row) => (int) $row['execution_need_type_id'] === (int) $type->id)) $validator->errors()->add('execution_needs', "احتياج {$type->name} إلزامي للبازار.");
            foreach ($selected as $i => $row) {
                $type = $types->get((int) ($row['execution_need_type_id'] ?? 0));
                if (! $type) { $validator->errors()->add("execution_needs.$i", 'احتياج التنفيذ غير متاح للبازار.'); continue; }
                if ($type->code === 'invitations') {
                    if (empty($row['invitation_type'])) $validator->errors()->add("execution_needs.$i.invitation_type", 'نوع بطاقة الدعوة مطلوب.');
                    if (($row['invitation_type'] ?? null) === 'electronic' && empty($row['distribution_channel'])) $validator->errors()->add("execution_needs.$i.distribution_channel", 'قناة التوزيع الإلكتروني مطلوبة.');
                    if (($row['distribution_channel'] ?? null) === 'other' && blank($row['distribution_other'] ?? null)) $validator->errors()->add("execution_needs.$i.distribution_other", 'يرجى وصف قناة التوزيع الأخرى.');
                } elseif (blank($row['planned_details'] ?? null)) $validator->errors()->add("execution_needs.$i.planned_details", 'تفاصيل احتياج التنفيذ مطلوبة عند اختياره.');
            }
            foreach ($this->input('tables', []) as $i => $table) {
                if (($table['rental_type'] ?? null) === 'individual' && (blank($table['tenant_name'] ?? null) || blank($table['tenant_phone'] ?? null))) $validator->errors()->add("tables.$i.tenant_name", 'اسم وهاتف المستأجر الفرد مطلوبان.');
                if (($table['rental_type'] ?? null) === 'organization' && empty($table['community_organization_id'])) $validator->errors()->add("tables.$i.community_organization_id", 'اختر المؤسسة أو الجمعية أو المجتمع المحلي.');
                foreach (['community_organization_id' => CommunityOrganization::class] as $field => $model) if (! empty($table[$field]) && ! $model::query()->whereKey($table[$field])->where('branch_id', $branchId)->active()->exists()) $validator->errors()->add("tables.$i.$field", 'الجهة غير متاحة لهذا الفرع.');
                if (! User::query()->whereKey($table['liaison_user_id'] ?? 0)->where('status', 'active')->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereHas('assignedBranches', fn ($b) => $b->whereKey($branchId)))->exists()) $validator->errors()->add("tables.$i.liaison_user_id", 'ضابط الارتباط غير متاح لهذا الفرع.');
            }
        });
    }
}
