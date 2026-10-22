<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Modules\Events\Models\MonitoringReport;
use App\Modules\Events\Models\MonthlyActivity;
use App\Modules\Events\Models\PostExecutionVerification;
use App\Modules\Events\Support\PostExecutionVerificationIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostExecutionVerificationIdentityCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_transition_query_reads_old_and_new_identity_rows_without_duplication(): void
    {
        foreach (PostExecutionVerificationIdentity::acceptedTypes() as $type) {
            AuditLog::query()->create([
                'action' => 'post_execution_verified',
                'module' => 'evaluation',
                'entity_type' => $type,
                'entity_id' => 101,
            ]);
        }

        AuditLog::query()->create([
            'action' => 'unrelated',
            'module' => 'evaluation',
            'entity_type' => 'App\\Models\\MonthlyActivity',
            'entity_id' => 101,
        ]);

        $logs = AuditLog::query()
            ->whereIn('entity_type', PostExecutionVerificationIdentity::acceptedTypes())
            ->where('entity_id', 101)
            ->get();

        $this->assertCount(2, $logs);
        $this->assertCount(2, $logs->unique('id'));
        $this->assertEqualsCanonicalizing(
            PostExecutionVerificationIdentity::acceptedTypes(),
            $logs->pluck('entity_type')->all()
        );
    }

    public function test_canonical_model_keeps_table_and_id_based_parent_relationships(): void
    {
        $verification = new PostExecutionVerification();

        $this->assertSame('post_execution_verifications', $verification->getTable());
        $this->assertInstanceOf(MonthlyActivity::class, $verification->activity()->getRelated());
        $this->assertSame('monthly_activity_id', $verification->activity()->getForeignKeyName());
        $this->assertInstanceOf(MonitoringReport::class, $verification->monitoringReport()->getRelated());
        $this->assertSame('monitoring_report_id', $verification->monitoringReport()->getForeignKeyName());
        $this->assertInstanceOf(PostExecutionVerification::class, (new MonthlyActivity())->postExecutionVerifications()->getRelated());
        $this->assertInstanceOf(PostExecutionVerification::class, (new MonitoringReport())->verifications()->getRelated());
    }

}
