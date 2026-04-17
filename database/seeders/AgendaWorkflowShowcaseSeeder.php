<?php

namespace Database\Seeders;

use App\Models\AgendaApproval;
use App\Models\AgendaEvent;
use App\Models\AgendaParticipation;
use App\Models\Branch;
use App\Models\Department;
use App\Models\EventCategory;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\AgendaWorkflowBridgeService;
use App\Services\DynamicWorkflowService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AgendaWorkflowShowcaseSeeder extends Seeder
{
    public function __construct(
        protected DynamicWorkflowService $dynamicWorkflowService,
        protected AgendaWorkflowBridgeService $agendaWorkflowBridgeService
    ) {
    }

    public function run(): void
    {
        $creator = User::query()->where('email', 'relations-officer@zaha.test')->first();
        $relationsManager = User::query()->where('email', 'relations-manager@zaha.test')->first();
        $executiveManager = User::query()->where('email', 'executive-manager@zaha.test')->first();
        $zarqaBranch = $this->resolveBranch(['zarqa', 'الزرقاء', 'الرصيفة']);
        $irbidBranch = $this->resolveBranch(['irbid', 'إربد', 'اربد', 'المشارع']);
        $khaldaBranch = $this->resolveBranch(['khalda', 'خلدا', 'طبربور', 'amman', 'عمان']);
        $ownerDepartment = Department::query()->orderBy('sort_order')->first();
        $partnerDepartment = Department::query()->orderBy('sort_order')->skip(1)->first();
        $category = EventCategory::query()
            ->when($ownerDepartment, fn ($query) => $query->where('department_id', $ownerDepartment->id))
            ->orderBy('sort_order')
            ->first();

        if (! $creator || ! $relationsManager || ! $executiveManager || ! $ownerDepartment) {
            return;
        }

        $year = now()->year;

        $draftEvent = $this->upsertAgendaEvent([
            'event_name' => 'Showcase Agenda Draft - Community Open Day',
            'event_date' => Carbon::create($year, 5, 12),
            'event_type' => 'optional',
            'plan_type' => 'unified',
            'notes' => 'Draft showcase case for agenda workflow preview.',
            'owner_department_id' => $ownerDepartment->id,
            'partner_department_ids' => array_filter([$partnerDepartment?->id]),
            'event_category_id' => $category?->id,
            'branches' => [
                [$zarqaBranch?->id, 'participant'],
                [$irbidBranch?->id, 'unspecified'],
            ],
            'created_by' => $creator->id,
        ]);
        $this->resetAgendaWorkflow($draftEvent);

        $submittedEvent = $this->upsertAgendaEvent([
            'event_name' => 'Showcase Agenda Submitted - Awaiting Relations Review',
            'event_date' => Carbon::create($year, 6, 5),
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'notes' => 'Submitted showcase case waiting for relations manager review.',
            'owner_department_id' => $ownerDepartment->id,
            'partner_department_ids' => array_filter([$partnerDepartment?->id]),
            'event_category_id' => $category?->id,
            'branches' => [
                [$zarqaBranch?->id, 'participant'],
                [$irbidBranch?->id, 'participant'],
            ],
            'created_by' => $creator->id,
        ]);
        $this->resetAgendaWorkflow($submittedEvent);
        $this->decideAgenda($submittedEvent, $creator, DynamicWorkflowService::DECISION_APPROVED, 'Submitted to the workflow.');

        $changesRequestedEvent = $this->upsertAgendaEvent([
            'event_name' => 'Showcase Agenda Changes Requested - Partnership Forum',
            'event_date' => Carbon::create($year, 7, 14),
            'event_type' => 'optional',
            'plan_type' => 'non_unified',
            'notes' => 'Changes requested showcase case.',
            'owner_department_id' => $ownerDepartment->id,
            'partner_department_ids' => array_filter([$partnerDepartment?->id]),
            'event_category_id' => $category?->id,
            'branches' => [
                [$zarqaBranch?->id, 'participant'],
                [$khaldaBranch?->id, 'participant'],
            ],
            'created_by' => $creator->id,
        ]);
        $this->resetAgendaWorkflow($changesRequestedEvent);
        $this->decideAgenda($changesRequestedEvent, $creator, DynamicWorkflowService::DECISION_APPROVED, 'Submitted with branch commitments.');
        $this->decideAgenda($changesRequestedEvent, $relationsManager, DynamicWorkflowService::DECISION_CHANGES_REQUESTED, 'Please refine the external partner plan and budget alignment.');

        $midFlowEvent = $this->upsertAgendaEvent([
            'event_name' => 'Showcase Agenda Mid Flow - Awaiting Executive Approval',
            'event_date' => Carbon::create($year, 8, 20),
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'notes' => 'Relations approved and awaiting executive approval.',
            'owner_department_id' => $ownerDepartment->id,
            'partner_department_ids' => array_filter([$partnerDepartment?->id]),
            'event_category_id' => $category?->id,
            'branches' => [
                [$zarqaBranch?->id, 'participant'],
                [$irbidBranch?->id, 'participant'],
                [$khaldaBranch?->id, 'participant'],
            ],
            'created_by' => $creator->id,
        ]);
        $this->resetAgendaWorkflow($midFlowEvent);
        $this->decideAgenda($midFlowEvent, $creator, DynamicWorkflowService::DECISION_APPROVED, 'Submitted for HQ approval.');
        $this->decideAgenda($midFlowEvent, $relationsManager, DynamicWorkflowService::DECISION_APPROVED, 'Relations review completed.');

        $publishedEvent = $this->upsertAgendaEvent([
            'event_name' => 'Showcase Agenda Published - National Campaign Launch',
            'event_date' => Carbon::create($year, 9, 10),
            'event_type' => 'mandatory',
            'plan_type' => 'unified',
            'notes' => 'Fully approved showcase case.',
            'owner_department_id' => $ownerDepartment->id,
            'partner_department_ids' => array_filter([$partnerDepartment?->id]),
            'event_category_id' => $category?->id,
            'branches' => [
                [$zarqaBranch?->id, 'participant'],
                [$irbidBranch?->id, 'participant'],
                [$khaldaBranch?->id, 'participant'],
            ],
            'created_by' => $creator->id,
        ]);
        $this->resetAgendaWorkflow($publishedEvent);
        $this->decideAgenda($publishedEvent, $creator, DynamicWorkflowService::DECISION_APPROVED, 'Submitted and validated.');
        $this->decideAgenda($publishedEvent, $relationsManager, DynamicWorkflowService::DECISION_APPROVED, 'Relations review completed.');
        $this->decideAgenda($publishedEvent, $executiveManager, DynamicWorkflowService::DECISION_APPROVED, 'Final executive approval granted.');
    }

    protected function upsertAgendaEvent(array $data): AgendaEvent
    {
        /** @var Carbon $eventDate */
        $eventDate = $data['event_date'];

        $event = AgendaEvent::query()->updateOrCreate(
            ['event_name' => $data['event_name']],
            [
                'event_date' => $eventDate->toDateString(),
                'event_day' => $eventDate->translatedFormat('l'),
                'month' => (int) $eventDate->format('m'),
                'day' => (int) $eventDate->format('d'),
                'department_id' => $data['owner_department_id'],
                'owner_department_id' => $data['owner_department_id'],
                'event_category_id' => $data['event_category_id'],
                'event_category' => optional(EventCategory::query()->find($data['event_category_id']))->name,
                'plan_type' => $data['plan_type'],
                'event_type' => $data['event_type'],
                'is_mandatory' => $data['event_type'] === 'mandatory',
                'is_unified' => $data['plan_type'] === 'unified',
                'status' => 'draft',
                'relations_approval_status' => 'pending',
                'executive_approval_status' => 'pending',
                'created_by' => $data['created_by'],
                'notes' => $data['notes'],
                'version' => 1,
            ]
        );

        $event->partnerDepartments()->sync($data['partner_department_ids'] ?? []);
        $event->participations()->where('entity_type', 'branch')->delete();

        foreach ($data['branches'] as [$branchId, $status]) {
            if (! $branchId) {
                continue;
            }

            AgendaParticipation::query()->create([
                'agenda_event_id' => $event->id,
                'entity_type' => 'branch',
                'entity_id' => $branchId,
                'participation_status' => $status,
                'updated_by' => $data['created_by'],
            ]);
        }

        return $event->fresh(['partnerDepartments', 'participations']);
    }

    protected function resetAgendaWorkflow(AgendaEvent $agendaEvent): void
    {
        $agendaEvent->approvals()->delete();

        WorkflowInstance::query()
            ->where('entity_type', AgendaEvent::class)
            ->where('entity_id', $agendaEvent->id)
            ->delete();

        $agendaEvent->update([
            'status' => 'draft',
            'relations_approval_status' => 'pending',
            'executive_approval_status' => 'pending',
            'approved_by_relations_at' => null,
            'approved_by_executive_at' => null,
        ]);
    }

    protected function decideAgenda(AgendaEvent $agendaEvent, User $actor, string $decision, ?string $comment = null): void
    {
        $instance = $this->dynamicWorkflowService->forModel('agenda', $agendaEvent);

        if (! $instance) {
            return;
        }

        $step = $this->dynamicWorkflowService->currentStepForUser($instance, $actor)
            ?? $this->dynamicWorkflowService->currentStep($instance);

        if (! $step) {
            return;
        }

        AgendaApproval::query()->create([
            'agenda_event_id' => $agendaEvent->id,
            'step' => $step->step_key,
            'decision' => $decision,
            'comment' => $comment,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        $this->dynamicWorkflowService->recordDecision($instance, $step, $actor, $decision, $comment);
        $this->agendaWorkflowBridgeService->syncApprovalState($agendaEvent, $instance->fresh());
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
