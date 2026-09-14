# Events branch developer handover

Date: 2026-09-14

Branch: `work`
Current tip when prepared: `615d2d0` (`refactor: move low-risk events support models`)

This is the practical continuation document. Code is authoritative; use the
linked focused documents for detailed rationale. Do not infer production
readiness from the completed architecture phases.

## 1. Executive summary and phase state

This branch changed Events from a Monthly-centric implementation with very large
controllers into three cooperating areas: preserved Monthly Activities, Agenda,
and an independent Ramadan Iftar aggregate, supported by reusable targeting,
execution, monitoring, workflow, and reference-data infrastructure.

Monthly behavior and its historical `monthly_activities` rows remain intact.
Routes now target focused controllers, but existing planning, Agenda sync,
workspace, approval, execution-needs JSON, lifecycle, feedback, post-execution,
change-request, trash/restore, and reporting behavior remains authoritative.
Ramadan gained its own guidance-gated planning, approval, execution, monitoring
review, closure, and approved-plan versioning lifecycle; it is not a Monthly row.

The first Common schema design created four parallel detail tables. Phase 2.3
reversed that duplication and generalized four established tables instead. Phase
2.8A subsequently moved only fourteen support/catalogue models whose own FQCNs
were not persisted. Identity-sensitive aggregates and request models stayed put.

| Phase | Current state | Outcome |
|---|---|---|
| 0 / 0.1 | complete | route/behavior baseline and safety characterization |
| 1.x | complete in source | Common Events foundations and full Ramadan lifecycle/versioning |
| 2.1 | complete | focused Monthly controllers; legacy giants deleted |
| 2.2 | complete | consolidation audit and decisions |
| 2.3 | complete in source | established detail tables generalized; duplicate development schema removed |
| 2.4 | complete | architecture reconciliation/current-state authority |
| 2.5 | complete in source | reference-data and authorization/workflow bootstrap reconciled |
| 2.6 | **incomplete** | runtime gate blocked/deferred |
| 2.7 | complete | stored-identity and namespace readiness audit |
| 2.8A | complete statically | fourteen LOW-risk support/catalogue models moved |

Unfinished work begins with the release-blocking runtime gate. Later work covers
route-bound support models, stored-identity compatibility, carefully separated
aggregate cutovers, and distinct high-risk Monthly data migrations.

## 2. Full change inventory

### ADDED

| Area | Artifact | Purpose | Current status / consumers |
|---|---|---|---|
| Database | `beneficiary_segments`, `mobilization_methods`, `monitoring_methods`, `community_organizations`, `local_communities` | reusable Event/Ramadan reference and business catalogues | final schema; business-managed rows are not default-seeded |
| Database | `ramadan_iftars` and attendee/meal/item/gift/program-segment tables | independent, versionable Ramadan aggregate and planning/actual detail | current Ramadan controllers/services/models |
| Database | `execution_teams`, `subject_volunteer_requirements`, `subject_execution_needs`, `monitoring_reports`, `event_guidance_versions`, `ramadan_iftar_change_requests` | Common execution, monitoring, guidance, normalized needs, and approved-plan change workflow | final for current Ramadan implementation |
| Database | `2026_09_14_000100_generalize_existing_event_detail_tables.php` | generalize four historical tables without discarding Monthly IDs | final Phase 2.3 forward migration; runtime still unverified |
| Models | flat `app/Modules/Events/Models` set | Ramadan aggregate/details plus Common aliases/catalogues/execution/monitoring | current model home; includes Phase 2.8A support models |
| Controllers | `app/Modules/Events/Http/Controllers/Ramadan/*` | guidance, planning, workspace, submission, approvals, execution, monitoring/review, closure, change requests | current route targets |
| Controllers | focused Monthly controller set | one public responsibility per controller | current route targets; see §8 |
| Services | Ramadan guidance/planning/submission/approval/execution/monitoring/closure/change services | transactional business rules separated from HTTP layer | current operational owners |
| Services | `DynamicWorkflowService`, Agenda/Monthly presenters and lifecycle bridges | reusable workflow execution and legacy-state presentation/synchronization | current; Monthly legacy status mirror remains transitional |
| Requests | Ramadan store/execution/monitoring/review/change/decision requests | stage-specific validation and authorization inputs | current controllers |
| Concerns | `InteractsWithMonthlyActivities`, `InteractsWithMonthlyActivityApprovals` | shared Monthly visibility/status/needs/action-log and approval helpers | active focused controllers |
| Routes | Ramadan lifecycle/review/guidance groups and focused Monthly targets | stable web contracts with role/permission and branch middleware | statically inspected; runtime route boot pending |
| Permissions | Ramadan view/create/edit/submit/approve/execute/monitor/review/close/change permissions | explicit stage access | seeded through aggregate authorization seeder |
| Workflows | `ramadan_iftars`, `ramadan_iftar_change_requests`, dynamic Monthly/Agenda definitions | planning/change approval sequences and logs | definitions in seeders/config/services; runtime resolution pending |
| Seeders | Event reference sub-seeders, `EventReferenceDataSeeder`, `CanonicalExecutionNeedTypeSeeder` | deterministic catalogue bootstrap | source current; see the duplicate-call warning in §14 |
| Tests | Common targeting/execution/monitoring/needs; Ramadan lifecycle/versioning; Monthly route/regression; schema/bootstrap tests | architecture and behavior safety net | present but not executed in this dependency-less checkout |
| Views/UI | Ramadan planning/workspace/execution/monitoring/review/change screens and updates to Monthly/Agenda views | operational web flow and localized status presentation | source current; browser/RTL pending |
| Documentation | current-state, foundations, Ramadan slices, consolidation, identity audit, runtime report, this handover | decision history and continuation map | statuses classified in §17 |

### MODIFIED

