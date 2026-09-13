# Monthly Activities controller refactor — Phase 2.1

## Status

**Legacy controller retirement: PARTIAL.** Public route ownership is now separated under
`App\Modules\Events\Http\Controllers\MonthlyActivities`, but the focused controllers
still inherit the proven implementations from the two legacy controllers. This is an
intentional compatibility seam while runtime dependencies are unavailable. It must not
be mistaken for completed method/helper extraction.

The repository state at the start of this slice did not contain the documented
`MonthlyActivitiesBrowseController` or `MonthlyActivityCalendarController`; both routes
still targeted `MonthlyActivitiesController`. Phase 2.1 establishes those named route
owners without changing their inherited implementations.

## Complete legacy public-action inventory

### `MonthlyActivitiesController`

| Action | Route name / verb | Responsibility | View or response | Principal collaborators/helper families |
|---|---|---|---|---|
| `index` | `role.relations.activities.index` GET | Browse | `pages.monthly_activities.index` | visibility, month/status summaries, workflow presenter |
| `calendar` | `role.relations.activities.calendar` GET | Browse/calendar | calendar view | visibility, month/status filters |
| `trash` | `role.relations.activities.trash` GET | Trash | trash view | branch visibility, month filters |
| `restore` | `role.relations.activities.trash.restore` PATCH | Trash | redirect | visibility and legacy lifecycle fields |
| `create` | `role.relations.activities.create` GET | Planning | create form | lookups, branch/agenda visibility, prefill |
| `syncFromAgenda` | `role.relations.activities.sync_from_agenda` POST | Planning bootstrap | redirect | validation, branch scope, legacy Monthly persistence |
| `store` | `role.relations.activities.store` POST | Planning | redirect | inline validation, normalization, transaction, workflow, notification, audit |
| `edit` | `role.relations.activities.edit` GET | Planning | edit/post-execution form | visibility, change-request state, presenters/lookups |
| `update` | `role.relations.activities.update` PUT | Planning/execution form mutation | redirect | inline validation, normalization, transaction, version/change requests, workflow, notification, audit |
| `show` | `role.relations.activities.show` GET | Workspace | show view | visibility, workflow presenter, attachments/change requests |
| `showDeleted` | `role.relations.activities.deleted.show` GET | Historical guard | 404 | none (deleted records intentionally unavailable) |
| `destroy` | `role.relations.activities.destroy` DELETE | Trash/change request | redirect | visibility, change-request service, audit |
| `submit` | `role.relations.activities.submit` PATCH | Submission | redirect | lifecycle, workflow, notifications, audit |
| `close` | `role.relations.activities.close` PATCH | Execution/post-execution | redirect | lifecycle, post-execution authorization and normalization |
| `returnedFeedback` | `role.relations.activities.returned_feedback` GET | Feedback | feedback view | role/branch SQL scope |
| `postExecutionFeedback` | `role.relations.activities.post_execution_feedback` GET | Post-execution feedback | feedback view | role/branch SQL scope, legacy JSON |
| `changeRequestReports` | `role.super_admin.monthly_activities.change_requests.reports` GET | Reports | report view | request filters and workflow aggregates |

### `MonthlyActivitiesApprovalsController`

| Action | Route name / verb | Responsibility | Response | Principal collaborators/helper families |
|---|---|---|---|---|
| `index` | `role.programs.approvals.index` GET | Approval/change-request queue | approvals view | SQL branch scope, workflow presenter/service |
| `details` | `role.programs.approvals.details` GET | Approval review | JSON | workflow/branch filters and presentation helpers |
| `update` | `role.programs.approvals.update` PUT | Planning approval decision | redirect | lifecycle, dynamic workflow, notifications, audit, department notes |
| `decideExecutionNeed` | `role.programs.approvals.execution_needs.update` PUT | Execution-need decision | redirect | legacy execution-needs JSON and authorization helpers |
| `decidePostExecution` | `role.programs.approvals.post_execution_decision` PATCH | Post-execution review | redirect | notifications and legacy post-execution payload |
| `decideDeleteRequest` | `role.programs.approvals.delete_requests.update` PUT | Change-request decision | redirect | `PlanChangeRequestWorkflowService` |
| `decideEditRequest` | `role.programs.approvals.edit_requests.update` PUT | Change-request decision | redirect | `PlanChangeRequestWorkflowService` |

### Other legacy Monthly controller layer inspected

- `CommunicationsRequestsController`: `index`, `board`, `update`, and public
  `requirementsFor`. It owns a separate communications workflow and was not moved.
- `WorkshopsRequestsController`: `index`, `update`. It owns workshop requests and was
  not moved.
