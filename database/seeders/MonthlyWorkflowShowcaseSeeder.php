<?php

namespace Database\Seeders;

use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivityApproval;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\DynamicWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class MonthlyWorkflowShowcaseSeeder extends Seeder
{
    public function __construct(
        protected DynamicWorkflowService $dynamicWorkflowService
    ) {
    }

    public function run(): void
    {
        $zarqaBranch = $this->resolveBranch(['zarqa', 'الزرقاء', 'الرصيفة']);
        $irbidBranch = $this->resolveBranch(['irbid', 'إربد', 'اربد', 'المشارع']);

        if (! $zarqaBranch || ! $irbidBranch) {
            return;
        }

        $creatorZarqa = $this->ensureWorkflowUser(
            'showcase-branch-relations-officer-zarqa@zaha.test',
            'Showcase Branch Relations Officer Zarqa',
            'branch_relations_officer',
            $zarqaBranch
        );
        $managerZarqa = $this->ensureWorkflowUser(
            'showcase-branch-relations-manager-zarqa@zaha.test',
            'Showcase Branch Relations Manager Zarqa',
            'branch_relations_manager',
            $zarqaBranch
        );
        $creatorIrbid = User::query()->where('email', 'branch-relations-officer@zaha.test')->first()
            ?? $this->ensureWorkflowUser(
                'showcase-branch-relations-officer-irbid@zaha.test',
                'Showcase Branch Relations Officer Irbid',
                'branch_relations_officer',
                $irbidBranch
            );
        $managerIrbid = $this->ensureWorkflowUser(
            'showcase-branch-relations-manager-irbid@zaha.test',
            'Showcase Branch Relations Manager Irbid',
            'branch_relations_manager',
            $irbidBranch
        );
        $branchCoordinator = User::query()->where('email', 'branch-coordinator@zaha.test')->first();
        $relationsOfficer = User::query()->where('email', 'relations-officer@zaha.test')->first();
        $relationsManager = User::query()->where('email', 'relations-manager@zaha.test')->first();
        $programsManager = User::query()->where('email', 'programs-manager@zaha.test')->first();
        $workshopsSecretary = User::query()->where('email', 'workshops-secretary@zaha.test')->first();
        $communicationHead = User::query()->where('email', 'communication-head@zaha.test')->first();
        $executiveManager = User::query()->where('email', 'executive-manager@zaha.test')->first();

        if (! $creatorZarqa || ! $managerZarqa || ! $creatorIrbid || ! $managerIrbid || ! $branchCoordinator || ! $relationsOfficer || ! $relationsManager || ! $executiveManager) {
            return;
        }

        $branchCoordinator->assignedBranches()->sync(array_values(array_filter([$zarqaBranch->id, $irbidBranch->id])));

        $publishedAgenda = AgendaEvent::query()->where('event_name', 'Showcase Agenda Published - National Campaign Launch')->first();
        $midFlowAgenda = AgendaEvent::query()->where('event_name', 'Showcase Agenda Mid Flow - Awaiting Executive Approval')->first();

        $draftActivity = $this->upsertMonthlyActivity([
            'title' => 'Showcase Monthly Draft - Manual Branch Meetup',
            'date' => Carbon::create(now()->year, 5, 20),
            'branch' => $zarqaBranch,
            'creator' => $creatorZarqa,
            'agenda_event_id' => null,
            'description' => 'Draft manual activity for monthly workflow preview.',
            'requires_programs' => false,
            'requires_workshops' => false,
            'requires_communications' => false,
        ]);
        $this->resetMonthlyWorkflow($draftActivity);

        $approvedActivity = $this->upsertMonthlyActivity([
            'title' => 'Showcase Monthly Approved - Agenda Linked Launch',
            'date' => Carbon::create(now()->year, 9, 12),
            'branch' => $zarqaBranch,
            'creator' => $creatorZarqa,
            'agenda_event_id' => $publishedAgenda?->id,
            'description' => 'Agenda-linked approved monthly activity.',
            'requires_programs' => false,
            'requires_workshops' => false,
            'requires_communications' => false,
        ]);
        $this->resetMonthlyWorkflow($approvedActivity);
        $this->decideMonthly($approvedActivity, $creatorZarqa, DynamicWorkflowService::DECISION_APPROVED, 'Submitted from branch.');
        $this->decideMonthly($approvedActivity, $managerZarqa, DynamicWorkflowService::DECISION_APPROVED, 'Branch manager approved.');
        $this->decideMonthly($approvedActivity, $branchCoordinator, DynamicWorkflowService::DECISION_APPROVED, 'Coordinator approved for assigned branch.');
        $this->decideMonthly($approvedActivity, $relationsOfficer, DynamicWorkflowService::DECISION_APPROVED, 'HQ relations officer approved.');
        $this->decideMonthly($approvedActivity, $relationsManager, DynamicWorkflowService::DECISION_APPROVED, 'HQ relations manager approved.');
        $this->decideMonthly($approvedActivity, $executiveManager, DynamicWorkflowService::DECISION_APPROVED, 'Final approval completed.');

        $awaitingCoordinatorActivity = $this->upsertMonthlyActivity([
            'title' => 'Showcase Monthly In Review - Awaiting Branch Coordinator',
            'date' => Carbon::create(now()->year, 6, 18),
            'branch' => $irbidBranch,
            'creator' => $creatorIrbid,
            'agenda_event_id' => $midFlowAgenda?->id,
            'description' => 'Awaiting branch coordinator decision for assigned branch.',
            'requires_programs' => false,
            'requires_workshops' => false,
            'requires_communications' => false,
        ]);
        $this->resetMonthlyWorkflow($awaitingCoordinatorActivity);
        $this->decideMonthly($awaitingCoordinatorActivity, $creatorIrbid, DynamicWorkflowService::DECISION_APPROVED, 'Submitted from branch.');
        $this->decideMonthly($awaitingCoordinatorActivity, $managerIrbid, DynamicWorkflowService::DECISION_APPROVED, 'Branch manager approved.');

        $allRequirementsActivity = $this->upsertMonthlyActivity([
            'title' => 'Showcase Monthly In Review - All Requirement Tracks',
            'date' => Carbon::create(now()->year, 7, 24),
            'branch' => $zarqaBranch,
            'creator' => $creatorZarqa,
            'agenda_event_id' => $publishedAgenda?->id,
            'description' => 'Monthly activity that activates programs, workshops, and communications steps.',
            'requires_programs' => true,
            'requires_workshops' => true,
            'requires_communications' => true,
        ]);
        $this->resetMonthlyWorkflow($allRequirementsActivity);
        $this->decideMonthly($allRequirementsActivity, $creatorZarqa, DynamicWorkflowService::DECISION_APPROVED, 'Submitted from branch.');
        $this->decideMonthly($allRequirementsActivity, $managerZarqa, DynamicWorkflowService::DECISION_APPROVED, 'Branch manager approved.');
        $this->decideMonthly($allRequirementsActivity, $branchCoordinator, DynamicWorkflowService::DECISION_APPROVED, 'Coordinator approved.');
        $this->decideMonthly($allRequirementsActivity, $relationsOfficer, DynamicWorkflowService::DECISION_APPROVED, 'HQ relations officer approved.');
        if ($programsManager) {
            $this->decideMonthly($allRequirementsActivity, $programsManager, DynamicWorkflowService::DECISION_APPROVED, 'Programs review completed.');
        }
        if ($workshopsSecretary) {
            $this->decideMonthly($allRequirementsActivity, $workshopsSecretary, DynamicWorkflowService::DECISION_APPROVED, 'Workshops review completed.');
        }

        $changesRequestedActivity = $this->upsertMonthlyActivity([
            'title' => 'Showcase Monthly Changes Requested - Branch Revision Needed',
            'date' => Carbon::create(now()->year, 8, 8),
            'branch' => $zarqaBranch,
            'creator' => $creatorZarqa,
            'agenda_event_id' => null,
            'description' => 'Changes requested case after coordinator review.',
            'requires_programs' => false,
            'requires_workshops' => false,
            'requires_communications' => false,
        ]);
        $this->resetMonthlyWorkflow($changesRequestedActivity);
        $this->decideMonthly($changesRequestedActivity, $creatorZarqa, DynamicWorkflowService::DECISION_APPROVED, 'Submitted from branch.');
        $this->decideMonthly($changesRequestedActivity, $managerZarqa, DynamicWorkflowService::DECISION_APPROVED, 'Branch manager approved.');
        $this->decideMonthly($changesRequestedActivity, $branchCoordinator, DynamicWorkflowService::DECISION_CHANGES_REQUESTED, 'Please adjust the schedule and branch resource plan.');
    }

    protected function upsertMonthlyActivity(array $data): MonthlyActivity
    {
        /** @var Carbon $date */
        $date = $data['date'];

        $activity = MonthlyActivity::query()->firstOrNew(['title' => $data['title']]);
        $activity->forceFill([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'activity_date' => $date->toDateString(),
            'title' => $data['title'],
            'proposed_date' => $date->toDateString(),
            'is_in_agenda' => filled($data['agenda_event_id']),
            'is_from_agenda' => filled($data['agenda_event_id']),
            'agenda_event_id' => $data['agenda_event_id'],
            'responsible_party' => 'Branch Relations Team',
            'description' => $data['description'],
            'location_type' => 'onsite',
            'location_details' => 'Main hall',
            'status' => 'draft',
            'execution_status' => 'planned',
            'plan_stage' => 1,
            'plan_version' => 1,
            'lifecycle_status' => 'Draft',
            'requires_programs' => (bool) $data['requires_programs'],
            'requires_workshops' => (bool) $data['requires_workshops'],
            'requires_communications' => (bool) $data['requires_communications'],
            'relations_officer_approval_status' => 'pending',
            'relations_manager_approval_status' => 'pending',
            'programs_officer_approval_status' => $data['requires_programs'] ? 'pending' : 'skipped',
            'programs_manager_approval_status' => $data['requires_programs'] ? 'pending' : 'skipped',
            'liaison_approval_status' => 'pending',
            'hq_relations_manager_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
            'branch_id' => $data['branch']->id,
            'created_by' => $data['creator']->id,
        ]);
        $activity->save();

        return $activity->fresh();
    }

    protected function resetMonthlyWorkflow(MonthlyActivity $activity): void
    {
        $activity->approvals()->delete();

        WorkflowInstance::query()
            ->where('entity_type', MonthlyActivity::class)
            ->where('entity_id', $activity->id)
            ->delete();

        $activity->update([
            'status' => 'draft',
            'relations_officer_approval_status' => 'pending',
            'relations_manager_approval_status' => 'pending',
            'programs_officer_approval_status' => (bool) $activity->requires_programs ? 'pending' : 'skipped',
            'programs_manager_approval_status' => (bool) $activity->requires_programs ? 'pending' : 'skipped',
            'liaison_approval_status' => 'pending',
            'hq_relations_manager_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
        ]);
    }

    protected function decideMonthly(MonthlyActivity $activity, User $actor, string $decision, ?string $comment = null): void
    {
        $instance = $this->dynamicWorkflowService->forModel('monthly_activities', $activity);

        if (! $instance) {
            return;
        }

        $step = $this->dynamicWorkflowService->currentStepForUser($instance, $actor);

        if (! $step) {
            return;
        }

        MonthlyActivityApproval::query()->create([
            'monthly_activity_id' => $activity->id,
            'step' => $step->step_key,
            'decision' => $decision,
            'comment' => $comment,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        $this->dynamicWorkflowService->recordDecision($instance, $step, $actor, $decision, $comment);
        $this->syncMonthlyActivityStatus($activity->fresh(), $instance->fresh(), $step->step_key, $decision);
    }

    protected function syncMonthlyActivityStatus(MonthlyActivity $activity, WorkflowInstance $instance, string $stepKey, string $decision): void
    {
        $updates = [
            'status' => match ($instance->status) {
                DynamicWorkflowService::DECISION_APPROVED => 'approved',
                DynamicWorkflowService::DECISION_REJECTED => 'rejected',
                DynamicWorkflowService::DECISION_CHANGES_REQUESTED => 'changes_requested',
                default => 'in_review',
            },
        ];

        $roleFieldMap = [
            'monthly_branch_relations_officer_submit' => 'relations_officer_approval_status',
            'monthly_branch_relations_manager_review' => 'relations_manager_approval_status',
            'monthly_programs_manager_review' => 'programs_manager_approval_status',
            'monthly_relations_manager_review' => 'hq_relations_manager_approval_status',
            'monthly_executive_manager_final_approval' => 'executive_approval_status',
        ];

        if (isset($roleFieldMap[$stepKey])) {
            $updates[$roleFieldMap[$stepKey]] = $decision;
        }

        $activity->update($updates);
    }

    protected function ensureWorkflowUser(string $email, string $name, string $role, Branch $branch): User
    {
        $phoneSuffix = str_pad((string) (abs(crc32($email)) % 10000000), 7, '0', STR_PAD_LEFT);

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => '079' . $phoneSuffix,
                'status' => 'active',
                'branch_id' => $branch->id,
                'password' => Hash::make('password'),
            ]
        );

        $user->syncRoles([$role]);

        return $user;
    }

    protected function resolveBranch(array $needles): ?Branch
    {
        $branches = Branch::query()->get();

        foreach ($branches as $branch) {
            $haystack = mb_strtolower(trim((string) $branch->name . ' ' . (string) $branch->city));

            foreach ($needles as $needle) {
                if (str_contains($haystack, mb_strtolower($needle))) {
                    return $branch;
                }
            }
        }

        return $branches->first();
    }
}
