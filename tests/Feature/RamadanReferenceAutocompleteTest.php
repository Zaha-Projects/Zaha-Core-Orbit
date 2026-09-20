<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\LocalCommunity;
use Database\Seeders\CommunityOrganizationSeeder;
use Database\Seeders\LocalCommunitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RamadanReferenceAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_are_idempotent_and_preserve_admin_edits(): void
    {
        $branch = Branch::factory()->create();
        $this->seed([CommunityOrganizationSeeder::class, LocalCommunitySeeder::class]);
        CommunityOrganization::first()->update(['contact_name' => 'تعديل الإدارة', 'is_active' => false]);
        $this->seed([CommunityOrganizationSeeder::class, LocalCommunitySeeder::class]);
        $this->assertSame(1, CommunityOrganization::where('branch_id', $branch->id)->count());
        $this->assertSame(1, LocalCommunity::where('branch_id', $branch->id)->count());
        $this->assertSame('تعديل الإدارة', CommunityOrganization::first()->contact_name);
        $this->assertFalse(CommunityOrganization::first()->is_active);
    }

    public function test_search_and_inline_create_are_branch_scoped_and_normalized_duplicates_are_rejected(): void
    {
        Role::findOrCreate('relations_officer', 'web');
        $branch = Branch::factory()->create();
        $other = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->assignRole('relations_officer');
        CommunityOrganization::create(['branch_id' => $branch->id, 'name' => 'جمعية النور']);
        CommunityOrganization::create(['branch_id' => $other->id, 'name' => 'جمعية النور الأخرى']);
        LocalCommunity::create(['branch_id' => $other->id, 'name' => 'مجتمع بعيد']);

        $this->actingAs($user)->getJson(route('events.ramadan.iftars.references.organizations.index', ['q' => 'النور']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'جمعية النور');
        $this->actingAs($user)->getJson(route('events.ramadan.iftars.references.local-communities.index', ['q' => 'بعيد']))
            ->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($user)->postJson(route('events.ramadan.iftars.references.local-communities.store'), ['name' => 'حي السلام'])
            ->assertCreated()->assertJsonPath('name', 'حي السلام');
        $this->assertDatabaseHas('local_communities', ['branch_id' => $branch->id, 'name' => 'حي السلام']);
        $this->actingAs($user)->postJson(route('events.ramadan.iftars.references.organizations.store'), ['name' => '  جمعية   النور  '])
            ->assertStatus(422)->assertJsonValidationErrors('name');
    }

    public function test_reference_endpoints_require_authentication(): void
    {
        $this->getJson(route('events.ramadan.iftars.references.organizations.index'))->assertUnauthorized();
        $this->postJson(route('events.ramadan.iftars.references.local-communities.store'), ['name' => 'X'])->assertUnauthorized();
    }
}
