<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RamadanReferencePreflight extends Command
{
    protected $signature = 'ramadan:reference-preflight';
    protected $description = 'Read-only duplicate, whitespace, schema and reference checks before Ramadan unique indexes';

    public function handle(): int
    {
        $tables = [
            'community_organizations' => 'name', 'local_communities' => 'name',
            'mobilization_methods' => 'name_ar', 'target_groups' => 'name',
            'beneficiary_segments' => 'name_ar', 'ramadan_iftar_gift_types' => 'name_ar',
            'execution_need_types' => 'name', 'monitoring_methods' => 'name_ar',
        ];
        $report = ['server' => DB::selectOne('SELECT VERSION() AS version, DATABASE() AS db'), 'tables' => []];
        $issues = 0;
        foreach ($tables as $table => $column) {
            $branchScoped = in_array($table, ['community_organizations', 'local_communities'], true);
            $groups = DB::table($table)->selectRaw(($branchScoped ? 'branch_id, ' : '')."TRIM(`{$column}`) AS name, COUNT(*) AS count")
                ->groupByRaw(($branchScoped ? 'branch_id, ' : '')."TRIM(`{$column}`)")->havingRaw('COUNT(*) > 1')->get();
            $foreignKeys = DB::select('SELECT TABLE_NAME AS source_table, COLUMN_NAME AS source_column FROM information_schema.KEY_COLUMN_USAGE WHERE REFERENCED_TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME = ?', [$table]);
            foreach ($groups as $group) {
                $group->ids = DB::table($table)->whereRaw("TRIM(`{$column}`) = ?", [$group->name])
                    ->when($branchScoped, fn ($query) => $query->where('branch_id', $group->branch_id))->orderBy('id')->pluck('id')->all();
                $group->references = [];
                foreach ($group->ids as $id) {
                    foreach ($foreignKeys as $fk) {
                        $group->references[$id][$fk->source_table.'.'.$fk->source_column] = DB::table($fk->source_table)->where($fk->source_column, $id)->count();
                    }
                }
            }
            $whitespace = DB::table($table)->select('id', $column)->whereRaw("BINARY `{$column}` <> BINARY TRIM(`{$column}`) OR `{$column}` REGEXP '[[:space:]]{2,}'")->get();
            $nullBranches = $branchScoped ? DB::table($table)->whereNull('branch_id')->pluck('id')->all() : [];
            $issues += $groups->count() + $whitespace->count() + count($nullBranches);
            $report['tables'][$table] = [
                'rows' => DB::table($table)->count(), 'duplicate_groups' => $groups,
                'whitespace' => $whitespace, 'null_branch_ids' => $nullBranches,
                'columns' => DB::select('SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_TYPE, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME IN (?, ?)', [$table, $column, 'branch_id']),
                'indexes' => DB::select('SELECT INDEX_NAME, MAX(CHAR_LENGTH(INDEX_NAME)) AS name_length FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? GROUP BY INDEX_NAME', [$table]),
            ];
        }
        $report['issues'] = $issues;
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $issues ? 1 : 0;
    }
}
