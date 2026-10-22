<?php

namespace Tests\Unit;

use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Support\EventAggregateIdentity;
use PHPUnit\Framework\TestCase;

class MonthlyActivityAggregateIdentityTest extends TestCase
{
    public function test_monthly_activity_is_cut_over_with_legacy_read_compatibility(): void
    {
        $legacy = EventAggregateIdentity::MONTHLY_ACTIVITY_LEGACY;
        $canonical = EventAggregateIdentity::MONTHLY_ACTIVITY_CANONICAL;

        $this->assertSame('App\\Models\\MonthlyActivity', $legacy);
        $this->assertSame('App\\Modules\\Events\\Models\\MonthlyActivity', $canonical);
        $this->assertSame([$legacy, $canonical], EventAggregateIdentity::acceptedTypes($legacy));
        $this->assertSame([$legacy, $canonical], EventAggregateIdentity::acceptedTypes($canonical));
        $this->assertSame(MonthlyActivity::class, EventAggregateIdentity::installedModelFor($legacy));
        $this->assertSame(MonthlyActivity::class, EventAggregateIdentity::installedModelFor($canonical));
        $this->assertSame(MonthlyActivity::class, EventAggregateIdentity::currentWriteType($canonical));
        $this->assertFalse(class_exists($legacy));
        $this->assertTrue(class_exists($canonical));
    }
}
