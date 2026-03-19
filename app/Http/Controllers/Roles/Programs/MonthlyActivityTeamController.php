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
            'team_name' => ['nullable', 'string', 'max:255'],
            'member_name' => ['required', 'string', 'max:255'],
            'member_email' => ['nullable', 'email', 'max:255'],
            'role_desc' => ['nullable', 'string', 'max:255'],
        ]);

        MonthlyActivityTeam::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'team_name' => $data['team_name'] ?? null,
            'member_name' => $data['member_name'],
            'member_email' => $data['member_email'] ?? null,
            'role_desc' => $data['role_desc'] ?? null,
        ]);

        return redirect()
            ->route('role.relations.activities.edit', $monthlyActivity)
            ->with('status', __('app.roles.programs.monthly_activities.team.created'));
    }

    public function update(Request $request, MonthlyActivityTeam $monthlyActivityTeam)
    {
        $data = $request->validate([
            'team_name' => ['nullable', 'string', 'max:255'],
            'member_name' => ['required', 'string', 'max:255'],
            'member_email' => ['nullable', 'email', 'max:255'],
            'role_desc' => ['nullable', 'string', 'max:255'],
        ]);

        $monthlyActivityTeam->update([
            'team_name' => $data['team_name'] ?? null,
            'member_name' => $data['member_name'],
            'member_email' => $data['member_email'] ?? null,
            'role_desc' => $data['role_desc'] ?? null,
        ]);

        return redirect()
            ->route('role.relations.activities.edit', $monthlyActivityTeam->monthly_activity_id)
            ->with('status', __('app.roles.programs.monthly_activities.team.updated'));
    }

    public function destroy(MonthlyActivityTeam $monthlyActivityTeam)
    {
        $activityId = $monthlyActivityTeam->monthly_activity_id;
        $monthlyActivityTeam->delete();

        return redirect()
            ->route('role.relations.activities.edit', $activityId)
            ->with('status', __('app.roles.programs.monthly_activities.team.deleted'));
    }
}
