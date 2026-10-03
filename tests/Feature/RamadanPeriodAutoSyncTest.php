<?php

namespace Tests\Feature;

use App\Modules\Events\Models\RamadanIftarGift;
use App\Modules\Events\Models\RamadanPeriod;
use App\Modules\Events\Services\RamadanPeriodCalculator;
use App\Modules\Events\Services\RamadanPeriodSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class RamadanPeriodAutoSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_native_calculator_returns_a_hijri_year_and_proposed_dates(): void
    {
        $proposal = app(RamadanPeriodCalculator::class)->calculate(2027);
        $this->assertIsInt($proposal['hijri_year']);
        $this->assertSame('2027', substr($proposal['suggested_start_date'], 0, 4));
        $this->assertNotEmpty($proposal['suggested_end_date']);
        $this->assertSame(RamadanPeriodCalculator::SOURCE, $proposal['calculation_source']);
    }

    public function test_sync_creates_inactive_unconfirmed_proposal_and_resync_preserves_operational_dates(): void
    {
        $service = app(RamadanPeriodSyncService::class);
        $period = $service->sync(2027);
        $this->assertFalse($period->is_active);
        $this->assertFalse($period->is_confirmed);
        $period->update(['start_date'=>'2027-02-09','end_date'=>'2027-03-10','is_confirmed'=>true]);
        $service->sync(2027);
        $this->assertDatabaseHas('ramadan_periods',['year'=>2027,'start_date'=>'2027-02-09','end_date'=>'2027-03-10','is_confirmed'=>true]);
        $this->assertDatabaseCount('ramadan_periods',1);
    }

    public function test_resync_refreshes_proposal_fields_only(): void
    {
        $calculator = \Mockery::mock(RamadanPeriodCalculator::class);
        $calculator->shouldReceive('calculate')->twice()->andReturn(
            ['year'=>2027,'hijri_year'=>1448,'suggested_start_date'=>'2027-02-08','suggested_end_date'=>'2027-03-08','calculation_source'=>'intl_umm_al_qura'],
            ['year'=>2027,'hijri_year'=>1448,'suggested_start_date'=>'2027-02-09','suggested_end_date'=>'2027-03-09','calculation_source'=>'intl_umm_al_qura']
        );
        $service = new RamadanPeriodSyncService($calculator);
        $period = $service->sync(2027);
        $period->update(['start_date'=>'2027-02-10','end_date'=>'2027-03-11','is_confirmed'=>true]);
        $service->sync(2027);
        $period->refresh();
        $this->assertSame('2027-02-09',$period->suggested_start_date->toDateString());
        $this->assertSame('2027-02-10',$period->start_date->toDateString());
        $this->assertTrue($period->is_confirmed);
    }

    public function test_suggested_dates_require_review_before_activation(): void
    {
        $period = app(RamadanPeriodSyncService::class)->sync(2027);
        $period->useSuggestedDates();
        $this->assertSame($period->fresh()->suggested_start_date->toDateString(), $period->fresh()->start_date->toDateString());
        try { $period->fresh()->activate(); $this->fail('Unconfirmed period activated.'); }
        catch (LogicException $exception) { $this->assertStringContainsString('Confirm', $exception->getMessage()); }
        $period->fresh()->confirm(); $period->fresh()->activate();
        $this->assertTrue($period->fresh()->is_active);
    }

    public function test_manual_date_adjustments_are_not_artificially_limited(): void
    {
        $period = app(RamadanPeriodSyncService::class)->sync(2027);
        $period->update(['start_date'=>'2027-02-12','end_date'=>'2027-03-13']);
        $this->assertSame('2027-02-12',$period->fresh()->start_date->toDateString());
    }

    public function test_command_uses_the_proposal_service_without_activation(): void
    {
        $this->artisan('ramadan:sync-period',['year'=>2028])->assertSuccessful();
        $this->assertDatabaseHas('ramadan_periods',['year'=>2028,'is_active'=>false,'is_confirmed'=>false,'calculation_source'=>'intl_umm_al_qura']);
    }

    public function test_gift_types_are_a_three_value_structural_enum(): void
    {
        $this->assertSame([RamadanIftarGift::TYPE_GIFTS,RamadanIftarGift::TYPE_SHIELDS,RamadanIftarGift::TYPE_BOTH],RamadanIftarGift::types());
        $this->assertFalse(class_exists('App\\Modules\\Events\\Models\\RamadanIftarGiftType'));
        $this->assertFalse(class_exists('App\\Modules\\Events\\Http\\Controllers\\Admin\\RamadanGiftTypeController'));
        $this->assertFalse(\Schema::hasTable('ramadan_iftar_gift_types'));
    }
}
