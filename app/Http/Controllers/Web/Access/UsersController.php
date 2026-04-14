<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;

class UsersController extends Controller
{
    public function index()
    {
        $this->authorize('users.view');

        $users = User::with(['roles', 'permissions', 'deniedPermissions', 'branch', 'assignedBranches'])->orderBy('name')->get();
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
            'assigned_branch_ids' => ['nullable', 'array'],
            'assigned_branch_ids.*' => ['integer', 'exists:branches,id'],
            'status' => ['required', 'string', 'max:50'],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'password' => ['required', 'string', 'min:8'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $data = $this->normalizeBranchAssignments($data);
        $this->ensureBranchCoordinatorAssignments($data);

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
        $this->syncAssignedBranches($user, $data);

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
            'assigned_branch_ids' => ['nullable', 'array'],
            'assigned_branch_ids.*' => ['integer', 'exists:branches,id'],
            'center_id' => ['nullable'],
            'status' => ['required', 'string', 'max:50'],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'password' => ['nullable', 'string', 'min:8'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
            'denied_permissions' => ['nullable', 'array'],
            'denied_permissions.*' => ['string', Rule::exists('permissions', 'name')->where('guard_name', 'web')],
        ]);

        $data = $this->normalizeBranchAssignments($data);
        $this->ensureBranchCoordinatorAssignments($data);

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
        $this->syncAssignedBranches($user, $data);

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

    protected function normalizeBranchAssignments(array $data): array
    {
        $assignedBranchIds = collect($data['assigned_branch_ids'] ?? [])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if (($data['role'] ?? null) !== 'branch_coordinator') {
            $data['assigned_branch_ids'] = [];

            return $data;
        }

        if (filled($data['branch_id'] ?? null)) {
            $assignedBranchIds->push((int) $data['branch_id']);
        }

        $assignedBranchIds = $assignedBranchIds->unique()->values();

        if ($assignedBranchIds->isNotEmpty() && empty($data['branch_id'])) {
            $data['branch_id'] = $assignedBranchIds->first();
        }

        $data['assigned_branch_ids'] = $assignedBranchIds->all();

        return $data;
    }

    protected function syncAssignedBranches(User $user, array $data): void
    {
        $user->assignedBranches()->sync($data['assigned_branch_ids'] ?? []);
    }

    protected function ensureBranchCoordinatorAssignments(array $data): void
    {
        if (($data['role'] ?? null) !== 'branch_coordinator') {
            return;
        }

        if (! empty($data['assigned_branch_ids'] ?? [])) {
            return;
        }

        throw ValidationException::withMessages([
            'assigned_branch_ids' => __('app.roles.super_admin.users.fields.assigned_branches_required'),
        ]);
    }
}
