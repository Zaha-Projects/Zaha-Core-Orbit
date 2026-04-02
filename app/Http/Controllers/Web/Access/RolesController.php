<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

class RolesController extends Controller
{
    private function normalizePermissionIds(array $permissions): array
    {
        return collect($permissions)
            ->map(fn ($permission) => (int) $permission)
            ->filter(fn (int $permissionId) => $permissionId > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function index()
    {
        $this->authorize('roles.view');

        $roles = Role::query()
            ->where('guard_name', 'web')
            ->with('permissions')
            ->orderBy('name')
            ->get();
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module');

        return view('pages.access.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $this->authorize('roles.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($this->normalizePermissionIds($data['permissions'] ?? []));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('role.super_admin.roles')->with('status', __('app.roles.super_admin.roles.created'));
    }

    public function update(Request $request, Role $role)
    {
        $this->authorize('roles.manage');

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id),
            ],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('guard_name', 'web')],
        ]);

        $role->update([
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
        ]);
        $role->syncPermissions($this->normalizePermissionIds($data['permissions'] ?? []));
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('role.super_admin.roles')->with('status', __('app.roles.super_admin.roles.updated', ['role' => $role->name]));
    }

    public function destroy(Role $role)
    {
        $this->authorize('roles.manage');

        abort_if($role->name === 'super_admin', 422, __('Cannot delete super_admin role'));

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()->route('role.super_admin.roles')->with('status', __('app.roles.super_admin.roles.deleted', ['role' => $role->name]));
    }
}
