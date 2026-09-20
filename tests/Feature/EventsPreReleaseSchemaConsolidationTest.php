<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Models\EventSupply;
use App\Modules\Events\Models\ExecutionTeamMember;
use App\Models\PostExecutionVerification;
use App\Modules\Events\Models\TargetGroup;
use App\Models\User;
use App\Modules\Events\Models\EventSubjectTypes;
use App\Modules\Events\Models\SubjectTargetGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EventsPreReleaseSchemaConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_generalized_tables_exist_for_the_four_consolidated_concepts(): void
    {
        foreach (['subject_target_groups', 'subject_supplies', 'field_verifications', 'monthly_activity_team', 'monthly_activity_supplies'] as $table) {
            $this->assertFalse(Schema::hasTable($table), $table.' must not be part of the fresh-install schema.');
        }

        $this->assertTrue(Schema::hasColumns('event_target_group', [
            'monthly_activity_id', 'subject_type', 'subject_id', 'beneficiary_segment_id',
            'planned_count', 'actual_count',
        ]));
        $this->assertTrue(Schema::hasColumns('execution_team_members', [
            'monthly_activity_id', 'execution_team_id', 'task_completed', 'confirmed_by',
        ]));
        $this->assertTrue(Schema::hasColumns('event_supplies', [
            'monthly_activity_id', 'subject_type', 'subject_id', 'planned_quantity', 'actual_quantity',
        ]));
        $this->assertTrue(Schema::hasColumns('post_execution_verifications', [
            'monthly_activity_id', 'monitoring_report_id', 'planned_value', 'actual_value', 'match_status',
        ]));
    }

    public function test_monthly_rows_keep_their_ids_and_legacy_relationships(): void
    {
        $activity = MonthlyActivity::factory()->create();
        $user = User::factory()->create();
        $target = TargetGroup::query()->create(['name' => 'Historical target']);

        $targetRowId = DB::table('event_target_group')->insertGetId([
            'monthly_activity_id' => $activity->id,
            'subject_type' => EventSubjectTypes::MONTHLY_ACTIVITY,
            'subject_id' => $activity->id,
            'target_group_id' => $target->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $team = ExecutionTeamMember::query()->create([
            'monthly_activity_id' => $activity->id,
            'user_id' => $user->id,
            'member_name' => 'Historical member',
        ]);
        $supply = EventSupply::query()->create([
            'monthly_activity_id' => $activity->id,
            'item_name' => 'Historical supply',
            'quantity' => 3,
            'available' => true,
        ]);

        $this->assertSame($targetRowId, SubjectTargetGroup::query()->findOrFail($targetRowId)->id);
        $this->assertTrue($activity->fresh()->targetGroups->contains($target));
        $this->assertSame($team->id, $activity->fresh()->team()->firstOrFail()->id);
        $this->assertSame($supply->id, $activity->fresh()->supplies()->firstOrFail()->id);
    }

    public function test_monthly_verification_model_remains_bound_to_historical_table(): void
    {
        $activity = MonthlyActivity::factory()->create();
        $branch = Branch::query()->findOrFail($activity->branch_id);
        $verification = PostExecutionVerification::query()->create([
            'monthly_activity_id' => $activity->id,
            'branch_id' => $branch->id,
            'field_key' => 'actual_attendance',
            'status' => 'pending',
        ]);

        $this->assertSame('post_execution_verifications', $verification->getTable());
        $this->assertSame($verification->id, $activity->fresh()->postExecutionVerifications()->firstOrFail()->id);
    }
}
