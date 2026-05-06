<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;

class EvaluationAssignmentsController extends Controller
{
    public function index()
    {
        $branches = Branch::query()->orderBy('name')->get();
        $officers = User::query()
            ->role('evaluation_officer')
            ->with('assignedBranches:id,name')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('roles.super_admin.evaluation_assignments', compact('branches', 'officers'));
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->hasRole('evaluation_officer'), 404);

        $data = $request->validate([
            'assigned_branch_ids' => ['nullable', 'array'],
            'assigned_branch_ids.*' => ['integer', 'exists:branches,id'],
        ]);

        $user->assignedBranches()->sync($data['assigned_branch_ids'] ?? []);

        return back()->with('status', 'تم تحديث إسناد الفروع لمسؤول التقييم.');
    }
}
