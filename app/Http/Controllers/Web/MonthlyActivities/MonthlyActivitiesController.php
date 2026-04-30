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
use App\Models\WorkflowLog;
use App\Models\WorkflowInstance;
use App\Models\EvaluationQuestion;
use App\Models\EventStatusLookup;
use App\Models\MonthlyActivityFollowup;
use App\Models\MonthlyActivityEvaluationResponse;
use App\Models\TargetGroup;
use App\Models\Setting;
use App\Models\WorkflowActionLog;
use App\Models\ZahaTimeOption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\ConflictDetectionService;
use App\Services\WorkflowNotificationService;
use App\Services\NotificationService;
use App\Services\MonthlyActivityWorkflowService;
use App\Services\MonthlyActivityLifecycleService;
use App\Services\DynamicWorkflowService;
use App\Services\MonthlyWorkflowPresenter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class MonthlyActivitiesController extends Controller
{
    protected const MONTHLY_ACTIVITY_EDIT_ROLES = [
        'relations_manager',
        'relations_officer',
        'branch_relations_manager',
        'branch_relations_officer',
        'followup_officer',
        'super_admin',
    ];

    protected function currentUserBranchId(?User $user): ?int
    {
        $branchIds = $this->scopedBranchIds($user);

        return count($branchIds) === 1 ? $branchIds[0] : null;
    }

    protected function ownBranchId(?User $user): ?int
    {
        return filled($user?->branch_id) ? (int) $user->branch_id : null;
    }

    /**
     * @return array<int, int>
     */
    protected function scopedBranchIds(?User $user): array
    {
        if (! $this->shouldScopeToUserBranch($user)) {
            return [];
        }

        return method_exists($user, 'scopedBranchIds')
            ? $user->scopedBranchIds()
            : (filled($user?->branch_id) ? [(int) $user->branch_id] : []);
    }

    protected function canAccessScopedBranch(?User $user, ?int $branchId): bool
    {
        $scopedBranchIds = $this->scopedBranchIds($user);

        return $scopedBranchIds === [] || in_array((int) $branchId, $scopedBranchIds, true);
    }

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

    protected function isSupersededVersion(MonthlyActivity $monthlyActivity): bool
    {
        return $monthlyActivity->newerVersions()->exists();
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
        if (! $this->shouldScopeToUserBranch($user)) {
            return $query;
        }

        $ownBranchId = $this->ownBranchId($user);
        if ($ownBranchId) {
            $query->where('branch_id', $ownBranchId);
        }

        return $query;
    }

    protected function applyOtherBranchesScope($query, ?User $user)
    {
        $ownBranchId = $this->ownBranchId($user);

        if ($ownBranchId) {
            $query->where('branch_id', '!=', $ownBranchId);
        }

        return $query;
    }

    protected function canViewOtherBranches(?User $user): bool
    {
        return $user !== null
            && ($user->can('monthly_activities.view_other_branches') || $user->hasRole('super_admin'));
    }

    protected function applyDraftVisibilityScope($query, ?User $user)
    {
        return $query->where(function ($visibilityQuery) use ($user) {
            $visibilityQuery->where('status', '!=', 'draft');

            if ($user) {
                $visibilityQuery->orWhere('created_by', $user->id);
            }
        });
    }

    protected function isVolunteerCoordinatorOnly(?User $user): bool
    {
        return $user !== null
            && $user->hasRole('volunteer_coordinator')
            && ! $user->hasRole('super_admin');
    }

    protected function applyVolunteerCoordinatorVisibilityScope($query, ?User $user)
    {
        if (! $this->isVolunteerCoordinatorOnly($user)) {
            return $query;
        }

        return $query->where(function ($volunteerQuery) {
            $volunteerQuery
                ->where('needs_volunteers', true)
                ->orWhere('required_volunteers', '>', 0)
                ->orWhere('volunteers_count', '>', 0)
                ->orWhere('volunteers_required', true);
        });
    }

    protected function canCompleteAfterExecution(MonthlyActivity $monthlyActivity, ?User $user): bool
    {
        return $user !== null
            && $this->canUseMonthlyActivityEditRoute($user)
            && (int) $monthlyActivity->created_by === (int) $user->id;
    }

    protected function canUseMonthlyActivityEditRoute(?User $user): bool
    {
        return $user !== null
            && $user->hasAnyRole(self::MONTHLY_ACTIVITY_EDIT_ROLES);
    }

    protected function ensureActivityVisibleToUser(MonthlyActivity $monthlyActivity, User $user): void
    {
        if (! $this->canAccessScopedBranch($user, $monthlyActivity->branch_id)) {
            abort(403);
        }

        if ((string) $monthlyActivity->status === 'draft' && (int) $monthlyActivity->created_by !== (int) $user->id) {
            abort(403);
        }

        if ($this->isVolunteerCoordinatorOnly($user) && ! $this->activityNeedsVolunteers($monthlyActivity)) {
            abort(403);
        }
    }

    protected function activityNeedsVolunteers(MonthlyActivity $monthlyActivity): bool
    {
        return (bool) $monthlyActivity->needs_volunteers
            || (int) ($monthlyActivity->required_volunteers ?? 0) > 0
            || (int) ($monthlyActivity->volunteers_count ?? 0) > 0
            || (bool) $monthlyActivity->volunteers_required;
    }

    protected function monthlyLockDays(): int
    {
        return max(0, (int) Setting::valueOf('monthly_plan_lock_days', '5'));
    }

    protected function monthlyIndexPerPage(?int $requestedPerPage = null): int
    {
        if ($requestedPerPage !== null) {
            return max(5, min(100, $requestedPerPage));
        }

        return max(5, min(100, (int) Setting::valueOf('monthly_activities_index_per_page', '10')));
    }

    protected function buildLockAt(string $proposedDate): ?Carbon
    {
        return Carbon::parse($proposedDate)->subDays($this->monthlyLockDays())->endOfDay();
    }

    protected function isLocked(MonthlyActivity $monthlyActivity): bool
    {
        return $monthlyActivity->lock_at !== null && now()->greaterThanOrEqualTo($monthlyActivity->lock_at);
    }

    protected function safeExternalUrlRules(): array
    {
        return [
            'nullable',
            'url',
            'max:500',
            function (string $attribute, mixed $value, \Closure $fail) {
                if (! filled($value)) {
                    return;
                }

                $url = trim((string) $value);
                $parts = parse_url($url);
                $scheme = strtolower((string) ($parts['scheme'] ?? ''));
                $host = strtolower((string) ($parts['host'] ?? ''));

                if (! in_array($scheme, ['http', 'https'], true)) {
                    $fail('صيغة الرابط غير آمنة.');
                    return;
                }

                $allowedHosts = ['google.com', 'drive.google.com'];
                $isAllowed = collect($allowedHosts)->contains(fn (string $allowed) => $host === $allowed || Str::endsWith($host, '.'.$allowed));
                if (! $isAllowed) {
                    $fail('الرابط يجب أن يكون ضمن النطاقات الموثوقة.');
                }
            },
        ];
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

    protected function statusLookupOptions(string $module, array $allowedCodes = [], ?string $currentCode = null)
    {
        return EventStatusLookup::query()
            ->forModule($module)
            ->when($allowedCodes !== [], fn ($query) => $query->whereIn('code', $allowedCodes))
            ->where(function ($query) use ($currentCode) {
                $query->where('is_active', true);

                if (filled($currentCode)) {
                    $query->orWhere('code', $currentCode);
                }
            })
            ->ordered()
            ->get()
            ->unique('code')
            ->values();
    }

    protected function monthlyPlanningStatusOptions(?string $currentCode = null)
    {
        return $this->statusLookupOptions('monthly_activities', [
            'draft',
            'submitted',
            'changes_requested',
            'postponed',
            'cancelled',
            'closed',
        ], $currentCode);
    }

    protected function monthlyCreationStatusOptions(?string $currentCode = null)
    {
        return $this->statusLookupOptions('monthly_activities', [
            'draft',
            'submitted',
            'postponed',
            'cancelled',
        ], $currentCode);
    }

    protected function monthlyCloseStatusOptions(?string $currentCode = null)
    {
        return $this->statusLookupOptions('monthly_activities', [
            'closed',
            'completed',
            'executed',
        ], $currentCode);
    }

    protected function executionStatusLabels(): array
    {
        return [
            'executed' => 'منفذة',
            'postponed' => 'مؤجلة',
            'cancelled' => 'ملغية',
        ];
    }

    protected function agendaEventsForUser(?User $user, ?MonthlyActivity $monthlyActivity = null)
    {
        $selectedEventId = $monthlyActivity?->agenda_event_id;

        return $this->agendaEventsQueryForUser($user, $selectedEventId)
            ->orderBy('month')
            ->orderBy('day')
            ->get();
    }

    protected function agendaEventsQueryForUser(?User $user, ?int $selectedEventId = null)
    {
        $scopedBranchIds = $this->scopedBranchIds($user);

        return AgendaEvent::query()
            ->when($scopedBranchIds !== [], function ($query) use ($scopedBranchIds, $selectedEventId) {
                $query->forBranchAudience($scopedBranchIds, null, $selectedEventId);
            });
    }

    protected function findAgendaEventForUser(?User $user, int $agendaEventId): ?AgendaEvent
    {
        return $this->agendaEventsQueryForUser($user, $agendaEventId)
            ->whereKey($agendaEventId)
            ->first();
    }

    protected function flashCreatePrefill(Request $request): void
    {
        if ($request->session()->hasOldInput()) {
            return;
        }

        // نجهز تعبئة مبدئية ذكية حسب التاريخ أو الفرع أو فعالية الأجندة القادمة من الواجهة.
        $user = $request->user();
        $prefill = [];
        $date = trim((string) $request->query('date', ''));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $prefill['activity_date'] = $date;
            $prefill['proposed_date'] = $date;
        }

        $branchId = (int) $request->query('branch_id', 0);
        if ($branchId > 0 && $this->canAccessScopedBranch($user, $branchId)) {
            $prefill['branch_id'] = $branchId;
        }

        $agendaEventId = (int) $request->query('agenda_event_id', 0);
        if ($agendaEventId > 0) {
            $agendaEvent = $this->findAgendaEventForUser($user, $agendaEventId);

            if ($agendaEvent) {
                $resolvedDate = $prefill['proposed_date'] ?? (
                    optional($agendaEvent->event_date)?->toDateString()
                    ?? Carbon::create(now()->year, (int) $agendaEvent->month, (int) $agendaEvent->day)->toDateString()
                );

                $prefill = array_merge($prefill, [
                    'activity_date' => $prefill['activity_date'] ?? $resolvedDate,
                    'proposed_date' => $resolvedDate,
                    'agenda_event_id' => $agendaEvent->id,
                    'title' => $agendaEvent->event_name,
                    'description' => $agendaEvent->notes,
                    'short_description' => Str::limit(trim((string) $agendaEvent->notes), 255, ''),
                    'is_in_agenda' => 1,
                ]);
            }
        }

        if ($prefill !== []) {
            $request->session()->flash('_old_input', $prefill);
        }
    }

    protected function normalizePlanningPayload(array &$data): void
    {
        $this->normalizeVolunteerAgeRange($data);
        $this->normalizeExecutionNeedsFollowup($data);
        $this->normalizeExecutionNeedsPayload($data);
        $this->normalizeSuppliesPayload($data);

        $data['execution_status'] = $data['execution_status'] ?? 'executed';
        $data['status'] = $data['status'] ?? 'draft';

        if (($data['location_type'] ?? null) === 'inside_center') {
            $data['outside_place_name'] = null;
            $data['outside_google_maps_url'] = null;
            $data['outside_contact_number'] = null;
            $data['external_liaison_name'] = null;
            $data['external_liaison_phone'] = null;
            $data['outside_address'] = null;
        } else {
            $data['internal_location'] = null;
        }

        if (! (bool) ($data['needs_official_correspondence'] ?? false)) {
            $data['official_correspondence_reason'] = null;
            $data['official_correspondence_target'] = null;
            $data['official_correspondence_brief'] = null;
        }

        $data['needs_official_letters'] = false;
        $data['letter_purpose'] = null;

        $description = trim((string) ($data['description'] ?? ''));
        if ($description !== '') {
            $data['description'] = $description;
            $data['short_description'] = Str::limit($description, 255, '');
        }

        if (! (bool) ($data['needs_volunteers'] ?? false)) {
            $data['required_volunteers'] = null;
            $data['volunteer_need'] = null;
            $data['volunteer_age_range'] = null;
            $data['volunteer_age_from'] = null;
            $data['volunteer_age_to'] = null;
            $data['volunteer_gender'] = null;
            $data['volunteer_tasks_summary'] = null;
        }

        if (($data['execution_status'] ?? 'executed') !== 'postponed') {
            $data['rescheduled_date'] = null;
            $data['reschedule_reason'] = null;
        }

        if (($data['execution_status'] ?? 'executed') !== 'cancelled') {
            $data['cancellation_reason'] = null;
        }

        if (! (bool) ($data['requires_supplies'] ?? false)) {
            $data['supplies'] = [];
        }

        if (! (bool) ($data['has_partners'] ?? false)) {
            $data['partners'] = [];
        }

        if (! (bool) ($data['has_sponsor'] ?? false)) {
            $data['sponsors'] = [];
        }
    }

    protected function normalizeVolunteerAgeRange(array &$data): void
    {
        $from = isset($data['volunteer_age_from']) && $data['volunteer_age_from'] !== ''
            ? (int) $data['volunteer_age_from']
            : null;
        $to = isset($data['volunteer_age_to']) && $data['volunteer_age_to'] !== ''
            ? (int) $data['volunteer_age_to']
            : null;

        if ($from !== null && $to !== null) {
            $data['volunteer_age_range'] = $from . '-' . $to;
        } elseif (! isset($data['volunteer_age_range'])) {
            $data['volunteer_age_range'] = null;
        }
    }

    protected function extractVolunteerAgeBounds(?string $range): array
    {
        $range = trim((string) $range);
        if ($range === '') {
            return [null, null];
        }

        if (preg_match('/^\s*(\d{1,2})\s*[-–]\s*(\d{1,2})\s*$/u', $range, $matches)) {
            return [(int) $matches[1], (int) $matches[2]];
        }

        return [null, null];
    }

    protected function normalizeExecutionNeedsFollowup(array &$data): void
    {
        $rows = $data['execution_needs_followup'] ?? null;
        if (! is_array($rows)) {
            $data['execution_needs_followup'] = null;
            return;
        }

        $normalized = collect($rows)
            ->map(function ($row, $key) {
                if (! is_array($row)) {
                    return null;
                }

                $status = in_array((string) ($row['status'] ?? ''), ['secured', 'not_secured'], true)
                    ? (string) $row['status']
                    : null;
                $reason = trim((string) ($row['reason'] ?? ($row['notes'] ?? '')));
                $score = $row['effectiveness_score'] ?? null;
                $score = $score === '' || $score === null ? null : (int) $score;

                if (! $status && $reason === '' && $score === null) {
                    return null;
                }

                return [
                    'key' => (string) $key,
                    'status' => $status,
                    'reason' => $reason !== '' ? $reason : null,
                    'notes' => $reason !== '' ? $reason : null,
                    'effectiveness_score' => $score,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $data['execution_needs_followup'] = $normalized === [] ? null : $normalized;
    }

    protected function filterExecutionNeedsFollowupToEnabled(MonthlyActivity $monthlyActivity, array &$data): void
    {
        if (empty($data['execution_needs_followup'])) {
            return;
        }

        $enabledKeys = array_keys($monthlyActivity->enabledExecutionNeeds());

        $data['execution_needs_followup'] = collect($data['execution_needs_followup'])
            ->filter(fn (array $row) => in_array((string) ($row['key'] ?? ''), $enabledKeys, true))
            ->values()
            ->all();
    }

    protected function notifyExecutionNeedOwners(MonthlyActivity $monthlyActivity): void
    {
        $definitions = $monthlyActivity->enabledExecutionNeeds();
        if ($definitions === []) {
            return;
        }

        $notifications = app(NotificationService::class);
        $activity = $monthlyActivity->fresh(['branch', 'supplies']);
        $url = route('role.relations.activities.show', $activity);

        collect($definitions)
            ->map(fn (array $definition, string $key) => array_merge($definition, ['key' => $key]))
            ->groupBy('owner_role')
            ->each(function (Collection $needs, ?string $role) use ($activity, $notifications, $url) {
                if (blank($role)) {
                    return;
                }

                $users = $this->executionNeedOwnerUsers($role, $activity);
                if ($users->isEmpty()) {
                    return;
                }

                $labels = $needs->pluck('label')->implode('، ');

                $notifications->notifyUsers(
                    $users,
                    'monthly_activity_execution_need',
                    'احتياج تنفيذ على خطة شهرية',
                    "النشاط \"{$activity->title}\" بحاجة إلى: {$labels}.",
                    $url,
                    [
                        'monthly_activity_id' => $activity->id,
                        'branch_id' => $activity->branch_id,
                        'need_keys' => $needs->pluck('key')->values()->all(),
                        'role' => $role,
                    ]
                );
            });
    }

    protected function executionNeedOwnerUsers(string $role, MonthlyActivity $monthlyActivity): Collection
    {
        $query = User::role($role)
            ->where('status', 'active')
            ->when($role === 'branch_coordinator', function ($query) use ($monthlyActivity) {
                $query->where(function ($branchQuery) use ($monthlyActivity) {
                    $branchQuery
                        ->whereHas('assignedBranches', fn ($assignedQuery) => $assignedQuery->whereKey($monthlyActivity->branch_id))
                        ->orWhere('branch_id', $monthlyActivity->branch_id);
                });
            });

        $users = $query->get();

        if ($users->isEmpty() && $role === 'branch_coordinator') {
            return User::role($role)->where('status', 'active')->get();
        }

        return $users;
    }

    protected function normalizeSuppliesPayload(array &$data): void
    {
        if (! isset($data['supplies']) || ! is_array($data['supplies'])) {
            return;
        }

        $data['supplies'] = collect($data['supplies'])->map(function ($supply) {
            if (! is_array($supply)) {
                return $supply;
            }

            if (! isset($supply['provider_type']) && isset($supply['insurance_mechanism'])) {
                $supply['provider_type'] = $supply['insurance_mechanism'];
            }

            if (! isset($supply['provider_name']) && isset($supply['insurance_other_details'])) {
                $supply['provider_name'] = $supply['insurance_other_details'];
            }

            return $supply;
        })->all();
    }

    protected function normalizeExecutionNeedsPayload(array &$data): void
    {
        $sectionLink = static fn (string $needCode, bool $enabled): array => [
            'need_code' => $needCode,
            'enabled' => $enabled,
            'future_cycle_id' => null,
        ];

        $needsCeremonyAgenda = (bool) ($data['needs_ceremony_agenda'] ?? false);
        $needsTransport = (bool) ($data['needs_transport'] ?? false);
        $needsMaintenance = (bool) ($data['needs_maintenance_workers'] ?? false);
        $needsGifts = (bool) ($data['needs_gifts'] ?? false);
        $needsPrograms = (bool) ($data['needs_programs_participation'] ?? false);
        $needsCertificates = (bool) ($data['needs_certificates_and_thanks'] ?? false);
        $needsInvitations = (bool) ($data['needs_invitations'] ?? false);
        $ceremonyItems = collect($data['ceremony_items'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item, int $index): array {
                return [
                    'order' => isset($item['order']) ? (int) $item['order'] : ($index + 1),
                    'name' => $item['name'] ?? null,
                    'time_from' => $item['time_from'] ?? null,
                    'time_to' => $item['time_to'] ?? null,
                    'description' => $item['description'] ?? null,
                ];
            })
            ->values();
        $firstCeremonyItem = $ceremonyItems->first() ?? [];

        $payload = [
            'schema_version' => 2,
            'needs_registry' => [
                'ceremony' => $sectionLink('ceremony', $needsCeremonyAgenda),
                'transport' => $sectionLink('transport', $needsTransport),
                'maintenance' => $sectionLink('maintenance', $needsMaintenance),
                'gifts' => $sectionLink('gifts', $needsGifts),
                'programs' => $sectionLink('programs', $needsPrograms),
                'certificates' => $sectionLink('certificates', $needsCertificates),
                'thanks_letters' => $sectionLink('thanks_letters', $needsCertificates),
                'invitations' => $sectionLink('invitations', $needsInvitations),
            ],
            'needs_ceremony_agenda' => $needsCeremonyAgenda,
            'ceremony' => [
                'need_code' => 'ceremony',
                'future_cycle_id' => null,
                'items_count' => $data['ceremony_items_count'] ?? ($ceremonyItems->count() ?: null),
                'time_from' => $data['ceremony_time_from'] ?? ($firstCeremonyItem['time_from'] ?? null),
                'time_to' => $data['ceremony_time_to'] ?? ($firstCeremonyItem['time_to'] ?? null),
                'item_name' => $data['ceremony_item_name'] ?? ($firstCeremonyItem['name'] ?? null),
                'item_description' => $data['ceremony_item_description'] ?? ($firstCeremonyItem['description'] ?? null),
                'items' => $ceremonyItems->all(),
            ],
            'needs_transport' => $needsTransport,
            'transport' => [
                'need_code' => 'transport',
                'future_cycle_id' => null,
                'vehicles_count' => $data['transport_vehicles_count'] ?? null,
                'vehicle_type' => $data['transport_vehicle_type'] ?? null,
                'passengers_count' => $data['transport_passengers_count'] ?? null,
                'trip_direction' => $data['transport_trip_direction'] ?? null,
                'start_from' => $data['transport_start_from'] ?? null,
                'start_to' => $data['transport_start_to'] ?? null,
            ],
            'needs_maintenance_workers' => $needsMaintenance,
            'maintenance' => [
                'need_code' => 'maintenance',
                'future_cycle_id' => null,
                                'type' => $data['maintenance_type'] ?? null,
            ],
            'needs_gifts' => $needsGifts,
            'gifts' => [
                'need_code' => 'gifts',
                'future_cycle_id' => null,
                'count' => $data['gifts_count'] ?? null,
                'description' => $data['gifts_description'] ?? null,
                'delivery_entity' => $data['gifts_delivery_entity'] ?? null,
            ],
            'needs_programs_participation' => $needsPrograms,
            'programs' => [
                'need_code' => 'programs',
                'future_cycle_id' => null,
                'need_trainer' => (bool) ($data['programs_need_trainer'] ?? false),
                'trainer_description' => $data['programs_trainer_description'] ?? null,
                'trainer_count' => $data['programs_trainer_count'] ?? null,
                'zaha_time_options' => collect($data['programs_zaha_time_options'] ?? [])->filter()->values()->all(),
                'zaha_time_other' => $data['programs_zaha_time_other'] ?? null,
                'show_name' => $data['programs_show_name'] ?? null,
                'show_description' => $data['programs_show_description'] ?? null,
                'fun_note' => $data['programs_fun_note'] ?? null,
            ],
            'needs_certificates_and_thanks' => $needsCertificates,
            'certificates' => [
                'need_code' => 'certificates',
                'future_cycle_id' => null,
                'count' => $data['certificates_count'] ?? null,
                'template' => $data['certificates_template'] ?? null,
                'for' => $data['certificates_for'] ?? null,
            ],
            'thanks_letters' => [
                'need_code' => 'thanks_letters',
                'future_cycle_id' => null,
                'count' => $data['thanks_letters_count'] ?? null,
                'template' => $data['thanks_letters_template'] ?? null,
                'for' => $data['thanks_letters_for'] ?? null,
            ],
            'needs_invitations' => $needsInvitations,
            'invitations' => [
                'need_code' => 'invitations',
                'future_cycle_id' => null,
                'type' => $data['invitation_type'] ?? null,
                'paper_template' => $data['invitation_paper_template'] ?? null,
                'paper_copies' => $data['invitation_paper_copies'] ?? null,
                'electronic_template' => $data['invitation_electronic_template'] ?? null,
            ],
        ];

        $data['execution_needs_payload'] = $payload;
    }

    protected function shouldSubmitFromRequest(Request $request): bool
    {
        return $request->input('submit_action') === 'submit';
    }

    protected function submitActivityForApproval(
        MonthlyActivity $monthlyActivity,
        User $actor,
        WorkflowNotificationService $workflowNotifications,
        MonthlyActivityLifecycleService $lifecycle,
        DynamicWorkflowService $dynamicWorkflowService,
        ?Request $request = null
    ): void {
        $instance = $dynamicWorkflowService->forModel('monthly_activities', $monthlyActivity);
        abort_unless($instance !== null, 422, __('app.roles.programs.monthly_activities.approvals.errors.no_active_workflow'));

        $currentStep = $dynamicWorkflowService->currentStep($instance);

        if ($instance->status === 'changes_requested' && $currentStep?->step_type !== 'sub') {
            $dynamicWorkflowService->markResubmitted($instance);
            $instance = $instance->fresh();
            $currentStep = $dynamicWorkflowService->currentStep($instance);
        }

        if ($currentStep?->step_type === 'sub') {
            WorkflowLog::query()->create([
                'workflow_instance_id' => $instance->id,
                'workflow_step_id' => $currentStep->id,
                'acted_by' => $actor->id,
                'action' => 'approved',
                'comment' => null,
                'edit_request_iteration' => (int) $instance->edit_request_count,
                'acted_at' => now(),
            ]);

            $dynamicWorkflowService->advanceToNextStep($instance->fresh());
            $instance = $instance->fresh();
        }

        $monthlyActivity->update([
            'status' => 'submitted',
        ]);

        if (($monthlyActivity->lifecycle_status ?: 'Draft') !== 'Submitted') {
            $lifecycle->transitionOrFail($monthlyActivity, 'Submitted');
        }

        $workflowNotifications->approvalRequested($instance, $monthlyActivity, route('role.programs.approvals.index'), $actor);

        if ($request && $request->user()) {
            $this->logWorkflowAction('submitted', $monthlyActivity, $request, 'submitted');
        }
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $viewScope = $request->input('scope', 'default');
        $selectedStatus = trim((string) $request->input('status', ''));
        $selectedSummaryFilter = trim((string) $request->input('summary_filter', ''));
        $requestedPerPage = filter_var($request->input('per_page'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]) ?: null;

        if ($viewScope === 'all_branches' && ! $this->canViewOtherBranches($user)) {
            abort(403);
        }

        $activitiesBaseQuery = MonthlyActivity::query()
            ->withCount('newerVersions')
            ->whereDoesntHave('newerVersions')
            ->enterpriseFilter($request->except('status'))
            ->notArchived();

        if ($viewScope !== 'all_branches') {
            $this->applyBranchVisibilityScope($activitiesBaseQuery, $user);
        }
        $this->applyDraftVisibilityScope($activitiesBaseQuery, $user);
        $this->applyVolunteerCoordinatorVisibilityScope($activitiesBaseQuery, $user);
        $this->applyMonthlyPageStatusFilter($activitiesBaseQuery, $selectedStatus);

        if ($viewScope === 'all_branches') {
            $this->applyOtherBranchesScope($activitiesBaseQuery, $user);

            $activitiesBaseQuery
                ->where('status', 'approved')
                ->where(function ($query) {
                    $query->where('executive_approval_status', 'approved')
                        ->orWhereIn('lifecycle_status', ['Exec Director Approved', 'Approved', 'Published'])
                        ->orWhereHas('workflowInstance', fn ($workflowQuery) => $workflowQuery->where('status', 'approved'));
                });
        }

        $summaryCards = $this->buildMonthlyIndexSummaryCards($activitiesBaseQuery);
        $this->applyMonthlyIndexSummaryFilter($activitiesBaseQuery, $selectedSummaryFilter);

        $activities = (clone $activitiesBaseQuery)
            ->with([
                'branch',
                'agendaEvent',
                'creator',
            ])
            ->orderBy('month')
            ->orderBy('day')
            ->paginate($this->monthlyIndexPerPage($requestedPerPage))
            ->withQueryString();

        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $this->scopedBranchIds($user);
        $ownBranchId = $this->ownBranchId($user);
        if ($scopedBranchIds !== [] && $viewScope !== 'all_branches') {
            $branches->where('id', $ownBranchId);
        }
        if ($viewScope === 'all_branches' && $ownBranchId) {
            $branches->where('id', '!=', $ownBranchId);
        }
        $branches = $branches->get();
        $agendaEvents = AgendaEvent::orderBy('month')->orderBy('day')->get();
        $filters = [
            'year' => $request->input('year'),
            'month' => $request->input('month'),
            'status' => $selectedStatus,
            'branch_id' => $request->input('branch_id'),
            'summary_filter' => $selectedSummaryFilter,
            'per_page' => $this->monthlyIndexPerPage($requestedPerPage),
        ];
        $canFilterBranches = $viewScope === 'all_branches'
            ? $this->canViewOtherBranches($user)
            : ($scopedBranchIds === []);

        $monthlyStatusOptions = $this->monthlyPageStatusOptions();
        $monthlyActivityEditRoles = self::MONTHLY_ACTIVITY_EDIT_ROLES;

        return view('pages.monthly_activities.activities.index', compact(
            'activities',
            'branches',
            'agendaEvents',
            'filters',
            'canFilterBranches',
            'viewScope',
            'monthlyStatusOptions',
            'summaryCards',
            'monthlyActivityEditRoles',
        ));
    }

    protected function buildMonthlyIndexSummaryCards(Builder $baseQuery): Collection
    {
        $cards = collect([
            [
                'key' => 'total',
                'filter_key' => '',
                'label' => __('app.roles.programs.monthly_activities.list_title'),
                'count' => (clone $baseQuery)->count(),
            ],
            [
                'key' => 'approved',
                'filter_key' => 'approved',
                'label' => __('app.roles.programs.monthly_activities.statuses.approved'),
                'count' => (clone $baseQuery)->where('status', 'approved')->count(),
            ],
        ]);

        // Group pending activities by the live workflow step that is currently waiting for approval.
        $pendingApprovalCards = (clone $baseQuery)
            ->with([
                'workflowInstance.currentStep.role',
                'workflowInstance.currentStep.permission',
            ])
            ->get()
            ->map(fn (MonthlyActivity $activity): ?array => $this->resolvePendingApprovalCardSnapshot($activity))
            ->filter()
            ->groupBy('step_id')
            ->map(function (Collection $group): array {
                $first = $group->first();

                return [
                    'key' => 'pending-step-' . $first['step_id'],
                    'filter_key' => 'pending_step:' . $first['step_id'],
                    'label' => $first['label'],
                    'count' => $group->count(),
                    'sort_order' => $first['sort_order'],
                ];
            })
            ->sortBy('sort_order')
            ->values()
            ->map(fn (array $card): array => Arr::except($card, ['sort_order']));

        return $cards->merge($pendingApprovalCards)->values();
    }

    protected function applyMonthlyIndexSummaryFilter(Builder $query, ?string $summaryFilter): void
    {
        $summaryFilter = trim((string) $summaryFilter);

        if ($summaryFilter === '') {
            return;
        }

        if ($summaryFilter === 'approved') {
            $query->where('status', 'approved');

            return;
        }

        if (preg_match('/^pending_step:(\d+)$/', $summaryFilter, $matches) === 1) {
            $stepId = (int) ($matches[1] ?? 0);

            if ($stepId <= 0) {
                return;
            }

            $query->whereHas('workflowInstance', function ($workflowQuery) use ($stepId) {
                $workflowQuery
                    ->where('current_step_id', $stepId)
                    ->whereNotIn('status', [
                        DynamicWorkflowService::DECISION_APPROVED,
                        DynamicWorkflowService::DECISION_REJECTED,
                        DynamicWorkflowService::DECISION_CHANGES_REQUESTED,
                    ]);
            });
        }
    }

    protected function resolvePendingApprovalCardSnapshot(MonthlyActivity $activity): ?array
    {
        $instance = $activity->workflowInstance;
        $currentStep = $instance?->currentStep;

        if (! $currentStep || (string) $currentStep->step_type === 'sub') {
            return null;
        }

        if (in_array((string) ($instance?->status ?? ''), [
            DynamicWorkflowService::DECISION_APPROVED,
            DynamicWorkflowService::DECISION_REJECTED,
            DynamicWorkflowService::DECISION_CHANGES_REQUESTED,
        ], true)) {
            return null;
        }

        $roleLabel = $currentStep->role?->display_name
            ?: ($currentStep->permission?->name
                ? $this->fallbackWorkflowFilterLabel($currentStep->permission->name)
                : ($currentStep->role?->name
                    ? $this->fallbackWorkflowFilterLabel($currentStep->role->name)
                    : null));

        if (! filled($roleLabel)) {
            return null;
        }

        return [
            'step_id' => (int) $currentStep->id,
            'label' => __('workflow_ui.approvals.filters.pending_role', ['role' => $roleLabel]),
            'sort_order' => ((int) $currentStep->step_order * 1000) + (int) ($currentStep->approval_level ?? 0),
        ];
    }

    protected function fallbackWorkflowFilterLabel(?string $value): string
    {
        if (! filled($value)) {
            return __('app.common.na');
        }

        return (string) Str::of($value)->replace('_', ' ')->title();
    }

    protected function monthlyPageStatusOptions(): Collection
    {
        return collect([
            (object) ['code' => 'draft', 'name' => __('app.roles.programs.monthly_activities.statuses.draft')],
            (object) ['code' => 'submitted', 'name' => __('app.roles.programs.monthly_activities.statuses.submitted')],
            (object) ['code' => 'approved', 'name' => __('app.roles.programs.monthly_activities.statuses.approved')],
        ]);
    }

    protected function applyMonthlyPageStatusFilter($query, ?string $status): void
    {
        $status = trim((string) $status);

        if ($status === '') {
            return;
        }

        $query->where(function ($statusQuery) use ($status) {
            match ($status) {
                'draft' => $statusQuery->where('status', 'draft'),
                'approved' => $statusQuery->whereIn('status', ['approved']),
                'submitted' => $statusQuery->whereIn('status', ['submitted', 'pending', 'in_review', 'changes_requested', 'rejected', 'postponed', 'cancelled', 'closed', 'completed', 'executed']),
                default => $statusQuery->where('status', $status),
            };
        });
    }

    public function create(Request $request)
    {
        $this->flashCreatePrefill($request);

        $user = $request->user();
        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $this->scopedBranchIds($user);
        if ($scopedBranchIds !== []) {
            $branches->whereIn('id', $scopedBranchIds);
        }
        $branches = $branches->get();
        $agendaEvents = $this->agendaEventsForUser($user);
        $targetGroups = TargetGroup::where('is_active', true)->orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::where('is_active', true)->orderBy('sort_order')->get();
        $zahaTimeOptions = ZahaTimeOption::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $monthlyStatusOptions = $this->monthlyCreationStatusOptions('draft');
        $executionStatusLabels = $this->executionStatusLabels();

        return view('pages.monthly_activities.activities.create', compact(
            'branches',
            'agendaEvents',
            'targetGroups',
            'evaluationQuestions',
            'zahaTimeOptions',
            'monthlyStatusOptions',
            'executionStatusLabels',
        ));
    }

    protected function flashFormPrefill(MonthlyActivity $monthlyActivity): void
    {
        if (session()->hasOldInput()) {
            return;
        }

        $monthlyActivity->loadMissing(['sponsors', 'partners', 'supplies', 'targetGroups']);

        $needsVolunteers = (bool) $monthlyActivity->needs_volunteers;
        $needsOfficialCorrespondence = (bool) $monthlyActivity->needs_official_correspondence;
        $outsideCenter = $monthlyActivity->location_type === 'outside_center';
        $needsSupplies = $monthlyActivity->supplies->isNotEmpty();
        [$volunteerAgeFrom, $volunteerAgeTo] = $this->extractVolunteerAgeBounds($monthlyActivity->volunteer_age_range);

        $prefill = array_merge($monthlyActivity->getAttributes(), [
            'title' => $monthlyActivity->title,
            'activity_date' => optional($monthlyActivity->activity_date)->toDateString() ?: optional($monthlyActivity->proposed_date)->toDateString(),
            'proposed_date' => optional($monthlyActivity->proposed_date)->toDateString(),
            'branch_id' => $monthlyActivity->branch_id,
            'agenda_event_id' => $monthlyActivity->agenda_event_id,
            'is_in_agenda' => (int) $monthlyActivity->is_in_agenda,
            'status' => $monthlyActivity->status,
            'execution_status' => $monthlyActivity->execution_status ?: 'executed',
            'location_type' => $monthlyActivity->location_type,
            'internal_location' => $outsideCenter ? null : $monthlyActivity->internal_location,
            'outside_place_name' => $outsideCenter ? $monthlyActivity->outside_place_name : null,
            'outside_google_maps_url' => $outsideCenter ? $monthlyActivity->outside_google_maps_url : null,
            'outside_contact_number' => $outsideCenter ? $monthlyActivity->outside_contact_number : null,
            'external_liaison_name' => $outsideCenter ? $monthlyActivity->external_liaison_name : null,
            'external_liaison_phone' => $outsideCenter ? $monthlyActivity->external_liaison_phone : null,
            'outside_address' => $outsideCenter ? $monthlyActivity->outside_address : null,
            'time_from' => optional($monthlyActivity->time_from)->format('H:i'),
            'time_to' => optional($monthlyActivity->time_to)->format('H:i'),
            'short_description' => $monthlyActivity->short_description,
            'description' => $monthlyActivity->description,
            'needs_volunteers' => (int) $needsVolunteers,
            'required_volunteers' => $needsVolunteers ? $monthlyActivity->required_volunteers : null,
            'volunteer_need' => $needsVolunteers ? $monthlyActivity->volunteer_need : null,
            'volunteer_age_range' => $needsVolunteers ? $monthlyActivity->volunteer_age_range : null,
            'volunteer_age_from' => $needsVolunteers ? $volunteerAgeFrom : null,
            'volunteer_age_to' => $needsVolunteers ? $volunteerAgeTo : null,
            'volunteer_gender' => $needsVolunteers ? $monthlyActivity->volunteer_gender : null,
            'volunteer_tasks_summary' => $needsVolunteers ? $monthlyActivity->volunteer_tasks_summary : null,
            'needs_official_correspondence' => (int) $needsOfficialCorrespondence,
            'official_correspondence_reason' => $needsOfficialCorrespondence ? $monthlyActivity->official_correspondence_reason : null,
            'official_correspondence_target' => $needsOfficialCorrespondence ? $monthlyActivity->official_correspondence_target : null,
            'official_correspondence_brief' => $needsOfficialCorrespondence ? $monthlyActivity->official_correspondence_brief : null,
            'rescheduled_date' => optional($monthlyActivity->rescheduled_date)->toDateString(),
            'reschedule_reason' => $monthlyActivity->reschedule_reason,
            'cancellation_reason' => $monthlyActivity->cancellation_reason,
            'requires_supplies' => (int) $needsSupplies,
            'supplies' => $needsSupplies
                ? $monthlyActivity->supplies->map(fn ($supply) => [
                    'item_name' => $supply->item_name,
                    'available' => (int) $supply->available,
                    'provider_type' => $supply->provider_type,
                    'provider_name' => $supply->provider_name,
                ])->values()->all()
                : [],
            'has_sponsor' => (int) $monthlyActivity->has_sponsor,
            'sponsors' => $monthlyActivity->sponsors->map(fn ($sponsor) => [
                'name' => $sponsor->name,
                'title' => $sponsor->title,
            ])->values()->all(),
            'has_partners' => (int) $monthlyActivity->has_partners,
            'partners' => $monthlyActivity->partners->map(fn ($partner) => [
                'name' => $partner->name,
                'role' => $partner->role,
            ])->values()->all(),
            'target_group_ids' => $monthlyActivity->targetGroups->pluck('id')->all(),
            'target_group_other' => $monthlyActivity->target_group_other,
            'planning_attachment' => $monthlyActivity->planning_attachment,
        ]);

        session()->flash('_old_input', $prefill);
    }

    protected function normalizeComparableValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        return $value;
    }

    protected function meaningfulChangedFields(array $oldValues, array $newValues): array
    {
        return collect($newValues)
            ->filter(function ($newValue, string $field) use ($oldValues) {
                return $this->normalizeComparableValue($oldValues[$field] ?? null) !== $this->normalizeComparableValue($newValue);
            })
            ->keys()
            ->values()
            ->all();
    }

    protected function activityHasApprovalTrail(MonthlyActivity $monthlyActivity): bool
    {
        $instance = WorkflowInstance::query()
            ->where('entity_type', MonthlyActivity::class)
            ->where('entity_id', $monthlyActivity->id)
            ->withCount('logs')
            ->first();

        if (! $instance) {
            return ! in_array((string) $monthlyActivity->status, ['draft', 'cancelled'], true);
        }

        return $instance->logs_count > 0
            || ! in_array((string) $instance->status, ['pending'], true)
            || ! in_array((string) $monthlyActivity->status, ['draft', 'cancelled'], true);
    }

    protected function shouldStartNewVersion(MonthlyActivity $monthlyActivity, array $changedFields, bool $isRescheduled): bool
    {
        if ($changedFields === []) {
            return false;
        }

        if (! $this->hasManagerOrLaterApproval($monthlyActivity)) {
            return false;
        }

        return $this->isApprovedVersion($monthlyActivity)
            || $isRescheduled
            || $this->activityHasApprovalTrail($monthlyActivity);
    }

    protected function hasManagerOrLaterApproval(MonthlyActivity $monthlyActivity): bool
    {
        return in_array((string) $monthlyActivity->relations_manager_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->programs_manager_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->liaison_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->hq_relations_manager_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->executive_approval_status, ['approved'], true)
            || in_array((string) $monthlyActivity->lifecycle_status, ['Branch Relations Manager Approved', 'Primary Relations Manager Approved', 'Executive Manager Approved', 'Exec Director Approved'], true)
            || $this->isApprovedVersion($monthlyActivity);
    }

    protected function isReadOnlyUnifiedAgendaActivity(MonthlyActivity $monthlyActivity): bool
    {
        $monthlyActivity->loadMissing('agendaEvent');

        return (bool) $monthlyActivity->is_from_agenda
            && (string) $monthlyActivity->plan_type === 'unified'
            && (string) optional($monthlyActivity->agendaEvent)->event_type === 'mandatory';
    }

    protected function canBranchEditUnifiedNonCoreFields(MonthlyActivity $monthlyActivity, ?User $user): bool
    {
        return $this->isReadOnlyUnifiedAgendaActivity($monthlyActivity)
            && (bool) config('monthly_activity.unified_branch_edit.enabled', true)
            && $this->shouldScopeToUserBranch($user)
            && ! $user?->hasRole('super_admin');
    }

    /**
     * @return array<int, string>
     */
    protected function unifiedLockedFields(): array
    {
        return collect(config('monthly_activity.unified_branch_edit.locked_fields', []))
            ->filter(fn ($field) => is_string($field) && $field !== '')
            ->values()
            ->all();
    }

    protected function applyUnifiedLockedFieldValues(MonthlyActivity $monthlyActivity, array &$data, User $user): void
    {
        if (! $this->canBranchEditUnifiedNonCoreFields($monthlyActivity, $user)) {
            return;
        }

        $lockedFields = $this->unifiedLockedFields();
        $locked = fn (string $field): bool => in_array($field, $lockedFields, true);

        if ($locked('title')) {
            $data['title'] = $monthlyActivity->title;
        }
        if ($locked('activity_date')) {
            $data['activity_date'] = optional($monthlyActivity->activity_date)->toDateString()
                ?: optional($monthlyActivity->proposed_date)->toDateString()
                ?: ($data['activity_date'] ?? null);
        }
        if ($locked('proposed_date')) {
            $data['proposed_date'] = optional($monthlyActivity->proposed_date)->toDateString() ?: ($data['proposed_date'] ?? null);
        }
        if ($locked('branch_id')) {
            $data['branch_id'] = (int) $monthlyActivity->branch_id;
        }
        if ($locked('agenda_event_id')) {
            $data['agenda_event_id'] = $monthlyActivity->agenda_event_id;
            $data['is_in_agenda'] = (bool) $monthlyActivity->is_in_agenda;
        }
        if ($locked('target_group_ids')) {
            $data['target_group_ids'] = $monthlyActivity->targetGroups()->pluck('target_groups.id')->map(fn ($id) => (int) $id)->all();
            $data['target_group_id'] = $monthlyActivity->target_group_id;
            $data['target_group_other'] = $monthlyActivity->target_group_other;
        }
        if ($locked('responsible_entities')) {
            $data['responsible_entities'] = array_values(array_filter([
                $monthlyActivity->requires_communications ? 'relations' : null,
                $monthlyActivity->requires_programs ? 'programs' : null,
            ]));
            $data['requires_programs'] = (bool) $monthlyActivity->requires_programs;
            $data['requires_communications'] = (bool) $monthlyActivity->requires_communications;
        }
    }

    protected function applyAgendaLockedFieldValues(array &$data): void
    {
        $agendaEventId = (int) ($data['agenda_event_id'] ?? 0);
        if ($agendaEventId <= 0) {
            return;
        }

        $agendaEvent = AgendaEvent::query()->find($agendaEventId);
        if (! $agendaEvent) {
            return;
        }

        $agendaDate = optional($agendaEvent->event_date)?->toDateString()
            ?? Carbon::create((int) ($data['year'] ?? now()->year), (int) $agendaEvent->month, (int) $agendaEvent->day)->toDateString();

        $data['title'] = (string) $agendaEvent->event_name;
        $data['description'] = (string) ($agendaEvent->notes ?? '');
        $data['proposed_date'] = $agendaDate;
        $data['activity_date'] = $agendaDate;
        $data['is_in_agenda'] = true;
    }

    public function syncFromAgenda(Request $request)
    {
        if ($branchId = $this->currentUserBranchId($request->user())) {
            $request->merge(['branch_id' => $branchId]);
        }

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'month' => ['required', 'integer', 'between:1,12'],
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
        ]);

        if (! $this->canAccessScopedBranch($request->user(), (int) $data['branch_id'])) {
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
            ->where('is_active', true)
            ->where(function ($query) use ($data) {
                $query->whereHas('participations', function ($participationQuery) use ($data) {
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

            $isReadOnlyUnified = (string) ($event->plan_type ?? 'non_unified') === 'unified'
                && (string) ($event->event_type ?? '') === 'mandatory';

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
                'status' => $isReadOnlyUnified ? 'approved' : 'draft',
                'execution_status' => 'executed',
                'relations_manager_approval_status' => $isReadOnlyUnified ? 'approved' : null,
                'executive_approval_status' => $isReadOnlyUnified ? 'approved' : null,
                'lifecycle_status' => $isReadOnlyUnified ? 'Approved' : null,
                'lock_at' => $this->buildLockAt(optional($event->event_date)?->toDateString() ?? Carbon::create($data['year'], $event->month, $event->day)->toDateString()),
                'is_official' => false,
                'branch_id' => (int) $data['branch_id'],
                'created_by' => $request->user()->id,
            ]);

            $created++;
        }

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.sync.done', ['count' => $created]));
    }

    public function store(
        Request $request,
        ConflictDetectionService $conflicts,
        MonthlyActivityWorkflowService $workflowService,
        WorkflowNotificationService $workflowNotifications,
        MonthlyActivityLifecycleService $lifecycle,
        DynamicWorkflowService $dynamicWorkflowService
    )
    {
        if ($request->hasFile('planning_attachment') && ! $request->hasFile('branch_plan_file')) {
            $request->files->set('branch_plan_file', $request->file('planning_attachment'));
        }

        if ($branchId = $this->currentUserBranchId($request->user())) {
            $request->merge(['branch_id' => $branchId]);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'activity_date' => ['required', 'date'],
            'proposed_date' => ['required', 'date'],
            'branch_id' => ['required', 'exists:branches,id'],
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
            'is_in_agenda' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
            'submit_action' => ['nullable', 'in:draft,submit'],
            'execution_status' => ['required', 'in:executed,postponed,cancelled'],
            'responsible_party' => ['nullable', 'string', 'max:255'],

            'location_type' => ['required', 'in:inside_center,outside_center'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'internal_location' => ['nullable', 'string', 'max:255', 'required_if:location_type,inside_center'],
            'outside_place_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'outside_google_maps_url' => array_merge($this->safeExternalUrlRules(), ['required_if:location_type,outside_center']),
            'outside_contact_number' => ['nullable', 'required_if:location_type,outside_center', 'regex:/^(\\+962|0)7[789]\\d{7}$/'],
            'external_liaison_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'external_liaison_phone' => ['nullable', 'string', 'max:50', 'required_if:location_type,outside_center'],
            'outside_address' => ['nullable', 'string'],
            'execution_time' => ['nullable', 'string', 'max:255'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_to' => ['nullable', 'date_format:H:i', 'after_or_equal:time_from'],
            'target_group' => ['nullable', 'string', 'max:255'],
            'target_group_id' => ['nullable', 'exists:target_groups,id'],
            'target_group_ids' => ['nullable', 'array'],
            'target_group_ids.*' => ['nullable', 'integer', 'exists:target_groups,id'],
            'target_group_other' => ['nullable', 'string', 'max:255', 'required_if:target_group,other'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'volunteer_need' => ['nullable', 'string', 'max:255'],
            'needs_volunteers' => ['nullable', 'boolean'],
            'required_volunteers' => ['nullable', 'integer', 'min:1', 'required_if:needs_volunteers,1'],
            'volunteer_age_from' => ['nullable', 'integer', 'min:10', 'max:80', 'required_if:needs_volunteers,1'],
            'volunteer_age_to' => ['nullable', 'integer', 'min:10', 'max:80', 'required_if:needs_volunteers,1', 'gte:volunteer_age_from'],
            'volunteer_age_range' => ['nullable', 'string', 'max:255'],
            'volunteer_gender' => ['nullable', 'in:male,female,both', 'required_if:needs_volunteers,1'],
            'volunteer_tasks_summary' => ['nullable', 'string', 'max:1500', 'required_if:needs_volunteers,1'],
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
            'planning_attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'needs_official_correspondence' => ['nullable', 'boolean'],
            'official_correspondence_reason' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_target' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_brief' => ['nullable', 'string', 'max:1500', 'required_if:needs_official_correspondence,1'],
            'has_sponsor' => ['nullable', 'boolean'],
            'sponsor_name_title' => ['nullable', 'string', 'max:255'],
            'has_partners' => ['nullable', 'boolean'],
            'partner_1_name' => ['nullable', 'string', 'max:255'],
            'partner_1_role' => ['nullable', 'string', 'max:255'],
            'partner_2_name' => ['nullable', 'string', 'max:255'],
            'partner_2_role' => ['nullable', 'string', 'max:255'],
            'partner_3_name' => ['nullable', 'string', 'max:255'],
            'partner_3_role' => ['nullable', 'string', 'max:255'],
            'rescheduled_date' => ['nullable', 'date', 'required_if:execution_status,postponed'],
            'reschedule_reason' => ['nullable', 'string', 'required_if:execution_status,postponed'],
            'cancellation_reason' => ['nullable', 'string', 'required_if:execution_status,cancelled'],
            'relations_approval_on_reschedule' => ['nullable', 'boolean'],
            'audience_satisfaction_percent' => ['nullable', 'numeric', 'between:0,100'],
            'evaluation_score' => ['nullable', 'numeric', 'between:0,100'],
            'sponsors' => ['array'],
            'sponsors.*.name' => ['nullable', 'string', 'max:255'],
            'sponsors.*.title' => ['nullable', 'string', 'max:255'],
            'sponsors.*.is_official' => ['nullable', 'boolean'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:255'],
            'partners.*.role' => ['nullable', 'required_with:partners.*.name', 'string', 'max:255'],
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
            'supplies.*.provider_type' => ['nullable', 'string', 'max:255', 'required_if:supplies.*.available,0'],
            'supplies.*.provider_name' => ['nullable', 'string', 'max:255', 'required_if:supplies.*.available,0'],
            'evaluations' => ['nullable', 'array'],
            'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
            'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
            'evaluations.*.note' => ['nullable', 'string'],
            'followup_remarks' => ['nullable', 'string'],
            'execution_needs_followup' => ['nullable', 'array'],
            'execution_needs_followup.*.status' => ['nullable', 'in:secured,not_secured'],
            'execution_needs_followup.*.reason' => ['nullable', 'string', 'max:1000'],
            'execution_needs_followup.*.notes' => ['nullable', 'string', 'max:1000'],
            'execution_needs_followup.*.effectiveness_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            'needs_ceremony_agenda' => ['nullable', 'boolean'],
            'ceremony_items_count' => ['nullable', 'integer', 'min:1'],
            'ceremony_time_from' => ['nullable', 'date_format:H:i'],
            'ceremony_time_to' => ['nullable', 'date_format:H:i', 'after_or_equal:ceremony_time_from'],
            'ceremony_item_name' => ['nullable', 'string', 'max:255'],
            'ceremony_item_description' => ['nullable', 'string', 'max:500'],
            'ceremony_items' => ['nullable', 'array'],
            'ceremony_items.*.order' => ['nullable', 'integer', 'min:1'],
            'ceremony_items.*.name' => ['nullable', 'string', 'max:255'],
            'ceremony_items.*.time_from' => ['nullable', 'date_format:H:i'],
            'ceremony_items.*.time_to' => ['nullable', 'date_format:H:i'],
            'ceremony_items.*.description' => ['nullable', 'string', 'max:500'],
            'needs_transport' => ['nullable', 'boolean'],
            'transport_vehicles_count' => ['nullable', 'integer', 'min:1'],
            'transport_vehicle_type' => ['nullable', 'in:bus,car'],
            'transport_passengers_count' => ['nullable', 'integer', 'min:1'],
            'transport_trip_direction' => ['nullable', 'in:go_only,round_trip,return_only'],
            'transport_start_from' => ['nullable', 'string', 'max:255'],
            'transport_start_to' => ['nullable', 'string', 'max:255'],
            'needs_maintenance_workers' => ['nullable', 'boolean'],
                        'maintenance_type' => ['nullable', 'string', 'max:255'],
            'needs_gifts' => ['nullable', 'boolean'],
            'gifts_count' => ['nullable', 'integer', 'min:1'],
            'gifts_description' => ['nullable', 'string', 'max:500'],
            'gifts_delivery_entity' => ['nullable', 'string', 'max:255'],
            'needs_programs_participation' => ['nullable', 'boolean'],
            'programs_need_trainer' => ['nullable', 'boolean'],
            'programs_needs_zaha_time' => ['nullable', 'boolean'],
            'programs_needs_show' => ['nullable', 'boolean'],
            'programs_needs_fun' => ['nullable', 'boolean'],
            'programs_trainer_description' => ['nullable', 'string', 'max:255'],
            'programs_trainer_count' => ['nullable', 'integer', 'min:1'],
            'programs_zaha_time_options' => ['nullable', 'array'],
            'programs_zaha_time_options.*' => ['nullable', 'string', 'max:100'],
            'programs_zaha_time_other' => ['nullable', 'string', 'max:255'],
            'programs_show_name' => ['nullable', 'string', 'max:255'],
            'programs_show_description' => ['nullable', 'string', 'max:500'],
            'programs_fun_note' => ['nullable', 'string', 'max:255'],
            'needs_certificates_and_thanks' => ['nullable', 'boolean'],
            'needs_certificates_details' => ['nullable', 'boolean'],
            'needs_thanks_letters_details' => ['nullable', 'boolean'],
            'certificates_count' => ['nullable', 'integer', 'min:1'],
            'certificates_template' => ['nullable', 'string', 'max:255'],
            'certificates_for' => ['nullable', 'string', 'max:255'],
            'thanks_letters_count' => ['nullable', 'integer', 'min:1'],
            'thanks_letters_template' => ['nullable', 'string', 'max:255'],
            'thanks_letters_for' => ['nullable', 'string', 'max:255'],
            'needs_invitations' => ['nullable', 'boolean'],
            'invitation_type' => ['nullable', 'in:paper,electronic'],
            'invitation_paper_template' => ['nullable', 'string', 'max:255'],
            'invitation_paper_copies' => ['nullable', 'integer', 'min:1'],
            'invitation_electronic_template' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $this->applyAgendaLockedFieldValues($data);

        if (! $this->canAccessScopedBranch($request->user(), (int) $data['branch_id'])) {
            abort(403);
        }

        if (! empty($data['agenda_event_id'])) {
            $hasActiveForSameAgenda = MonthlyActivity::query()
                ->where('branch_id', (int) $data['branch_id'])
                ->where('agenda_event_id', (int) $data['agenda_event_id'])
                ->where('status', '!=', 'cancelled')
                ->whereDoesntHave('newerVersions')
                ->exists();

            if ($hasActiveForSameAgenda) {
                return back()->withErrors(['agenda_event_id' => 'لا يمكن ربط أكثر من خطة فعالة لنفس الفرع مع نفس فعالية الأجندة.'])->withInput();
            }
        }

        $this->normalizePlanningPayload($data);

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
            'activity_date' => $date->toDateString(),
            'branch_plan_file' => $request->file('branch_plan_file')?->store('monthly/plans/v1', 'public'),
            'description' => $data['description'] ?? null,
            'location_type' => $data['location_type'],
            'location_details' => $data['location_details'] ?? null,
            'internal_location' => $data['internal_location'] ?? null,
            'outside_place_name' => $data['outside_place_name'] ?? null,
            'outside_google_maps_url' => $data['outside_google_maps_url'] ?? null,
            'outside_contact_number' => $data['outside_contact_number'] ?? null,
            'external_liaison_name' => $data['external_liaison_name'] ?? null,
            'external_liaison_phone' => $data['external_liaison_phone'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'status' => 'draft',
            'execution_status' => $data['execution_status'],
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
            'short_description' => $data['short_description'] ?? null,
            'work_teams_count' => $data['work_teams_count'] ?? null,
            'volunteer_need' => $data['volunteer_need'] ?? null,
            'needs_volunteers' => (bool) ($data['needs_volunteers'] ?? false),
            'required_volunteers' => $data['required_volunteers'] ?? null,
            'volunteer_age_range' => $data['volunteer_age_range'] ?? null,
            'volunteer_gender' => $data['volunteer_gender'] ?? null,
            'volunteer_tasks_summary' => $data['volunteer_tasks_summary'] ?? null,
            'expected_attendance' => $data['expected_attendance'] ?? null,
            'actual_attendance' => $data['actual_attendance'] ?? null,
            'attendance_notes' => $data['attendance_notes'] ?? null,
            'has_sponsor' => (bool) (($data['has_sponsor'] ?? false) || !empty($data['sponsors'] ?? [])),
            'sponsor_name_title' => $data['sponsor_name_title'] ?? null,
            'has_partners' => (bool) (($data['has_partners'] ?? false) || !empty($data['partners'] ?? [])),
            'needs_official_letters' => false,
            'needs_official_correspondence' => (bool) ($data['needs_official_correspondence'] ?? false),
            'official_correspondence_reason' => $data['official_correspondence_reason'] ?? null,
            'official_correspondence_target' => $data['official_correspondence_target'] ?? null,
            'official_correspondence_brief' => $data['official_correspondence_brief'] ?? null,
            'letter_purpose' => null,
            'rescheduled_date' => $data['rescheduled_date'] ?? null,
            'reschedule_reason' => $data['reschedule_reason'] ?? null,
            'cancellation_reason' => $data['cancellation_reason'] ?? null,
            'relations_approval_on_reschedule' => (bool) ($data['relations_approval_on_reschedule'] ?? false),
            'audience_satisfaction_percent' => $data['audience_satisfaction_percent'] ?? null,
            'evaluation_score' => $data['evaluation_score'] ?? null,
            'needs_media_coverage' => (bool) ($data['needs_media_coverage'] ?? false),
            'media_coverage_notes' => $data['media_coverage_notes'] ?? null,
            'requires_programs' => (bool) (($data['requires_programs'] ?? false) || in_array('programs', $data['responsible_entities'] ?? [], true)),
            'is_program_related' => (bool) ($data['is_program_related'] ?? false),
            'requires_workshops' => (bool) ($data['requires_workshops'] ?? false),
            'requires_communications' => (bool) (($data['requires_communications'] ?? false) || ($data['needs_media_coverage'] ?? false) || in_array('relations', $data['responsible_entities'] ?? [], true)),
            'execution_needs_payload' => $data['execution_needs_payload'] ?? null,
            'execution_needs_followup' => $data['execution_needs_followup'] ?? null,
            'lock_at' => $this->buildLockAt($data['proposed_date']),
            'is_official' => false,
            'branch_id' => $data['branch_id'],
            'created_by' => $request->user()->id,
        ]);

        $workflowService->initializeDynamicStatuses($monthlyActivity);
        $this->syncTargetGroups($monthlyActivity, $data);
        Log::info('monthly_activity.created', [
            'monthly_activity_id' => $monthlyActivity->id,
            'created_by' => $request->user()->id,
            'plan_version' => $monthlyActivity->plan_version,
        ]);

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
        $this->notifyExecutionNeedOwners($monthlyActivity);

        if ($this->shouldSubmitFromRequest($request)) {
            $this->submitActivityForApproval($monthlyActivity, $request->user(), $workflowNotifications, $lifecycle, $dynamicWorkflowService, $request);
        } else {
            $workflowNotifications->created($monthlyActivity, $request->user(), route('role.relations.activities.show', $monthlyActivity));
        }

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.created'))
            ->with('warning', $conflictWarning);
    }

    public function edit(MonthlyActivity $monthlyActivity)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());

        if (! request()->boolean('form') && request('mode') !== 'post') {
            $monthlyActivity->load(['branch', 'creator', 'agendaEvent', 'sponsors', 'partners', 'supplies', 'team', 'targetGroups'])
                ->loadCount('newerVersions');

            $monthlyStatusLabels = $this->statusLookupOptions('monthly_activities', [], (string) $monthlyActivity->status)
                ->pluck('name', 'code')
                ->all();
            $executionStatusLabels = $this->executionStatusLabels();
            $archivedVersions = collect();
            $cursor = $monthlyActivity->previousVersion;
            while ($cursor) {
                $archivedVersions->push($cursor);
                $cursor = $cursor->previousVersion;
            }

            return view('pages.monthly_activities.activities.show', [
                'monthlyActivity' => $monthlyActivity,
                'editMirrorMode' => true,
                'monthlyStatusLabels' => $monthlyStatusLabels,
                'executionStatusLabels' => $executionStatusLabels,
                'archivedVersions' => $archivedVersions,
            ]);
        }

        $monthlyActivity->load(['agendaEvent', 'supplies', 'team', 'attachments', 'approvals', 'sponsors', 'partners', 'evaluationResponses.question', 'followups']);
        if (request()->boolean('form')) {
            $this->flashFormPrefill($monthlyActivity);
        }
        $branches = Branch::query()->orderBy('name');
        $scopedBranchIds = $this->scopedBranchIds(request()->user());
        if ($scopedBranchIds !== []) {
            $branches->whereIn('id', $scopedBranchIds);
        }
        $branches = $branches->get();
        $agendaEvents = $this->agendaEventsForUser(request()->user(), $monthlyActivity);
        $targetGroups = TargetGroup::where('is_active', true)->orderBy('sort_order')->get();
        $evaluationQuestions = EvaluationQuestion::where('is_active', true)->orderBy('sort_order')->get();
        $zahaTimeOptions = ZahaTimeOption::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $monthlyStatusOptions = $this->monthlyPlanningStatusOptions((string) $monthlyActivity->status);
        $monthlyCloseStatusOptions = $this->monthlyCloseStatusOptions((string) $monthlyActivity->status);
        $executionStatusLabels = $this->executionStatusLabels();

        return view('pages.monthly_activities.activities.edit', compact(
            'monthlyActivity',
            'branches',
            'agendaEvents',
            'targetGroups',
            'evaluationQuestions',
            'zahaTimeOptions',
            'monthlyStatusOptions',
            'monthlyCloseStatusOptions',
            'executionStatusLabels',
        ));
    }

    public function show(MonthlyActivity $monthlyActivity, MonthlyWorkflowPresenter $monthlyWorkflowPresenter)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());

        $monthlyActivity->load([
            'branch',
            'creator',
            'agendaEvent',
            'sponsors',
            'partners',
            'supplies',
            'team',
            'targetGroups',
            'attachments.uploader',
            'workflowInstance.workflow.steps.role',
            'workflowInstance.currentStep.role',
            'workflowInstance.currentStep.permission',
            'workflowInstance.logs.step.role',
            'workflowInstance.logs.step.permission',
            'workflowInstance.logs.actor',
        ])
            ->loadCount('newerVersions');
        $monthlyWorkflowPresenter->attach($monthlyActivity, request()->user());
        $monthlyStatusLabels = $this->statusLookupOptions('monthly_activities', [], (string) $monthlyActivity->status)
            ->pluck('name', 'code')
            ->all();
        $executionStatusLabels = $this->executionStatusLabels();
        $archivedVersions = collect();
        $cursor = $monthlyActivity->previousVersion;
        while ($cursor) {
            $archivedVersions->push($cursor);
            $cursor = $cursor->previousVersion;
        }

        return view('pages.monthly_activities.activities.show', compact('monthlyActivity', 'monthlyStatusLabels', 'executionStatusLabels', 'archivedVersions'));
    }

    public function update(
        Request $request,
        MonthlyActivity $monthlyActivity,
        ConflictDetectionService $conflicts,
        MonthlyActivityWorkflowService $workflowService,
        WorkflowNotificationService $workflowNotifications,
        MonthlyActivityLifecycleService $lifecycle,
        DynamicWorkflowService $dynamicWorkflowService
    )
    {
        if ($request->hasFile('planning_attachment') && ! $request->hasFile('branch_plan_file')) {
            $request->files->set('branch_plan_file', $request->file('planning_attachment'));
        }

        $this->ensureActivityVisibleToUser($monthlyActivity, $request->user());

        if ($this->isSupersededVersion($monthlyActivity)) {
            return back()->withErrors([
                'status' => 'هذه نسخة قديمة من النشاط. يرجى متابعة آخر نسخة فقط.',
            ]);
        }

        if ($branchId = $this->currentUserBranchId($request->user())) {
            $request->merge(['branch_id' => $branchId]);
        }

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

        if ($request->boolean('post_execution_needs_only')) {
            $data = $request->validate([
                'execution_needs_followup' => ['nullable', 'array'],
                'execution_needs_followup.*.status' => ['nullable', 'in:secured,not_secured'],
                'execution_needs_followup.*.reason' => ['nullable', 'string', 'max:1000'],
                'execution_needs_followup.*.notes' => ['nullable', 'string', 'max:1000'],
                'execution_needs_followup.*.effectiveness_score' => ['nullable', 'integer', 'min:0', 'max:10'],
            ]);

            $this->normalizeExecutionNeedsFollowup($data);
            $this->filterExecutionNeedsFollowupToEnabled($monthlyActivity->fresh(['supplies']), $data);
            $monthlyActivity->update([
                'execution_needs_followup' => $data['execution_needs_followup'] ?? null,
            ]);
            $this->logWorkflowAction('execution_needs_followup_updated', $monthlyActivity, $request, $monthlyActivity->status);

            return redirect()
                ->route('role.relations.activities.edit', ['monthlyActivity' => $monthlyActivity, 'mode' => 'post'])
                ->with('status', 'تم حفظ متابعة احتياجات التنفيذ بنجاح.');
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
            'agenda_event_id' => ['nullable', 'exists:agenda_events,id'],
            'is_in_agenda' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'max:50'],
            'submit_action' => ['nullable', 'in:draft,submit'],
            'execution_status' => ['required', 'in:executed,postponed,cancelled'],
            'responsible_party' => ['nullable', 'string', 'max:255'],

            'location_type' => ['required', 'in:inside_center,outside_center'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'internal_location' => ['nullable', 'string', 'max:255', 'required_if:location_type,inside_center'],
            'outside_place_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'outside_google_maps_url' => array_merge($this->safeExternalUrlRules(), ['required_if:location_type,outside_center']),
            'outside_contact_number' => ['nullable', 'required_if:location_type,outside_center', 'regex:/^(\\+962|0)7[789]\\d{7}$/'],
            'external_liaison_name' => ['nullable', 'string', 'max:255', 'required_if:location_type,outside_center'],
            'external_liaison_phone' => ['nullable', 'string', 'max:50', 'required_if:location_type,outside_center'],
            'outside_address' => ['nullable', 'string'],
            'execution_time' => ['nullable', 'string', 'max:255'],
            'time_from' => ['nullable', 'date_format:H:i'],
            'time_to' => ['nullable', 'date_format:H:i', 'after_or_equal:time_from'],
            'target_group' => ['nullable', 'string', 'max:255'],
            'target_group_id' => ['nullable', 'exists:target_groups,id'],
            'target_group_ids' => ['nullable', 'array'],
            'target_group_ids.*' => ['nullable', 'integer', 'exists:target_groups,id'],
            'target_group_other' => ['nullable', 'string', 'max:255', 'required_if:target_group,other'],
            'short_description' => ['nullable', 'string', 'max:255'],
            'volunteer_need' => ['nullable', 'string', 'max:255'],
            'needs_volunteers' => ['nullable', 'boolean'],
            'required_volunteers' => ['nullable', 'integer', 'min:1', 'required_if:needs_volunteers,1'],
            'volunteer_age_from' => ['nullable', 'integer', 'min:10', 'max:80', 'required_if:needs_volunteers,1'],
            'volunteer_age_to' => ['nullable', 'integer', 'min:10', 'max:80', 'required_if:needs_volunteers,1', 'gte:volunteer_age_from'],
            'volunteer_age_range' => ['nullable', 'string', 'max:255'],
            'volunteer_gender' => ['nullable', 'in:male,female,both', 'required_if:needs_volunteers,1'],
            'volunteer_tasks_summary' => ['nullable', 'string', 'max:1500', 'required_if:needs_volunteers,1'],
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
            'planning_attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,xlsx,xls', 'max:5120'],
            'needs_official_correspondence' => ['nullable', 'boolean'],
            'official_correspondence_reason' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_target' => ['nullable', 'string', 'max:255', 'required_if:needs_official_correspondence,1'],
            'official_correspondence_brief' => ['nullable', 'string', 'max:1500', 'required_if:needs_official_correspondence,1'],
            'has_sponsor' => ['nullable', 'boolean'],
            'sponsor_name_title' => ['nullable', 'string', 'max:255'],
            'has_partners' => ['nullable', 'boolean'],
            'partner_1_name' => ['nullable', 'string', 'max:255'],
            'partner_1_role' => ['nullable', 'string', 'max:255'],
            'partner_2_name' => ['nullable', 'string', 'max:255'],
            'partner_2_role' => ['nullable', 'string', 'max:255'],
            'partner_3_name' => ['nullable', 'string', 'max:255'],
            'partner_3_role' => ['nullable', 'string', 'max:255'],
            'rescheduled_date' => ['nullable', 'date', 'required_if:execution_status,postponed'],
            'reschedule_reason' => ['nullable', 'string', 'required_if:execution_status,postponed'],
            'cancellation_reason' => ['nullable', 'string', 'required_if:execution_status,cancelled'],
            'relations_approval_on_reschedule' => ['nullable', 'boolean'],
            'audience_satisfaction_percent' => ['nullable', 'numeric', 'between:0,100'],
            'evaluation_score' => ['nullable', 'numeric', 'between:0,100'],
            'sponsors' => ['array'],
            'sponsors.*.name' => ['nullable', 'string', 'max:255'],
            'sponsors.*.title' => ['nullable', 'string', 'max:255'],
            'sponsors.*.is_official' => ['nullable', 'boolean'],
            'partners' => ['array'],
            'partners.*.name' => ['nullable', 'string', 'max:255'],
            'partners.*.role' => ['nullable', 'required_with:partners.*.name', 'string', 'max:255'],
            'partners.*.contact_info' => ['nullable', 'string', 'max:255'],
            'evaluations' => ['nullable', 'array'],
                'evaluations.*.score' => ['nullable', 'numeric', 'between:0,5'],
                'evaluations.*.answer_value' => ['nullable', 'string', 'max:255'],
                'evaluations.*.note' => ['nullable', 'string'],
                'followup_remarks' => ['nullable', 'string'],
                'needs_ceremony_agenda' => ['nullable', 'boolean'],
                'ceremony_items_count' => ['nullable', 'integer', 'min:1'],
                'ceremony_time_from' => ['nullable', 'date_format:H:i'],
                'ceremony_time_to' => ['nullable', 'date_format:H:i', 'after_or_equal:ceremony_time_from'],
                'ceremony_item_name' => ['nullable', 'string', 'max:255'],
                'ceremony_item_description' => ['nullable', 'string', 'max:500'],
                'ceremony_items' => ['nullable', 'array'],
                'ceremony_items.*.order' => ['nullable', 'integer', 'min:1'],
                'ceremony_items.*.name' => ['nullable', 'string', 'max:255'],
                'ceremony_items.*.time_from' => ['nullable', 'date_format:H:i'],
                'ceremony_items.*.time_to' => ['nullable', 'date_format:H:i'],
                'ceremony_items.*.description' => ['nullable', 'string', 'max:500'],
                'needs_transport' => ['nullable', 'boolean'],
                'transport_vehicles_count' => ['nullable', 'integer', 'min:1'],
                'transport_vehicle_type' => ['nullable', 'in:bus,car'],
                'transport_passengers_count' => ['nullable', 'integer', 'min:1'],
            'transport_trip_direction' => ['nullable', 'in:go_only,round_trip,return_only'],
            'transport_start_from' => ['nullable', 'string', 'max:255'],
            'transport_start_to' => ['nullable', 'string', 'max:255'],
                'needs_maintenance_workers' => ['nullable', 'boolean'],
                                'maintenance_type' => ['nullable', 'string', 'max:255'],
                'needs_gifts' => ['nullable', 'boolean'],
                'gifts_count' => ['nullable', 'integer', 'min:1'],
                'gifts_description' => ['nullable', 'string', 'max:500'],
                'gifts_delivery_entity' => ['nullable', 'string', 'max:255'],
                'needs_programs_participation' => ['nullable', 'boolean'],
                'programs_need_trainer' => ['nullable', 'boolean'],
                'programs_needs_zaha_time' => ['nullable', 'boolean'],
                'programs_needs_show' => ['nullable', 'boolean'],
                'programs_needs_fun' => ['nullable', 'boolean'],
                'programs_trainer_description' => ['nullable', 'string', 'max:255'],
                'programs_trainer_count' => ['nullable', 'integer', 'min:1'],
                'programs_zaha_time_options' => ['nullable', 'array'],
                'programs_zaha_time_options.*' => ['nullable', 'string', 'max:100'],
                'programs_zaha_time_other' => ['nullable', 'string', 'max:255'],
                'programs_show_name' => ['nullable', 'string', 'max:255'],
                'programs_show_description' => ['nullable', 'string', 'max:500'],
                'programs_fun_note' => ['nullable', 'string', 'max:255'],
                'needs_certificates_and_thanks' => ['nullable', 'boolean'],
                'needs_certificates_details' => ['nullable', 'boolean'],
                'needs_thanks_letters_details' => ['nullable', 'boolean'],
                'certificates_count' => ['nullable', 'integer', 'min:1'],
                'certificates_template' => ['nullable', 'string', 'max:255'],
                'certificates_for' => ['nullable', 'string', 'max:255'],
                'thanks_letters_count' => ['nullable', 'integer', 'min:1'],
                'thanks_letters_template' => ['nullable', 'string', 'max:255'],
                'thanks_letters_for' => ['nullable', 'string', 'max:255'],
                'needs_invitations' => ['nullable', 'boolean'],
                'invitation_type' => ['nullable', 'in:paper,electronic'],
                'invitation_paper_template' => ['nullable', 'string', 'max:255'],
                'invitation_paper_copies' => ['nullable', 'integer', 'min:1'],
                'invitation_electronic_template' => ['nullable', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:2000'],
            ]);

        $this->applyAgendaLockedFieldValues($data);
        $this->applyUnifiedLockedFieldValues($monthlyActivity, $data, $request->user());

        if (! $this->canAccessScopedBranch($request->user(), (int) $data['branch_id'])) {
            abort(403);
        }

        if (! empty($data['agenda_event_id'])) {
            $hasActiveForSameAgenda = MonthlyActivity::query()
                ->where('branch_id', (int) $data['branch_id'])
                ->where('agenda_event_id', (int) $data['agenda_event_id'])
                ->where('status', '!=', 'cancelled')
                ->whereDoesntHave('newerVersions')
                ->where('id', '!=', $monthlyActivity->id)
                ->exists();

            if ($hasActiveForSameAgenda) {
                return back()->withErrors(['agenda_event_id' => 'لا يمكن ربط أكثر من خطة فعالة لنفس الفرع مع نفس فعالية الأجندة.'])->withInput();
            }
        }

        $this->normalizePlanningPayload($data);

        $date = Carbon::parse($data['activity_date']);
        $conflictNames = $conflicts->findMonthlyActivityConflicts($data['proposed_date'], (int) $data['branch_id'], $monthlyActivity->id, $data['execution_time'] ?? null);
        $conflictWarning = empty($conflictNames) ? null : __('Potential overlap with: :activities', ['activities' => implode(', ', $conflictNames)]);
        $isFromAgenda = ! empty($data['agenda_event_id']);
        $planType = $isFromAgenda ? optional(AgendaEvent::find($data['agenda_event_id']))->plan_type : 'non_unified';
        $branchPlanFile = $monthlyActivity->branch_plan_file;
        $branchPlanLocked = $this->canBranchEditUnifiedNonCoreFields($monthlyActivity, $request->user())
            && in_array('planning_attachment', $this->unifiedLockedFields(), true);
        if ($request->hasFile('branch_plan_file') && ! $branchPlanLocked) {
            if ($branchPlanFile) {
                Storage::disk('public')->delete($branchPlanFile);
            }
            $nextVersionPath = 'monthly/plans/v'.((int) ($monthlyActivity->plan_version ?: 1));
            $branchPlanFile = $request->file('branch_plan_file')->store($nextVersionPath, 'public');
        }

        $oldValues = $monthlyActivity->only([
            'title',
            'activity_date',
            'proposed_date',
            'agenda_event_id',
            'is_in_agenda',
            'description',
            'location_type',
            'location_details',
            'internal_location',
            'outside_place_name',
            'outside_google_maps_url',
            'outside_contact_number',
            'external_liaison_name',
            'external_liaison_phone',
            'outside_address',
            'status',
            'execution_status',
            'plan_stage',
            'plan_version',
            'responsible_party',
            'execution_time',
            'time_from',
            'time_to',
            'target_group',
            'target_group_id',
            'target_group_other',
            'short_description',
            'work_teams_count',
            'volunteer_need',
            'needs_volunteers',
            'required_volunteers',
            'volunteer_age_range',
            'volunteer_gender',
            'volunteer_tasks_summary',
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
            'official_correspondence_brief',
            'letter_purpose',
            'rescheduled_date',
            'reschedule_reason',
            'cancellation_reason',
            'relations_approval_on_reschedule',
            'audience_satisfaction_percent',
            'evaluation_score',
            'requires_programs',
            'requires_workshops',
            'requires_communications',
            'is_program_related',
            'execution_needs_payload',
            'execution_needs_followup',
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

        $isRescheduled = ($data['execution_status'] ?? null) === 'postponed'
            || (
                ! empty($data['rescheduled_date'])
                && optional($monthlyActivity->rescheduled_date)?->toDateString() !== (string) $data['rescheduled_date']
            );
        $nextStage = (int) ($monthlyActivity->plan_stage ?: 1);
        $nextVersion = (int) ($monthlyActivity->plan_version ?: 1);
        $newStatus = $this->shouldSubmitFromRequest($request) ? $monthlyActivity->status : 'draft';
        $newLifecycleStatus = $monthlyActivity->lifecycle_status ?: 'Draft';
        $startsNewVersion = false;

        $newValues = [
            'month' => (int) $date->format('m'),
            'day' => (int) $date->format('d'),
            'title' => $data['title'],
            'activity_date' => $date->toDateString(),
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
            'outside_contact_number' => $data['outside_contact_number'] ?? null,
            'external_liaison_name' => $data['external_liaison_name'] ?? null,
            'external_liaison_phone' => $data['external_liaison_phone'] ?? null,
            'outside_address' => $data['outside_address'] ?? null,
            'status' => $newStatus,
            'execution_status' => $data['execution_status'],
            'plan_stage' => $nextStage,
            'plan_version' => $nextVersion,
            'previous_version_id' => $startsNewVersion ? $monthlyActivity->id : $monthlyActivity->previous_version_id,
            'responsible_party' => $data['responsible_party'] ?? null,
            'execution_time' => $data['execution_time'] ?? null,
            'time_from' => $data['time_from'] ?? null,
            'time_to' => $data['time_to'] ?? null,
            'target_group' => $data['target_group'] ?? null,
            'target_group_id' => $data['target_group_id'] ?? ($data['target_group_ids'][0] ?? null),
            'target_group_other' => $data['target_group_other'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'work_teams_count' => $data['work_teams_count'] ?? null,
            'volunteer_need' => $data['volunteer_need'] ?? null,
            'needs_volunteers' => (bool) ($data['needs_volunteers'] ?? false),
            'required_volunteers' => $data['required_volunteers'] ?? null,
            'volunteer_age_range' => $data['volunteer_age_range'] ?? null,
            'volunteer_gender' => $data['volunteer_gender'] ?? null,
            'volunteer_tasks_summary' => $data['volunteer_tasks_summary'] ?? null,
            'expected_attendance' => $data['expected_attendance'] ?? null,
            'actual_attendance' => $data['actual_attendance'] ?? null,
            'attendance_notes' => $data['attendance_notes'] ?? null,
            'has_sponsor' => (bool) (($data['has_sponsor'] ?? false) || !empty($data['sponsors'] ?? [])),
            'sponsor_name_title' => $data['sponsor_name_title'] ?? null,
            'has_partners' => (bool) (($data['has_partners'] ?? false) || !empty($data['partners'] ?? [])),
            'needs_official_letters' => false,
            'needs_official_correspondence' => (bool) ($data['needs_official_correspondence'] ?? false),
            'official_correspondence_reason' => $data['official_correspondence_reason'] ?? null,
            'official_correspondence_target' => $data['official_correspondence_target'] ?? null,
            'official_correspondence_brief' => $data['official_correspondence_brief'] ?? null,
            'letter_purpose' => null,
            'rescheduled_date' => $data['rescheduled_date'] ?? null,
            'reschedule_reason' => $data['reschedule_reason'] ?? null,
            'cancellation_reason' => $data['cancellation_reason'] ?? null,
            'relations_approval_on_reschedule' => (bool) ($data['relations_approval_on_reschedule'] ?? false),
            'audience_satisfaction_percent' => $data['audience_satisfaction_percent'] ?? null,
            'evaluation_score' => $data['evaluation_score'] ?? null,
            'needs_media_coverage' => (bool) ($data['needs_media_coverage'] ?? false),
            'media_coverage_notes' => $data['media_coverage_notes'] ?? null,
            'requires_programs' => (bool) ($data['requires_programs'] ?? false),
            'is_program_related' => (bool) ($data['is_program_related'] ?? false),
            'requires_workshops' => (bool) ($data['requires_workshops'] ?? false),
            'requires_communications' => (bool) (($data['requires_communications'] ?? false) || ($data['needs_media_coverage'] ?? false)),
            'execution_needs_payload' => $data['execution_needs_payload'] ?? null,
            'execution_needs_followup' => $data['execution_needs_followup'] ?? null,
            'branch_id' => $data['branch_id'],
            'lifecycle_status' => $newLifecycleStatus,
            'relations_officer_approval_status' => $monthlyActivity->relations_officer_approval_status,
            'relations_manager_approval_status' => $monthlyActivity->relations_manager_approval_status,
            'programs_officer_approval_status' => $monthlyActivity->programs_officer_approval_status,
            'programs_manager_approval_status' => $monthlyActivity->programs_manager_approval_status,
            'liaison_approval_status' => $monthlyActivity->liaison_approval_status,
            'hq_relations_manager_approval_status' => $monthlyActivity->hq_relations_manager_approval_status,
            'executive_approval_status' => $monthlyActivity->executive_approval_status,
        ];

        $changedFields = $this->meaningfulChangedFields($oldValues, $newValues);
        $startsNewVersion = $this->shouldStartNewVersion($monthlyActivity, $changedFields, $isRescheduled);

        if ($startsNewVersion) {
            $nextStage++;
            $nextVersion++;
            $newStatus = 'draft';
            $newLifecycleStatus = 'Draft';

            $newValues['status'] = $newStatus;
            $newValues['plan_stage'] = $nextStage;
            $newValues['plan_version'] = $nextVersion;
            $newValues['previous_version_id'] = $monthlyActivity->id;
            $newValues['lifecycle_status'] = $newLifecycleStatus;
            $newValues['relations_officer_approval_status'] = 'pending';
            $newValues['relations_manager_approval_status'] = 'pending';
            $newValues['programs_officer_approval_status'] = 'pending';
            $newValues['programs_manager_approval_status'] = 'pending';
            $newValues['liaison_approval_status'] = 'pending';
            $newValues['hq_relations_manager_approval_status'] = 'pending';
            $newValues['executive_approval_status'] = 'pending';
        }

        $activityToSave = $monthlyActivity;

        DB::transaction(function () use ($startsNewVersion, $newValues, $data, $request, $monthlyActivity, &$activityToSave) {
            $lockedCurrent = MonthlyActivity::query()->whereKey($monthlyActivity->id)->lockForUpdate()->firstOrFail();
            $activityToSave = $lockedCurrent;

            if ($startsNewVersion) {
                $activityToSave = MonthlyActivity::create([
                'month' => $newValues['month'],
                'day' => $newValues['day'],
                'activity_date' => $newValues['activity_date'],
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
                'outside_contact_number' => $newValues['outside_contact_number'],
                'external_liaison_name' => $newValues['external_liaison_name'],
                'external_liaison_phone' => $newValues['external_liaison_phone'],
                'outside_address' => $newValues['outside_address'],
                'status' => $newValues['status'],
                'execution_status' => $newValues['execution_status'],
                'plan_stage' => $newValues['plan_stage'],
                'plan_version' => $newValues['plan_version'],
                'previous_version_id' => $newValues['previous_version_id'],
                'responsible_party' => $newValues['responsible_party'],
                'execution_time' => $newValues['execution_time'],
                'time_from' => $newValues['time_from'],
                'time_to' => $newValues['time_to'],
                'target_group' => $newValues['target_group'],
                'target_group_id' => $newValues['target_group_id'],
                'target_group_other' => $newValues['target_group_other'],
                'short_description' => $newValues['short_description'],
                'work_teams_count' => $newValues['work_teams_count'],
                'volunteer_need' => $newValues['volunteer_need'],
                'needs_volunteers' => $newValues['needs_volunteers'],
                'required_volunteers' => $newValues['required_volunteers'],
                'volunteer_age_range' => $newValues['volunteer_age_range'],
                'volunteer_gender' => $newValues['volunteer_gender'],
                'volunteer_tasks_summary' => $newValues['volunteer_tasks_summary'],
                'expected_attendance' => $newValues['expected_attendance'],
                'actual_attendance' => $newValues['actual_attendance'],
                'attendance_notes' => $newValues['attendance_notes'],
                'has_sponsor' => $newValues['has_sponsor'],
                'sponsor_name_title' => $newValues['sponsor_name_title'],
                'has_partners' => $newValues['has_partners'],
                'needs_official_letters' => false,
                'needs_official_correspondence' => $newValues['needs_official_correspondence'],
                'official_correspondence_reason' => $newValues['official_correspondence_reason'],
                'official_correspondence_target' => $newValues['official_correspondence_target'],
                'official_correspondence_brief' => $newValues['official_correspondence_brief'],
                'letter_purpose' => null,
                'rescheduled_date' => $newValues['rescheduled_date'],
                'reschedule_reason' => $newValues['reschedule_reason'],
                'cancellation_reason' => $newValues['cancellation_reason'],
                'relations_approval_on_reschedule' => $newValues['relations_approval_on_reschedule'],
                'audience_satisfaction_percent' => $newValues['audience_satisfaction_percent'],
                'evaluation_score' => $newValues['evaluation_score'],
                'needs_media_coverage' => $newValues['needs_media_coverage'],
                'media_coverage_notes' => $newValues['media_coverage_notes'],
                'requires_programs' => $newValues['requires_programs'],
                'is_program_related' => $newValues['is_program_related'],
                'requires_workshops' => $newValues['requires_workshops'],
                'requires_communications' => $newValues['requires_communications'],
                'execution_needs_payload' => $newValues['execution_needs_payload'],
                'execution_needs_followup' => $newValues['execution_needs_followup'],
                'branch_id' => $newValues['branch_id'],
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

                $lockedCurrent->update([
                    'status' => 'cancelled',
                ]);

                WorkflowInstance::query()
                    ->where('entity_type', MonthlyActivity::class)
                    ->where('entity_id', $lockedCurrent->id)
                    ->update([
                        'status' => 'rejected',
                        'current_step_id' => null,
                        'completed_at' => now(),
                    ]);
            } else {
                $lockedCurrent->update([
            'month' => $newValues['month'],
            'day' => $newValues['day'],
            'activity_date' => $newValues['activity_date'],
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
            'outside_contact_number' => $newValues['outside_contact_number'],
            'external_liaison_name' => $newValues['external_liaison_name'],
            'external_liaison_phone' => $newValues['external_liaison_phone'],
            'outside_address' => $newValues['outside_address'],
            'status' => $newValues['status'],
            'execution_status' => $newValues['execution_status'],
            'plan_stage' => $newValues['plan_stage'],
            'plan_version' => $newValues['plan_version'],
            'previous_version_id' => $newValues['previous_version_id'],
            'responsible_party' => $newValues['responsible_party'],
            'execution_time' => $newValues['execution_time'],
            'time_from' => $newValues['time_from'],
            'time_to' => $newValues['time_to'],
            'target_group' => $newValues['target_group'],
            'target_group_id' => $newValues['target_group_id'],
            'target_group_other' => $newValues['target_group_other'],
            'short_description' => $newValues['short_description'],
            'work_teams_count' => $newValues['work_teams_count'],
            'volunteer_need' => $newValues['volunteer_need'],
            'needs_volunteers' => $newValues['needs_volunteers'],
            'required_volunteers' => $newValues['required_volunteers'],
            'volunteer_age_range' => $newValues['volunteer_age_range'],
            'volunteer_gender' => $newValues['volunteer_gender'],
            'volunteer_tasks_summary' => $newValues['volunteer_tasks_summary'],
            'expected_attendance' => $newValues['expected_attendance'],
            'actual_attendance' => $newValues['actual_attendance'],
            'attendance_notes' => $newValues['attendance_notes'],
            'has_sponsor' => $newValues['has_sponsor'],
            'sponsor_name_title' => $newValues['sponsor_name_title'],
            'has_partners' => $newValues['has_partners'],
            'needs_official_letters' => false,
            'needs_official_correspondence' => $newValues['needs_official_correspondence'],
            'official_correspondence_reason' => $newValues['official_correspondence_reason'],
            'official_correspondence_target' => $newValues['official_correspondence_target'],
            'official_correspondence_brief' => $newValues['official_correspondence_brief'],
            'letter_purpose' => null,
            'rescheduled_date' => $newValues['rescheduled_date'],
            'reschedule_reason' => $newValues['reschedule_reason'],
            'cancellation_reason' => $newValues['cancellation_reason'],
            'relations_approval_on_reschedule' => $newValues['relations_approval_on_reschedule'],
            'audience_satisfaction_percent' => $newValues['audience_satisfaction_percent'],
            'evaluation_score' => $newValues['evaluation_score'],
            'needs_media_coverage' => $newValues['needs_media_coverage'],
            'media_coverage_notes' => $newValues['media_coverage_notes'],
            'requires_programs' => $newValues['requires_programs'],
            'is_program_related' => $newValues['is_program_related'],
            'requires_workshops' => $newValues['requires_workshops'],
            'requires_communications' => $newValues['requires_communications'],
            'execution_needs_payload' => $newValues['execution_needs_payload'],
            'execution_needs_followup' => $newValues['execution_needs_followup'],
            'branch_id' => $newValues['branch_id'],
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
                $activityToSave = $lockedCurrent;
            }
        });

        if ($startsNewVersion) {
            $workflowService->initializeDynamicStatuses($activityToSave);
        }
        $this->syncTargetGroups($activityToSave, $data);
        Log::info('monthly_activity.updated', [
            'monthly_activity_id' => $activityToSave->id,
            'updated_by' => $request->user()->id,
            'plan_version' => $activityToSave->plan_version,
            'new_version_created' => $startsNewVersion,
        ]);

        $this->syncSponsorsAndPartners($activityToSave, $data);
        $this->notifyExecutionNeedOwners($activityToSave);
        if (($request->user()->hasRole('followup_officer') || $request->user()->hasRole('super_admin')) && $this->canSubmitPostEvaluation($activityToSave)) {
            $this->syncEvaluationData($activityToSave, $data, $request->user()->id);
        }
        $this->logChanges($activityToSave, $oldValues, $newValues, $request->user()->id);
        $this->logWorkflowAction($startsNewVersion ? 'new_version_created' : 'updated', $activityToSave, $request, $activityToSave->status, [
            'changed_fields' => $changedFields,
            'source_activity_id' => $startsNewVersion ? $monthlyActivity->id : null,
        ]);

        if ($this->shouldSubmitFromRequest($request)) {
            $this->submitActivityForApproval($activityToSave, $request->user(), $workflowNotifications, $lifecycle, $dynamicWorkflowService, $request);
        }

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.updated', ['activity' => $monthlyActivity->title]))
            ->with('warning', $conflictWarning);
    }

    public function submit(MonthlyActivity $monthlyActivity, WorkflowNotificationService $workflowNotifications, MonthlyActivityLifecycleService $lifecycle, DynamicWorkflowService $dynamicWorkflowService)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, request()->user());
        $actor = request()->user();

        if ($this->isReadOnlyUnifiedAgendaActivity($monthlyActivity)) {
            return redirect()
                ->route('role.relations.activities.show', $monthlyActivity)
                ->with('warning', 'هذه فعالية موحدة ومعتمدة ولا تحتاج إرسالًا للاعتماد.');
        }

        if ($this->isSupersededVersion($monthlyActivity)) {
            return back()->withErrors([
                'status' => 'هذه نسخة قديمة من النشاط ولا يمكن إرسالها للاعتماد.',
            ]);
        }

        $this->submitActivityForApproval($monthlyActivity, $actor, $workflowNotifications, $lifecycle, $dynamicWorkflowService, request());

        return redirect()
            ->route('role.relations.activities.index')
            ->with('status', __('app.roles.programs.monthly_activities.submitted', ['activity' => $monthlyActivity->title]));
    }

    public function close(Request $request, MonthlyActivity $monthlyActivity, MonthlyActivityLifecycleService $lifecycle)
    {
        $this->ensureActivityVisibleToUser($monthlyActivity, $request->user());

        if ($this->isReadOnlyUnifiedAgendaActivity($monthlyActivity)) {
            return redirect()
                ->route('role.relations.activities.show', $monthlyActivity)
                ->with('warning', 'هذه فعالية موحدة ومعتمدة ومخصصة للعرض فقط.');
        }

        if ($this->isSupersededVersion($monthlyActivity)) {
            return back()->withErrors([
                'status' => 'هذه نسخة قديمة من النشاط ولا يمكن إغلاقها.',
            ]);
        }

        $data = $request->validate([
            'actual_date' => ['nullable', 'date'],
            'actual_attendance' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $monthlyActivity->update([
            'actual_date' => $data['actual_date'] ?? $monthlyActivity->actual_date,
            'actual_attendance' => $data['actual_attendance'] ?? $monthlyActivity->actual_attendance,
            'status' => $data['status'],
            'execution_status' => $data['status'] === 'executed' ? 'executed' : $monthlyActivity->execution_status,
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
        $viewScope = $request->input('scope', 'default');

        if ($viewScope === 'all_branches' && ! $this->canViewOtherBranches($request->user())) {
            abort(403);
        }

        $query = MonthlyActivity::query()
            ->with(['branch', 'agendaEvent'])
            ->whereDoesntHave('newerVersions')
            ->notArchived();

        $query->where(function ($dateQuery) use ($year, $month) {
            $dateQuery
                ->where(function ($proposedDateQuery) use ($year, $month) {
                    $proposedDateQuery
                        ->whereNotNull('proposed_date')
                        ->whereYear('proposed_date', $year)
                        ->whereMonth('proposed_date', $month);
                })
                ->orWhere(function ($fallbackMonthQuery) use ($month) {
                    $fallbackMonthQuery
                        ->whereNull('proposed_date')
                        ->where('month', $month);
                });
        });

        if ($viewScope !== 'all_branches') {
            $this->applyBranchVisibilityScope($query, $request->user());
        }
        $this->applyDraftVisibilityScope($query, $request->user());
        $this->applyVolunteerCoordinatorVisibilityScope($query, $request->user());

        if ($viewScope === 'all_branches') {
            $this->applyOtherBranchesScope($query, $request->user());

            $query
                ->where('status', 'approved')
                ->where(function ($approvalQuery) {
                    $approvalQuery
                        ->where('executive_approval_status', 'approved')
                        ->orWhereIn('lifecycle_status', ['Exec Director Approved', 'Approved', 'Published'])
                        ->orWhereHas('workflowInstance', fn ($workflowQuery) => $workflowQuery->where('status', 'approved'));
                });
        }

        $items = $query->orderBy('month')
            ->orderBy('day')
            ->orderBy('proposed_date')
            ->get()
            ->map(function (MonthlyActivity $activity) use ($year, $request) {
            $isReadOnlyUnified = $this->isReadOnlyUnifiedAgendaActivity($activity);
            $canBranchPartialEditUnified = $this->canBranchEditUnifiedNonCoreFields($activity, $request->user());
            $canCompleteAfterExecution = $this->canCompleteAfterExecution($activity, $request->user());
            $canOpenEdit = $this->canUseMonthlyActivityEditRoute($request->user());

            return [
                'id' => $activity->id,
                'title' => $activity->title,
                'date' => optional($activity->proposed_date)->format('Y-m-d')
                    ?? sprintf('%04d-%02d-%02d', $year, $activity->month, $activity->day),
                'branch' => $activity->branch?->name,
                'status' => $activity->status,
                'source_label' => $activity->is_in_agenda
                    ? __('app.roles.programs.monthly_activities.sources.from_agenda')
                    : __('app.roles.programs.monthly_activities.sources.manual'),
                'event_type' => $activity->agendaEvent?->event_type,
                'event_type_label' => $activity->agendaEvent?->event_type
                    ? __('app.roles.relations.agenda.types.' . $activity->agendaEvent->event_type)
                    : null,
                'plan_type' => $activity->plan_type,
                'plan_type_label' => $activity->plan_type
                    ? __('app.roles.relations.agenda.plans.' . $activity->plan_type)
                    : null,
                'plan_version' => (int) ($activity->plan_version ?: 1),
                'requires_workshops' => (bool) $activity->requires_workshops,
                'requires_communications' => (bool) $activity->requires_communications,
                'edit_url' => route('role.relations.activities.edit', $activity),
                'post_execution_url' => $canCompleteAfterExecution
                    ? route('role.relations.activities.edit', ['monthlyActivity' => $activity, 'mode' => 'post'])
                    : null,
                'can_complete_after_execution' => $canCompleteAfterExecution,
                'open_url' => ($isReadOnlyUnified && ! $canBranchPartialEditUnified)
                    ? route('role.relations.activities.show', $activity)
                    : ($canOpenEdit ? route('role.relations.activities.edit', $activity) : route('role.relations.activities.show', $activity)),
                'read_only_unified' => $isReadOnlyUnified && ! $canBranchPartialEditUnified,
            ];
            })->values();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'items' => $items,
        ]);
    }
}
