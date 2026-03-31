<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Workflow;
use App\Models\WorkflowStep;
use App\Services\WorkflowGovernanceService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class WorkflowsController extends Controller
{
    public function index()
    {
        $workflows = Workflow::with(['steps.role', 'steps.permission'])->orderBy('module')->orderBy('id')->get();
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('module')->orderBy('name')->get();

        return view('pages.access.workflows.index', compact('workflows', 'roles', 'permissions'));
    }

    public function store(Request $request, WorkflowGovernanceService $governance)
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', 'unique:workflows,code'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $governance->createWorkflow($data);
        } catch (QueryException $exception) {
            abort_if($this->isActiveModuleUniqueViolation($exception), 422, __('app.roles.super_admin.workflows.errors.single_active_per_module'));
            throw $exception;
        }

        return back()->with('status', __('app.roles.super_admin.workflows.created'));
    }

    public function update(Request $request, Workflow $workflow, WorkflowGovernanceService $governance)
    {
        $data = $request->validate([
            'module' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100', 'unique:workflows,code,' . $workflow->id],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $governance->updateWorkflow($workflow, $data);
        } catch (QueryException $exception) {
            abort_if($this->isActiveModuleUniqueViolation($exception), 422, __('app.roles.super_admin.workflows.errors.single_active_per_module'));
            throw $exception;
        }

        return back()->with('status', __('app.roles.super_admin.workflows.updated'));
    }

    public function destroy(Workflow $workflow)
    {
        $workflow->delete();

        return back()->with('status', __('app.roles.super_admin.workflows.deleted'));
    }

    public function storeStep(Request $request, Workflow $workflow, WorkflowGovernanceService $governance)
    {
        $data = $this->validateStepRequest($request);
        abort_if(empty($data['role_id']) && empty($data['permission_id']), 422, __('app.roles.super_admin.workflows.errors.role_or_permission_required'));
        $governance->validateStepUniqueness($workflow, $data);

        $workflow->steps()->create([
            ...$data,
            'is_editable' => (bool) ($data['is_editable'] ?? true),
        ]);

        return back()->with('status', __('app.roles.super_admin.workflows.step_created'));
    }

    public function updateStep(Request $request, WorkflowStep $step, WorkflowGovernanceService $governance)
    {
        $data = $this->validateStepRequest($request);
        abort_if(empty($data['role_id']) && empty($data['permission_id']), 422, __('app.roles.super_admin.workflows.errors.role_or_permission_required'));
        $governance->validateStepUniqueness($step->workflow, $data, $step->id);

        $step->update([
            ...$data,
            'is_editable' => (bool) ($data['is_editable'] ?? false),
        ]);

        return back()->with('status', __('app.roles.super_admin.workflows.step_updated'));
    }

    public function destroyStep(WorkflowStep $step)
    {
        $step->delete();

        return back()->with('status', __('app.roles.super_admin.workflows.step_deleted'));
    }

    private function validateStepRequest(Request $request): array
    {
        return $request->validate([
            'step_key' => ['required', 'string', 'max:100'],
            'step_order' => ['required', 'integer', 'min:1'],
            'approval_level' => ['required', 'integer', 'min:1'],
            'step_type' => ['required', 'in:sub,main'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'role_id' => ['nullable', 'exists:roles,id'],
            'permission_id' => ['nullable', 'exists:permissions,id'],
            'is_editable' => ['nullable', 'boolean'],
        ]);
    }

    private function isActiveModuleUniqueViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'workflows_unique_active_module');
    }
}
