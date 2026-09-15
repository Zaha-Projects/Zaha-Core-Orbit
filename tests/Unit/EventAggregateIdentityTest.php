<?php

namespace Tests\Unit;

use App\Models\AgendaEvent;
use App\Modules\Events\Support\EventAggregateIdentity;
use PHPUnit\Framework\TestCase;

class EventAggregateIdentityTest extends TestCase
{
    public function test_agenda_has_an_exact_legacy_and_canonical_identity_pair(): void
    {
        $this->assertSame(AgendaEvent::class, EventAggregateIdentity::AGENDA_LEGACY);
        $this->assertSame('App\\Modules\\Events\\Models\\AgendaEvent', EventAggregateIdentity::AGENDA_CANONICAL);
        $this->assertSame(
            [EventAggregateIdentity::AGENDA_LEGACY, EventAggregateIdentity::AGENDA_CANONICAL],
            EventAggregateIdentity::acceptedTypes(AgendaEvent::class)
        );
        $this->assertSame(AgendaEvent::class, EventAggregateIdentity::legacyFor(EventAggregateIdentity::AGENDA_CANONICAL));
        $this->assertSame(EventAggregateIdentity::AGENDA_CANONICAL, EventAggregateIdentity::canonicalFor(AgendaEvent::class));
        $this->assertSame(AgendaEvent::class, EventAggregateIdentity::installedModelFor(EventAggregateIdentity::AGENDA_CANONICAL));
        $this->assertSame(AgendaEvent::class, EventAggregateIdentity::currentWriteType(EventAggregateIdentity::AGENDA_CANONICAL));
        $this->assertTrue(EventAggregateIdentity::isCompatibleIdentity(EventAggregateIdentity::AGENDA_CANONICAL));
    }

    public function test_unknown_identity_retains_existing_identity_behavior(): void
    {
        $unknown = 'App\\Models\\UnrelatedAggregate';

        $this->assertSame([$unknown], EventAggregateIdentity::acceptedTypes($unknown));
        $this->assertNull(EventAggregateIdentity::legacyFor($unknown));
        $this->assertNull(EventAggregateIdentity::canonicalFor($unknown));
        $this->assertNull(EventAggregateIdentity::installedModelFor($unknown));
        $this->assertSame($unknown, EventAggregateIdentity::currentWriteType($unknown));
        $this->assertFalse(EventAggregateIdentity::isCompatibleIdentity($unknown));
    }
}