| Artifact | Old responsibility | Current responsibility / reason | Compatibility / transitional state |
|---|---|---|---|
| `MonthlyActivity` | Monthly aggregate with namespace-local support relations | same aggregate, now imports Phase 2.8A support models and exposes generalized target relations while preserving legacy fields | FQCN and table unchanged; JSON needs/post-execution and volunteer summary remain authoritative/transitional |
| `AgendaEvent` | Agenda aggregate with namespace-local support relations | same aggregate, now imports relocated Agenda support/catalogue models and links Monthly/Ramadan | FQCN/table unchanged because workflow/audit/request history stores it |
| Monthly controllers/routes | two giant implementation owners | focused controllers own browse, calendar, planning, workspace, lifecycle, feedback, approvals, reports, and trash | public route names/URIs preserved; giants removed |
| `DynamicWorkflowService` and lifecycle/presenter services | workflow definitions and legacy state were more distributed | FQCN-based instances/logs plus module-specific synchronization/presentation | identity-sensitive; Monthly legacy fields still mirrored |
| historical Monthly tables | Monthly-only ownership | four tables generalized with nullable subject/report/team ownership while retaining old columns and IDs | table names intentionally transitional for team/supply; do not rename casually |
| lookup models/seeders | global `App\Models` ownership and multiple bootstrap entry points | Phase 2.8A flat Events namespace; one reference aggregate plus canonical needs source | values/scopes/codes unchanged; runtime bootstrap pending |
| reports/search/views | old model imports/status helpers | imports point to relocated support/catalogue models; reporting still reads historical workflows/Monthly fields | no report semantics intentionally changed |

### DELETED

| Deleted artifact | Why | Replacement | Compatibility warning |
|---|---|---|---|
| `MonthlyActivitiesController` | giant controller was physically split | focused Monthly controllers | do not restore delegation/inheritance |
| `MonthlyActivitiesApprovalsController` | giant approval owner was physically split | approval queue/decision/post-execution/change-request controllers | route contracts depend on focused targets |
| migrations creating `subject_target_groups`, `execution_team_members`, `subject_supplies`, `field_verifications` | duplicated established Monthly rows/concepts before release | Phase 2.3 generalization migration | names may remain in historical docs/negative tests only |
| `ExecutionTeamMember`, `SubjectSupply`, `FieldVerification` models | mapped to abandoned duplicate tables | `MonthlyActivityTeam`, `MonthlyActivitySupply`, `PostExecutionVerification` | current names/tables are intentional |
| `ExecutionNeedTypeSeeder` | competing non-canonical catalogue source | `CanonicalExecutionNeedTypeSeeder` | never restore the old seeder |
| old `App\Models` files for fourteen Phase 2.8A models | low-risk ownership moved | one class each under flat Events namespace | no wrappers or aliases were retained |

### SUPERSEDED / REPLACED

| Original approach | Why reversed | Current approach | Current source |
|---|---|---|---|
| `subject_target_groups` | duplicate targeting rows and loss of Monthly IDs | generalized `event_target_group` | `SubjectTargetGroup`; Phase 2.3 migration |
| `execution_team_members` | duplicate of historical members | `execution_teams` header + generalized `monthly_activity_team` members | `ExecutionTeam`, `MonthlyActivityTeam` |
| `subject_supplies` | duplicate of historical supplies | generalized `monthly_activity_supplies` | `MonthlyActivitySupply` |
| `field_verifications` | duplicate verification concept | generalized `post_execution_verifications` | `MonitoringReport`, `PostExecutionVerification` |
| focused controllers inheriting giants | implementation still physically centralized | standalone focused controllers, giant files deleted | `app/Modules/Events/Http/Controllers/MonthlyActivities` |
| `ExecutionNeedTypeSeeder` | multiple catalogue authorities | canonical definitions/model + `CanonicalExecutionNeedTypeSeeder` | model and seeder in current namespaces |
| monitoring review deferred | review actors/mismatch rules were initially unresolved | explicit supervisor review/return/resubmit/approve lifecycle | `RamadanMonitoringReviewController`, monitoring service |
| partial Phase 1.13 closure | monitoring review/readiness was incomplete | integrated closure after approved monitoring | closure model/service/controller/tests |

## 3. Current final database architecture

“Final” means the current source decision, not runtime proof.

### Monthly domain

| Table | Primary model / namespace | M | R | A | Classification |
|---|---|:---:|:---:|:---:|---|
| `monthly_activities` | `MonthlyActivity` / `App\Models` | Y | N | linked from Agenda | final aggregate; legacy payload columns remain authoritative |
| `monthly_activity_approvals` | `MonthlyActivityApproval` / Events | Y | N | N | retained legacy history |
| `monthly_activity_change_logs`, `monthly_activity_evaluation_responses`, `monthly_activity_followups`, `monthly_activity_partners`, `monthly_activity_sponsors` | corresponding Events models | Y | N | N | final current support tables |
| `monthly_activity_attachments` | `MonthlyActivityAttachment` / `App\Models` | Y | N | N | final table, namespace move pending |
| `monthly_activity_volunteer_needs` | `MonthlyActivityVolunteerNeed` / `App\Models` | Y | N | N | legacy-authoritative; semantic decision pending |
| Monthly edit/delete request tables | request models / `App\Models` | Y | N | N | current; stored FQCN sensitive |

### Agenda domain

| Table | Primary model / namespace | M | R | A | Classification |
|---|---|:---:|:---:|:---:|---|
| `agenda_events` | `AgendaEvent` / `App\Models` | sync source | optional link | Y | final aggregate; identity-sensitive namespace |
| `agenda_event_targets`, `agenda_approvals`, `agenda_participations` | Phase 2.8A Events models | N | N | Y | final support tables |
| `agenda_event_partner_departments` | pivot | N | N | Y | final |
| Annual Agenda edit/delete request tables | request models / `App\Models` | N | N | Y | current; stored FQCN sensitive |

### Ramadan domain

| Table | Primary model / namespace | M | R | A | Classification |
|---|---|:---:|:---:|:---:|---|
| `ramadan_iftars` | `RamadanIftar` / Events | N | Y | optional FK | final independent aggregate |
| attendee/meal/meal-item/gift/program-segment tables | corresponding Ramadan Events models | N | Y | N | final planning/actual detail |
| `ramadan_iftar_change_requests` | `RamadanIftarChangeRequest` / Events | N | Y | N | final versioning request |
| `event_guidance_versions` | `EventGuidanceVersion` / Events | N | Y | N | final prerequisite; business-managed |

### Common Events

