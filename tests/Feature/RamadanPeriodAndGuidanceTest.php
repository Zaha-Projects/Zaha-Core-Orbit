<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Support\RamadanPeriod;
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
        Setting::query()->where('key', RamadanPeriod::START_KEY)->update(['value' => '2026-02-20']);
        $this->seed(RamadanPeriodSeeder::class);

        $this->assertSame('2026-02-20', Setting::valueOf(RamadanPeriod::START_KEY));
        $this->assertSame(4, Setting::query()->whereIn('key', [RamadanPeriod::YEAR_KEY, RamadanPeriod::START_KEY, RamadanPeriod::END_KEY, RamadanPeriod::ACTIVE_KEY])->count());
        $this->assertTrue(RamadanPeriod::contains('2026-02-20'));
        $this->assertFalse(RamadanPeriod::contains('2026-01-01'));
    }

    public function test_guidance_seed_is_idempotent_and_preserves_the_source_title_and_sections(): void
    {
        $this->seed(RamadanIftarGuidanceSeeder::class);
        $this->seed(RamadanIftarGuidanceSeeder::class);

        $this->assertSame(1, EventGuidanceVersion::query()->where('code', EventGuidanceVersion::RAMADAN_IFTAR)->count());
        $guidance = EventGuidanceVersion::currentForRamadan();
        $this->assertSame('تعليمات عامة لإفطارات رمضان 2025', $guidance?->title);
        $sections = json_decode((string) $guidance?->content, true);
        $this->assertCount(10, $sections);
        $this->assertSame('الجهة المنفذة', $sections[0]['title']);
        $this->assertSame('اعتبارات عامة', $sections[9]['title']);
    }

    public function test_inactive_period_rejects_all_dates(): void
    {
        $this->seed(RamadanPeriodSeeder::class);
        Setting::query()->where('key', RamadanPeriod::ACTIVE_KEY)->update(['value' => '0']);

        $this->assertNull(RamadanPeriod::active());
        $this->assertFalse(RamadanPeriod::contains('2026-02-20'));
    }
}
