<?php

namespace Tests\Feature;

use App\Models\EventCategory;
use App\Models\EventStatusLookup;
use App\Models\EventType;
use App\Models\ExecutionNeedType;
use App\Models\TargetGroup;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\LocalCommunity;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\MonitoringMethod;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventReferenceDataBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_bootstrap_is_repeatable_and_populates_required_event_reference_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $firstCounts = $this->referenceCounts();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($firstCounts, $this->referenceCounts());
        $this->assertEqualsCanonicalizing(
            ExecutionNeedType::canonicalCodes(),
            ExecutionNeedType::query()->canonical()->pluck('code')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['cameras', 'field_visit'],
            MonitoringMethod::query()->pluck('code')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['children', 'adolescents', 'youth', 'women', 'other'],
            BeneficiarySegment::query()->pluck('code')->all()
        );
        $this->assertNotEmpty(TargetGroup::query()->pluck('name')->all());
        $this->assertNotEmpty(EventType::query()->pluck('name')->all());
        $this->assertNotEmpty(EventCategory::query()->pluck('name')->all());
        $this->assertNotEmpty(EventStatusLookup::query()->pluck('code')->all());
    }

    public function test_bootstrap_wiring_excludes_superseded_and_abandoned_artifacts(): void
    {
        $databaseSeeder = file_get_contents(database_path('seeders/DatabaseSeeder.php'));
        $referenceSeeder = file_get_contents(database_path('seeders/EventReferenceDataSeeder.php'));
        $seederSources = collect(glob(database_path('seeders/*.php')))
            ->map(fn (string $path): string => file_get_contents($path))
            ->implode("\n");

        $this->assertStringContainsString('EventReferenceDataSeeder::class', $databaseSeeder);
        $this->assertStringContainsString('CanonicalExecutionNeedTypeSeeder::class', $databaseSeeder);

        foreach ([
            'DepartmentSeeder',
            'EventTypeSeeder',
            'TargetGroupSeeder',
            'BeneficiarySegmentSeeder',
            'MonitoringMethodSeeder',
            'EventStatusLookupSeeder',
            'EventCategorySeeder',
        ] as $requiredSeeder) {
            $this->assertStringContainsString($requiredSeeder.'::class', $referenceSeeder);
        }

        $this->assertStringNotContainsString('ShowcaseSeeder::class', $databaseSeeder);
        $this->assertFileDoesNotExist(database_path('seeders/ExecutionNeedTypeSeeder.php'));

        foreach (['subject_target_groups', 'execution_team_members', 'subject_supplies', 'field_verifications'] as $removedTable) {
            $this->assertStringNotContainsString($removedTable, $seederSources);
        }
    }

    public function test_business_managed_and_showcase_data_is_not_created_by_default_bootstrap(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, EventGuidanceVersion::query()->count());
        $this->assertSame(0, MobilizationMethod::query()->count());
        $this->assertSame(0, CommunityOrganization::query()->count());
        $this->assertSame(0, LocalCommunity::query()->count());

        $source = file_get_contents(database_path('seeders/DatabaseSeeder.php'));
        $this->assertStringNotContainsString('ShowcaseSeeder::class', $source);
    }

    private function referenceCounts(): array
    {
        return [
            'target_groups' => TargetGroup::query()->count(),
            'beneficiary_segments' => BeneficiarySegment::query()->count(),
            'monitoring_methods' => MonitoringMethod::query()->count(),
            'event_types' => EventType::query()->count(),
            'event_categories' => EventCategory::query()->count(),
            'event_statuses' => EventStatusLookup::query()->count(),
            'execution_need_types' => ExecutionNeedType::query()->count(),
        ];
    }
}
