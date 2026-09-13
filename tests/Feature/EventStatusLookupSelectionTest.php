<?php

namespace Tests\Feature;

use App\Models\EventStatusLookup;
use App\Modules\Events\Models\EventContexts;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventStatusLookupSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_selection_scope_returns_active_options_in_existing_order(): void
    {
        EventStatusLookup::query()->create([
            'module' => EventContexts::AGENDA,
            'code' => 'second',
            'name' => 'B',
            'is_active' => true,
            'sort_order' => 20,
        ]);
        EventStatusLookup::query()->create([
            'module' => EventContexts::AGENDA,
            'code' => 'first',
            'name' => 'A',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        EventStatusLookup::query()->create([
            'module' => EventContexts::AGENDA,
            'code' => 'inactive',
            'name' => 'Inactive',
            'is_active' => false,
            'sort_order' => 5,
        ]);
        EventStatusLookup::query()->create([
            'module' => EventContexts::MONTHLY_ACTIVITIES,
            'code' => 'monthly_only',
            'name' => 'Monthly',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $codes = EventStatusLookup::query()
            ->forModule(EventContexts::AGENDA)
            ->availableForSelection()
            ->ordered()
            ->pluck('code')
            ->all();

        $this->assertSame(['first', 'second'], $codes);
    }

    public function test_selection_scope_keeps_the_current_inactive_option(): void
    {
        EventStatusLookup::query()->create([
            'module' => EventContexts::MONTHLY_ACTIVITIES,
            'code' => 'draft',
            'name' => 'Draft',
            'is_active' => true,
            'sort_order' => 10,
        ]);
        EventStatusLookup::query()->create([
            'module' => EventContexts::MONTHLY_ACTIVITIES,
            'code' => 'legacy',
            'name' => 'Legacy',
            'is_active' => false,
            'sort_order' => 20,
        ]);
        EventStatusLookup::query()->create([
            'module' => EventContexts::MONTHLY_ACTIVITIES,
            'code' => 'other_inactive',
            'name' => 'Other',
            'is_active' => false,
            'sort_order' => 30,
        ]);

        $codes = EventStatusLookup::query()
            ->forModule(EventContexts::MONTHLY_ACTIVITIES)
            ->availableForSelection('legacy')
            ->ordered()
            ->pluck('code')
            ->all();

        $this->assertSame(['draft', 'legacy'], $codes);
    }
}
