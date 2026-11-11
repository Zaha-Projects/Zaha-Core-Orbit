<?php

namespace Tests\Feature;

use App\Modules\Movement\Models\VehicleType;
use Database\Seeders\MovementReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * MovementReferenceDataSeeder (plan §18.5, Revision 4): firstOrCreate, idempotent, never overwrites
 * manager edits, never deletes. Database test — disposable database only.
 */
class MovementReferenceDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeds_the_four_types_with_null_default_capacity(): void
    {
        $this->seed(MovementReferenceDataSeeder::class);

        $types = VehicleType::query()->orderBy('sort_order')->get();
        $this->assertSame(['bus', 'minibus', 'van', 'car'], $types->pluck('code')->all());
        $this->assertSame(['large', 'medium', 'medium', 'small'], $types->pluck('size_class')->all());
        $this->assertSame([null, null, null, null], $types->pluck('default_capacity')->all());
        $this->assertTrue($types->every(fn (VehicleType $t) => $t->is_active && $t->name_ar && $t->name_en));
    }

    public function test_reseeding_is_idempotent_keeps_manager_edits_and_deletes_nothing(): void
    {
        $this->seed(MovementReferenceDataSeeder::class);

        VehicleType::query()->where('code', 'bus')->update(['name_en' => 'Coach (edited)', 'default_capacity' => 45, 'size_class' => 'medium']);
        VehicleType::query()->where('code', 'van')->firstOrFail()->delete();
        VehicleType::create(['code' => 'truck', 'name_ar' => 'شاحنة', 'name_en' => 'Truck', 'size_class' => 'large', 'is_active' => true]);

        $snapshot = fn () => DB::table('movement_vehicle_types')->orderBy('id')->get()->toArray();
        $before = $snapshot();

        $this->seed(MovementReferenceDataSeeder::class);
        $this->seed(MovementReferenceDataSeeder::class);

        $this->assertEquals($before, $snapshot(), 'Re-seeding must not change, add or delete any row.');
        $this->assertSoftDeleted('movement_vehicle_types', ['code' => 'van']);
    }
}
