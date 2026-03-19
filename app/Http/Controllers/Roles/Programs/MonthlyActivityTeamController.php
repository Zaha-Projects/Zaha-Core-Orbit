<?php

namespace App\Http\Controllers\Roles\Programs;

use App\Http\Controllers\Controller;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityTeam;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MonthlyActivityTeamController extends Controller
{
    public function store(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:255'],
            'member_name' => ['required', 'string', 'max:255'],
            'member_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('monthly_activity_team', 'member_email')->where(fn ($q) => $q->where('monthly_activity_id', $monthlyActivity->id)),
            ],
            'role_desc' => ['required', 'string', 'max:255'],
        ]);

        MonthlyActivityTeam::create([
            'monthly_activity_id' => $monthlyActivity->id,
            'team_name' => $data['team_name'],
            'member_name' => $data['member_name'],
            'member_email' => $data['member_email'],
            'role_desc' => $data['role_desc'],
        ]);

        return redirect()
            ->route('role.relations.activities.edit', $monthlyActivity)
            ->with('status', __('app.roles.programs.monthly_activities.team.created'));
    }

    public function update(Request $request, MonthlyActivityTeam $monthlyActivityTeam)
    {
        $data = $request->validate([
            'team_name' => ['required', 'string', 'max:255'],
            'member_name' => ['required', 'string', 'max:255'],
            'member_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('monthly_activity_team', 'member_email')
                    ->where(fn ($q) => $q->where('monthly_activity_id', $monthlyActivityTeam->monthly_activity_id))
                    ->ignore($monthlyActivityTeam->id),
            ],
            'role_desc' => ['required', 'string', 'max:255'],
        ]);

        $monthlyActivityTeam->update($data);

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