| Table | Primary model / namespace | M | R | A | Classification |
|---|---|:---:|:---:|:---:|---|
| `target_groups` | `TargetGroup` / Events | Y | Y | indirectly | final catalogue |
| `beneficiary_segments` | `BeneficiarySegment` / Events | N | Y | N | final catalogue |
| `event_target_group` | `SubjectTargetGroup` / Events plus Monthly pivot | Y | Y | N | final generalized targeting |
| `execution_teams` | `ExecutionTeam` / Events | N | Y | N | final shared header |
| `execution_team_members` | `ExecutionTeamMember` / Events | Y | Y | N | renamed generalized historical member storage |
| `event_supplies` | `EventSupply` / Events | Y | Y | N | renamed generalized historical supply storage |
| `subject_volunteer_requirements` | `SubjectVolunteerRequirement` / Events | N | Y | N | final segmented Common structure |
| `execution_need_types` | `ExecutionNeedType` / Events | JSON codes | Y | N | final canonical master |
| `subject_execution_needs` | `SubjectExecutionNeed` / Events | not yet | Y | N | final Common/Ramadan transactional storage |
| `monitoring_methods` | `MonitoringMethod` / Events | N | Y | N | final catalogue |
| `monitoring_reports` | `MonitoringReport` / Events | not yet | Y | N | final report envelope |
| `post_execution_verifications` | `PostExecutionVerification` / `App\Models` | Y | Y | N | final shared verification storage; identity-sensitive namespace |

### Application-wide infrastructure

`users`, roles/permissions pivots, branches, centers, departments/units,
`workflows`, `workflow_steps`, `workflow_instances`, `workflow_logs`,
`workflow_action_logs`, `audit_logs`, notifications, communications, donations,
workshops, correspondence, attachments, and payments stay application-wide.

### Phase 2.3 final consolidation — never fork it again

```text
targeting          event_target_group
team members       monthly_activity_team
supplies           monthly_activity_supplies
field verification post_execution_verifications
```

`subject_target_groups`, `execution_team_members`, `subject_supplies`, and
`field_verifications` are abandoned development tables and must not be recreated.

## 4. Current model ownership and stored identity

### Final flat `App\Modules\Events\Models`

- Agenda support: `AgendaApproval`, `AgendaEventTarget`, `AgendaParticipation`.
- Monthly support: `MonthlyActivityApproval`, `MonthlyActivityChangeLog`,
  `MonthlyActivityEvaluationResponse`, `MonthlyActivityFollowup`,
  `MonthlyActivityPartner`, `MonthlyActivitySponsor`.
- Catalogues/registries: `EventCategory`, `EventStatusLookup`, `EventType`,
  `ExecutionNeedType`, `TargetGroup`, `BeneficiarySegment`,
  `MobilizationMethod`, `MonitoringMethod`, `CommunityOrganization`,
  `LocalCommunity`, `EventGuidanceVersion`, `EventContexts`, `EventSubjectTypes`.
- Common execution/monitoring: `ExecutionTeam`, `SubjectTargetGroup`,
  `SubjectExecutionNeed`, `SubjectVolunteerRequirement`, `MonitoringReport`.
- Ramadan: `RamadanIftar`, `RamadanIftarAttendee`, `RamadanIftarChangeRequest`,
  `RamadanIftarMeal`, `RamadanIftarMealItem`, `RamadanIftarGift`,
  `RamadanIftarProgramSegment`.

### Events-owned models deliberately still under `App\Models`

| Model | Stored identity | Route binding | Other blocker | Planned slice |
|---|---|---|---|---|
| `MonthlyActivity` | critical: workflow/action/audit/request/notification/correspondence FQCN | extensive | historical aggregate | C5 after C1/C2 |
| `AgendaEvent` | critical: workflow/action/audit/request FQCN | extensive | historical aggregate | C4 after C1/C2 |
| four Monthly/Agenda request models | their FQCN is workflow identity; row also stores aggregate FQCN | request parameters exist | two-layer identity | C3 |
| `MonthlyActivityAttachment` | none found | yes | medium route/source surface | B1 |
| `MonthlyActivityTeam` | none found | yes | shared storage/name transitional | B1, without rename |
| `MonthlyActivitySupply` | none found | yes | shared storage/name transitional | B1, without rename |
| `PostExecutionVerification` | `audit_logs.entity_type` | no direct route found | audit history | C6 |
| `MonthlyActivityVolunteerNeed` | none found | no | cardinality/segmentation semantics | G1 |

### DO NOT MOVE THESE MODELS BLINDLY

FQCN-sensitive stores are:

- `workflow_instances.entity_type` — exact class drives uniqueness and dynamic
  `class_exists`/`::query()` resolution;
- `workflow_action_logs.entity_type` and `audit_logs.entity_type` — historical
  filtering/rendering;
- `official_correspondences.correspondable_type` — Eloquent inverse morph for
  `MonthlyActivity`, a critical extra dependency;
- Monthly/Agenda request-table `entity_type` — aggregate FQCN, while each
  request model is separately stored as its workflow entity;
- notification JSON metadata — `get_class($entity)` values.

Moving `MonthlyActivity`, `AgendaEvent`, the four request models, or
`PostExecutionVerification` requires live distinct-value inventory, explicit
old/new reads, one canonical writer, duplicate-workflow prevention, transactional
backfill, rollback/queue planning, observation, and time-boxed compatibility.
Never solve this with an unrestricted global morph map or indefinite duplicate
writable models.

### Stable alias architecture

`EventSubjectTypes` reserves `monthly_activity` and `ramadan_iftar`. These values
are stored in `event_target_group`, `monthly_activity_supplies`,
`execution_teams`, `subject_volunteer_requirements`, `subject_execution_needs`,
and `monitoring_reports`. A stable subject alias is a domain identity, **not** a
PHP model FQCN; namespaces can change without rewriting those rows. There is no
Agenda subject alias and no global morph map.

## 5. Monthly current state

Monthly remains operationally centered on `monthly_activities`:

- planning can create/edit directly or synchronize from Agenda;
- browse/calendar/workspace apply branch visibility and version/read-only rules;
- submission and dynamic approvals coexist with mirrored legacy status columns;
- execution needs remain `execution_needs_payload` plus
  `execution_needs_followup`; only the type catalogue is shared;
