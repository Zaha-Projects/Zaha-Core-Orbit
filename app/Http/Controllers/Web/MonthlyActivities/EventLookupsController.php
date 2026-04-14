<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentUnit;
use App\Models\EvaluationQuestion;
use App\Models\EventCategory;
use App\Models\EventStatusLookup;
use App\Models\TargetGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventLookupsController extends Controller
{
    public function index()
    {
        $targetGroups = TargetGroup::query()->orderBy('sort_order')->orderBy('name')->get();
        $evaluationQuestions = EvaluationQuestion::query()->orderBy('sort_order')->orderBy('question')->get();
        $departments = Department::query()->orderBy('sort_order')->orderBy('name')->get();
        $departmentUnits = DepartmentUnit::query()->orderBy('sort_order')->orderBy('name')->get();
        $eventCategories = EventCategory::query()->with('department')->orderBy('sort_order')->orderBy('name')->get();
        $statusLookups = EventStatusLookup::query()->ordered()->get()->groupBy('module');

        return view('pages.monthly_activities.lookups.admin', compact(
            'targetGroups',
            'evaluationQuestions',
            'departments',
            'departmentUnits',
            'eventCategories',
            'statusLookups',
        ));
    }

    public function storeDepartment(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:32'],
        ]);

        Department::query()->create([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'color_hex' => $data['color_hex'] ?? '#2563EB',
            'icon' => $data['icon'] ?? 'DPT',
        ]);

        return back()->with('status', 'تمت إضافة وحدة/قسم جديد.');
    }

    public function updateDepartment(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:32'],
        ]);

        $department->update([
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'color_hex' => $data['color_hex'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        return back()->with('status', 'تم تحديث الوحدة/القسم.');
    }

    public function storeTargetGroup(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_other' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        TargetGroup::create([
            'name' => $data['name'],
            'is_other' => (bool) ($data['is_other'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('status', 'تم إضافة فئة مستهدفة.');
    }

    public function updateTargetGroup(Request $request, TargetGroup $targetGroup)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('target_groups', 'name')->ignore($targetGroup->id)],
            'is_other' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $targetGroup->update([
            'name' => $data['name'],
            'is_other' => (bool) ($data['is_other'] ?? false),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'تم تحديث الفئة المستهدفة.');
    }

    public function storeEvaluationQuestion(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer_type' => ['required', 'in:score_5,text'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        EvaluationQuestion::create([
            'question' => $data['question'],
            'answer_type' => $data['answer_type'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('status', 'تم إضافة سؤال تقييم.');
    }

    public function updateEvaluationQuestion(Request $request, EvaluationQuestion $evaluationQuestion)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer_type' => ['required', 'in:score_5,text'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $evaluationQuestion->update([
            'question' => $data['question'],
            'answer_type' => $data['answer_type'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('status', 'تم تحديث سؤال التقييم.');
    }

    public function storeDepartmentUnit(Request $request)
    {
        $data = $request->validate([
            'unit_key' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:department_units,unit_key'],
            'name' => ['required', 'string', 'max:255'],
            'role_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:32'],
        ]);

        DepartmentUnit::create([
            'unit_key' => $data['unit_key'],
            'name' => $data['name'],
            'role_name' => $data['role_name'] ?: null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'color_hex' => $data['color_hex'] ?? '#2563EB',
            'icon' => '🏢',
        ]);

        return back()->with('status', 'تمت إضافة وحدة قسم جديدة.');
    }

    public function updateDepartmentVisual(Request $request, Department $department)
    {
        $data = $request->validate([
            'color_hex' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', 'max:32'],
        ]);

        $department->update($data);

        return back()->with('status', 'تم تحديث لون/أيقونة القسم.');
    }

    public function updateUnitVisual(Request $request, DepartmentUnit $departmentUnit)
    {
        $data = $request->validate([
            'color_hex' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['required', 'string', 'max:32'],
        ]);

        $departmentUnit->update($data);

        return back()->with('status', 'تم تحديث لون/أيقونة الوحدة.');
    }

    public function updateDepartmentUnit(Request $request, DepartmentUnit $departmentUnit)
    {
        $data = $request->validate([
            'unit_key' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('department_units', 'unit_key')->ignore($departmentUnit->id)],
            'name' => ['required', 'string', 'max:255'],
            'role_name' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'color_hex' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'icon' => ['nullable', 'string', 'max:32'],
        ]);

        $departmentUnit->update([
            'unit_key' => $data['unit_key'],
            'name' => $data['name'],
            'role_name' => $data['role_name'] ?: null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
            'color_hex' => $data['color_hex'] ?? null,
            'icon' => $data['icon'] ?? null,
        ]);

        return back()->with('status', 'تم تحديث وحدة/قسم الشراكة.');
    }

    public function storeEventCategory(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        EventCategory::query()->create([
            'department_id' => (int) $data['department_id'],
            'name' => $data['name'],
            'active' => (bool) ($data['active'] ?? true),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'تمت إضافة تصنيف جديد.');
    }

    public function updateEventCategory(Request $request, EventCategory $eventCategory)
    {
        $data = $request->validate([
            'department_id' => ['required', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ]);

        $eventCategory->update([
            'department_id' => (int) $data['department_id'],
            'name' => $data['name'],
            'active' => (bool) ($data['active'] ?? false),
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return back()->with('status', 'تم تحديث التصنيف.');
    }

    public function storeStatusLookup(Request $request)
    {
        $data = $request->validate([
            'module' => ['required', Rule::in(['agenda', 'monthly_activities'])],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('event_status_lookups')->where(fn ($query) => $query->where('module', $request->input('module')))],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        EventStatusLookup::query()->create([
            'module' => $data['module'],
            'code' => $data['code'],
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('status', 'تمت إضافة حالة جديدة.');
    }

    public function updateStatusLookup(Request $request, EventStatusLookup $eventStatusLookup)
    {
        $data = $request->validate([
            'module' => ['required', Rule::in(['agenda', 'monthly_activities'])],
            'code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('event_status_lookups')->ignore($eventStatusLookup->id)->where(fn ($query) => $query->where('module', $request->input('module')))],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $eventStatusLookup->update([
            'module' => $data['module'],
            'code' => $data['code'],
            'name' => $data['name'],
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('status', 'تم تحديث الحالة.');
    }
}
