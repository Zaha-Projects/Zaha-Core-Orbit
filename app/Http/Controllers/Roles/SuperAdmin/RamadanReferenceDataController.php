<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\LocalCommunity;
use App\Modules\Events\Models\MealType;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\TargetGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RamadanReferenceDataController extends Controller
{
    private const MODELS = [
        'target_groups' => TargetGroup::class,
        'beneficiary_segments' => BeneficiarySegment::class,
        'execution_need_types' => ExecutionNeedType::class,
        'community_organizations' => CommunityOrganization::class,
        'local_communities' => LocalCommunity::class,
        'mobilization_methods' => MobilizationMethod::class,
        'monitoring_methods' => MonitoringMethod::class,
        'meal_types' => MealType::class,
    ];

    public function index()
    {
        $resources = collect(self::MODELS)->mapWithKeys(function (string $model, string $key): array {
            $query = $model::query();
            if (in_array($key, ['community_organizations', 'local_communities'], true)) {
                $query->with('branch')->orderBy('branch_id')->orderBy('name');
            } else {
                $query->orderBy('sort_order')->orderBy('id');
            }

            return [$key => $query->get()];
        });

        return view('pages.admin.ramadan-reference-data.index', [
            'resources' => $resources,
            'branches' => Branch::query()->orderBy('name')->get(),
            'usageScopes' => ExecutionNeedType::usageScopes(),
            'segmentDimensions' => BeneficiarySegment::dimensions(),
        ]);
    }

    public function store(Request $request, string $resource)
    {
        $model = $this->modelFor($resource);
        $record = new $model;
        $record->fill($this->validated($request, $resource, $record));
        if ($record instanceof ExecutionNeedType) $record->is_canonical = true;
        $record->save();

        return back()->with('status', 'تمت إضافة القيمة المرجعية.');
    }

    public function update(Request $request, string $resource, int $id)
    {
        $model = $this->modelFor($resource);
        /** @var Model $record */
        $record = $model::query()->findOrFail($id);
        $oldScope = $record instanceof ExecutionNeedType ? $record->usage_scope : null;
        $record->fill($this->validated($request, $resource, $record));
        if ($record instanceof ExecutionNeedType && $oldScope !== $record->usage_scope) $record->scope_configured_at = now();
        $record->save();

        return back()->with('status', 'تم تحديث القيمة المرجعية.');
    }

    private function modelFor(string $resource): string
    {
        abort_unless(isset(self::MODELS[$resource]), 404);

        return self::MODELS[$resource];
    }

    private function validated(Request $request, string $resource, Model $record): array
    {
        $boolean = ['is_active', 'is_other', 'is_monthly_activity', 'is_ramadan_iftar', 'mandatory_for_ramadan'];
        foreach ($boolean as $field) {
            if ($request->has($field)) $request->merge([$field => $request->boolean($field)]);
        }
        $table = $record->getTable();
        $id = $record->getKey();
        $commonCode = ['required', 'string', 'max:100', Rule::unique($table, 'code')->ignore($id)];
        $uniqueName = fn (string $column): array => ['required', 'string', 'max:255', Rule::unique($table, $column)->ignore($id)];
        $rules = match ($resource) {
            'target_groups' => ['code' => $commonCode, 'name' => $uniqueName('name'), 'is_other' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'], 'is_monthly_activity' => ['required', 'boolean'], 'is_ramadan_iftar' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0']],
            'beneficiary_segments' => ['code' => $commonCode, 'name_ar' => $uniqueName('name_ar'), 'name_en' => ['required', 'string', 'max:255'], 'dimension' => ['required', Rule::in(BeneficiarySegment::dimensions())], 'minimum_age' => ['nullable', 'integer', 'min:0', 'max:120'], 'maximum_age' => ['nullable', 'integer', 'min:0', 'max:120', 'gte:minimum_age'], 'is_other' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0']],
            'execution_need_types' => ['code' => $commonCode, 'name' => $uniqueName('name'), 'description' => ['nullable', 'string'], 'usage_scope' => ['required', Rule::in(ExecutionNeedType::usageScopes())], 'mandatory_for_ramadan' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0']],
            'community_organizations', 'local_communities' => ['branch_id' => ['required', 'integer', 'exists:branches,id'], 'name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')->where(fn ($query) => $query->where('branch_id', (int) $request->input('branch_id')))->ignore($id)], 'contact_name' => ['nullable', 'string', 'max:255'], 'contact_phone' => ['nullable', 'string', 'max:50'], 'location_name' => ['nullable', 'string', 'max:255'], 'address' => ['nullable', 'string'], 'google_maps_url' => ['nullable', 'url', 'max:2048'], 'is_active' => ['required', 'boolean']],
            'mobilization_methods' => ['code' => $commonCode, 'name_ar' => $uniqueName('name_ar'), 'name_en' => ['required', 'string', 'max:255'], 'is_other' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0']],
            'monitoring_methods' => ['code' => $commonCode, 'name_ar' => $uniqueName('name_ar'), 'name_en' => ['required', 'string', 'max:255'], 'is_active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0']],
            'meal_types' => ['code' => $commonCode, 'name_ar' => $uniqueName('name_ar'), 'description' => ['nullable', 'string'], 'is_active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0']],
        };

        return $request->validate($rules, [
            'code.unique' => 'الرمز مستخدم مسبقًا.',
            'name.unique' => 'الاسم مستخدم مسبقًا.',
            'name_ar.unique' => 'الاسم العربي مستخدم مسبقًا.',
        ]);
    }
}
