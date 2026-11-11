<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * movement:import-legacy-fleet (plan §18.4, D1, Revision 4). Never run against a real database.
 * Database test — disposable database only.
 */
class MovementImportLegacyFleetTest extends TestCase
{
    use RefreshDatabase;

    private array $legacy = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMockingConsoleOutput();

        $branch = DB::table('branches')->insertGetId(['name' => 'Branch', 'created_at' => now(), 'updated_at' => now()]);
        $vehicle = fn (array $row) => DB::table('vehicles')->insertGetId($row + ['status' => 'available', 'branch_id' => $branch, 'created_at' => now(), 'updated_at' => now()]);
        $driver = fn (array $row) => DB::table('drivers')->insertGetId($row + ['status' => 'active', 'created_at' => now(), 'updated_at' => now()]);

        $this->legacy = [
            'real' => $vehicle(['vehicle_no' => 'Z-01', 'plate_no' => '12-34567', 'status' => 'in_service']),
            'real_no_plate' => $vehicle(['vehicle_no' => 'Z-02', 'plate_no' => null]),
            'demo' => $vehicle(['vehicle_no' => 'BUS-1', 'plate_no' => 'TRN-101']),
            'no_number' => $vehicle(['vehicle_no' => null, 'plate_no' => '99-11111']),
            'dup_plate' => $vehicle(['vehicle_no' => 'Z-03', 'plate_no' => '12-34567']),
            'dup_existing' => $vehicle(['vehicle_no' => 'EXISTING-1', 'plate_no' => '55-55555']),
            'legacy_deleted' => $vehicle(['vehicle_no' => 'Z-04', 'plate_no' => '77-77777', 'deleted_at' => now()]),
            'driver_real' => $driver(['name' => 'Real Driver', 'phone' => '0781234567', 'status' => 'on_leave']),
            'driver_demo' => $driver(['name' => 'Ahmad Khalil', 'phone' => '0790001001']),
            'driver_deleted' => $driver(['name' => 'Gone Driver', 'deleted_at' => now()]),
        ];

        // A Movement vehicle that already uses one of the legacy fleet numbers.
        Vehicle::create(['fleet_number' => 'EXISTING-1', 'operational_status' => Vehicle::STATUS_OPERATIONAL]);
    }

    private function runImport(array $options = []): string
    {
        $this->assertSame(0, Artisan::call('movement:import-legacy-fleet', $options));

        return Artisan::output();
    }

    public function test_dry_run_is_the_default_and_writes_nothing(): void
    {
        $writes = [];
        DB::listen(function ($query) use (&$writes) {
            if (! preg_match('/^\s*select\b/i', $query->sql)) {
                $writes[] = $query->sql;
            }
        });

        $output = $this->runImport();
        $this->runImport(['--dry-run' => true]);

        $this->assertSame([], $writes, 'A dry run must not write.');
        $this->assertSame(1, Vehicle::withTrashed()->count());
        $this->assertSame(0, Driver::withTrashed()->count());
        $this->assertStringContainsString('DRY RUN', $output);
        $this->assertMatchesRegularExpression('/vehicle\s*\|\s*'.$this->legacy['real'].'\s*\|\s*would_import/', $output);
    }

    public function test_execute_imports_real_rows_as_pending_verification_and_reports_the_rest(): void
    {
        $output = $this->runImport(['--execute' => true]);

        $real = Vehicle::query()->where('legacy_source_id', $this->legacy['real'])->firstOrFail();
        $this->assertSame('Z-01', $real->fleet_number);
        $this->assertSame('12-34567', $real->plate_number);
        $this->assertSame(Vehicle::STATUS_PENDING_VERIFICATION, $real->operational_status);
        $this->assertSame('Imported; legacy status: in_service', $real->status_reason);
        $this->assertNull($real->vehicle_type_id);
        $this->assertNull($real->passenger_capacity);
        $this->assertTrue(Vehicle::query()->where('legacy_source_id', $this->legacy['real_no_plate'])->whereNull('plate_number')->exists());

        foreach (['demo', 'no_number', 'dup_plate', 'dup_existing', 'legacy_deleted'] as $key) {
            $this->assertFalse(Vehicle::withTrashed()->where('legacy_source_id', $this->legacy[$key])->exists(), "[{$key}] must not be imported.");
        }
        $this->assertMatchesRegularExpression('/'.$this->legacy['demo'].'\s*\|\s*skipped\s*\|\s*-\s*\|\s*demo row/', $output);
        $this->assertMatchesRegularExpression('/'.$this->legacy['no_number'].'\s*\|\s*skipped\s*\|\s*-\s*\|\s*missing vehicle_no/', $output);
        $this->assertMatchesRegularExpression('/'.$this->legacy['dup_plate'].'\s*\|\s*duplicate\s*\|\s*-\s*\|\s*plate 12-34567/', $output);
        $this->assertMatchesRegularExpression('/'.$this->legacy['dup_existing'].'\s*\|\s*duplicate\s*\|\s*-\s*\|\s*fleet number EXISTING-1/', $output);

        $driver = Driver::query()->where('legacy_source_id', $this->legacy['driver_real'])->firstOrFail();
        $this->assertSame('Real Driver', $driver->name);
        $this->assertSame(Driver::STATUS_PENDING_VERIFICATION, $driver->employment_status);
        $this->assertNull($driver->user_id);
        $this->assertFalse(Driver::withTrashed()->where('legacy_source_id', $this->legacy['driver_demo'])->exists());
        $this->assertFalse(Driver::withTrashed()->where('legacy_source_id', $this->legacy['driver_deleted'])->exists());

        $this->assertSame(2, AuditLog::query()->where('action', 'vehicle.imported')->count());
        $this->assertSame(1, AuditLog::query()->where('action', 'driver.imported')->count());

        // Legacy tables are read only.
        $this->assertSame(7, DB::table('vehicles')->count());
        $this->assertSame(3, DB::table('drivers')->count());
    }

    public function test_rerun_imports_nothing_new_even_after_a_soft_delete(): void
    {
        $this->runImport(['--execute' => true]);
        Vehicle::query()->where('legacy_source_id', $this->legacy['real'])->firstOrFail()->delete();
        $before = [Vehicle::withTrashed()->count(), Driver::withTrashed()->count(), AuditLog::query()->count()];

        $output = $this->runImport(['--execute' => true]);

        $this->assertSame($before, [Vehicle::withTrashed()->count(), Driver::withTrashed()->count(), AuditLog::query()->count()]);
        $this->assertMatchesRegularExpression('/vehicle\s*\|\s*'.$this->legacy['real'].'\s*\|\s*already_imported/', $output);
        $this->assertMatchesRegularExpression('/driver\s*\|\s*'.$this->legacy['driver_real'].'\s*\|\s*already_imported/', $output);
    }

    public function test_execute_and_dry_run_together_are_refused(): void
    {
        $this->assertSame(1, Artisan::call('movement:import-legacy-fleet', ['--execute' => true, '--dry-run' => true]));
        $this->assertSame(1, Vehicle::withTrashed()->count());
    }
}
