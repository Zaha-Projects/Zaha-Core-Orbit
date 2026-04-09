<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\MonthlyActivityChangeLog;
use App\Models\MonthlyActivityPartner;
use App\Models\MonthlyActivitySponsor;
use App\Models\MonthlyActivity;
use App\Models\MonthlyActivitySupply;
use App\Models\MonthlyActivityTeam;
use App\Models\WorkflowInstance;
use App\Models\EvaluationQuestion;
use App\Models\MonthlyActivityFollowup;
use App\Models\MonthlyActivityEvaluationResponse;
use App\Models\TargetGroup;
use App\Models\Setting;
use App\Models\WorkflowActionLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\ConflictDetectionService;
use App\Services\NotificationService;
use App\Services\MonthlyActivityWorkflowService;
use App\Services\MonthlyActivityLifecycleService;
use App\Services\DynamicWorkflowService;
use Illuminate\Support\Facades\Storage;

class MonthlyActivitiesController extends Controller
{
    protected function isApprovedVersion(MonthlyActivity $monthlyActivity): bool
    {
        $workflowInstance = WorkflowInstance::query()
            ->where('entity_type', MonthlyActivity::class)
            ->where('entity_id', $monthlyActivity->id)
            ->latest('id')
            ->first();

        return $workflowInstance?->status === 'approved'
            || $monthlyActivity->status === 'approved'
            || $monthlyActivity->executive_approval_status === 'approved'
            || $monthlyActivity->lifecycle_status === 'Exec Director Approved';
    }

    protected function syncTargetGroups(MonthlyActivity $monthlyActivity, array $data): void
    {
        $ids = collect($data['target_group_ids'] ?? [])
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $monthlyActivity->targetGroups()->sync(collect($ids)->mapWithKeys(fn ($id) => [$id => ['custom_text' => null]])->all());
    }

    protected function shouldScopeToUserBranch(?User $user): bool
    {
        return $user !== null
            && method_exists($user, 'hasBranchScopedMonthlyVisibility')
            && $user->hasBranchScopedMonthlyVisibility()
            && ! empty($user->branch_id);
    }

    protected function applyBranchVisibilityScope($query, ?User $user)
    {
        if ($this->shouldScopeToUserBranch($user)) {
            $query->where('branch_id', $user->branch_id);
        }

        return $query;
    }

    protected function ensureActivityVisibleToUser(MonthlyActivity $monthlyActivity, User $user): void
    {
        if ($this->shouldScopeToUserBranch($user) && (int) $monthlyActivity->branch_id !== (int) $user->branch_id) {
            abort(403);
        }
    }

    protected function monthlyLockDays(): int
    {
        return max(0, (int) Setting::valueOf('monthly_plan_lock_days', '5'));
    }

    protected function buildLockAt(string $proposedDate): ?Carbon
    {
        return Carbon::parse($proposedDate)->subDays($this->monthlyLockDays())->endOfDay();
    }

    protected function isLocked(MonthlyActivity $monthlyActivity): bool
    {
        return $monthlyActivity->lock_at !== null && now()->greaterThanOrEqualTo($monthlyActivity->lock_at);
    }

