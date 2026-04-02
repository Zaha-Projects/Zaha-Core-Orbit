<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

class UsersController extends Controller
{
    public function index()
    {
        $this->authorize('users.view');

        $users = User::with(['roles', 'permissions', 'deniedPermissions', 'branch'])->orderBy('name')->get();
        $roles = Role::query()->where('guard_name', 'web')->with('permissions')->orderBy('name')->get();
        $permissions = Permission::query()->where('guard_name', 'web')->orderBy('module')->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        return view('pages.access.users.index', compact('users', 'roles', 'permissions', 'branches'));
    }

    public function store(Request $request)
    {
        $this->authorize('users.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'status' => ['required', 'string', 'max:50'],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'password' => ['required', 'string', 'min:8'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'branch_id' => $data['branch_id'],
            'status' => $data['status'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);

        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', $data['role'])
            ->with('permissions:id,name')
            ->first();
        $rolePermissionNames = $role?->permissions->pluck('name') ?? collect();
        $extraPermissions = collect($data['permissions'] ?? [])
            ->unique()
            ->diff($rolePermissionNames)
            ->values()
            ->all();
        $user->syncPermissions($extraPermissions);

        $denied = Permission::query()->whereIn('name', $data['denied_permissions'] ?? [])->pluck('id');
        $user->deniedPermissions()->sync($denied);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('role.super_admin.users')
            ->with('status', __('app.roles.super_admin.users.created'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorize('users.manage');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'center_id' => ['nullable'],
            'status' => ['required', 'string', 'max:50'],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'password' => ['nullable', 'string', 'min:8'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'branch_id' => $data['branch_id'],
            'center_id' => null,
            'status' => $data['status'],
            'password' => $data['password'] ? Hash::make($data['password']) : $user->password,
        ]);

        $user->syncRoles([$data['role']]);

        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', $data['role'])
            ->with('permissions:id,name')
            ->first();
        $rolePermissionNames = $role?->permissions->pluck('name') ?? collect();
        $directOverrides = collect($data['permissions'] ?? [])
            ->unique()
            ->diff($rolePermissionNames)
            ->values()
            ->all();
        $user->syncPermissions($directOverrides);

        $denied = Permission::query()->whereIn('name', $data['denied_permissions'] ?? [])->pluck('id');
        $user->deniedPermissions()->sync($denied);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('role.super_admin.users')
            ->with('status', __('app.roles.super_admin.users.updated', ['user' => $user->name]));
    }

    public function destroy(User $user)
    {
        $this->authorize('users.manage');

        $user->delete();

        return redirect()
            ->route('role.super_admin.users')
            ->with('status', __('app.roles.super_admin.users.deleted', ['user' => $user->name]));
    }
}
