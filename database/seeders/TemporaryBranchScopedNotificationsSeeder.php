<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\InAppNotification;
use App\Models\MonthlyActivity;
use App\Models\Role;
use App\Models\User;
use App\Models\Workflow;
use App\Models\WorkflowInstance;
use App\Models\WorkflowStep;
use App\Services\DynamicWorkflowService;
use App\Services\NotificationService;
use App\Services\WorkflowNotificationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class TemporaryBranchScopedNotificationsSeeder extends Seeder
{
    public function __construct(
        protected DynamicWorkflowService $workflows,
        protected WorkflowNotificationService $workflowNotifications,
        protected NotificationService $notifications
    ) {
    }

    public function run(): void
    {
        $this->ensureRolesAndPermissions();
        $workflow = $this->ensureMonthlyWorkflow();

        $ammanBranch = $this->ensureBranch('اختبار النطاق - فرع عمّان', 'عمّان');
        $irbidBranch = $this->ensureBranch('اختبار النطاق - فرع إربد', 'إربد');

        $ammanUsers = $this->ensureBranchUsers($ammanBranch, 'amman');
        $irbidUsers = $this->ensureBranchUsers($irbidBranch, 'irbid');

        $ammanUsers['branch_coordinator']->assignedBranches()->sync([$ammanBranch->id]);
        $irbidUsers['branch_coordinator']->assignedBranches()->sync([$irbidBranch->id]);

        $pendingSupervisorAmman = $this->ensureActivity(
            'اختبار النطاق - نشاط عمّان بانتظار رئيس الفرع',
            $ammanBranch,
            $ammanUsers['relations_officer'],
            Carbon::now()->addDays(10),
            ['needs_volunteers' => true]
        );
        $pendingSupervisorIrbid = $this->ensureActivity(
            'اختبار النطاق - نشاط إربد بانتظار رئيس الفرع',
            $irbidBranch,
            $irbidUsers['relations_officer'],
            Carbon::now()->addDays(11),
            ['needs_volunteers' => true]
        );
        $pendingCoordinatorAmman = $this->ensureActivity(
            'اختبار النطاق - نشاط عمّان بانتظار منسق الفروع',
            $ammanBranch,
            $ammanUsers['relations_officer'],
            Carbon::now()->addDays(12),
            ['requires_communications' => true, 'needs_media_coverage' => true]
        );
        $pendingCoordinatorIrbid = $this->ensureActivity(
            'اختبار النطاق - نشاط إربد بانتظار منسق الفروع',
            $irbidBranch,
            $irbidUsers['relations_officer'],
            Carbon::now()->addDays(13),
            ['requires_communications' => true, 'needs_media_coverage' => true]
        );

        $this->resetWorkflow($pendingSupervisorAmman, $workflow);
        $this->resetWorkflow($pendingSupervisorIrbid, $workflow);
        $this->resetWorkflow($pendingCoordinatorAmman, $workflow);
        $this->resetWorkflow($pendingCoordinatorIrbid, $workflow);

        $this->advanceToSupervisor($pendingSupervisorAmman, $ammanUsers['relations_officer']);
        $this->advanceToSupervisor($pendingSupervisorIrbid, $irbidUsers['relations_officer']);
        $this->advanceToCoordinator($pendingCoordinatorAmman, $ammanUsers['relations_officer'], $ammanUsers['supervisor']);
        $this->advanceToCoordinator($pendingCoordinatorIrbid, $irbidUsers['relations_officer'], $irbidUsers['supervisor']);

        $this->seedPublishedNotification($pendingCoordinatorAmman, $ammanUsers['supervisor']);
        $this->seedPublishedNotification($pendingCoordinatorIrbid, $irbidUsers['supervisor']);
        $this->seedExecutionNeedNotifications($pendingSupervisorAmman, $ammanUsers['relations_officer']);
        $this->seedExecutionNeedNotifications($pendingSupervisorIrbid, $irbidUsers['relations_officer']);
        $this->seedReadTabDemoNotifications(collect($ammanUsers)->merge($irbidUsers)->unique('id')->values());
    }

    protected function ensureRolesAndPermissions(): void
    {
        $rolePermissions = [
            'relations_officer' => ['branches.view.own', 'monthly_activities.view', 'monthly_activities.approve'],
            'supervisor' => ['branches.view.own', 'monthly_activities.view', 'monthly_activities.approve'],
            'branch_coordinator' => ['branches.view.own', 'monthly_activities.view', 'monthly_activities.approve'],
            'volunteer_coordinator' => ['branches.view.own', 'monthly_activities.view'],
            'communication_head' => ['branches.view.own', 'monthly_activities.view'],
            'relations_manager' => ['branches.view.all', 'monthly_activities.view', 'monthly_activities.approve'],
            'executive_manager' => ['branches.view.all', 'monthly_activities.view', 'monthly_activities.approve'],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            $role = Role::findOrCreate($roleName, 'web');
            $role->givePermissionTo(collect($permissions)->map(fn (string $permission) => Permission::findOrCreate($permission, 'web'))->all());
        }
    }

    protected function ensureMonthlyWorkflow(): Workflow
    {
        $workflow = $this->workflows->findActiveWorkflow('monthly_activities');

        if ($workflow) {
            return $workflow;
        }

        $workflow = Workflow::query()->create([
            'code' => 'temporary_branch_scope_monthly_activity_approval',
            'module' => 'monthly_activities',
            'name_ar' => 'سير مؤقت لاختبار نطاق الفروع',
            'name_en' => 'Temporary Branch Scope Workflow',
            'is_active' => true,
        ]);

        collect([
            ['monthly_relations_officer_submit', 1, 'sub', 'relations_officer', null, null],
            ['monthly_supervisor_review', 2, 'main', 'supervisor', 'monthly_created_by_branch_relations', '1'],
            ['monthly_branch_coordinator_review', 3, 'main', 'branch_coordinator', 'monthly_branch_coordinator_required', '1'],
            ['monthly_relations_manager_review', 4, 'main', 'relations_manager', null, null],
            ['monthly_executive_manager_final_approval', 5, 'main', 'executive_manager', 'executive_review_required', '1'],
        ])->each(function (array $step) use ($workflow): void {
            WorkflowStep::query()->create([
                'workflow_id' => $workflow->id,
                'step_key' => $step[0],
                'step_order' => $step[1],
                'approval_level' => $step[1],
                'name_ar' => $step[0],
                'name_en' => $step[0],
                'step_type' => $step[2],
                'role_id' => Role::findByName($step[3], 'web')->id,
                'condition_field' => $step[4],
                'condition_value' => $step[5],
                'is_editable' => $step[2] === 'sub',
            ]);
        });

        return $workflow->fresh('steps.role');
    }

    protected function ensureBranch(string $name, string $city): Branch
    {
        return Branch::query()->updateOrCreate(
            ['name' => $name],
            ['city' => $city, 'address' => 'بيانات اختبار مؤقتة', 'is_main' => false]
        );
    }

    /**
     * @return array<string, User>
     */
    protected function ensureBranchUsers(Branch $branch, string $branchKey): array
    {
        return collect([
            'relations_officer' => ['مسؤول علاقات', 'relations-officer'],
            'supervisor' => ['رئيس فرع', 'supervisor'],
            'branch_coordinator' => ['منسق فروع', 'branch-coordinator'],
            'volunteer_coordinator' => ['مسؤول تطوع', 'volunteer-coordinator'],
            'communication_head' => ['رئيس قسم اتصال', 'communication-head'],
        ])->mapWithKeys(function (array $definition, string $roleName) use ($branch, $branchKey): array {
            $user = User::query()->updateOrCreate(
                ['email' => "temp-scope-{$definition[1]}-{$branchKey}@zaha.test"],
                [
                    'name' => "{$definition[0]} - {$branch->city}",
                    'phone' => '07999'.str_pad((string) $branch->id, 5, '0', STR_PAD_LEFT),
                    'status' => 'active',
                    'branch_id' => $branch->id,
                    'password' => Hash::make('password'),
                ]
            );
            $user->syncRoles([$roleName]);

            return [$roleName => $user->fresh('roles')];
        })->all();
    }

    protected function ensureActivity(string $title, Branch $branch, User $creator, Carbon $date, array $extra = []): MonthlyActivity
    {
        $activity = MonthlyActivity::query()->firstOrNew(['title' => $title]);
        $activity->forceFill(array_merge([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $title,
            'activity_date' => $date->toDateString(),
            'proposed_date' => $date->toDateString(),
            'location_type' => 'inside_center',
            'location_details' => 'قاعة اختبار نطاق الفروع',
            'responsible_party' => 'فريق اختبار النطاق',
            'description' => 'نشاط مؤقت لاختبار نطاق الاعتمادات والإشعارات حسب الفرع.',
            'status' => 'draft',
            'execution_status' => 'planned',
            'lifecycle_status' => 'Draft',
            'is_from_agenda' => false,
            'is_in_agenda' => false,
            'branch_id' => $branch->id,
            'created_by' => $creator->id,
            'relations_officer_approval_status' => 'pending',
            'relations_manager_approval_status' => 'pending',
            'liaison_approval_status' => 'pending',
            'hq_relations_manager_approval_status' => 'pending',
            'executive_approval_status' => 'skipped',
            'executive_review_required' => false,
        ], $extra));
        $activity->save();

        return $activity->fresh('creator');
    }

    protected function resetWorkflow(MonthlyActivity $activity, Workflow $workflow): void
    {
        $activity->approvals()->delete();
        InAppNotification::query()
            ->where('meta', 'like', '%"entity_id":'.$activity->id.'%')
            ->where('meta', 'like', '%"entity_type":"App\\\\Models\\\\MonthlyActivity"%')
            ->delete();

        WorkflowInstance::query()
            ->where('workflow_id', $workflow->id)
            ->where('entity_type', MonthlyActivity::class)
            ->where('entity_id', $activity->id)
            ->delete();

        $activity->forceFill([
            'status' => 'draft',
            'relations_officer_approval_status' => 'pending',
            'relations_manager_approval_status' => 'pending',
            'liaison_approval_status' => 'pending',
            'hq_relations_manager_approval_status' => 'pending',
            'executive_approval_status' => 'skipped',
            'executive_review_required' => false,
        ])->save();
    }

    protected function advanceToSupervisor(MonthlyActivity $activity, User $relationsOfficer): void
    {
        $instance = $this->workflows->forModel('monthly_activities', $activity);
        $step = $this->workflows->currentStepForUser($instance, $relationsOfficer);

        if ($step) {
            $this->workflows->recordDecision($instance, $step, $relationsOfficer, DynamicWorkflowService::DECISION_APPROVED, 'اختبار نطاق الفروع: إرسال مسؤول العلاقات.');
        }

        $activity->forceFill([
            'status' => 'in_review',
            'relations_officer_approval_status' => 'approved',
            'relations_manager_approval_status' => 'pending',
        ])->save();

        $this->workflowNotifications->approvalRequested(
            $instance->fresh(),
            $activity->fresh('creator'),
            route('role.programs.approvals.index'),
            $relationsOfficer
        );
    }

    protected function advanceToCoordinator(MonthlyActivity $activity, User $relationsOfficer, User $supervisor): void
    {
        $this->advanceToSupervisor($activity, $relationsOfficer);

        $instance = $this->workflows->forModel('monthly_activities', $activity->fresh());
        $step = $this->workflows->currentStepForUser($instance, $supervisor);

        if ($step) {
            $this->workflows->recordDecision($instance, $step, $supervisor, DynamicWorkflowService::DECISION_APPROVED, 'اختبار نطاق الفروع: اعتماد رئيس الفرع.');
        }

        $activity->forceFill([
            'status' => 'in_review',
            'relations_manager_approval_status' => 'approved',
            'liaison_approval_status' => 'pending',
        ])->save();

        $this->workflowNotifications->approvalRequested(
            $instance->fresh(),
            $activity->fresh('creator'),
            route('role.programs.approvals.index'),
            $supervisor
        );
    }

    protected function seedPublishedNotification(MonthlyActivity $activity, User $actor): void
    {
        InAppNotification::query()
            ->where('type', 'workflow_published')
            ->where('meta', 'like', '%"entity_id":'.$activity->id.'%')
            ->delete();

        $this->workflowNotifications->published(
            $activity->fresh('creator'),
            $actor,
            route('role.relations.activities.show', $activity)
        );
    }

    protected function seedExecutionNeedNotifications(MonthlyActivity $activity, User $actor): void
    {
        $roles = collect($activity->enabledExecutionNeeds())
            ->flatMap(fn (array $definition): array => (array) ($definition['decision_roles'] ?? []))
            ->unique()
            ->values();

        foreach ($roles as $role) {
            $recipients = User::role($role)
                ->where('status', 'active')
                ->where(function ($query) use ($activity): void {
                    $query->whereHas('assignedBranches', fn ($branchQuery) => $branchQuery->whereKey($activity->branch_id))
                        ->orWhere(function ($fallbackQuery) use ($activity): void {
                            $fallbackQuery
                                ->whereDoesntHave('assignedBranches')
                                ->where('branch_id', $activity->branch_id);
                        });
                })
                ->get();

            InAppNotification::query()
                ->where('type', 'temp_branch_scope_execution_need')
                ->where('meta', 'like', '%"monthly_activity_id":'.$activity->id.'%')
                ->where('meta', 'like', '%"role":"'.$role.'"%')
                ->delete();

            $this->notifications->notifyUsers(
                $recipients,
                'temp_branch_scope_execution_need',
                'اختبار نطاق احتياج تنفيذ حسب الفرع',
                "احتياج تنفيذ للنشاط {$activity->title} موجه فقط لدور {$role} في نفس الفرع.",
                route('role.relations.activities.show', $activity),
                [
                    'temporary_branch_scope_seed' => true,
                    'monthly_activity_id' => $activity->id,
                    'branch_id' => $activity->branch_id,
                    'role' => $role,
                    'actor_id' => $actor->id,
                ]
            );
        }
    }

    protected function seedReadTabDemoNotifications($users): void
    {
        foreach ($users as $user) {
            InAppNotification::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'type' => 'temp_branch_scope_read_demo',
                    'title' => 'اختبار تبويب المقروء',
                ],
                [
                    'message' => 'هذا الإشعار مقروء مسبقاً لاختبار تبويب المقروء داخل قائمة الإشعارات.',
                    'action_url' => route('role.programs.approvals.index'),
                    'meta' => ['temporary_branch_scope_seed' => true],
                    'read_at' => now(),
                ]
            );
        }
    }
}