- `EventLookupsController`: `index` and lookup create/update actions for Zaha time,
  departments/units, target groups, evaluation questions, categories and statuses. It
  is an admin lookup controller, not part of Monthly plan lifecycle decomposition.
- Existing focused Programs controllers for supplies, teams, attachments and other
  resources remain unchanged.

All route middleware, views, request payloads, redirects and collaborators remain in the
inherited implementations; only route controller ownership changed.

## Old to new action map

```text
MonthlyActivitiesController::index
→ MonthlyActivitiesBrowseController::index
MonthlyActivitiesController::calendar
→ MonthlyActivityCalendarController::calendar
MonthlyActivitiesController::create/store/edit/update/syncFromAgenda
→ MonthlyActivityPlanningController (same actions)
MonthlyActivitiesController::show/showDeleted
→ MonthlyActivityWorkspaceController (same actions)
MonthlyActivitiesController::submit/close
→ MonthlyActivityLifecycleController (same actions)
MonthlyActivitiesController::trash/restore/destroy
→ MonthlyActivityTrashController (same actions)
MonthlyActivitiesController::returnedFeedback/postExecutionFeedback
→ MonthlyActivityFeedbackController (same actions)
MonthlyActivitiesController::changeRequestReports
→ MonthlyActivityReportsController::changeRequestReports

MonthlyActivitiesApprovalsController::index/details
→ MonthlyActivityApprovalQueueController (same actions)
MonthlyActivitiesApprovalsController::update/decideExecutionNeed
→ MonthlyActivityApprovalDecisionController (same actions)
MonthlyActivitiesApprovalsController::decidePostExecution
→ MonthlyActivityPostExecutionDecisionController::decidePostExecution
MonthlyActivitiesApprovalsController::decideDeleteRequest/decideEditRequest
→ MonthlyActivityChangeRequestDecisionController (same actions)
```

## Route-contract preservation

The 24 moved route entries retain the existing name, HTTP verb, URI, route parameter,
route order and middleware declarations. Existing Blade links therefore continue to use
unchanged route names. A route-contract characterization test locks the new controller
action plus the verb/URI for all 24 routes and samples critical role/branch middleware.
No route was removed or declared obsolete.

## Helper classification

- **A — one concern:** month/calendar filters; form-prefill/normalization; trash
  restoration; feedback query builders; decision formatting. These remain in the
  compatibility parent pending physical extraction.
- **B — shared:** branch visibility, role resolution, activity visibility, status
  lookup options, workflow presentation, active change-request data. These are retained
  once in the legacy parent and were not duplicated.
- **C — domain/service:** lifecycle transitions, dynamic workflow decisions,
  notifications and plan-change-request orchestration already delegate to existing
  services. Their calls and transactions are unchanged.
- **D — query/presentation:** summary cards, status labels, approval card/timeline and
  filter builders remain beside the inherited endpoints for now.
- **E — dead:** no helper was deleted in this slice because runtime-backed proof is not
  available and several tests intentionally reflect protected helpers.

No focused controller calls another controller. The inheritance seam is the explicit
remaining retirement blocker.

## Remaining legacy dependencies and retirement gate

No application route points directly to either legacy controller after this slice.
They cannot yet be deleted because:

1. all focused route owners inherit their proven implementations and shared helpers;
2. focused-controller characterization tests still reflect the inherited
   `statusAfterPlanningEdit` and `executionNeedOwnerUsers` helpers directly;
3. `FollowupWorkspaceController::monthlyPlans` still delegates to the legacy
   `MonthlyActivitiesController::index`; replacing this pre-existing controller call
   requires extracting the browse query/presentation behavior rather than redirecting
   the dependency to another controller;
4. the 4,253-line activity controller and 1,130-line approval controller have highly
   cross-cutting helper families that require runtime regression coverage during
   physical extraction.

Accordingly, route cutover is complete but physical legacy-controller retirement is
partial. The next controller-cleanup pass must move endpoint bodies/helper families into
focused controllers or narrowly scoped services, update the two intentional test
references, then repeat the repository-wide zero-reference gate before deletion.

## Storage and behavior invariants

- No Monthly model, table, JSON field or relationship changed.
- No Common Event read/write or dual-write was introduced.
- `execution_needs_payload`, `execution_needs_followup`, and post-execution payloads
  remain legacy-authoritative.
- Workflow definitions, roles, permissions, transactions, notifications and audit calls
  are unchanged.
- No Blade file, Ramadan production file, Agenda controller, migration or seeder changed.

## Phase 2.2 inventory candidates — not implemented

- Monthly Targeting → Common
- Monthly Execution Teams → Common
- Monthly Volunteers → Common
- Monthly Supplies → Common
- Monthly Execution Needs → canonical Common
- Monthly Monitoring → Common
- Legacy storage decommission
- Seeder/bootstrap audit
