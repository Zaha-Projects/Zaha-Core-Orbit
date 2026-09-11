<?php

namespace App\Modules\Events\Services;

use App\Models\User;
use App\Models\WorkflowActionLog;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\MonitoringMethod;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\RamadanIftar;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RamadanIftarMonitoringService
{
    public function save(RamadanIftar $iftar, MonitoringReport $report, array $data, User $actor): MonitoringReport
    {
        return DB::transaction(function () use ($iftar, $report, $data, $actor) {
            $locked = RamadanIftar::query()->lockForUpdate()->findOrFail($iftar->id);
            $this->assertMonitorable($locked);
            if ($report->exists) {
                $report = $locked->monitoringReports()->whereKey($report->id)->lockForUpdate()->first();
                if (! $report) $this->invalid('report', 'The monitoring report does not belong to this Ramadan Iftar.');
                if (! in_array($report->status, [MonitoringReport::STATUS_DRAFT, MonitoringReport::STATUS_RETURNED], true)) {
                    $this->invalid('status', 'Submitted monitoring reports are read-only.');
                }
            } else {
                $report = new MonitoringReport([
                    'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
                    'subject_id' => $locked->id,
                    'status' => MonitoringReport::STATUS_DRAFT,
                ]);
            }
            if (! MonitoringMethod::query()->active()->whereKey($data['monitoring_method_id'])->exists()) {
                $this->invalid('monitoring_method_id', 'The monitoring method is inactive.');
            }
            $report->fill(Arr::only($data, ['monitoring_method_id', 'observed_at', 'general_notes']));
            $report->monitor_user_id = $actor->id;
            $report->save();
            $this->syncVerifications($locked, $report, $data['verifications'], $actor);
            $this->audit($locked, $actor, 'monitoring_report_saved', $report);

            return $report->fresh('verifications');
        });
    }

    public function submit(RamadanIftar $iftar, MonitoringReport $report, User $actor): MonitoringReport
    {
        return DB::transaction(function () use ($iftar, $report, $actor) {
            $locked = RamadanIftar::query()->lockForUpdate()->findOrFail($iftar->id);
            $this->assertMonitorable($locked);
            $report = $locked->monitoringReports()->whereKey($report->id)->lockForUpdate()->first();
            if (! $report || ! in_array($report->status, [MonitoringReport::STATUS_DRAFT, MonitoringReport::STATUS_RETURNED], true)) {
                $this->invalid('report', 'This monitoring report cannot be submitted.');
            }
            if (! $report->verifications()->exists()) {
                $this->invalid('verifications', 'At least one field verification is required.');
            }
            $report->update(['status' => MonitoringReport::STATUS_SUBMITTED, 'submitted_at' => now(), 'monitor_user_id' => $actor->id]);
            $this->audit($locked, $actor, 'monitoring_report_submitted', $report);

            return $report->fresh();
        });
    }

    public function candidates(RamadanIftar $iftar): array
    {
        $rows = [
            $this->candidate(null, null, 'attendance', 'Attendance', $iftar->expected_attendance, $iftar->actual_attendance),
            $this->candidate(null, null, 'meals', 'Meals', $iftar->planned_meals_count, $iftar->actual_meals_count),
            $this->candidate(null, null, 'date', 'Event date', optional($iftar->planned_date)->format('Y-m-d'), optional($iftar->actual_date)->format('Y-m-d')),
        ];
        foreach ($iftar->meals as $row) $rows[] = $this->candidate('meal', $row->id, 'quantity', 'Meal: '.$row->description, $row->planned_quantity, $row->actual_quantity);
        foreach ($iftar->gifts as $row) $rows[] = $this->candidate('gift', $row->id, 'quantity', 'Gift: '.$row->description, $row->planned_quantity, $row->actual_quantity);
        foreach ($iftar->programSegments as $row) $rows[] = $this->candidate('program_segment', $row->id, 'execution_status', 'Program: '.$row->name, 'planned', $row->execution_status);
        foreach ($iftar->executionTeams as $row) $rows[] = $this->candidate('execution_team', $row->id, 'members_count', 'Team: '.$row->name, $row->planned_members_count, $row->actual_members_count);
        foreach ($iftar->volunteerRequirements as $row) $rows[] = $this->candidate('volunteer_requirement', $row->id, 'count', 'Volunteer requirement #'.$row->id, $row->planned_count, $row->actual_count);
        foreach ($iftar->supplies as $row) $rows[] = $this->candidate('supply', $row->id, 'quantity', 'Supply: '.$row->item_name, $row->planned_quantity, $row->actual_quantity);
        foreach ($iftar->executionNeeds as $row) $rows[] = $this->candidate('execution_need', $row->id, 'result', 'Execution need: '.optional($row->executionNeedType)->name, $row->planned_details, $row->actual_details);

        return $rows;
    }

    private function syncVerifications(RamadanIftar $iftar, MonitoringReport $report, array $rows, User $actor): void
    {
        $candidates = collect($this->candidates($iftar))->keyBy(fn ($row) => $this->candidateKey($row));
        $existing = $report->verifications()->get()->keyBy('id');
        $kept = [];
        foreach ($rows as $row) {
            $candidate = $candidates->get($this->candidateKey($row));
            if (! $candidate) $this->invalid('verifications', 'A verification target is not part of this Ramadan Iftar.');
            $verification = isset($row['id']) ? $existing->get((int) $row['id']) : null;
            if (isset($row['id']) && ! $verification) $this->invalid('verifications', 'A verification row does not belong to this report.');
            $verification = $verification ?: $report->verifications()->make();
            $verification->fill([
                'detail_type' => $candidate['detail_type'], 'detail_id' => $candidate['detail_id'],
                'field_key' => $candidate['field_key'], 'field_label' => $candidate['field_label'],
                'planned_value' => ['value' => $candidate['planned']], 'actual_value' => ['value' => $candidate['actual']],
                'match_status' => $row['match_status'], 'note' => $row['note'] ?? null,
                'verified_by' => $actor->id, 'verified_at' => now(),
            ]);
            $verification->save();
            $kept[] = $verification->id;
        }
        $report->verifications()->whereNotIn('id', $kept)->delete();
    }

    private function candidate($type, $id, string $key, string $label, $planned, $actual): array
    {
        return ['detail_type' => $type, 'detail_id' => $id, 'field_key' => $key, 'field_label' => $label, 'planned' => $planned, 'actual' => $actual];
    }

    private function candidateKey(array $row): string
    {
        return ($row['detail_type'] ?? 'aggregate').':'.($row['detail_id'] ?? '0').':'.$row['field_key'];
    }

    private function assertMonitorable(RamadanIftar $iftar): void
    {
        if ($iftar->status !== RamadanIftar::STATUS_APPROVED || $iftar->execution_status !== RamadanIftar::EXECUTION_STATUS_IN_PROGRESS) {
            $this->invalid('status', 'Monitoring requires an approved Ramadan Iftar with execution in progress.');
        }
    }

    private function audit(RamadanIftar $iftar, User $actor, string $action, MonitoringReport $report): void
    {
        WorkflowActionLog::query()->create([
            'module' => RamadanIftar::WORKFLOW_MODULE, 'entity_type' => RamadanIftar::class, 'entity_id' => $iftar->id,
            'action_type' => $action, 'status' => $report->status, 'performed_by' => $actor->id,
            'meta' => ['monitoring_report_id' => $report->id], 'performed_at' => now(),
        ]);
    }

    private function invalid(string $key, string $message): void
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
