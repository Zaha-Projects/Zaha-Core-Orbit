<?php

namespace App\Http\Controllers\Web\Access;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Center;
use Illuminate\Http\Request;

class CentersController extends Controller
{
    public function index()
    {
        $centers = Center::with('branch')->orderBy('name')->get();
        $branches = Branch::orderBy('name')->get();

        return view('pages.access.centers.index', compact('centers', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        Center::create($data);

        return redirect()
            ->route('role.super_admin.centers')
            ->with('status', __('app.roles.super_admin.centers.created'));
    }

    public function update(Request $request, Center $center)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $center->update($data);

        return redirect()
            ->route('role.super_admin.centers')
            ->with('status', __('app.roles.super_admin.centers.updated', ['center' => $center->name]));
    }

    public function destroy(Center $center)
    {
        $center->delete();

        return redirect()
            ->route('role.super_admin.centers')
            ->with('status', __('app.roles.super_admin.centers.deleted', ['center' => $center->name]));
    }
}
