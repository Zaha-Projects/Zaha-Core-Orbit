<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\MonthlyKpi;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonthlyKpiSeeder extends Seeder
{
    public function run(): void
    {
        $followup = User::role('followup_officer')->first();

        foreach (Branch::orderBy('id')->get() as $branch) {
MonthlyKpi::updateOrCreate(
    [
        'year' => (int) date('Y'),
        'month' => (int) date('n'),
        'branch_id' => $branch->id,
    ],
    [
        'planned_activities_count' => 10,
        'unplanned_activities_count' => 2,
        'modification_rate_percent' => 12,
        'plan_commitment_percent' => 88,
        'mobilization_efficiency_percent' => 81,
        'branch_monthly_score' => 84,
        'followup_commitment_score' => 80,
        'notes' => 'بيانات تأسيسية أولية للوحة مؤشرات الأداء.',
        'created_by' => $followup?->id,
    ]
);
        }
    }
}
