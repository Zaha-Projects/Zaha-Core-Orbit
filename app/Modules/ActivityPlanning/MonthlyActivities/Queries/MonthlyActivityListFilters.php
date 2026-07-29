<?php

namespace App\Modules\ActivityPlanning\MonthlyActivities\Queries;

use Illuminate\Http\Request;

final class MonthlyActivityListFilters
{
    public string $viewScope;
    public string $status;
    public ?int $branchId;
    public string $summaryFilter;
    public int $year;
    public int $month;
    public bool $showDeleted;
    public int $perPage;

    public static function fromRequest(Request $request): self
    {
        $filters = new self();
        $filters->viewScope = (string) $request->input('scope', 'default');
        $filters->status = trim((string) $request->input('status', ''));
        $filters->branchId = filter_var($request->input('branch_id'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;
        $filters->summaryFilter = trim((string) $request->input('summary_filter', ''));
        $filters->year = self::normalizeYear($request->input('year'));
        $filters->month = self::normalizeMonth($request->input('month'));
        $filters->showDeleted = $request->boolean('deleted');

        $allowedPerPage = [8, 16, 24, 50, 100];
        $requestedPerPage = (int) $request->input('per_page', 8);
        $filters->perPage = in_array($requestedPerPage, $allowedPerPage, true) ? $requestedPerPage : 8;

        return $filters;
    }

    public static function normalizeYear($year): int
    {
        $normalizedYear = filter_var($year, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 2000, 'max_range' => 2100],
        ]);

        return $normalizedYear ?: now()->year;
    }

    public static function normalizeMonth($month): int
    {
        $normalizedMonth = filter_var($month, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 12],
        ]);

        return $normalizedMonth ?: now()->month;
    }
}
