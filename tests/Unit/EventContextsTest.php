<?php

namespace Tests\Unit;

use App\Modules\Events\Models\EventContexts;
use PHPUnit\Framework\TestCase;

class EventContextsTest extends TestCase
{
    public function test_context_values_are_exact_and_stable(): void
    {
        $this->assertSame('agenda', EventContexts::AGENDA);
        $this->assertSame('monthly_activities', EventContexts::MONTHLY_ACTIVITIES);
        $this->assertSame(['agenda', 'monthly_activities'], EventContexts::all());
    }
}
