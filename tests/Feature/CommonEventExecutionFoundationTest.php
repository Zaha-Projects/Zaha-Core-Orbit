<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use App\Modules\Events\Models\BeneficiarySegment;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\ExecutionTeam;
use App\Modules\Events\Models\ExecutionTeamMember;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\SubjectSupply;
use App\Modules\Events\Models\SubjectVolunteerRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CommonEventExecutionFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ramadan_supports_multiple_teams_and_independent_planned_actual_counts(): void
    {
        [$iftar, $leader] = $this->iftarFixture();
        $first = ExecutionTeam::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'name' => 'Reception',
            'leader_user_id' => $leader->id,
            'planned_members_count' => 5,
            'actual_members_count' => 4,
        ]);
        $second = ExecutionTeam::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'name' => 'Serving',
        ]);

        $this->assertTrue($first->leader->is($leader));
        $this->assertSame(5, $first->planned_members_count);
        $this->assertSame(4, $first->actual_members_count);
        $this->assertSame([$first->id, $second->id], $iftar->executionTeams()->orderBy('id')->pluck('id')->all());
    }

    public function test_team_members_support_internal_external_and_unevaluated_tasks(): void
    {
        [$iftar, $user] = $this->iftarFixture();
        $team = ExecutionTeam::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'name' => 'Operations',
        ]);
        $internal = ExecutionTeamMember::query()->create([
            'execution_team_id' => $team->id,
            'user_id' => $user->id,
            'confirmed_by' => $user->id,
        ]);
        $externalA = ExecutionTeamMember::query()->create([
            'execution_team_id' => $team->id,
            'member_name' => 'External A',
            'phone' => '0790000000',
        ]);
        $externalB = ExecutionTeamMember::query()->create([
            'execution_team_id' => $team->id,
            'member_name' => 'External B',
            'phone' => '0790000000',
        ]);

        $this->assertTrue($internal->user->is($user));
        $this->assertTrue($internal->confirmer->is($user));
        $this->assertNull($externalA->refresh()->task_completed);
        $this->assertSame(3, $team->members()->count());

        $team->delete();
        $this->assertDatabaseMissing('execution_team_members', ['id' => $externalB->id]);
    }

    public function test_volunteer_requirements_allow_multiple_rows_and_lookup_classification(): void
    {
        [$iftar] = $this->iftarFixture();
        $segment = BeneficiarySegment::query()->create([
            'code' => 'youth', 'name_ar' => 'شباب', 'name_en' => 'Youth',
            'dimension' => BeneficiarySegment::DIMENSION_AGE,
        ]);
        $first = SubjectVolunteerRequirement::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'beneficiary_segment_id' => $segment->id,
            'planned_count' => 8,
            'actual_count' => 6,
        ]);
        $second = SubjectVolunteerRequirement::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'planned_count' => 2,
        ]);

        $this->assertTrue($first->beneficiarySegment->is($segment));
        $this->assertSame(8, $first->planned_count);
        $this->assertSame(6, $first->actual_count);
        $this->assertNull($second->gender);
        $this->assertSame(SubjectVolunteerRequirement::STATUS_PENDING, $second->refresh()->status);
        $this->assertSame(2, $iftar->volunteerRequirements()->count());
    }

    public function test_supplies_allow_multiple_rows_and_preserve_assessment_and_values(): void
    {
        [$iftar] = $this->iftarFixture();
        $first = SubjectSupply::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'item_name' => 'Tables',
            'planned_quantity' => 10,
            'actual_quantity' => 8,
            'provider_type' => 'supporter',
            'provider_name' => 'Partner',
            'estimated_value' => '250.50',
        ]);
        SubjectSupply::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'item_name' => 'Tables',
            'planned_quantity' => 2,
        ]);

        $this->assertSame(10, $first->planned_quantity);
        $this->assertSame(8, $first->actual_quantity);
        $this->assertNull($first->is_available);
        $this->assertSame('supporter', $first->provider_type);
        $this->assertSame('Partner', $first->provider_name);
        $this->assertSame('250.50', $first->estimated_value);
        $this->assertSame(SubjectSupply::STATUS_PENDING, $first->refresh()->status);
        $this->assertSame(2, $iftar->supplies()->count());
    }

    public function test_ramadan_relationships_reject_same_id_rows_for_other_subject_types(): void
    {
        [$iftar] = $this->iftarFixture();

        foreach ([EventSubjectTypes::RAMADAN_IFTAR, EventSubjectTypes::MONTHLY_ACTIVITY] as $type) {
            ExecutionTeam::query()->create(['subject_type' => $type, 'subject_id' => $iftar->id, 'name' => $type]);
            SubjectVolunteerRequirement::query()->create(['subject_type' => $type, 'subject_id' => $iftar->id, 'planned_count' => 1]);
            SubjectSupply::query()->create(['subject_type' => $type, 'subject_id' => $iftar->id, 'item_name' => $type, 'planned_quantity' => 1]);
        }

        $this->assertSame([EventSubjectTypes::RAMADAN_IFTAR], $iftar->executionTeams()->pluck('subject_type')->all());
        $this->assertSame([EventSubjectTypes::RAMADAN_IFTAR], $iftar->volunteerRequirements()->pluck('subject_type')->all());
        $this->assertSame([EventSubjectTypes::RAMADAN_IFTAR], $iftar->supplies()->pluck('subject_type')->all());
    }

    public function test_subject_query_scope_rejects_unknown_aliases(): void
    {
        $this->expectException(InvalidArgumentException::class);
        ExecutionTeam::query()->forSubject('App\\Models\\User', 1)->get();
    }

    private function iftarFixture(): array
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $iftar = RamadanIftar::query()->create([
            'branch_id' => $branch->id,
            'title' => 'Ramadan Iftar',
            'relations_officer_id' => $user->id,
            'created_by' => $user->id,
            'planned_date' => '2027-03-01',
            'location_type' => RamadanIftar::LOCATION_OUTSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_LOCAL_COMMUNITY,
            'planned_meals_count' => 0,
            'expected_attendance' => 0,
        ]);

        return [$iftar, $user];
    }
}
