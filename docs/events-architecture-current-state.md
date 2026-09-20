# Events architecture — current state

> **Status synchronization (2026-09-20):** this remains the architecture reference. The authoritative continuation/TODO ledger is [`events-ramadan-current-state-audit.md`](events-ramadan-current-state-audit.md), including the completed AgendaEvent cutover, Ramadan reconciliation/dashboard, remaining identity-sensitive models, and consolidated staging debt.

**Status: AUTHORITATIVE_CURRENT**

**Reconciled:** 2026-09-13

**Repository baseline:** commit containing Phase 2.3 (`31a159f`)

**Authority:** this is the single primary Events architecture source of truth. Code and the active migration graph take precedence if this document later drifts.

## 1. Executive finding and authority

The current repository is internally consistent at the executable level: focused Monthly controllers own their methods; Ramadan is an independent aggregate; the four Phase 2.3 abandoned tables and three abandoned models have no runtime references; and Ramadan relations use the four generalized established tables. The migration graph is conceptually ordered for MySQL, but runtime proof is unavailable because `vendor/autoload.php` is absent.

Three intentional transitional areas remain:

1. shared team-member, supply, and verification models retain Monthly-oriented names and `App\Models` namespaces;
2. Monthly execution needs and post-execution evidence remain legacy JSON/Monthly structures;
3. the approved department catalogue is bootstrapped as an explicit dependency of Event categories; guidance, mobilization methods, and real community directories remain intentionally business-managed.

Source hierarchy: code → migrations → relationships → routes → seeders/config → tests → this document → supporting documents → historical plans.

## 2. Current domain boundaries

| Domain | Aggregate / responsibility | Storage ownership | Shared dependencies |
|---|---|---|---|
| Agenda | annual events, audiences, participation, approval, edit/delete requests | Agenda-specific tables | workflow engine, Event lookups; source link for Monthly and Ramadan |
| Monthly Activities | planning, Agenda sync, approvals, execution-needs decisions, post-execution/evaluation, changes, trash, reports | `monthly_activities` and Monthly detail/history tables remain authoritative | target master/pivot; generalized team-member, supply, verification tables; workflow engine |
| Ramadan Iftars | guidance-aware planning, approval, execution, monitoring, closure, approved-plan revisions | `ramadan_iftars`, Ramadan detail tables, and selected Common/generalized tables | Common targeting, teams, volunteers, supplies, execution needs, monitoring, workflow |
| Common inside Events | concepts shared by Event aggregates, not by unrelated application modules | Event lookup and subject-owned/generalized tables | users, branches, application workflow/security |
| Application common | identity, organization, authorization, workflow infrastructure | global application/package tables | all modules |

## 3. Complete phase reconciliation

Allowed final statuses are used exactly as defined by Phase 2.4.

| Phase | Goal | Actually implemented | Superseded / changed portion | Remaining | Final status |
|---|---|---|---|---|---|
| Phase 0 | Baseline and route/behavior safety characterization | Route contracts, Monthly/Agenda behavior inventory, missing report restoration and safety tests | Old report and controller ownership descriptions predate modularization | Runtime suite still needs execution | PARTIALLY_RETAINED |
| Phase 0.1 | Lightweight Events structure/contract seam | Events module folders, subject alias constants and route compatibility groundwork | Any assumption that Phase 1.1 was absent was checkout-specific | None in current code | COMPLETE_CURRENT |
| Phase 1.1 | Lightweight Events foundation | `EventSubjectTypes`, module structure and separation rules | Initial Monthly-only registration expanded to Ramadan | None | COMPLETE_CURRENT |
| Phase 1.2 | Shared-read and Execution Needs discovery | Lookup/relationship inventory and phase gate; no premature write cutover | “needs preparation” status became historical once foundation landed | Monthly JSON migration remains | COMPLETE_BUT_LATER_SUPERSEDED |
| Phase 1.3 | Common targeting foundation | target master applicability, beneficiary segments and subject targeting model | `subject_target_groups` table was abandoned in 2.3 | Final storage is generalized `event_target_group` | REPLACED |
| Phase 1.4 | Ramadan reference foundation | segments, mobilization/monitoring methods, organization/community references | None | Approved mobilization bootstrap policy unresolved | COMPLETE_CURRENT |
| Phase 1.5 | Ramadan core aggregate | independent `ramadan_iftars` with ownership, lifecycle, version fields | None | None | COMPLETE_CURRENT |
| Phase 1.6 | Ramadan detail foundation | attendees, meals/items, gifts, program segments | None | None | COMPLETE_CURRENT |
| Phase 1.7 | Common execution foundation | teams, members, volunteers, supplies | member and supply tables replaced by generalized established tables | Names/models remain transitional | PARTIALLY_RETAINED |
| Phase 1.8 | Common monitoring foundation | methods, reports and verification snapshots | `field_verifications` replaced by generalized established table | Monthly report-envelope migration remains | PARTIALLY_RETAINED |
| Phase 1.9 | Ramadan planning CRUD | validated transactional create/edit and child synchronization | Relations rebound in 2.3, behavior retained | Runtime proof pending | COMPLETE_CURRENT |
| Guidance prerequisite | Versioned guidance acceptance before planning | `event_guidance_versions`, acceptance service/controller and historical linkage | None | Guidance content remains business-managed | COMPLETE_CURRENT |
| Execution Needs normalization | Canonical master, mappings and applicability | canonical flags/mapping/seeder plus `subject_execution_needs` | Older `ExecutionNeedTypeSeeder` removed in Phase 2.5 | Monthly JSON transition remains a separate future slice | PARTIALLY_RETAINED |
| Phase 1.10 | Submission and normal planning approval | independent Ramadan workflow instance, queue and decisions | None | Runtime proof pending | COMPLETE_CURRENT |
| Phase 1.11 | Actual execution flow | start/update actuals/team tasks/needs and audit | member/supply model/table binding changed in 2.3 | Runtime proof pending | COMPLETE_CURRENT |
| Phase 1.12 | Monitoring and workspace | monitoring report CRUD/submission and operational hub | verification table binding changed in 2.3 | Runtime proof pending | COMPLETE_CURRENT |
| Phase 1.13 partial | Execution completion | completion readiness and transition | Folded into completed 1.13 | None | COMPLETE_BUT_LATER_SUPERSEDED |
| Monitoring Review prerequisite | Return/approve monitoring with branch/self / self-review and mismatch-note rules | dedicated review controller/request/service path | None | Runtime proof pending | COMPLETE_CURRENT |
| Localization/UI cleanup | Arabic/English parity and professional Ramadan workspace | translations, navigation and focused views | None | Browser verification pending | COMPLETE_CURRENT |
| Final Closure | Close completed I approved Iftar after authoritative monitoring | closure readiness/model/service/controller and audit | None | Runtime proof pending | COMPLETE_CURRENT |
| Phase 1.13 | Completion plus final closure | integrated completion/monitoring/closure lifecycle | Supersedes partial 1.13 marker | None | COMPLETE_CURRENT |
| Phase 1.14 | Approved-plan change requests/versioning | dedicated request/review workflow, immutable source and draft child deep copy | Child storage rebound in 2.3 | Runtime concurrency proof pending | COMPLETE_CURRENT |
| Phase 2.1 | Monthly controller modularization | route ownership map and focused controllers | Phase 2.1A inheritance seam was temporary | None | COMPLETE_CURRENT |
| Phase 2.1B | Physical extraction and legacy retirement | implementations moved, traits retained, two giant legacy controllers deleted | Replaced inheritance compatibility seam | Trait size is a maintainability concern, not a blocker | COMPLETE_CURRENT |
| Phase 2.2 | Data-model consolidation audit | explicit keep/generalize/merge decisions | Its pre-2.3 inventory is historical where it names abandoned tables as current | Retained as decision record | COMPLETE_BUT_LATER_SUPERSEDED |
| Phase 2.3 | Pre-release schema consolidation | four established tables generalized; duplicate migrations/models removed; Ramadan rebound | Reversed four Phase 1 Common table choices | Runtime `migrate:fresh` proof pending | COMPLETE_CURRENT |
| Phase 2.4 | Full reconciliation | this code-first current-state audit and documentation authority map | N/A | Runtime checks pending due environment | COMPLETE_CURRENT |
| Phase 2.8B | Shared support-model naming consolidation | attachment namespace move; team/supply final models and in-place table renames | Earlier import-only proposal broadened after semantic recheck | Runtime proof remains Phase 2.6 debt | COMPLETE_CURRENT |
| Phase 2.5 | Event reference-data bootstrap reconciliation | deterministic reference orchestrator, canonical need source, idempotency coverage, and removal of superseded need seeder | Replaces the incomplete reference bootstrap documented by 2.4 | Runtime seed execution pending | COMPLETE_CURRENT |

## 4. Final architecture decision matrix

