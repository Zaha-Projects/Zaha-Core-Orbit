<?php

namespace App\Modules\Events\Http\Requests\Ramadan;

use App\Modules\Events\Models\AgendaEvent;
use App\Modules\Events\Models\TargetGroup;
use App\Models\User;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\LocalCommunity;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarMealItem;
use App\Modules\Events\Models\RamadanIftarGift;
use App\Modules\Events\Models\RamadanPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRamadanIftarRequest extends FormRequest
{
    private const CONTACT_PHONE_REGEX = '/^[0-9+()\-\s]{7,25}$/';
    private bool $prepared = false;
    private array $submittedNeedDetails = [];
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
        if (! $this->prepared) {
            $this->submittedNeedDetails = array_intersect(array_values(ExecutionNeedType::IFTAR_DETAIL_FIELDS), array_keys($this->all()));
            $this->prepared = true;
        }
        $collections = ['attendees', 'target_groups', 'meals', 'gifts', 'program_segments', 'execution_teams', 'volunteer_requirements', 'supplies', 'execution_needs'];
        $branchId = $this->route('ramadanIftar')?->branch_id
            ?? $this->user()?->branch_id
            ?? collect($this->user()?->scopedBranchIds() ?? [])->first();
        $rows = collect($collections)->mapWithKeys(function (string $key): array {
            $value = $this->input($key, []);

            return [$key => is_array($value) ? $this->meaningfulRows($key, $value) : $value];
        })->all();
        $hostType = $this->input('host_type');
        if ($hostType === 'organization') {
            $hostType = $this->route('ramadanIftar')?->host_type === RamadanIftar::HOST_CENTER
                ? RamadanIftar::HOST_CENTER
                : RamadanIftar::HOST_ASSOCIATION;
        }
        $periodId = $this->route('ramadanIftar')?->ramadan_period_id;
        if (! $this->route('ramadanIftar') || $this->route('ramadanIftar')?->planned_date?->toDateString() !== $this->input('planned_date')) {
            $periodId = RamadanPeriod::current()?->getKey();
        }
        $this->merge(array_merge(
            $rows,
            ['branch_id' => $branchId, 'ramadan_period_id' => $periodId, 'host_type' => $hostType]
        ));
    }

    private function meaningfulRows(string $key, array $rows): array
    {
        $identity = [
            'attendees' => 'full_name', 'target_groups' => 'target_group_id', 'meals' => 'description',
            'gifts' => 'description', 'program_segments' => 'name', 'execution_teams' => 'name',
            'volunteer_requirements' => 'planned_count', 'supplies' => 'item_name',
            'execution_needs' => 'execution_need_type_id',
        ][$key];

        return array_values(array_filter($rows, function ($row) use ($key, $identity): bool {
            if (! is_array($row)) return false;
            if (filled($row[$identity] ?? null)) return true;
            if ($key === 'attendees') return filled($row['phone'] ?? null) || isset($row['age']);
            if ($key === 'meals') {
                return filled($row['restaurant_name'] ?? null)
                    || filled($row['restaurant_contact'] ?? null)
                    || collect($row['items'] ?? [])->contains(fn ($item) => filled($item['name'] ?? null) || filled($item['notes'] ?? null));
            }
            if ($key === 'execution_teams') {
                return collect($row['members'] ?? [])->contains(fn ($member) => filled($member['member_name'] ?? null) || filled($member['role_name'] ?? null) || filled($member['task_description'] ?? null));
            }
            if ($key === 'supplies') return filled($row['provider_name'] ?? null) || filled($row['notes'] ?? null) || (int) ($row['planned_quantity'] ?? 0) > 0;

            return false;
        }));
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'ramadan_period_id' => [Rule::requiredIf(fn () => ! $this->route('ramadanIftar') || $this->route('ramadanIftar')?->planned_date?->toDateString() !== $this->input('planned_date')), 'nullable', 'integer', 'exists:ramadan_periods,id'],
            'agenda_event_id' => ['nullable', 'integer', 'exists:agenda_events,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'relations_officer_id' => ['required', 'integer', 'exists:users,id'],
            'planned_date' => ['required', 'date'],
            'time_from' => ['nullable', 'date_format:H:i,H:i:s'],
            'time_to' => ['nullable', 'date_format:H:i,H:i:s', 'after_or_equal:time_from'],
            'location_type' => ['required', Rule::in(RamadanIftar::locationTypes())],
            'location_name' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => in_array($this->input('host_type'), [RamadanIftar::HOST_ASSOCIATION, RamadanIftar::HOST_CENTER], true))],
            'address' => ['nullable', 'string'],
            'google_maps_url' => ['nullable', 'url', 'max:2048'],
            'contact_name' => ['nullable', 'string', 'max:255', Rule::requiredIf(fn () => in_array($this->input('host_type'), [RamadanIftar::HOST_ASSOCIATION, RamadanIftar::HOST_CENTER], true))],
            'contact_phone' => ['nullable', 'string', 'max:25', 'regex:'.self::CONTACT_PHONE_REGEX, Rule::requiredIf(fn () => in_array($this->input('host_type'), [RamadanIftar::HOST_ASSOCIATION, RamadanIftar::HOST_CENTER], true))],
            'supporting_entity_name' => ['nullable', 'string', 'max:255'],
            'host_type' => ['required', Rule::in(RamadanIftar::hostTypes())],
            'community_organization_id' => ['nullable', 'integer', 'exists:community_organizations,id'],
            'local_community_id' => ['nullable', 'integer', 'exists:local_communities,id'],
            'mobilization_method_id' => ['nullable', 'integer', 'exists:mobilization_methods,id'],
            'mobilization_method_other' => ['nullable', 'string'],
            'attendees' => ['array'],
            'attendees.*.id' => ['nullable', 'integer'],
            'attendees.*.full_name' => ['required', 'string', 'max:255'],
            'attendees.*.phone' => ['required', 'string', 'max:25', 'regex:'.self::CONTACT_PHONE_REGEX],
            'attendees.*.age' => ['required', 'integer', 'min:0', 'max:120'],
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
            'meals.*.planned_quantity' => ['required', 'integer', 'min:1'],
            'meals.*.source_type' => ['nullable', 'string', 'max:50'],
            'meals.*.source_name' => ['nullable', 'string', 'max:255'],
            'meals.*.restaurant_name' => ['required', 'string', 'max:255'],
            'meals.*.restaurant_contact' => ['required', 'string', 'max:25', 'regex:'.self::CONTACT_PHONE_REGEX],
            'meals.*.estimated_value' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'meals.*.items' => ['required', 'array', 'min:1'],
            'meals.*.items.*.id' => ['nullable', 'integer'],
            'meals.*.items.*.name' => ['required', 'string', 'max:255'],
            'meals.*.items.*.item_type' => ['required', Rule::in(RamadanIftarMealItem::types())],
            'meals.*.items.*.quantity' => ['nullable', 'integer', 'min:0'],
            'meals.*.items.*.notes' => ['required', 'string'],
            'meals.*.items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'gifts' => ['present', 'array'],
            'gifts.*.id' => ['nullable', 'integer'],
            'gifts.*.gift_type' => ['required', Rule::in(RamadanIftarGift::types())],
            'gifts.*.description' => ['required', 'string'],
            'gifts.*.planned_quantity' => ['required', 'integer', 'min:0'],
            'gifts.*.has_supporting_entity' => ['required', 'boolean'],
            'gifts.*.supporting_entity_name' => ['nullable', 'string', 'max:255'],
            'gifts.*.unit_value' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'program_segments' => ['present', 'array'],
            'program_segments.*.id' => ['nullable', 'integer'],
            'program_segments.*.name' => ['required', 'string', 'max:255'],
            'program_segments.*.starts_at' => ['nullable', 'date_format:H:i,H:i:s'],
            'program_segments.*.ends_at' => ['nullable', 'date_format:H:i,H:i:s'],
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
            'volunteer_requirements.*.beneficiary_segment_id' => ['required', 'integer', 'exists:beneficiary_segments,id'],
            'volunteer_requirements.*.gender' => ['required', Rule::in(['male', 'female', 'mixed'])],
            'volunteer_requirements.*.planned_count' => ['required', 'integer', 'min:1'],
            'volunteer_requirements.*.tasks_summary' => ['required', 'string'],
            'supplies' => ['present', 'array'],
            'supplies.*.id' => ['nullable', 'integer'],
            'supplies.*.item_name' => ['required', 'string', 'max:255'],
            'supplies.*.planned_quantity' => ['required', 'integer', 'min:0'],
            'supplies.*.provider_type' => ['nullable', 'string', 'max:50'],
            'supplies.*.provider_name' => ['nullable', 'string', 'max:255'],
            'supplies.*.estimated_value' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'supplies.*.planned_available' => ['required', 'boolean'],
            'supplies.*.notes' => ['nullable', 'string'],
            'execution_needs' => ['present', 'array'],
            'execution_needs.*.id' => ['nullable', 'integer'],
            'execution_needs.*.execution_need_type_id' => ['required', 'integer', 'distinct', 'exists:execution_need_types,id'],
            'execution_needs.*.is_required' => ['required', 'boolean'],
            'execution_needs.*.planned_details' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => __('ramadan_iftars.labels.name'),
            'branch_id' => __('ramadan_iftars.fields.branch'),
            'agenda_event_id' => __('ramadan_iftars.labels.agenda_event'),
            'relations_officer_id' => __('ramadan_iftars.fields.relations_officer'),
            'planned_date' => __('ramadan_iftars.fields.planned_date'),
            'community_organization_id' => __('ramadan_iftars.labels.community_organization'),
            'local_community_id' => __('ramadan_iftars.labels.local_community'),
            'mobilization_method_id' => __('ramadan_iftars.labels.mobilization_method'),
            'mobilization_method_other' => __('ramadan_iftars.labels.mobilization_method_other'),
            'location_name' => __('ramadan_iftars.labels.location_name'),
            'contact_name' => __('ramadan_iftars.labels.contact_name'),
            'contact_phone' => __('ramadan_iftars.labels.contact_phone'),
            'attendees.*.full_name' => 'اسم الحاضر',
            'attendees.*.phone' => 'هاتف الحاضر',
            'attendees.*.age' => 'عمر الحاضر',
            'meals.*.description' => 'وصف الوجبة',
            'meals.*.planned_quantity' => 'عدد الوجبات',
            'meals.*.restaurant_name' => 'اسم المطعم',
            'meals.*.restaurant_contact' => 'رقم التواصل مع المطعم',
            'meals.*.items.*.name' => 'اسم الطبق',
            'meals.*.items.*.notes' => 'مكونات الطبق',
            'volunteer_requirements.*.beneficiary_segment_id' => 'الفئة العمرية للمتطوعين',
            'volunteer_requirements.*.gender' => 'جنس المتطوعين',
            'volunteer_requirements.*.planned_count' => 'عدد المتطوعين',
            'volunteer_requirements.*.tasks_summary' => 'مهام المتطوعين',
            'target_groups.*.target_group_id' => 'الفئة المستهدفة',
            'target_groups.*.beneficiary_segment_id' => 'شريحة المستفيدين',
            'target_groups.*.planned_count' => 'العدد المخطط للفئة',
            'execution_teams.*.name' => 'اسم فريق التنفيذ',
            'execution_teams.*.members.*.member_name' => 'اسم عضو فريق التنفيذ',
            'program_segments.*.name' => 'اسم فقرة البرنامج',
            'supplies.*.item_name' => 'اسم المستلزم',
            'supplies.*.planned_quantity' => 'كمية المستلزم',
            'gifts.*.description' => 'وصف الهدية أو الدرع',
            'gifts.*.planned_quantity' => 'كمية الهدايا أو الدروع',
            'execution_needs.*.execution_need_type_id' => 'احتياج التنفيذ',
        ];
    }

    public function messages(): array
    {
        return [
            'contact_phone.regex' => 'أدخل رقم تواصل صالحًا باستخدام الأرقام والمسافات و + أو - أو الأقواس فقط.',
            'attendees.*.phone.regex' => 'أدخل رقم تواصل صالحًا للحاضر.',
            'meals.*.restaurant_contact.regex' => 'أدخل رقم تواصل صالحًا للمطعم.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) return;
            $branchId = (int) $this->input('branch_id');
            $existingIftar = $this->route('ramadanIftar');
            $unchangedDate = $existingIftar instanceof RamadanIftar && $existingIftar->planned_date?->toDateString() === $this->input('planned_date');
            if (! $unchangedDate && ! RamadanPeriod::contains($this->input('planned_date'))) {
                $validator->errors()->add('planned_date', 'تاريخ الإفطار يجب أن يكون ضمن فترة شهر رمضان المحددة من الإدارة.');
            }
            if (! $this->canAccessBranch($branchId)) $validator->errors()->add('branch_id', __('validation.exists', ['attribute' => __('ramadan_iftars.fields.branch')]));
            $agendaId = $this->input('agenda_event_id');
            if ($agendaId && ! AgendaEvent::query()->whereKey($agendaId)->forBranchAudience([$branchId])->exists()) {
                $validator->errors()->add('agenda_event_id', __('validation.exists', ['attribute' => __('ramadan_iftars.labels.agenda_event')]));
            }
            $this->validateBranchReference($validator, CommunityOrganization::class, 'community_organization_id', $branchId);
            $this->validateBranchReference($validator, LocalCommunity::class, 'local_community_id', $branchId);
            $this->validateBranchUsers($validator, $branchId);
            $this->validateConditionalLookups($validator);
            $types = ExecutionNeedType::ramadanAvailableTypes()->keyBy('id');
            foreach (ExecutionNeedType::IFTAR_DETAIL_FIELDS as $code => $field) {
                if (! $types->contains('code', $code) && in_array($field, $this->submittedNeedDetails, true)) {
                    $validator->errors()->add($field, 'هذا الاحتياج غير متاح للتعديل؛ بياناته السابقة محفوظة للقراءة فقط.');
                }
            }
            foreach ($this->input('execution_needs', []) as $i => $need) {
                if (! $types->has($need['execution_need_type_id'])) {
                    $validator->errors()->add("execution_needs.$i.execution_need_type_id", __('validation.exists', ['attribute' => __('ramadan_iftars.labels.execution_need')]));
                }
            }
            $selected = collect($this->input('execution_needs'))->filter(fn ($row) => (bool) ($row['is_required'] ?? false))->pluck('execution_need_type_id')->map(fn ($id) => (int) $id);
            foreach ($types->filter->isMandatoryForRamadan() as $type) {
                if (! $selected->contains((int) $type->id)) $validator->errors()->add('execution_needs', "متطلب التنفيذ {$type->name} إجباري.");
            }
            if ($types->firstWhere('code', 'execution_team') && empty($this->input('execution_teams'))) $validator->errors()->add('execution_teams', 'فريق التنفيذ إجباري.');
            $this->validateConditionalDetails($validator, $types);
        });
    }

    private function validateConditionalDetails(Validator $validator, $types): void
    {
        $enabled = collect($this->input('execution_needs'))->filter(fn ($row) => (bool) ($row['is_required'] ?? false))
            ->pluck('execution_need_type_id')->map(fn ($id) => (int) $id);
        foreach (['supplies' => 'supplies', 'gifts_shields' => 'gifts'] as $code => $collection) {
            $type = $types->firstWhere('code', $code);
            if ($type && $enabled->contains((int) $type->id) && empty($this->input($collection))) {
                $validator->errors()->add($collection, "يجب إدخال تفاصيل {$type->name} عند تفعيله.");
            }
        }
        foreach ($this->input('supplies') as $i => $row) {
            if (blank($row['item_name'] ?? null) || ! isset($row['planned_quantity'])) $validator->errors()->add("supplies.$i", 'بيانات سطر المستلزمات غير مكتملة.');
        }
        foreach ($this->input('gifts') as $i => $row) {
            if (blank($row['description'] ?? null) || ! isset($row['planned_quantity'])) $validator->errors()->add("gifts.$i", 'بيانات سطر الهدايا أو الدروع غير مكتملة.');
        }
        if ($this->input('host_type') === RamadanIftar::HOST_LOCAL_COMMUNITY && empty($this->input('attendees'))) {
            $validator->errors()->add('attendees', 'يجب إدخال كشف حضور للمجتمع المحلي.');
        }
    }

    private function canAccessBranch(int $branchId): bool
    {
        $user = $this->user();
        return $user->hasRole('super_admin') || $user->can('branches.view.all') || $user->hasAccessToScopedBranch($branchId);
    }

    private function validateBranchReference(Validator $validator, string $model, string $field, int $branchId): void
    {
        $id = $this->input($field);
        $labels = [
            'community_organization_id' => __('ramadan_iftars.labels.community_organization'),
            'local_community_id' => __('ramadan_iftars.labels.local_community'),
        ];
        $isExistingValue = $this->route('ramadanIftar') instanceof RamadanIftar
            && (int) $this->route('ramadanIftar')->getAttribute($field) === (int) $id;
        $reference = $model::query()->whereKey($id)->where('branch_id', $branchId);
        if (! $isExistingValue) $reference->where('is_active', true);
        if ($id && ! $reference->exists()) $validator->errors()->add($field, __('validation.exists', ['attribute' => $labels[$field]]));
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
            if ($id && ! User::query()->whereKey($id)->where('status', 'active')->where(function ($q) use ($branchId) { $q->where('branch_id', $branchId)->orWhereHas('assignedBranches', fn ($b) => $b->whereKey($branchId)); })->exists()) $validator->errors()->add($path, 'المستخدم المحدد غير متاح لهذا الفرع.');
        }
    }

    private function validateConditionalLookups(Validator $validator): void
    {
        $host = $this->input('host_type');
        if (in_array($host, [RamadanIftar::HOST_ASSOCIATION, RamadanIftar::HOST_CENTER], true) && ! $this->input('community_organization_id')) $validator->errors()->add('community_organization_id', __('validation.required', ['attribute' => __('ramadan_iftars.labels.community_organization')]));
        if ($host === RamadanIftar::HOST_LOCAL_COMMUNITY && ! $this->input('local_community_id')) $validator->errors()->add('local_community_id', __('validation.required', ['attribute' => __('ramadan_iftars.labels.local_community')]));
        if ($host === RamadanIftar::HOST_LOCAL_COMMUNITY && ! $this->input('mobilization_method_id')) $validator->errors()->add('mobilization_method_id', __('validation.required', ['attribute' => __('ramadan_iftars.labels.mobilization_method')]));
        if ($id = $this->input('mobilization_method_id')) {
            $isExistingMethod = $this->route('ramadanIftar') instanceof RamadanIftar && (int) $this->route('ramadanIftar')->mobilization_method_id === (int) $id;
            $methodQuery = MobilizationMethod::query()->whereKey($id);
            if (! $isExistingMethod) $methodQuery->active();
            $method = $methodQuery->first();
            if (! $method) $validator->errors()->add('mobilization_method_id', __('validation.exists', ['attribute' => __('ramadan_iftars.labels.mobilization_method')]));
            elseif ($method->is_other && blank($this->input('mobilization_method_other'))) $validator->errors()->add('mobilization_method_other', __('validation.required', ['attribute' => __('ramadan_iftars.labels.mobilization_method_other')]));
        }
        $existingTargetIds = $this->route('ramadanIftar') instanceof RamadanIftar
            ? $this->route('ramadanIftar')->targetGroupSelections()->pluck('target_group_id')
            : collect();
        $existingSegmentIds = $this->route('ramadanIftar') instanceof RamadanIftar
            ? $this->route('ramadanIftar')->targetGroupSelections()->pluck('beneficiary_segment_id')
                ->merge($this->route('ramadanIftar')->volunteerRequirements()->pluck('beneficiary_segment_id'))->filter()
            : collect();
        foreach ($this->input('target_groups', []) as $i => $row) {
            $group = TargetGroup::query()->whereKey($row['target_group_id'])->where(function ($query) use ($existingTargetIds) {
                $query->where(fn ($available) => $available->active()->forRamadanIftars())->orWhereIn('id', $existingTargetIds);
            })->first();
            if (! $group) $validator->errors()->add("target_groups.$i.target_group_id", __('validation.exists', ['attribute' => 'الفئة المستهدفة']));
            elseif ($group->is_other && blank($row['target_group_custom_text'] ?? null)) $validator->errors()->add("target_groups.$i.target_group_custom_text", __('validation.required', ['attribute' => 'تفصيل الفئة الأخرى']));
            if ($segmentId = ($row['beneficiary_segment_id'] ?? null)) {
                $segment = BeneficiarySegment::query()->whereKey($segmentId)->where(fn ($query) => $query->active()->orWhereIn('id', $existingSegmentIds))->first();
                if (! $segment) $validator->errors()->add("target_groups.$i.beneficiary_segment_id", __('validation.exists', ['attribute' => 'شريحة المستفيدين']));
                elseif ($segment->is_other && blank($row['segment_custom_text'] ?? null)) $validator->errors()->add("target_groups.$i.segment_custom_text", __('validation.required', ['attribute' => 'تفصيل الشريحة الأخرى']));
            }
        }
        foreach ($this->input('volunteer_requirements', []) as $i => $requirement) {
            $segmentId = $requirement['beneficiary_segment_id'] ?? null;
            if ($segmentId && ! BeneficiarySegment::query()->whereKey($segmentId)->where(fn ($query) => $query->active()->orWhereIn('id', $existingSegmentIds))->exists()) {
                $validator->errors()->add("volunteer_requirements.$i.beneficiary_segment_id", __('validation.exists', ['attribute' => 'الفئة العمرية للمتطوعين']));
            }
        }
        foreach ($this->input('gifts', []) as $i => $gift) if (($gift['has_supporting_entity'] ?? false) && blank($gift['supporting_entity_name'] ?? null)) $validator->errors()->add("gifts.$i.supporting_entity_name", __('validation.required', ['attribute' => 'اسم الجهة الداعمة']));
        foreach ($this->input('program_segments', []) as $i => $segment) if (! empty($segment['executor_user_id']) && filled($segment['external_executor_name'] ?? null)) $validator->errors()->add("program_segments.$i.external_executor_name", 'اختر منفذًا داخليًا أو خارجيًا، وليس كليهما.');
        foreach ($this->input('execution_teams', []) as $i => $team) foreach ($team['members'] ?? [] as $j => $member) if (empty($member['user_id']) && blank($member['member_name'] ?? null)) $validator->errors()->add("execution_teams.$i.members.$j.member_name", 'يجب اختيار مستخدم أو إدخال اسم عضو الفريق.');
    }
}
