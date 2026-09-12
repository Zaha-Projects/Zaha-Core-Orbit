<?php

namespace Tests\Feature;

use App\Models\TargetGroup;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\SubjectTargetGroup;
use Database\Seeders\BeneficiarySegmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommonEventTargetingFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_target_group_applicability_filters_are_independent_and_preserve_active_defaults(): void
    {
        $both = TargetGroup::query()->create([
            'name' => 'Both',
            'is_other' => false,
            'is_active' => true,
            'sort_order' => 0,
        ])->refresh();
        $monthlyOnly = $this->targetGroup('Monthly only', true, false);
        $ramadanOnly = $this->targetGroup('Ramadan only', false, true);
        $inactive = $this->targetGroup('Inactive', true, true, false);

        $this->assertTrue($both->is_monthly_activity);
        $this->assertTrue($both->is_ramadan_iftar);
        $this->assertEqualsCanonicalizing(
            [$both->id, $monthlyOnly->id],
            TargetGroup::query()->active()->forMonthlyActivities()->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$both->id, $ramadanOnly->id],
            TargetGroup::query()->active()->forRamadanIftars()->pluck('id')->all()
        );
        $this->assertEqualsCanonicalizing(
            [$both->id, $monthlyOnly->id, $ramadanOnly->id],
            TargetGroup::query()->active()->pluck('id')->all()
        );
        $this->assertNotContains($inactive->id, TargetGroup::query()->active()->pluck('id')->all());
    }

    public function test_beneficiary_segment_seeding_is_idempotent_and_keeps_unknown_age_bounds_null(): void
    {
        $this->seed(BeneficiarySegmentSeeder::class);
        $this->seed(BeneficiarySegmentSeeder::class);

        $segments = BeneficiarySegment::query()->active()->ordered()->get()->keyBy('code');

        $this->assertSame(
            ['age', 'gender', 'social', 'other'],
            BeneficiarySegment::dimensions()
        );
        $this->assertSame(
            ['children', 'adolescents', 'youth', 'women', 'other'],
            $segments->keys()->all()
        );
        $this->assertCount(5, $segments);
        $this->assertSame(BeneficiarySegment::DIMENSION_GENDER, $segments['women']->dimension);
        $this->assertSame(BeneficiarySegment::DIMENSION_OTHER, $segments['other']->dimension);
        $this->assertTrue($segments['other']->is_other);

        foreach (['children', 'adolescents', 'youth'] as $code) {
            $this->assertSame(BeneficiarySegment::DIMENSION_AGE, $segments[$code]->dimension);
            $this->assertNull($segments[$code]->minimum_age);
            $this->assertNull($segments[$code]->maximum_age);
        }
    }

    public function test_subject_target_group_persists_shared_data_without_a_parent_foreign_key(): void
    {
        $this->seed(BeneficiarySegmentSeeder::class);
        $targetGroup = $this->targetGroup('Other', true, true, true, true);
        $segment = BeneficiarySegment::query()->where('code', 'other')->firstOrFail();

        $row = SubjectTargetGroup::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => 999999,
            'target_group_id' => $targetGroup->id,
            'target_group_custom_text' => 'Custom target',
            'beneficiary_segment_id' => $segment->id,
            'segment_custom_text' => 'Custom segment',
            'planned_count' => 25,
            'actual_count' => null,
            'notes' => 'Planning note',
        ]);

        $this->assertSame(999999, $row->subject_id);
        $this->assertSame(25, $row->planned_count);
        $this->assertNull($row->actual_count);
        $this->assertSame('Custom target', $row->target_group_custom_text);
        $this->assertSame('Custom segment', $row->segment_custom_text);
        $this->assertTrue($row->targetGroup->is($targetGroup));
        $this->assertTrue($row->beneficiarySegment->is($segment));
        $this->assertTrue(Schema::hasColumns('subject_target_groups', [
            'subject_type',
            'subject_id',
            'target_group_id',
            'beneficiary_segment_id',
            'planned_count',
            'actual_count',
        ]));
    }

    private function targetGroup(
        string $name,
        bool $monthly = true,
        bool $ramadan = true,
        bool $active = true,
        bool $other = false
    ): TargetGroup {
        return TargetGroup::query()->create([
            'name' => $name,
            'is_other' => $other,
            'is_active' => $active,
            'is_monthly_activity' => $monthly,
            'is_ramadan_iftar' => $ramadan,
            'sort_order' => 0,
        ]);
    }
}
