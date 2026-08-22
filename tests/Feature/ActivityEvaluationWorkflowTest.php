<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\EvaluationForm;
use App\Models\EvaluationQuestion;
use App\Models\MonthlyActivity;
use App\Models\Role;
use App\Models\User;
use App\Services\ActivityEvaluationService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\EvaluationWorkflowPermissionSeeder;
use Database\Seeders\EvaluationWorkflowAccessSeeder;
use Database\Seeders\CompleteRolePermissionSeeder;
use Database\Seeders\EvaluationOfficerUsersSeeder;
use Database\Seeders\FollowupOfficerUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
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

    public function test_permission_seeder_assigns_new_permissions_after_the_cache_was_warmed(): void
    {
        $role = Role::query()->where('name', 'followup_officer')->firstOrFail();
        Permission::query()->where('name', 'followup.dashboard.view')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $role->getAllPermissions();

        $this->seed(EvaluationWorkflowAccessSeeder::class);

        $this->assertDatabaseHas('permissions', [
            'name' => 'followup.dashboard.view',
            'guard_name' => 'web',
        ]);
        $this->assertTrue($role->fresh()->hasPermissionTo('followup.dashboard.view'));
    }

    public function test_access_seeder_provisions_complete_permissions_for_the_new_officer_accounts(): void
    {
        $this->seed(EvaluationWorkflowAccessSeeder::class);

        $followupRole = Role::findByName('followup_officer', 'web');
        $evaluationRole = Role::findByName('evaluation_officer', 'web');

        $this->assertTrue($followupRole->hasPermissionTo('followup.dashboard.view'));
        $this->assertTrue($followupRole->hasPermissionTo('followup.post_execution.verify'));
        $this->assertTrue($followupRole->hasPermissionTo('evaluation.submit_branch'));
        $this->assertTrue($evaluationRole->hasPermissionTo('evaluation.view_all'));
        $this->assertTrue($evaluationRole->hasPermissionTo('post_execution.view_all'));
        $this->assertTrue($evaluationRole->hasPermissionTo('followup.monthly_plans.view'));
    }

    public function test_complete_access_seeder_is_additive_and_allows_seeded_officers_through_middleware(): void
    {
        $customPermission = Permission::create(['name' => 'custom.permission', 'guard_name' => 'web']);
        $role = Role::findByName('followup_officer', 'web');
        $role->givePermissionTo($customPermission);

        $this->seed(CompleteRolePermissionSeeder::class);

        $role = $role->fresh();
        $this->assertTrue($role->hasPermissionTo('custom.permission'));
        $this->assertTrue($role->hasPermissionTo('followup.dashboard.view'));

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->syncRoles([$role]);
        $user->assignedBranches()->sync([$branch->id]);

        $this->actingAs($user)->get(route('followup.dashboard'))->assertOk();
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

    public function test_followup_officer_can_render_monthly_plan_details(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->syncRoles(['followup_officer']);
        $user->assignedBranches()->sync([$branch->id]);
        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'actual_attendance' => 42,
            'post_execution_payload' => [
                'schema_version' => 1,
                'completed_at' => '2026-07-02 11:12:39',
                'teams' => [[
                    'team_name' => 'فريق العلاقات العامة',
                    'planned_members_count' => 3,
                    'actual_attendance_count' => 2,
                    'all_members_attended' => false,
                    'accomplished_tasks' => 'متابعة تنفيذ الخطة.',
                ]],
                'ceremony_items' => [],
            ],
        ]);
        $activity->attachments()->create([
            'file_type' => 'post_execution',
            'title' => 'صورة التنفيذ',
            'file_path' => 'monthly-activities/test.jpg',
            'uploaded_by' => $user->id,
        ]);
        app(ActivityEvaluationService::class)->synchronizeVerificationFields($activity);

        $this->actingAs($user)
            ->get(route('followup.monthly-plans.show', $activity))
            ->assertOk()
            ->assertSee($activity->title)
            ->assertSee('عدد الحضور المتوقع')
            ->assertSee('عدد الحضور الفعلي')
            ->assertSee('42')
            ->assertSee('صورة التنفيذ')
            ->assertSee('تنزيل')
            ->assertSee('المهام المنجزة — الفريق رقم 1');

        $this->actingAs($user)
            ->get(route('evaluations.verification.review', $activity))
            ->assertOk()
            ->assertSee('تاريخ ووقت إكمال ما بعد التنفيذ')
            ->assertSee('إصدار نموذج ما بعد التنفيذ')
            ->assertSee('اسم الفريق — الفريق رقم 1')
            ->assertSee('عدد الحضور الفعلي — الفريق رقم 1')
            ->assertDontSee('teams 0 actual attendance count');
    }

    public function test_evaluation_submission_uses_laravel_eight_compatible_request_access(): void
    {
        $this->assertTrue(Schema::hasColumn('activity_evaluation_answers', 'question_sort_order'));

        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->syncRoles(['followup_officer']);
        $user->assignedBranches()->sync([$branch->id]);
        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'post_execution_submitted',
            'post_execution_payload' => ['attendance' => 25],
        ]);
        $form = EvaluationForm::create(['name_ar' => 'نموذج', 'name_en' => 'Form', 'is_active' => true]);
        $question = EvaluationQuestion::create([
            'evaluation_form_id' => $form->id, 'question' => 'Quality', 'question_ar' => 'الجودة',
            'question_en' => 'Quality', 'answer_type' => 'score_10', 'minimum_score' => 1,
            'maximum_score' => 10, 'weight' => 1, 'is_required' => true, 'is_active' => true,
        ]);
        $service = app(ActivityEvaluationService::class);
        $service->synchronizeVerificationFields($activity);
        $activity->postExecutionVerifications()->update(['status' => 'correct']);

        $this->actingAs($user)->post(route('evaluations.store', $activity), [
            'evaluation_form_id' => (string) $form->id,
            'answers' => [$question->id => ['score' => 8]],
            'notes' => 'تقييم مكتمل',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_evaluations', [
            'monthly_activity_id' => $activity->id,
            'evaluation_form_id' => $form->id,
        ]);
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

    public function test_verification_page_renders_legacy_array_correction_values(): void
    {
        $branch = Branch::factory()->create();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->syncRoles(['followup_officer']);
        $user->assignedBranches()->sync([$branch->id]);
        $activity = MonthlyActivity::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'post_execution_submitted',
            'post_execution_payload' => ['attendance' => 45],
        ]);

        app(ActivityEvaluationService::class)->synchronizeVerificationFields($activity);
        $activity->postExecutionVerifications()->firstOrFail()->update([
            'status' => 'incorrect',
            'corrected_value' => ['value' => ['attendance' => 40, 'source' => 'attendance sheet']],
        ]);

        $this->actingAs($user)
            ->get(route('evaluations.verification.review', $activity))
            ->assertOk()
            ->assertSee('attendance sheet');
    }

    public function test_evaluation_officer_seeder_follows_the_existing_user_seeder_convention(): void
    {
        $this->seed(EvaluationOfficerUsersSeeder::class);
        $officer = User::query()->where('email', 'evaluation-officer01@zaha.test')->firstOrFail();

        $this->assertSame('مسؤول التقييم 01', $officer->name);
        $this->assertSame('0762200001', $officer->phone);
        $this->assertTrue($officer->hasRole('evaluation_officer'));
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('password', $officer->password));
    }

    public function test_followup_seeder_assigns_primary_and_assigned_branch(): void
    {
        $branch = Branch::factory()->create(['city' => 'فرع اختباري']);
        $this->seed(FollowupOfficerUsersSeeder::class);
        $officer = User::query()->where('email', 'followup-officer.branch01@zaha.test')->firstOrFail();

        $this->assertSame($branch->id, $officer->branch_id);
        $this->assertTrue($officer->assignedBranches()->whereKey($branch->id)->exists());
        $this->assertTrue($officer->hasRole('followup_officer'));
    }

    public function test_followup_sidebar_contains_only_the_six_workspace_links(): void
    {
        $branch = Branch::factory()->create();
        $officer = User::factory()->create(['branch_id' => $branch->id]);
        $officer->syncRoles(['followup_officer']);
        $officer->assignedBranches()->sync([$branch->id]);

        $response = $this->actingAs($officer)->get(route('followup.dashboard'));
        $response->assertOk();
        foreach (['لوحة التحكم', 'الخطط الشهرية', 'بانتظار التقييم', 'التقييمات السابقة', 'دليل المستخدمين', 'الملف الشخصي'] as $label) {
            $response->assertSee($label);
        }
        $response->assertDontSee('تقارير الإدارة')->assertDontSee('إعدادات الموقع')->assertDontSee('الأجندة السنوية');
    }

    public function test_followup_dashboard_uses_clear_branch_lifecycle_statistics(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $officer = User::factory()->create(['branch_id' => $branch->id]);
        $officer->syncRoles(['followup_officer']);
        $officer->assignedBranches()->sync([$branch->id]);

        MonthlyActivity::factory()->count(2)->create(['branch_id' => $branch->id]);
        MonthlyActivity::factory()->create(['branch_id' => $otherBranch->id]);

        $response = $this->actingAs($officer)->get(route('followup.dashboard'));

        $response->assertOk()
            ->assertSeeInOrder(['data-stat-key="all_plans"', '>2</div>', 'جميع خطط الفرع'], false)
            ->assertSee('دليل مراجعة بيانات ما بعد التنفيذ والتقييم')
            ->assertSee('تحقق من كل قيمة')
            ->assertSee('ابدأ التقييم بعد اكتمال المراجعة')
            ->assertSee('تم إكمال ما بعد التنفيذ (بانتظار مراجعة ما بعد التنفيذ)')
            ->assertSee('تمت مراجعة ما بعد التنفيذ (بانتظار التقييم)')
            ->assertSee('تم التقييم')
            ->assertDontSee('خطط هذا الشهر')
            ->assertDontSee('متوسط تقييم الفرع');

        $this->actingAs($officer)
            ->get(route('followup.awaiting-evaluation'))
            ->assertOk()
            ->assertSee('دليل مراجعة بيانات ما بعد التنفيذ والتقييم')
            ->assertSee('هذه البيانات يُدخلها فريق الفرع بعد تنفيذ النشاط فعليًا')
            ->assertSee('fa-clipboard-list', false)
            ->assertSee('fa-search', false)
            ->assertSee('fa-star', false);
    }

    public function test_followup_monthly_calendar_is_branch_scoped(): void
    {
        $branch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $officer = User::factory()->create(['branch_id' => $branch->id]);
        $officer->syncRoles(['followup_officer']);
        $officer->assignedBranches()->sync([$branch->id]);
        $officer->givePermissionTo(Permission::findOrCreate('branches.view.all', 'web'));
        MonthlyActivity::factory()->create(['branch_id' => $branch->id, 'title' => 'خطة فرعي', 'proposed_date' => now()]);
        MonthlyActivity::factory()->create(['branch_id' => $otherBranch->id, 'title' => 'خطة فرع آخر', 'proposed_date' => now()]);

        $this->actingAs($officer)
            ->get(route('followup.monthly-plans', ['branch_id' => $otherBranch->id, 'scope' => 'all_branches']))
            ->assertOk()
            ->assertSee('خطة فرعي')
            ->assertDontSee('خطة فرع آخر')
            ->assertDontSee('name="branch_id"', false);

        $this->actingAs($officer)
            ->getJson(route('role.relations.activities.calendar', [
                'year' => now()->year,
                'month' => now()->month,
                'branch_id' => $otherBranch->id,
                'scope' => 'all_branches',
            ]))
            ->assertOk()
            ->assertJsonFragment(['title' => 'خطة فرعي'])
            ->assertJsonMissing(['title' => 'خطة فرع آخر']);
    }

    public function test_followup_and_evaluation_officers_use_the_existing_monthly_plans_calendar(): void
    {
        $branch = Branch::factory()->create();
        MonthlyActivity::factory()->create(['branch_id' => $branch->id, 'title' => 'خطة التقويم الموحد', 'proposed_date' => now()]);

        $followup = User::factory()->create(['branch_id' => $branch->id]);
        $followup->syncRoles(['followup_officer']);
        $followup->assignedBranches()->sync([$branch->id]);
        $this->actingAs($followup)->get(route('followup.monthly-plans'))
            ->assertOk()->assertSee('monthly-activities-module')->assertSee('خطة التقويم الموحد');

        $evaluationOfficer = User::factory()->create();
        $evaluationOfficer->syncRoles(['evaluation_officer']);
        $this->actingAs($evaluationOfficer)->get(route('followup.monthly-plans'))
            ->assertOk()->assertSee('monthly-activities-module')->assertSee('خطة التقويم الموحد');
    }

    public function test_evaluation_officer_sidebar_keeps_monthly_plans_and_hides_all_branches_duplicate(): void
    {
        $officer = User::factory()->create();
        $officer->syncRoles(['evaluation_officer']);

        $this->actingAs($officer)->get(route('evaluations.dashboard'))
            ->assertOk()
            ->assertSee(route('role.relations.activities.index'), false)
            ->assertDontSee(route('role.relations.activities.index', ['scope' => 'all_branches']), false);
    }
}
