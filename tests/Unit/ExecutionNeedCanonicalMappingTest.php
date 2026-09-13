<?php

namespace Tests\Unit;

use App\Models\ExecutionNeedType;
use App\Models\MonthlyActivity;
use PHPUnit\Framework\TestCase;

class ExecutionNeedCanonicalMappingTest extends TestCase
{
    public function test_every_known_legacy_code_has_an_explicit_mapping(): void
    {
        $knownCodes = [
            'volunteers', 'official_correspondence', 'media_coverage', 'supplies',
            'official_sponsorship', 'external_partners', 'ceremony_agenda', 'ceremony',
            'transport', 'maintenance_workers', 'maintenance', 'gifts_shields', 'gifts',
            'programs_participation', 'programs', 'certificates', 'thanks_letters',
            'certificates_thanks', 'invitations',
        ];

        foreach ($knownCodes as $code) {
            $mapping = ExecutionNeedType::mappingForLegacy($code);
            $this->assertNotNull($mapping, $code.' must have an explicit compatibility outcome.');
            foreach ($mapping['canonical'] as $canonicalCode) {
                $this->assertContains($canonicalCode, ExecutionNeedType::canonicalCodes());
            }
        }

        $this->assertNull(ExecutionNeedType::mappingForLegacy('translated-or-guessed-label'));
        $this->assertSame(
            ['classification' => ExecutionNeedType::MAPPING_SPLIT, 'canonical' => ['certificates', 'thanks_letters']],
            ExecutionNeedType::mappingForLegacy('certificates_thanks')
        );
    }

    public function test_monthly_activity_keeps_its_legacy_definition_api(): void
    {
        $this->assertSame(
            array_keys(MonthlyActivity::EXECUTION_NEED_DEFINITIONS),
            array_keys(MonthlyActivity::executionNeedDefinitions())
        );
        $this->assertFalse(method_exists(MonthlyActivity::class, 'executionNeeds'));
    }
}
