<?php

namespace Tests\Feature;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\TargetGroup;
use App\Models\User;
use App\Modules\Events\Models\CommunityOrganization;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\LocalCommunity;
use App\Modules\Events\Models\MobilizationMethod;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\SubjectTargetGroup;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RamadanIftarCoreAggregateTest extends TestCase
{
    use RefreshDatabase;

    public function test_iftar_persists_core_fields_and_direct_relationships(): void
    {
        $branch = Branch::factory()->create();
        $relationsOfficer = User::factory()->create(['branch_id' => $branch->id]);
        $creator = User::factory()->create(['branch_id' => $branch->id]);
        $agendaEvent = AgendaEvent::query()->create([
            'month' => 3,
            'day' => 1,
            'event_name' => 'Ramadan Iftar Agenda Event',
            'created_by' => $creator->id,
        ]);
        $organization = CommunityOrganization::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Host Association',
        ]);
        $community = LocalCommunity::query()->create([
            'branch_id' => $branch->id,
            'name' => 'Local Community',
        ]);
        $mobilizationMethod = MobilizationMethod::query()->create([
            'code' => 'other',
            'name_ar' => 'أخرى',
            'name_en' => 'Other',
            'is_other' => true,
        ]);

        $iftar = $this->createIftar($branch, $relationsOfficer, $creator, [
            'agenda_event_id' => $agendaEvent->id,
            'community_organization_id' => $organization->id,
            'local_community_id' => $community->id,
            'mobilization_method_id' => $mobilizationMethod->id,
            'mobilization_method_other' => 'Community invitations',
            'planned_meals_count' => 100,
            'expected_attendance' => 90,
        ])->refresh();

        $this->assertSame('2027-03-01', $iftar->planned_date->toDateString());
        $this->assertSame(100, $iftar->planned_meals_count);
        $this->assertSame(90, $iftar->expected_attendance);
        $this->assertNull($iftar->actual_date);
        $this->assertNull($iftar->actual_meals_count);
        $this->assertNull($iftar->actual_attendance);
        $this->assertTrue($iftar->agendaEvent->is($agendaEvent));
        $this->assertTrue($iftar->branch->is($branch));
        $this->assertTrue($iftar->relationsOfficer->is($relationsOfficer));
        $this->assertTrue($iftar->creator->is($creator));
        $this->assertTrue($iftar->communityOrganization->is($organization));
        $this->assertTrue($iftar->localCommunity->is($community));
        $this->assertTrue($iftar->mobilizationMethod->is($mobilizationMethod));
    }

    public function test_branch_is_required(): void
    {
        $user = User::factory()->create();

        $this->expectException(QueryException::class);
        RamadanIftar::query()->create([
            'title' => 'Missing branch',
            'relations_officer_id' => $user->id,
            'created_by' => $user->id,
            'planned_date' => '2027-03-01',
            'location_type' => RamadanIftar::LOCATION_OUTSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_LOCAL_COMMUNITY,
            'planned_meals_count' => 0,
            'expected_attendance' => 0,
        ]);
    }

    public function test_versioning_allows_multiple_versions_for_the_same_agenda_event(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $agendaEvent = AgendaEvent::query()->create([
            'month' => 3,
            'day' => 2,
            'event_name' => 'Versioned Iftar',
            'created_by' => $user->id,
        ]);
        $parent = $this->createIftar($branch, $user, $user, ['agenda_event_id' => $agendaEvent->id]);
        $version = $this->createIftar($branch, $user, $user, [
            'agenda_event_id' => $agendaEvent->id,
            'parent_version_id' => $parent->id,
            'version_number' => 2,
        ]);

        $this->assertSame(1, $parent->version_number);
        $this->assertSame(2, $version->version_number);
        $this->assertTrue($version->parentVersion->is($parent));
        $this->assertTrue($parent->versions->contains($version));
    }

    public function test_target_group_relationship_is_explicitly_scoped_to_ramadan(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $iftar = $this->createIftar($branch, $user, $user);
        $targetGroup = TargetGroup::query()->create([
            'name' => 'Families',
            'is_active' => true,
            'is_other' => false,
            'sort_order' => 1,
        ]);
        $ramadanRow = SubjectTargetGroup::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'target_group_id' => $targetGroup->id,
            'planned_count' => 10,
        ]);
        SubjectTargetGroup::query()->create([
            'subject_type' => EventSubjectTypes::MONTHLY_ACTIVITY,
            'subject_id' => $iftar->id,
            'target_group_id' => $targetGroup->id,
            'planned_count' => 20,
        ]);

        $this->assertSame([$ramadanRow->id], $iftar->targetGroupSelections()->pluck('id')->all());
    }

    public function test_soft_delete_and_monthly_activity_independence(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $iftar = $this->createIftar($branch, $user, $user);

        $this->assertFalse(Schema::hasColumn('ramadan_iftars', 'monthly_activity_id'));

        $iftar->delete();

        $this->assertNull(RamadanIftar::query()->find($iftar->id));
        $this->assertNotNull(RamadanIftar::withTrashed()->find($iftar->id));
    }

    private function createIftar(Branch $branch, User $relationsOfficer, User $creator, array $attributes = []): RamadanIftar
    {
        return RamadanIftar::query()->create(array_merge([
            'branch_id' => $branch->id,
            'title' => 'Ramadan Iftar',
            'relations_officer_id' => $relationsOfficer->id,
            'created_by' => $creator->id,
            'planned_date' => '2027-03-01',
            'location_type' => RamadanIftar::LOCATION_OUTSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_LOCAL_COMMUNITY,
            'planned_meals_count' => 0,
            'expected_attendance' => 0,
        ], $attributes));
    }
}
