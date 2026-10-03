<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNavigationAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_grouped_reference_and_ramadan_navigation(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin)->get(route('events.ramadan.admin.index'))->assertOk()
            ->assertSee('الإعدادات والبيانات المرجعية')
            ->assertSee('البيانات المرجعية العامة')
            ->assertSee('المؤسسات والمراكز')
            ->assertSee('المجتمعات المحلية')
            ->assertSee('طرق الحشد والاستقطاب')
            ->assertSee('ramadanAdminMenu', false);
    }

    public function test_non_admin_is_blocked_from_lookup_management(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('role.super_admin.events_lookups.index'))->assertForbidden();
        $this->actingAs($user)->get(route('role.super_admin.ramadan_reference_data.index'))->assertForbidden();
    }
}
