<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * 2026_11_11_000800_drop_legacy_transport_request_tables (plan §18.3, Revision 5, release set 4B).
 * - SQLite tests use a private in-memory connection only (skipped without pdo_sqlite).
 * - The MySQL test compares SHOW CREATE TABLE of the original migrations with down(); it runs only
 *   when MOVEMENT_DISPOSABLE_DB=true and the default connection is MySQL (the disposable test container, CLAUDE.md).
 */
class MovementDropLegacyTransportRequestTablesMigrationTest extends TestCase
{
    private const CONNECTION = 'movement_b_drop_sqlite';
    private const MIGRATION = '2026_11_11_000800_drop_legacy_transport_request_tables.php';
    private const ORIGINALS = [
        '2026_02_13_0000070_create_transport_requests_table.php',
        '2026_02_13_0000071_create_transport_request_trips_table.php',
        '2026_02_13_0000072_create_transport_request_actions_table.php',
        '2026_02_13_0000073_create_transport_request_feedback_table.php',
    ];
    private const TABLES = ['transport_requests', 'transport_request_trips', 'transport_request_actions', 'transport_request_feedback'];

    private ?string $originalDefault = null;
    private bool $usedMysql = false;

    private function useIsolatedSqlite(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not installed.');
        }

        $this->originalDefault = config('database.default');
        config([
            'database.connections.'.self::CONNECTION => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true],
            'database.default' => self::CONNECTION,
        ]);
        DB::purge(self::CONNECTION);

        foreach (['users', 'branches', 'drivers', 'vehicles'] as $parent) {
            Schema::create($parent, fn (Blueprint $table) => $table->id());
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalDefault !== null) {
            DB::purge(self::CONNECTION);
            config(['database.default' => $this->originalDefault]);
        }
        if ($this->usedMysql) {
            RefreshDatabaseState::$migrated = false;
        }

        parent::tearDown();
    }

    private function migration(): object
    {
        return require database_path('migrations/'.self::MIGRATION);
    }

    private function assertBTables(bool $present): void
    {
        foreach (self::TABLES as $table) {
            $this->assertSame($present, Schema::hasTable($table), "Unexpected presence state for [{$table}].");
        }
    }

    private function seedOneOfEach(): void
    {
        DB::table('users')->insert(['id' => 1]);
        DB::table('drivers')->insert(['id' => 1]);
        DB::table('vehicles')->insert(['id' => 1]);
        DB::table('transport_requests')->insert(['id' => 1, 'requester_id' => 1, 'request_date' => '2026-01-01']);
    }

    public function test_sqlite_down_recreates_empty_structure_and_up_is_idempotent(): void
    {
        $this->useIsolatedSqlite();
        $migration = $this->migration();

        $migration->down();
        $this->assertBTables(true);
        $this->assertTrue(Schema::hasColumns('transport_requests', ['requester_id', 'requester_branch_id', 'request_date', 'day_name', 'driver_id', 'status', 'movement_officer_notes', 'general_notes']));
        $this->assertTrue(Schema::hasColumns('transport_request_feedback', ['transport_request_id', 'submitted_by', 'punctuality_score', 'overall_score', 'comment']));

        $migration->up();
        $this->assertBTables(false);
        $migration->up();
        $this->assertBTables(false);
    }

    public function test_sqlite_guard_refuses_rows_checks_children_and_honours_the_flag(): void
    {
        $this->useIsolatedSqlite();
        $migration = $this->migration();
        $migration->down();
        $this->seedOneOfEach();
        DB::table('transport_request_feedback')->insert(['transport_request_id' => 1]);

        try {
            $migration->up();
            $this->fail('The migration dropped B tables that still contain rows.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('[transport_request_feedback]', $e->getMessage(), 'Children are checked first.');
        }
        $this->assertBTables(true);
        $this->assertSame(1, DB::table('transport_requests')->count());

        config(['movement.legacy.allow_drop_with_rows' => true]);
        $migration->up();
        $this->assertBTables(false);
    }

    public function test_sqlite_tables_are_dropped_children_first(): void
    {
        $this->useIsolatedSqlite();
        $migration = $this->migration();
        $migration->down();

        $drops = [];
        DB::listen(function ($query) use (&$drops) {
            if (preg_match('/^drop table if exists "?(\w+)"?/i', $query->sql, $m)) {
                $drops[] = $m[1];
            }
        });
        $migration->up();

        $this->assertSame(['transport_request_feedback', 'transport_request_actions', 'transport_request_trips', 'transport_requests'], $drops);
    }

    public function test_mysql_down_matches_the_original_structure_and_the_full_chain_migrates(): void
    {
        // migrate:fresh wipes the default connection: run only when the runner declares it disposable.
        if (! filter_var(env('MOVEMENT_DISPOSABLE_DB', false), FILTER_VALIDATE_BOOLEAN)
            || config('database.connections.'.config('database.default').'.driver') !== 'mysql') {
            $this->markTestSkipped('Needs a disposable MySQL database (MOVEMENT_DISPOSABLE_DB=true), e.g. the test container.');
        }
        $this->usedMysql = true;

        // Full chain (including this drop) applies cleanly.
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
        $this->assertBTables(false);

        foreach (self::ORIGINALS as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $showCreate = fn () => collect(self::TABLES)->mapWithKeys(fn ($t) => [$t => preg_replace('/ AUTO_INCREMENT=\d+/', '', ((array) DB::selectOne("SHOW CREATE TABLE `{$t}`"))['Create Table'])])->all();
        $original = $showCreate();

        $migration = $this->migration();
        $migration->up();
        $this->assertBTables(false);
        $migration->down();
        $recreated = $showCreate();

        $this->assertSame($original, $recreated, 'down() must recreate the original B structure exactly.');
        $migration->up();
        $this->assertBTables(false);
    }
}
