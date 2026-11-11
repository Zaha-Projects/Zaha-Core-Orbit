<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Movement Phase 3 migrations (plan §7.1–7.5, §18.0 category 2, Revision 4).
 * Database test — run only against a disposable database (CLAUDE.md, database safety).
 * Runs the full chain with migrate:fresh itself, because DDL cannot run inside RefreshDatabase's transaction.
 */
class MovementFleetMigrationsTest extends TestCase
{
    /** FK order: every table only references tables created before it. */
    private const MIGRATIONS = [
        'movement_vehicle_types' => '2026_11_11_000100_create_movement_vehicle_types_table.php',
        'movement_locations' => '2026_11_11_000200_create_movement_locations_table.php',
        'movement_vehicles' => '2026_11_11_000300_create_movement_vehicles_table.php',
        'movement_drivers' => '2026_11_11_000400_create_movement_drivers_table.php',
        'movement_unavailabilities' => '2026_11_11_000500_create_movement_unavailabilities_table.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // The full migration chain, including the Phase 3 tables, must apply cleanly.
        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
    }

    private function migration(string $table): object
    {
        return require database_path('migrations/'.self::MIGRATIONS[$table]);
    }

    public function test_full_chain_creates_all_tables_with_expected_columns(): void
    {
        foreach (array_keys(self::MIGRATIONS) as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} missing after migrate:fresh.");
        }

        $this->assertTrue(Schema::hasColumns('movement_vehicles', ['fleet_number', 'plate_number', 'vehicle_type_id', 'passenger_capacity', 'operational_status', 'status_reason', 'status_changed_at', 'status_changed_by', 'home_location_id', 'legacy_source_id', 'deleted_at']));
        $this->assertTrue(Schema::hasColumns('movement_drivers', ['user_id', 'name', 'phone', 'licence_number', 'licence_category', 'licence_expires_on', 'employment_status', 'legacy_source_id', 'deleted_at']));
        $this->assertTrue(Schema::hasColumns('movement_locations', ['name_ar', 'name_en', 'address', 'latitude', 'longitude', 'google_place_id', 'maps_url', 'branch_id', 'is_active', 'deleted_at']));

        // R5: no branch isolation on vehicles or drivers.
        $this->assertFalse(Schema::hasColumn('movement_vehicles', 'branch_id'));
        $this->assertFalse(Schema::hasColumn('movement_drivers', 'branch_id'));
        // Unavailability windows are not a catalogue: no soft deletes (§7.4).
        $this->assertFalse(Schema::hasColumn('movement_unavailabilities', 'deleted_at'));
    }

    public function test_migrations_are_dated_after_the_cutoff_and_phase_1_in_fk_order(): void
    {
        $files = array_values(self::MIGRATIONS);
        $sorted = $files;
        sort($sorted);
        $this->assertSame($files, $sorted, 'Phase 3 migrations must sort in FK order.');

        foreach ($files as $file) {
            $this->assertGreaterThan('2026_09_10_000100', substr($file, 0, 17));
            $this->assertGreaterThan('2026_11_10_000100', substr($file, 0, 17));
            $this->assertFileExists(database_path('migrations/'.$file));
        }
    }

    public function test_foreign_keys_follow_the_d19_policy(): void
    {
        $rules = collect(DB::select(
            'SELECT TABLE_NAME AS tbl, COLUMN_NAME AS col, REFERENCED_TABLE_NAME AS ref, rc.DELETE_RULE AS on_delete
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND rc.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
             WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME LIKE ? AND k.REFERENCED_TABLE_NAME IS NOT NULL',
            ['movement\\_%']
        ))->mapWithKeys(fn ($row) => [$row->tbl.'.'.$row->col => $row->ref.':'.$row->on_delete])->sortKeys()->all();

        $this->assertSame([
            'movement_drivers.user_id' => 'users:SET NULL',
            'movement_locations.branch_id' => 'branches:SET NULL',
            'movement_unavailabilities.created_by' => 'users:SET NULL',
            'movement_unavailabilities.driver_id' => 'movement_drivers:RESTRICT',
            'movement_unavailabilities.vehicle_id' => 'movement_vehicles:RESTRICT',
            'movement_vehicles.home_location_id' => 'movement_locations:SET NULL',
            'movement_vehicles.status_changed_by' => 'users:SET NULL',
            'movement_vehicles.vehicle_type_id' => 'movement_vehicle_types:RESTRICT',
        ], $rules);
    }

    public function test_unique_identifiers_include_soft_deleted_rows(): void
    {
        DB::table('movement_vehicles')->insert(['fleet_number' => 'F-1', 'plate_number' => 'P-1', 'operational_status' => 'operational', 'deleted_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DB::table('movement_vehicles')->insert(['fleet_number' => 'F-1', 'operational_status' => 'operational', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_down_and_up_work_in_reverse_fk_order_while_empty(): void
    {
        foreach (array_reverse(array_keys(self::MIGRATIONS)) as $table) {
            $this->migration($table)->down();
            $this->assertFalse(Schema::hasTable($table), "{$table} should be dropped.");
        }

        foreach (array_keys(self::MIGRATIONS) as $table) {
            $this->migration($table)->up();
            $this->assertTrue(Schema::hasTable($table), "{$table} should be recreated.");
        }
    }

    public function test_down_refuses_while_rows_exist(): void
    {
        $typeId = DB::table('movement_vehicle_types')->insertGetId(['code' => 'bus', 'name_ar' => 'حافلة', 'name_en' => 'Bus', 'size_class' => 'large', 'created_at' => now(), 'updated_at' => now()]);
        $locationId = DB::table('movement_locations')->insertGetId(['name_en' => 'Depot', 'created_at' => now(), 'updated_at' => now()]);
        $vehicleId = DB::table('movement_vehicles')->insertGetId(['fleet_number' => 'F-9', 'vehicle_type_id' => $typeId, 'passenger_capacity' => 30, 'operational_status' => 'operational', 'home_location_id' => $locationId, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('movement_drivers')->insert(['name' => 'Driver', 'employment_status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('movement_unavailabilities')->insert(['vehicle_id' => $vehicleId, 'type' => 'maintenance', 'starts_at' => '2026-12-01 07:00:00', 'ends_at' => '2026-12-01 09:00:00', 'created_at' => now(), 'updated_at' => now()]);

        foreach (array_keys(self::MIGRATIONS) as $table) {
            try {
                $this->migration($table)->down();
                $this->fail("down() of {$table} must refuse while rows exist.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString("[{$table}]", $e->getMessage());
            }
            $this->assertTrue(Schema::hasTable($table), "{$table} must still exist.");
        }
    }
}
