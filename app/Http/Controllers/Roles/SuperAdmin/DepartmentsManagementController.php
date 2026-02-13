<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DepartmentsManagementController extends Controller
{
    private function indexRouteParameters(Request $request): array
    {
        return [
            'search' => trim((string) $request->input('search', '')),
            'sort' => $request->input('sort') === 'name_desc' ? 'name_desc' : 'name_asc',
        ];
    }

    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $sort = $request->query('sort') === 'name_desc' ? 'name_desc' : 'name_asc';

        $departments = Department::query()
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderBy('name', $sort === 'name_desc' ? 'desc' : 'asc')
            ->get();

        return view('roles.super_admin.departments', compact('departments', 'search', 'sort'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);

        Department::create($data);

        return redirect()
            ->route('role.super_admin.departments', $this->indexRouteParameters($request))
            ->with('status', __('app.roles.super_admin.departments.created'));
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,' . $department->id],
        ]);

        $department->update($data);

        return redirect()
            ->route('role.super_admin.departments', $this->indexRouteParameters($request))
            ->with('status', __('app.roles.super_admin.departments.updated', ['department' => $department->name]));
    }

    public function destroy(Request $request, Department $department)
    {
        $name = $department->name;
        $department->delete();

        return redirect()
            ->route('role.super_admin.departments', $this->indexRouteParameters($request))
            ->with('status', __('app.roles.super_admin.departments.deleted', ['department' => $name]));
    }
}
