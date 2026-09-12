<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ExecutionNeedType;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\RamadanIftar;
use App\Modules\Events\Models\SubjectExecutionNeed;
use Database\Seeders\CanonicalExecutionNeedTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommonEventExecutionNeedsFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_canonical_seeder_is_idempotent_and_preserves_existing_ids_and_custom_rows(): void
    {
        $transport = ExecutionNeedType::query()->create(['code' => 'transport', 'name' => 'Old label']);
        $custom = ExecutionNeedType::query()->create(['code' => 'custom_need', 'name' => 'Custom']);

        $this->seed(CanonicalExecutionNeedTypeSeeder::class);
        $this->seed(CanonicalExecutionNeedTypeSeeder::class);

        $this->assertSame($transport->id, ExecutionNeedType::query()->where('code', 'transport')->value('id'));
        $this->assertTrue(ExecutionNeedType::query()->where('code', 'transport')->firstOrFail()->is_canonical);
        $this->assertSame($custom->id, ExecutionNeedType::query()->where('code', 'custom_need')->value('id'));
        $this->assertFalse(ExecutionNeedType::query()->where('code', 'custom_need')->firstOrFail()->is_canonical);
        $this->assertCount(count(ExecutionNeedType::canonicalCodes()), ExecutionNeedType::query()->canonical()->active()->get());
    }

    public function test_subject_scope_and_ramadan_relationship_isolate_equal_numeric_ids(): void
    {
        $this->seed(CanonicalExecutionNeedTypeSeeder::class);
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $iftar = RamadanIftar::query()->create($this->iftarAttributes($branch, $user));
        $monthly = MonthlyActivity::factory()->create(['id' => $iftar->id, 'branch_id' => $branch->id, 'created_by' => $user->id]);
        $type = ExecutionNeedType::query()->canonical()->firstOrFail();

        SubjectExecutionNeed::query()->create([
            'subject_type' => EventSubjectTypes::RAMADAN_IFTAR,
            'subject_id' => $iftar->id,
            'execution_need_type_id' => $type->id,
            'is_required' => true,
            'status' => SubjectExecutionNeed::STATUS_PENDING,
        ]);
        SubjectExecutionNeed::query()->create([
            'subject_type' => EventSubjectTypes::MONTHLY_ACTIVITY,
            'subject_id' => $iftar->id,
            'execution_need_type_id' => $type->id,
            'is_required' => true,
            'status' => SubjectExecutionNeed::STATUS_PENDING,
        ]);

        $this->assertSame($iftar->id, $monthly->id);
        $this->assertCount(1, $iftar->executionNeeds);
        $this->assertCount(1, SubjectExecutionNeed::query()->forSubject(EventSubjectTypes::RAMADAN_IFTAR, $iftar->id)->get());
        $this->assertTrue($iftar->executionNeeds->first()->executionNeedType->is($type));
    }

    private function iftarAttributes(Branch $branch, User $user): array
    {
        return [
            'branch_id' => $branch->id,
            'title' => 'Execution needs foundation',
            'relations_officer_id' => $user->id,
            'created_by' => $user->id,
            'planned_date' => '2027-03-01',
            'location_type' => RamadanIftar::LOCATION_OUTSIDE_CENTER,
            'host_type' => RamadanIftar::HOST_ASSOCIATION,
            'planned_meals_count' => 0,
            'expected_attendance' => 0,
        ];
    }
}