| Concept | Original design | Intermediate design | Current implementation | Final decision | Final table | Final model | Final namespace | Monthly | Ramadan | Agenda | Seeder | Transitional? | Remaining action |
|---|---|---|---|---|---|---|---|:---:|:---:|:---:|---|:---:|---|
| Ramadan aggregate | Reuse/compare Monthly | Independent aggregate | Independent aggregate/version chain | Keep independent | `ramadan_iftars` | `RamadanIftar` | `App\Modules\Events\Models` | No | Yes | optional FK | none | No | none |
| Monthly aggregate | Existing authoritative storage | Kept unchanged during Ramadan | Still authoritative | Keep domain-specific | `monthly_activities` | `MonthlyActivity` | currently `App\Models`; final Events namespace only after identity plan | Yes | No | generated from Agenda | none | Yes | identity-safe namespace decision |
| Target master | Existing Monthly lookup | applicability flags | one shared master | Keep | `target_groups` | `TargetGroup` | currently `App\Models`; final Events | Yes | Yes | indirect | reference | Yes | namespace only |
| Target selections | Monthly pivot + proposed Common table | `subject_target_groups` | generalized old pivot | Keep generalized established table | `event_target_group` | `SubjectTargetGroup` | Events | Yes | Yes | No | none | No | name acceptable |
| Beneficiary segments | none | new Common lookup | retained | Keep justified | `beneficiary_segments` | `BeneficiarySegment` | Events | future | Yes | No | reference | No | none |
| Team headers | no normalized Monthly header | new Common header | retained Common header | Keep justified | `execution_teams` | `ExecutionTeam` | Events | future | Yes | No | none | No | Monthly adapter only if required |
| Team members | old Monthly member rows + new Common rows | duplicate development table abandoned | generalized historical table renamed in place | Final shared member concept | `execution_team_members` | `ExecutionTeamMember` | `App\Modules\Events\Models` | Yes | Yes | No | none | No | runtime verification |
| Volunteer requirements | one-row Monthly summary | repeatable Common rows | both retained due distinct business facts | Keep separate (Phase 2.9) | `monthly_activity_volunteer_needs`; `subject_volunteer_requirements` | corresponding models | both models under Events; storage remains separate | Yes | Yes | No | none | No | optional read projection only when a concrete report requires it |
| Supplies | old Monthly + duplicate Common | duplicate development table abandoned | generalized historical table renamed in place | Final shared Event supply | `event_supplies` | `EventSupply` | `App\Modules\Events\Models` | Yes | Yes | No | none | No | runtime verification |
| Execution Need master | lookup master | canonical flags/mappings | sole master | Keep and generalize in place | `execution_need_types` | `ExecutionNeedType` | `App\Modules\Events\Models` (moved 2.8A) | Yes | Yes | potential | canonical required | Yes | canonical seeder retained; namespace complete |
| Execution Need transactions | Monthly JSON | Common relational table | JSON for Monthly, rows for Ramadan | Keep separate by lifecycle contract (Phase 2.10) | Monthly JSON; `subject_execution_needs` for Common/Ramadan | `MonthlyActivity` casts; `SubjectExecutionNeed` | mixed | Yes | Yes | No | none | No | richer schema/live audit required before reconsideration |
| Monitoring methods | none | Common lookup | retained | Keep justified | `monitoring_methods` | `MonitoringMethod` | Events | future | Yes | No | reference | No | none |
| Monitoring reports | Monthly payload/evaluation/follow-up workflow | Common monitoring envelope | Ramadan Common envelope, Monthly legacy workflow | Keep Monthly separate (Phase 2.11) | `monitoring_reports`; Monthly legacy stores | `MonitoringReport`; Monthly-specific models | mixed | Yes | Yes | No | none | No | no migration approved; verification remains shared |
| Field verification | old Monthly correction rows + new Common | duplicate `field_verifications` | generalized old table | Keep storage; name is semantically broad enough | `post_execution_verifications` | `PostExecutionVerification` | `App\Models` now; Events final | Yes | Yes | No | none | Yes | namespace only; keep table name |
| Guidance | none | Event-versioned guidance | Ramadan uses versioned business content | Keep | `event_guidance_versions` | `EventGuidanceVersion` | Events | No | Yes | No | business-managed | No | none |
| Workflow | existing generic engine | module configuration added | application-wide engine reused | Keep global | workflow tables | `Workflow*` | `App\Models` | Yes | Yes | Yes | required | No | protect stored FQCNs |
| Ramadan plan workflow | proposed dedicated module | seeded five steps | independent per version | Keep | workflow tables | `RamadanIftar` identity | mixed | No | Yes | No | required | No | none |
| Ramadan monitoring review | initially deferred | explicit supervisor review | service-owned report state + action logs | Keep, not a DynamicWorkflow instance | monitoring/action-log tables | `MonitoringReport` | Events | No | Yes | No | none | No | document distinction |
| Change requests | Monthly semantics considered | separate Ramadan request table/workflow | separate domain semantics retained | Keep separate | domain request tables | request models | mixed | Yes | Yes | Yes | workflow required | Yes | namespace identity plan |
| Monthly controllers | giant legacy controllers | focused routes inheriting legacy | physically focused controllers | Keep | N/A | N/A | Events controllers | Yes | No | No | none | No | no refactor required |
| Seeder strategy | multiple optional calls | canonical/reference additions | `DatabaseSeeder` calls Event references, canonical needs, then complete auth/workflows | One authoritative path per master | lookup/workflow/security | models above | mixed | Yes | Yes | Yes | see §16 | No | runtime idempotency proof |

## 5. Fresh-database table inventory

Every table below should exist unless explicitly labelled removed. All active tables were traced to migrations; grouped rows share the same lifecycle/owner.

| Table(s) | Created by | Later altered by | Primary model / namespace | Owner and use | State | Seeder |
|---|---|---|---|---|---|---|
| `agenda_events` | `2024_02_01_010300` | category/ownership/version migrations | `AgendaEvent`, `App\Models` | Agenda; linked by Monthly/Ramadan | final domain table | business rows |
| `agenda_event_targets`, `agenda_approvals`, `agenda_participations`, `agenda_event_partner_departments` | 2024/2026 Agenda migrations | selected indexes/fields | Agenda models, `App\Models` | Agenda only | final domain tables | none |
| `annual_agenda_edit_requests`, `annual_agenda_delete_requests` | `2026_06_12_000001` | none | request models, `App\Models` | Agenda changes | final domain tables | none |
| `monthly_activities` | `2024_02_01_010600` | multiple Monthly/version/evaluation migrations | `MonthlyActivity`, `App\Models` | Monthly aggregate | final, legacy-rich | business rows |
| `monthly_activity_volunteer_needs`, attachments, approvals, sponsors, partners, change logs, followups, evaluation responses, KPIs | respective Monthly migrations | selected later fields | corresponding Monthly models; support models under Events where audited | Monthly | final domain/legacy | mixed |
| `activity_evaluations`, `activity_evaluation_answers`, `evaluation_forms`, `evaluation_questions` | evaluation migrations | question sort-order migration | evaluation models, `App\Models` | Monthly evaluation | final domain | business/reference forms |
| `monthly_plan_edit_requests`, `monthly_plan_delete_requests` | `2026_06_12_000001` | none | request models, `App\Models` | Monthly change/delete | final domain tables | none |
| `event_target_group` | `2026_03_19_120004` | `2026_09_14_000100` | `SubjectTargetGroup`, Events | Monthly + Ramadan targeting | final generalized | none |
| `execution_team_members` | `2024_02_01_010800` as `monthly_activity_team` | `2026_09_14_000100`, renamed by `000200` | `ExecutionTeamMember`, Events | Monthly + Ramadan members | final generalized storage | none |
| `event_supplies` | `2024_02_01_010700` as `monthly_activity_supplies` | `2026_09_14_000100`, renamed by `000200` | `EventSupply`, Events | Monthly + Ramadan supplies | final generalized storage | none |
| `post_execution_verifications` | `2026_07_25_000100` | `2026_09_14_000100` | `PostExecutionVerification`, `App\Models` | Monthly + Ramadan verification | final generalized | none |
| `target_groups` | `2026_03_16_000090` | `2026_09_10_000100` | `TargetGroup`, `App\Models` | Common Events master | final | reference |
| `beneficiary_segments` | `2026_09_10_000200` | none | `BeneficiarySegment`, Events | Common Events | final | reference |
| `execution_teams` | `2026_09_10_001400` | none | `ExecutionTeam`, Events | Common Events | final | none |
| `subject_volunteer_requirements` | `2026_09_10_001600` | none | `SubjectVolunteerRequirement`, Events | Ramadan/Common | final | none |
| `execution_need_types` | `2026_04_26_223000` | `2026_09_11_000300` | `ExecutionNeedType`, Events | Common Events master | final | canonical |
| `subject_execution_needs` | `2026_09_11_000400` | none | `SubjectExecutionNeed`, Events | Ramadan/Common | final | none |
| `monitoring_methods`, `monitoring_reports` | `2026_09_10_000500`, `001800` | none | monitoring models, Events | Ramadan/Common monitoring | final | method reference only |
| `event_guidance_versions` | `2026_09_11_000100` | Ramadan FK migration | `EventGuidanceVersion`, Events | Ramadan guidance | final | do not auto-seed |
| `mobilization_methods`, `community_organizations`, `local_communities` | `2026_09_10_000400`, `000600`, `000700` | none | Events models | Event references/directories | final | business-managed |
| `ramadan_iftars` | `2026_09_10_000800` | guidance FK | `RamadanIftar`, Events | Ramadan aggregate | final | none |
| Ramadan attendee, meal, meal-item, gift, program-segment tables | `2026_09_10_000900`–`001300` | none | Ramadan detail models, Events | Ramadan | final | none |
| `ramadan_iftar_change_requests` | `2026_09_13_000100` | none | `RamadanIftarChangeRequest`, Events | Ramadan revisions | final | none |
| `event_types`, `event_categories`, `event_status_lookups` | respective lookup migrations | none | lookup models, `App\Models` | Agenda/Monthly Events | final | reference |
| `workflows`, `workflow_steps`, `workflow_instances`, `workflow_logs`, `workflow_action_logs` | workflow migrations | selected indexes | `Workflow*`, `App\Models` | application-global | final | required definitions |
| `roles`, `permissions`, package pivots | security migrations | package/application changes | security models | application-global | final | required |
| removed: `subject_target_groups`, `execution_team_members`, `subject_supplies`, `field_verifications` | create migrations removed in 2.3 | N/A | models removed except rebound `SubjectTargetGroup` | none | removed; must not exist | none |