- feedback, post-execution decision, evaluation/follow-up, lifecycle/close,
  change requests, trash/restore, and reports remain current behaviors;
- `post_execution_payload` remains legacy-authoritative; Monthly has not moved
  wholesale to `monitoring_reports`;
- the Monthly volunteer summary remains `monthly_activity_volunteer_needs`;
- existing edit/delete request/version semantics remain authoritative.

Do not describe Monthly as fully normalized into Common storage. Preserve old
plans, actor attribution, decisions, reports, JSON meaning, and version history.

### Final focused controller ownership

| Controller | Public responsibility/actions |
|---|---|
| `MonthlyActivitiesBrowseController` | index/browse/filter/pagination |
| `MonthlyActivityCalendarController` | calendar |
| `MonthlyActivityPlanningController` | create, Agenda sync, store, edit, update |
| `MonthlyActivityWorkspaceController` | active and deleted workspaces |
| `MonthlyActivityLifecycleController` | submit, close |
| `MonthlyActivityFeedbackController` | returned and post-execution feedback |
| `MonthlyActivityApprovalQueueController` | queue index and details |
| `MonthlyActivityApprovalDecisionController` | planning and execution-need decisions |
| `MonthlyActivityPostExecutionDecisionController` | post-execution decision |
| `MonthlyActivityChangeRequestDecisionController` | edit/delete request decisions |
| `MonthlyActivityTrashController` | trash, restore, delete/request deletion |
| `MonthlyActivityReportsController` | change-request reports |

`InteractsWithMonthlyActivities` centralizes shared visibility, status,
execution-needs, action-log, and change-request helpers.
`InteractsWithMonthlyActivityApprovals` centralizes approval/post-execution
authorization and presentation helpers. `MonthlyActivitiesController` and
`MonthlyActivitiesApprovalsController` are deleted and must not return.

## 6. Ramadan current state and invariant rules

### Main lifecycle

| Stage | Controller | Service | Model/status identity | Main storage | Primary route actors |
|---|---|---|---|---|---|
| Guidance | `RamadanGuidanceController` | `RamadanGuidanceAcceptanceService` | published `EventGuidanceVersion` accepted by user | guidance version row; presented/accepted version, time, and user are session keys | relations manager/officer, super admin |
| Planning | `RamadanIftarController` | `RamadanIftarPlanningService` | Ramadan draft | aggregate and planning detail/Common tables | relations manager/officer, super admin |
| Submission | `RamadanIftarSubmissionController` | `RamadanIftarSubmissionService` | `ramadan_iftars` workflow FQCN; submitted | aggregate + workflow tables/action logs | submit permission |
| Planning approval | approval queue/decision controllers | `RamadanIftarApprovalService` + dynamic workflow | changes requested/approved | workflow instances/logs/action logs | configured approvers, branch isolation |
| Execution | `RamadanIftarExecutionController` | `RamadanIftarExecutionService` | planned → in progress | aggregate actuals, attendees, teams/members, supplies, needs | follow-up officer/super admin |
| Completion | same | same | execution completed | same + action logs | follow-up officer/super admin |
| Monitoring | `RamadanIftarMonitoringController` | `RamadanIftarMonitoringService` | report draft/submitted | monitoring reports + post-execution verifications | follow-up officer/super admin |
| Review | `RamadanMonitoringReviewController` | same monitoring service | returned/resubmitted/approved | report/verifications/action logs | supervisor/super admin |
| Closure | `RamadanIftarClosureController` | `RamadanIftarClosureService` | aggregate closed | aggregate + action log | supervisor/super admin |

### Approved-plan change flow

Approved, not-started Version N → `RamadanIftarChangeRequestController` →
`RamadanIftarChangeRequestService`/dynamic request workflow → review controller →
approved request creates draft Version N+1 → Version N+1 follows the normal
planning submission/approval workflow.

### Rules that must not be lost

1. Ramadan is independent of `monthly_activities`; `agenda_event_id` is optional.
2. A published guidance version and user acceptance are required before planning.
3. Planning approval and execution are separate stages.
4. Execution completion does not require monitoring approval.
5. Closure requires approved planning, completed execution, an approved
   authoritative monitoring report, and a not-already-closed aggregate.
6. A mismatch can be approved only with a documented note.
7. The authoritative report is the latest approved by `updated_at DESC`, then
   highest `id` as tie-breaker.
8. An approved-plan change is allowed only before execution starts.
9. Final request approval creates Version N+1; the source stays immutable.
10. Version N+1 returns to normal planning approval.
11. Planning-owned targets/segments/meals/items/gifts/program/teams/members/
    volunteers/supplies/needs are copied; actuals, attendance, monitoring,
    verification history, workflow instances/logs, and closure state are not.

## 7. Common Events foundations

### Execution needs

`execution_need_types` is the master catalogue, bootstrapped only by
`CanonicalExecutionNeedTypeSeeder`. `subject_execution_needs` is transactional
Common/Ramadan storage. Monthly still uses legacy JSON and is not migrated.

Canonical model codes are: `volunteers`, `official_correspondence`,
`media_coverage`, `supplies`, `official_sponsorship`, `external_partners`,
`ceremony_agenda`, `transport`, `maintenance_workers`, `gifts_shields`,
`programs_participation`, `certificates`, `thanks_letters`, `invitations`.

### Monitoring

- `monitoring_methods`: stable method catalogue (`cameras`, `field_visit`).
- `monitoring_reports`: lifecycle/envelope (`draft`, `submitted`, `returned`,
  `approved`).
- `post_execution_verifications`: final shared field-verification storage.

Monthly post-execution/evaluation/follow-up remains partly legacy and is not
automatically equivalent to a monitoring report. Never recreate
`field_verifications`.

### Targeting

`target_groups` is the shared catalogue. `beneficiary_segments` adds Ramadan
segmentation. `event_target_group` retains Monthly `monthly_activity_id` and old
pivot columns while adding `subject_type`, `subject_id`, segment, planned/actual
count, and notes for generalized use. Monthly and Ramadan share the table through
different relationship shapes. Never recreate `subject_target_groups`.

