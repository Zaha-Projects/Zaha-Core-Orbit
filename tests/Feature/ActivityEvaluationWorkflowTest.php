<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\EvaluationWorkflowPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityEvaluationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(RolesSeeder::class);
        $this->seed(EvaluationWorkflowPermissionSeeder::class);
    }

    public function test_followup_officer_is_restricted_to_exactly_the_assigned_branch(): void
    {
        $branch = Branch::factory()->create();
        $other = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->syncRoles(['followup_officer']);
        $user->assignedBranches()->sync([$branch->id]);
        $own = MonthlyActivity::factory()->create(['branch_id' => $branch->id, 'post_execution_payload' => ['attendance' => 40]]);
        $foreign = MonthlyActivity::factory()->create(['branch_id' => $other->id, 'post_execution_payload' => ['attendance' => 30]]);

        $this->actingAs($user)->get(route('evaluations.verification.review', $own))->assertOk();
        $this->actingAs($user)->get(route('evaluations.verification.review', $foreign))->assertForbidden();
    }

    public function test_verification_preserves_original_and_weighted_evaluation_updates_status(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->syncRoles(['followup_officer']);
        $user->assignedBranches()->sync([$branch->id]);
        $activity = MonthlyActivity::factory()->create(['branch_id' => $branch->id, 'status' => 'post_execution_submitted', 'post_execution_payload' => ['attendance' => 45]]);
        $form = EvaluationForm::create(['name_ar' => 'نموذج', 'name_en' => 'Form', 'is_active' => true]);
        $q1 = EvaluationQuestion::create(['evaluation_form_id' => $form->id, 'question' => 'Q1', 'question_ar' => 'س1', 'question_en' => 'Q1', 'answer_type' => 'score_10', 'minimum_score' => 1, 'maximum_score' => 10, 'weight' => 1, 'is_required' => true, 'is_active' => true]);
        $q2 = EvaluationQuestion::create(['evaluation_form_id' => $form->id, 'question' => 'Q2', 'question_ar' => 'س2', 'question_en' => 'Q2', 'answer_type' => 'score_10', 'minimum_score' => 1, 'maximum_score' => 10, 'weight' => 3, 'is_required' => true, 'is_active' => true]);
        $service = app(ActivityEvaluationService::class);
        $service->synchronizeVerificationFields($activity);
        $verification = $activity->postExecutionVerifications()->firstOrFail();
        $service->verify($activity, $user, [$verification->id => ['status' => 'incorrect', 'corrected_value' => 40, 'note' => 'Source checked']]);

        $this->assertSame(45, data_get($verification->fresh()->original_value, 'value'));
        $this->assertSame(40, data_get($verification->fresh()->corrected_value, 'value'));
        $evaluation = $service->submit($activity, $form, $user, [$q1->id => ['score' => 4], $q2->id => ['score' => 8]]);
        $this->assertEquals(7.0, (float) $evaluation->normalized_score);
        $this->assertSame('evaluated', $activity->fresh()->status);
    }

    public function test_incorrect_verification_requires_a_corrected_value(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $activity = MonthlyActivity::factory()->create(['branch_id' => $branch->id, 'post_execution_payload' => ['attendance' => 45]]);
        $service = app(ActivityEvaluationService::class);
        $service->synchronizeVerificationFields($activity);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $service->verify($activity, $user, [$activity->postExecutionVerifications()->first()->id => ['status' => 'incorrect']]);
    }
}