## 6. Table and model naming review

| Current table | Decision | Current concept | Preferred model/name | Reason |
|---|---|---|---|---|
| `event_target_group` | **KEEP NAME** | Event subject-to-target selection | Keep table; `SubjectTargetGroup` is acceptable | Existing name is already Event-generic. Singular pivot naming is conventional enough and a rename adds no semantic value. |
| `execution_team_members` | **FINAL** | shared execution-team member rows plus legacy Monthly ownership | `ExecutionTeamMember` | Renamed in place from the generalized historical table in Phase 2.8B. |
| `event_supplies` | **FINAL** | subject-owned Event supplies plus legacy Monthly columns | `EventSupply` | Renamed in place from the generalized historical table in Phase 2.8B. |
| `post_execution_verifications` | **KEEP NAME** | field verification after execution for Monthly and monitored Ramadan | eventual Events namespace; keep `PostExecutionVerification` | “Post execution” describes both use cases. A rename would be aesthetic and high-blast-radius. |

No rename is **REQUIRED** for correctness today.

## 7. Model architecture

| Model group | Current namespace | Table | Consumers | Stored FQCN / identity risk | Final namespace | Action |
|---|---|---|---|---|---|---|
| `MonthlyActivity`, all Monthly detail/request/evaluation models | `App\Models` | Monthly tables | controllers, services, jobs/reports, workflow entity types | High for aggregate/request identities; low-medium for details | Events | TRANSITIONAL; do not bulk move |
| `AgendaEvent`, Agenda details/requests | `App\Models` | Agenda tables | Agenda controllers/services, workflow, Monthly/Ramadan links | High for aggregate/request workflow/entity strings | Events | TRANSITIONAL |
| `TargetGroup`, `ExecutionNeedType`, Event lookup models | Events | Event lookup tables | Agenda/Monthly/Ramadan | no stored self-FQCN found | Events | MOVED 2.8A |
| `MonthlyActivityTeam` | `App\Models` | `monthly_activity_team` | Monthly + `ExecutionTeam::members` | Low stored FQCN; route bindings use model in Programs controller | Events | RENAME/MOVE later |
| `MonthlyActivitySupply` | `App\Models` | `monthly_activity_supplies` | Monthly + Ramadan | Low stored FQCN; explicit route model binding exists | Events | RENAME/MOVE later |
| `PostExecutionVerification` | `App\Models` | `post_execution_verifications` | evaluation + Ramadan monitoring | Audit logs store its FQCN, so high identity risk | Events | MOVE only with alias/history plan |
| `SubjectTargetGroup` | Events | `event_target_group` | Ramadan and Common tests | No workflow identity; subject alias risk only | Events | KEEP |
| `ExecutionTeam`, volunteer/execution-need models | Events | Common tables | Ramadan | subject alias, not FQCN, is persisted | Events | KEEP |
| `MonitoringReport` | Events | `monitoring_reports` | Ramadan monitoring/closure | workflow action metadata uses IDs; subject alias persisted | Events | KEEP |
| `RamadanIftar`, change request | Events | Ramadan tables | full Ramadan flow | High: workflow `entity_type` uses class FQCN | Events | KEEP |
| Ramadan detail/reference/guidance models | Events | Ramadan/Common tables | Ramadan | generally low; FK IDs dominate | Events | KEEP |
| `Workflow*` | `App\Models` | workflow tables | Events and unrelated application callers | Very high stored entity FQCN risk, but globally owned | `App\Models` | KEEP |
| `User`, `Role`, `Branch`, organization/security | `App\Models`/package | global tables | entire application | global | current | KEEP |

## 8. Controller architecture

### Monthly focused controllers

| Controller | Responsibility | Correct module | Legacy controller dependency | Logic location | Status |
|---|---|:---:|:---:|---|---|
| `MonthlyActivitiesBrowseController` | browse/filter/paginate | Yes | none | controller + shared concern | current |
| `MonthlyActivityCalendarController` | calendar | Yes | none | controller | current |
| `MonthlyActivityPlanningController` | create, Agenda sync, store, edit, update | Yes | none | controller + lifecycle/workflow services | current |
| `MonthlyActivityWorkspaceController` | active/deleted detail workspace | Yes | none | controller + presenters | current |
| `MonthlyActivityLifecycleController` | submit and close | Yes | none | thin controller + lifecycle/workflow | current |
| `MonthlyActivityFeedbackController` | returned and post-execution feedback | Yes | none | controller | current |
| `MonthlyActivityTrashController` | trash, restore, delete request/direct deletion | Yes | none | controller + request workflow | current |
| `MonthlyActivityReportsController` | change-request reporting | Yes | none | controller | current |
| `MonthlyActivityApprovalQueueController` | queue and details JSON | Yes | none | controller + workflow presenter | current |
| `MonthlyActivityApprovalDecisionController` | planning and execution-need decisions | Yes | none | controller + services | current |
| `MonthlyActivityPostExecutionDecisionController` | post-execution decision | Yes | none | controller | current |
| `MonthlyActivityChangeRequestDecisionController` | edit/delete request decisions | Yes | none | controller + request workflow | current |

`MonthlyActivitiesController` and `MonthlyActivitiesApprovalsController` are absent. The two concerns are current shared controller collaborators, not inheritance shims; their size is technical debt but no duplicated controller implementation was found.

### Ramadan controllers

Ramadan controllers are correctly grouped by planning CRUD, workspace, submission, approval queue/decision, execution, monitoring, monitoring review, closure, guidance, and change-request review. They delegate transactions and lifecycle rules to focused services. No superseded table/model reference remains.

### Agenda controllers

Agenda controllers remain in the legacy Web/Agenda area. This is a current boundary, not a Phase 2.4 defect; Agenda redesign was out of scope. They reuse application workflow/change-request services and Agenda-specific models.

## 9. Services, concerns, presenters, and requests

| Artifact | Classification | Finding |
|---|---|---|
| `InteractsWithMonthlyActivities` | CURRENT | shared branch, visibility, lifecycle, JSON normalization and workflow helpers used by focused Monthly controllers |
| `InteractsWithMonthlyActivityApprovals` | CURRENT | shared approval query/decision presentation helpers |
| `MonthlyActivityLifecycleService` | CURRENT | canonical Monthly transition checks/mutations |
| `MonthlyActivityWorkflowService` | TRANSITIONAL | explicitly deprecated compatibility mirror for legacy status fields but still injected by planning; not dead |
| `DynamicWorkflowService` | CURRENT | application-wide workflow engine with configured branch scoping |
| `WorkflowNotificationService` | CURRENT | notification side effects for Agenda/Monthly/Ramadan workflows |
| `PlanChangeRequestWorkflowService` | CURRENT | Monthly/Agenda request workflow; not used for Ramadan revision semantics |
| Monthly/Agenda workflow presenters | CURRENT | view shaping only |
| Ramadan planning/submission/approval/execution/monitoring/change/closure/guidance services | CURRENT | focused transactional owners; no overlap requiring refactor |
| Ramadan Form Requests | CURRENT | planning, execution, monitoring and decisions; authorization also enforced by routes/controllers/services |
| `VerifyPostExecutionRequest` | CURRENT | Monthly verification request and status vocabulary |

## 10. Ramadan end-to-end flow

