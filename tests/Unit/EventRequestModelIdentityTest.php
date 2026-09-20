<?php

namespace Tests\Unit;

use App\Modules\Events\Models\AnnualAgendaDeleteRequest;
use App\Modules\Events\Models\AnnualAgendaEditRequest;
use App\Models\MonthlyPlanDeleteRequest;
use App\Models\MonthlyPlanEditRequest;
use App\Modules\Events\Support\EventRequestModelIdentity;
use PHPUnit\Framework\TestCase;

class EventRequestModelIdentityTest extends TestCase
{
    public function test_each_request_has_the_expected_installed_and_writer_identity(): void
    {
        $pairs = [
            [EventRequestModelIdentity::MONTHLY_EDIT_LEGACY, EventRequestModelIdentity::MONTHLY_EDIT_CANONICAL, MonthlyPlanEditRequest::class],
            [EventRequestModelIdentity::MONTHLY_DELETE_LEGACY, EventRequestModelIdentity::MONTHLY_DELETE_CANONICAL, MonthlyPlanDeleteRequest::class],
            [EventRequestModelIdentity::AGENDA_EDIT_LEGACY, EventRequestModelIdentity::AGENDA_EDIT_CANONICAL, AnnualAgendaEditRequest::class],
            [EventRequestModelIdentity::AGENDA_DELETE_LEGACY, EventRequestModelIdentity::AGENDA_DELETE_CANONICAL, AnnualAgendaDeleteRequest::class],
        ];

        foreach ($pairs as [$legacy, $canonical, $installed]) {
            $this->assertSame([$legacy, $canonical], EventRequestModelIdentity::acceptedTypes($legacy));
            $this->assertSame([$legacy, $canonical], EventRequestModelIdentity::acceptedTypes($canonical));
            $this->assertSame($legacy, EventRequestModelIdentity::legacyFor($canonical));
            $this->assertSame($canonical, EventRequestModelIdentity::canonicalFor($legacy));
            $this->assertSame($installed, EventRequestModelIdentity::installedModelFor($legacy));
            $this->assertSame($installed, EventRequestModelIdentity::installedModelFor($canonical));
            $this->assertSame($installed, EventRequestModelIdentity::currentWriteType($legacy));
            $this->assertSame($installed, EventRequestModelIdentity::currentWriteType($canonical));
        }
    }

    public function test_annual_agenda_legacy_classes_are_not_installed(): void
    {
        $this->assertFalse(class_exists(EventRequestModelIdentity::AGENDA_EDIT_LEGACY));
        $this->assertFalse(class_exists(EventRequestModelIdentity::AGENDA_DELETE_LEGACY));
        $this->assertTrue(class_exists(EventRequestModelIdentity::AGENDA_EDIT_CANONICAL));
        $this->assertTrue(class_exists(EventRequestModelIdentity::AGENDA_DELETE_CANONICAL));
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
