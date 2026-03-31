<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesController extends Controller
{
    public function index()
    {
        $roles = Role::with('permissions')->orderBy('name')->get();
        $permissions = Permission::orderBy('module')->orderBy('name')->get()->groupBy('module');

        return view('pages.access.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('role.super_admin.roles')->with('status', __('app.roles.super_admin.roles.created'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
            'name_ar' => ['nullable', 'string', 'max:255'],
            'name_en' => ['nullable', 'string', 'max:255'],
            'permissions' => ['array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->update([
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_en' => $data['name_en'] ?? null,
        ]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('role.super_admin.roles')->with('status', __('app.roles.super_admin.roles.updated', ['role' => $role->name]));
    }

    public function destroy(Role $role)
    {
        abort_if($role->name === 'super_admin', 422, __('Cannot delete super_admin role'));

        $role->delete();

        return redirect()->route('role.super_admin.roles')->with('status', __('app.roles.super_admin.roles.deleted', ['role' => $role->name]));
    }
}