    protected function logChanges(MonthlyActivity $monthlyActivity, array $oldValues, array $newValues, int $userId): void
    {
        foreach ($newValues as $field => $newValue) {
            $oldValue = $oldValues[$field] ?? null;
            if ((string) $oldValue === (string) $newValue) {
                continue;
            }

            MonthlyActivityChangeLog::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'changed_by' => $userId,
                'field_name' => $field,
                'old_value' => $oldValue !== null ? (string) $oldValue : null,
                'new_value' => $newValue !== null ? (string) $newValue : null,
                'changed_at' => now(),
            ]);
        }
    }

    protected function syncSponsorsAndPartners(MonthlyActivity $monthlyActivity, array $data): void
    {
        $monthlyActivity->sponsors()->delete();
        foreach (($data['sponsors'] ?? []) as $sponsor) {
            $name = trim((string) ($sponsor['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            MonthlyActivitySponsor::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'name' => $name,
                'title' => $sponsor['title'] ?? null,
                'is_official' => (bool) ($sponsor['is_official'] ?? true),
            ]);
        }

        $monthlyActivity->partners()->delete();
        $seen = [];
        foreach (($data['partners'] ?? []) as $index => $partner) {
            $name = trim((string) ($partner['name'] ?? ''));
            if ($name === '' || in_array(mb_strtolower($name), $seen, true)) {
                continue;
            }

            $seen[] = mb_strtolower($name);

            MonthlyActivityPartner::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'name' => $name,
                'role' => $partner['role'] ?? null,
                'contact_info' => $partner['contact_info'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function logWorkflowAction(string $actionType, MonthlyActivity $monthlyActivity, Request $request, ?string $status = null, ?array $meta = null): void
    {
        WorkflowActionLog::create([
            'module' => 'monthly_activities',
            'entity_type' => MonthlyActivity::class,
            'entity_id' => $monthlyActivity->id,
            'action_type' => $actionType,
            'status' => $status,
            'performed_by' => $request->user()->id,
            'meta' => $meta,
            'performed_at' => now(),
        ]);
    }


    protected function syncEvaluationData(MonthlyActivity $monthlyActivity, array $data, int $userId): void
    {
        $monthlyActivity->evaluationResponses()->delete();
        foreach (($data['evaluations'] ?? []) as $questionId => $payload) {
            if (empty($payload['score']) && empty($payload['answer_value']) && empty($payload['note'])) {
                continue;
            }

            MonthlyActivityEvaluationResponse::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'evaluation_question_id' => (int) $questionId,
                'score' => $payload['score'] ?? null,
                'answer_value' => $payload['answer_value'] ?? null,
                'note' => $payload['note'] ?? null,
                'created_by' => $userId,
            ]);
        }

        if (! empty($data['followup_remarks'])) {
            MonthlyActivityFollowup::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'remarks' => $data['followup_remarks'],
                'created_by' => $userId,
            ]);
        }
    }

    protected function canSubmitPostEvaluation(MonthlyActivity $monthlyActivity): bool
    {
        return in_array($monthlyActivity->status, ['executed', 'completed', 'closed'], true)
            || ! empty($monthlyActivity->actual_date)
            || in_array((string) $monthlyActivity->lifecycle_status, ['Executed', 'Evaluated', 'Closed'], true);
    }

    public function index(Request $request)
    {
        $activitiesQuery = MonthlyActivity::with([
            'branch',
                        'agendaEvent',
            'creator',
            'workflowInstance.workflow.steps.role',
            'workflowInstance.logs.step',
        ])
            ->enterpriseFilter($request->all())
            ->notArchived();

        $this->applyBranchVisibilityScope($activitiesQuery, $request->user());

        $activities = $activitiesQuery
            ->orderBy('month')
            ->orderBy('day')
            ->paginate(15)
            ->withQueryString();

        $branches = Branch::query()->orderBy('name');
                if ($this->shouldScopeToUserBranch($request->user())) {
            $branches->where('id', $request->user()->branch_id);
        }
        $branches = $branches->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();

        return view('pages.monthly_activities.activities.index', compact('activities', 'branches', 'agendaEvents'));
    }

    public function create()
    {
        $user = request()->user();
        $branches = Branch::query()->orderBy('name');
                if ($this->shouldScopeToUserBranch($user)) {
            $branches->where('id', $user->branch_id);
        }
        $branches = $branches->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();
        $targetGroups = TargetGroup::where('is_active', true)->orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::where('is_active', true)->orderBy('sort_order')->get();

        return view('pages.monthly_activities.activities.create', compact('branches', 'agendaEvents', 'targetGroups', 'evaluationQuestions'));
    }

    public function syncFromAgenda(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['nullable'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        if ($this->shouldScopeToUserBranch($request->user()) && (int) $data['branch_id'] !== (int) $request->user()->branch_id) {
            abort(403);
        }

        $events = AgendaEvent::query()
            ->where(function ($query) use ($data) {
                $query->where(function ($q) use ($data) {
                    $q->whereNotNull('event_date')
                        ->whereMonth('event_date', $data['month'])
                        ->whereYear('event_date', $data['year']);
                })->orWhere(function ($q) use ($data) {
                    $q->whereNull('event_date')
                        ->where('month', $data['month']);
                });
            })
            ->whereIn('status', ['relations_approved', 'published'])
            ->where(function ($query) use ($data) {
                $query->where('event_type', 'mandatory')
                    ->orWhereHas('participations', function ($participationQuery) use ($data) {
                        $participationQuery
                            ->where('entity_type', 'branch')
                            ->where('entity_id', $data['branch_id'])
                            ->where('participation_status', 'participant');
                    });
            })
            ->get();

        $created = 0;
        foreach ($events as $event) {
            $exists = MonthlyActivity::query()
                ->where('agenda_event_id', $event->id)
                ->where('branch_id', $data['branch_id'])
                ->exists();

            if ($exists) {
                continue;
            }

            MonthlyActivity::create([
                'month' => (int) $event->month,
                'day' => (int) $event->day,
                'title' => $event->event_name,
                'proposed_date' => optional($event->event_date)?->toDateString() ?? Carbon::create($data['year'], $event->month, $event->day)->toDateString(),
                'is_in_agenda' => true,
                'is_from_agenda' => true,
                'agenda_event_id' => $event->id,
                'participation_status' => $event->event_type === 'optional' ? 'unspecified' : 'participant',
                'plan_type' => $event->plan_type ?? 'non_unified',
                'description' => $event->notes,
                'location_type' => 'inside_center',
                'location_details' => null,
                'status' => 'draft',
                'lock_at' => $this->buildLockAt(optional($event->event_date)?->toDateString() ?? Carbon::create($data['year'], $event->month, $event->day)->toDateString()),
                'is_official' => false,
                'branch_id' => (int) $data['branch_id'],
                'center_id' => null,
                'created_by' => $request->user()->id,
            ]);

            $created++;
        }

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.sync.done', ['count' => $created]));
    }

    public function store(Request $request, ConflictDetectionService $conflicts, MonthlyActivityWorkflowService $workflowService)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['nullable'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
            'is_in_agenda' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'max:50'],
            'responsible_party' => ['nullable', 'string', 'max:255'],

            'location_type' => ['required', 'in:inside_center,outside_center'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'internal_location' => ['nullable', 'string', 'max:255', 'required_if:location_type,inside_center'],
            'outside_place_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'outside_google_maps_url' => ['nullable', 'url', 'max:500', 'required_if:location_type,outside_center'],
            'outside_contact_number' => ['nullable', 'string', 'max:50'],
            'outside_address' => ['nullable', 'string'],
            'execution_time' => ['nullable', 'string', 'max:255'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_to' => ['nullable', 'date_format:H:i', 'after:time_from'],
            'target_group' => ['nullable', 'string', 'max:255'],
            'target_group_id' => ['nullable', 'exists:target_groups,id'],
            'target_group_ids' => ['nullable', 'array'],
            'target_group_ids.*' => ['nullable', 'integer', 'exists:target_groups,id'],
            'target_group_other' => ['nullable', 'string', 'max:255', 'required_if:target_group,other'],
            'short_description' => ['nullable', 'string'],
            'volunteer_need' => ['nullable', 'string', 'max:255'],
            'needs_volunteers' => ['nullable', 'boolean'],
            'required_volunteers' => ['nullable', 'integer', 'min:1', 'required_if:needs_volunteers,1'],
            'expected_attendance' => ['nullable', 'integer', 'min:0'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'attendance_notes' => ['nullable', 'string'],
            'work_teams_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'needs_media_coverage' => ['nullable', 'boolean'],
            'media_coverage_notes' => ['nullable', 'string'],
            'requires_programs' => ['nullable', 'boolean'],
            'requires_workshops' => ['nullable', 'boolean'],
            'requires_communications' => ['nullable', 'boolean'],
            'responsible_entities' => ['nullable', 'array'],
            'responsible_entities.*' => ['in:relations,programs'],
            'is_program_related' => ['nullable', 'boolean'],
            'participation_status' => ['nullable', 'in:participant,not_participant,unspecified'],
            'branch_plan_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'needs_official_correspondence' => ['nullable', 'boolean'],
            'official_correspondence_reason' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_target' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'has_sponsor' => ['nullable', 'boolean'],
            'sponsor_name_title' => ['nullable', 'string', 'max:255'],
            'has_partners' => ['nullable', 'boolean'],
            'partner_1_name' => ['nullable', 'string', 'max:255'],
            'partner_1_role' => ['nullable', 'string', 'max:255'],
            'partner_2_name' => ['nullable', 'string', 'max:255'],
            'partner_2_role' => ['nullable', 'string', 'max:255'],
            'partner_3_name' => ['nullable', 'string', 'max:255'],
            'partner_3_role' => ['nullable', 'string', 'max:255'],
            'needs_official_letters' => ['nullable', 'boolean'],
            'letter_purpose' => ['nullable', 'string', 'max:255'],
            'rescheduled_date' => ['nullable', 'date'],
            'reschedule_reason' => ['nullable', 'string'],
            'relations_approval_on_reschedule' => ['nullable', 'boolean'],
            'audience_satisfaction_percent' => ['nullable', 'numeric', 'between:0,100'],
            'evaluation_score' => ['nullable', 'numeric', 'between:0,100'],
            'sponsors' => ['array'],
            'sponsors.*.name' => ['nullable', 'string', 'max:255'],
            'sponsors.*.title' => ['nullable', 'string', 'max:255'],
            'sponsors.*.is_official' => ['nullable', 'boolean'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:255'],
            'partners.*.role' => ['required_with:partners.*.name', 'string', 'max:255'],
            'partners.*.contact_info' => ['nullable', 'string', 'max:255'],
            'team_members' => ['nullable', 'array'],
            'team_members.*.team_name' => ['nullable', 'string', 'max:255'],
            'team_members.*.member_name' => ['nullable', 'string', 'max:255'],
            'team_members.*.member_email' => ['nullable', 'email', 'max:255'],
            'team_members.*.role_desc' => ['nullable', 'string', 'max:255'],
            'team_groups' => ['nullable', 'array'],
            'team_groups.*.team_name' => ['nullable', 'string', 'max:255'],
            'team_groups.*.members' => ['nullable', 'array'],
            'team_groups.*.members.*.member_name' => ['nullable', 'string', 'max:255'],
            'team_groups.*.members.*.role_desc' => ['nullable', 'string', 'max:255'],
            'requires_supplies' => ['nullable', 'boolean'],
            'supplies' => ['nullable', 'array'],
            'supplies.*.item_name' => ['nullable', 'string', 'max:255'],
            'supplies.*.available' => ['nullable', 'boolean'],
            'supplies.*.provider_type' => ['nullable', 'string', 'max:255'],
            'supplies.*.provider_name' => ['nullable', 'string', 'max:255'],
            'evaluations' => ['nullable', 'array'],
            'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
            'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
            'evaluations.*.note' => ['nullable', 'string'],
            'followup_remarks' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        if ($this->shouldScopeToUserBranch($request->user()) && (int) $data['branch_id'] !== (int) $request->user()->branch_id) {
            abort(403);
        }

        if (($data['location_type'] ?? null) === 'inside_center') {
            $data['outside_place_name'] = null;
            $data['outside_google_maps_url'] = null;
            $data['outside_address'] = null;
        } else {
            $data['internal_location'] = null;
        }

        $date = Carbon::parse($data['activity_date']);
        $conflictNames = $conflicts->findMonthlyActivityConflicts($data['proposed_date'], (int) $data['branch_id'], null, $data['execution_time'] ?? null);
        $conflictWarning = empty($conflictNames) ? null : __('Potential overlap with: :activities', ['activities' => implode(', ', $conflictNames)]);
        $isFromAgenda = ! empty($data['agenda_event_id']);
        $planType = $isFromAgenda ? optional(AgendaEvent::find($data['agenda_event_id']))->plan_type : 'non_unified';

        $monthlyActivity = MonthlyActivity::create([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'proposed_date' => $data['proposed_date'],
            'is_in_agenda' => (bool) ($data['is_in_agenda'] ?? !empty($data['agenda_event_id'])),
            'is_from_agenda' => $isFromAgenda,
            'agenda_event_id' => $data['agenda_event_id'] ?? null,
            'participation_status' => $data['participation_status'] ?? 'unspecified',
            'plan_type' => $planType ?? 'non_unified',
            'branch_plan_file' => $request->file('branch_plan_file')?->store('monthly/plans', 'public'),
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_contact_number' => $data['outside_contact_number'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'status' => $data['status'],
            'plan_stage' => 1,
            'plan_version' => 1,
            'previous_version_id' => null,
            'responsible_party' => $data['responsible_party'] ?? null,
            'execution_time' => $data['execution_time'] ?? null,
            'time_from' => $data['time_from'] ?? null,
            'time_to' => $data['time_to'] ?? null,
            'target_group' => $data['target_group'] ?? null,
            'target_group_id' => $data['target_group_id'] ?? ($data['target_group_ids'][0] ?? null),
            'target_group_other' => $data['target_group_other'] ?? null,
            'target_group_id' => $data['target_group_id'] ?? null,
            'target_group_other' => $data['target_group_other'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'work_teams_count' => $data['work_teams_count'] ?? null,
            'work_teams_count' => $data['work_teams_count'] ?? null,
            'volunteer_need' => $data['volunteer_need'] ?? null,
            'needs_volunteers' => (bool) ($data['needs_volunteers'] ?? false),
            'required_volunteers' => $data['required_volunteers'] ?? null,
            'expected_attendance' => $data['expected_attendance'] ?? null,
            'actual_attendance' => $data['actual_attendance'] ?? null,
            'attendance_notes' => $data['attendance_notes'] ?? null,
            'needs_volunteers' => (bool) ($data['needs_volunteers'] ?? false),
            'required_volunteers' => $data['required_volunteers'] ?? null,
            'expected_attendance' => $data['expected_attendance'] ?? null,
            'actual_attendance' => $data['actual_attendance'] ?? null,
            'attendance_notes' => $data['attendance_notes'] ?? null,
            'has_sponsor' => (bool) (($data['has_sponsor'] ?? false) || !empty($data['sponsors'] ?? [])),
            'sponsor_name_title' => $data['sponsor_name_title'] ?? null,
            'has_partners' => (bool) (($data['has_partners'] ?? false) || !empty($data['partners'] ?? [])),
            'needs_official_letters' => (bool) ($data['needs_official_letters'] ?? false),
            'needs_official_correspondence' => (bool) ($data['needs_official_correspondence'] ?? false),
            'official_correspondence_reason' => $data['official_correspondence_reason'] ?? null,
            'official_correspondence_target' => $data['official_correspondence_target'] ?? null,
            'needs_official_correspondence' => (bool) ($data['needs_official_correspondence'] ?? false),
            'official_correspondence_reason' => $data['official_correspondence_reason'] ?? null,
            'letter_purpose' => $data['letter_purpose'] ?? null,
            'rescheduled_date' => $data['rescheduled_date'] ?? null,
            'reschedule_reason' => $data['reschedule_reason'] ?? null,
            'relations_approval_on_reschedule' => (bool) ($data['relations_approval_on_reschedule'] ?? false),
            'audience_satisfaction_percent' => $data['audience_satisfaction_percent'] ?? null,
            'evaluation_score' => $data['evaluation_score'] ?? null,
            'needs_media_coverage' => (bool) ($data['needs_media_coverage'] ?? false),
            'media_coverage_notes' => $data['media_coverage_notes'] ?? null,
            'needs_media_coverage' => (bool) ($data['needs_media_coverage'] ?? false),
            'media_coverage_notes' => $data['media_coverage_notes'] ?? null,
            'requires_programs' => (bool) (($data['requires_programs'] ?? false) || in_array('programs', $data['responsible_entities'] ?? [], true)),
            'is_program_related' => (bool) ($data['is_program_related'] ?? false),
            'requires_workshops' => (bool) ($data['requires_workshops'] ?? false),
            'requires_communications' => (bool) (($data['requires_communications'] ?? false) || in_array('relations', $data['responsible_entities'] ?? [], true)),
            'lock_at' => $this->buildLockAt($data['proposed_date']),
            'is_official' => false,
            'branch_id' => $data['branch_id'],
            'center_id' => null,
            'created_by' => $request->user()->id,
        ]);

        $workflowService->initializeDynamicStatuses($monthlyActivity);
        $this->syncTargetGroups($monthlyActivity, $data);

        $this->syncSponsorsAndPartners($monthlyActivity, $data);
        foreach (($data['team_groups'] ?? []) as $groupIndex => $group) {
            $teamName = trim((string) ($group['team_name'] ?? '')) ?: 'فريق '.((int) $groupIndex + 1);
            foreach (($group['members'] ?? []) as $member) {
                $memberName = trim((string) ($member['member_name'] ?? ''));
                if ($memberName === '') {
                    continue;
                }
                MonthlyActivityTeam::create([
                    'monthly_activity_id' => $monthlyActivity->id,
                    'team_name' => $teamName,
                    'member_name' => $memberName,
                    'member_email' => null,
                    'role_desc' => $member['role_desc'] ?? null,
                ]);
            }
        }
        foreach (($data['team_members'] ?? []) as $member) {
            $memberName = trim((string) ($member['member_name'] ?? ''));
            if ($memberName === '') {
                continue;
            }
            MonthlyActivityTeam::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'team_name' => $member['team_name'] ?? null,
                'member_name' => $memberName,
                'member_email' => $member['member_email'] ?? null,
                'role_desc' => $member['role_desc'] ?? null,
            ]);
        }
        foreach (($data['supplies'] ?? []) as $supply) {
            $itemName = trim((string) ($supply['item_name'] ?? ''));
            if ($itemName === '') {
                continue;
            }
            $available = (bool) ($supply['available'] ?? false);
            MonthlyActivitySupply::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'item_name' => $itemName,
                'available' => $available,
                'status' => $available ? 'available' : 'needed',
                'provider_type' => $available ? null : ($supply['provider_type'] ?? null),
                'provider_name' => $available ? null : ($supply['provider_name'] ?? null),
            ]);
        }
        if ($request->user()->hasRole('followup_officer') || $request->user()->hasRole('super_admin')) {
            $this->syncEvaluationData($monthlyActivity, $data, $request->user()->id);
        }
        $this->logWorkflowAction('created', $monthlyActivity, $request, $monthlyActivity->status);

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.created'))
            ->with('warning', $conflictWarning);
    }

    public function edit(MonthlyActivity $monthlyActivity)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());

        $monthlyActivity->load(['supplies', 'team', 'attachments', 'approvals', 'sponsors', 'partners', 'evaluationResponses.question', 'followups']);
        $branches = Branch::orderBy('name')->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();
        $targetGroups = TargetGroup::where('is_active', true)->orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::where('is_active', true)->orderBy('sort_order')->get();

        return view('pages.monthly_activities.activities.edit', compact('monthlyActivity', 'branches', 'agendaEvents', 'targetGroups', 'evaluationQuestions'));
    }

    public function show(MonthlyActivity $monthlyActivity)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());

        $monthlyActivity->load(['branch', 'creator', 'agendaEvent', 'sponsors', 'partners', 'supplies', 'team', 'targetGroups']);

        return view('pages.monthly_activities.activities.show', compact('monthlyActivity'));
    }

    public function update(Request $request, MonthlyActivity $monthlyActivity, ConflictDetectionService $conflicts, MonthlyActivityWorkflowService $workflowService)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, $request->user());

        if ($request->user()->hasRole('followup_officer') && ! $request->user()->hasRole('super_admin')) {
            abort_unless($this->canSubmitPostEvaluation($monthlyActivity), 422, 'التقييم متاح فقط بعد تنفيذ الفعالية.');

            $data = $request->validate([
                'evaluations' => ['nullable', 'array'],
                'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
                'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
                'evaluations.*.note' => ['nullable', 'string'],
                'followup_remarks' => ['nullable', 'string'],
            ]);

            $this->syncEvaluationData($monthlyActivity, $data, $request->user()->id);
            $this->logWorkflowAction('evaluation_submitted', $monthlyActivity, $request, $monthlyActivity->status);

            return redirect()
                ->route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'mode' => 'post'])
                ->with('status', 'تم حفظ متابعة وتقييم الفعالية بنجاح.');
        }

        $isCreator = (int) $monthlyActivity->created_by === (int) $request->user()->id;

        if ($this->isLocked($monthlyActivity) && ! $request->user()->hasRole('super_admin') && ! $isCreator) {
            return back()->withErrors(['status' => __('app.roles.programs.monthly_activities.errors.locked')]);
        }

        if ($request->user()->hasRole('programs_officer') && $monthlyActivity->executive_approval_status === 'approved' && ! $isCreator) {
            return back()->withErrors(['status' => 'لا يمكن تعديل الفعالية بعد الاعتماد التنفيذي النهائي.']);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['nullable'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
            'is_in_agenda' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'max:50'],
            'responsible_party' => ['nullable', 'string', 'max:255'],

            'location_type' => ['required', 'in:inside_center,outside_center'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'internal_location' => ['nullable', 'string', 'max:255', 'required_if:location_type,inside_center'],
            'outside_place_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'outside_google_maps_url' => ['nullable', 'url', 'max:500', 'required_if:location_type,outside_center'],
            'outside_address' => ['nullable', 'string'],
            'execution_time' => ['nullable', 'string', 'max:255'],
            'target_group' => ['nullable', 'string', 'max:255'],
            'target_group_id' => ['nullable', 'exists:target_groups,id'],
            'target_group_other' => ['nullable', 'string', 'max:255', 'required_if:target_group,other'],
            'short_description' => ['nullable', 'string'],
            'volunteer_need' => ['nullable', 'string', 'max:255'],
            'needs_volunteers' => ['nullable', 'boolean'],
            'required_volunteers' => ['nullable', 'integer', 'min:1', 'required_if:needs_volunteers,1'],
            'expected_attendance' => ['nullable', 'integer', 'min:0'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'attendance_notes' => ['nullable', 'string'],
            'work_teams_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'needs_media_coverage' => ['nullable', 'boolean'],
            'media_coverage_notes' => ['nullable', 'string'],
            'requires_programs' => ['nullable', 'boolean'],
            'requires_workshops' => ['nullable', 'boolean'],
            'requires_communications' => ['nullable', 'boolean'],
            'is_program_related' => ['nullable', 'boolean'],
            'participation_status' => ['nullable', 'in:participant,not_participant,unspecified'],
            'branch_plan_file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'needs_official_correspondence' => ['nullable', 'boolean'],
            'official_correspondence_reason' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_target' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'has_sponsor' => ['nullable', 'boolean'],
            'sponsor_name_title' => ['nullable', 'string', 'max:255'],
            'has_partners' => ['nullable', 'boolean'],
            'partner_1_name' => ['nullable', 'string', 'max:255'],
            'partner_1_role' => ['nullable', 'string', 'max:255'],
            'partner_2_name' => ['nullable', 'string', 'max:255'],
            'partner_2_role' => ['nullable', 'string', 'max:255'],
            'partner_3_name' => ['nullable', 'string', 'max:255'],
            'partner_3_role' => ['nullable', 'string', 'max:255'],
            'needs_official_letters' => ['nullable', 'boolean'],
            'letter_purpose' => ['nullable', 'string', 'max:255'],
            'rescheduled_date' => ['nullable', 'date'],
            'reschedule_reason' => ['nullable', 'string'],
            'relations_approval_on_reschedule' => ['nullable', 'boolean'],
            'audience_satisfaction_percent' => ['nullable', 'numeric', 'between:0,100'],
            'evaluation_score' => ['nullable', 'numeric', 'between:0,100'],
            'sponsors' => ['array'],
            'sponsors.*.name' => ['nullable', 'string', 'max:255'],
            'sponsors.*.title' => ['nullable', 'string', 'max:255'],
            'sponsors.*.is_official' => ['nullable', 'boolean'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:255'],
            'partners.*.role' => ['required_with:partners.*.name', 'string', 'max:255'],
            'partners.*.contact_info' => ['nullable', 'string', 'max:255'],
            'evaluations' => ['nullable', 'array'],
            'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
            'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
            'evaluations.*.note' => ['nullable', 'string'],
            'followup_remarks' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        if ($this->shouldScopeToUserBranch($request->user()) && (int) $data['branch_id'] !== (int) $request->user()->branch_id) {
            abort(403);
        }

        if (($data['location_type'] ?? null) === 'inside_center') {
            $data['outside_place_name'] = null;
            $data['outside_google_maps_url'] = null;
            $data['outside_address'] = null;
        } else {
            $data['internal_location'] = null;
        }

        $date = Carbon::parse($data['activity_date']);
        $conflictNames = $conflicts->findMonthlyActivityConflicts($data['proposed_date'], (int) $data['branch_id'], $monthlyActivity->id, $data['execution_time'] ?? null);
        $conflictWarning = empty($conflictNames) ? null : __('Potential overlap with: :activities', ['activities' => implode(', ', $conflictNames)]);
        $isFromAgenda = ! empty($data['agenda_event_id']);
        $planType = $isFromAgenda ? optional(AgendaEvent::find($data['agenda_event_id']))->plan_type : 'non_unified';
        $branchPlanFile = $monthlyActivity->branch_plan_file;
        if ($request->hasFile('branch_plan_file')) {
            if ($branchPlanFile) {
                Storage::disk('public')->delete($branchPlanFile);
            }
            $branchPlanFile = $request->file('branch_plan_file')->store('monthly/plans', 'public');
        }

        $oldValues = $monthlyActivity->only([
            'title',
            'proposed_date',
            'agenda_event_id',
            'is_in_agenda',
            'description',
            'location_type',
            'location_details',
            'internal_location',
            'outside_place_name',
            'outside_google_maps_url',
            'outside_address',
            'status',
            'plan_stage',
            'plan_version',
            'responsible_party',
            'execution_time',
            'target_group',
            'target_group_id',
            'target_group_other',
            'short_description',
            'work_teams_count',
            'volunteer_need',
            'needs_volunteers',
            'required_volunteers',
            'expected_attendance',
            'actual_attendance',
            'attendance_notes',
            'has_sponsor',
            'sponsor_name_title',
            'has_partners',
            'partner_1_name',
            'partner_1_role',
            'partner_2_name',
            'partner_2_role',
            'partner_3_name',
            'partner_3_role',
            'needs_official_letters',
            'needs_official_correspondence',
            'official_correspondence_reason',
            'official_correspondence_target',
            'letter_purpose',
            'rescheduled_date',
            'reschedule_reason',
            'relations_approval_on_reschedule',
            'audience_satisfaction_percent',
            'evaluation_score',
            'requires_programs',
            'requires_workshops',
            'requires_communications',
            'is_program_related',
            'participation_status',
            'plan_type',
            'branch_plan_file',
            'branch_id',
            'month',
            'day',
            'lifecycle_status',
            'relations_officer_approval_status',
            'relations_manager_approval_status',
            'programs_officer_approval_status',
            'programs_manager_approval_status',
            'liaison_approval_status',
            'hq_relations_manager_approval_status',
            'executive_approval_status',
        ]);

        $isRescheduled = ($data['status'] ?? null) === 'postponed'
            || (
                ! empty($data['rescheduled_date'])
                && optional($monthlyActivity->rescheduled_date)?->toDateString() !== (string) $data['rescheduled_date']
            );
        $startsNewVersion = $this->isApprovedVersion($monthlyActivity) || $isRescheduled;
        $nextStage = (int) ($monthlyActivity->plan_stage ?: 1);
        $nextVersion = (int) ($monthlyActivity->plan_version ?: 1);
        $newStatus = $data['status'];
        $newLifecycleStatus = $monthlyActivity->lifecycle_status ?: 'Draft';

        if ($startsNewVersion) {
            $nextStage++;
            $nextVersion++;
            $newStatus = 'draft';
            $newLifecycleStatus = 'Draft';
        }

        $newValues = [
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'proposed_date' => $data['proposed_date'],
            'agenda_event_id' => $data['agenda_event_id'] ?? null,
            'is_in_agenda' => (bool) ($data['is_in_agenda'] ?? $isFromAgenda),
            'is_from_agenda' => $isFromAgenda,
            'participation_status' => $data['participation_status'] ?? 'unspecified',
            'plan_type' => $planType ?? 'non_unified',
            'branch_plan_file' => $branchPlanFile,
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'status' => $newStatus,
            'plan_stage' => $nextStage,
            'plan_version' => $nextVersion,
            'previous_version_id' => $startsNewVersion ? $monthlyActivity->id : $monthlyActivity->previous_version_id,
            'responsible_party' => $data['responsible_party'] ?? null,
            'execution_time' => $data['execution_time'] ?? null,
            'target_group' => $data['target_group'] ?? null,
            'target_group_id' => $data['target_group_id'] ?? null,
            'target_group_other' => $data['target_group_other'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'work_teams_count' => $data['work_teams_count'] ?? null,
            'volunteer_need' => $data['volunteer_need'] ?? null,
            'needs_volunteers' => (bool) ($data['needs_volunteers'] ?? false),
            'required_volunteers' => $data['required_volunteers'] ?? null,
            'expected_attendance' => $data['expected_attendance'] ?? null,
            'actual_attendance' => $data['actual_attendance'] ?? null,
            'attendance_notes' => $data['attendance_notes'] ?? null,
            'has_sponsor' => (bool) (($data['has_sponsor'] ?? false) || !empty($data['sponsors'] ?? [])),
            'sponsor_name_title' => $data['sponsor_name_title'] ?? null,
            'has_partners' => (bool) (($data['has_partners'] ?? false) || !empty($data['partners'] ?? [])),
            'needs_official_letters' => (bool) ($data['needs_official_letters'] ?? false),
            'needs_official_correspondence' => (bool) ($data['needs_official_correspondence'] ?? false),
            'official_correspondence_reason' => $data['official_correspondence_reason'] ?? null,
            'official_correspondence_target' => $data['official_correspondence_target'] ?? null,
            'letter_purpose' => $data['letter_purpose'] ?? null,
            'rescheduled_date' => $data['rescheduled_date'] ?? null,
            'reschedule_reason' => $data['reschedule_reason'] ?? null,
            'relations_approval_on_reschedule' => (bool) ($data['relations_approval_on_reschedule'] ?? false),
            'audience_satisfaction_percent' => $data['audience_satisfaction_percent'] ?? null,
            'evaluation_score' => $data['evaluation_score'] ?? null,
            'needs_media_coverage' => (bool) ($data['needs_media_coverage'] ?? false),
            'media_coverage_notes' => $data['media_coverage_notes'] ?? null,
            'requires_programs' => (bool) ($data['requires_programs'] ?? false),
            'is_program_related' => (bool) ($data['is_program_related'] ?? false),
            'requires_workshops' => (bool) ($data['requires_workshops'] ?? false),
            'requires_communications' => (bool) ($data['requires_communications'] ?? false),
            'branch_id' => $data['branch_id'],
            'center_id' => null,
            'lifecycle_status' => $newLifecycleStatus,
            'relations_officer_approval_status' => $startsNewVersion ? 'pending' : $monthlyActivity->relations_officer_approval_status,
            'relations_manager_approval_status' => $startsNewVersion ? 'pending' : $monthlyActivity->relations_manager_approval_status,
            'programs_officer_approval_status' => $startsNewVersion ? 'pending' : $monthlyActivity->programs_officer_approval_status,
            'programs_manager_approval_status' => $startsNewVersion ? 'pending' : $monthlyActivity->programs_manager_approval_status,
            'liaison_approval_status' => $startsNewVersion ? 'pending' : $monthlyActivity->liaison_approval_status,
            'hq_relations_manager_approval_status' => $startsNewVersion ? 'pending' : $monthlyActivity->hq_relations_manager_approval_status,
            'executive_approval_status' => $startsNewVersion ? 'pending' : $monthlyActivity->executive_approval_status,
        ];

        $activityToSave = $monthlyActivity;

        if ($startsNewVersion) {
            $activityToSave = MonthlyActivity::create([
                'month' => $newValues['month'],
                'day' => $newValues['day'],
                'title' => $newValues['title'],
                'proposed_date' => $newValues['proposed_date'],
                'is_in_agenda' => $newValues['is_in_agenda'],
                'agenda_event_id' => $newValues['agenda_event_id'],
                'is_from_agenda' => $newValues['is_from_agenda'],
                'participation_status' => $newValues['participation_status'],
                'plan_type' => $newValues['plan_type'],
                'branch_plan_file' => $newValues['branch_plan_file'],
                'description' => $newValues['description'],
                'location_type' => $newValues['location_type'],
                'location_details' => $newValues['location_details'],
                'internal_location' => $newValues['internal_location'],
                'outside_place_name' => $newValues['outside_place_name'],
                'outside_google_maps_url' => $newValues['outside_google_maps_url'],
                'outside_address' => $newValues['outside_address'],
                'status' => $newValues['status'],
                'plan_stage' => $newValues['plan_stage'],
                'plan_version' => $newValues['plan_version'],
                'previous_version_id' => $newValues['previous_version_id'],
                'responsible_party' => $newValues['responsible_party'],
                'execution_time' => $newValues['execution_time'],
                'target_group' => $newValues['target_group'],
                'target_group_id' => $newValues['target_group_id'],
                'target_group_other' => $newValues['target_group_other'],
                'short_description' => $newValues['short_description'],
                'work_teams_count' => $newValues['work_teams_count'],
                'volunteer_need' => $newValues['volunteer_need'],
                'needs_volunteers' => $newValues['needs_volunteers'],
                'required_volunteers' => $newValues['required_volunteers'],
                'expected_attendance' => $newValues['expected_attendance'],
                'actual_attendance' => $newValues['actual_attendance'],
                'attendance_notes' => $newValues['attendance_notes'],
                'has_sponsor' => $newValues['has_sponsor'],
                'sponsor_name_title' => $newValues['sponsor_name_title'],
                'has_partners' => $newValues['has_partners'],
                'needs_official_letters' => $newValues['needs_official_letters'],
                'needs_official_correspondence' => $newValues['needs_official_correspondence'],
                'official_correspondence_reason' => $newValues['official_correspondence_reason'],
                'official_correspondence_target' => $newValues['official_correspondence_target'],
                'letter_purpose' => $newValues['letter_purpose'],
                'rescheduled_date' => $newValues['rescheduled_date'],
                'reschedule_reason' => $newValues['reschedule_reason'],
                'relations_approval_on_reschedule' => $newValues['relations_approval_on_reschedule'],
                'audience_satisfaction_percent' => $newValues['audience_satisfaction_percent'],
                'evaluation_score' => $newValues['evaluation_score'],
                'needs_media_coverage' => $newValues['needs_media_coverage'],
                'media_coverage_notes' => $newValues['media_coverage_notes'],
                'requires_programs' => $newValues['requires_programs'],
                'is_program_related' => $newValues['is_program_related'],
                'requires_workshops' => $newValues['requires_workshops'],
                'requires_communications' => $newValues['requires_communications'],
                'branch_id' => $newValues['branch_id'],
                'center_id' => null,
                'lifecycle_status' => $newValues['lifecycle_status'],
                'relations_officer_approval_status' => $newValues['relations_officer_approval_status'],
                'relations_manager_approval_status' => $newValues['relations_manager_approval_status'],
                'programs_officer_approval_status' => $newValues['programs_officer_approval_status'],
                'programs_manager_approval_status' => $newValues['programs_manager_approval_status'],
                'liaison_approval_status' => $newValues['liaison_approval_status'],
                'hq_relations_manager_approval_status' => $newValues['hq_relations_manager_approval_status'],
                'executive_approval_status' => $newValues['executive_approval_status'],
                'lock_at' => $this->buildLockAt($data['proposed_date']),
                'is_official' => $this->buildLockAt($data['proposed_date'])?->isPast() ?? false,
                'created_by' => $request->user()->id,
            ]);

            $monthlyActivity->update([
                'status' => 'cancelled',
            ]);
        } else {
            $monthlyActivity->update([
            'month' => $newValues['month'],
            'day' => $newValues['day'],
            'title' => $newValues['title'],
            'proposed_date' => $newValues['proposed_date'],
            'is_in_agenda' => $newValues['is_in_agenda'],
            'agenda_event_id' => $newValues['agenda_event_id'],
            'is_from_agenda' => $newValues['is_from_agenda'],
            'participation_status' => $newValues['participation_status'],
            'plan_type' => $newValues['plan_type'],
            'branch_plan_file' => $newValues['branch_plan_file'],
            'description' => $newValues['description'],
            'location_type' => $newValues['location_type'],
            'location_details' => $newValues['location_details'],
            'internal_location' => $newValues['internal_location'],
            'outside_place_name' => $newValues['outside_place_name'],
            'outside_google_maps_url' => $newValues['outside_google_maps_url'],
            'outside_address' => $newValues['outside_address'],
            'status' => $newValues['status'],
            'plan_stage' => $newValues['plan_stage'],
            'plan_version' => $newValues['plan_version'],
            'previous_version_id' => $newValues['previous_version_id'],
            'responsible_party' => $newValues['responsible_party'],
            'execution_time' => $newValues['execution_time'],
            'target_group' => $newValues['target_group'],
            'target_group_id' => $newValues['target_group_id'],
            'target_group_other' => $newValues['target_group_other'],
            'short_description' => $newValues['short_description'],
            'work_teams_count' => $newValues['work_teams_count'],
            'volunteer_need' => $newValues['volunteer_need'],
            'needs_volunteers' => $newValues['needs_volunteers'],
            'required_volunteers' => $newValues['required_volunteers'],
            'expected_attendance' => $newValues['expected_attendance'],
            'actual_attendance' => $newValues['actual_attendance'],
            'attendance_notes' => $newValues['attendance_notes'],
            'has_sponsor' => $newValues['has_sponsor'],
            'sponsor_name_title' => $newValues['sponsor_name_title'],
            'has_partners' => $newValues['has_partners'],
            'needs_official_letters' => $newValues['needs_official_letters'],
            'needs_official_correspondence' => $newValues['needs_official_correspondence'],
            'official_correspondence_reason' => $newValues['official_correspondence_reason'],
            'official_correspondence_target' => $newValues['official_correspondence_target'],
            'letter_purpose' => $newValues['letter_purpose'],
            'rescheduled_date' => $newValues['rescheduled_date'],
            'reschedule_reason' => $newValues['reschedule_reason'],
            'relations_approval_on_reschedule' => $newValues['relations_approval_on_reschedule'],
            'audience_satisfaction_percent' => $newValues['audience_satisfaction_percent'],
            'evaluation_score' => $newValues['evaluation_score'],
            'needs_media_coverage' => $newValues['needs_media_coverage'],
            'media_coverage_notes' => $newValues['media_coverage_notes'],
            'requires_programs' => $newValues['requires_programs'],
            'is_program_related' => $newValues['is_program_related'],
            'requires_workshops' => $newValues['requires_workshops'],
            'requires_communications' => $newValues['requires_communications'],
            'branch_id' => $newValues['branch_id'],
            'center_id' => null,
            'lifecycle_status' => $newValues['lifecycle_status'],
            'relations_officer_approval_status' => $newValues['relations_officer_approval_status'],
            'relations_manager_approval_status' => $newValues['relations_manager_approval_status'],
            'programs_officer_approval_status' => $newValues['programs_officer_approval_status'],
            'programs_manager_approval_status' => $newValues['programs_manager_approval_status'],
            'liaison_approval_status' => $newValues['liaison_approval_status'],
            'hq_relations_manager_approval_status' => $newValues['hq_relations_manager_approval_status'],
            'executive_approval_status' => $newValues['executive_approval_status'],
            'lock_at' => $this->buildLockAt($data['proposed_date']),
            'is_official' => $this->buildLockAt($data['proposed_date'])?->isPast() ?? false,
            ]);
        }

        $workflowService->initializeDynamicStatuses($activityToSave);

        $this->syncSponsorsAndPartners($activityToSave, $data);
        if (($request->user()->hasRole('followup_officer') || $request->user()->hasRole('super_admin')) && $this->canSubmitPostEvaluation($activityToSave)) {
            $this->syncEvaluationData($activityToSave, $data, $request->user()->id);
        }
        $this->logChanges($activityToSave, $oldValues, $newValues, $request->user()->id);
        $this->logWorkflowAction($startsNewVersion ? 'new_version_created' : 'updated', $activityToSave, $request, $activityToSave->status, [
            'changed_fields' => array_keys($newValues),
            'source_activity_id' => $startsNewVersion ? $monthlyActivity->id : null,
        ]);

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.updated', ['activity' => $monthlyActivity->title]))
            ->with('warning', $conflictWarning);
    }

    public function submit(MonthlyActivity $monthlyActivity, NotificationService $notifications, MonthlyActivityLifecycleService $lifecycle, DynamicWorkflowService $dynamicWorkflowService)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());

        $instance = $dynamicWorkflowService->forModel('monthly_activities', $monthlyActivity);
        abort_unless($instance !== null, 422, __('app.roles.programs.monthly_activities.approvals.errors.no_active_workflow'));

        if ($instance->status === 'changes_requested') {
            $dynamicWorkflowService->markResubmitted($instance);
        }

        $monthlyActivity->update([
            'status' => 'submitted',
        ]);

        if (($monthlyActivity->lifecycle_status ?: 'Draft') !== 'Submitted') {
            $lifecycle->transitionOrFail($monthlyActivity, 'Submitted');
        }

        $notifications->notifyUsers(User::role('relations_officer')->get(), 'approval_requested', __('app.roles.programs.monthly_activities.approvals.notifications.submit_title'), __('app.roles.programs.monthly_activities.approvals.notifications.submit_body', ['activity' => $monthlyActivity->title]), route('role.programs.approvals.index'));

        $request = request();
        if ($request && $request->user()) {
            $this->logWorkflowAction('submitted', $monthlyActivity, $request, 'submitted');
        }

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.submitted', ['activity' => $monthlyActivity->title]));
    }

    public function close(Request $request, MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycle)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, $request->user());

        $data = $request->validate([
            'actual_date' => ['nullable', 'date'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $monthlyActivity->update([
            'actual_date' => $data['actual_date'] ?? $monthlyActivity->actual_date,
            'actual_attendance' => $data['actual_attendance'] ?? $monthlyActivity->actual_attendance,
            'status' => $data['status'],
            'is_official' => true,
        ]);

        if (($data['status'] ?? null) === 'executed') {
            $lifecycle->transitionOrFail($monthlyActivity, 'Executed');
        }

        $this->logWorkflowAction('closed', $monthlyActivity, $request, $data['status']);

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.closed', ['activity' => $monthlyActivity->title]));
    }

    public function calendar(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $month = (int) $request->input('month', now()->month);

        $query = MonthlyActivity::query()
            ->with('branch')
            ->whereYear('proposed_date', $year)
            ->whereMonth('proposed_date', $month)
            ->notArchived();

        $this->applyBranchVisibilityScope($query, $request->user());

        $items = $query->orderBy('proposed_date')->orderBy('day')->get()->map(function (MonthlyActivity $activity) {
            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'date' => optional($activity->proposed_date)->format('Y-m-d')
                    ?? sprintf('%04d-%02d-%02d', now()->year, $activity->month, $activity->day),
                'branch' => $activity->branch?->name,
                'status' => $activity->status,
                'requires_workshops' => (bool) $activity->requires_workshops,
                'requires_communications' => (bool) $activity->requires_communications,
                'edit_url' => route('role.relations.activities.edit', $activity),
            ];
        })->values();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'items' => $items,
        ]);
    }
}
