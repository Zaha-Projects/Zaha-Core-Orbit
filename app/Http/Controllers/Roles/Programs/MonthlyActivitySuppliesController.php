<?php

namespace App\Http\Controllers\Roles\Programs;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivitySupply;
use Illuminate\Http\Request;

class MonthlyActivitySuppliesController extends Controller
{
    public function store(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:available,missing'],
            'available' => ['nullable', 'boolean'],
        ]);

        MonthlyActivitySupply::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'item_name' => $data['item_name'],
            'status' => $data['status'],
            'available' => ($data['status'] ?? 'available') === 'available',
        ]);

        return redirect()
            ->route('role.relations.activities.edit', $monthlyActivity)
            ->with('status', __('app.roles.programs.monthly_activities.supplies.created'));
    }

    public function update(Request $request, MonthlyActivitySupply $monthlyActivitySupply)
    {
        $data = $request->validate([
            'item_name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:available,missing'],
            'available' => ['nullable', 'boolean'],
        ]);

        $monthlyActivitySupply->update([
            'item_name' => $data['item_name'],
            'status' => $data['status'],
            'available' => ($data['status'] ?? 'available') === 'available',
        ]);

        return redirect()
            ->route('role.relations.activities.edit', $monthlyActivitySupply->monthly_activity_id)
            ->with('status', __('app.roles.programs.monthly_activities.supplies.updated'));
    }

    public function destroy(MonthlyActivitySupply $monthlyActivitySupply)
    {
        $activityId = $monthlyActivitySupply->monthly_activity_id;
        $monthlyActivitySupply->delete();

        return redirect()
            ->route('role.relations.activities.edit', $activityId)
            ->with('status', __('app.roles.programs.monthly_activities.supplies.deleted'));
    }
}
