<?php

namespace Tests\Unit;

use App\Models\AnnualAgendaDeleteRequest;
use App\Models\AnnualAgendaEditRequest;
use App\Models\MonthlyPlanDeleteRequest;
use App\Models\MonthlyPlanEditRequest;
use App\Modules\Events\Support\EventRequestModelIdentity;
use PHPUnit\Framework\TestCase;

class EventRequestModelIdentityTest extends TestCase
{
    public function test_each_request_has_an_exact_legacy_and_canonical_identity_pair(): void
    {
        $pairs = [
            MonthlyPlanEditRequest::class => EventRequestModelIdentity::MONTHLY_EDIT_CANONICAL,
            MonthlyPlanDeleteRequest::class => EventRequestModelIdentity::MONTHLY_DELETE_CANONICAL,
            AnnualAgendaEditRequest::class => EventRequestModelIdentity::AGENDA_EDIT_CANONICAL,
            AnnualAgendaDeleteRequest::class => EventRequestModelIdentity::AGENDA_DELETE_CANONICAL,
        ];

        foreach ($pairs as $legacy => $canonical) {
            $this->assertSame([$legacy, $canonical], EventRequestModelIdentity::acceptedTypes($legacy));
            $this->assertSame([$legacy, $canonical], EventRequestModelIdentity::acceptedTypes($canonical));
            $this->assertSame($legacy, EventRequestModelIdentity::legacyFor($canonical));
            $this->assertSame($canonical, EventRequestModelIdentity::canonicalFor($legacy));
            $this->assertSame($legacy, EventRequestModelIdentity::installedModelFor($legacy));
            $this->assertSame($legacy, EventRequestModelIdentity::installedModelFor($canonical));
            $this->assertSame($legacy, EventRequestModelIdentity::currentWriteType($legacy));
            $this->assertSame($legacy, EventRequestModelIdentity::currentWriteType($canonical));
        }
    }

    public function test_unknown_identities_retain_the_existing_identity_behavior(): void
    {
        $unknown = 'App\\Models\\UnrelatedWorkflowSubject';

        $this->assertSame([$unknown], EventRequestModelIdentity::acceptedTypes($unknown));
        $this->assertNull(EventRequestModelIdentity::legacyFor($unknown));
        $this->assertNull(EventRequestModelIdentity::canonicalFor($unknown));
        $this->assertNull(EventRequestModelIdentity::installedModelFor($unknown));
        $this->assertSame($unknown, EventRequestModelIdentity::currentWriteType($unknown));
        $this->assertFalse(EventRequestModelIdentity::isCompatibleIdentity($unknown));
    }
}
