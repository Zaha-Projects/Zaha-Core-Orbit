# Events Refactor — Phase 0 Baseline

Recorded: 2026-09-10

## Scope and current architecture

This baseline records the repository state to protect before `Events/Common` work begins. It does not introduce Common services, polymorphic tables, Ramadan Iftars, migrations, morph maps, or production refactoring.

The current application does **not** yet have `app/Modules/Events`. Monthly planning is primarily served by two legacy controllers:

- `Web\MonthlyActivities\MonthlyActivitiesController` (4,163 lines): browse, calendar, create/update, agenda synchronization, submit, close/post-execution, trash/restore, returned feedback, and post-execution feedback.
- `Web\MonthlyActivities\MonthlyActivitiesApprovalsController` (1,130 lines): approval browse/details/decision, post-execution and execution-need decisions, and edit/delete request decisions.

Agenda behavior remains primarily in:

- `Web\Agenda\AgendaEventsController` (1,354 lines): browse and CRUD, submit, branch/unit participation, quick subscription, and creation/synchronization of monthly activities.
- `Web\Agenda\AgendaApprovalsController`: agenda approval queue and decisions.

Already-extracted endpoint controllers that must be preserved are:

- `Roles\Programs\MonthlyActivitySuppliesController` for supply mutations.
- `Roles\Programs\MonthlyActivityTeamController` for team mutations.
- `Roles\Programs\MonthlyActivityAttachmentsController` for attachment upload/download/delete.
- `Web\MonthlyActivities\WorkshopsRequestsController` and `CommunicationsRequestsController` for downstream request boards.
- `Web\Evaluation\ActivityEvaluationsController` for verification/evaluation HTTP flows.

`FollowupWorkspaceController::monthlyPlans()` still delegates directly to the legacy `MonthlyActivitiesController::index()`; it is not an extracted monthly browse implementation. No completed valid extraction was reverted in Phase 0.

## Monthly Activity HTTP inventory

“Extracted” describes the current controller boundary only. Route-contract tests intentionally do not assert controller class names, allowing later extraction while protecting HTTP compatibility.

