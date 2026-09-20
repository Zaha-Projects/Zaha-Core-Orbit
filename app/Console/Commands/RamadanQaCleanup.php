<?php

namespace App\Console\Commands;

use App\Models\MonthlyActivity;
use App\Modules\Events\Models\RamadanIftar;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RamadanQaCleanup extends Command
{
    protected $signature = 'ramadan:cleanup-local-qa {--execute : Delete only the verified September 2026 QA manifest}';
    protected $description = 'Preview or remove the specific locally documented Ramadan QA records; refuses production';

    public function handle(): int
    {
        if (! app()->environment('local') || DB::connection()->getDatabaseName() !== 'zaha_core_orbit'
            || ! in_array(config('database.connections.'.config('database.default').'.host'), ['127.0.0.1', 'localhost'], true)) {
            $this->error('Refusing cleanup outside local zaha_core_orbit on loopback.');
            return 1;
        }

        return DB::transaction(function (): int {
            $manifest = [
                'ramadan_iftars' => ['title', [2 => 'اختبار QA قبل الإنتاج - جمعية - معدل', 3 => 'اختبار QA قبل الإنتاج - مجتمع محلي']],
                'monthly_activities' => ['title', [2 => 'اختبار QA خطة شهرية مخصصة 20260919']],
                'mobilization_methods' => ['code', [22 => 'qa_preprod_mobilization_20260918', 23 => 'qa_preprod_mobilization_dup_20260918']],
                'target_groups' => ['code', [25 => 'qa_preprod_target_both_20260918', 26 => 'qa_preprod_target_iftar_20260918', 27 => 'qa_preprod_target_monthly_20260918', 28 => 'qa_preprod_target_none_20260918']],
                'beneficiary_segments' => ['code', [21 => 'qa_preprod_age_20260918']],
                'ramadan_iftar_gift_types' => ['code', [13 => 'qa_preprod_gift_20260918']],
                'execution_need_types' => ['code', [76 => 'qa_preprod_need_both_20260918', 77 => 'qa_preprod_need_iftar_20260918', 78 => 'qa_preprod_need_monthly_20260918', 79 => 'qa_preprod_need_none_20260918', 80 => 'qa_custom_monthly_20260919']],
                'community_organizations' => ['name', [2 => 'جمعية اختبار QA قبل الإنتاج - معدلة']],
                'local_communities' => ['name', [2 => 'مجتمع اختبار QA قبل الإنتاج - معدل']],
            ];
            $roots = [];
            foreach ($manifest as $table => [$column, $expected]) {
                $rows = DB::table($table)->whereIn('id', array_keys($expected))->lockForUpdate()->get();
                foreach ($rows as $row) {
                    $name = $row->title ?? $row->name ?? $row->name_ar ?? '';
                    if ($row->$column !== $expected[$row->id] || stripos($name, 'QA') === false) {
                        throw new RuntimeException("QA identity mismatch: {$table} #{$row->id}; nothing deleted.");
                    }
                    if (isset($row->branch_id) && (int) $row->branch_id !== 18) throw new RuntimeException('QA branch changed; refusing cleanup.');
                    if (isset($row->status) && ($row->status !== 'draft' || $row->execution_status !== 'planned' || (int) $row->created_by !== 4)) throw new RuntimeException('QA plan is no longer an untouched draft; refusing cleanup.');
                    if (isset($row->is_active) && $row->is_active) throw new RuntimeException('Deactivate QA reference records before cleanup.');
                }
                $roots[$table] = $rows->pluck('id')->all();
            }

            $iftars = $roots['ramadan_iftars'];
            $monthly = $roots['monthly_activities'];
            $queries = [];
            $add = function (string $table, $query) use (&$queries): void { $queries[$table] = $query; };
            $subjectQuery = fn (string $table) => DB::table($table)->where(function ($query) use ($iftars, $monthly) {
                $query->where(fn ($q) => $q->where('subject_type', 'ramadan_iftar')->whereIn('subject_id', $iftars))
                    ->orWhere(fn ($q) => $q->where('subject_type', 'monthly_activity')->whereIn('subject_id', $monthly));
            });
            $entityQuery = fn (string $table) => DB::table($table)->where(function ($query) use ($iftars, $monthly) {
                $query->where(fn ($q) => $q->where('entity_type', RamadanIftar::class)->whereIn('entity_id', $iftars))
                    ->orWhere(fn ($q) => $q->where('entity_type', MonthlyActivity::class)->whereIn('entity_id', $monthly));
            });
            $teams = $subjectQuery('execution_teams')->pluck('id')->all();
            $meals = DB::table('ramadan_iftar_meals')->whereIn('ramadan_iftar_id', $iftars)->pluck('id')->all();
            $instances = $entityQuery('workflow_instances')->pluck('id')->all();
            if ($subjectQuery('monitoring_reports')->exists()
                || DB::table('monthly_plan_edit_requests')->whereIn('entity_id', $monthly)->exists()
                || DB::table('monthly_plan_delete_requests')->whereIn('entity_id', $monthly)->exists()
                || DB::table('workflow_logs')->whereIn('workflow_instance_id', $instances)->exists()) {
                throw new RuntimeException('QA records have operational or approval history; manual review required.');
            }
            $add('ramadan_iftar_meal_items', DB::table('ramadan_iftar_meal_items')->whereIn('ramadan_iftar_meal_id', $meals));
            $add('execution_team_members', DB::table('execution_team_members')->whereIn('execution_team_id', $teams)->orWhereIn('monthly_activity_id', $monthly));
            foreach (['event_target_group', 'event_supplies', 'subject_execution_needs', 'subject_volunteer_requirements', 'execution_teams'] as $table) $add($table, $subjectQuery($table));
            foreach (['ramadan_iftar_attendees', 'ramadan_iftar_meals', 'ramadan_iftar_gifts', 'ramadan_iftar_program_segments'] as $table) $add($table, DB::table($table)->whereIn('ramadan_iftar_id', $iftars));
            foreach (['workflow_action_logs', 'workflow_instances', 'audit_logs'] as $table) $add($table, $entityQuery($table));
            $add('monthly_activity_change_logs', DB::table('monthly_activity_change_logs')->whereIn('monthly_activity_id', $monthly));
            $notifications = DB::table('in_app_notifications')->where('type', 'workflow_created')->get()->filter(function ($row) use ($monthly) {
                $meta = json_decode($row->meta ?? '{}', true);
                return in_array((int) ($meta['entity_id'] ?? 0), $monthly, true)
                    && str_replace('\\\\', '\\', $meta['entity_type'] ?? '') === MonthlyActivity::class
                    && str_contains($row->message, 'اختبار QA خطة شهرية مخصصة 20260919');
            })->pluck('id')->all();
            $add('in_app_notifications', DB::table('in_app_notifications')->whereIn('id', $notifications));
            foreach ($roots as $table => $ids) $add($table, DB::table($table)->whereIn('id', $ids));

            $deletions = [];
            foreach ($queries as $table => $query) $deletions[$table] = (clone $query)->lockForUpdate()->pluck('id')->all();
            // Check every incoming FK, including nullable SET NULL links, before any deletion.
            $foreignKeys = DB::table('information_schema.KEY_COLUMN_USAGE')->whereRaw('REFERENCED_TABLE_SCHEMA = DATABASE()')->whereIn('REFERENCED_TABLE_NAME', array_keys($deletions))->get();
            foreach ($foreignKeys as $fk) {
                $linked = DB::table($fk->TABLE_NAME)->whereIn($fk->COLUMN_NAME, $deletions[$fk->REFERENCED_TABLE_NAME])->pluck('id')->all();
                if (array_diff($linked, $deletions[$fk->TABLE_NAME] ?? [])) throw new RuntimeException("External reference in {$fk->TABLE_NAME}.{$fk->COLUMN_NAME}; nothing deleted.");
            }
            // Gift codes are a legacy string reference rather than an FK.
            $giftCodes = DB::table('ramadan_iftar_gift_types')->whereIn('id', $roots['ramadan_iftar_gift_types'])->pluck('code');
            if (DB::table('ramadan_iftar_gifts')->whereIn('gift_type', $giftCodes)->whereNotIn('id', $deletions['ramadan_iftar_gifts'])->exists()) throw new RuntimeException('QA gift type is used outside QA.');
            $this->line(json_encode(['mode' => $this->option('execute') ? 'execute' : 'preview', 'records' => $deletions], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if (! $this->option('execute')) return 0;
            foreach ($deletions as $table => $ids) DB::table($table)->whereIn('id', $ids)->delete();
            foreach ($queries as $table => $query) {
                if ((clone $query)->exists()) throw new RuntimeException("Remaining QA relation: {$table}; cleanup rolled back.");
            }
            $this->info('QA cleanup committed: all listed records and their verified dependent rows removed; no remaining QA relations.');
            return 0;
        });
    }
}
