<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Driver;
use App\Models\MovementDay;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class MovementSeeder extends Seeder
{
    public function run(): void
    {
        if (! Driver::query()->exists()) {
            foreach ([
                ['name' => 'Ahmad Khalil', 'phone' => '0790001001', 'status' => 'active'],
                ['name' => 'Sami Odeh', 'phone' => '0790001002', 'status' => 'active'],
                ['name' => 'Nader Issa', 'phone' => '0790001003', 'status' => 'active'],
                ['name' => 'Fadi Jaber', 'phone' => '0790001004', 'status' => 'active'],
            ] as $driver) {
                Driver::query()->create($driver);
            }
        }

        if (! Vehicle::query()->exists()) {
            $branchId = Branch::query()->value('id');
            foreach (range(1, 6) as $index) {
                Vehicle::query()->create([
                    'plate_no' => 'TRN-10'.$index,
                    'vehicle_no' => 'BUS-'.$index,
                    'status' => 'available',
                    'branch_id' => $branchId,
                ]);
            }
        }

        $drivers = Driver::query()->get();
        $vehicles = Vehicle::query()->get();
        $creatorId = User::query()->value('id');

        foreach ($drivers->take(4) as $driver) {
            foreach (range(0, 4) as $offset) {
                $movementDay = MovementDay::query()->firstOrCreate([
                    'driver_id' => $driver->id,
                    'date' => now()->subDays($offset)->toDateString(),
                ], [
                    'notes' => 'Daily movement record',
                    'created_by' => $creatorId,
                ]);

                if ($movementDay->trips()->exists()) {
                    continue;
                }

                foreach (range(1, rand(1, 4)) as $tripNo) {
                    $movementDay->trips()->create([
                        'vehicle_id' => $vehicles->random()->id,
                        'destination' => collect(['Khalda Center', 'Downtown Center', 'Airport Road', 'Sports Hall'])->random(),
                        'team' => collect(['Programs', 'Relations', 'Logistics', 'Support'])->random(),
                        'departure_time' => sprintf('%02d:%02d', rand(7, 15), [0, 15, 30, 45][array_rand([0, 1, 2, 3])]),
                        'return_time' => sprintf('%02d:%02d', rand(10, 22), [0, 15, 30, 45][array_rand([0, 1, 2, 3])]),
                    ]);
                }
            }
        }
    }
}