### Teams, supplies, volunteers

- `execution_teams` is the shared header; `monthly_activity_team` is the current
  shared member table. `ExecutionTeamMember` is only a possible future model
  name—not implemented. Do not recreate `execution_team_members`.
- `monthly_activity_supplies`/`MonthlyActivitySupply` is current shared storage.
  `EventSupply` is only a possible future model name. Do not recreate
  `subject_supplies`.
- `monthly_activity_volunteer_needs` and `subject_volunteer_requirements` remain
  separate because summary cardinality and segmented requirement semantics are
  not proven equivalent. Do not force a merge.

## 8. Bootstrap and seeder truth

The intended Phase 2.5 chain is:

```text
DatabaseSeeder
├── EventReferenceDataSeeder
│   ├── DepartmentSeeder
│   ├── EventTypeSeeder
│   ├── TargetGroupSeeder
│   ├── BeneficiarySegmentSeeder
│   ├── MonitoringMethodSeeder
│   ├── EventStatusLookupSeeder
│   └── EventCategorySeeder
├── CanonicalExecutionNeedTypeSeeder
└── CompleteRolePermissionSeeder
```

**Current-code discrepancy:** `DatabaseSeeder` calls
`CanonicalExecutionNeedTypeSeeder` once before and once after
`CompleteRolePermissionSeeder`. The seeder is written idempotently, but Phase 2.6
never executed this chain. Treat the second call as an observed bootstrap defect
candidate: reproduce during A1, remove only with a failing/contract-backed fix,
and update the failure ledger. Do not silently describe the current chain as a
single call.

Default bootstrap intentionally does not invent rows for
`event_guidance_versions`, `mobilization_methods`, `community_organizations`, or
`local_communities`. Published guidance remains a manual business prerequisite.

## 9. Runtime verification debt and evidence boundary

```text
PHASE 2.6 IS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED
```

Composer package downloads were blocked by the environment proxy with cURL
error 56: `CONNECT tunnel failed, response 403`. Consequently Laravel boot,
`migrate:fresh`, first/second `db:seed`, idempotency and row inspection,
`route:list`, PHPUnit, browser smoke, and Arabic/RTL smoke are **not verified**.

### STATICALLY VERIFIED

- PHP syntax of files changed in completed source slices;
- `git diff --check` at slice boundaries;
- source/import/FQCN, identity-writer, morph, route-target, migration-dependency,
  abandoned-schema, and duplicate-file scans;
- focused controller action ownership and model relationship wiring by source;
- Phase 2.8A old FQCNs absent from active PHP source.

### RUNTIME VERIFIED

None in this checkout. Composer lock/platform validation is environment
validation, not application runtime verification.

### NOT VERIFIED

Laravel boot, routes, fresh/upgrade/rollback migration behavior, real schema
columns/indexes/FKs, seeding/double-seeding, relationships against a database,
all tests, concurrency, browser rendering, and RTL.

## 10. Important commit / phase timeline

| Commit | Title | Phase | Current? |
|---|---|---|---|
| `3d825c7`, `9cb4375` | phase-zero safety tests/blockers | 0/0.1 | yes, characterization |
| `376da3a` | restore Events foundation/centralize subject types | early 1.x | yes |
| `637e967`–`88d8d38` | Common targeting/reference/Ramadan aggregate/detail/execution/monitoring foundations | 1.x foundations | yes except four schemas superseded by 2.3 |
| `26da705`, `a14c904`, `d12f9ee` | Ramadan planning, guidance, normalized needs | 1.x | yes |
| `1d87038`, `705b0b3`, `240ad51`, `80f9abe` | submission/approval, execution, monitoring, review | 1.x | yes |
| `5911119`, `52184df` | closure and integrated completion/UI polish | 1.13 | later commit completes earlier partial state |
| `f67d8eb` | approved-plan versioning | 1.14 | yes |
| `485ab96`, `2a9428a` | modularize then retire legacy Monthly controllers | 2.1 | final standalone controllers current |
| `040852d` | data-model consolidation audit | 2.2 | decision history/current supporting doc |
| `b9a47c0` | consolidate pre-release Event storage | 2.3 | yes; removes four duplicate tables/models |
| `892705c` | reconcile current Events architecture | 2.4 | yes |
| `239e3be` | reconcile reference-data bootstrap | 2.5 | current intent; duplicate canonical call still needs runtime handling |
| `9b60b78` | identity audit and runtime report/current-state update | 2.6/2.7 documentation | yes; 2.6 remains incomplete |
| `615d2d0` | move low-risk support models | 2.8A | current tip before this handover |

## 11. Developer file map

| Need | Start here |
|---|---|
| authoritative current architecture | `docs/events-architecture-current-state.md` |
| practical continuation | `docs/events-branch-handover.md` |
| stored identities/namespace plan | `docs/events-model-identity-and-namespace-audit.md` |
| runtime blocker/failure ledger | `docs/events-runtime-verification-report.md` |
| Phase 2.2 decisions | `docs/events-data-model-consolidation-audit.md` |
| Monthly controller ownership | `docs/monthly-activities-controller-refactor.md` |
| Ramadan versioning | `docs/ramadan-iftar-approved-plan-versioning.md` |
| historical Ramadan rollout | `docs/ramadan-iftars-master-todo.md` (history, not phase authority) |
| Events code | `app/Modules/Events` |
| focused controllers | `app/Modules/Events/Http/Controllers/{MonthlyActivities,Ramadan}` |
| final flat model namespace | `app/Modules/Events/Models` |
| Ramadan services | `app/Modules/Events/Services` |
| schema | `database/migrations` |
| bootstrap | `database/seeders` |
| regression safety net | `tests/Feature`, `tests/Unit` |
| UI | `resources/views/pages/events`, plus existing `agenda` and `monthly_activities` pages |

## 12. Executable remaining backlog

Each item is a separate PR/slice unless explicitly stated otherwise.

### TASK A1 — Complete Phase 2.6 Runtime Verification

- **Priority/Risk:** P0; HIGH / RELEASE BLOCKER.
- **Prerequisites:** network-capable PHP 8.3/Composer environment; explicitly
  disposable supported database.
