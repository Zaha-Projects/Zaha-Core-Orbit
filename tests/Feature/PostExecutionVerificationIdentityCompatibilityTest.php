<?php

namespace Tests\Feature;

use App\Models\AuditLog;
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
}
