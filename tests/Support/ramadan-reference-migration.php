<?php

// Run after the feature suite has migrated an isolated MySQL test database.
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = DB::selectOne('SELECT DATABASE() AS db')->db;
if (! app()->environment('testing') || ! in_array($db, ['zaha_review_test_20260917_01', 'zaha_review_baseline_20260917_01'], true)) {
    throw new RuntimeException('Refusing DDL outside the isolated review test databases.');
}
$migration = require database_path('migrations/2026_09_18_000100_add_reference_name_uniqueness.php');
$indexes = ['community_org_branch_name_uq','local_community_branch_name_uq','mobilization_methods_name_ar_uq','target_groups_name_uq','beneficiary_segments_name_ar_uq','execution_need_types_name_uq','monitoring_methods_name_ar_uq'];
$check = function (bool $condition, string $message): void { if (! $condition) throw new RuntimeException($message); };
$count = fn () => (int) DB::table('information_schema.STATISTICS')->whereRaw('TABLE_SCHEMA = DATABASE()')->whereIn('INDEX_NAME', $indexes)->distinct()->count('INDEX_NAME');
$check($count() === 7, 'Run feature suite migrations first.');
$branch = App\Models\Branch::factory()->create();
$tables = ['community_organizations', 'local_communities'];
try {
    $migration->down();
    $check($count() === 0, 'down did not remove all seven indexes');
    foreach ($tables as $table) DB::table($table)->insert(['branch_id'=>$branch->id,'name'=>'QA migration unique']);
    $migration->up();
    $check($count() === 7, 'healthy up failed');
    foreach ($tables as $table) $check(DB::table($table)->where('branch_id',$branch->id)->value('name') === 'QA migration unique', 'migration changed data');
    $migration->down();
    $check($count() === 0, 'healthy rollback failed');
    echo "PASS healthy up and rollback; data preserved\n";
    foreach ($tables as $table) {
        foreach (['QA migration unique', 'qa migration unique', ' QA migration unique', 'QA migration unique ', 'QA  migration unique'] as $duplicate) {
            $id = DB::table($table)->insertGetId(['branch_id' => $branch->id, 'name' => $duplicate]);
            try {
                $migration->up();
                throw new LogicException('Invalid data was accepted');
            } catch (RuntimeException $e) {
                $check(str_contains($e->getMessage(), $table), 'Unexpected migration rejection: '.$e->getMessage());
                $check($count() === 0, 'Migration performed partial DDL before rejecting duplicates');
                echo 'PASS '.$table.' rejection before DDL: '.json_encode($duplicate)."\n";
            }
            DB::table($table)->where('id', $id)->delete();
        }
        try {
            DB::table($table)->insert(['branch_id' => null, 'name' => 'QA null branch']);
            throw new LogicException('NULL branch unexpectedly accepted');
        } catch (Illuminate\Database\QueryException $e) {
            $check(($e->errorInfo[1] ?? null) === 1048, 'Unexpected NULL branch failure');
            echo "PASS {$table} NULL branch rejected by existing NOT NULL schema\n";
        }
    }
    $migration->up();
    $check($count() === 7, 'final up failed');
    $check(max(array_map('strlen',$indexes)) <= 64, 'index name exceeds MySQL limit');
    echo "PASS reapply; all index names within MySQL 64-character limit\n";
} finally {
    foreach ($tables as $table) DB::table($table)->where('branch_id',$branch->id)->delete();
    $branch->delete();
    if ($count() === 0) $migration->up();
}
