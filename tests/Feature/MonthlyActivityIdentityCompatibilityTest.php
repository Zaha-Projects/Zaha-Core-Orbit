<?php

namespace Tests\Feature;

use App\Models\MonthlyActivity;
use App\Models\OfficialCorrespondence;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Modules\Events\Services\MonthlyActivityOfficialCorrespondenceService;
use App\Modules\Events\Models\MonthlyPlanDeleteRequest;
use App\Modules\Events\Models\MonthlyPlanEditRequest;
use App\Modules\Events\Support\EventAggregateIdentity;
use App\Services\DynamicWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\TestCase;

class MonthlyActivityIdentityCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_workflow_reuses_either_identity_writes_legacy_and_rejects_duplicates(): void
    {
        $activity = MonthlyActivity::factory()->create();
        $workflow = Workflow::query()->create(['code'=>'monthly_identity','module'=>'monthly_identity','name_ar'=>'هوية','name_en'=>'Identity','is_active'=>true]);
        $service = app(DynamicWorkflowService::class);

        foreach (EventAggregateIdentity::acceptedTypes(MonthlyActivity::class) as $identity) {
            $row = WorkflowInstance::query()->create(['workflow_id'=>$workflow->id,'entity_type'=>$identity,'entity_id'=>$activity->id,'status'=>'pending']);
            $this->assertSame($row->id, $service->forEntity($workflow, MonthlyActivity::class, $activity->id)->id);
            $row->delete();
        }

        $created = $service->forEntity($workflow, EventAggregateIdentity::MONTHLY_ACTIVITY_CANONICAL, $activity->id);
        $this->assertSame(EventAggregateIdentity::MONTHLY_ACTIVITY_LEGACY, $created->entity_type);
        $created->delete();

        foreach (EventAggregateIdentity::acceptedTypes(MonthlyActivity::class) as $identity) {
            WorkflowInstance::query()->create(['workflow_id'=>$workflow->id,'entity_type'=>$identity,'entity_id'=>$activity->id,'status'=>'pending']);
        }
        $this->expectException(LogicException::class);
        $service->forEntity($workflow, MonthlyActivity::class, $activity->id);
    }

    public function test_correspondence_reads_both_types_and_sync_preserves_current_writer(): void
    {
        $activity = MonthlyActivity::factory()->create();
        $service = app(MonthlyActivityOfficialCorrespondenceService::class);

        $legacy = $service->sync($activity, ['reason'=>'legacy']);
        $this->assertSame(EventAggregateIdentity::MONTHLY_ACTIVITY_LEGACY, $legacy->correspondable_type);
        $this->assertTrue($legacy->correspondable->is($activity));
        $this->assertSame($legacy->id, $activity->fresh()->officialCorrespondence->id);

        $legacy->update(['correspondable_type'=>EventAggregateIdentity::MONTHLY_ACTIVITY_CANONICAL]);
        $canonical = $service->sync($activity, ['reason'=>'canonical']);
        $this->assertSame($legacy->id, $canonical->id);
        $this->assertSame(EventAggregateIdentity::MONTHLY_ACTIVITY_CANONICAL, $canonical->correspondable_type);
        $this->assertTrue($canonical->fresh()->correspondable->is($activity));
    }

    public function test_correspondence_mixed_identity_duplicate_is_explicit_conflict(): void
    {
        $activity = MonthlyActivity::factory()->create();
        foreach (EventAggregateIdentity::acceptedTypes(MonthlyActivity::class) as $identity) {
            OfficialCorrespondence::query()->create(['correspondable_type'=>$identity,'correspondable_id'=>$activity->id,'reason'=>$identity]);
        }

        $this->expectException(LogicException::class);
        app(MonthlyActivityOfficialCorrespondenceService::class)->sync($activity, ['reason'=>'unsafe']);
    }

    public function test_canonical_request_models_read_both_aggregate_identities(): void
    {
        $activity = MonthlyActivity::factory()->create();
        $user = User::factory()->create();

        foreach (EventAggregateIdentity::acceptedTypes(MonthlyActivity::class) as $identity) {
            foreach ([[MonthlyPlanEditRequest::class, 'edit'], [MonthlyPlanDeleteRequest::class, 'delete']] as [$class, $type]) {
                $request = $class::query()->create([
                    'requester_id'=>$user->id,
                    'request_type'=>$type,
                    'entity_type'=>$identity,
                    'entity_id'=>$activity->id,
                    'status'=>'pending',
                    'requested_at'=>now(),
                ]);
                $this->assertTrue($request->monthlyActivity->is($activity));
            }
        }

        $this->assertSame(MonthlyActivity::class, EventAggregateIdentity::currentWriteType(EventAggregateIdentity::MONTHLY_ACTIVITY_CANONICAL));
    }
}
