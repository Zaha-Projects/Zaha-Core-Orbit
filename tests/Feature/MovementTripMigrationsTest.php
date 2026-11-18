<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

/**
 * Movement Phase 5 (5A) table migrations (plan §7.8–7.10, §18.0 category 2, Revision 6).
 * Database test — runs migrate:fresh itself, so it needs a disposable database (MOVEMENT_DISPOSABLE_DB=true).
 */
class MovementTripMigrationsTest extends TestCase
{
    /** FK order: each table references only tables created before it. */
    private const MIGRATIONS = [
        'movement_trips' => '2026_11_18_000200_create_movement_trips_table.php',
        'movement_trip_stops' => '2026_11_18_000300_create_movement_trip_stops_table.php',
        'movement_bookings' => '2026_11_18_000400_create_movement_bookings_table.php',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (! filter_var(env('MOVEMENT_DISPOSABLE_DB', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->markTestSkipped('Needs a disposable database (MOVEMENT_DISPOSABLE_DB=true), e.g. the test container.');
        }

        $this->assertSame(0, Artisan::call('migrate:fresh', ['--force' => true]));
    }

    protected function tearDown(): void
    {
        // Rows inserted here are committed; make the next RefreshDatabase class rebuild the schema.
        RefreshDatabaseState::$migrated = false;

        parent::tearDown();
    }

    private function migration(string $table): object
    {
        return require database_path('migrations/'.self::MIGRATIONS[$table]);
    }

    /** Minimal committed rows: a trip with two stops and one request booking. */
    private function seedTrip(): array
    {
        $userId = DB::table('users')->insertGetId(['name' => 'U', 'email' => uniqid().'@example.test', 'password' => 'x', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $tripId = DB::table('movement_trips')->insertGetId(['status' => 'draft', 'pre_trip_buffer_minutes' => 15, 'post_trip_buffer_minutes' => 15, 'created_at' => now(), 'updated_at' => now()]);
        $a = DB::table('movement_trip_stops')->insertGetId(['movement_trip_id' => $tripId, 'sequence' => 1, 'name' => 'A', 'created_at' => now(), 'updated_at' => now()]);
        $b = DB::table('movement_trip_stops')->insertGetId(['movement_trip_id' => $tripId, 'sequence' => 2, 'name' => 'B', 'created_at' => now(), 'updated_at' => now()]);

        return [$userId, $tripId, $a, $b];
    }

    private function booking(array $ids, string $source, string $status): array
    {
        [$userId, $tripId, $a, $b] = $ids;

        return ['movement_trip_id' => $tripId, 'source' => $source, 'booked_by_user_id' => $userId, 'pickup_stop_id' => $a, 'dropoff_stop_id' => $b,
            'passenger_count' => 2, 'status' => $status, 'created_at' => now(), 'updated_at' => now()];
    }

    public function test_full_chain_creates_the_tables_and_retires_c(): void
    {
        $this->assertFalse(Schema::hasTable('movement_days'), 'C is dropped (§18.3).');
        $this->assertTrue(Schema::hasColumns('movement_trips', [
            'reference_no', 'status', 'vehicle_id', 'driver_id', 'seat_capacity', 'planned_departure_at', 'planned_arrival_at',
            'pre_trip_buffer_minutes', 'post_trip_buffer_minutes', 'blocked_from', 'blocked_until', 'paired_trip_id',
            'vehicle_waits_between', 'driver_waits_between', 'allows_join', 'booking_closes_at', 'estimated_distance_m',
            'estimated_duration_s', 'route_source', 'merged_into_trip_id', 'published_at', 'published_by', 'cancelled_at',
            'cancelled_by', 'cancellation_reason', 'actual_departure_at', 'actual_arrival_at', 'driver_acknowledged_at', 'created_by',
        ]));
        $this->assertFalse(Schema::hasColumn('movement_trips', 'movement_day_id'));
        $this->assertFalse(Schema::hasColumn('movement_trips', 'branch_id'), 'R5: no branch isolation.');
        $this->assertTrue(Schema::hasColumns('movement_trip_stops', ['movement_trip_id', 'sequence', 'location_id', 'name', 'address', 'latitude', 'longitude', 'maps_url', 'planned_arrival_at', 'planned_departure_at', 'running_seconds_from_previous', 'distance_m_from_previous', 'allows_pickup', 'allows_drop_off', 'actual_arrival_at', 'actual_departure_at']));
        $this->assertTrue(Schema::hasColumns('movement_bookings', ['movement_trip_id', 'request_leg_id', 'source', 'booked_by_user_id', 'branch_id', 'context_type', 'context_id', 'context_label', 'pickup_stop_id', 'dropoff_stop_id', 'passenger_count', 'contact_phone', 'notes', 'status', 'client_token', 'active_join_key', 'decided_by', 'decided_at', 'decision_comment', 'cancelled_by', 'cancelled_at', 'cancellation_reason']));

        $nullable = collect(DB::select("SELECT COLUMN_NAME AS col, IS_NULLABLE AS n FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'movement_trips'"))->pluck('n', 'col');
        $this->assertSame('YES', $nullable['seat_capacity'], 'Revision 6: empty until set or assigned.');
    }

    public function test_foreign_keys_follow_d19(): void
    {
        $rules = collect(DB::select(
            'SELECT k.TABLE_NAME AS tbl, k.COLUMN_NAME AS col, k.REFERENCED_TABLE_NAME AS ref, rc.DELETE_RULE AS on_delete
             FROM information_schema.KEY_COLUMN_USAGE k
             JOIN information_schema.REFERENTIAL_CONSTRAINTS rc ON rc.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND rc.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
             WHERE k.TABLE_SCHEMA = DATABASE() AND k.TABLE_NAME IN (?, ?, ?) AND k.REFERENCED_TABLE_NAME IS NOT NULL',
            array_keys(self::MIGRATIONS)
        ))->mapWithKeys(fn ($row) => [$row->tbl.'.'.$row->col => $row->ref.':'.$row->on_delete])->sortKeys()->all();

        $this->assertSame([
            'movement_bookings.booked_by_user_id' => 'users:RESTRICT',
            'movement_bookings.branch_id' => 'branches:SET NULL',
            'movement_bookings.cancelled_by' => 'users:SET NULL',
            'movement_bookings.decided_by' => 'users:SET NULL',
            'movement_bookings.dropoff_stop_id' => 'movement_trip_stops:RESTRICT',
            'movement_bookings.movement_trip_id' => 'movement_trips:RESTRICT',
            'movement_bookings.pickup_stop_id' => 'movement_trip_stops:RESTRICT',
            'movement_bookings.request_leg_id' => 'movement_request_legs:RESTRICT',
            'movement_trip_stops.location_id' => 'movement_locations:RESTRICT',
            'movement_trip_stops.movement_trip_id' => 'movement_trips:CASCADE',
            'movement_trips.cancelled_by' => 'users:SET NULL',
            'movement_trips.created_by' => 'users:SET NULL',
            'movement_trips.driver_id' => 'movement_drivers:RESTRICT',
            'movement_trips.merged_into_trip_id' => 'movement_trips:SET NULL',
            'movement_trips.paired_trip_id' => 'movement_trips:SET NULL',
            'movement_trips.published_by' => 'users:SET NULL',
            'movement_trips.vehicle_id' => 'movement_vehicles:RESTRICT',
        ], $rules);
    }

    public function test_active_join_key_backstops_one_active_join_per_user_and_trip(): void
    {
        $ids = $this->seedTrip();
        DB::table('movement_bookings')->insert($this->booking($ids, 'request', 'confirmed'));
        DB::table('movement_bookings')->insert($this->booking($ids, 'request', 'confirmed'));
        DB::table('movement_bookings')->insert($this->booking($ids, 'join', 'cancelled'));
        DB::table('movement_bookings')->insert($this->booking($ids, 'join', 'pending'));

        $keys = DB::table('movement_bookings')->orderBy('id')->pluck('active_join_key')->all();
        $this->assertSame([null, null, null, $ids[1].'-'.$ids[0]], $keys, 'Only active join bookings get a key.');

        $this->expectException(QueryException::class);
        DB::table('movement_bookings')->insert($this->booking($ids, 'join', 'confirmed'));
    }

    public function test_stop_sequence_is_unique_per_trip(): void
    {
        [, $tripId] = $this->seedTrip();

        $this->expectException(QueryException::class);
        DB::table('movement_trip_stops')->insert(['movement_trip_id' => $tripId, 'sequence' => 2, 'name' => 'Duplicate', 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_down_refuses_while_rows_exist(): void
    {
        $ids = $this->seedTrip();
        DB::table('movement_bookings')->insert($this->booking($ids, 'request', 'confirmed'));

        foreach (array_reverse(array_keys(self::MIGRATIONS)) as $table) {
            try {
                $this->migration($table)->down();
                $this->fail("down() of {$table} must refuse while rows exist.");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString("[{$table}]", $e->getMessage());
            }
            $this->assertTrue(Schema::hasTable($table));
        }
    }

    public function test_rollback_newest_first_and_up_again_while_empty(): void
    {
        $chain = self::MIGRATIONS + ['movement_days' => '2026_11_18_000100_drop_legacy_movement_day_tables.php'];
        uasort($chain, fn ($a, $b) => strcmp($a, $b));
        $run = fn (string $file) => require database_path('migrations/'.$file);

        foreach (array_reverse($chain, true) as $table => $file) {
            $run($file)->down();
        }
        $this->assertTrue(Schema::hasTable('movement_days'), 'Rolling back the C drop restores the empty C tables.');
        $this->assertTrue(Schema::hasColumn('movement_trips', 'movement_day_id'));

        foreach ($chain as $file) {
            $run($file)->up();
        }
        $this->assertFalse(Schema::hasTable('movement_days'));
        $this->assertTrue(Schema::hasColumn('movement_trips', 'reference_no'));
        $this->assertTrue(Schema::hasTable('movement_bookings'));
    }
}
