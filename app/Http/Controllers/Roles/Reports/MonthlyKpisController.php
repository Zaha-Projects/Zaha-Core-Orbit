<?php

namespace App\Http\Controllers\Roles\Reports;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Center;
use App\Models\MonthlyActivity;
use App\Models\MonthlyKpi;
use App\Models\Setting;
use Illuminate\Http\Request;

class MonthlyKpisController extends Controller
{
    public function index(Request $request)
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();

        $year = (int) ($request->input('year') ?: now()->year);
        $month = (int) ($request->input('month') ?: now()->month);
        $branchId = $request->input('branch_id');
        $centerId = $request->input('center_id');

        $kpis = MonthlyKpi::with(['branch', 'center', 'creator'])
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        $computedKpi = $this->computeKpi($year, $month, $branchId, $centerId);

        return view('roles.reports.kpis', compact(
            'kpis',
            'branches',
            'centers',
            'computedKpi',
            'year',
            'month',
            'branchId',
            'centerId'
        ));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasRole('followup_officer'), 403);

        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'center_id' => ['nullable', 'exists:centers,id'],
            'planned_activities_count' => ['required', 'integer', 'min:0'],
            'unplanned_activities_count' => ['required', 'integer', 'min:0'],
            'modification_rate_percent' => ['nullable', 'integer', 'between:0,100'],
            'plan_commitment_percent' => ['nullable', 'integer', 'between:0,100'],
            'mobilization_efficiency_percent' => ['nullable', 'integer', 'between:0,100'],
            'branch_monthly_score' => ['nullable', 'integer', 'between:0,100'],
            'followup_commitment_score' => ['nullable', 'integer', 'between:0,100'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! isset($data['branch_monthly_score']) || $data['branch_monthly_score'] === null) {
            $score = $this->calculateBranchMonthlyScore(
                $data['plan_commitment_percent'] ?? null,
                $data['mobilization_efficiency_percent'] ?? null
            );

            if ($score !== null) {
                $data['branch_monthly_score'] = $score;
            }
        }

        MonthlyKpi::updateOrCreate(
            [
                'year' => $data['year'],
                'month' => $data['month'],
                'branch_id' => $data['branch_id'] ?? null,
                'center_id' => $data['center_id'] ?? null,
            ],
            array_merge($data, ['created_by' => $request->user()->id])
        );

        return redirect()->route('role.reports.kpis.index', [
            'year' => $data['year'],
            'month' => $data['month'],
            'branch_id' => $data['branch_id'] ?? null,
            'center_id' => $data['center_id'] ?? null,
        ])->with('status', 'تم حفظ مؤشرات الأداء الشهرية.');
    }

    private function computeKpi(int $year, int $month, $branchId = null, $centerId = null): array
    {
        $query = MonthlyActivity::query()->where('month', $month);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        if ($centerId) {
            $query->where('center_id', $centerId);
        }

        $activities = $query->get();

        $total = $activities->count();
        $planned = $activities->where('is_in_agenda', true)->count();
        $unplanned = $total - $planned;
        $modified = $activities->filter(fn ($activity) => !empty($activity->rescheduled_date) || !empty($activity->modified_proposed_date))->count();
        $completed = $activities->whereIn('status', ['completed', 'closed', 'done'])->count();

        $modificationRate = $total > 0 ? (int) round(($modified / $total) * 100) : null;
        $commitment = $total > 0 ? (int) round(($completed / $total) * 100) : null;
        $mobilization = $activities->whereNotNull('audience_satisfaction_percent')->count() > 0
            ? (int) round($activities->whereNotNull('audience_satisfaction_percent')->avg('audience_satisfaction_percent'))
            : null;

        return [
            'year' => $year,
            'month' => $month,
            'planned_activities_count' => $planned,
            'unplanned_activities_count' => $unplanned,
            'modification_rate_percent' => $modificationRate,
            'plan_commitment_percent' => $commitment,
            'mobilization_efficiency_percent' => $mobilization,
            'branch_monthly_score' => $this->calculateBranchMonthlyScore($commitment, $mobilization),
        ];
    }

    private function calculateBranchMonthlyScore(?int $planCommitment, ?int $satisfaction): ?int
    {
        if ($planCommitment === null || $satisfaction === null) {
            return null;
        }

        $satisfactionWeight = (int) Setting::valueOf('branch_monthly_score_weight_satisfaction', '40');
        $commitmentWeight = (int) Setting::valueOf('branch_monthly_score_weight_commitment', '60');

        $totalWeight = $satisfactionWeight + $commitmentWeight;
        if ($totalWeight <= 0) {
            return null;
        }

        return (int) round((($satisfaction * $satisfactionWeight) + ($planCommitment * $commitmentWeight)) / $totalWeight);
    }
}
