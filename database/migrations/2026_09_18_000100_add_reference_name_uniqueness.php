<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $globalNames = [
            'mobilization_methods' => 'name_ar',
            'target_groups' => 'name',
            'beneficiary_segments' => 'name_ar',
            'ramadan_iftar_gift_types' => 'name_ar',
            'execution_need_types' => 'name',
            'monitoring_methods' => 'name_ar',
        ];
        foreach ($globalNames as $table => $column) {
            $duplicate = DB::table($table)->select($column)
                ->groupBy($column)->havingRaw('COUNT(*) > 1')->first();
            if ($duplicate) {
                throw new RuntimeException("Resolve duplicate {$table}.{$column} values before adding the unique constraint.");
            }
        }
        foreach (['community_organizations', 'local_communities'] as $table) {
            $duplicate = DB::table($table)->select('branch_id', 'name')
                ->groupBy('branch_id', 'name')->havingRaw('COUNT(*) > 1')->first();
            if ($duplicate) {
                throw new RuntimeException("Resolve duplicate {$table} branch/name values before adding the unique constraint.");
            }
        }
        Schema::table('community_organizations', fn (Blueprint $table) => $table->unique(['branch_id', 'name'], 'community_org_branch_name_uq'));
        Schema::table('local_communities', fn (Blueprint $table) => $table->unique(['branch_id', 'name'], 'local_community_branch_name_uq'));
        foreach ($globalNames as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($column, "{$table}_{$column}_uq"));
        }
    }

    public function down(): void
    {
        foreach ([
            'mobilization_methods' => 'name_ar',
            'target_groups' => 'name',
            'beneficiary_segments' => 'name_ar',
            'ramadan_iftar_gift_types' => 'name_ar',
            'execution_need_types' => 'name',
            'monitoring_methods' => 'name_ar',
        ] as $table => $column) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique("{$table}_{$column}_uq"));
        }
        Schema::table('community_organizations', fn (Blueprint $table) => $table->dropUnique('community_org_branch_name_uq'));
        Schema::table('local_communities', fn (Blueprint $table) => $table->dropUnique('local_community_branch_name_uq'));
    }
};