| Transition | Controller / Request | Service | Writes | Workflow / permission / audit / notification |
|---|---|---|---|---|
| Guidance view/accept | `RamadanGuidanceController` | `RamadanGuidanceAcceptanceService` | session acceptance; guidance reference | create-capable roles; no plan workflow yet |
| Create/edit planning | `RamadanIftarController`; `StoreRamadanIftarRequest` | `RamadanIftarPlanningService` | aggregate, generalized targeting/supplies/team members, meals/gifts/programs, volunteers, needs | create/edit permissions; transaction; guidance required |
| Submit | `RamadanIftarSubmissionController` | `RamadanIftarSubmissionService` | plan status/timestamp + workflow instance/log | `ramadan_iftars`; submit permission; action log + workflow notification |
| Planning approval | queue + decision controllers; `DecideRamadanIftarRequest` | `RamadanIftarApprovalService` + dynamic workflow | workflow/log/status/approved timestamp | five seeded steps; approve permission; notifications |
| Start/update execution | `RamadanIftarExecutionController`; execution request | `RamadanIftarExecutionService` | actual aggregate/detail values, generalized supplies/member tasks, attendees, needs | execute permission; workflow action logs; no planning-history mutation |
| Complete execution | execution controller | execution service | `execution_status=completed` | readiness checks + action log |
| Monitor/save/submit | `RamadanIftarMonitoringController`; monitoring request | `RamadanIftarMonitoringService` | `monitoring_reports`, generalized verifications | monitor permission; monitoring action logs; no DynamicWorkflow instance |
| Review monitoring | `RamadanMonitoringReviewController`; review request | monitoring service | report status | review permission, branch/self-review/mismatch rules, action log |
| Closure | `RamadanIftarClosureController` | `RamadanIftarClosureService` | `closed_at`/status | close permission; authoritative approved report; action log |
| Request approved-plan change | change-request controller/request | `RamadanIftarChangeRequestService` | request + workflow instance/log | dedicated create permission and change-request workflow |
| Approve request/create N+1 | review controller/request | change-request service | approved request + new draft aggregate/deep-copied planning children | dedicated review permission; transaction/locks; revision audit; normal plan workflow starts only on later submit |

The entire Ramadan path uses `event_target_group`, `execution_team_members`, `event_supplies`, and `post_execution_verifications` through current relations. It does not reference the abandoned duplicate tables.

## 11. Monthly end-to-end flow and legacy dependencies

| Flow | Controller | Main collaborators | Authoritative storage | Permission/workflow | Legacy dependency / future work |
|---|---|---|---|---|---|
| Browse | browse controller | dynamic workflow, shared concern | Monthly aggregate/relations | view + branch rules | legacy status fields and JSON filters |
| Calendar | calendar controller | model queries | Monthly aggregate | view/branch | none beyond aggregate |
| Planning/Agenda sync | planning controller | conflict, lifecycle, Monthly workflow/notifications | Monthly aggregate, target pivot, team, supplies, attachments/partners | create/edit roles | execution-needs JSON; deprecated status mirror |
| Workspace | workspace controller | presenter/change-request service | Monthly aggregate/relations | role and branch visibility | legacy JSON/post-execution structures |
| Submit | lifecycle controller | lifecycle + dynamic workflow + notifications | aggregate/workflow tables | Monthly workflow | legacy status columns mirrored |
| Approval queue/decision | queue/decision controllers | dynamic workflow/presenter/lifecycle | workflow + Monthly approval/status fields | approval roles/branch SQL | dual representation of workflow state |
| Execution-needs decisions | approval decision controller | concern/config | `execution_needs_payload` + `execution_needs_followup` | role matrix | future normalized transaction migration |
| Close/execution behavior | lifecycle controller | lifecycle service | Monthly status/execution fields | existing roles | semantics remain Monthly-specific |
| Feedback/post-execution | feedback and post-execution decision controllers | notification/evaluation logic | JSON, followups, verification/evaluation tables | role/branch rules | future monitoring-envelope decision |
| Change requests | trash/planning/change-decision controllers | `PlanChangeRequestWorkflowService` | Monthly edit/delete request tables | existing request sequence | separate from Ramadan by design |
| Trash/restore/delete | trash controller | change-request service | soft deletes/request tables | current roles | none |
| Reports | reports controller and application report controllers | model queries | Monthly aggregate/history | reports permissions | must survive future JSON/model changes |

Already shared: target master/pivot, generalized member and supply tables, execution-need master, application workflow. Not yet shared: Monthly volunteer summary, execution-need transactions, monitoring report envelopes, aggregate/detail models and legacy status/JSON semantics.

## 12. Agenda boundary

Agenda owns `agenda_events`, targets, participations, approvals, partner departments, and Agenda edit/delete request tables. It uses Event type/category/status lookups and the application workflow/change-request infrastructure. Monthly may be generated/synchronized from Agenda; Ramadan has an optional `agenda_event_id`. Agenda target records describe organizational audiences and are not the beneficiary-targeting relation. No Agenda schema/controller redesign is authorized by this audit.

## 13. Definitive Common Events boundary

| Category | Common today? | Monthly | Ramadan | Agenda | Final table/model | Transitional? |
|---|:---:|:---:|:---:|:---:|---|:---:|
| Target master/selection | Yes | Yes | Yes | lookup only | `target_groups`; `event_target_group` / `SubjectTargetGroup` | model namespace only |
| Beneficiary segmentation | Yes inside Events | future | Yes | No | `beneficiary_segments` / `BeneficiarySegment` | No |
| Event lookups | Yes | Yes | selected | Yes | type/category/status masters | namespace only |
| Execution team headers | Yes | not normalized | Yes | No | `execution_teams` / `ExecutionTeam` | Monthly adoption deferred |
| Team members | Yes | Yes | Yes | No | `execution_team_members` / `ExecutionTeamMember` | final |
| Volunteer requirements | Partly | separate summary | Yes | No | two tables/models | semantic decision pending |
| Supplies | Yes | Yes | Yes | No | `event_supplies` / `EventSupply` | final |
| Execution Need master | Yes | Yes | Yes | No | `execution_need_types` / `ExecutionNeedType` | namespace/bootstrap cleanup |
| Execution Need transactions | Yes for new Event flows | JSON | Yes | No | `subject_execution_needs` | Monthly migration pending |
| Monitoring methods/reports | Yes for new Event flows | legacy | Yes | No | `monitoring_methods`, `monitoring_reports` | Monthly migration pending |
| Field verification | Yes | Yes | Yes | No | `post_execution_verifications` / `PostExecutionVerification` | namespace only |
| Guidance | Reusable Events structure, currently Ramadan-only | No | Yes | No | `event_guidance_versions` / `EventGuidanceVersion` | No hypothetical expansion |
| Workflow integration | Application-common, not Events-owned | Yes | Yes | Yes | workflow tables/services | No |

## 14. Workflow identity audit

| Flow | Workflow module/code | Stored entity identity | Steps/decision owner | Risk / final identity |
|---|---|---|---|---|
| Agenda planning | `agenda` / `agenda_approval` | `App\Models\AgendaEvent` FQCN in instances | Relations Officer → Relations Manager → Executive Manager | High namespace-move risk; retain stored identity |
| Monthly planning | `monthly_activities` / `monthly_activity_approval` | `App\Modules\Events\Models\MonthlyActivity` canonical FQCN; legacy identity read-compatible | Relations Officer; conditional Supervisor/Coordinator; Relations Manager; conditional Executive | High namespace-move risk; legacy status mirror remains |
| Monthly edit/delete requests | request tables + `PlanChangeRequestWorkflowService` | request/entity type strings | existing Monthly/Agenda semantics | High; not interchangeable with Ramadan |
| Ramadan planning | `ramadan_iftars` / `ramadan_iftar_approval` | `App\Modules\Events\Models\RamadanIftar` | five seeded steps | Final identity; per-version instance |
| Ramadan monitoring | no DynamicWorkflow definition | report ID in action-log metadata | Follow-up submit; Supervisor review | Do not invent workflow instance; report status is authoritative |
| Ramadan change request | `ramadan_iftar_change_requests` / `ramadan_iftar_change_request_approval` | `RamadanIftarChangeRequest` FQCN | Supervisor → Coordinator → Relations Manager → Executive | Final identity; separate from resulting plan approval |

`config/workflows.php` scopes only branch-local roles for Monthly, Ramadan planning, and Ramadan change requests. Any model move involving stored FQCNs requires aliases or a transactional identity migration; this audit makes no such change.

## 15. Permissions and roles

| Family | Seeded permissions | Route/service alignment | Finding |
|---|---|---|---|
| Agenda | view/create/update/delete/approve/participation.update | routes use roles or matching permissions; approval routes rely on authenticated group plus controller/workflow checks | No missing seeded permission found; middleware is heterogeneous historical behavior |
| Monthly | view/create/edit/delete/approve/view_other_branches | routes preserve role-heavy production contract; controllers/services enforce branch/workflow rules | No new permissions needed; submit/close have no dedicated permission by design |
| Ramadan | view/create/edit/submit/approve/execute/monitor/monitor.review/close/change_request.create/change_request.review | route middleware and service checks match seeded names | No missing or unused Ramadan permission found |
| Common administration | none dedicated | lookups are consumed through existing administration/Events flows | No orphan permission found |

Role assignments match intended actors: Relations Officer requests Ramadan changes; Supervisor/Coordinator/Relations Manager/Executive review workflow stages; Follow-up Officer executes/monitors; Supervisor reviews monitoring/closes; super-admin overrides are explicit. No safe permission correction was required.

## 16. Seeder/bootstrap map

