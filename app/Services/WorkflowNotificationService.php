<?php

namespace App\Services;

use App\Models\AgendaEvent;
use App\Models\InAppNotification;
use App\Models\MonthlyActivity;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class WorkflowNotificationService
{
    public function __construct(
        protected NotificationService $notifications,
        protected DynamicWorkflowService $workflows
    ) {
    }

    public function approvalRequested(WorkflowInstance $instance, Model $entity, string $url, ?User $actor = null): void
    {
        if ($actor) {
            $this->submittedForApproval($entity, $actor, $url);
        }

        $this->automaticApprovals($instance, $entity, $url);

        if ($this->isCompletedApproval($instance)) {
            $publisher = $actor ?? $this->creatorFor($entity) ?? User::query()->orderBy('id')->first();
            if ($publisher) {
                $this->published($entity, $publisher, $url, $instance);
            }

            return;
        }

        $recipients = $this->workflows->eligibleUsersForStep($instance);

        $this->notifications->notifyUsers(
            $recipients,
            'approval_requested',
            __('app.workflow_notifications.approval_requested.title'),
            __('app.workflow_notifications.approval_requested.message', [
                'item' => $this->entityTitle($entity),
            ]),
            $url,
            $this->withTranslationMeta($this->metaFor($instance, $entity), 'app.workflow_notifications.approval_requested.title', 'app.workflow_notifications.approval_requested.message', [
                'item' => $this->entityTitle($entity),
            ])
        );
    }

    public function created(Model $entity, User $actor, string $url): void
    {
        $this->notifications->notifyUsers(
            collect([$this->creatorFor($entity) ?: $actor])->filter()->unique('id'),
            'workflow_created',
            __('app.workflow_notifications.created_draft.title'),
            __('app.workflow_notifications.created_draft.message', [
                'type' => $this->entityLabel($entity),
                'item' => $this->entityTitle($entity),
                'actor' => $actor->name,
            ]),
            $url,
            $this->withTranslationMeta(
                ['entity_type' => get_class($entity), 'entity_id' => $entity->getKey(), 'actor_id' => $actor->id],
                'app.workflow_notifications.created_draft.title',
                'app.workflow_notifications.created_draft.message',
                [
                    'type' => $this->entityLabelKey($entity),
                    'item' => $this->entityTitle($entity),
                    'actor' => $actor->name,
                ],
                ['type']
            )
        );
    }

    public function submittedForApproval(Model $entity, User $actor, string $url): void
    {
        $this->notifications->notifyUsers(
            collect([$actor]),
            'workflow_submitted',
            __('app.workflow_notifications.submitted.title'),
            __('app.workflow_notifications.submitted.message', [
                'item' => $this->entityTitle($entity),
            ]),
            $url,
            $this->withTranslationMeta(
                ['entity_type' => get_class($entity), 'entity_id' => $entity->getKey(), 'actor_id' => $actor->id],
                'app.workflow_notifications.submitted.title',
                'app.workflow_notifications.submitted.message',
                ['item' => $this->entityTitle($entity)]
            )
        );
    }

    public function approvalDecision(WorkflowInstance $instance, Model $entity, User $actor, string $decision, string $url): void
    {
        $this->notifyPreviousActorsAndCreator($instance, $entity, $actor, $decision, $url);
        $this->automaticApprovals($instance, $entity, $url);

        if ($this->isCompletedApproval($instance)) {
            $this->published($entity, $actor, $url, $instance);

            return;
        }

        if ($decision === DynamicWorkflowService::DECISION_APPROVED) {
            $this->approvalRequested($instance, $entity, $url);
        }
    }

    public function deleted(Model $entity, User $actor, Collection $extraRecipients, ?string $url = null): void
    {
        $this->notifications->notifyUsers(
            $this->concernedUsers($entity, $extraRecipients)->reject(fn (User $user) => (int) $user->id === (int) $actor->id),
            'workflow_deleted',
            __('app.workflow_notifications.deleted.title'),
            __('app.workflow_notifications.deleted.message', [
                'item' => $this->entityTitle($entity),
                'actor' => $actor->name,
            ]),
            $url,
            $this->withTranslationMeta(
                ['entity_type' => get_class($entity), 'entity_id' => $entity->getKey()],
                'app.workflow_notifications.deleted.title',
                'app.workflow_notifications.deleted.message',
                ['item' => $this->entityTitle($entity), 'actor' => $actor->name]
            )
        );
    }

    public function published(Model $entity, User $actor, string $url, ?WorkflowInstance $instance = null): void
    {
        $this->notifications->notifyUsers(
            $this->activeUsers(),
            'workflow_published',
            __('app.workflow_notifications.published.title'),
            __('app.workflow_notifications.published.message', [
                'item' => $this->entityTitle($entity),
                'actor' => $actor->name,
            ]),
            $url,
            $this->withTranslationMeta(
                $instance ? $this->metaFor($instance, $entity) : ['entity_type' => get_class($entity), 'entity_id' => $entity->getKey()],
                'app.workflow_notifications.published.title',
                'app.workflow_notifications.published.message',
                ['item' => $this->entityTitle($entity), 'actor' => $actor->name]
            )
        );
    }

    protected function notifyPreviousActorsAndCreator(WorkflowInstance $instance, Model $entity, User $actor, string $decision, string $url): void
    {
        $recipients = $this->concernedUsers($entity, $this->previousActors($instance))
            ->reject(fn (User $user) => (int) $user->id === (int) $actor->id);

        $this->notifications->notifyUsers(
            $recipients,
            'approval_decision',
            $this->decisionTitle($decision),
            __('app.workflow_notifications.decision.message', [
                'item' => $this->entityTitle($entity),
                'decision' => $this->decisionText($decision),
                'actor' => $actor->name,
            ]),
            $url,
            $this->withTranslationMeta(
                $this->metaFor($instance, $entity) + ['decision' => $decision, 'actor_id' => $actor->id],
                $this->decisionTitleKey($decision),
                'app.workflow_notifications.decision.message',
                [
                    'item' => $this->entityTitle($entity),
                    'decision' => $this->decisionTextKey($decision),
                    'actor' => $actor->name,
                ],
                ['decision']
            )
        );
    }

    public function automaticApprovals(WorkflowInstance $instance, Model $entity, string $url): void
    {
        $instance->loadMissing('logs.actor');

        $this->autoApprovalLogs($instance)->each(function (WorkflowLog $log) use ($instance, $entity, $url): void {
            if (! $log->actor || $this->autoApprovalAlreadyNotified($log)) {
                return;
            }

            $recipients = $this->concernedUsers($entity, $this->previousActorsUpTo($instance, $log))
                ->push($log->actor)
                ->filter()
                ->unique('id')
                ->values();

            $this->notifications->notifyUsers(
                $recipients,
                'workflow_auto_approved',
                __('app.workflow_notifications.auto_approved.title'),
                __('app.workflow_notifications.auto_approved.message', [
                    'item' => $this->entityTitle($entity),
                    'actor' => $log->actor->name,
                ]),
                $url,
                $this->withTranslationMeta(
                    $this->metaFor($instance, $entity) + ['workflow_log_id' => $log->id, 'actor_id' => $log->actor->id],
                    'app.workflow_notifications.auto_approved.title',
                    'app.workflow_notifications.auto_approved.message',
                    ['item' => $this->entityTitle($entity), 'actor' => $log->actor->name]
                )
            );
        });
    }

    protected function autoApprovalLogs(WorkflowInstance $instance): Collection
    {
        $autoComments = collect([
            __('app.workflow_auto_approval.log_comment'),
            trans('app.workflow_auto_approval.log_comment', [], 'ar'),
            trans('app.workflow_auto_approval.log_comment', [], 'en'),
        ])->filter()->unique()->values()->all();

        return $instance->logs()
            ->with('actor')
            ->where('action', DynamicWorkflowService::DECISION_APPROVED)
            ->whereIn('comment', $autoComments)
            ->get();
    }

    protected function autoApprovalAlreadyNotified(WorkflowLog $log): bool
    {
        return InAppNotification::query()
            ->where('type', 'workflow_auto_approved')
            ->where('meta', 'like', '%"workflow_log_id":'.$log->id.'%')
            ->exists();
    }

    protected function concernedUsers(Model $entity, Collection $extraRecipients): Collection
    {
        return collect([$this->creatorFor($entity)])
            ->filter()
            ->merge($extraRecipients)
            ->unique('id')
            ->values();
    }

    protected function previousActors(WorkflowInstance $instance): Collection
    {
        return $instance->logs()
            ->with('actor')
            ->get()
            ->map(fn (WorkflowLog $log) => $log->actor)
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function previousActorsUpTo(WorkflowInstance $instance, WorkflowLog $targetLog): Collection
    {
        return $instance->logs()
            ->with('actor')
            ->where('id', '<=', $targetLog->id)
            ->get()
            ->map(fn (WorkflowLog $log) => $log->actor)
            ->filter()
            ->unique('id')
            ->values();
    }

    protected function activeUsers(): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->get();
    }

    protected function creatorFor(Model $entity): ?User
    {
        if ($entity instanceof MonthlyActivity || $entity instanceof AgendaEvent) {
            return $entity->creator;
        }

        return null;
    }

    protected function entityTitle(Model $entity): string
    {
        if ($entity instanceof AgendaEvent) {
            return (string) $entity->event_name;
        }

        if ($entity instanceof MonthlyActivity) {
            return (string) $entity->title;
        }

        return class_basename($entity).' #'.$entity->getKey();
    }

    protected function entityLabel(Model $entity): string
    {
        return __($this->entityLabelKey($entity));
    }

    protected function entityLabelKey(Model $entity): string
    {
        if ($entity instanceof AgendaEvent) {
            return 'app.workflow_notifications.entity.agenda';
        }

        if ($entity instanceof MonthlyActivity) {
            return 'app.workflow_notifications.entity.monthly_activity';
        }

        return 'app.workflow_notifications.entity.item';
    }

    protected function isCompletedApproval(WorkflowInstance $instance): bool
    {
        return (string) $instance->status === DynamicWorkflowService::DECISION_APPROVED;
    }

    protected function decisionTitle(string $decision): string
    {
        return __($this->decisionTitleKey($decision));
    }

    protected function decisionTitleKey(string $decision): string
    {
        return match ($decision) {
            DynamicWorkflowService::DECISION_APPROVED => 'app.workflow_notifications.decision.approved_title',
            DynamicWorkflowService::DECISION_CHANGES_REQUESTED => 'app.workflow_notifications.decision.changes_requested_title',
            DynamicWorkflowService::DECISION_REJECTED => 'app.workflow_notifications.decision.rejected_title',
            default => 'app.workflow_notifications.decision.update_title',
        };
    }

    protected function decisionText(string $decision): string
    {
        return __($this->decisionTextKey($decision));
    }

    protected function decisionTextKey(string $decision): string
    {
        return match ($decision) {
            DynamicWorkflowService::DECISION_APPROVED => 'app.workflow_notifications.decision.approved_text',
            DynamicWorkflowService::DECISION_CHANGES_REQUESTED => 'app.workflow_notifications.decision.changes_requested_text',
            DynamicWorkflowService::DECISION_REJECTED => 'app.workflow_notifications.decision.rejected_text',
            default => 'app.workflow_notifications.decision.updated_text',
        };
    }

    protected function withTranslationMeta(array $meta, string $titleKey, string $messageKey, array $replace = [], array $translatedReplaceKeys = []): array
    {
        return $meta + [
            'i18n' => [
                'title_key' => $titleKey,
                'message_key' => $messageKey,
                'replace' => $replace,
                'translated_replace_keys' => $translatedReplaceKeys,
            ],
        ];
    }

    protected function metaFor(WorkflowInstance $instance, Model $entity): array
    {
        return [
            'workflow_instance_id' => $instance->id,
            'workflow_id' => $instance->workflow_id,
            'current_step_id' => $instance->current_step_id,
            'entity_type' => get_class($entity),
            'entity_id' => $entity->getKey(),
        ];
    }
}
