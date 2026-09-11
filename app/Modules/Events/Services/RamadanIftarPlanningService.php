<?php

namespace App\Modules\Events\Services;

use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\ExecutionTeam;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarMeal;
use App\Modules\Events\Models\RamadanIftarProgramSegment;
use App\Modules\Events\Models\SubjectSupply;
use App\Modules\Events\Models\SubjectVolunteerRequirement;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RamadanIftarPlanningService
{
    private const CORE_FIELDS = [
        'agenda_event_id', 'branch_id', 'title', 'description', 'relations_officer_id',
        'planned_date', 'time_from', 'time_to', 'location_type', 'location_name',
        'address', 'google_maps_url', 'contact_name', 'contact_phone',
        'supporting_entity_name', 'host_type', 'community_organization_id',
        'local_community_id', 'mobilization_method_id', 'mobilization_method_other',
    ];

    public function create(array $data, User $creator): RamadanIftar
    {
        return DB::transaction(function () use ($data, $creator) {
            $iftar = RamadanIftar::query()->create(array_merge(
                Arr::only($data, self::CORE_FIELDS),
                $this->derivedTotals($data),
                [
                    'created_by' => $creator->getKey(),
                    'status' => RamadanIftar::STATUS_DRAFT,
                    'execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED,
                    'version_number' => 1,
                ],
            ));

            $this->syncPlanning($iftar, $data);
            $this->refreshPlanningTotals($iftar);

            return $iftar->fresh();
        });
    }

    public function update(RamadanIftar $iftar, array $data): RamadanIftar
    {
        if ($iftar->status !== RamadanIftar::STATUS_DRAFT) {
            throw ValidationException::withMessages(['status' => 'Only draft Ramadan Iftars may be edited.']);
        }

        return DB::transaction(function () use ($iftar, $data) {
            $this->assertOwnership($iftar, $data);
            $iftar->update(Arr::only($data, self::CORE_FIELDS));
            $this->syncPlanning($iftar, $data);
            $this->refreshPlanningTotals($iftar);

            return $iftar->fresh();
        });
    }

    private function derivedTotals(array $data): array
    {
        return [
            'expected_attendance' => collect($data['target_groups'])->sum('planned_count'),
            'planned_meals_count' => collect($data['meals'])->sum('planned_quantity'),
        ];
    }

    private function refreshPlanningTotals(RamadanIftar $iftar): void
    {
        $iftar->update([
            'expected_attendance' => $iftar->targetGroupSelections()->sum('planned_count'),
            'planned_meals_count' => $iftar->meals()->sum('planned_quantity'),
        ]);
    }

    private function syncPlanning(RamadanIftar $iftar, array $data): void
    {
        $this->syncTargetGroups($iftar, $data['target_groups']);
        $this->syncMeals($iftar, $data['meals']);
        $this->syncSimple($iftar->gifts(), $data['gifts'], [
            'description', 'planned_quantity', 'has_supporting_entity', 'supporting_entity_name', 'unit_value',
        ], fn (array $row) => ['estimated_total_value' => $this->giftTotal($row)], ['actual_quantity'], null, true);
        $this->syncSimple($iftar->programSegments(), $data['program_segments'], [
            'name', 'starts_at', 'ends_at', 'duration_minutes', 'sort_order', 'executor_user_id', 'external_executor_name',
        ], fn () => ['execution_status' => RamadanIftarProgramSegment::STATUS_PLANNED], ['actual_notes'], function ($model) {
            return $model->execution_status !== RamadanIftarProgramSegment::STATUS_PLANNED;
        });
        $this->syncTeams($iftar, $data['execution_teams']);
        $this->syncSimple($iftar->volunteerRequirements(), $data['volunteer_requirements'], [
            'beneficiary_segment_id', 'gender', 'planned_count', 'tasks_summary',
        ], fn () => ['status' => SubjectVolunteerRequirement::STATUS_PENDING], ['actual_count']);
        $this->syncSimple($iftar->supplies(), $data['supplies'], [
            'item_name', 'planned_quantity', 'provider_type', 'provider_name', 'estimated_value', 'notes',
        ], fn () => ['status' => SubjectSupply::STATUS_PENDING], ['actual_quantity', 'is_available']);
    }

    private function syncTargetGroups(RamadanIftar $iftar, array $rows): void
    {
        $this->syncSimple($iftar->targetGroupSelections(), $rows, [
            'target_group_id', 'target_group_custom_text', 'beneficiary_segment_id',
            'segment_custom_text', 'planned_count', 'notes',
        ], fn () => [
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->getKey(),
        ], ['actual_count']);
    }

    private function syncMeals(RamadanIftar $iftar, array $rows): void
    {
        $existing = $iftar->meals()->get()->keyBy('id');
        $kept = [];
        foreach ($rows as $row) {
            $meal = isset($row['id']) ? $existing->get((int) $row['id']) : new RamadanIftarMeal(['ramadan_iftar_id' => $iftar->getKey()]);
            if (! $meal) $this->invalidOwnedId('meals');
            $meal->fill(Arr::only($row, ['description', 'planned_quantity', 'source_type', 'source_name', 'restaurant_name', 'restaurant_contact', 'estimated_value']))->save();
            $kept[] = $meal->getKey();
            $this->syncSimple($meal->items(), $row['items'] ?? [], ['name', 'item_type', 'quantity', 'notes', 'sort_order']);
        }
        $iftar->meals()->whereNotIn('id', $kept)->get()->each(function (RamadanIftarMeal $meal) {
            if ($meal->actual_quantity === null && $meal->rating === null) $meal->delete();
        });
    }

    private function syncTeams(RamadanIftar $iftar, array $rows): void
    {
        $existing = $iftar->executionTeams()->get()->keyBy('id');
        $kept = [];
        foreach ($rows as $row) {
            $team = isset($row['id']) ? $existing->get((int) $row['id']) : new ExecutionTeam([
                'subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->getKey(),
            ]);
            if (! $team) $this->invalidOwnedId('execution_teams');
            $team->fill(Arr::only($row, ['name', 'leader_user_id', 'planned_members_count', 'notes']))->save();
            $kept[] = $team->getKey();
            $this->syncSimple($team->members(), $row['members'] ?? [], ['user_id', 'member_name', 'phone', 'role_name', 'task_description'], null, ['task_completed', 'actual_task_note', 'confirmed_at']);
        }
        $iftar->executionTeams()->whereNotIn('id', $kept)->get()->each(function (ExecutionTeam $team) {
            if ($team->actual_members_count === null && ! $team->members()->whereNotNull('task_completed')->exists()) $team->delete();
        });
    }

    private function syncSimple($relation, array $rows, array $fields, ?callable $defaults = null, array $actualFields = [], ?callable $hasActual = null, bool $alwaysApplyDefaults = false): void
    {
        $existing = $relation->get()->keyBy('id');
        $kept = [];
        foreach ($rows as $row) {
            $model = isset($row['id']) ? $existing->get((int) $row['id']) : $relation->make();
            if (! $model) $this->invalidOwnedId($relation->getRelated()->getTable());
            $attributes = Arr::only($row, $fields);
            if ($defaults && ($alwaysApplyDefaults || ! $model->exists)) $attributes = array_merge($attributes, $defaults($row));
            $model->fill($attributes)->save();
            $kept[] = $model->getKey();
        }
        $relation->whereNotIn('id', $kept)->get()->each(function (Model $model) use ($actualFields, $hasActual) {
            $containsActual = $hasActual ? $hasActual($model) : collect($actualFields)->contains(fn ($field) => $model->getAttribute($field) !== null);
            if (! $containsActual) $model->delete();
        });
    }

    private function assertOwnership(RamadanIftar $iftar, array $data): void
    {
        $checks = [
            'target_groups' => $iftar->targetGroupSelections(), 'meals' => $iftar->meals(),
            'gifts' => $iftar->gifts(), 'program_segments' => $iftar->programSegments(),
            'execution_teams' => $iftar->executionTeams(), 'volunteer_requirements' => $iftar->volunteerRequirements(),
            'supplies' => $iftar->supplies(),
        ];
        foreach ($checks as $key => $query) $this->assertIdsBelong($key, $data[$key], $query);
        foreach ($data['meals'] as $i => $meal) if (! empty($meal['id'])) {
            $ownedMeal = $iftar->meals()->find($meal['id']);
            if ($ownedMeal) $this->assertIdsBelong("meals.$i.items", $meal['items'] ?? [], $ownedMeal->items());
        }
        foreach ($data['execution_teams'] as $i => $team) if (! empty($team['id'])) {
            $ownedTeam = $iftar->executionTeams()->find($team['id']);
            if ($ownedTeam) $this->assertIdsBelong("execution_teams.$i.members", $team['members'] ?? [], $ownedTeam->members());
        }
    }

    private function assertIdsBelong(string $key, array $rows, $query): void
    {
        $ids = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->unique();
        if ($ids->isNotEmpty() && $query->whereKey($ids->all())->count() !== $ids->count()) $this->invalidOwnedId($key);
    }

    private function invalidOwnedId(string $key): never
    {
        throw ValidationException::withMessages([$key => 'One or more records do not belong to this Ramadan Iftar.']);
    }

    private function giftTotal(array $gift): ?string
    {
        if (! isset($gift['unit_value']) || $gift['unit_value'] === '') return null;
        [$whole, $fraction] = array_pad(explode('.', (string) $gift['unit_value'], 2), 2, '');
        $cents = ((int) $whole * 100) + (int) str_pad(substr($fraction, 0, 2), 2, '0');
        $total = $cents * (int) $gift['planned_quantity'];
        return sprintf('%d.%02d', intdiv($total, 100), $total % 100);
    }
}