| Seeder/data | Called from | Idempotent | Classification | Order/dependency/finding |
|---|---|:---:|---|---|
| `EventReferenceDataSeeder` | first active `DatabaseSeeder` call | delegates idempotent seeders | REFERENCE_DATA_SEED | approved departments → types → targets → segments → monitoring methods → statuses → department-owned categories |
| `CompleteRolePermissionSeeder` | third active `DatabaseSeeder` call | delegated repeatable seeders | REQUIRED_PRODUCTION_SEED | calls permissions, roles, `WorkflowSeeder`, and evaluation access in safe order |
| `CanonicalExecutionNeedTypeSeeder` | active `DatabaseSeeder` | Yes (`updateOrCreate`) | REQUIRED_PRODUCTION_SEED | authoritative execution-need bootstrap |
| `ExecutionNeedTypeSeeder` | removed in Phase 2.5 | N/A | SUPERSEDED | canonical definitions fully replace its old aliases/catalogue |
| `WorkflowSeeder` | called by `CompleteRolePermissionSeeder` | Yes, definition-driven | REQUIRED_PRODUCTION_SEED | safely follows permission/role creation inside the complete bootstrap |
| `RolesSeeder`, `RolePermissionSeeder` | via complete bootstrap/direct deployment conventions | repeatable | REQUIRED_PRODUCTION_SEED | must precede workflow seeding |
| `TargetGroupSeeder` | `EventReferenceDataSeeder` | Yes (`updateOrCreate` by name) | REFERENCE_DATA_SEED | active; schema has no stable code, so approved name is the available business key |
| `BeneficiarySegmentSeeder` | `EventReferenceDataSeeder` | Yes (`updateOrCreate` by code) | REFERENCE_DATA_SEED | active before planning selections |
| `MonitoringMethodSeeder` | `EventReferenceDataSeeder` | Yes (`updateOrCreate` by code) | REFERENCE_DATA_SEED | active; provides `cameras` and `field_visit` required by monitoring |
| Event type/status seeders | `EventReferenceDataSeeder` | `updateOrCreate` | REFERENCE_DATA_SEED | active and independent of organization rows |
| `DepartmentSeeder` | first in `EventReferenceDataSeeder` | Yes (`updateOrCreate`) | REQUIRED_PRODUCTION_SEED | approved dependency for department-owned Event categories |
| `EventCategorySeeder` | last in `EventReferenceDataSeeder` | Yes (`updateOrCreate`) | REFERENCE_DATA_SEED | active after its approved department catalogue dependency |
| `event_guidance_versions` | application publishing only | N/A | BUSINESS_MANAGED_DATA | never fake-seed approved guidance |
| mobilization/community/local data | business UI/data | N/A | BUSINESS_MANAGED_DATA | no fabricated production values |
| Agenda/Monthly/workflow/post-execution showcase seeders | explicit/manual only | fixture-oriented | TEST_DEV_FIXTURE | never production bootstrap |

No seeder references an abandoned Phase 2.3 table. The authoritative chain is now `EventReferenceDataSeeder` → `CanonicalExecutionNeedTypeSeeder` → `CompleteRolePermissionSeeder`; the latter internally guarantees permission → role → workflow ordering. No hard-coded numeric reference ID was found in Event runtime/bootstrap code.

Automatic production-safe bootstrap includes the approved department catalogue, Event types, target groups, beneficiary segments, monitoring methods, Event statuses and categories, canonical execution needs, permissions, roles, workflows, and evaluation access. Manual business setup must publish a current guidance version and create approved mobilization methods, real community organizations, and local communities. Showcase seeders remain manual test/development fixtures.

## 17. Migration graph audit

Chronological dependency summary:

1. 2024 establishes Agenda, Monthly aggregate, volunteer, supply, team, attachments and approvals.
2. early 2026 adds Event/Monthly lookups, histories, evaluation and target pivot.
3. March/April establishes generic workflow tables, Event statuses and execution-need master.
4. June/July adds version/change requests and post-execution verification/evaluation.
5. September 10 creates justified Ramadan/reference/Common tables but no abandoned duplicate table.
6. September 11 adds guidance linkage, execution-need canonical flags and transactions.
7. September 13 adds Ramadan change requests.
8. September 14 generalizes `event_target_group`, `monthly_activity_team`, `monthly_activity_supplies`, and `post_execution_verifications` after all referenced prerequisite tables exist.

Static dependency review found no active migration referencing the four removed tables. Phase 2.3 depends on `beneficiary_segments`, `execution_teams`, `monitoring_reports`, users, and the four established tables; all are created earlier. MySQL and PostgreSQL nullability paths are explicit. The fallback `change()` path requires DBAL on databases whose grammar cannot alter nullability, so non-MySQL test portability is a **medium** fresh-install risk. Rollback to pre-generalization cannot succeed if Common rows with null Monthly ownership remain; that is a safe-data constraint requiring an explicit rollback policy, not silent deletion.

`migrate:fresh` is conceptually coherent, but is **not runtime-proven** in this environment.

## 18. Routes audit

Static route parsing confirms:

- Monthly public names/URIs remain routed to focused Events controllers.
- No route references either deleted legacy Monthly controller.
- Ramadan routes cover index/show, guidance, planning create/edit, submit, planning approvals, execution start/update/complete, monitoring create/edit/submit, monitoring review, closure, and change-request review.
- Agenda routes remain on Agenda controllers.
- Repeated local names such as `index`, `show`, and `decision` occur under distinct name prefixes; they are not duplicate final route names.
- No abandoned model/table appears in route definitions.

Because Laravel cannot boot without vendor dependencies, runtime route-name collision proof remains pending.

## 19. Test architecture

| Test group | Classification | Current meaning |
|---|---|---|
| `EventsPhaseZeroRouteContractTest`, Monthly controller route contract | CURRENT CONTRACT / HISTORICAL CHARACTERIZATION | public route stability and legacy retirement |
| Monthly branch, pagination, lifecycle, execution, mutation, production-readiness tests | CURRENT FEATURE TEST | preserve existing Monthly behavior and branch SQL |
| Common targeting/execution/monitoring/needs tests | CURRENT FEATURE TEST | now exercise final generalized bindings and justified tables |
| `EventsPreReleaseSchemaConsolidationTest` | STATIC ARCHITECTURE TEST + feature schema test | asserts abandoned tables absent, generalized columns present, Monthly IDs/relations preserved |
| Ramadan planning/approval/execution/monitoring/review/closure/change tests | CURRENT FEATURE TEST | full independent lifecycle and versioning |
| Event context/subject/canonical mapping/workflow config/localization tests | STATIC ARCHITECTURE / CURRENT CONTRACT | aliases, mappings, config and language parity |
| older assertions naming removed tables | SUPERSEDED | updated in Phase 2.3; none remain except intentional negative assertion |
| dead tests | DEAD | none conclusively found |

Missing runtime coverage is environmental rather than absent test files: migration execution, route boot, database behavior, concurrency, and browser rendering have not run in this checkout.

## 20. Reversed and superseded decisions

| Original decision | Why originally chosen | Why changed | Final decision | Current evidence |
|---|---|---|---|---|
| create `subject_target_groups` | clean polymorphic Ramadan-first targeting | duplicated an established relational pivot and would discard Monthly IDs | generalize `event_target_group`; remove new table | rebound `SubjectTargetGroup`, 09-14 migration, negative schema test |
| create `execution_team_members` | clean child table for new team headers | duplicated historical member rows | generalize `monthly_activity_team`; retain `execution_teams` | `ExecutionTeam::members()` uses `MonthlyActivityTeam` |
| create `subject_supplies` | generic subject-owned supply schema | duplicated established Monthly supply rows | generalize `monthly_activity_supplies` | Ramadan relation uses `MonthlyActivitySupply` |
| create `field_verifications` | report-owned monitoring snapshots | duplicated field-verification concept | generalize `post_execution_verifications` | `MonitoringReport::verifications()` uses `PostExecutionVerification` |
| focused Monthly controllers inheriting legacy controllers | low-risk initial route cutover | implementation remained physically centralized | physically extract and delete legacy controllers | no legacy files/references; route contract test |
| Monthly workflow service as primary lifecycle owner | compatibility during dynamic workflow rollout | dynamic workflow/lifecycle services became authoritative | retain deprecated mirror only while planning writes legacy statuses | deprecation annotation + active injections |
| old execution-needs catalogue seeder | initial need types | canonical mapping/applicability became authoritative | `CanonicalExecutionNeedTypeSeeder` only active source | `DatabaseSeeder` call order |
| monitoring review initially deferred | actor/rules unresolved | supervisor review and mismatch rules later confirmed | report-status review service, not separate workflow engine | review routes/service/tests |
| execution completion as partial 1.13 | closure prerequisite unfinished | monitoring review and closure completed | integrated Phase 1.13 lifecycle | closure model/service/routes/tests |

## 21. Artifact and documentation status

### Artifact status

| Artifact | Type | Current? | Historical? | Removed? | Replacement | Action needed? |
|---|---|:---:|:---:|:---:|---|---|
| focused Monthly controllers | controllers | Yes | No | No | N/A | none |
| legacy Monthly giant controllers | controllers | No | Yes | Yes | focused controllers/concerns | do not recreate |
| Ramadan controllers/services/requests | runtime | Yes | No | No | N/A | runtime verification |
| four generalized established tables | schema | Yes | Yes | No | N/A | naming decisions above |
| four abandoned Common tables | schema | No | Yes | Yes | generalized tables | do not recreate |
| removed Common member/supply/verification models | models | No | Yes | Yes | established models | do not restore |
| `SubjectTargetGroup` | model | Yes | No | No | rebound to pivot | keep |
| Monthly-named shared models | models | Yes | Yes | No | possible generic names | transitional |
| workflow/config/services | infrastructure | Yes | No | No | N/A | preserve identities |
| `ExecutionNeedTypeSeeder` | seeder | No | Yes | Yes | canonical seeder | none; do not restore |
| this document | architecture | Yes | No | No | primary authority | maintain with changes |

