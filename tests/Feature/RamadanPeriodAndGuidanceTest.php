<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Support\RamadanPeriod;
use App\Modules\Events\Models\RamadanPeriod as Period;
use Database\Seeders\RamadanIftarGuidanceSeeder;
use Database\Seeders\RamadanPeriodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RamadanPeriodAndGuidanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_seed_is_idempotent_and_does_not_overwrite_admin_dates(): void
    {
        $this->seed(RamadanPeriodSeeder::class);
        Period::query()->where('year', 2026)->update(['start_date' => '2026-02-20']);
        $this->seed(RamadanPeriodSeeder::class);

        $this->assertSame('2026-02-20', Period::query()->where('year', 2026)->firstOrFail()->start_date->toDateString());
        $this->assertDatabaseCount('ramadan_periods', 1);
        $this->assertSame(4, Setting::query()->whereIn('key', [RamadanPeriod::YEAR_KEY, RamadanPeriod::START_KEY, RamadanPeriod::END_KEY, RamadanPeriod::ACTIVE_KEY])->count());
        $this->assertTrue(RamadanPeriod::contains('2026-02-20'));
        $this->assertFalse(RamadanPeriod::contains('2026-01-01'));
    }

    public function test_guidance_seed_is_idempotent_and_updates_the_display_title_without_changing_sections(): void
    {
        $this->seed(RamadanIftarGuidanceSeeder::class);
        $this->seed(RamadanIftarGuidanceSeeder::class);

        $this->assertSame(1, EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->count());
        $guidance = EventGuidanceVersion::currentForRamadan();
        $this->assertSame('تعليمات عامة لإفطارات رمضان', $guidance?->title);
        $sections = json_decode((string) $guidance?->content, true);
        $this->assertCount(10, $sections);
        $this->assertSame('الجهه المنفذه', $sections[0]['title']);
        $this->assertSame('اعتبارات عامه', $sections[9]['title']);
    }

    public function test_inactive_period_rejects_all_dates(): void
    {
        $this->seed(RamadanPeriodSeeder::class);
        Period::query()->where('year', 2026)->update(['is_active' => false]);

        $this->assertNull(RamadanPeriod::active());
        $this->assertFalse(RamadanPeriod::contains('2026-02-20'));
    }
}
