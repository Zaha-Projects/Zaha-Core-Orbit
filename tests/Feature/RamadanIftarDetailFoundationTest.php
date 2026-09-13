<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\TargetGroup;
use App\Models\User;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarAttendee;
use App\Modules\Events\Models\RamadanIftarGift;
use App\Modules\Events\Models\RamadanIftarMeal;
use App\Modules\Events\Models\RamadanIftarMealItem;
use App\Modules\Events\Models\RamadanIftarProgramSegment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RamadanIftarDetailFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_attendees_allow_shared_phones_nullable_classification_and_default_to_not_attended(): void
    {
        [$iftar] = $this->iftarFixture();

        $first = RamadanIftarAttendee::query()->create([
            'ramadan_iftar_id' => $iftar->id,
            'full_name' => 'First family member',
            'phone' => '0790000000',
        ]);
        $second = RamadanIftarAttendee::query()->create([
            'ramadan_iftar_id' => $iftar->id,
            'full_name' => 'Second family member',
            'phone' => '0790000000',
        ]);

        $this->assertFalse($first->refresh()->attended);
        $this->assertNull($first->age);
        $this->assertNull($first->target_group_id);
        $this->assertNull($first->beneficiary_segment_id);
        $this->assertTrue($first->ramadanIftar->is($iftar));
        $this->assertSame([$first->id, $second->id], $iftar->attendees()->orderBy('id')->pluck('id')->all());
    }

    public function test_attendee_can_reference_existing_targeting_lookups(): void
    {
        [$iftar] = $this->iftarFixture();
        $targetGroup = TargetGroup::query()->create(['name' => 'Families']);
        $segment = BeneficiarySegment::query()->create([
            'code' => 'children',
            'name_ar' => 'أطفال',
            'name_en' => 'Children',
            'dimension' => BeneficiarySegment::DIMENSION_AGE,
        ]);
        $attendee = RamadanIftarAttendee::query()->create([
            'ramadan_iftar_id' => $iftar->id,
            'full_name' => 'Classified attendee',
            'target_group_id' => $targetGroup->id,
            'beneficiary_segment_id' => $segment->id,
        ]);

        $this->assertTrue($attendee->targetGroup->is($targetGroup));
        $this->assertTrue($attendee->beneficiarySegment->is($segment));
    }

    public function test_meals_and_items_preserve_planned_actual_and_ordered_detail_values(): void
    {
        [$iftar] = $this->iftarFixture();
        $meal = RamadanIftarMeal::query()->create([
            'ramadan_iftar_id' => $iftar->id,
            'description' => 'Iftar meal',
            'planned_quantity' => 100,
            'estimated_value' => '1250.50',
        ]);
        $later = RamadanIftarMealItem::query()->create([
            'ramadan_iftar_meal_id' => $meal->id,
            'name' => 'Rice',
            'item_type' => RamadanIftarMealItem::TYPE_SIDE,
            'sort_order' => 20,
        ]);
        $earlier = RamadanIftarMealItem::query()->create([
            'ramadan_iftar_meal_id' => $meal->id,
            'name' => 'Rice',
            'item_type' => RamadanIftarMealItem::TYPE_MAIN,
            'sort_order' => 10,
        ]);

        $this->assertSame(100, $meal->planned_quantity);
        $this->assertNull($meal->actual_quantity);
        $this->assertSame('1250.50', $meal->estimated_value);
        $this->assertNull($meal->rating);
        $this->assertTrue($meal->ramadanIftar->is($iftar));
        $this->assertTrue($earlier->meal->is($meal));
        $this->assertSame([$earlier->id, $later->id], $meal->items()->pluck('id')->all());
    }

    public function test_gifts_and_program_segments_persist_separate_actual_and_executor_data(): void
    {
        [$iftar, $user] = $this->iftarFixture();
        $gift = RamadanIftarGift::query()->create([
            'ramadan_iftar_id' => $iftar->id,
            'description' => 'Children gifts',
            'planned_quantity' => 40,
            'actual_quantity' => 35,
            'has_supporting_entity' => true,
            'supporting_entity_name' => 'Supporting entity',
            'unit_value' => '3.25',
            'estimated_total_value' => '130.00',
        ]);
        $later = RamadanIftarProgramSegment::query()->create([
            'ramadan_iftar_id' => $iftar->id,
            'name' => 'External segment',
            'external_executor_name' => 'External facilitator',
            'execution_status' => RamadanIftarProgramSegment::STATUS_CANCELLED,
            'sort_order' => 20,
        ]);
        $earlier = RamadanIftarProgramSegment::query()->create([
            'ramadan_iftar_id' => $iftar->id,
            'name' => 'Internal segment',
            'executor_user_id' => $user->id,
            'execution_status' => RamadanIftarProgramSegment::STATUS_COMPLETED,
            'sort_order' => 10,
        ]);

        $this->assertSame(40, $gift->planned_quantity);
        $this->assertSame(35, $gift->actual_quantity);
        $this->assertSame('3.25', $gift->unit_value);
        $this->assertSame('130.00', $gift->estimated_total_value);
        $this->assertTrue($gift->ramadanIftar->is($iftar));
        $this->assertTrue($earlier->executor->is($user));
        $this->assertSame('External facilitator', $later->external_executor_name);
        $this->assertSame([$earlier->id, $later->id], $iftar->programSegments()->pluck('id')->all());
    }

    public function test_aggregate_relationships_are_isolated_between_iftars(): void
    {
        [$first] = $this->iftarFixture();
        [$second] = $this->iftarFixture();

        foreach ([$first, $second] as $index => $iftar) {
            RamadanIftarAttendee::query()->create(['ramadan_iftar_id' => $iftar->id, 'full_name' => "Attendee {$index}"]);
            RamadanIftarMeal::query()->create(['ramadan_iftar_id' => $iftar->id, 'description' => "Meal {$index}", 'planned_quantity' => 1]);
            RamadanIftarGift::query()->create(['ramadan_iftar_id' => $iftar->id, 'description' => "Gift {$index}", 'planned_quantity' => 1]);
            RamadanIftarProgramSegment::query()->create(['ramadan_iftar_id' => $iftar->id, 'name' => "Segment {$index}"]);
        }

        $this->assertSame(['Attendee 0'], $first->attendees()->pluck('full_name')->all());
        $this->assertSame(['Meal 0'], $first->meals()->pluck('description')->all());
        $this->assertSame(['Gift 0'], $first->gifts()->pluck('description')->all());
        $this->assertSame(['Segment 0'], $first->programSegments()->pluck('name')->all());
    }

    public function test_soft_delete_keeps_details_but_force_delete_cascades_them(): void
    {
        [$iftar] = $this->iftarFixture();
        $attendee = RamadanIftarAttendee::query()->create([
            'ramadan_iftar_id' => $iftar->id,
            'full_name' => 'Stored attendee',
        ]);

        $iftar->delete();
        $this->assertDatabaseHas('ramadan_iftar_attendees', ['id' => $attendee->id]);

        $iftar->forceDelete();
        $this->assertDatabaseMissing('ramadan_iftar_attendees', ['id' => $attendee->id]);
    }

    private function iftarFixture(): array
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $iftar = RamadanIftar::query()->create([
            'branch_id' => $branch->id,
            'title' => 'Ramadan Iftar',
            'relations_officer_id' => $user->id,
            'created_by' => $user->id,
            'planned_date' => '2027-03-01',
            'location_type' => RamadanIftar::LOCATION_OUTSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_LOCAL_COMMUNITY,
            'planned_meals_count' => 0,
            'expected_attendance' => 0,
        ]);

        return [$iftar, $user];
    }
}
