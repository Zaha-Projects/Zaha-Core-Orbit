# Monthly Activities controller refactor — Phase 2.1 final architecture

**Status: CURRENT_SUPPORTING**

Current source of truth: `docs/events-architecture-current-state.md`.

## Status

**Phase 2.1 complete.** Phase 2.1A established the route-owner map. Phase 2.1B
physically moved every routed action into that focused controller, moved exclusive
helpers with its action, retained only genuinely shared protected behavior in two
Monthly-specific concern traits, removed the compatibility inheritance from both
legacy controllers, removed the Follow-up controller delegation, and deleted both
legacy controller files.

## Physical public-action ownership

| Focused controller | Physical public methods |
|---|---|
| `MonthlyActivitiesBrowseController` | `index` |
| `MonthlyActivityCalendarController` | `calendar` |
| `MonthlyActivityPlanningController` | `create`, `syncFromAgenda`, `store`, `edit`, `update` |
| `MonthlyActivityWorkspaceController` | `showDeleted`, `show` |
| `MonthlyActivityLifecycleController` | `submit`, `close` |
| `MonthlyActivityTrashController` | `trash`, `restore`, `destroy` |
| `MonthlyActivityFeedbackController` | `returnedFeedback`, `postExecutionFeedback` |
| `MonthlyActivityReportsController` | `changeRequestReports` |
| `MonthlyActivityApprovalQueueController` | `index`, `details` |
| `MonthlyActivityApprovalDecisionController` | `update`, `decideExecutionNeed` |
| `MonthlyActivityPostExecutionDecisionController` | `decidePostExecution` |
| `MonthlyActivityChangeRequestDecisionController` | `decideDeleteRequest`, `decideEditRequest` |

All classes live under
`App\Modules\Events\Http\Controllers\MonthlyActivities`. They contain their routed
methods directly and none extends either deleted legacy controller.

## Route contract

The existing 24 Monthly route entries retain their route names, HTTP verbs, URIs,
parameters, ordering and middleware declarations. Existing views/forms continue to use
unchanged route names. `MonthlyActivityControllerRouteContractTest` locks every target,
verb and URI and samples the critical role/permission and branch middleware strings.

The Follow-up route `followup.monthly-plans` now points directly to
`MonthlyActivitiesBrowseController::index`. The obsolete
`FollowupWorkspaceController::monthlyPlans` forwarding method and legacy-controller
import were deleted. Its URI, name and permission middleware are unchanged.

## Shared collaborators

### `InteractsWithMonthlyActivities`

A Monthly-specific concern trait containing only protected behavior
used by two or more of the browse, calendar, planning, workspace, lifecycle and trash
controllers. It owns branch/record visibility, shared lifecycle eligibility, legacy
Execution Need normalization, shared workflow submission/audit, shared index date
filters and shared workspace presentation relations. It has no public route action.

### `InteractsWithMonthlyActivityApprovals`

A Monthly-specific approval concern trait containing only protected behavior
used by two or more approval controllers: view-only protection, branch approval scope,
post-execution reviewer eligibility, Execution Need decision presentation, focus-area
formatting and final-step detection. It has no public route action.

No focused controller calls another controller. Existing domain services remain the
same: `MonthlyActivityLifecycleService`, `MonthlyActivityWorkflowService`,
`DynamicWorkflowService`, `WorkflowNotificationService`, `NotificationService`,
`PlanChangeRequestWorkflowService`, `ConflictDetectionService` and
`MonthlyWorkflowPresenter`.

## Legacy retirement proof

Deleted:

- `app/Http/Controllers/Web/MonthlyActivities/MonthlyActivitiesController.php`
- `app/Http/Controllers/Web/MonthlyActivities/MonthlyActivitiesApprovalsController.php`

Repository-wide production/test searches contain no reference to either deleted FQCN,
no focused class extends either legacy class, and no controller invokes a Monthly
controller. Remaining text references occur only in historical/planning documentation
that describes the pre-refactor architecture, plus the unrelated
`StaffMonthlyActivitiesController` class whose name is not a legacy dependency.

## Behavior and storage invariants

- Endpoint bodies were moved without intentional logic edits.
- Inline validation, authorization, branch filtering, query/eager-loading behavior,
  pagination, views, redirects, response JSON and flash messages are unchanged.
- Existing transaction boundaries, workflow calls, notifications and audit calls moved
  with their endpoint/helper bodies.
