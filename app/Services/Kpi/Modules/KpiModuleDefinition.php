<?php

namespace App\Services\Kpi\Modules;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for how a module's records map onto the shared KPI lifecycle:
 * live record → planned → approved → executed → closed.
 *
 * Subclasses only describe raw module conditions. The cumulative ("reached at least") stage
 * expressions are composed here so every module is nested the same way:
 * closed ⊆ executed ⊆ approved ⊆ planned ⊆ live records.
 */
abstract class KpiModuleDefinition
{
    public const STAGES = ['planned', 'approved', 'executed', 'closed'];

    /** Query alias used for the module table in every SQL fragment. */
    protected const ALIAS = 's';

    abstract public function key(): string;

    abstract protected function table(): string;

    /** Unqualified column used to place a record inside a KPI period. */
    abstract protected function dateColumn(): string;

    /** Live/current-record filter (soft deletes, archives, superseded versions). */
    abstract protected function applyLive(Builder $query): void;

    /**
     * Raw module conditions per stage (SQL using the "s" alias), or null when the module
     * does not own that stage.
     *
     * @return array{planned: string, approved: string, executed: ?string, closed: ?string}
     */
    abstract protected function rawStageConditions(): array;

    /** Arabic reason shown for stages the module does not own. */
    public function unavailableReason(string $stage): ?string
    {
        return null;
    }

    /** Arabic coverage notes shown under the module's figures. */
    public function notes(): array
    {
        return [];
    }

    public function supportsStage(string $stage): bool
    {
        return $this->rawStageConditions()[$stage] !== null;
    }

    public function dateExpression(): string
    {
        return self::ALIAS.'.'.$this->dateColumn();
    }

    /** Live records only; no period, no branch scope. */
    public function liveQuery(): Builder
    {
        $query = DB::table($this->table().' as '.self::ALIAS);
        $this->applyLive($query);

        return $query;
    }

    /**
     * Restrict to the given branches. Null means "all branches" (caller verified oversight).
     */
    public function applyBranchScope(Builder $query, ?array $branchIds): void
    {
        if ($branchIds === null) {
            return;
        }

        if ($branchIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn(self::ALIAS.'.branch_id', $branchIds);
    }

    /**
     * Prepare a query for grouping by branch and return the branch key expression.
     */
    public function joinBranchKey(Builder $query, ?array $branchIds): string
    {
        $this->applyBranchScope($query, $branchIds);

        return self::ALIAS.'.branch_id';
    }

    /**
     * Cumulative stage expressions: a record that reached a later stage also counts for
     * every earlier stage. Unsupported stages are omitted.
     *
     * @return array<string, string>
     */
    public function cumulativeStageSql(): array
    {
        $raw = $this->rawStageConditions();
        $planned = '('.$raw['planned'].')';
        $later = static fn (array $stages): string => implode(' OR ', array_map(
            static fn (string $condition): string => '('.$condition.')',
            array_values(array_filter(array_intersect_key($raw, array_flip($stages))))
        ));

        $sql = ['planned' => $planned];
        $sql['approved'] = $planned.' AND ('.$later(['approved', 'executed', 'closed']).')';
        if ($raw['executed'] !== null || $raw['closed'] !== null) {
            $sql['executed'] = $planned.' AND ('.$later(['executed', 'closed']).')';
        }
        if ($raw['closed'] !== null) {
            $sql['closed'] = $planned.' AND ('.$raw['closed'].')';
        }

        return $sql;
    }

    protected static function quoteList(array $values): string
    {
        return implode(', ', array_map(static fn (string $value): string => "'".str_replace("'", "''", $value)."'", $values));
    }
}
