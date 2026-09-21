<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Events\Models\EventGuidanceAcknowledgement;
use App\Modules\Events\Models\EventGuidanceVersion;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\RamadanPeriod;
use Database\Seeders\MobilizationMethodSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RamadanAdminConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_period_activation_preserves_history_and_deactivates_previous_period(): void
    {
        $old = RamadanPeriod::query()->create(['year'=>2025,'hijri_year'=>1446,'start_date'=>'2025-03-01','end_date'=>'2025-03-30','is_confirmed'=>true,'is_active'=>true]);
        $current = RamadanPeriod::query()->create(['year'=>2026,'hijri_year'=>1447,'start_date'=>'2026-02-18','end_date'=>'2026-03-19','is_confirmed'=>true,'is_active'=>false]);
        $current->activate();

        $this->assertFalse($old->fresh()->is_active);
        $this->assertTrue($current->fresh()->is_active);
        $this->assertSame($current->id, RamadanPeriod::current()->id);
        $this->assertDatabaseCount('ramadan_periods', 2);
    }

    public function test_guidance_publish_keeps_acknowledgements_and_selects_one_current_version(): void
    {
        $user = User::factory()->create();
        $old = EventGuidanceVersion::query()->create(['code'=>EventGuidanceVersion::RAMADAN_IFTAR,'version_number'=>1,'title'=>'Old','content'=>'Old','is_active'=>true,'published_at'=>now()]);
        EventGuidanceAcknowledgement::query()->create(['user_id'=>$user->id,'event_guidance_version_id'=>$old->id,'acknowledged_at'=>now()]);
        $draft = EventGuidanceVersion::query()->create(['code'=>EventGuidanceVersion::RAMADAN_IFTAR,'version_number'=>2,'title'=>'New','content'=>'New','is_active'=>false]);
        Role::findOrCreate('super_admin');
        $admin = User::factory()->create(); $admin->assignRole('super_admin');

        $this->actingAs($admin)->patch(route('events.ramadan.admin.guidance.publish',$draft))->assertRedirect();
        $this->assertFalse($old->fresh()->is_active);
        $this->assertSame($draft->id, EventGuidanceVersion::currentForRamadan()->id);
        $this->assertDatabaseHas('event_guidance_acknowledgements',['user_id'=>$user->id,'event_guidance_version_id'=>$old->id]);
        $this->assertDatabaseMissing('event_guidance_acknowledgements',['user_id'=>$user->id,'event_guidance_version_id'=>$draft->id]);
    }

    public function test_mobilization_seed_is_repeatable_and_preserves_admin_changes(): void
    {
        $this->seed(MobilizationMethodSeeder::class);
        MobilizationMethod::query()->where('code','direct_contact')->update(['name_ar'=>'تسمية الإدارة','is_active'=>false]);
        $this->seed(MobilizationMethodSeeder::class);
        $this->assertDatabaseHas('mobilization_methods',['code'=>'direct_contact','name_ar'=>'تسمية الإدارة','is_active'=>false]);
    }

    public function test_ramadan_admin_requires_super_admin_role(): void
    {
        $this->actingAs(User::factory()->create())->get(route('events.ramadan.admin.index'))->assertForbidden();
        Role::findOrCreate('super_admin'); $admin=User::factory()->create(); $admin->assignRole('super_admin');
        $this->actingAs($admin)->get(route('events.ramadan.admin.index'))->assertOk();
    }
}
