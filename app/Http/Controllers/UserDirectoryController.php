<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserDirectoryController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'role' => ['nullable', 'string', 'exists:roles,name'],
        ]);

        $users = User::query()
            ->select('users.*')
            ->with(['branch', 'assignedBranches', 'roles'])
            ->leftJoin('branches', 'users.branch_id', '=', 'branches.id')
            ->when($filters['branch_id'] ?? null, function ($query, int $branchId): void {
                $query->where(function ($branchQuery) use ($branchId): void {
                    $branchQuery->where('branch_id', $branchId)
                        ->orWhereHas('assignedBranches', fn ($assignedQuery) => $assignedQuery->where('branches.id', $branchId));
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role): mixed => $query->role($role))
            ->orderByRaw('branches.id IS NULL')
            ->orderBy('branches.id')
            ->orderBy('users.name')
            ->paginate(25)
            ->withQueryString();

        return view('directory.users', [
            'users' => $users,
            'branches' => Branch::query()->orderByRaw('is_main DESC')->orderBy('id')->get(),
            'roles' => Role::query()->where('guard_name', 'web')->orderBy('name_ar')->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }
}