### Documentation authority

| Document | Status | Use |
|---|---|---|
| `events-architecture-current-state.md` | AUTHORITATIVE_CURRENT | single primary source |
| `events-data-model-consolidation-audit.md` | CURRENT_SUPPORTING | Phase 2.2 decisions plus Phase 2.3 outcome; earlier inventory is historical |
| `monthly-activities-controller-refactor.md` | CURRENT_SUPPORTING | final physical controller ownership |
| `events-common-targeting-foundation.md`, `events-common-execution-foundation.md`, `events-common-monitoring-foundation.md` | CURRENT_SUPPORTING | focused foundations updated for 2.3 |
| Ramadan flow/closure/versioning/guidance documents | CURRENT_SUPPORTING | focused behavioral explanations; code remains authoritative |
| `ramadan-iftars-master-todo.md` | HISTORICAL_RECORD | phase handover through Ramadan 1.14 plus 2.3 note; header phase is stale |
| `ramadan-iftars-data-design-ar.md` | SUPERSEDED | original design; explicitly names four abandoned tables |
| `events-phase-1-2-architecture-review.md` | HISTORICAL_RECORD | checkout-specific early architecture review |
| `events-refactor-baseline.md` | HISTORICAL_RECORD | Phase 0 baseline, including now-removed delegation |
| `events-execution-needs-phase-gate.md`, `events-execution-needs-normalization.md` | CURRENT_SUPPORTING/HISTORICAL_RECORD | canonical decision history; current implementation is code + this document |
| `project-restructure-todo.md`, `monthly-activities-execution-needs-todo.md` | HISTORICAL_RECORD | backlog context, not current authority |
| remaining Ramadan foundation/detail documents | CURRENT_SUPPORTING | narrowly scoped implementation records |

## 22. Dead and leftover search

Executable search findings:

- abandoned table names: only the intentional negative schema assertion;
- removed model class names: no executable references;
- deleted legacy controller names: only negative tests and the unrelated `StaffMonthlyActivitiesController` substring;
- `legacy`: expected compatibility paths in Monthly workflow/status code;
- `deprecated`: `MonthlyActivityWorkflowService`, still used and therefore transitional rather than dead;
- `TODO`/`FIXME`/`temporary`: no actionable Events runtime marker found;
- dead models/services/controllers/traits/imports: none conclusively established by static use search.

Historical documents retain old names deliberately and now point readers to this document through status headers.

## 23. Remaining risks and ordered work

| Priority | Risk | Evidence | Safe next action |
|---|---|---|---|
| HIGH | Runtime validation gap for the complete branch | vendor missing; no migration/test/route boot | restore dependencies and execute a targeted integration gate before any namespace/table rename |
| HIGH | Stored FQCN identity for Monthly/Agenda models and `PostExecutionVerification` audit logs | workflow/request/action-log entity types | inventory live distinct values and define alias/migration policy before moving models |
| HIGH | Monthly JSON execution-needs/post-execution migration | authoritative JSON with code/history semantics | separate reconciliation/backfill design after runtime baseline |
| MEDIUM | Non-MySQL nullability migration fallback and rollback constraints | Phase 2.3 migration uses fallback `change()`; Common rows cannot down-migrate losslessly | run MySQL fresh/upgrade/rollback-forward tests; document supported rollback |
| MEDIUM | Runtime bootstrap proof | reference and auth/workflow chains are statically deterministic but vendor is absent | run fresh seed twice and assert stable counts |
| MEDIUM | Shared model/table names | Monthly prefixes now serve Ramadan | defer rename until runtime baseline and identity/raw-reference map |
| LOW | Large Monthly concern traits | shared and actively used, no duplicate implementation | revisit only with concrete maintenance need |
| LOW | Historical documentation contains abandoned names | status-labelled history | retain; do not rewrite history |

Ordered future sequence:

1. **Events runtime integration gate**: restore dependencies; run `migrate:fresh`, Phase 2.3 schema tests, Monthly/Ramadan/Agenda workflow suites, route boot, and migration upgrade rehearsal. No architecture change.
2. **Stored identity inventory**: inspect real `entity_type`/audit/request values and decide alias policy.
3. **Shared model naming/namespace slice**: only after steps 1–2; consider `ExecutionTeamMember` and `EventSupply`, while retaining `post_execution_verifications` table name.
4. **Monthly execution-needs migration design and implementation**.
5. **Monthly monitoring-envelope migration design and implementation**.
6. **Volunteer semantics decision**.
7. **Legacy compatibility cleanup**, only after multiple verified cutovers.

## 24. Reconciliation status

`PHASE 2.4 COMPLETE`

## 25. Reference-data bootstrap status

`PHASE 2.5 COMPLETE`

The default bootstrap has one explicit path for stable Event reference data, one
canonical execution-need source, and one existing aggregate for authorization
and workflow definitions. Runtime execution remains a deployment gate rather
than an unverified claim because Composer dependencies are unavailable in this
environment.

## 26. Phase 2.6 runtime verification

`PHASE 2.6 INCOMPLETE`

On 2026-09-13, PHP 8.3.31-dev and Composer 2.9.7 successfully validated the
project and all locked platform requirements. The lock-authoritative Composer
install could not restore dependencies: GitHub package downloads repeatedly
failed with cURL error 56, `CONNECT tunnel failed, response 403`. The autoloader
remained absent, so Laravel boot, route boot, migrations, seed/idempotency,
database schema inspection, feature tests, and browser/RTL smoke tests could not
run. No disposable database was configured, and no destructive database command
was attempted.

No runtime fix or architecture change was made. The detailed command results,
failure ledger, unexecuted test matrix, rollback constraint, and exact next
verification slice are recorded in
[`events-runtime-verification-report.md`](events-runtime-verification-report.md).

The Phase 2.6 resume attempt on the same date again began with
`VENDOR_MISSING`. A fresh lock-authoritative install under PHP 8.3 again failed
on unrelated GitHub distributions with cURL error 56 and proxy response 403.
Per the gate instructions, verification stopped at dependency restoration and
the incomplete status is unchanged.

## 27. Stored identity and model namespace readiness

`PHASE 2.7 COMPLETE`

The stored-identity audit confirms that folder ownership cannot drive aggregate
moves. `MonthlyActivity`, `AgendaEvent`, the four Monthly/Agenda change-request
models, `RamadanIftar`, and `RamadanIftarChangeRequest` are persisted workflow
identities. Aggregate FQCNs also occur in workflow action logs, audit logs, and
notification metadata. A direct class move would orphan history or create a
second workflow identity for the same row.

Common Event detail relations instead use the stable aliases
`monthly_activity` and `ramadan_iftar`; there is no unrestricted global morph
map. The repository should retain its flat `App\Modules\Events\Models`
destination rather than introduce domain subnamespaces. Low-risk catalogue and
supporting models may move only after runtime verification; aggregates remain a
do-not-move-yet group pending explicit dual-identity compatibility and stored
value migration.

The detailed inventory, identity/workflow/polymorphic maps, raw-reference
counts, naming decisions, compatibility requirements, tests, and staged future
sequence are authoritative in
[`events-model-identity-and-namespace-audit.md`](events-model-identity-and-namespace-audit.md).

Runtime debt remains explicit:

```text
PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED
```

Current branch handover: [`events-branch-handover.md`](events-branch-handover.md).

## 28. Low-risk Events support model namespace consolidation

`PHASE 2.8A COMPLETE`

All fourteen Phase 2.7 LOW-risk models moved from `App\Models` into the existing
flat `App\Modules\Events\Models` namespace: three Agenda support models, six
Monthly support models, and five Event catalogue models. Their names, inferred
tables, relationships, constants, scopes, codes, and behavior were preserved.
All active PHP imports and aggregate relationship targets now use the final
classes; no old model file, compatibility wrapper, `class_alias`, or global
morph map remains.

The pre-move and post-move identity scans found none of these support models in
workflow, action-log, audit-log, request, notification, or correspondence FQCN
writers. No model was deferred from the approved group. No schema, stored value,
route definition, workflow, permission, controller ownership, or business logic
changed.

Identity-sensitive aggregates and request models remain in `App\Models`, as do
the medium-risk route-bound attachment/team/supply models,
`PostExecutionVerification`, and the volunteer-semantic hold. The exact moved
and remaining lists, factory finding, and next batch are recorded in the Phase
2.8A outcome section of
[`events-model-identity-and-namespace-audit.md`](events-model-identity-and-namespace-audit.md).

Runtime debt is unchanged:

```text
PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED
```

## 28. Phase 2.8B shared support-model finalization (2026-09-14)

`PHASE 2.8B COMPLETE`

The semantic recheck confirmed three different outcomes:

