<?php

namespace Tests\Feature;

use App\Support\HijriDateFormatter;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ApplicationHeaderDateTest extends TestCase
{
    public function test_hijri_formatter_uses_app_timezone_without_database_state(): void
    {
        config(['app.timezone' => 'UTC']);
        $formatted = app(HijriDateFormatter::class)->format(CarbonImmutable::parse('2026-09-20 09:35:00', 'UTC'));

        $this->assertStringContainsString('هـ', $formatted);
        $this->assertStringContainsString('ربيع الآخر', $formatted);
    }

    public function test_header_contains_authoritative_gregorian_hijri_and_local_live_clock_hooks(): void
    {
        $source = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $this->assertStringContainsString('data-app-clock', $source);
        $this->assertStringContainsString('data-clock-gregorian', $source);
        $this->assertStringContainsString('data-clock-hijri', $source);
        $this->assertStringContainsString("config('app.timezone')", $source);
        $this->assertStringContainsString('window.setInterval(tick,1000)', $source);
        $this->assertStringNotContainsString('RamadanPeriod', $source);
    }
}
