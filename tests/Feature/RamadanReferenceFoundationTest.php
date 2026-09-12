<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\LocalCommunity;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\MonitoringMethod;
use Database\Seeders\MonitoringMethodSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RamadanReferenceFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobilization_methods_filter_order_and_enforce_unique_codes(): void
    {
        MobilizationMethod::query()->create([
            'code' => 'other',
            'name_ar' => 'أخرى',
            'name_en' => 'Other',
            'is_other' => true,
            'is_active' => true,
            'sort_order' => 20,
        ]);
        MobilizationMethod::query()->create([
            'code' => 'approved_method',
            'name_ar' => 'طريقة معتمدة',
            'name_en' => 'Approved Method',
            'is_other' => false,
            'is_active' => true,
            'sort_order' => 10,
        ]);
        MobilizationMethod::query()->create([
            'code' => 'inactive',
            'name_ar' => 'غير فعالة',
            'name_en' => 'Inactive',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $methods = MobilizationMethod::query()->active()->ordered()->get();

        $this->assertSame(['approved_method', 'other'], $methods->pluck('code')->all());
        $this->assertTrue($methods->last()->is_other);

        $this->expectException(QueryException::class);
        MobilizationMethod::query()->create([
            'code' => 'other',
            'name_ar' => 'مكرر',
            'name_en' => 'Duplicate',
        ]);
    }

    public function test_monitoring_seeder_is_idempotent_and_omits_uncertain_terminology(): void
    {
        $this->seed(MonitoringMethodSeeder::class);
        $this->seed(MonitoringMethodSeeder::class);
        MonitoringMethod::query()->create([
            'code' => 'inactive_method',
            'name_ar' => 'غير فعالة',
            'name_en' => 'Inactive',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $methods = MonitoringMethod::query()->active()->ordered()->get();

        $this->assertSame(['cameras', 'field_visit'], $methods->pluck('code')->all());
        $this->assertCount(2, $methods);
        $this->assertFalse($methods->contains('code', 'mystery_shopper'));
    }

    public function test_community_organization_persists_branch_contact_and_location(): void
    {
        $branch = Branch::factory()->create();

        $organization = CommunityOrganization::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Community Association',
            'contact_name' => 'Contact Person',
            'contact_phone' => '0790000000',
            'location_name' => 'Community Hall',
            'address' => 'Amman',
            'google_maps_url' => 'https://maps.google.com/?q=31.95,35.91',
        ])->refresh();

        $this->assertTrue($organization->is_active);
        $this->assertTrue($organization->branch->is($branch));
        $this->assertSame('Contact Person', $organization->contact_name);
        $this->assertSame('0790000000', $organization->contact_phone);
        $this->assertSame('Community Hall', $organization->location_name);
        $this->assertSame('Amman', $organization->address);
        $this->assertSame('https://maps.google.com/?q=31.95,35.91', $organization->google_maps_url);
    }

    public function test_community_organization_requires_a_branch(): void
    {
        $this->expectException(QueryException::class);

        CommunityOrganization::query()->create(['name' => 'Missing branch']);
    }

    public function test_local_community_persists_branch_contact_and_location(): void
    {
        $branch = Branch::factory()->create();

        $community = LocalCommunity::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Neighborhood',
            'location_name' => 'District Center',
            'address' => 'Zarqa',
            'google_maps_url' => 'https://maps.google.com/?q=32.07,36.09',
            'contact_name' => 'Community Liaison',
            'contact_phone' => '0780000000',
        ])->refresh();

        $this->assertTrue($community->is_active);
        $this->assertTrue($community->branch->is($branch));
        $this->assertSame('District Center', $community->location_name);
        $this->assertSame('Zarqa', $community->address);
        $this->assertSame('Community Liaison', $community->contact_name);
        $this->assertSame('0780000000', $community->contact_phone);
    }

    public function test_local_community_requires_a_branch(): void
    {
        $this->expectException(QueryException::class);

        LocalCommunity::query()->create(['name' => 'Missing branch']);
    }
}