| Route name | Method | URI | Current action | State | Existing behavioral coverage before Phase 0 | Phase-0 addition / gap |
|---|---|---|---|---|---|---|
| `role.relations.activities.index` | GET | `dashboard/relations/monthly-activities` | `MonthlyActivitiesController@index` | Legacy | branch visibility, grouped status, role UI | HTTP contract protected |
| `role.relations.activities.calendar` | GET | `.../monthly-activities/calendar` | `MonthlyActivitiesController@calendar` | Legacy | status filtering, creator action, follow-up branch scope | HTTP contract protected |
| `role.relations.activities.trash` | GET | `.../monthly-activities/trash` | `MonthlyActivitiesController@trash` | Legacy | none direct | HTTP contract protected; browse behavior remains a gap |
| `role.relations.activities.returned_feedback` | GET | `.../monthly-activities/returned-feedback` | `MonthlyActivitiesController@returnedFeedback` | Legacy | rejection redirect/filter interactions | HTTP contract protected; dedicated render/scope case remains a gap |
| `role.relations.activities.post_execution_feedback` | GET | `.../monthly-activities/post-execution-feedback` | `MonthlyActivitiesController@postExecutionFeedback` | Legacy | post-execution notifications link here | HTTP contract protected; dedicated render/scope case remains a gap |
| `role.relations.activities.trash.restore` | PATCH | `.../trash/{monthlyActivity}/restore` | `MonthlyActivitiesController@restore` | Legacy | none | authorized restore, redirect, flash, persisted state |
| `role.relations.activities.sync_from_agenda` | POST | `.../sync-from-agenda` | `MonthlyActivitiesController@syncFromAgenda` | Legacy | mandatory/active/participant filtering | HTTP contract protected |
| `role.relations.activities.create` | GET | `.../monthly-activities/create` | `MonthlyActivitiesController@create` | Legacy | role UI access | HTTP contract protected |
| `role.relations.activities.store` | POST | `.../monthly-activities` | `MonthlyActivitiesController@store` | Legacy | successful planning persistence and validation failures | HTTP contract protected |
| `role.relations.activities.deleted.show` | GET | `.../deleted/{monthlyActivity}` | `MonthlyActivitiesController@showDeleted` | Legacy | none direct | HTTP contract protected; response behavior remains a gap |
| `role.relations.activities.edit` | GET | `.../{monthlyActivity}/edit` | `MonthlyActivitiesController@edit` | Legacy | workflow summary, role UI access | HTTP contract protected |
| `role.relations.activities.show` | GET | `.../{monthlyActivity}` | `MonthlyActivitiesController@show` | Legacy | workflow summary, volunteer restriction, role UI access | HTTP contract protected |
| `role.relations.activities.update` | PUT | `.../{monthlyActivity}` | `MonthlyActivitiesController@update` | Legacy | versioning and correction state | cross-branch mutation denial added |
| `role.relations.activities.destroy` | DELETE | `.../{monthlyActivity}` | `MonthlyActivitiesController@destroy` | Legacy | agenda delete only; no monthly behavior | unauthorized denial plus authorized soft delete added |
| `role.relations.activities.submit` | PATCH | `.../{monthlyActivity}/submit` | `MonthlyActivitiesController@submit` | Legacy | workflow advance, resubmit, superseded denial | HTTP contract protected |
| `role.relations.activities.close` | PATCH | `.../{monthlyActivity}/close` | `MonthlyActivitiesController@close` | Legacy | creator submission, supervisor close, persisted post data | HTTP contract protected |
| `role.programs.approvals.index` | GET | `dashboard/programs/monthly-activities/approvals` | `MonthlyActivitiesApprovalsController@index` | Legacy approval controller | branch scope, pagination/filtering, programs-manager view-only | HTTP contract protected |
| `role.programs.approvals.details` | GET | `.../approvals/{monthlyActivity}/details` | `MonthlyActivitiesApprovalsController@details` | Legacy approval controller | modal/detail rendering through approval tests | HTTP contract protected |
| `role.programs.approvals.update` | PUT | `.../approvals/{monthlyActivity}` | `MonthlyActivitiesApprovalsController@update` | Legacy approval controller | unauthorized/current-step rules and sequential workflow decisions | HTTP contract protected |
| `role.programs.approvals.post_execution_decision` | PATCH | `.../{monthlyActivity}/post-execution-decision` | `MonthlyActivitiesApprovalsController@decidePostExecution` | Legacy approval controller | post-execution flow and view-only denial | HTTP contract protected |
| `role.programs.approvals.execution_needs.update` | PUT | `.../{monthlyActivity}/execution-need` | `MonthlyActivitiesApprovalsController@decideExecutionNeed` | Legacy approval controller | role UI/decision behavior partially covered | HTTP contract protected |
| `role.programs.approvals.delete_requests.update` | PUT | `.../delete-requests/{deleteRequest}` | `MonthlyActivitiesApprovalsController@decideDeleteRequest` | Legacy approval controller | notification/link behavior | HTTP contract protected |
| `role.programs.approvals.edit_requests.update` | PUT | `.../edit-requests/{editRequest}` | `MonthlyActivitiesApprovalsController@decideEditRequest` | Legacy approval controller | notification/link behavior | HTTP contract protected |

The separately extracted supply, team, and attachment routes retain their current names under `role.programs.*`; they were not moved or changed in Phase 0.

## Agenda HTTP inventory

| Route family | Methods | Current controller | Existing coverage | Phase-0 protection |
|---|---|---|---|---|
| agenda index/create/store/show/edit/update/destroy/submit | GET/POST/PUT/DELETE/PATCH | `AgendaEventsController` | creation rules, visibility/publication, deletion boundaries, role access, workflow | name/method/URI/auth contracts |
| unit and branch participation, quick subscribe | PATCH/POST | `AgendaEventsController` | monthly-plan creation/update, redirect, visibility, inactive event restriction | name/method/URI/auth contracts |
| agenda approval queue/decision | GET/PUT | `AgendaApprovalsController` | dynamic role-driven approval and modal details | name/method/URI/auth contracts |

## Test baseline found

### Browse and branch visibility

- `MonthlyActivityBranchVisibilityTest`: own-branch index, approved other-branch scope, volunteer filtering, grouped status, calendar filtering, detail denial, and calendar action visibility.
- `MonthlyActivityApprovalsBranchScopeTest`: supervisor queue is restricted to the supervisor’s branch.
- `ActivityEvaluationWorkflowTest`: follow-up officer branch scope and calendar/list rendering.