- **Scope:** restore exact lock; boot Artisan/about/routes; fresh migration twice;
  inspect Phase 2.3 schema; seed twice and inspect catalogues/business-managed
  zeros; run focused Common/Monthly/Agenda/Ramadan/workflow suites, then full
  suite; browser/English/Arabic RTL smoke; maintain failure ledger. Reproduce and
  resolve the duplicate canonical-seeder call if it violates the contract.
- **Non-goals:** architecture/model moves, package updates, production data.
- **DoD:** all Phase 2.6 gates pass or exact blocker remains documented; only
  reproduced minimal fixes; runtime report/current-state updated.

### TASK B1 — Move Route-Bound Shared Support Models

- **Priority/Risk:** P1 after A1; MEDIUM.
- **Prerequisites:** A1 baseline green.
- **Scope:** move only `MonthlyActivityAttachment`, `MonthlyActivityTeam`, and
  `MonthlyActivitySupply` to flat Events namespace; update imports, aggregate/
  Common relations, route type hints, seeders/views/tests; static old-FQCN scan;
  focused implicit-binding and relationship regression.
- **Non-goals:** renames, tables, aggregates, requests, verification, volunteers.
- **DoD:** one class per model, old files/references absent, route parameter names
  and DB behavior unchanged, focused and broad regressions green.

### TASK C1 — Live Stored Identity Inventory

- **Priority/Risk:** P1; HIGH, read-only.
- **Prerequisites:** A1; sanitized production-like snapshot/access approval.
- **Scope:** distinct values/counts/samples from all identity columns and JSON
  metadata; queue/dead-letter/backlog and external-worker inventory; compare IDs
  that could collide under dual identity.
- **Non-goals:** writes, backfills, class moves.
- **DoD:** reviewed value matrix, row counts, anomalies, rollback/retention needs.

### TASK C2 — Identity Compatibility Layer

- **Priority/Risk:** P1; HIGH.
- **Prerequisites:** C1 and green A1.
- **Scope:** explicit old/new resolution and duplicate prevention for workflow,
  request, audit/history, notification, and correspondence identities; tests;
  one canonical writer plan and retirement criteria.
- **Non-goals:** unrestricted morph map, `class_alias`, aggregate cutover.
- **DoD:** old rows resolve, new writer identity is deterministic, duplicate
  workflows impossible, rollback and monitoring plan proven.

### TASK C3 — Identity-Sensitive Request Model Cutover

- **Priority/Risk:** P2; HIGH.
- **Prerequisites:** C2.
- **Scope:** one controlled cutover for the four Monthly/Agenda request classes,
  including workflow entity values and existing request loading/decisions.
- **Non-goals:** aggregate moves in this PR.
- **DoD:** historical/new requests and workflows work; transactional backfill and
  rollback tested; no duplicate instances.

### TASK C4 — AgendaEvent Cutover

- **Priority/Risk:** P2; HIGH.
- **Prerequisites:** C2, C3, green Agenda/Monthly-sync regression.
- **Scope:** compatibility-backed namespace move and stored identity backfills
  for workflow/action/audit/request/notification data.
- **Non-goals:** Monthly aggregate move.
- **DoD:** history, requests, routes, Agenda approval and Monthly sync pass across
  old/new identities; observation evidence recorded.

### TASK C5 — MonthlyActivity Cutover

- **Priority/Risk:** P2; CRITICAL.
- **Prerequisites:** C2, C3, preferably C4 lessons; full Monthly baseline green.
- **Scope:** compatibility-backed aggregate move covering workflow/action/audit/
  request/notification identity and official-correspondence morph.
- **Non-goals:** JSON needs/monitoring/volunteer migration.
- **DoD:** all historical identity and correspondence rows resolve; no duplicate
  workflows; route/Monthly/Agenda sync/report suites pass; rollback proven.

### TASK C6 — PostExecutionVerification Cutover

Superseded by the Phase 2.8C handover addendum and its concrete focused design.
The implementation slice is now named **PHASE 2.8D —
POSTEXECUTIONVERIFICATION IDENTITY CUTOVER** and remains gated on Phase 2.6 and
the live identity inventory.

### TASK C7 — Compatibility Retirement

- **Priority/Risk:** P3; HIGH if premature.
- **Prerequisites:** C3–C6 deployed, stored-value scans clean, queues empty,
  rollback window expired, monitoring stable.
- **Scope:** remove time-boxed old readers/wrappers and migration-only code.
- **Non-goals:** unrelated dead-code cleanup.
- **DoD:** zero old values/backlogs, final writer/read tests pass, rollback policy
  approved, no duplicate active class remains.

### TASK D1 — Reassess MonthlyActivityTeam Model Name

- **Priority/Risk:** P3; MEDIUM.
- **Prerequisites:** B1 and runtime stability.
- **Scope:** decide whether a model-only `ExecutionTeamMember` name improves API;
  inventory raw references and identity/serialization again.
- **Non-goals:** do not rename `monthly_activity_team` for aesthetics.
- **DoD:** evidence-backed KEEP/rename decision and isolated tested plan.

### TASK D2 — Reassess MonthlyActivitySupply Model Name

- **Priority/Risk:** P3; MEDIUM.
- **Prerequisites:** B1 and runtime stability.
- **Scope:** decide whether model-only `EventSupply` is justified.
- **Non-goals:** do not rename `monthly_activity_supplies` for aesthetics.
- **DoD:** evidence-backed KEEP/rename decision and isolated tested plan.

### TASK E1 — Monthly Execution Needs Normalization

- **Priority/Risk:** P2; HIGH.
- **Prerequisites:** A1; historical JSON/data reconciliation design; do not overlap
  C5 changes to `MonthlyActivity`.
- **Scope:** map/backfill/read-transition `execution_needs_payload` and
  `execution_needs_followup` toward `subject_execution_needs`, preserving codes,
  decisions, actors, dates, reports, and old plan versions.
- **Non-goals:** semantic loss, destructive one-shot migration, workflow redesign.
- **DoD:** reconciliation report, dual-read/cutover/rollback, historical and new
  flows/reports proven, no unmapped data hidden.

### TASK F1 — Monthly Monitoring Envelope Migration

