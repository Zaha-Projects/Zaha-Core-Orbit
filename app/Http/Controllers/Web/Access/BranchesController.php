<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchesController extends Controller
{
    private function validatedBranchData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:32'],
        ]);
    }

    public function index()
    {
        $branches = Branch::orderBy('name')->get();

        return view('pages.access.branches.index', compact('branches'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedBranchData($request);

        Branch::create($data);

        return redirect()
            ->route('role.super_admin.branches')
            ->with('status', __('app.roles.super_admin.branches.created'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $this->validatedBranchData($request);

        $branch->update($data);

        return redirect()
            ->route('role.super_admin.branches')
            ->with('status', __('app.roles.super_admin.branches.updated', ['branch' => $branch->name]));
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();

        return redirect()
            ->route('role.super_admin.branches')
            ->with('status', __('app.roles.super_admin.branches.deleted', ['branch' => $branch->name]));
    }
}