### Create, update, submit, lifecycle, and post execution

- `ProductionReadinessMonthlyActivitiesTest`: store validation, supply persistence, update/version behavior, superseded submit restriction, and agenda creation compatibility.
- `MonthlyActivityExecutionCompletionRegressionTest`: planning persistence and creator-to-supervisor post-execution close flow.
- `PostExecutionCompletionTest`: post-execution needs, teams, ceremony data, and review submission.
- `MonthlyActivityLifecycleServiceTest`: allowed and rejected lifecycle transitions.

### Workflow and approvals

- `WorkflowGovernanceAndApprovalsTest`: unauthorized approval, required comments, corrections/resubmission, sequential flow, automatic approval, agenda workflow, prerequisite steps, final/executive publication paths, skipped coordinator behavior, and workflow presentation.
- `MonthlyActivityApprovalsPaginationTest`: approval filters/counts/pagination, rejection requirements, redirect preservation, and notification deduplication.
- `ProgramsManagerViewOnlyAccessTest`: read-only access and mutation/approval denial for the programs manager.

### Phase-0 tests added

- `EventsPhaseZeroRouteContractTest` protects Monthly Activity and Agenda route names, methods, URIs, authentication, and representative branch-isolation middleware without locking controller classes.
- `MonthlyActivityMutationSafetyNetTest` characterizes cross-branch update denial, unauthorized-role delete denial, authorized draft soft deletion, redirect/flash response, and admin restoration.

## Authorization and branch-scope baseline

The representative required cases are now covered:

- Branch A cannot list/open relevant Branch B data through existing visibility tests.
- Branch A cannot update a Branch B activity through the new mutation safety-net test.
- An unauthorized `staff` user cannot delete a monthly activity.
- An authorized relations officer can delete their draft, and an admin can restore it.
- A supervisor approval queue is scoped to their branch.
- A user not assigned to the current workflow step cannot approve; prerequisite and workflow-state restrictions remain covered.

These tests characterize the existing role middleware, `EnforceBranchIsolation`, controller visibility guards, Spatie roles/permissions, and dynamic workflow. They do not introduce a parallel authorization system.

## Compatibility contracts to preserve

1. Keep all route names, methods, and URIs asserted by `EventsPhaseZeroRouteContractTest` while controllers move.
2. Keep authentication on all protected routes and `branch.isolation` on the monthly browse/calendar/trash/feedback/create/store/sync/restore endpoints currently carrying it.
3. Preserve current redirects and session messages for monthly draft deletion and restoration.
4. Preserve current HTML views for index/show/edit/approvals and JSON shape for calendar and approval details until an explicitly versioned contract change.
5. Preserve soft deletion and the restoration normalization from `cancelled` back to `draft`.
6. Preserve the workflow entity module `monthly_activities`, route parameters (`monthlyActivity`, `deleteRequest`, `editRequest`), and current-step authorization behavior during controller extraction.
7. Preserve current branch scoping based on branch permissions/assignments and creator-only draft restrictions.

## Known blockers and remaining Phase-0 gaps

- Dependencies are absent: `vendor/autoload.php` does not exist. Therefore Artisan and PHPUnit cannot execute in this environment. Syntax/static checks can run, but no automated test is reported as passed until dependencies are restored externally without changing production dependencies.
- The named admin route `role.super_admin.monthly_activities.change_requests.reports` points to `MonthlyActivitiesController@changeRequestReports`, but no such public method was found during reconnaissance. This pre-existing route needs product confirmation or restoration before Phase 1; Phase 0 does not change production behavior to guess the intended response.
- Direct behavior tests are still absent for trash listing, deleted-show, returned-feedback rendering, and post-execution-feedback rendering. Their HTTP contracts are protected and adjacent behavior exists, but these should be filled before moving those particular methods.
- Route contracts currently protect middleware selectively. Long role strings are recorded in `routes/web.php` but intentionally not frozen wholesale because the target architecture moves authorization to Policies; representative authorization behavior is protected instead.

## Phase status

Phase 0 safety-net changes are implemented, but execution is unverified in the current checkout because Composer dependencies are missing. Phase 1 must not begin until the Feature suite executes successfully and the unresolved admin change-request report route is dispositioned.
