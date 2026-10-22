<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\ExecutionNeedType;
use App\Modules\Events\Models\TargetGroup;
use Database\Seeders\RamadanReferenceDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RamadanReferenceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_manage_reference_data_and_values_are_unique(): void
    {
        $this->seed(RamadanReferenceDataSeeder::class);
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $regular = User::factory()->create();
        $url = route('role.super_admin.ramadan_reference_data.index');
        $this->actingAs($regular)->get($url)->assertForbidden();
        $this->actingAs($admin)->get($url)->assertOk()->assertSee('طرق الحشد والاستقطاب');

        $store = route('role.super_admin.ramadan_reference_data.store', 'target_groups');
        $payload = ['code' => 'managed_group', 'name' => 'فئة مدارة', 'is_other' => 0, 'is_active' => 1, 'is_monthly_activity' => 1, 'is_ramadan_iftar' => 0, 'sort_order' => 50];
        $this->actingAs($admin)->post($store, $payload)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($store, $payload)->assertSessionHasErrors('code');
        $duplicateName = array_merge($payload, ['code' => 'managed_group_duplicate']);
        $this->actingAs($admin)->post($store, $duplicateName)->assertSessionHasErrors('name');
        $group = TargetGroup::query()->where('code', 'managed_group')->sole();
        $this->assertTrue($group->is_monthly_activity);
        $this->assertFalse($group->is_ramadan_iftar);
        $this->assertContains($group->id, TargetGroup::query()->active()->forMonthlyActivities()->pluck('id')->all());
        $this->assertNotContains($group->id, TargetGroup::query()->active()->forRamadanIftars()->pluck('id')->all());
    }

    public function test_execution_need_scope_and_branch_reference_uniqueness_are_managed(): void
    {
        $this->seed(RamadanReferenceDataSeeder::class);
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $need = ExecutionNeedType::query()->where('code', 'transport')->firstOrFail();
        $this->actingAs($admin)->put(route('role.super_admin.ramadan_reference_data.update', ['execution_need_types', $need->id]), [
            'code' => $need->code, 'name' => $need->name, 'description' => $need->description,
            'usage_scope' => 'none', 'mandatory_for_ramadan' => 0, 'is_active' => 1, 'sort_order' => $need->sort_order,
        ])->assertSessionHasNoErrors();
        $this->assertSame('none', $need->fresh()->usage_scope);
        $this->assertNotNull($need->fresh()->scope_configured_at);

        $branch = Branch::factory()->create();
        $route = route('role.super_admin.ramadan_reference_data.store', 'community_organizations');
        $payload = ['branch_id' => $branch->id, 'name' => 'Unique host', 'is_active' => 1];
        $this->actingAs($admin)->post($route, $payload)->assertSessionHasNoErrors();
        $this->actingAs($admin)->post($route, $payload)->assertSessionHasErrors('name');
        $this->assertSame(1, CommunityOrganization::query()->where('branch_id', $branch->id)->where('name', 'Unique host')->count());
    }
}
