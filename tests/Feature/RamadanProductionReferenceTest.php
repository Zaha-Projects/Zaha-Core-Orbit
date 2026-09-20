<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Events\Http\Requests\Ramadan\StoreRamadanIftarRequest;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\RamadanIftarGift;
use App\Modules\Events\Models\RamadanIftarGiftType;
use App\Modules\Events\Models\RamadanPeriod;
use App\Modules\Events\Models\TargetGroup;
use Database\Seeders\RamadanReferenceDataSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RamadanProductionReferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_seeders_are_repeatable_and_preserve_admin_changes(): void
    {
        $this->seed(RamadanReferenceDataSeeder::class);
        $tables = ['target_groups', 'beneficiary_segments', 'monitoring_methods', 'mobilization_methods', 'execution_need_types', 'ramadan_periods', 'event_guidance_versions', 'ramadan_iftar_gift_types'];
        $counts = collect($tables)->mapWithKeys(fn ($table) => [$table => \DB::table($table)->count()]);
        MobilizationMethod::query()->where('code', 'direct_contact')->update(['name_ar' => 'تسمية الإدارة', 'is_active' => false]);
        BeneficiarySegment::query()->where('code', 'children')->update(['minimum_age' => 6, 'maximum_age' => 12]);
        TargetGroup::query()->where('code', 'children')->update(['name' => 'فئة معدلة', 'is_active' => false]);
        ExecutionNeedType::query()->where('code', 'transport')->firstOrFail()->update(['usage_scope' => 'none']);
        RamadanIftarGiftType::query()->where('code', 'gifts')->update(['name_ar' => 'هدية معدلة', 'is_active' => false]);
        RamadanPeriod::query()->where('year', 2026)->update(['start_date' => '2026-02-20']);
        $this->seed(RamadanReferenceDataSeeder::class);
        $this->seed(RamadanReferenceDataSeeder::class);

        foreach ($counts as $table => $count) {
            $this->assertDatabaseCount($table, $count);
        }
        $this->assertDatabaseHas('mobilization_methods', ['code' => 'direct_contact', 'name_ar' => 'تسمية الإدارة', 'is_active' => false]);
        $this->assertDatabaseHas('beneficiary_segments', ['code' => 'children', 'minimum_age' => 6, 'maximum_age' => 12]);
        $this->assertDatabaseHas('target_groups', ['code' => 'children', 'name' => 'فئة معدلة', 'is_active' => false]);
        $this->assertSame('none', ExecutionNeedType::query()->where('code', 'transport')->firstOrFail()->usage_scope);
        $this->assertNotContains('gifts', RamadanIftarGift::types());
        $this->assertFalse(RamadanPeriod::contains('2026-02-19'));
    }

    public function test_all_four_scopes_map_to_existing_flags_and_queries(): void
    {
        foreach (ExecutionNeedType::usageScopes() as $scope) {
            $type = ExecutionNeedType::query()->create(['code' => $scope, 'name' => $scope, 'usage_scope' => $scope]);
            $this->assertSame($scope, $type->fresh()->usage_scope);
        }
        $this->assertEqualsCanonicalizing(['monthly_plans', 'both'], ExecutionNeedType::query()->active()->forMonthlyActivities()->pluck('code')->all());
        $this->assertEqualsCanonicalizing(['iftars', 'both'], ExecutionNeedType::query()->active()->forRamadanIftars()->pluck('code')->all());
        ExecutionNeedType::query()->where('code', 'both')->update(['is_active' => false]);
        $this->assertSame(['iftars'], ExecutionNeedType::query()->active()->forRamadanIftars()->pluck('code')->all());
    }

    public function test_unknown_usage_scope_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new ExecutionNeedType(['usage_scope' => 'invalid']);
    }

    public function test_monthly_validation_rejects_an_iftar_only_or_hidden_need(): void
    {
        $type = ExecutionNeedType::query()->create(['code' => 'transport', 'name' => 'Transport', 'usage_scope' => 'iftars']);
        foreach (['iftars', 'none'] as $scope) {
            $type->update(['usage_scope' => $scope]);
            $this->assertNotContains('transport', ExecutionNeedType::monthlyAvailableCodes());
            try {
                ExecutionNeedType::validateMonthlySelection(['needs_transport' => true]);
                $this->fail('Unavailable monthly selection was accepted.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('needs_transport', $exception->errors());
            }
        }
        $type->update(['usage_scope' => 'both']);
        ExecutionNeedType::validateMonthlySelection(['needs_transport' => true]);
        $this->assertContains('transport', ExecutionNeedType::monthlyAvailableCodes());
    }

    public function test_ramadan_year_is_unique_at_database_level(): void
    {
        $period = ['year' => 2026, 'hijri_year' => 1447, 'start_date' => '2026-02-18', 'end_date' => '2026-03-19'];
        RamadanPeriod::query()->create($period);
        $this->expectException(QueryException::class);
        RamadanPeriod::query()->create($period);
    }

    public function test_period_validation_rejects_invalid_dates_and_reversed_ranges(): void
    {
        foreach ([['2026-02-30', '2026-03-19'], ['2026-03-19', '2026-02-18']] as [$start, $end]) {
            $this->assertTrue(Validator::make(['year' => 2026, 'hijri_year' => 1447, 'start_date' => $start, 'end_date' => $end], RamadanPeriod::rules())->fails());
        }
        $this->assertTrue(Validator::make(['year' => 1447, 'hijri_year' => 1447, 'start_date' => '2026-02-18', 'end_date' => '2026-03-19'], RamadanPeriod::rules())->fails());
    }

    public function test_partial_legacy_period_settings_are_not_completed_with_guessed_dates(): void
    {
        \App\Models\Setting::query()->create(['key' => 'ramadan_period_year', 'value' => '2027']);
        try {
            $this->seed(\Database\Seeders\RamadanPeriodSeeder::class);
            $this->fail('Partial settings should require administrator correction.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Incomplete legacy Ramadan period', $exception->getMessage());
        }
        $this->assertDatabaseCount('ramadan_periods', 0);
        $this->assertNull(\App\Models\Setting::valueOf('ramadan_period_start_date'));
    }

    public function test_period_validation_uses_the_single_active_year_and_inclusive_boundaries(): void
    {
        RamadanPeriod::query()->create(['year' => 2026, 'hijri_year' => 1447, 'start_date' => '2026-02-18', 'end_date' => '2026-03-19', 'is_active' => true]);
        // Test-only dates, not a seeded assertion about Ramadan in this year.
        RamadanPeriod::query()->create(['year' => 2027, 'hijri_year' => 1448, 'start_date' => '2027-02-01', 'end_date' => '2027-03-01', 'is_active' => true]);
        $this->assertFalse(RamadanPeriod::contains('2026-02-18'));
        foreach (['2027-02-01', '2027-03-01'] as $date) {
            $this->assertTrue(RamadanPeriod::contains($date));
        }
        $this->assertFalse(RamadanPeriod::contains('2026-03-20'));
        $this->assertFalse(RamadanPeriod::contains('invalid'));
        RamadanPeriod::query()->where('year', 2027)->update(['is_active' => false]);
        $this->assertFalse(RamadanPeriod::contains('2027-02-01'));
    }

    public function test_guidance_publishes_new_version_without_rewriting_old_content(): void
    {
        $old = EventGuidanceVersion::query()->create(['code' => EventGuidanceVersion::RAMADAN_IFTAR, 'version_number' => 1, 'title' => 'Old', 'content' => 'Historical accepted content', 'is_active' => true, 'published_at' => now()]);
        $this->seed(\Database\Seeders\RamadanIftarGuidanceSeeder::class);
        $this->seed(\Database\Seeders\RamadanIftarGuidanceSeeder::class);
        $this->assertSame('Historical accepted content', $old->fresh()->content);
        $this->assertTrue($old->fresh()->is_active);
        $this->assertDatabaseCount('event_guidance_versions', 2);
        $this->assertSame($old->id, EventGuidanceVersion::currentForRamadan()->id);
        $seeded = EventGuidanceVersion::query()->where('version_number', 2)->firstOrFail();
        $this->assertFalse($seeded->is_active);
        $this->assertNull($seeded->published_at);
        $this->assertStringContainsString('عدم اخراج الاثاث', $seeded->content);
        $this->assertSame(hash_file('sha256', public_path('تعليمات افطارات رمضان 2026.pdf')), $seeded->source_sha256);
    }

    public function test_gift_validation_reads_active_reference_values(): void
    {
        RamadanIftarGiftType::query()->where('code', 'gifts')->update(['is_active' => false]);
        $request = new StoreRamadanIftarRequest;
        $rules = ['gift_type' => $request->rules()['gifts.*.gift_type']];
        $this->assertTrue(Validator::make(['gift_type' => 'gifts'], $rules)->fails());
        $this->assertTrue(Validator::make(['gift_type' => 'unknown'], $rules)->fails());
        $this->assertTrue(Validator::make(['gift_type' => 'shields'], $rules)->passes());
    }

    public function test_reference_codes_are_unique(): void
    {
        MobilizationMethod::query()->create(['code' => 'unique', 'name_ar' => 'طريقة', 'name_en' => 'Method']);
        $this->expectException(QueryException::class);
        MobilizationMethod::query()->create(['code' => 'unique', 'name_ar' => 'طريقة', 'name_en' => 'Method']);
    }

    public function test_iftar_request_rejects_unavailable_references_dates_and_cross_branch_ids(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $branch = \App\Models\Branch::factory()->create();
        $admin = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $admin->assignRole('super_admin');
        $this->seed(\Database\Seeders\RamadanPeriodSeeder::class);
        $this->seed(\Database\Seeders\RamadanIftarGuidanceSeeder::class);
        \App\Modules\Events\Models\EventGuidanceAcknowledgement::query()->create([
            'user_id' => $admin->id, 'event_guidance_version_id' => EventGuidanceVersion::currentForRamadan()->id,
            'acknowledged_at' => now(),
        ]);
        $host = \App\Modules\Events\Models\CommunityOrganization::query()->create(['branch_id' => $branch->id, 'name' => 'Host']);
        $otherHost = \App\Modules\Events\Models\CommunityOrganization::query()->create(['branch_id' => \App\Models\Branch::factory()->create()->id, 'name' => 'Other branch']);
        $method = MobilizationMethod::query()->create(['code' => 'inactive', 'name_ar' => 'طريقة', 'name_en' => 'Method', 'is_active' => false]);
        $need = ExecutionNeedType::query()->create(['code' => 'hidden', 'name' => 'Hidden', 'is_canonical' => true, 'usage_scope' => 'none']);
        $payload = [
            'title' => 'Valid plan', 'planned_date' => '2026-02-20', 'relations_officer_id' => $admin->id,
            'host_type' => 'association', 'location_type' => 'outside_center', 'community_organization_id' => $host->id,
            'contact_name' => 'Test liaison', 'contact_phone' => '0790000000', 'location_name' => 'Test location',
        ];
        $url = route('events.ramadan.iftars.store');
        $this->actingAs($admin)->post($url, $payload + ['execution_needs' => [['execution_need_type_id' => $need->id, 'is_required' => true]]])
            ->assertSessionHasErrors('execution_needs.0.execution_need_type_id');
        $need->update(['usage_scope' => 'monthly_plans']);
        $this->actingAs($admin)->post($url, $payload + ['execution_needs' => [['execution_need_type_id' => $need->id, 'is_required' => true]]])
            ->assertSessionHasErrors('execution_needs.0.execution_need_type_id');
        $this->actingAs($admin)->post($url, $payload + ['mobilization_method_id' => $method->id])->assertSessionHasErrors('mobilization_method_id');
        $this->actingAs($admin)->post($url, array_replace($payload, ['planned_date' => '2026-04-01']))->assertSessionHasErrors('planned_date');
        $this->actingAs($admin)->post($url, array_replace($payload, ['community_organization_id' => $otherHost->id]))->assertSessionHasErrors('community_organization_id');
        $this->actingAs($admin)->post($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('ramadan_iftars', 1);
    }

    public function test_both_planning_forms_filter_scopes_and_hidden_types(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $branch = \App\Models\Branch::factory()->create();
        $admin = User::factory()->create(['branch_id' => $branch->id]);
        $admin->assignRole('super_admin');
        $this->seed(RamadanReferenceDataSeeder::class);
        $transport = ExecutionNeedType::query()->where('code', 'transport')->firstOrFail();
        $supplies = ExecutionNeedType::query()->where('code', 'supplies')->firstOrFail();
        $gifts = ExecutionNeedType::query()->where('code', 'gifts_shields')->firstOrFail();
        $transport->update(['usage_scope' => 'iftars']);
        $supplies->update(['usage_scope' => 'monthly_plans']);
        $gifts->update(['usage_scope' => 'none']);
        ExecutionNeedType::query()->where('code', 'certificates')->firstOrFail()->update(['usage_scope' => 'none']);

        $this->actingAs($admin)->get(route('role.relations.activities.create'))
            ->assertOk()->assertDontSee('name="needs_transport"', false)
            ->assertDontSee('name="needs_gifts"', false)->assertSee('name="requires_supplies"', false)
            ->assertSee('name="needs_volunteers"', false)
            ->assertDontSee('name="needs_certificates_details"', false)
            ->assertSee('name="needs_thanks_letters_details"', false)
            ->assertSee('class="js-team-groups-container"', false);

        ExecutionNeedType::query()->where('code', 'execution_team')->firstOrFail()->update(['usage_scope' => 'both']);
        $this->actingAs($admin)->get(route('role.relations.activities.create'))->assertOk()
            ->assertSee('class="js-team-groups-container"', false);

        \App\Modules\Events\Models\EventGuidanceAcknowledgement::query()->create([
            'user_id' => $admin->id, 'event_guidance_version_id' => EventGuidanceVersion::currentForRamadan()->id,
            'acknowledged_at' => now(),
        ]);
        $this->actingAs($admin)->get(route('events.ramadan.iftars.create'))->assertOk()
            ->assertSee('id="need-'.$transport->id.'"', false)
            ->assertDontSee('id="need-'.$supplies->id.'"', false)
            ->assertDontSee('id="need-'.$gifts->id.'"', false);
        $this->actingAs($admin)->get(route('role.super_admin.site_settings.index'))->assertOk()
            ->assertSee('name="execution_need_scopes[0][usage_scope]"', false);
    }

    public function test_admin_can_update_period_and_scopes_and_regular_user_cannot(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $type = ExecutionNeedType::query()->create(['code' => 'transport', 'name' => 'Transport', 'usage_scope' => 'both']);
        $payload = $this->settingsPayload() + ['execution_need_scopes' => [['id' => $type->id, 'usage_scope' => 'none']]];
        $url = route('role.super_admin.site_settings.update');
        $this->actingAs($admin)->put($url, $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->actingAs($admin)->put($url, $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('ramadan_periods', 1);
        $this->assertSame('none', $type->fresh()->usage_scope);
        $this->actingAs($admin)->put($url, array_replace($payload, ['ramadan_period_end_date' => '2026-01-01']))->assertSessionHasErrors('ramadan_period_end_date');
        $payload['execution_need_scopes'][0]['usage_scope'] = 'invalid';
        $this->actingAs($admin)->put($url, $payload)->assertSessionHasErrors('execution_need_scopes.0.usage_scope');
        $this->actingAs(User::factory()->create())->put($url, $this->settingsPayload())->assertForbidden();
    }

    public function test_settings_html_uses_selected_and_checked_attributes_without_raw_blade_directives(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->seed(RamadanReferenceDataSeeder::class);
        $team = ExecutionNeedType::query()->where('code', 'execution_team')->firstOrFail();
        $team->update(['usage_scope' => 'iftars']);
        RamadanPeriod::query()->where('year', 2026)->update(['is_active' => true]);

        $this->actingAs($admin)->get(route('role.super_admin.site_settings.index'))
            ->assertOk()
            ->assertSee('value="iftars" selected', false)
            ->assertSee('id="ramadan_period_is_active" name="ramadan_period_is_active" value="1" checked', false)
            ->assertDontSee('@selected', false)
            ->assertDontSee('parent->index', false);
    }

    public function test_period_only_update_preserves_scopes_and_does_not_mark_them_configured(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $type = ExecutionNeedType::query()->create(['code' => 'transport', 'name' => 'Transport', 'usage_scope' => 'iftars']);

        $this->actingAs($admin)->put(route('role.super_admin.site_settings.update'), $this->settingsPayload())
            ->assertSessionHasNoErrors();

        $this->assertSame('iftars', $type->fresh()->usage_scope);
        $this->assertNull($type->fresh()->scope_configured_at);
        $this->assertDatabaseHas('ramadan_periods', ['year' => 2026, 'is_active' => true]);
    }

    private function settingsPayload(): array
    {
        return [
            'admin_reports_cache_ttl_minutes' => 10, 'admin_reports_cache_prefix' => 'test',
            'ramadan_period_year' => 2026, 'ramadan_period_start_date' => '2026-02-18',
            'ramadan_period_end_date' => '2026-03-19', 'ramadan_period_is_active' => 1,
        ];
    }
}
