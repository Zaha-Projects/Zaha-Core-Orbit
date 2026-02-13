<?php

namespace App\Http\Controllers\Roles\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentsManagementController extends Controller
{
    public function index()
    {
        $departments = Department::orderBy('name')->get();

        return view('roles.super_admin.departments', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name'],
        ]);

        Department::create($data);

        return redirect()
            ->route('role.super_admin.departments')
            ->with('status', __('app.roles.super_admin.departments.created'));
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:departments,name,' . $department->id],
        ]);

        $department->update($data);

        return redirect()
            ->route('role.super_admin.departments')
            ->with('status', __('app.roles.super_admin.departments.updated', ['department' => $department->name]));
    }

    public function destroy(Department $department)
    {
        $name = $department->name;
        $department->delete();

        return redirect()
            ->route('role.super_admin.departments')
            ->with('status', __('app.roles.super_admin.departments.deleted', ['department' => $name]));
    }
}
