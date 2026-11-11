<?php

namespace App\Console\Commands;

use App\Modules\Movement\Models\Driver;
use App\Modules\Movement\Models\Vehicle;
use App\Modules\Movement\Support\MovementAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * One-time import of real legacy vehicles and drivers (plan §18.4, D1, Revision 4). Lives in
 * app/Console/Commands under the approved R4 exception.
 *
 * - Dry run by default; writes only with --execute.
 * - Idempotent through legacy_source_id (soft-deleted Movement rows included): a legacy id is never imported twice.
 * - Skips the demo rows of the removed MovementSeeder, soft-deleted legacy rows, vehicles without vehicle_no,
 *   and duplicate fleet/plate numbers (reported, not imported).
 * - Vehicles arrive as pending_verification with the legacy status in status_reason; drivers as
 *   pending_verification. Type and capacity are left for the manager. No request, day or trip data is read.
 * - Reads the legacy tables with the query builder; the legacy models are never used.
 */
class MovementImportLegacyFleet extends Command
{
    protected $signature = 'movement:import-legacy-fleet
        {--execute : Write the import. Without it the command is a dry run}
        {--dry-run : Explicit dry run (the default)}';

    protected $description = 'Import real legacy vehicles/drivers into the Movement module as pending_verification (dry run unless --execute).';

    /** Demo vehicles from the removed MovementSeeder (git history, ea11b87^): plate TRN-10x with vehicle_no BUS-x. */
    public const DEMO_VEHICLE_PLATE = '/^TRN-10\d$/';
    public const DEMO_VEHICLE_NUMBER = '/^BUS-\d$/';

    /** Demo drivers from the removed MovementSeeder: exact name + phone pairs. */
    public const DEMO_DRIVERS = [
        ['Ahmad Khalil', '0790001001'],
        ['Sami Odeh', '0790001002'],
        ['Nader Issa', '0790001003'],
        ['Fadi Jaber', '0790001004'],
    ];

    public function handle(): int
    {
        if ($this->option('execute') && $this->option('dry-run')) {
            $this->error('Use either --execute or --dry-run, not both.');

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $this->info($execute ? 'EXECUTE: rows will be written.' : 'DRY RUN: nothing will be written. Re-run with --execute to import.');

        $report = [];
        $import = function () use (&$report, $execute) {
            $report = array_merge($this->vehicles($execute), $this->drivers($execute));
        };

        $execute ? DB::transaction($import) : $import();

        $this->table(['Kind', 'Legacy ID', 'Result', 'Movement ID', 'Detail'], $report);
        $counts = collect($report)->countBy(fn (array $row) => $row[0].' '.$row[2]);
        $this->table(['Kind / result', 'Count'], $counts->map(fn ($count, $key) => [$key, $count])->values()->all());

        return self::SUCCESS;
    }

    private function vehicles(bool $execute): array
    {
        if (! Schema::hasTable('vehicles')) {
            return [['vehicle', '-', 'skipped', '-', 'legacy table vehicles does not exist']];
        }

        $report = [];
        $seenFleet = Vehicle::withTrashed()->pluck('fleet_number')->map(fn ($v) => mb_strtolower($v))->flip()->all();
        $seenPlate = Vehicle::withTrashed()->whereNotNull('plate_number')->pluck('plate_number')->map(fn ($v) => mb_strtolower($v))->flip()->all();
        $imported = Vehicle::withTrashed()->whereNotNull('legacy_source_id')->pluck('id', 'legacy_source_id')->all();

        foreach (DB::table('vehicles')->orderBy('id')->get() as $row) {
            $fleet = trim((string) $row->vehicle_no);
            $plate = trim((string) $row->plate_no);

            $skip = match (true) {
                isset($imported[$row->id]) => ['already_imported', 'Movement vehicle #'.$imported[$row->id]],
                $row->deleted_at !== null => ['skipped', 'deleted in the legacy table'],
                preg_match(self::DEMO_VEHICLE_PLATE, $plate) === 1 && preg_match(self::DEMO_VEHICLE_NUMBER, $fleet) === 1 => ['skipped', 'demo row'],
                $fleet === '' => ['skipped', 'missing vehicle_no'],
                isset($seenFleet[mb_strtolower($fleet)]) => ['duplicate', 'fleet number '.$fleet.' already exists'],
                $plate !== '' && isset($seenPlate[mb_strtolower($plate)]) => ['duplicate', 'plate '.$plate.' already exists'],
                default => null,
            };

            if ($skip) {
                $report[] = ['vehicle', $row->id, $skip[0], $skip[0] === 'already_imported' ? $imported[$row->id] : '-', $skip[1]];

                continue;
            }

            $seenFleet[mb_strtolower($fleet)] = true;
            if ($plate !== '') {
                $seenPlate[mb_strtolower($plate)] = true;
            }

            $newId = '-';
            if ($execute) {
                $vehicle = Vehicle::create([
                    'fleet_number' => $fleet,
                    'plate_number' => $plate !== '' ? $plate : null,
                    'operational_status' => Vehicle::STATUS_PENDING_VERIFICATION,
                    'status_reason' => 'Imported; legacy status: '.($row->status ?? 'none'),
                    'status_changed_at' => now(),
                    'legacy_source_id' => $row->id,
                ]);
                MovementAudit::record('vehicle.imported', $vehicle, null, $vehicle->getAttributes());
                $newId = $vehicle->id;
            }

            $report[] = ['vehicle', $row->id, $execute ? 'imported' : 'would_import', $newId, $fleet.($plate !== '' ? ' / '.$plate : '')];
        }

        return $report;
    }

    private function drivers(bool $execute): array
    {
        if (! Schema::hasTable('drivers')) {
            return [['driver', '-', 'skipped', '-', 'legacy table drivers does not exist']];
        }

        $report = [];
        $imported = Driver::withTrashed()->whereNotNull('legacy_source_id')->pluck('id', 'legacy_source_id')->all();
        $demo = collect(self::DEMO_DRIVERS)->map(fn (array $pair) => $pair[0].'|'.$pair[1])->flip()->all();

        foreach (DB::table('drivers')->orderBy('id')->get() as $row) {
            $name = trim((string) $row->name);
            $phone = trim((string) $row->phone);

            $skip = match (true) {
                isset($imported[$row->id]) => ['already_imported', 'Movement driver #'.$imported[$row->id]],
                $row->deleted_at !== null => ['skipped', 'deleted in the legacy table'],
                isset($demo[$name.'|'.$phone]) => ['skipped', 'demo row'],
                $name === '' => ['skipped', 'missing name'],
                default => null,
            };

            if ($skip) {
                $report[] = ['driver', $row->id, $skip[0], $skip[0] === 'already_imported' ? $imported[$row->id] : '-', $skip[1]];

                continue;
            }

            // Legacy drivers carry no licence number, so there are no licence duplicates to detect.
            $newId = '-';
            if ($execute) {
                $driver = Driver::create([
                    'name' => $name,
                    'phone' => $phone !== '' ? $phone : null,
                    'employment_status' => Driver::STATUS_PENDING_VERIFICATION,
                    'notes' => 'Imported; legacy status: '.($row->status ?? 'none'),
                    'legacy_source_id' => $row->id,
                ]);
                MovementAudit::record('driver.imported', $driver, null, $driver->getAttributes());
                $newId = $driver->id;
            }

            $report[] = ['driver', $row->id, $execute ? 'imported' : 'would_import', $newId, $name];
        }

        return $report;
    }
}
