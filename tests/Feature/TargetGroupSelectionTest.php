<?php

namespace Tests\Feature;

use App\Models\MonthlyActivity;
use App\Models\TargetGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TargetGroupSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_preserves_monthly_activity_selection_order(): void
    {
        $this->targetGroup('Second', true, 20);
        $this->targetGroup('Inactive', false, 5);
        $this->targetGroup('First', true, 10);

        $groups = TargetGroup::query()->active()->orderBy('sort_order')->get();

        $this->assertSame(['First', 'Second'], $groups->pluck('name')->all());
        $this->assertEqualsCanonicalizing(
            [
                'id',
                'name',
                'is_other',
                'is_active',
                'is_monthly_activity',
                'is_ramadan_iftar',
                'sort_order',
                'created_at',
                'updated_at',
            ],
            array_keys($groups->first()->getAttributes())
        );
    }

    public function test_active_scope_preserves_agenda_selection_order(): void
    {
        $this->targetGroup('Zulu', true, 10);
        $this->targetGroup('Hidden', false, 5);
        $this->targetGroup('Alpha', true, 20);

        $groups = TargetGroup::query()->active()->orderBy('name')->get();

        $this->assertSame(['Alpha', 'Zulu'], $groups->pluck('name')->all());
    }

    public function test_existing_monthly_activity_pivot_relationship_is_unchanged(): void
    {
        $activity = MonthlyActivity::factory()->create();
        $active = $this->targetGroup('Active', true, 10);
        $inactive = $this->targetGroup('Existing inactive', false, 20);

        $activity->targetGroups()->attach([
            $active->id => ['custom_text' => null],
            $inactive->id => ['custom_text' => 'Existing value'],
        ]);

        $this->assertEqualsCanonicalizing(
            [$active->id, $inactive->id],
            $activity->targetGroups()->pluck('target_groups.id')->all()
        );
        $this->assertSame(
            [$active->id],
            TargetGroup::query()->active()->pluck('id')->all()
        );
        $this->assertSame(
            'Existing value',
            $activity->targetGroups()->whereKey($inactive->id)->firstOrFail()->pivot->custom_text
        );
    }

    private function targetGroup(string $name, bool $active, int $sortOrder): TargetGroup
    {
        return TargetGroup::query()->create([
            'name' => $name,
            'is_other' => false,
            'is_active' => $active,
            'sort_order' => $sortOrder,
        ]);
    }
}
