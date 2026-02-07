<?php

namespace App\Http\Controllers\Roles\Programs;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityTeam;
use Illuminate\Http\Request;

class MonthlyActivityTeamController extends Controller
{
    public function store(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'member_name' => ['required', 'string', 'max:255'],
            'role_desc' => ['nullable', 'string', 'max:255'],
        ]);

        MonthlyActivityTeam::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'member_name' => $data['member_name'],
            'role_desc' => $data['role_desc'] ?? null,
        ]);

        return redirect()
            ->route('role.programs.activities.edit', $monthlyActivity)
            ->with('status', __('app.roles.programs.monthly_activities.team.created'));
    }

    public function update(Request $request, MonthlyActivityTeam $monthlyActivityTeam)
    {
        $data = $request->validate([
            'member_name' => ['required', 'string', 'max:255'],
            'role_desc' => ['nullable', 'string', 'max:255'],
        ]);

        $monthlyActivityTeam->update([
            'member_name' => $data['member_name'],
            'role_desc' => $data['role_desc'] ?? null,
        ]);

        return redirect()
            ->route('role.programs.activities.edit', $monthlyActivityTeam->monthly_activity_id)
            ->with('status', __('app.roles.programs.monthly_activities.team.updated'));
    }

    public function destroy(MonthlyActivityTeam $monthlyActivityTeam)
    {
        $activityId = $monthlyActivityTeam->monthly_activity_id;
        $monthlyActivityTeam->delete();

        return redirect()
            ->route('role.programs.activities.edit', $activityId)
            ->with('status', __('app.roles.programs.monthly_activities.team.deleted'));
    }
}