- **Priority/Risk:** P2; HIGH.
- **Prerequisites:** A1; semantic mapping/audit; coordinate with C6 and avoid
  overlapping verification-class work.
- **Scope:** evaluate/backfill Monthly `post_execution_payload` and true
  verification data toward `monitoring_reports`/`post_execution_verifications`.
- **Non-goals:** collapse non-equivalent evaluation/follow-up concepts.
- **DoD:** field-level equivalence decisions, lossless migration/rollback,
  Monthly reports/lifecycle and Ramadan monitoring regression green.

### TASK G1 — Volunteer Storage Semantic Decision

- **Priority/Risk:** P2; HIGH semantic risk.
- **Prerequisites:** A1 and representative historical data.
- **Scope:** compare Monthly single-summary cardinality with segmented Common
  requirements; choose keep/adapter/generalize and define migration only if safe.
- **Non-goals:** forced merge or changed volunteer meaning.
- **DoD:** approved semantics/cardinality decision, compatibility and test plan.

### TASK H1 — Remove Expired Namespace Compatibility

- **Priority/Risk:** P3; HIGH if early. **Prerequisite:** C7 evidence. **Scope:**
  remove only expired namespace bridges. **Non-goals:** broad cleanup. **DoD:**
  old identities absent and full regression green.

### TASK H2 — Dead-Code Cleanup

- **Priority/Risk:** P3; LOW/MEDIUM. **Prerequisite:** completed cutovers.
  **Scope:** prove and remove unused transitional services/imports. **Non-goals:**
  behavioral refactor. **DoD:** usage proof and full regression.

### TASK H3 — Historical Compatibility Cleanup

- **Priority/Risk:** P3; MEDIUM. **Prerequisite:** retention/rollback approval.
  **Scope:** remove obsolete mirrors/readers only when stored history no longer
  needs them. **Non-goals:** rewrite history. **DoD:** retention evidence and
  reports preserved.

### TASK H4 — Final Production-Readiness Review

- **Priority/Risk:** P0 before release; HIGH. **Prerequisite:** required C/E/F/G
  decisions and all deployed slices. **Scope:** fresh/upgrade/rollback, seed,
  security/branch scope, workflows, reports, queues, browser/RTL, observability.
  **Non-goals:** feature work. **DoD:** signed release checklist and no open P0.

## 13. Dependency graph and developer split

```text
A1 runtime baseline (release blocker)
 ├─> B1 route-bound support move ─> D1 / D2 naming decisions
 ├─> C1 live identity inventory ─> C2 compatibility layer
 │                              └─> C3 request cutover
 │                                   ├─> C4 AgendaEvent cutover
 │                                   └─> C5 MonthlyActivity cutover
 │                              └─> C6 verification cutover
 │                    C3+C4+C5+C6 ─> C7 ─> H1/H2/H3
 ├─> G1 volunteer semantic decision
 ├─> E1 Monthly needs normalization (serialize with C5)
 └─> F1 Monthly monitoring migration (coordinate with C6)

All required deployed slices ─> H4 production-readiness review
```

Recommended assignment:

- **Developer A first:** A1 only—environment, DB, failure ledger, regression.
- **Developer B after A1:** B1. It can run beside read-only C1 once A1 establishes
  the baseline; both must avoid the same imports/models during final integration.
- **Developer C after A1:** C1, then design C2. C2 must merge before any C3–C6.
- **Cutover owner:** serialize C3, C4, C5; do not parallelize aggregate identity
  writers/backfills. C6 may be separate only with non-overlapping audit migration.
- **Later specialists:** E1 and F1 must not overlap C5/C6 respectively; G1 can be
  researched independently after A1 but cannot implement a merge without review.

## 14. DO NOT REINTRODUCE

- **DO NOT RECREATE `subject_target_groups`.**
- **DO NOT RECREATE `execution_team_members` as a duplicate table.**
- **DO NOT RECREATE `subject_supplies`.**
- **DO NOT RECREATE `field_verifications`.**
- **DO NOT RESTORE `MonthlyActivitiesController`.**
- **DO NOT RESTORE `MonthlyActivitiesApprovalsController`.**
- **DO NOT RESTORE `ExecutionNeedTypeSeeder`.**
- **DO NOT ADD an unrestricted global morph map.**
- **DO NOT MOVE `MonthlyActivity`, `AgendaEvent`, request models, or
  `PostExecutionVerification` without stored-identity compatibility.**
- **DO NOT AUTO-SEED fake guidance, mobilization, organization, or community data.**
- Do not rename historical shared tables merely for aesthetics.
- Do not migrate Monthly JSON, monitoring, or volunteers as namespace cleanup.
- Do not make Ramadan a `monthly_activities` subtype or require Agenda linkage.

## 15. New developer starting checklist

1. Read `docs/events-architecture-current-state.md`.
2. Read this handover.
3. Read the identity audit and runtime report for the task you take.
4. Confirm Phase 2.6 remains incomplete and inspect the current branch/log.
5. Do not revive superseded schema/controllers/seeders.
6. Work one task slice and one identity cutover at a time.
7. Preserve Monthly rows, JSON meaning, actors, approvals, reports, and versions.
8. Keep Ramadan independent and preserve its lifecycle rules.
9. Preserve old/new stored FQCN compatibility where required.
10. Use a disposable DB for destructive verification.
11. Update the current-state document and failure ledger after each phase.
12. Do not call static inspection runtime proof.

## 16. Immediate recommendation

The next developer should take **TASK A1 — Complete Phase 2.6 Runtime
Verification** and no architecture refactor in the same slice. It is the only
honest prerequisite for release and for every proposed model/data cutover.

## Phase 2.8B handover addendum (2026-09-14)

`PHASE 2.8B COMPLETE`

Phase 2.8B supersedes the earlier import-only recommendation in this handover.
The final support map is:

| Concept | Final model | Final table | Scope |
|---|---|---|---|
| attachment | `App\\Modules\\Events\\Models\\MonthlyActivityAttachment` | `monthly_activity_attachments` | Monthly only |
| team member | `App\\Modules\\Events\\Models\\ExecutionTeamMember` | `execution_team_members` | Monthly + Common/Ramadan |
| supply | `App\\Modules\\Events\\Models\\EventSupply` | `event_supplies` | Monthly + Common/Ramadan |

