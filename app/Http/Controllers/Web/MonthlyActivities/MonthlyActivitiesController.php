<?php

namespace App\Http\Controllers\Web\MonthlyActivities;

use App\Http\Controllers\Controller;
use App\Models\AgendaEvent;
use App\Models\Branch;
use App\Models\Center;
use App\Models\MonthlyActivityChangeLog;
use App\Models\MonthlyActivityPartner;
use App\Models\MonthlyActivitySponsor;
use App\Models\MonthlyActivity;
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

class MonthlyActivitiesController extends Controller
{
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
        foreach (($data['partners'] ?? []) as $index => $partner) {
            $name = trim((string) ($partner['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            MonthlyActivityPartner::create([
                'monthly_activity_id' => $monthlyActivity->id,
                'name' => $name,
                'role' => $partner['role'] ?? null,
                'sort_order' => $index + 1,
            ]);
        }
    }

    protected function logWorkflowAction(string $actionType, MonthlyActivity $monthlyActivity, Request $request, ?string $status = null, ?array $meta = null): void
    {
        WorkflowActionLog::create([
            'module' => 'monthly_activity',
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

    public function index(Request $request)
    {
        $activities = MonthlyActivity::with(['branch', 'center', 'agendaEvent', 'creator'])
            ->enterpriseFilter($request->all())
            ->notArchived()
            ->orderBy('month')
            ->orderBy('day')
            ->paginate(15)
            ->withQueryString();
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();

        return view('pages.monthly_activities.activities.index', compact('activities', 'branches', 'centers', 'agendaEvents'));
    }

    public function create()
    {
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();
        $targetGroups = TargetGroup::where('is_active', true)->orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::where('is_active', true)->orderBy('sort_order')->get();

        return view('pages.monthly_activities.activities.create', compact('branches', 'centers', 'agendaEvents', 'targetGroups', 'evaluationQuestions'));
    }

    public function syncFromAgenda(Request $request)
    {
        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        $centerBelongsToBranch = Center::query()
            ->where('id', $data['center_id'])
            ->where('branch_id', $data['branch_id'])
            ->exists();

        if (! $centerBelongsToBranch) {
            return back()->withErrors(['center_id' => __('app.roles.programs.monthly_activities.errors.center_branch_mismatch')]);
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
                'agenda_event_id' => $event->id,
                'description' => $event->notes,
                'location_type' => 'inside_center',
                'location_details' => null,
                'status' => 'draft',
                'lock_at' => $this->buildLockAt(optional($event->event_date)?->toDateString() ?? Carbon::create($data['year'], $event->month, $event->day)->toDateString()),
                'is_official' => false,
                'branch_id' => (int) $data['branch_id'],
                'center_id' => (int) $data['center_id'],
                'created_by' => $request->user()->id,
            ]);

            $created++;
        }

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.sync.done', ['count' => $created]));
    }

    public function store(Request $request, ConflictDetectionService $conflicts)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
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
            'target_group_other' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'volunteer_need' => ['nullable', 'string', 'max:255'],
            'needs_volunteers' => ['nullable', 'boolean'],
            'required_volunteers' => ['nullable', 'integer', 'min:0'],
            'expected_attendance' => ['nullable', 'integer', 'min:0'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'attendance_notes' => ['nullable', 'string'],
            'work_teams_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'needs_media_coverage' => ['nullable', 'boolean'],
            'media_coverage_notes' => ['nullable', 'string'],
            'needs_official_correspondence' => ['nullable', 'boolean'],
            'official_correspondence_reason' => ['nullable', 'string', 'max:255'],
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
            'partners.*.role' => ['nullable', 'string', 'max:255'],
            'evaluations' => ['nullable', 'array'],
            'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
            'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
            'evaluations.*.note' => ['nullable', 'string'],
            'followup_remarks' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        if (($data['location_type'] ?? null) === 'inside_center') {
            $data['outside_place_name'] = null;
            $data['outside_google_maps_url'] = null;
            $data['outside_address'] = null;
        } else {
            $data['internal_location'] = null;
        }

        $date = Carbon::parse($data['activity_date']);
        $conflictNames = $conflicts->findMonthlyActivityConflicts($data['proposed_date'], (int) $data['branch_id']);
        $conflictWarning = empty($conflictNames) ? null : __('Potential overlap with: :activities', ['activities' => implode(', ', $conflictNames)]);

        $monthlyActivity = MonthlyActivity::create([
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'proposed_date' => $data['proposed_date'],
            'is_in_agenda' => !empty($data['agenda_event_id']),
            'agenda_event_id' => $data['agenda_event_id'] ?? null,
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'status' => $data['status'],
            'responsible_party' => $data['responsible_party'] ?? null,
            'execution_time' => $data['execution_time'] ?? null,
            'target_group' => $data['target_group'] ?? null,
            'target_group_id' => $data['target_group_id'] ?? null,
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
            'lock_at' => $this->buildLockAt($data['proposed_date']),
            'is_official' => false,
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
            'created_by' => $request->user()->id,
        ]);

        $this->syncSponsorsAndPartners($monthlyActivity, $data);
        if ($request->user()->hasRole('followup_officer') || $request->user()->hasRole('super_admin')) {
            $this->syncEvaluationData($monthlyActivity, $data, $request->user()->id);
        }
        $this->logWorkflowAction('created', $monthlyActivity, $request, $monthlyActivity->status);

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.created'))
            ->with('warning', $conflictWarning);
    }

    public function edit(MonthlyActivity $monthlyActivity)
    {
        $monthlyActivity->load(['supplies', 'team', 'attachments', 'approvals', 'sponsors', 'partners', 'evaluationResponses.question', 'followups']);
        $branches = Branch::orderBy('name')->get();
        $centers = Center::orderBy('name')->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();
        $targetGroups = TargetGroup::where('is_active', true)->orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::where('is_active', true)->orderBy('sort_order')->get();

        return view('pages.monthly_activities.activities.edit', compact('monthlyActivity', 'branches', 'centers', 'agendaEvents', 'targetGroups', 'evaluationQuestions'));
    }

    public function update(Request $request, MonthlyActivity $monthlyActivity, ConflictDetectionService $conflicts)
    {
        if ($this->isLocked($monthlyActivity) && ! $request->user()->hasRole('super_admin')) {
            return back()->withErrors(['status' => __('app.roles.programs.monthly_activities.errors.locked')]);
        }

        if ($request->user()->hasRole('programs_officer') && $monthlyActivity->executive_approval_status === 'approved') {
            return back()->withErrors(['status' => 'لا يمكن تعديل الفعالية بعد الاعتماد التنفيذي النهائي.']);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'center_id' => ['required', 'exists:centers,id'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
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
            'target_group_other' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string'],
            'volunteer_need' => ['nullable', 'string', 'max:255'],
            'needs_volunteers' => ['nullable', 'boolean'],
            'required_volunteers' => ['nullable', 'integer', 'min:0'],
            'expected_attendance' => ['nullable', 'integer', 'min:0'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'attendance_notes' => ['nullable', 'string'],
            'work_teams_count' => ['nullable', 'integer', 'min:1', 'max:20'],
            'needs_media_coverage' => ['nullable', 'boolean'],
            'media_coverage_notes' => ['nullable', 'string'],
            'needs_official_correspondence' => ['nullable', 'boolean'],
            'official_correspondence_reason' => ['nullable', 'string', 'max:255'],
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
            'partners.*.role' => ['nullable', 'string', 'max:255'],
            'evaluations' => ['nullable', 'array'],
            'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
            'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
            'evaluations.*.note' => ['nullable', 'string'],
            'followup_remarks' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
        ]);

        if (($data['location_type'] ?? null) === 'inside_center') {
            $data['outside_place_name'] = null;
            $data['outside_google_maps_url'] = null;
            $data['outside_address'] = null;
        } else {
            $data['internal_location'] = null;
        }

        $date = Carbon::parse($data['activity_date']);
        $conflictNames = $conflicts->findMonthlyActivityConflicts($data['proposed_date'], (int) $data['branch_id']);
        $conflictWarning = empty($conflictNames) ? null : __('Potential overlap with: :activities', ['activities' => implode(', ', $conflictNames)]);

        $oldValues = $monthlyActivity->only([
            'title',
            'proposed_date',
            'agenda_event_id',
            'description',
            'location_type',
            'location_details',
            'internal_location',
            'outside_place_name',
            'outside_google_maps_url',
            'outside_address',
            'status',
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
            'letter_purpose',
            'rescheduled_date',
            'reschedule_reason',
            'relations_approval_on_reschedule',
            'audience_satisfaction_percent',
            'evaluation_score',
            'branch_id',
            'center_id',
            'month',
            'day',
        ]);

        $newValues = [
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'proposed_date' => $data['proposed_date'],
            'agenda_event_id' => $data['agenda_event_id'] ?? null,
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'status' => $data['status'],
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
            'letter_purpose' => $data['letter_purpose'] ?? null,
            'rescheduled_date' => $data['rescheduled_date'] ?? null,
            'reschedule_reason' => $data['reschedule_reason'] ?? null,
            'relations_approval_on_reschedule' => (bool) ($data['relations_approval_on_reschedule'] ?? false),
            'audience_satisfaction_percent' => $data['audience_satisfaction_percent'] ?? null,
            'evaluation_score' => $data['evaluation_score'] ?? null,
            'needs_media_coverage' => (bool) ($data['needs_media_coverage'] ?? false),
            'media_coverage_notes' => $data['media_coverage_notes'] ?? null,
            'branch_id' => $data['branch_id'],
            'center_id' => $data['center_id'],
        ];

        $monthlyActivity->update([
            'month' => $newValues['month'],
            'day' => $newValues['day'],
            'title' => $newValues['title'],
            'proposed_date' => $newValues['proposed_date'],
            'is_in_agenda' => !empty($data['agenda_event_id']),
            'agenda_event_id' => $newValues['agenda_event_id'],
            'description' => $newValues['description'],
            'location_type' => $newValues['location_type'],
            'location_details' => $newValues['location_details'],
            'internal_location' => $newValues['internal_location'],
            'outside_place_name' => $newValues['outside_place_name'],
            'outside_google_maps_url' => $newValues['outside_google_maps_url'],
            'outside_address' => $newValues['outside_address'],
            'status' => $newValues['status'],
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
            'letter_purpose' => $newValues['letter_purpose'],
            'rescheduled_date' => $newValues['rescheduled_date'],
            'reschedule_reason' => $newValues['reschedule_reason'],
            'relations_approval_on_reschedule' => $newValues['relations_approval_on_reschedule'],
            'audience_satisfaction_percent' => $newValues['audience_satisfaction_percent'],
            'evaluation_score' => $newValues['evaluation_score'],
            'needs_media_coverage' => $newValues['needs_media_coverage'],
            'media_coverage_notes' => $newValues['media_coverage_notes'],
            'branch_id' => $newValues['branch_id'],
            'center_id' => $newValues['center_id'],
            'lock_at' => $this->buildLockAt($data['proposed_date']),
            'is_official' => $this->buildLockAt($data['proposed_date'])?->isPast() ?? false,
        ]);

        $this->syncSponsorsAndPartners($monthlyActivity, $data);
        if ($request->user()->hasRole('followup_officer') || $request->user()->hasRole('super_admin')) {
            $this->syncEvaluationData($monthlyActivity, $data, $request->user()->id);
        }
        $this->logChanges($monthlyActivity, $oldValues, $newValues, $request->user()->id);
        $this->logWorkflowAction('updated', $monthlyActivity, $request, $monthlyActivity->status, [
            'changed_fields' => array_keys($newValues),
        ]);

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.updated', ['activity' => $monthlyActivity->title]))
            ->with('warning', $conflictWarning);
    }

    public function submit(MonthlyActivity $monthlyActivity, NotificationService $notifications)
    {
        $monthlyActivity->update([
            'status' => 'submitted',
        ]);

        $notifications->notifyUsers(User::role('relations_officer')->get(), 'approval_requested', 'Monthly activity approval requested', $monthlyActivity->title, route('role.programs.approvals.index'));

        $request = request();
        if ($request && $request->user()) {
            $this->logWorkflowAction('submitted', $monthlyActivity, $request, 'submitted');
        }

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.submitted', ['activity' => $monthlyActivity->title]));
    }

    public function close(Request $request, MonthlyActivity $monthlyActivity)
    {
        $data = $request->validate([
            'actual_date' => ['nullable', 'date'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $monthlyActivity->update([
            'actual_date' => $data['actual_date'] ?? $monthlyActivity->actual_date,
            'status' => $data['status'],
            'is_official' => true,
        ]);

        $this->logWorkflowAction('closed', $monthlyActivity, $request, $data['status']);

        return redirect()
            ->route('role.programs.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.closed', ['activity' => $monthlyActivity->title]));
    }
}
