<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ExecutionNeedType;
use App\Models\User;
use App\Modules\Events\Models\ExecutionTeam;
use App\Modules\Events\Models\ExecutionTeamMember;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\RamadanIftarGift;
use App\Modules\Events\Models\RamadanIftarMeal;
use App\Modules\Events\Models\RamadanIftarProgramSegment;
use App\Modules\Events\Models\SubjectExecutionNeed;
use App\Modules\Events\Models\SubjectSupply;
use App\Modules\Events\Models\SubjectVolunteerRequirement;
use App\Modules\Events\Services\RamadanIftarExecutionService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RamadanIftarExecutionFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolesSeeder::class);
    }

    public function test_execution_page_requires_approved_lifecycle_permission_and_branch(): void
    {
        [$iftar, $actor] = $this->executionFixture();
        $this->actingAs($actor)->get(route('events.ramadan.iftars.execution.show', $iftar))->assertOk();

        $iftar->update(['status' => RamadanIftar::STATUS_SUBMITTED]);
        $this->actingAs($actor)->get(route('events.ramadan.iftars.execution.show', $iftar))->assertForbidden();
        $iftar->update(['status' => RamadanIftar::STATUS_APPROVED]);

        $otherBranchActor = User::factory()->create(['branch_id' => Branch::factory()->create()->id, 'status' => 'active']);
        $otherBranchActor->givePermissionTo(['ramadan_iftars.execute', 'branches.view.own']);
        $this->actingAs($otherBranchActor)->get(route('events.ramadan.iftars.execution.show', $iftar))->assertForbidden();

        $unauthorized = User::factory()->create(['branch_id' => $iftar->branch_id, 'status' => 'active']);
        $unauthorized->assignRole('staff');
        $this->actingAs($unauthorized)->get(route('events.ramadan.iftars.execution.show', $iftar))->assertForbidden();
    }

    public function test_only_approved_iftar_can_start_and_execution_does_not_close_it(): void
    {
        [$iftar, $actor] = $this->executionFixture();
        $service = app(RamadanIftarExecutionService::class);
        foreach ([RamadanIftar::STATUS_DRAFT, RamadanIftar::STATUS_SUBMITTED, RamadanIftar::STATUS_CHANGES_REQUESTED] as $status) {
            $iftar->update(['status' => $status, 'execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED]);
            try {
                $service->start($iftar->fresh(), $actor);
                $this->fail('A non-approved Iftar entered execution.');
            } catch (ValidationException $exception) {
                $this->assertSame(RamadanIftar::EXECUTION_STATUS_PLANNED, $iftar->fresh()->execution_status);
            }
        }

        $iftar->update(['status' => RamadanIftar::STATUS_APPROVED]);
        $service->start($iftar->fresh(), $actor);
        $this->assertSame(RamadanIftar::EXECUTION_STATUS_IN_PROGRESS, $iftar->fresh()->execution_status);
        $this->assertNull($iftar->fresh()->closed_at);
    }

    public function test_full_actual_save_derives_totals_and_preserves_every_planned_value(): void
    {
        [$iftar, $actor, $rows] = $this->executionFixture();
        $service = app(RamadanIftarExecutionService::class);
        $service->start($iftar, $actor);
        $plannedDate = $iftar->planned_date->toDateString();

        $service->update($iftar->fresh(), $this->payload($rows, [
            'actual_date' => '2026-03-10',
            'planned_date' => '2030-01-01',
            'attendees' => [
                ['full_name' => 'First', 'phone' => '0500', 'attended' => true, 'checked_in_at' => '2000-01-01 00:00:00'],
                ['full_name' => 'Second', 'phone' => '0500', 'attended' => true],
                ['full_name' => 'Absent', 'attended' => false],
            ],
            'meals' => [['id' => $rows['meal']->id, 'actual_quantity' => 12, 'rating' => 4, 'rating_notes' => 'Good', 'planned_quantity' => 999]],
            'gifts' => [['id' => $rows['gift']->id, 'actual_quantity' => 3, 'planned_quantity' => 999]],
            'program_segments' => [['id' => $rows['program']->id, 'execution_status' => 'completed', 'actual_notes' => 'Delivered', 'name' => 'Forged']],
            'execution_teams' => [['id' => $rows['team']->id, 'actual_members_count' => 2, 'planned_members_count' => 999, 'members' => [['id' => $rows['member']->id, 'task_completed' => false, 'actual_task_note' => 'Incomplete', 'confirmed_by' => 999]]]],
            'volunteer_requirements' => [['id' => $rows['volunteer']->id, 'actual_count' => 4, 'planned_count' => 999]],
            'supplies' => [['id' => $rows['supply']->id, 'actual_quantity' => 7, 'is_available' => false, 'planned_quantity' => 999]],
            'execution_needs' => [['id' => $rows['need']->id, 'status' => 'completed', 'actual_details' => 'Provided', 'planned_details' => 'Forged', 'is_required' => false, 'completed_at' => '2000-01-01']],
        ]), $actor);

        $iftar->refresh();
        $this->assertSame('2026-03-10', $iftar->actual_date->toDateString());
        $this->assertSame($plannedDate, $iftar->planned_date->toDateString());
        $this->assertSame(2, $iftar->actual_attendance);
        $this->assertSame(12, $iftar->actual_meals_count);
        $this->assertSame(10, $rows['meal']->fresh()->planned_quantity);
        $this->assertSame(5, $rows['gift']->fresh()->planned_quantity);
        $this->assertSame('Program', $rows['program']->fresh()->name);
        $this->assertSame(3, $rows['team']->fresh()->planned_members_count);
        $this->assertSame(6, $rows['volunteer']->fresh()->planned_count);
        $this->assertSame(8, $rows['supply']->fresh()->planned_quantity);
        $this->assertSame('Planned transport', $rows['need']->fresh()->planned_details);
        $this->assertTrue($rows['need']->fresh()->is_required);
        $this->assertNotNull($rows['need']->fresh()->completed_at);
        $this->assertSame(RamadanIftarProgramSegment::STATUS_COMPLETED, $rows['program']->fresh()->execution_status);
        $this->assertFalse($rows['member']->fresh()->task_completed);
        $this->assertSame($actor->id, $rows['member']->fresh()->confirmed_by);
        $this->assertNotNull($rows['member']->fresh()->confirmed_at);
        $this->assertFalse($rows['supply']->fresh()->is_available);
        $this->assertCount(2, $iftar->attendees()->where('phone', '0500')->get());
        $this->assertNotSame(2000, $iftar->attendees()->where('full_name', 'First')->first()->checked_in_at->year);
    }

    public function test_foreign_child_id_rolls_back_all_actual_changes(): void
    {
        [$iftar, $actor, $rows] = $this->executionFixture();
        [$other, , $otherRows] = $this->executionFixture();
        $service = app(RamadanIftarExecutionService::class);
        $service->start($iftar, $actor);
        $foreignAttendee = $other->attendees()->create(['full_name' => 'Private attendee']);
        $foreignPayloads = [
            'meal' => ['meals', [['id' => $otherRows['meal']->id, 'actual_quantity' => 99]]],
            'program' => ['program_segments', [['id' => $otherRows['program']->id, 'execution_status' => 'completed']]],
            'team' => ['execution_teams', [['id' => $otherRows['team']->id, 'actual_members_count' => 99, 'members' => []]]],
            'supply' => ['supplies', [['id' => $otherRows['supply']->id, 'actual_quantity' => 99, 'is_available' => true]]],
            'execution need' => ['execution_needs', [['id' => $otherRows['need']->id, 'status' => 'completed']]],
            'attendee' => ['attendees', [['id' => $foreignAttendee->id, 'full_name' => 'Stolen', 'attended' => true]]],
        ];

        foreach ($foreignPayloads as $label => [$key, $foreignRows]) {
            $payload = $this->payload($rows, ['actual_date' => '2026-03-10']);
            $payload['meals'][0]['actual_quantity'] = 4;
            $payload[$key] = $foreignRows;
            try {
                $service->update($iftar->fresh(), $payload, $actor);
                $this->fail("A foreign {$label} ID was accepted.");
            } catch (ValidationException $exception) {
                $this->assertNull($iftar->fresh()->actual_date);
                $this->assertNull($rows['meal']->fresh()->actual_quantity);
            }
        }
        $this->assertNull($otherRows['supply']->fresh()->actual_quantity);
        $this->assertSame('Private attendee', $foreignAttendee->fresh()->full_name);
        $this->assertSame(RamadanIftar::STATUS_APPROVED, $other->status);
    }

    private function executionFixture(): array
    {
        $branch = Branch::factory()->create();
        $actor = User::factory()->create(['branch_id' => $branch->id, 'status' => 'active']);
        $actor->givePermissionTo(['ramadan_iftars.execute', 'branches.view.own']);
        $iftar = RamadanIftar::query()->create([
            'branch_id' => $branch->id, 'title' => 'Approved Iftar', 'relations_officer_id' => $actor->id,
            'created_by' => $actor->id, 'planned_date' => '2026-03-09', 'location_type' => RamadanIftar::LOCATION_INSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_CENTER, 'planned_meals_count' => 10, 'expected_attendance' => 20,
            'status' => RamadanIftar::STATUS_APPROVED, 'execution_status' => RamadanIftar::EXECUTION_STATUS_PLANNED,
            'approved_at' => now(),
        ]);
        $meal = RamadanIftarMeal::query()->create(['ramadan_iftar_id' => $iftar->id, 'description' => 'Meal', 'planned_quantity' => 10]);
        $gift = RamadanIftarGift::query()->create(['ramadan_iftar_id' => $iftar->id, 'description' => 'Gift', 'planned_quantity' => 5]);
        $program = RamadanIftarProgramSegment::query()->create(['ramadan_iftar_id' => $iftar->id, 'name' => 'Program']);
        $team = ExecutionTeam::query()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id, 'name' => 'Team', 'planned_members_count' => 3]);
        $member = ExecutionTeamMember::query()->create(['execution_team_id' => $team->id, 'member_name' => 'Member']);
        $volunteer = SubjectVolunteerRequirement::query()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id, 'planned_count' => 6]);
        $supply = SubjectSupply::query()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id, 'item_name' => 'Water', 'planned_quantity' => 8]);
        $type = ExecutionNeedType::query()->create(['code' => 'transport-'.$iftar->id, 'name' => 'Transport', 'is_active' => true, 'is_canonical' => true, 'is_ramadan_iftar' => true]);
        $need = SubjectExecutionNeed::query()->create(['subject_type' => EventSubjectTypes::RAMADAN_IFTAR, 'subject_id' => $iftar->id, 'execution_need_type_id' => $type->id, 'is_required' => true, 'planned_details' => 'Planned transport']);

        return [$iftar, $actor, compact('meal', 'gift', 'program', 'team', 'member', 'volunteer', 'supply', 'need')];
    }

    private function payload(array $rows, array $overrides = []): array
    {
        return array_replace([
            'actual_date' => null, 'attendees' => [],
            'meals' => [['id' => $rows['meal']->id, 'actual_quantity' => null, 'rating' => null, 'rating_notes' => null]],
            'gifts' => [['id' => $rows['gift']->id, 'actual_quantity' => null]],
            'program_segments' => [['id' => $rows['program']->id, 'execution_status' => 'planned', 'actual_notes' => null]],
            'execution_teams' => [['id' => $rows['team']->id, 'actual_members_count' => null, 'members' => [['id' => $rows['member']->id, 'task_completed' => null, 'actual_task_note' => null]]]],
            'volunteer_requirements' => [['id' => $rows['volunteer']->id, 'actual_count' => null]],
            'supplies' => [['id' => $rows['supply']->id, 'actual_quantity' => null, 'is_available' => null]],
            'execution_needs' => [['id' => $rows['need']->id, 'status' => 'pending', 'actual_details' => null]],
        ], $overrides);
    }
}
