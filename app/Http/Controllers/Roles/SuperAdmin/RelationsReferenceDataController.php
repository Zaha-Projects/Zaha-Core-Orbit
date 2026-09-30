<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\TargetGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RelationsReferenceDataController extends Controller
{
    private const MODELS = [
        'target_groups' => TargetGroup::class,
        'execution_needs' => ExecutionNeedType::class,
    ];

    public function index()
    {
        return view('pages.admin.relations-reference-data.index', [
            'resources' => collect(self::MODELS)->mapWithKeys(fn (string $model, string $key) => [
                $key => $model::query()->orderBy('sort_order')->orderBy('id')->get(),
            ]),
        ]);
    }

    public function store(Request $request, string $resource)
    {
        $model = $this->modelFor($resource);
        $record = new $model;
        $record->fill($this->validated($request, $resource, $record));
        if ($record instanceof ExecutionNeedType) {
            $record->is_canonical = true;
            $this->applyExecutionNeedConfiguration($record, $request);
        }
        $record->save();

        return back()->with('status', 'تمت إضافة القيمة المرجعية.');
    }

    public function update(Request $request, string $resource, int $id)
    {
        $record = $this->findRecord($resource, $id);
        $record->fill($this->validated($request, $resource, $record));
        if ($record instanceof ExecutionNeedType) {
            $this->applyExecutionNeedConfiguration($record, $request);
            $record->scope_configured_at = now();
        }
        $record->save();

        return back()->with('status', 'تم تحديث القيمة المرجعية.');
    }

    public function toggle(string $resource, int $id)
    {
        $record = $this->findRecord($resource, $id);
        $record->update(['is_active' => ! $record->is_active]);

        return back()->with('status', $record->is_active ? 'تم تفعيل القيمة المرجعية.' : 'تم تعطيل القيمة المرجعية.');
    }

    public function destroy(string $resource, int $id)
    {
        $record = $this->findRecord($resource, $id);
        if ($this->isReferenced($resource, $id)) {
            return back()->withErrors(['delete' => 'لا يمكن حذف القيمة لأنها مستخدمة في سجلات قائمة. عطّلها بدلًا من ذلك.']);
        }
        try {
            $record->delete();
        } catch (QueryException) {
            return back()->withErrors(['delete' => 'لا يمكن حذف القيمة لأنها مرتبطة بسجلات قائمة. عطّلها بدلًا من ذلك.']);
        }

        return back()->with('status', 'تم حذف القيمة المرجعية.');
    }

    private function validated(Request $request, string $resource, Model $record): array
    {
        foreach (['is_active', 'is_other', 'is_monthly_activity', 'is_ramadan_iftar', 'mandatory_for_monthly', 'mandatory_for_ramadan'] as $field) {
            if ($request->has($field)) $request->merge([$field => $request->boolean($field)]);
        }
        $table = $record->getTable();
        $id = $record->getKey();
        $code = ['required', 'string', 'max:100', Rule::unique($table, 'code')->ignore($id)];

        return $request->validate(match ($resource) {
            'target_groups' => [
                'code' => $code, 'type' => ['required', Rule::in(TargetGroup::types())], 'name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')->ignore($id)],
                'is_other' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'],
                'is_monthly_activity' => ['required', 'boolean'], 'is_ramadan_iftar' => ['required', 'boolean'],
                'sort_order' => ['required', 'integer', 'min:0'],
            ],
            'execution_needs' => [
                'code' => $code, 'name' => ['required', 'string', 'max:255', Rule::unique($table, 'name')->ignore($id)],
                'description' => ['nullable', 'string'], 'is_active' => ['required', 'boolean'],
                'is_monthly_activity' => ['required', 'boolean'], 'mandatory_for_monthly' => ['required', 'boolean'],
                'is_ramadan_iftar' => ['required', 'boolean'], 'mandatory_for_ramadan' => ['required', 'boolean'],
                'sort_order' => ['required', 'integer', 'min:0'],
            ],
        });
    }

    private function applyExecutionNeedConfiguration(ExecutionNeedType $record, Request $request): void
    {
        $record->module_config = [
            'monthly_activity' => ['available' => $request->boolean('is_monthly_activity'), 'required' => $request->boolean('is_monthly_activity') && $request->boolean('mandatory_for_monthly')],
            'ramadan_iftar' => ['available' => $request->boolean('is_ramadan_iftar'), 'required' => $request->boolean('is_ramadan_iftar') && $request->boolean('mandatory_for_ramadan')],
        ];
        $record->mandatory_for_monthly = $record->module_config['monthly_activity']['required'];
        $record->mandatory_for_ramadan = $record->module_config['ramadan_iftar']['required'];
    }

    private function isReferenced(string $resource, int $id): bool
    {
        return match ($resource) {
            'target_groups' => DB::table('event_target_group')->where('target_group_id', $id)->orWhere('classification_target_group_id', $id)->exists()
                || DB::table('subject_volunteer_requirements')->where('target_group_id', $id)->exists()
                || DB::table('ramadan_iftar_attendees')->where('target_group_id', $id)->exists(),
            'execution_needs' => DB::table('subject_execution_needs')->where('execution_need_type_id', $id)->exists(),
        };
    }

    private function modelFor(string $resource): string
    {
        abort_unless(isset(self::MODELS[$resource]), 404);
        return self::MODELS[$resource];
    }

    private function findRecord(string $resource, int $id): Model
    {
        $model = $this->modelFor($resource);
        return $model::query()->findOrFail($id);
    }
}
