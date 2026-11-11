<?php

namespace Database\Seeders;

use App\Modules\Movement\Models\VehicleType;
use Illuminate\Database\Seeder;

/**
 * Movement vehicle types (plan §7.1, §18.5, Revision 4).
 *
 * firstOrCreate by code (soft-deleted rows included): missing types are created, existing ones are never
 * overwritten (vehicle types are manager-maintained) and nothing is deleted. default_capacity stays NULL
 * because no real capacities are known; the manager sets them. Safe to re-run in production.
 */
class MovementReferenceDataSeeder extends Seeder
{
    /** @var array<int, array{code: string, name_ar: string, name_en: string, size_class: string, sort_order: int}> */
    public const TYPES = [
        ['code' => 'bus', 'name_ar' => 'حافلة', 'name_en' => 'Bus', 'size_class' => VehicleType::SIZE_LARGE, 'sort_order' => 10],
        ['code' => 'minibus', 'name_ar' => 'حافلة صغيرة', 'name_en' => 'Minibus', 'size_class' => VehicleType::SIZE_MEDIUM, 'sort_order' => 20],
        ['code' => 'van', 'name_ar' => 'فان', 'name_en' => 'Van', 'size_class' => VehicleType::SIZE_MEDIUM, 'sort_order' => 30],
        ['code' => 'car', 'name_ar' => 'سيارة', 'name_en' => 'Car', 'size_class' => VehicleType::SIZE_SMALL, 'sort_order' => 40],
    ];

    public function run(): void
    {
        foreach (self::TYPES as $type) {
            VehicleType::withTrashed()->firstOrCreate(
                ['code' => $type['code']],
                $type + ['default_capacity' => null, 'is_active' => true]
            );
        }
    }
}