- `execution_needs_payload`, `execution_needs_followup` and post-execution payloads
  remain legacy-authoritative.
- No model or model namespace moved.
- No database migration, schema alteration, backfill, seeder or permission changed.
- No Common Event read/write or dual-write was introduced.
- No Agenda, Ramadan or Monthly Blade file changed.

## Helper ownership

Exclusive helpers were moved directly beside their only routed concern. Helpers reached
by two or more focused concerns remain once in the appropriate Monthly-specific concern
trait. One repository-wide dead helper, `resolveStatusFilterValue`, was removed after
static call/reference analysis found no consumer.

| Helper | Old owner | New owner | Reason |
|---|---|---|---|
| `monthlyPageStatusOptions` | `MonthlyActivitiesController` | `MonthlyActivitiesBrowseController` | Used only by this focused responsibility. |
| `applyMonthlyIndexSummaryFilter` | `MonthlyActivitiesController` | `MonthlyActivitiesBrowseController` | Used only by this focused responsibility. |
| `buildMonthlyIndexSummaryCards` | `MonthlyActivitiesController` | `MonthlyActivitiesBrowseController` | Used only by this focused responsibility. |
| `resolvePendingApprovalCardSnapshot` | `MonthlyActivitiesController` | `MonthlyActivitiesBrowseController` | Used only by this focused responsibility. |
| `fallbackWorkflowFilterLabel` | `MonthlyActivitiesController` | `MonthlyActivitiesBrowseController` | Used only by this focused responsibility. |
| `approvalsReturnUrl` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalDecisionController` | Used only by this focused responsibility. |
| `publishApprovedLifecycle` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalDecisionController` | Used only by this focused responsibility. |
| `monthlyLegacyApprovalStatusUpdates` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalDecisionController` | Used only by this focused responsibility. |
| `storeDepartmentNoteIfPresent` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalDecisionController` | Used only by this focused responsibility. |
| `executionStatusLabel` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `monthlyActivityStatusLabel` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `monthlyChangeRequestStats` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `applyChangeRequestFilters` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `workflowTimelineForActivity` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `applyMonthlyActivityApprovalFilters` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `buildActivityCard` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `decisionOptionsForStep` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `buildCurrentStepOptions` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `buildStatusFilterOptions` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `applyWorkflowApprovalFilters` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityApprovalQueueController` | Used only by this focused responsibility. |
| `closeLifecycle` | `MonthlyActivitiesController` | `MonthlyActivityLifecycleController` | Used only by this focused responsibility. |
| `branchScopedRoleUsers` | `MonthlyActivitiesController` | `MonthlyActivityLifecycleController` | Used only by this focused responsibility. |
| `normalizePostExecutionPayload` | `MonthlyActivitiesController` | `MonthlyActivityLifecycleController` | Used only by this focused responsibility. |
| `logChanges` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizeChangeLogValue` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `shouldStartNewVersion` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `activityHasApprovalTrail` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `meaningfulChangedFields` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizeComparableValue` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `statusAfterPlanningEdit` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `shouldSubmitFromRequest` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `unifiedLockedFields` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `applyUnifiedLockedFieldValues` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `isLocked` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `notifyExecutionNeedsDecisionSubmitted` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `mergeExecutionNeedsFollowupForDecisionUser` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `canSubmitPostEvaluation` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `monthlyCloseStatusOptions` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `monthlyPlanningStatusOptions` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `flashFormPrefill` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `extractVolunteerAgeBounds` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `canUseMonthlyActivityPlanningEdit` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `notifyExecutionNeedOwners` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `executionNeedOwnerUsers` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `isBranchScopedExecutionNeedRole` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `syncEvaluationData` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `syncEvaluationSummary` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `syncSponsorsAndPartners` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `syncTargetGroups` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `syncOfficialCorrespondence` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `syncVolunteerNeed` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizePlanningPayload` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizeSuppliesPayload` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizeExecutionNeedsPayload` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizeExpectedAttendanceRange` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizeVolunteerAgeRange` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `applyAgendaLockedFieldValues` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `needAvailabilityRules` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `supplyValidationRules` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `expectedAttendanceRangeRules` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `safeExternalUrlRules` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizeSuppliesRequestPayload` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `normalizeMonthlyActivityContactPhones` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `buildLockAt` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `monthlyLockDays` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `currentUserBranchId` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `monthlyCreationStatusOptions` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `agendaEventsForUser` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `agendaEventsQueryForUser` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `flashCreatePrefill` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `findAgendaEventForUser` | `MonthlyActivitiesController` | `MonthlyActivityPlanningController` | Used only by this focused responsibility. |
| `branchScopedRoleUsers` | `MonthlyActivitiesApprovalsController` | `MonthlyActivityPostExecutionDecisionController` | Used only by this focused responsibility. |
| `abortIfProgramsManagerViewOnly` | `MonthlyActivitiesApprovalsController` | `InteractsWithMonthlyActivityApprovals` | Shared by multiple focused approval concerns; retained once in the approval trait. |
| `canReviewPostExecution` | `MonthlyActivitiesApprovalsController` | `InteractsWithMonthlyActivityApprovals` | Shared by multiple focused approval concerns; retained once in the approval trait. |
| `executionNeedDecisionItemsForActivity` | `MonthlyActivitiesApprovalsController` | `InteractsWithMonthlyActivityApprovals` | Shared by multiple focused approval concerns; retained once in the approval trait. |
| `focusAreaLabels` | `MonthlyActivitiesApprovalsController` | `InteractsWithMonthlyActivityApprovals` | Shared by multiple focused approval concerns; retained once in the approval trait. |
| `formatDecisionComment` | `MonthlyActivitiesApprovalsController` | `InteractsWithMonthlyActivityApprovals` | Shared by multiple focused approval concerns; retained once in the approval trait. |
| `isMonthlyRelationsManagerFinalStep` | `MonthlyActivitiesApprovalsController` | `InteractsWithMonthlyActivityApprovals` | Shared by multiple focused approval concerns; retained once in the approval trait. |
| `branchApprovalScope` | `MonthlyActivitiesApprovalsController` | `InteractsWithMonthlyActivityApprovals` | Shared by multiple focused approval concerns; retained once in the approval trait. |
| `monthlyActivityEditRoles` | `MonthlyActivitiesController` constants | `InteractsWithMonthlyActivities` | Preserves the shared role list without PHP 8.2-only trait constants. |
| `ownBranchId` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `followupOfficerBranchId` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `scopedBranchIds` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canAccessScopedBranch` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `isApprovedVersion` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `isSupersededVersion` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `shouldScopeToUserBranch` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `applyBranchVisibilityScope` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `applyOtherBranchesScope` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canViewOtherBranches` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `applyDraftVisibilityScope` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `isVolunteerCoordinatorOnly` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `applyVolunteerCoordinatorVisibilityScope` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canCompleteAfterExecution` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canReviewPostExecution` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canSubmitActivityForApproval` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canUseMonthlyActivityEditRoute` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `monthlyActivityChangeRequestRoles` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canManageMonthlyActivityChangeRequest` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `creatorIsPrimaryBranchRelationsOfficer` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `executionNeedDecisionRoles` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `executionNeedDecisionKeysForUser` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canDecideAnyExecutionNeed` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `ensureActivityVisibleToUser` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `activityNeedsVolunteers` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `logWorkflowAction` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `evaluationSummaryRules` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `statusLookupOptions` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `executionStatusLabels` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `normalizeExecutionNeedsFollowup` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `filterExecutionNeedsFollowupToEnabled` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `mergeExecutionNeedsFollowupRows` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `submitActivityForApproval` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `normalizeMonthlyIndexYear` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `normalizeMonthlyIndexMonth` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `applyMonthlyPageMonthFilter` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `applyMonthlyPageStatusFilter` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `hasManagerOrLaterApproval` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `isReadOnlyUnifiedAgendaActivity` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `canBranchEditUnifiedNonCoreFields` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `monthlyActivityWorkflowViewRelations` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |
| `activeMonthlyChangeRequestViewData` | `MonthlyActivitiesController` | `InteractsWithMonthlyActivities` | Shared by multiple focused Monthly concerns; retained once in the Monthly trait. |

## Next phase — not implemented

`PHASE 2.2 — EVENTS DATA MODEL CONSOLIDATION AUDIT`

That audit will classify each model/table decision as `KEEP`, `ALTER`, `RENAME`,
`MOVE MODEL`, `MERGE`, `KEEP NEW`, or `DEPRECATE`. It will cover future candidates only:
Monthly Targeting, Execution Teams, Volunteers, Supplies, canonical Execution Needs,
Monitoring, legacy storage decommission and seeder/bootstrap readiness.
