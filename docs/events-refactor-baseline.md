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
| `role.relations.activities.trash` | GET | `.../monthly-activities/trash` | `MonthlyActivitiesController@trash` | Legacy | none direct | branch-scoped render/record visibility characterized |
| `role.relations.activities.returned_feedback` | GET | `.../monthly-activities/returned-feedback` | `MonthlyActivitiesController@returnedFeedback` | Legacy | rejection redirect/filter interactions | branch-scoped render/record visibility characterized |
| `role.relations.activities.post_execution_feedback` | GET | `.../monthly-activities/post-execution-feedback` | `MonthlyActivitiesController@postExecutionFeedback` | Legacy | post-execution notifications link here | branch-scoped render/record visibility characterized |
| `role.relations.activities.trash.restore` | PATCH | `.../trash/{monthlyActivity}/restore` | `MonthlyActivitiesController@restore` | Legacy | none | authorized restore, redirect, flash, persisted state |
| `role.relations.activities.sync_from_agenda` | POST | `.../sync-from-agenda` | `MonthlyActivitiesController@syncFromAgenda` | Legacy | mandatory/active/participant filtering | HTTP contract protected |
| `role.relations.activities.create` | GET | `.../monthly-activities/create` | `MonthlyActivitiesController@create` | Legacy | role UI access | HTTP contract protected |
| `role.relations.activities.store` | POST | `.../monthly-activities` | `MonthlyActivitiesController@store` | Legacy | successful planning persistence and validation failures | HTTP contract protected |
| `role.relations.activities.deleted.show` | GET | `.../deleted/{monthlyActivity}` | `MonthlyActivitiesController@showDeleted` | Legacy | none direct | current unconditional 404 response characterized |
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

The separately extracted supply, team, and attachment routes retain their current names under `role.programs.*`; they were not moved or changed in Phase 0. The admin change-request report remains `GET dashboard/admin/monthly-activities/change-requests/reports` with route name `role.super_admin.monthly_activities.change_requests.reports`.

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
- `MonthlyActivityPhaseZeroEndpointsTest` characterizes the restored change-request report authorization/view contract, branch-scoped trash/feedback behavior, and the deleted-show route's current unconditional 404 response.

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

## Broken-route resolution

Git history proves that commit `cbfb6bf` introduced the route, the dedicated Blade report, and `MonthlyActivitiesController::changeRequestReports()` together. The route and Blade files remained, while the controller method was later lost. The report is therefore active intended behavior (Case A), not an obsolete contract. Phase 0 restores that historical action with the same filters, statistics, relationships, view name, route name, URI, HTTP method, and `super_admin|admin` authorization. A regression test now checks authorized rendering and unauthorized rejection.

## Runtime and test execution

- Host default: PHP 8.5.7-dev; Composer 2.9.7.
- The locked dependency set cannot install on PHP 8.5 because `nette/schema v1.2.5` supports PHP through 8.3 and `nette/utils v3.2.10` requires PHP below 8.4. No platform requirements were ignored and the lock file was not changed.
- A repository-compatible installed runtime, PHP 8.3.31-dev, was selected from `/root/.phpenv/versions/8.3snapshot/bin`. Composer validated the lock successfully and began 107 locked installs.
- Installation could not complete because the environment proxy returned HTTP 403 for GitHub dist archives and source fallbacks. There is no Dockerfile/Compose/devcontainer or pre-existing Composer cache/vendor tree in the environment. `vendor/autoload.php` therefore remains unavailable.
- Consequently, Artisan/PHPUnit tests could not execute. PHP syntax checks and `git diff --check` completed successfully. This is an environment/configuration blocker (category C), not a demonstrated application-test failure.

Commands attempted:

```bash
php -v
composer --version
php -m
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
PATH=/root/.phpenv/versions/8.3snapshot/bin:$PATH php -v
PATH=/root/.phpenv/versions/8.3snapshot/bin:$PATH COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist
php artisan test --filter=EventsPhaseZeroRouteContractTest
php artisan test --filter=MonthlyActivityMutationSafetyNetTest
php artisan test --filter=MonthlyActivityPhaseZeroEndpointsTest
php artisan test --testsuite=Feature
```

## Remaining Phase-0 gaps

- The new and existing Laravel tests still require actual execution after dependencies become available. No test is described as passed merely from static inspection.
- The former behavioral gaps for trash, deleted-show, returned-feedback, and post-execution-feedback now have focused characterization tests, but their execution remains blocked by the missing dependency tree.
- Route contracts protect middleware selectively. Long role strings remain recorded in `routes/web.php` but are intentionally not frozen wholesale because representative authorization behavior is the compatibility requirement.
- No pre-existing Feature-test failure was identified because PHPUnit could not start; the only verified failure is dependency installation/network configuration.

## Architecture-document verification

`docs/ramadan-iftars-data-design-ar.md` was read completely and has no working-tree modification in this task. Its presence as a file added earlier in the branch is intentional; Phase 0 did not rewrite the architectural source of truth.

## Phase status

The missing report action and known characterization gaps are addressed in code, but Phase 0 cannot be declared complete until the Phase-0 and relevant Feature tests actually execute in a dependency-complete environment. Phase 1 must not begin while that execution blocker remains.
