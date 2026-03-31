<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Center;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UsersController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'permissions', 'branch', 'center'])->orderBy('name')->get();
        $roles = Role::orderBy('name')->get();
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        return view('pages.access.users.index', compact('users', 'roles', 'permissions', 'branches', 'centers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'center_id' => ['nullable', 'exists:centers,id'],
            'status' => ['required', 'string', 'max:50'],
            'role' => ['required', 'exists:roles,name'],
            'password' => ['required', 'string', 'min:8'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
            'status' => $data['status'],
            'password' => Hash::make($data['password']),
        ]);

        $user->assignRole($data['role']);
        $user->syncPermissions($data['permissions'] ?? []);

        return redirect()
            ->route('role.super_admin.users')
            ->with('status', __('app.roles.super_admin.users.created'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'phone' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'center_id' => ['nullable', 'exists:centers,id'],
            'status' => ['required', 'string', 'max:50'],
            'role' => ['required', 'exists:roles,name'],
            'password' => ['nullable', 'string', 'min:8'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
            'status' => $data['status'],
            'password' => $data['password'] ? Hash::make($data['password']) : $user->password,
        ]);

        $user->syncRoles([$data['role']]);
        $user->syncPermissions($data['permissions'] ?? []);

        return redirect()
            ->route('role.super_admin.users')
            ->with('status', __('app.roles.super_admin.users.updated', ['user' => $user->name]));
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('role.super_admin.users')
            ->with('status', __('app.roles.super_admin.users.deleted', ['user' => $user->name]));
    }
}
