<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class WorkflowsController extends Controller
{
    public function index()
    {
        $workflows = Workflow::with(['steps' => fn ($query) => $query->orderBy('step_order'), 'steps.role'])->orderBy('module')->orderBy('id')->get();
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();
        return view('pages.access.workflows.index', compact('workflows', 'roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', 'unique:workflows,code'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Workflow::create([
            'module' => $data['module'],
            'code' => $data['code'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('status', __('app.roles.super_admin.workflows.created'));
    }

    public function update(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', 'unique:workflows,code,' . $workflow->id],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $workflow->update([
            'module' => $data['module'],
            'code' => $data['code'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('status', __('app.roles.super_admin.workflows.updated'));
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();

        return back()->with('status', __('app.roles.super_admin.workflows.deleted'));
    }

    public function storeStep(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'step_key' => ['required', 'string', 'max:100'],
            'step_order' => ['required', 'integer', 'min:1'],
            'approval_level' => ['required', 'integer', 'min:1'],
            'step_type' => ['required', 'in:sub,main'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_editable' => ['nullable', 'boolean'],
        ]);

        $workflow->steps()->create([
            'step_key' => $data['step_key'],
            'step_order' => $data['step_order'],
            'approval_level' => $data['approval_level'],
            'step_type' => $data['step_type'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'role_id' => $data['role_id'],
            'permission_id' => null,
            'is_editable' => (bool) ($data['is_editable'] ?? true),
        ]);

        return back()->with('status', __('app.roles.super_admin.workflows.step_created'));
    }

    public function updateStep(Request $request, WorkflowStep $step)
    {
        $data = $request->validate([
            'step_key' => ['required', 'string', 'max:100'],
            'step_order' => ['required', 'integer', 'min:1'],
            'approval_level' => ['required', 'integer', 'min:1'],
            'step_type' => ['required', 'in:sub,main'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'role_id' => ['required', 'exists:roles,id'],
            'is_editable' => ['nullable', 'boolean'],
        ]);

        $step->update([
            'step_key' => $data['step_key'],
            'step_order' => $data['step_order'],
            'approval_level' => $data['approval_level'],
            'step_type' => $data['step_type'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'role_id' => $data['role_id'],
            'permission_id' => null,
            'is_editable' => (bool) ($data['is_editable'] ?? false),
        ]);

        return back()->with('status', __('app.roles.super_admin.workflows.step_updated'));
    }



    public function reorderSteps(Request $request, Workflow $workflow)
    {
        $data = $request->validate([
            'ordered_ids' => ['required', 'string'],
        ]);

        $orderedIds = collect(explode(',', (string) $data['ordered_ids']))
            ->map(static fn ($id) => (int) trim($id))
            ->filter()
            ->values();

        $existingStepIds = $workflow->steps()->pluck('id')->sort()->values();

        abort_if(
            $orderedIds->count() !== $existingStepIds->count()
            || $orderedIds->sort()->values()->all() !== $existingStepIds->all(),
            422,
            app()->getLocale() === 'ar' ? 'ترتيب المراحل غير صالح.' : 'Invalid steps order.'
        );

        DB::transaction(function () use ($orderedIds, $workflow) {
            foreach ($orderedIds as $index => $stepId) {
                $workflow->steps()->whereKey($stepId)->update([
                    'step_order' => $index + 1,
                ]);
            }
        });

        return back()->with('status', app()->getLocale() === 'ar' ? 'تم تحديث ترتيب المراحل بنجاح.' : 'Workflow steps order updated successfully.');
    }

    public function destroyStep(WorkflowStep $step)
    {
        $step->delete();

        return back()->with('status', __('app.roles.super_admin.workflows.step_deleted'));
    }
}
