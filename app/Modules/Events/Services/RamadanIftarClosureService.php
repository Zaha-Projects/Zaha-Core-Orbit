<?php

namespace App\Modules\Events\Services;

use App\Models\User;
use App\Models\WorkflowActionLog;
use App\Modules\Events\Models\RamadanIftar;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

class RamadanIftarClosureService
{
    public function close(RamadanIftar $iftar, User $actor): RamadanIftar
    {
        return DB::transaction(function () use ($iftar, $actor) {
            $locked = RamadanIftar::query()->lockForUpdate()->findOrFail($iftar->getKey());
            if (! $actor->hasRole('super_admin') && (! $actor->hasRole('supervisor') || ! $actor->can('ramadan_iftars.close') || ! $actor->hasAccessToScopedBranch((int) $locked->branch_id))) {
                throw new AuthorizationException(__('ramadan_iftars.closure.errors.unauthorized'));
            }
            $readiness = $locked->closureReadiness();

            foreach ($readiness as $rule => $ready) {
                if (! $ready) {
                    throw ValidationException::withMessages([
                        'closure' => __('ramadan_iftars.closure.errors.'.$rule),
                    ]);
                }
            }

            $report = $locked->approvedMonitoringReportForClosure();
            $closedAt = now();
            $locked->forceFill(['closed_at' => $closedAt])->save();

            WorkflowActionLog::query()->create([
                'module' => RamadanIftar::WORKFLOW_MODULE,
                'entity_type' => RamadanIftar::class,
                'entity_id' => $locked->id,
                'action_type' => 'iftar_closed',
                'status' => 'closed',
                'performed_by' => $actor->id,
                'meta' => ['monitoring_report_id' => $report->id],
                'performed_at' => $closedAt,
            ]);

            return $locked->fresh();
        });
    }
}
