<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Exercises 2026_11_10_000100_drop_legacy_fleet_trip_tables on an isolated in-memory SQLite
 * connection only. It never touches the configured default (MySQL) database and is skipped
 * when pdo_sqlite is not installed.
 */
class DropLegacyFleetTripTablesMigrationTest extends TestCase
{
    private const CONNECTION = 'movement_migration_sqlite';

    private ?string $originalDefault = null;

    /**
     * Switch the default connection to a private in-memory SQLite database for this test only.
     * Called by the database tests; the flag-parsing test needs no database and always runs.
     */
    private function useIsolatedSqlite(): void
    {
        if (! extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite is not installed; the migration test needs an isolated in-memory SQLite connection.');
        }

        $this->originalDefault = config('database.default');
        config([
            'database.connections.'.self::CONNECTION => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'database.default' => self::CONNECTION,
        ]);
        DB::purge(self::CONNECTION);

        // Minimal parents for the legacy foreign keys.
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

        parent::tearDown();
    }

    private function migration(): object
    {
        $this->useIsolatedSqlite();

        return require database_path('migrations/2026_11_10_000100_drop_legacy_fleet_trip_tables.php');
    }

    private function assertLegacyTables(bool $present): void
    {
        foreach (['trips', 'trip_segments', 'trip_rounds'] as $table) {
            $this->assertSame($present, Schema::hasTable($table), "Unexpected presence state for [{$table}].");
        }
    }

    public function test_down_recreates_empty_structure_and_up_drops_empty_tables_idempotently(): void
    {
        $migration = $this->migration();

        $migration->down();
        $this->assertLegacyTables(true);
        $this->assertTrue(Schema::hasColumns('trips', ['trip_date', 'driver_id', 'vehicle_id', 'status', 'created_by', 'deleted_at']));
        $this->assertSame(0, DB::table('trips')->count());

        $migration->up();
        $this->assertLegacyTables(false);

        // Tables already absent: re-running must not fail.
        $migration->up();
        $this->assertLegacyTables(false);
    }

    public function test_up_refuses_to_drop_tables_that_contain_rows_unless_explicitly_allowed(): void
    {
        $migration = $this->migration();
        $migration->down();

        DB::table('users')->insert(['id' => 1]);
        DB::table('drivers')->insert(['id' => 1]);
        DB::table('vehicles')->insert(['id' => 1]);
        DB::table('trips')->insert(['trip_date' => '2026-01-01', 'driver_id' => 1, 'vehicle_id' => 1, 'created_by' => 1]);

        try {
            $migration->up();
            $this->fail('The migration dropped a legacy table that still contains rows.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('[trips]', $exception->getMessage());
        }
        $this->assertLegacyTables(true);
        $this->assertSame(1, DB::table('trips')->count());

        config(['movement.legacy.allow_drop_with_rows' => true]);
        $migration->up();
        $this->assertLegacyTables(false);
    }

    public function test_rows_in_a_child_table_are_detected_first_and_nothing_is_dropped(): void
    {
        $migration = $this->migration();
        $migration->down();

        DB::table('users')->insert(['id' => 1]);
        DB::table('drivers')->insert(['id' => 1]);
        DB::table('vehicles')->insert(['id' => 1]);
        $tripId = DB::table('trips')->insertGetId(['trip_date' => '2026-01-01', 'driver_id' => 1, 'vehicle_id' => 1, 'created_by' => 1]);
        DB::table('trip_rounds')->insert(['trip_id' => $tripId, 'round_no' => 1, 'location' => 'Amman']);

        try {
            $migration->up();
            $this->fail('The migration dropped legacy tables while they still contain rows.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('[trip_rounds]', $exception->getMessage());
        }

        // Guard runs for every table before any DROP, so nothing was dropped.
        $this->assertLegacyTables(true);
    }

    public function test_tables_are_dropped_children_first(): void
    {
        $migration = $this->migration();
        $migration->down();

        $drops = [];
        DB::listen(function ($query) use (&$drops) {
            if (preg_match('/^drop table if exists "?(\w+)"?/i', $query->sql, $match)) {
                $drops[] = $match[1];
            }
        });

        $migration->up();

        $this->assertSame(['trip_rounds', 'trip_segments', 'trips'], $drops);
    }

    /**
     * @dataProvider allowDropFlagValues
     */
    public function test_allow_drop_flag_is_parsed_strictly(?string $value, bool $expected): void
    {
        $previous = $_SERVER['MOVEMENT_ALLOW_LEGACY_DROP'] ?? null;

        try {
            if ($value === null) {
                unset($_SERVER['MOVEMENT_ALLOW_LEGACY_DROP']);
            } else {
                $_SERVER['MOVEMENT_ALLOW_LEGACY_DROP'] = $value;
            }

            $config = require config_path('movement.php');

            $this->assertSame($expected, $config['legacy']['allow_drop_with_rows']);
        } finally {
            if ($previous === null) {
                unset($_SERVER['MOVEMENT_ALLOW_LEGACY_DROP']);
            } else {
                $_SERVER['MOVEMENT_ALLOW_LEGACY_DROP'] = $previous;
            }
        }
    }

    public function allowDropFlagValues(): array
    {
        return [
            'unset' => [null, false],
            'true' => ['true', true],
            '1' => ['1', true],
            'false' => ['false', false],
            '0' => ['0', false],
            'no' => ['no', false],
            'off' => ['off', false],
            'empty' => ['', false],
        ];
    }
}