- `MonthlyActivityAttachment` remains a Monthly-only concept. It moved from
  `App\\Models` to `App\\Modules\\Events\\Models` without changing its class
  name, `monthly_activity_attachments` table, ownership, or route parameters.
  No Ramadan, Agenda, or Common Event attachment relation consumes this model.
- the former `MonthlyActivityTeam` is a shared member row owned either by the
  legacy `monthly_activity_id` or a Common `execution_team_id`. Its final model
  is `ExecutionTeamMember`, and the generalized historical
  `monthly_activity_team` table is renamed in place to
  `execution_team_members`.
- the former `MonthlyActivitySupply` is subject-owned shared Event storage used
  by Monthly and Ramadan. Its final model is `EventSupply`, and the generalized
  historical `monthly_activity_supplies` table is renamed in place to
  `event_supplies`.

Migration `2026_09_14_000200_finalize_shared_event_support_table_names.php`
uses only `Schema::rename` in `up()` and reverse renames in `down()`. It creates
no table and copies no row. The outbound foreign keys and indexes belong to the
renamed tables and remain attached during supported MySQL/MariaDB, PostgreSQL,
and SQLite table renames; no table references either support table's primary
key, so no inbound constraint needs recreation. Historical constraint/index
names may retain their origin names and are intentionally not churned.

The original development-only `execution_team_members` table from Phase 1.x
was abandoned in Phase 2.3.

The current `execution_team_members` name is the renamed and generalized
historical `monthly_activity_team` table.

Historical rows and IDs were preserved.

`event_supplies` is the renamed generalized historical
`monthly_activity_supplies` table, not a newly created replacement table.
Stable `monthly_activity` and `ramadan_iftar` subject aliases and
`subject_type`/`subject_id` semantics are unchanged. Monthly route parameter
names (`monthlyActivityTeam`, `monthlyActivitySupply`, and
`monthlyActivityAttachment`) also remain unchanged.

The runtime matrix still requires `migrate:fresh`, rollback/reapply, the focused
Events/Monthly/Ramadan suites, the complete suite, reference bootstrap
idempotency, and browser/API workflow verification in a dependency-complete
environment.

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

## 29. Phase 2.8C PostExecutionVerification identity compatibility (2026-09-14)

`PHASE 2.8C COMPLETE`

`App\Models\PostExecutionVerification` and `post_execution_verifications`
remain unchanged. Repository code proves one self-identity writer:
`ActivityEvaluationService::verify()` writes the model's full FQCN
to the caller-supplied string column `audit_logs.entity_type`. Audit readers are
aggregate/display queries; unlike `workflow_instances`, no `audit_logs` reader
dynamically resolves the value as a class and no verification identity is stored
in workflow/action logs.

A focused `PostExecutionVerificationIdentity` support class now defines the
legacy identity, intended future canonical identity, exact accepted read set,
and the current legacy write identity. The selected strategy is staged dual-read
followed by a canonical writer switch and optional exact transactional backfill;
no compatibility model, stable-alias framework, morph map, or arbitrary class
resolver is introduced. The complete consumer/writer/reader maps, live queries,
backfill, rollback, deployment order, retirement gates, and runtime tests are in
`docs/post-execution-verification-identity-cutover.md`.

The compatibility design is complete, but live distinct-value inventory and
Phase 2.6 runtime proof remain mandatory before the cutover:

`POSTEXECUTIONVERIFICATION CUTOVER NOT READY`

The dedicated future slice is **PHASE 2.8D — POSTEXECUTIONVERIFICATION IDENTITY
CUTOVER**. It is not implemented here.

NO STORED IDENTITY WAS BACKFILLED
NO MODEL NAMESPACE CUTOVER WAS PERFORMED
NO TABLE OR BUSINESS DATA WAS CHANGED
NO WORKFLOW OR MONITORING RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

## 30. Phase 2.8D gated cutover attempt (2026-09-14)

`POSTEXECUTIONVERIFICATION CUTOVER BLOCKED`

Phase 2.8D stopped at its mandatory runtime gate. `vendor/autoload.php` is
absent, Laravel therefore cannot be booted, and no explicitly disposable
database configuration or SQLite database was available. The required live
`audit_logs.entity_type` inventory and missing-reference query were not run, so
no live counts are claimed.

No model/import/view/relationship change was made, the audit writer remains on
`PostExecutionVerificationIdentity::LEGACY`, and no backfill was performed.
The exact blocking evidence and resume requirements are recorded in
`docs/post-execution-verification-identity-cutover.md` and
`docs/events-runtime-verification-report.md`.

NO MODEL NAMESPACE CUTOVER WAS PERFORMED
NO STORED IDENTITY WAS CHANGED
NO BUSINESS DATA WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

`PHASE 2.8D INCOMPLETE`

## 31. Phase 2.9 volunteer storage semantic reconciliation (2026-09-14)

`PHASE 2.9 COMPLETE`

The code-first audit concludes `KEEP SEPARATE`. The tables represent related but
different facts:

- `monthly_activity_volunteer_needs` is an optional zero/one Monthly planning
  summary with aggregate count, textual age range, aggregate gender, short need,
  and task description. Its owner FK is unique, and it has no actual count,
  segment FK, or operational status.
- `subject_volunteer_requirements` is a zero/many Event-subject line collection,
  currently used by Ramadan. Each row may carry beneficiary segment and gender,
  has planned/actual counts and status, feeds monitoring, and is plan-version
  copied without execution actuals.

A migration would be lossy: Monthly age/need fields have no destination; Common
segment/actual/status meanings have no Monthly source; summary-to-line mapping
would invent segmentation/status semantics; and legacy duplicate count/boolean
fields require live conflict analysis. No unified consumer presently justifies
an adapter. The full matrix, lifecycle/UI/report/workflow audit, options, test
gaps, and guarded future read-projection concept are in
`docs/events-volunteer-storage-reconciliation.md`.

NO VOLUNTEER DATA WAS MIGRATED
NO VOLUNTEER TABLE WAS RENAMED OR DELETED
NO BUSINESS RULE WAS CHANGED
NO DUAL-WRITE WAS INTRODUCED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

## 32. Phase 2.10 Monthly execution-needs normalization readiness (2026-09-14)

`PHASE 2.10 COMPLETE`

Decision: `KEEP LEGACY STORAGE`.

The canonical type vocabulary is shared, but the transaction contracts are not
equivalent. Monthly `execution_needs_payload` is a versioned structured planning
document combining enabled sources, center availability, registry entries and
rich section-specific fields. `execution_needs_followup` separately combines
role/name decision snapshots, secured/not-secured decisions, reasons, scores,
and provided/not-provided post-execution evidence. Current
`subject_execution_needs` supplies only required, planned text,
pending/completed, actual text, and completion time.

Flattening Monthly into Common rows would lose structured fields, availability,
actors, scores and typed outcomes; fabricate timestamps/statuses; mishandle
missing/null/false values; and make the combined `certificates_thanks` follow-up
decision ambiguous across two canonical types. Monthly JSON and Common/Ramadan
rows therefore remain separate authoritative transaction storage. The full
contract/mapping/status/UI/workflow/report audit, engine-labelled live queries,
options and future redesign prerequisites are in
`docs/monthly-execution-needs-normalization-audit.md`.

NO MONTHLY EXECUTION-NEEDS DATA WAS MIGRATED
NO MONTHLY EXECUTION-NEEDS WRITER WAS CHANGED
NO LEGACY JSON COLUMN WAS REMOVED OR RENAMED
NO DUAL-WRITE WAS INTRODUCED
NO BUSINESS OR WORKFLOW RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

## 33. Phase 2.11 Monthly monitoring normalization readiness (2026-09-14)

`PHASE 2.11 COMPLETE`

Decision: `KEEP MONTHLY POST-EXECUTION SEPARATE`.

The audit confirms that `monitoring_reports` is a Common/Ramadan monitoring
envelope, not a generic container for every post-execution fact. Monthly's
versioned `post_execution_payload`, two distinct evaluation stores, append-only
follow-up remarks, approval lifecycle, and historical action/report records have
different actors, cardinalities and purposes. They cannot be moved into a
monitoring envelope without semantic loss or workflow redesign.

`post_execution_verifications` remains intentionally shared but dual-mode:
Monthly verifies flattened payload fields with original/corrected values, while
Ramadan verifies planned/actual monitoring candidates owned by a monitoring
report. No new normalization is approved. The complete storage, lifecycle,
mapping, actor, closure, report, history and option audit is in
`docs/monthly-monitoring-normalization-audit.md`.

Any future implementation that changes the verification model namespace depends
on the live runtime gate in Phase 2.8D. Phase 2.11 neither retries nor bypasses
that gate.

NO MONTHLY MONITORING OR POST-EXECUTION DATA WAS MIGRATED
NO MONTHLY EVALUATION OR FOLLOW-UP SEMANTICS WERE CHANGED
NO MONITORING WRITER WAS CHANGED
NO LEGACY STORAGE WAS REMOVED
NO DUAL-WRITE WAS INTRODUCED
NO BUSINESS OR WORKFLOW RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

## 34. Phase 2.12 Monthly volunteer support-model move (2026-09-14)

`PHASE 2.12 COMPLETE`