The original development-only `execution_team_members` table from Phase 1.x
was abandoned in Phase 2.3.

The current `execution_team_members` name is the renamed and generalized
historical `monthly_activity_team` table.

Historical rows and IDs were preserved.

`event_supplies` is the renamed generalized historical
`monthly_activity_supplies` table, not a newly created replacement table.
The forward migration performs table renames only and reverses only those names.
All legacy Monthly ownership columns remain. All route URLs and parameter names,
stable Event subject aliases, planning/execution rules, and Phase 1.14 deep-copy
field selection remain unchanged.

The required deferred runtime matrix is: migration fresh/rollback/reapply;
Monthly CRUD, confirmation, reporting and post-execution flows; Ramadan planning,
approval, execution, monitoring, completion, closure and revision flows; seed
bootstrap twice; full PHPUnit; and browser/API route-binding verification.

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

## Phase 2.8C handover addendum (2026-09-14)

`PHASE 2.8C COMPLETE`

TASK C6 has been split. Phase 2.8C completed the compatibility design and added
the exact identity/write boundary without moving the model or changing stored
values. The concrete plan is
`docs/post-execution-verification-identity-cutover.md`.

### TASK C6 / PHASE 2.8D — PostExecutionVerification Identity Cutover

- **Current gate:** `POSTEXECUTIONVERIFICATION CUTOVER NOT READY`.
- **Prerequisites:** complete Phase 2.6; execute and review the documented live
  `audit_logs.entity_type` inventory; explain unknown/missing/duplicate results;
  confirm rollback targets accept both identities.
- **Scope:** move only `PostExecutionVerification`, update its imports and
  explicit view references, switch the focused writer to the canonical FQCN,
  retain exact dual-read, and observe before any exact transactional backfill.
- **Non-goals:** all aggregate/request identity work, monitoring envelope/data
  migration, business-rule changes, aliases/morph maps, and compatibility-model
  duplication.
- **DoD:** old and new history remains visible without duplicate audit events;
  one canonical writer is proven; Monthly/Ramadan and rollback matrices pass;
  backfill counts reconcile if backfill is separately approved.

NO STORED IDENTITY WAS BACKFILLED
NO MODEL NAMESPACE CUTOVER WAS PERFORMED
NO TABLE OR BUSINESS DATA WAS CHANGED
NO WORKFLOW OR MONITORING RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

## Phase 2.8D blocked handover addendum (2026-09-14)

`POSTEXECUTIONVERIFICATION CUTOVER BLOCKED`

C6 remains open. The attempt stopped before source cutover because
`vendor/autoload.php` is absent and no explicitly disposable database is
available. Laravel boot, live identity counts, missing-reference inspection,
and the focused regression matrix remain unexecuted. No Composer/network retry
was made.

Resume **C6 — Phase 2.8D PostExecutionVerification Identity Cutover** only after:

1. locked dependencies and `vendor/autoload.php` are already present;
2. Laravel boot succeeds;
3. the database is explicitly identified and proven disposable;
4. every live inventory query in
   `docs/post-execution-verification-identity-cutover.md` runs and its actual
   counts have no unresolved values or missing references;
5. the focused Monthly, Ramadan, audit, and identity suites pass.

Do not start C6B/backfill or compatibility retirement before C6 succeeds.

NO MODEL NAMESPACE CUTOVER WAS PERFORMED
NO STORED IDENTITY WAS CHANGED
NO BUSINESS DATA WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

`PHASE 2.8D INCOMPLETE`

## Phase 2.9 volunteer-storage handover addendum (2026-09-14)

`PHASE 2.9 COMPLETE`

Decision: `KEEP SEPARATE`.

`monthly_activity_volunteer_needs` remains the canonical zero/one Monthly
volunteer planning summary. `subject_volunteer_requirements` remains the
canonical repeatable planned/actual segmented requirement storage for
Common/Ramadan subjects. They do not represent the same business fact, and
Monthly must not be redirected or dual-written to Common storage for naming
uniformity.

The evidence, column/cardinality/UI/lifecycle matrices, historical-loss analysis,
option comparison, and test gaps are authoritative in
`docs/events-volunteer-storage-reconciliation.md`. No adapter should be built
until a concrete cross-Event report defines a unified read requirement. If that
need arises, the named design-only backlog item is **Phase 2.9A — Volunteer
Requirement Read-Projection Contract Audit**; it must preserve two separate
canonical writers.

C6/Phase 2.8D remains blocked and was not retried.

NO VOLUNTEER DATA WAS MIGRATED
NO VOLUNTEER TABLE WAS RENAMED OR DELETED
NO BUSINESS RULE WAS CHANGED
NO DUAL-WRITE WAS INTRODUCED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

## Phase 2.10 execution-needs handover addendum (2026-09-14)

`PHASE 2.10 COMPLETE`

Decision: `KEEP LEGACY STORAGE`.

Monthly `execution_needs_payload` and `execution_needs_followup` remain the
canonical Monthly transaction documents. `execution_need_types` remains the
shared canonical vocabulary. `subject_execution_needs` remains the canonical
Common/Ramadan planned/actual storage. Do not backfill or dual-write Monthly into
it: the current row schema cannot preserve Monthly structured sections,
availability, decision actor snapshots, scores, rejection/provision outcomes or
historical missing/null/false distinctions.

The authoritative audit is
`docs/monthly-execution-needs-normalization-audit.md`. It includes exact mapping,
historical-shape risks, report/change-history dependencies, MySQL-labelled live
queries and prerequisites for any future richer-schema compatibility redesign.
There is no approved execution-needs implementation slice while those semantics
remain unrepresented.

Phase 2.9 volunteer storage is not revisited. Phase 2.8D was not retried.

NO MONTHLY EXECUTION-NEEDS DATA WAS MIGRATED
NO MONTHLY EXECUTION-NEEDS WRITER WAS CHANGED
NO LEGACY JSON COLUMN WAS REMOVED OR RENAMED
NO DUAL-WRITE WAS INTRODUCED
NO BUSINESS OR WORKFLOW RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED
