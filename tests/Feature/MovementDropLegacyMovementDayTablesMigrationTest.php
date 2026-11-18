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
 * 2026_11_18_000100_drop_legacy_movement_day_tables (plan §18.3, Revision 6, release set 5A).
 * - SQLite tests use a private in-memory connection only (skipped without pdo_sqlite).
 * - The MySQL test compares SHOW CREATE TABLE of the original C migrations with down(); it runs only
 *   when MOVEMENT_DISPOSABLE_DB=true and the default connection is MySQL (the disposable test container, CLAUDE.md).
 */
class MovementDropLegacyMovementDayTablesMigrationTest extends TestCase
{
    private const CONNECTION = 'movement_c_drop_sqlite';
    private const MIGRATION = '2026_11_18_000100_drop_legacy_movement_day_tables.php';
    private const ORIGINALS = ['2026_03_09_000012_create_movement_days_table.php', '2026_03_09_000013_create_movement_trips_table.php'];
    private const NEW_TABLES = [
        'movement_bookings' => '2026_11_18_000400_create_movement_bookings_table.php',
        'movement_trip_stops' => '2026_11_18_000300_create_movement_trip_stops_table.php',
        'movement_trips' => '2026_11_18_000200_create_movement_trips_table.php',
    ];

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

        foreach (['users', 'drivers', 'vehicles'] as $parent) {
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

    private function assertCTables(bool $present): void
    {
        $this->assertSame($present, Schema::hasTable('movement_days'));
        $this->assertSame($present, Schema::hasTable('movement_trips') && Schema::hasColumn('movement_trips', 'movement_day_id'));
    }

    public function test_drop_is_dated_before_the_new_movement_trips_table(): void
    {
        $files = collect(scandir(database_path('migrations')))->filter(fn ($f) => str_ends_with($f, '.php'))->values();
        $this->assertLessThan($files->search('2026_11_18_000200_create_movement_trips_table.php'), $files->search(self::MIGRATION), '§18.3: drop C before creating the new movement_trips.');
        $this->assertLessThan($files->search(self::MIGRATION), $files->search('2026_11_11_000800_drop_legacy_transport_request_tables.php'));
    }

    public function test_sqlite_down_recreates_empty_structure_and_up_is_idempotent(): void
    {
        $this->useIsolatedSqlite();
        $migration = $this->migration();

        $migration->down();
        $this->assertCTables(true);
        $this->assertTrue(Schema::hasColumns('movement_days', ['driver_id', 'date', 'notes', 'created_by']));
        $this->assertTrue(Schema::hasColumns('movement_trips', ['movement_day_id', 'vehicle_id', 'destination', 'team', 'departure_time', 'return_time']));

        $migration->up();
        $this->assertCTables(false);
        $this->assertFalse(Schema::hasTable('movement_trips'));
        $migration->up();
        $this->assertCTables(false);
    }

    public function test_sqlite_guard_refuses_rows_checks_the_child_first_and_honours_the_flag(): void
    {
        $this->useIsolatedSqlite();
        $migration = $this->migration();
        $migration->down();
        DB::table('drivers')->insert(['id' => 1]);
        DB::table('movement_days')->insert(['id' => 1, 'driver_id' => 1, 'date' => '2026-03-10']);
        DB::table('movement_trips')->insert(['movement_day_id' => 1, 'destination' => 'Irbid']);

        try {
            $migration->up();
            $this->fail('The migration dropped C tables that still contain rows.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('[movement_trips]', $e->getMessage(), 'The child table is checked first.');
        }
        $this->assertCTables(true);

        config(['movement.legacy.allow_drop_with_rows' => true]);
        $migration->up();
        $this->assertCTables(false);
    }

    public function test_sqlite_never_drops_a_movement_trips_table_that_is_not_legacy_c(): void
    {
        $this->useIsolatedSqlite();
        Schema::create('movement_trips', function (Blueprint $table) {
            $table->id();
            $table->string('reference_no')->nullable();
        });
        DB::table('movement_trips')->insert(['reference_no' => 'MT-2030-000001']);

        $this->migration()->up();
        $this->assertTrue(Schema::hasTable('movement_trips'), 'The new module table must survive.');
        $this->assertSame(1, DB::table('movement_trips')->count());

        $this->expectException(RuntimeException::class);
        $this->migration()->down();
    }

    public function test_mysql_down_matches_the_original_structure(): void
    {
        // migrate:fresh wipes the default connection: run only when the runner declares it disposable.
        if (! filter_var(env('MOVEMENT_DISPOSABLE_DB', false), FILTER_VALIDATE_BOOLEAN)
            || config('database.connections.'.config('database.default').'.driver') !== 'mysql') {
            $this->markTestSkipped('Needs a disposable MySQL database (MOVEMENT_DISPOSABLE_DB=true), e.g. the test container.');
        }
        $this->usedMysql = true;

        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
        $this->assertFalse(Schema::hasTable('movement_days'));
        $this->assertFalse(Schema::hasColumn('movement_trips', 'movement_day_id'));

        // down() refuses while the new movement_trips exists (normal rollback removes it first).
        try {
            $this->migration()->down();
            $this->fail('down() must refuse while the new movement_trips exists.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('create_movement_trips_table', $e->getMessage());
        }

        foreach (self::NEW_TABLES as $file) {
            (require database_path('migrations/'.$file))->down();
        }
        foreach (self::ORIGINALS as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $showCreate = fn () => collect(['movement_days', 'movement_trips'])->mapWithKeys(fn ($t) => [$t => preg_replace('/ AUTO_INCREMENT=\d+/', '', ((array) DB::selectOne("SHOW CREATE TABLE `{$t}`"))['Create Table'])])->all();
        $original = $showCreate();

        $migration = $this->migration();
        $migration->up();
        $this->assertCTables(false);
        $migration->down();
        $this->assertSame($original, $showCreate(), 'down() must recreate the original C structure exactly.');

        $migration->up();
        foreach (array_reverse(self::NEW_TABLES) as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $this->assertTrue(Schema::hasTable('movement_bookings'));
    }
}