`MonthlyActivityVolunteerNeed` moved from `App\Models` to
`App\Modules\Events\Models`. Its class name, fillable fields, `belongsTo`
relationship, `HasFactory` behavior, and `monthly_activity_volunteer_needs`
table convention are unchanged. `MonthlyActivity::volunteerNeed()` remains the
same `hasOne` relationship and now imports the Events-owned support model.

The pre-move identity and route audit found no stored self-FQCN, audit/workflow
identity writer, explicit route binding, controller signature, serialization
contract, factory, `newFactory()` override, seeder import, Blade FQCN, or test
import for the model. The old production model file was removed; no compatibility
wrapper or migration was added.

Phase 2.9 remains authoritative: this namespace move does not merge the
Monthly-only zero/one planning summary with repeatable Common/Ramadan
`SubjectVolunteerRequirement` storage.

NO VOLUNTEER TABLE WAS RENAMED
NO VOLUNTEER DATA WAS MIGRATED
NO VOLUNTEER CARDINALITY OR BUSINESS SEMANTICS WERE CHANGED
NO DUAL-WRITE WAS INTRODUCED
NO STORED WORKFLOW/AUDIT IDENTITY WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

## 35. Phase 2.13 Monthly/Agenda request identity compatibility audit (2026-09-14)

`PHASE 2.13 COMPLETE`

The four request models remain under `App\Models`. Their own FQCNs are stored in
`workflow_instances.entity_type` by `DynamicWorkflowService::forModel()` through
`get_class()`, dynamically resolved by `resolveEntity()`, matched exactly by
the request models' `morphOne` relationships, and included in exact-string
report filters. A direct namespace move would hide historical workflows and may
create a second logical workflow because the unique key includes `entity_type`.

Request-row `entity_type` has a separate contract: it stores the aggregate FQCN
(`App\Models\MonthlyActivity` or `App\Models\AgendaEvent`), not the request
class. Request notifications and workflow action logs also retain aggregate
identity; `workflow_logs` stores only FKs and no model identity. No audit-log or
queue serialization of the four request models was found.

The selected future strategy is a focused exact dual-read identity map, legacy
writer first, followed by a separately gated **Monthly pair first** namespace
cutover. Aggregate identities remain untouched. Full evidence, matrices, live
inventory/orphan/duplicate queries, backfill, rollback, deployment order, and
runtime tests are in `docs/events-request-model-identity-cutover.md`.

`ExecutionNeedType` is already under `App\Modules\Events\Models` from Phase
2.8A and is not a remaining namespace candidate.

NO REQUEST MODEL NAMESPACE WAS CHANGED
NO STORED REQUEST IDENTITY WAS CHANGED
NO WORKFLOW INSTANCE OR REQUEST ROW WAS BACKFILLED
NO BUSINESS OR APPROVAL RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

## 36. Phase 2.13A request workflow identity compatibility (2026-09-15)

`PHASE 2.13A COMPLETE`

A narrow four-entry `EventRequestModelIdentity` map now supports exact legacy and
future canonical FQCN reads for the Monthly/Agenda edit/delete request models.
Dynamic resolution maps canonical stored identities to the currently installed
legacy classes. Workflow relationships read both identities, workflow creation
reuses either identity, and a mixed old/new duplicate for one workflow/request
causes an explicit hard failure. New workflow instances still use legacy FQCNs.

The Admin relations report now reads and groups both Monthly request identities
without splitting the logical request category; its aggregate filters are
unchanged. Request rows, action logs and notification metadata retain
`App\Models\MonthlyActivity` or `App\Models\AgendaEvent` identity.

All four request models remain under `App\Models`. Tests covering the helper,
resolution, relationships, reuse/create/conflict behavior and reports were added
but not executed because runtime dependencies remain unavailable. The Monthly
pair is source-ready only; live inventory/runtime prerequisites still block its
actual move.

NO REQUEST MODEL NAMESPACE WAS CHANGED
NO STORED REQUEST IDENTITY WAS BACKFILLED
NEW REQUEST WORKFLOW WRITES STILL USE LEGACY FQCNS
NO REQUEST-ROW AGGREGATE IDENTITY WAS CHANGED
NO BUSINESS OR APPROVAL RULE WAS CHANGED
NO DUAL-WRITE WAS INTRODUCED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

## 37. Phase 2.14 aggregate identity compatibility audit (2026-09-15)

`PHASE 2.14 COMPLETE`

`MonthlyActivity` and `AgendaEvent` remain under `App\Models`. Both persist
their FQCN in workflow instances, action history, selected audit rows, request
rows, and notification metadata. Monthly additionally persists its FQCN as the
`OfficialCorrespondence` morph type; a direct move would break both forward and
inverse historical correspondence resolution and could create a second logical
row under the type-bearing unique key.

The selected strategy is exact dual-read compatibility with a single legacy
writer before each move, followed by **SEPARATE CUTOVERS, AGENDA FIRST**. Agenda
has no correspondence morph surface in current source. Request-model workflow
identity and request-row aggregate identity are separate contracts; Phase 2.13B
is not a technical prerequisite for aggregate compatibility, but the cutovers
must not be combined. Full matrices, queries, rollback, and deployment ordering
are in `docs/events-aggregate-identity-cutover.md`.

NO AGGREGATE MODEL NAMESPACE WAS CHANGED
NO STORED AGGREGATE IDENTITY WAS CHANGED
NO WORKFLOW, REQUEST, AUDIT, ACTION-LOG, NOTIFICATION, OR CORRESPONDENCE ROW WAS BACKFILLED
NO BUSINESS OR APPROVAL RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED
PHASE 2.13B REMAINS BLOCKED BY RUNTIME/LIVE INVENTORY

## 38. Phase 2.14A Agenda aggregate identity compatibility (2026-09-15)

`PHASE 2.14A COMPLETE — SOURCE IMPLEMENTATION`

Agenda workflow source now accepts exact legacy and future canonical aggregate
FQCNs, resolves both to the installed legacy model, reuses either identity,
rejects mixed duplicates, and continues writing the legacy identity. The Agenda
model remains in `App\Models`; routes, request writers, action/audit/notification
history, Monthly behavior, schema, and data are unchanged.

Admin reporting normalizes both Agenda workflow identities into one logical
category. Focused tests are added but require staging execution. This source
completion does not assert production readiness.

CODEX RUNTIME VERIFICATION IS UNAVAILABLE
STAGING VERIFICATION IS REQUIRED BEFORE PRODUCTION RELEASE

## 39. Phase 2.14B AgendaEvent namespace cutover (2026-09-15)

`PHASE 2.14B COMPLETE — SOURCE IMPLEMENTATION`

`AgendaEvent` is now owned by `App\Modules\Events\Models`; `agenda_events`
remains unchanged. All active imports are canonical, while the focused aggregate
identity helper preserves exact legacy reads and uses the canonical installed
model/write identity. Mixed historical identity is supported without backfill.

No compatibility model, alias, morph map, migration, route change, workflow
rule, permission change, or data rewrite was introduced. Runtime and live-data
validation remain staging requirements rather than claims of this source phase.

CODEX RUNTIME VERIFICATION IS UNAVAILABLE
STAGING VERIFICATION IS REQUIRED BEFORE PRODUCTION RELEASE

## Ramadan Iftars UX and demo data enhancement (2026-09-15)

`SOURCE IMPLEMENTATION COMPLETE — STAGING VERIFICATION REQUIRED`

Ramadan routes now share a scoped visual theme; the browse experience uses
cards and a configured-period calendar. Existing Settings provide an active
Ramadan season, and create/edit dates are server-validated against it. Guidance
version 1 preserves the supplied 2025 heading and ten source sections. Focused,
idempotent period/guidance/demo seeders support a branch-23 staging showcase
without joining production reference bootstrap.

Workflow, approval, monitoring, closure, versioning, permissions, Common
execution-needs storage, and historical data semantics are unchanged. Details
and staging instructions are in `docs/ramadan-iftars-ui-and-demo-data.md`.

## Ramadan form reconciliation (2026-09-15)

The canonical execution-needs catalogue now owns context applicability and mandatory metadata. Ramadan uses `SubjectExecutionNeed`; Monthly legacy execution-needs storage remains unchanged. Ramadan team, supply, gift, program, attendee, target, meal, and volunteer planning remain in their established normalized tables. Creation is gated by a persisted acknowledgement of the current published guidance version, and branch ownership is server-derived.

### Ramadan dashboard integration hard review (2026-09-15)

The general dashboard exposes its seasonal Ramadan panel only for an active configured period and a user with `ramadan_iftars.view` (or super admin). Queries use the same scoped-branch rule as the Ramadan workspace, one conditional aggregate query, and one five-row eager-loaded upcoming query. Branch 23 is not referenced outside opt-in demo data.


## Phase 2.19 PostExecutionVerification cutover update (2026-09-20)

The Phase 2.8C/2.8D paragraphs above are historical records. Phase 2.19 supersedes
their namespace status: the sole model is now
`App\Modules\Events\Models\PostExecutionVerification`, the focused audit
identity writer is canonical, and both exact audit identities remain accepted.
No migration or backfill occurred; staging verification remains pending.
