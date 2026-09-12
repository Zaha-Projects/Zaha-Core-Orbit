<?php

namespace App\Modules\Events\Services;

use App\Models\User;
use App\Models\WorkflowActionLog;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarAttendee;
use App\Modules\Events\Models\SubjectExecutionNeed;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RamadanIftarExecutionService
{
    public function start(RamadanIftar $iftar, User $actor): RamadanIftar
    {
        return DB::transaction(function () use ($iftar, $actor) {
            $locked = RamadanIftar::query()->lockForUpdate()->findOrFail($iftar->getKey());
            $this->assertApproved($locked);
            if ($locked->execution_status !== RamadanIftar::EXECUTION_STATUS_PLANNED) {
                throw ValidationException::withMessages(['execution_status' => 'Execution has already started.']);
            }
            $locked->update(['execution_status' => RamadanIftar::EXECUTION_STATUS_IN_PROGRESS]);
            $this->audit($locked, $actor, 'execution_started');

            return $locked->fresh();
        });
    }

    public function update(RamadanIftar $iftar, array $data, User $actor): RamadanIftar
    {
        return DB::transaction(function () use ($iftar, $data, $actor) {
            $locked = RamadanIftar::query()->lockForUpdate()->findOrFail($iftar->getKey());
            $this->assertApproved($locked);
            if ($locked->execution_status !== RamadanIftar::EXECUTION_STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages(['execution_status' => 'Start execution before recording actual results.']);
            }

            $locked->update(Arr::only($data, ['actual_date']));
            $this->syncAttendees($locked, $data['attendees']);
            $this->updateOwned($locked->meals(), $data['meals'], ['actual_quantity', 'rating', 'rating_notes'], 'meals');
            $this->updateOwned($locked->gifts(), $data['gifts'], ['actual_quantity'], 'gifts');
            $this->updateOwned($locked->programSegments(), $data['program_segments'], ['execution_status', 'actual_notes'], 'program_segments');
            $this->syncTeams($locked, $data['execution_teams'], $actor);
            $this->updateOwned($locked->volunteerRequirements(), $data['volunteer_requirements'], ['actual_count'], 'volunteer_requirements');
            $this->updateOwned($locked->supplies(), $data['supplies'], ['actual_quantity', 'is_available'], 'supplies');
            $this->syncExecutionNeeds($locked, $data['execution_needs']);

            $locked->update([
                'actual_attendance' => $locked->attendees()->where('attended', true)->count(),
                'actual_meals_count' => $locked->meals()->whereNotNull('actual_quantity')->exists()
                    ? (int) $locked->meals()->sum('actual_quantity') : null,
            ]);
            $this->audit($locked, $actor, 'execution_updated');

            return $locked->fresh();
        });
    }

    public function complete(RamadanIftar $iftar, User $actor): RamadanIftar
    {
        return DB::transaction(function () use ($iftar, $actor) {
            $locked = RamadanIftar::query()->lockForUpdate()->findOrFail($iftar->getKey());
            $this->assertApproved($locked);
            if ($locked->execution_status !== RamadanIftar::EXECUTION_STATUS_IN_PROGRESS) {
                throw ValidationException::withMessages(['execution_status' => 'Only execution in progress may be completed.']);
            }
            $this->assertCompletionReady($locked);
            $locked->update(['execution_status' => RamadanIftar::EXECUTION_STATUS_COMPLETED]);
            $this->audit($locked, $actor, 'execution_completed');

            return $locked->fresh();
        });
    }

    private function syncAttendees(RamadanIftar $iftar, array $rows): void
    {
        $existing = $iftar->attendees()->get()->keyBy('id');
        foreach ($rows as $row) {
            if (! isset($row['id']) && blank($row['full_name'] ?? null)) continue;
            $attendee = isset($row['id']) ? $existing->get((int) $row['id']) : null;
            if (isset($row['id']) && ! $attendee) $this->invalidOwnedId('attendees');
            if (! empty($row['_delete'])) {
                if ($attendee) $attendee->delete();
                continue;
            }
            if (blank($row['full_name'] ?? null)) {
                throw ValidationException::withMessages(['attendees' => 'An attendee name is required.']);
            }
            $attendee = $attendee ?: new RamadanIftarAttendee(['ramadan_iftar_id' => $iftar->id]);
            $wasAttended = (bool) $attendee->attended;
            $attendee->fill(Arr::only($row, ['full_name', 'phone', 'age', 'target_group_id', 'beneficiary_segment_id', 'attended', 'notes']));
            $attended = (bool) $row['attended'];
            $attendee->checked_in_at = $attended ? ($wasAttended ? $attendee->checked_in_at : now()) : null;
            $attendee->save();
        }
    }

    private function syncTeams(RamadanIftar $iftar, array $rows, User $actor): void
    {
        $teams = $iftar->executionTeams()->with('members')->get()->keyBy('id');
        foreach ($rows as $row) {
            $team = $teams->get((int) $row['id']);
            if (! $team) $this->invalidOwnedId('execution_teams');
            $team->update(Arr::only($row, ['actual_members_count']));
            $members = $team->members->keyBy('id');
            foreach ($row['members'] as $memberRow) {
                $member = $members->get((int) $memberRow['id']);
                if (! $member) $this->invalidOwnedId('execution_team_members');
                $evaluation = array_key_exists('task_completed', $memberRow) ? $memberRow['task_completed'] : null;
                $member->update([
                    'task_completed' => $evaluation,
                    'actual_task_note' => $memberRow['actual_task_note'] ?? null,
                    'confirmed_by' => $evaluation === null ? null : $actor->id,
                    'confirmed_at' => $evaluation === null ? null : now(),
                ]);
            }
        }
    }

    private function syncExecutionNeeds(RamadanIftar $iftar, array $rows): void
    {
        $needs = $iftar->executionNeeds()->get()->keyBy('id');
        foreach ($rows as $row) {
            $need = $needs->get((int) $row['id']);
            if (! $need) $this->invalidOwnedId('execution_needs');
            $completed = $row['status'] === SubjectExecutionNeed::STATUS_COMPLETED;
            $need->update([
                'status' => $row['status'],
                'actual_details' => $row['actual_details'] ?? null,
                'completed_at' => $completed ? ($need->completed_at ?: now()) : null,
            ]);
        }
    }

    private function updateOwned(HasMany $relation, array $rows, array $fields, string $key): void
    {
        $existing = $relation->get()->keyBy('id');
        foreach ($rows as $row) {
            $model = $existing->get((int) $row['id']);
            if (! $model) $this->invalidOwnedId($key);
            $model->update(Arr::only($row, $fields));
        }
    }

    private function assertApproved(RamadanIftar $iftar): void
    {
        if ($iftar->status !== RamadanIftar::STATUS_APPROVED || ! $iftar->canAccessExecution()) {
            throw ValidationException::withMessages(['status' => 'Only approved Ramadan Iftars may enter execution.']);
        }
    }

    private function assertCompletionReady(RamadanIftar $iftar): void
    {
        $errors = [];
        if ($iftar->actual_date === null) $errors['actual_date'] = 'Actual date is required before execution completion.';
        if ($iftar->actual_attendance === null) $errors['actual_attendance'] = 'Attendance must be captured; zero attendance is a valid captured result.';
        if ($iftar->actual_attendance !== null && (int) $iftar->actual_attendance !== $iftar->attendees()->where('attended', true)->count()) $errors['actual_attendance'] = 'Actual attendance must match checked-in attendees.';
        if ($iftar->executionNeeds()->where('is_required', true)->where(function ($query) {
            $query->where('status', '!=', SubjectExecutionNeed::STATUS_COMPLETED)
                ->orWhereNull('actual_details')->orWhere('actual_details', '')->orWhereNull('completed_at');
        })->exists()) $errors['execution_needs'] = 'Every required Execution Need must be completed with actual details.';
        if ($errors !== []) throw ValidationException::withMessages($errors);
    }

    private function invalidOwnedId(string $key): void
    {
        throw ValidationException::withMessages([$key => 'An execution row does not belong to this Ramadan Iftar.']);
    }

    private function audit(RamadanIftar $iftar, User $actor, string $action): void
    {
        WorkflowActionLog::query()->create([
            'module' => RamadanIftar::WORKFLOW_MODULE, 'entity_type' => RamadanIftar::class,
            'entity_id' => $iftar->id, 'action_type' => $action, 'status' => $iftar->execution_status,
            'performed_by' => $actor->id, 'performed_at' => now(),
        ]);
    }
}
