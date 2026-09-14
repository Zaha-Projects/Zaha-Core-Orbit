# Events stored identity and model namespace readiness audit

## 1. Decision and scope

`PHASE 2.7 COMPLETE`

This is a source/schema audit, not runtime verification. No model, table, route,
workflow, permission, or stored value was changed. The repository uses a flat
`App\Modules\Events\Models` namespace today; adding Agenda/Monthly/Ramadan/Common
subnamespaces would multiply import and stored-identity churn without resolving
an actual ambiguity. The final destination remains the flat namespace.

The decisive split is not folder ownership but stored identity:

- `MonthlyActivity`, `AgendaEvent`, all four Monthly/Agenda request models,
  `RamadanIftar`, and `RamadanIftarChangeRequest` are workflow identities.
- `MonthlyActivity`, `AgendaEvent`, `RamadanIftar`, and
  `PostExecutionVerification` are also written as FQCNs to action/audit data.
- `MonthlyActivity` additionally owns an `OfficialCorrespondence` through the
  FQCN-backed `correspondable_type` morph.
- Common detail tables use stable subject aliases (`monthly_activity`,
  `ramadan_iftar`), not Eloquent morph classes.
- There is no `Relation::morphMap`, `enforceMorphMap`, model override of
  `getMorphClass`, explicit `Route::model`/`Route::bind`, or Events queue/job
  carrying a model in the audited source.

Accordingly, aggregates and workflow request classes must not move before a
runtime-backed identity compatibility migration. Supporting models with no
stored FQCN are candidates for a smaller import-only move after Phase 2.6.

## 2. Complete Events model inventory

“Consumers” summarizes the repository reference classes rather than listing
every file. “FQCN risk” means evidence that the model class itself is persisted;
route binding alone is reported separately.

| Model | Current namespace | Table | Domain owner / consumers | Final namespace | Identity / FQCN evidence | Action |
|---|---|---|---|---|---|---|
| `AgendaEvent` | `App\Models` | `agenda_events` | Agenda aggregate; controllers, workflow, Monthly/Ramadan links, reports, tests | flat Events | HIGH: workflow instances, action/audit logs, request `entity_type` | DEFER |
| `AgendaApproval` | `App\Models` | `agenda_approvals` | Agenda approval history/model relation | flat Events | none found | MOVE |
| `AgendaEventTarget` | `App\Models` | `agenda_event_targets` | Agenda targets/controllers | flat Events | own `target_type` contains domain codes, not this FQCN | MOVE |
| `AgendaParticipation` | `App\Models` | `agenda_participations` | Agenda branch/unit participation | flat Events | own `entity_type` contains `branch`/`department_unit` aliases | MOVE |
| `AnnualAgendaEditRequest` | `App\Models` | `annual_agenda_edit_requests` | Agenda change-request workflow | flat Events | HIGH: request is a workflow-instance entity; row also stores aggregate FQCN | MOVE WITH COMPATIBILITY |
| `AnnualAgendaDeleteRequest` | `App\Models` | `annual_agenda_delete_requests` | Agenda change-request workflow | flat Events | HIGH: same as edit request | MOVE WITH COMPATIBILITY |
| `MonthlyActivity` | `App\Models` | `monthly_activities` | Monthly aggregate; many controllers/services/models/views/tests | flat Events | HIGH: workflow, action/audit and notification metadata FQCN | DEFER |
| `MonthlyActivityApproval` | `App\Models` | `monthly_activity_approvals` | Monthly legacy approval history | flat Events | none found | MOVE |
| `MonthlyActivityAttachment` | `App\Modules\Events\Models` | `monthly_activity_attachments` | Monthly attachments/routes | flat Events | implicit route binding; no stored FQCN found | MOVED 2.8B |
| `MonthlyActivityChangeLog` | `App\Models` | `monthly_activity_change_logs` | Monthly field history | flat Events | IDs/values, not model FQCN | MOVE |
| `MonthlyActivityEvaluationResponse` | `App\Models` | `monthly_activity_evaluation_responses` | Monthly evaluation | flat Events | none found | MOVE |
| `MonthlyActivityFollowup` | `App\Models` | `monthly_activity_followups` | Monthly follow-up | flat Events | none found | MOVE |
| `MonthlyActivityPartner` | `App\Models` | `monthly_activity_partners` | Monthly partners | flat Events | none found | MOVE |
| `MonthlyActivitySponsor` | `App\Models` | `monthly_activity_sponsors` | Monthly sponsors | flat Events | none found | MOVE |
| `MonthlyActivityVolunteerNeed` | `App\Modules\Events\Models` | `monthly_activity_volunteer_needs` | Monthly-only volunteer planning summary | flat Events | none found on Phase 2.12 recheck | MOVED 2.12 |
| `ExecutionTeamMember` | `App\Modules\Events\Models` | `execution_team_members` | shared Monthly/Ramadan member; route bound | flat Events | no stored FQCN found | RENAMED/MOVED 2.8B |
| `EventSupply` | `App\Modules\Events\Models` | `event_supplies` | shared Monthly/Ramadan supply; route bound | flat Events | no stored FQCN found | RENAMED/MOVED 2.8B |
| `PostExecutionVerification` | `App\Models` | `post_execution_verifications` | shared verification; evaluation/monitoring | flat Events | MEDIUM: `AuditLog.entity_type` writer exists | MOVE WITH COMPATIBILITY |
| `MonthlyPlanEditRequest` | `App\Models` | `monthly_plan_edit_requests` | Monthly change workflow | flat Events | HIGH: request workflow FQCN; row stores Monthly FQCN | MOVE WITH COMPATIBILITY |
| `MonthlyPlanDeleteRequest` | `App\Models` | `monthly_plan_delete_requests` | Monthly change workflow | flat Events | HIGH: request workflow FQCN; row stores Monthly FQCN | MOVE WITH COMPATIBILITY |
| `EventCategory` | `App\Models` | `event_categories` | Agenda/Events catalogue | flat Events | no stored FQCN found | MOVE |
| `EventStatusLookup` | `App\Models` | `event_status_lookups` | Agenda/Monthly stable module catalogue | flat Events | module codes, not FQCN | MOVE |
| `EventType` | `App\Models` | `event_types` | Monthly/Events catalogue | flat Events | no stored FQCN found | MOVE |
| `ExecutionNeedType` | `App\Modules\Events\Models` | `execution_need_types` | shared Events canonical catalogue | flat Events | no stored FQCN found | MOVED 2.8A |
| `TargetGroup` | `App\Models` | `target_groups` | shared Events catalogue | flat Events | no stored FQCN found | MOVE |
| `RamadanIftar` | flat Events | `ramadan_iftars` | Ramadan aggregate/workflows/routes | flat Events | HIGH: workflow instances/action logs contain current FQCN | KEEP |
| `RamadanIftarChangeRequest` | flat Events | `ramadan_iftar_change_requests` | Ramadan change workflow/routes | flat Events | HIGH: request is workflow entity | KEEP |
| `RamadanIftarAttendee` | flat Events | `ramadan_iftar_attendees` | Ramadan actual attendance | flat Events | no stored FQCN found | KEEP |
| `RamadanIftarMeal` | flat Events | `ramadan_iftar_meals` | Ramadan planning detail | flat Events | no stored FQCN found | KEEP |
| `RamadanIftarMealItem` | flat Events | `ramadan_iftar_meal_items` | Ramadan meal child | flat Events | no stored FQCN found | KEEP |
| `RamadanIftarGift` | flat Events | `ramadan_iftar_gifts` | Ramadan planning detail | flat Events | no stored FQCN found | KEEP |
| `RamadanIftarProgramSegment` | flat Events | `ramadan_iftar_program_segments` | Ramadan planning detail | flat Events | no stored FQCN found | KEEP |
| `EventGuidanceVersion` | flat Events | `event_guidance_versions` | Ramadan planning prerequisite | flat Events | no stored FQCN found | KEEP |
| `BeneficiarySegment` | flat Events | `beneficiary_segments` | Common/Ramadan catalogue | flat Events | no stored FQCN found | KEEP |
| `MobilizationMethod` | flat Events | `mobilization_methods` | Common/Ramadan business catalogue | flat Events | no stored FQCN found | KEEP |
| `CommunityOrganization` | flat Events | `community_organizations` | Common/Ramadan business data | flat Events | no stored FQCN found | KEEP |
| `LocalCommunity` | flat Events | `local_communities` | Common/Ramadan business data | flat Events | no stored FQCN found | KEEP |
| `MonitoringMethod` | flat Events | `monitoring_methods` | Common monitoring catalogue | flat Events | no stored FQCN found | KEEP |
| `MonitoringReport` | flat Events | `monitoring_reports` | Common/Ramadan monitoring/routes | flat Events | stable `subject_type`; implicit route binding only | KEEP |
| `ExecutionTeam` | flat Events | `execution_teams` | Common/Ramadan execution header | flat Events | stable `subject_type`; no FQCN found | KEEP |
| `SubjectTargetGroup` | flat Events | `event_target_group` | Common targeting | flat Events | stable `subject_type`; no own FQCN found | KEEP |
| `SubjectExecutionNeed` | flat Events | `subject_execution_needs` | Common execution needs | flat Events | stable `subject_type`; no own FQCN found | KEEP |
| `SubjectVolunteerRequirement` | flat Events | `subject_volunteer_requirements` | Common/Ramadan volunteers | flat Events | stable `subject_type`; no own FQCN found | KEEP |
| `EventSubjectTypes` | flat Events | none | stable subject registry/value object | flat Events | emits aliases; maps aliases to classes in code | KEEP |
| `EventContexts` | flat Events | none | stable module registry/value object | flat Events | emits `agenda`/`monthly_activities` codes | KEEP |

Adjacent `ActivityAttendance`, `ActivityEvaluation`, `ActivityNote`, evaluation
questions/answers, `DonationCash`, `CommunicationsRequest`, `WorkshopsRequest`,
and correspondence models are consumed by Monthly but belong to attendance,
evaluation, donations, communications, workshops, and correspondence domains.
They remain `App\Models`; ownership cannot be inferred from a foreign key alone.

## 3. Raw-reference blast radius

Token counts from `app`, `database`, `routes`, `tests`, `resources`, and `config`
provide a relative migration size (a token may occur more than once per file):

| Candidate | Controllers | Services | Requests | Models | Views | Tests | Seeders | Other | Files containing explicit current FQCN |
|---|---:|---:|---:|---:|---:|---:|---:|---:|---:|
| `MonthlyActivity` | 176 | 59 | 1 | 25 | 0 | 125 | 23 | 8 | 74 |
| `AgendaEvent` | 77 | 32 | 2 | 10 | 0 | 29 | 11 | 0 | 29 |
| `RamadanIftar` | 60 | 100 | 11 | 10 | 1 | 142 | 0 | 0 | 35 |
| `RamadanIftarChangeRequest` | 7 | 13 | 0 | 2 | 0 | 5 | 0 | 0 | 4 |
| `TargetGroup` | 14 | 0 | 3 | 7 | 0 | 27 | 2 | 0 | 17 |
| `ExecutionNeedType` | 2 | 2 | 2 | 3 | 0 | 34 | 4 | 0 | 13 |
| `MonthlyActivityTeam` | 7 | 0 | 0 | 4 | 0 | 13 | 2 | 0 | 9 |
| `MonthlyActivitySupply` | 6 | 4 | 0 | 4 | 0 | 10 | 2 | 0 | 10 |
| `PostExecutionVerification` | 4 | 5 | 2 | 4 | 2 | 19 | 0 | 0 | 13 |
| `SubjectTargetGroup` | 0 | 0 | 0 | 2 | 0 | 8 | 0 | 0 | 4 |
| `ExecutionTeam` | 0 | 3 | 0 | 3 | 0 | 9 | 0 | 0 | 5 |
| `MonitoringReport` | 12 | 23 | 4 | 4 | 0 | 38 | 0 | 0 | 11 |

These counts measure source churn, not stored-identity proof. The first four
aggregate/request identities remain high risk even if imports can be automated.

## 4. Identity storage map

| Table | Column | Stored format / expected values | Current writer | Current reader | Risk |
|---|---|---|---|---|---|
| `workflow_instances` | `entity_type` | PHP FQCN | `DynamicWorkflowService::forModel/forEntity` and Ramadan submission | dynamic resolver (`class_exists`, `::query`), aggregate relations/services/reports | CRITICAL: direct move makes history unresolvable and may create duplicate workflow rows |
| `workflow_action_logs` | `entity_type` | PHP FQCN | Monthly concern, Agenda approval, change-request service, Ramadan services | reporting/history queries | HIGH: historical display/filter discontinuity |
| `audit_logs` | `entity_type` | PHP FQCN | Agenda controller, activity evaluation service and other audit writers | audit/report consumers | HIGH for Agenda/Monthly/verification history |
| `official_correspondences` | `correspondable_type` | Eloquent morph FQCN (`App\Models\MonthlyActivity`) | Monthly planning controller/showcase seeder | `MonthlyActivity::officialCorrespondence`, `OfficialCorrespondence::correspondable` | CRITICAL: a direct Monthly move breaks inverse resolution |
| four Monthly/Agenda request tables | `entity_type` | aggregate PHP FQCN | `PlanChangeRequestWorkflowService` | assertions, notification metadata and request queries | HIGH |
| same request tables | `request_type` | stable `edit`/`delete` code | change-request service | request workflow/presentation | LOW |
| `event_target_group` | `subject_type` | stable Events alias | Phase 2.3 backfill and planning services | filtered model relations | LOW; class move does not alter value |
| `monthly_activity_supplies` | `subject_type` | stable Events alias | Phase 2.3 backfill and planning services | filtered model relations | LOW |
| `execution_teams` | `subject_type` | stable Events alias | Ramadan planning | Ramadan relation | LOW |
| `subject_volunteer_requirements` | `subject_type` | stable Events alias | Ramadan planning | Ramadan relation | LOW |
| `subject_execution_needs` | `subject_type` | stable Events alias | Ramadan planning/common needs | filtered relation/services | LOW |
| `monitoring_reports` | `subject_type` | stable Events alias | Ramadan monitoring | Ramadan/report relations | LOW |
| `agenda_participations` | `entity_type` | `branch` or `department_unit` | Agenda controllers/seeders | Agenda visibility/sync | LOW; not a model FQCN |
| `agenda_event_targets` | `target_type` | target domain discriminator | Agenda code | Agenda target consumers | MEDIUM naming ambiguity, no evidence it stores candidate FQCNs |
| `model_has_roles`, `model_has_permissions`, `model_denied_permissions` | `model_type` | Spatie morph FQCN, principally `User` | permission package | permission package | global identity risk, but audited Events models are not assigned roles/permissions |
| `zaha_time_bookings` | `entity_type` | caller-provided string | finance controller | finance scheduling | unrelated generic identity; no Events model writer found |

`WorkflowLog` has no entity-type column: it belongs to `WorkflowInstance`, so
its historical identity is indirectly coupled through the instance FQCN.
Notification `meta` is JSON rather than a dedicated identity column;
`WorkflowNotificationService` writes `get_class($entity)` there, and a seeder
queries the serialized `App\\Models\\MonthlyActivity` value.

## 5. Workflow identity map

| Flow | Module | Stored `entity_type` | Model | Namespace move breaks? | Requirement |
|---|---|---|---|---|---|
| Agenda approval | `agenda` | `App\Models\AgendaEvent` | `AgendaEvent` | Yes | dual-read old/new identity plus atomic data migration before writer cutover |
| Monthly approval | `monthly_activities` | `App\Models\MonthlyActivity` | `MonthlyActivity` | Yes | same; reports explicitly filter old class |
| Monthly edit | request workflow module plus request row | request-model FQCN in workflow; Monthly FQCN in request | `MonthlyPlanEditRequest` / `MonthlyActivity` | Yes | compatibility for both identities; prevent duplicate instance |
| Monthly delete | same | request-model FQCN; Monthly FQCN | `MonthlyPlanDeleteRequest` / `MonthlyActivity` | Yes | same |
| Agenda edit | request workflow plus request row | request-model FQCN; Agenda FQCN | `AnnualAgendaEditRequest` / `AgendaEvent` | Yes | same |
| Agenda delete | same | request-model FQCN; Agenda FQCN | `AnnualAgendaDeleteRequest` / `AgendaEvent` | Yes | same |
| Ramadan approval | `ramadan_iftars` | `App\Modules\Events\Models\RamadanIftar` | `RamadanIftar` | Only if subnamespaced/renamed | keep flat namespace |
| Ramadan change request | `ramadan_iftar_change_requests` | request FQCN | `RamadanIftarChangeRequest` | Only if moved again | keep flat namespace |
| Ramadan monitoring actions | `ramadan_iftars` action log | Ramadan aggregate FQCN | `RamadanIftar` | Only if moved again | keep flat namespace |
| Ramadan closure actions | `ramadan_iftars` action log | Ramadan aggregate FQCN | `RamadanIftar` | Only if moved again | keep flat namespace |

`DynamicWorkflowService` creates uniqueness on workflow + exact FQCN + ID and
resolves by `class_exists($entity_type)` followed by that class's query. This is
the central compatibility boundary. `MonthlyActivityWorkflowService` is a
deprecated status mirror and `MonthlyActivityLifecycleService` delegates to the
dynamic workflow; neither converts identity to a stable alias.

## 6. Polymorphic and stable-subject map

- `MonthlyActivity::workflowInstance()` and `AgendaEvent::workflowInstance()`
  use Eloquent `morphOne`, so their default morph class is their FQCN.
- `MonthlyActivity::officialCorrespondence()` and
  `OfficialCorrespondence::correspondable()` form another unrestricted Eloquent
  morph whose stored `correspondable_type` is `MonthlyActivity::class`.
- The Ramadan aggregate and change request implement equivalent filtered
  `hasOne` relations explicitly against their FQCN.
- Common targeting, supplies, execution teams, volunteer requirements,
  execution needs, and monitoring reports are not unrestricted Eloquent morphs;
  relations filter stable codes from `EventSubjectTypes`.
- `EventSubjectTypes` reserves only `monthly_activity` and `ramadan_iftar`, and
  maps those aliases to model classes in code. There is no Agenda subject alias.
- `EventContexts` contains stable workflow/catalogue module codes `agenda` and
  `monthly_activities`; these are not model identities.
- No global morph map exists. None should be added as a namespace workaround.

## 7. Route-binding audit

No explicit binding registration or `resolveRouteBinding` override exists.
Laravel implicit binding derives the class from controller method type hints;
moving a class and updating imports keeps new requests working, but queued URLs
or route parameter names do not store the FQCN.

High source-churn implicit bindings are `MonthlyActivity`, `AgendaEvent`, and
`RamadanIftar`. Supporting bound models include `MonthlyActivityTeam`,
`MonthlyActivitySupply`, `MonthlyActivityAttachment`, `MonitoringReport`, and
`RamadanIftarChangeRequest`. Route parameter names can remain unchanged during a
future namespace-only move. Stored workflow identity, not routing, blocks the
aggregate moves.

## 8. Queue and notification serialization audit

No Events job, listener, notification, or mailable implementing `ShouldQueue` or
using `SerializesModels` with an Events model was found. Notifications are
written synchronously through notification services. Their JSON `meta` stores
`get_class($entity)`, so Monthly/Agenda/Ramadan FQCNs remain historical identity
data even without Laravel queue serialization.

Risk is currently notification-history filtering/rendering, not an identified
queued payload. Before any future move, production queue backlogs and external
workers must still be checked because repository source cannot prove queues are
empty.

## 9. Audit, history, and request identity

- `WorkflowActionLog` stores aggregate FQCNs for Monthly actions, Agenda
  approvals, and Ramadan submission/approval/execution/monitoring/closure/change
  actions.
- `AuditLog` stores `AgendaEvent::class`, `MonthlyActivity::class`, and
  `PostExecutionVerification::class` in current writers.
- `official_correspondences.correspondable_type` stores
  `MonthlyActivity::class`, and the inverse `morphTo` must continue resolving
  historical rows.
- Monthly field-change logs use `monthly_activity_id`, not a class discriminator.
- Workflow decision logs inherit identity through `workflow_instance_id`.
- Monthly/Agenda request tables store the source aggregate FQCN plus integer ID;
  their workflow instances separately store the request class FQCN.
- Ramadan change requests use explicit `ramadan_iftar_id` and
  `created_version_id`, but the request's workflow instance stores the request
  FQCN; its action logs use the Ramadan aggregate FQCN.
- Approval-history and notification metadata may embed identity strings in JSON
  and must be inventoried from a real database before migration.

## 10. Namespace and naming decisions

### Namespace map

- **Flat `App\Modules\Events\Models`:** all Events-owned catalogue, Agenda,
  Monthly, Ramadan, and Common models listed with that final namespace above.
- **Remain `App\Models`:** `User`, `Role`, `Branch`, `Center`, `Department`,
  `DepartmentUnit`, `Workflow`, `WorkflowStep`, `WorkflowInstance`,
  `WorkflowLog`, `WorkflowActionLog`, `AuditLog`, notification infrastructure,
  and communications/donations/workshops/correspondence/file infrastructure.
- Do not add four subnamespace layers: the storage risks remain identical while
  imports and compatibility surface increase.

### Team member — `DEFER`

Keep `MonthlyActivityTeam` and `monthly_activity_team` through the first
namespace move. `ExecutionTeamMember` is semantically clearer for shared use,
but renaming the retained historical table to the exact name of the abandoned
development table would obscure Phase 2.3 history and expand migration/raw-SQL
risk. Reconsider a **model-only** rename after runtime proof and usage migration;
do not rename the table in the foreseeable sequence.

### Supply — `DEFER`

`EventSupply` better describes current Monthly and Ramadan use, but
`monthly_activity_supplies` retains production-compatible IDs and legacy
Monthly columns. A model-only rename may eventually be justified; there is no
evidence supporting table churn to `event_supplies`. Keep both names for the
first move slice.

### Verification — keep table and model name

`PostExecutionVerification` accurately describes both Monthly post-execution
and Ramadan monitoring verification. Keep `post_execution_verifications`; move
the model only with audit-log compatibility because its FQCN is persisted.

### Targeting — keep table and model name

`SubjectTargetGroup` expresses the stable subject discriminator more accurately
than a Monthly-specific name. Keep both it and `event_target_group`; no evidence
justifies a rename.

## 11. Move-risk ranking

### LOW (after Phase 2.6)

`AgendaApproval`, `AgendaEventTarget`, `AgendaParticipation`,
`MonthlyActivityApproval`, `MonthlyActivityChangeLog`,
`MonthlyActivityEvaluationResponse`, `MonthlyActivityFollowup`,
`MonthlyActivityPartner`, `MonthlyActivitySponsor`, `EventCategory`,
`EventStatusLookup`, `EventType`, `ExecutionNeedType`, and `TargetGroup` have no
stored self-FQCN evidence and no model serialization evidence. Move as one
small import-only batch, with static reference and runtime relationship tests.

### MEDIUM

`MonthlyActivityAttachment`, `MonthlyActivityTeam`, and
`MonthlyActivitySupply` are implicitly route-bound and have broader Monthly
surface area but no stored self-FQCN evidence. `PostExecutionVerification` is
medium-to-high because audit rows store its FQCN. Naming changes increase each
risk and must be separate from namespace changes.

### HIGH / do not move yet

`MonthlyActivity`, `AgendaEvent`, all four Monthly/Agenda request models, and
`PostExecutionVerification` until audit compatibility exists. Also do not move
the already-correct `RamadanIftar` or `RamadanIftarChangeRequest` into new
subnamespaces: their current module FQCN is historical workflow identity.
Defer `MonthlyActivityVolunteerNeed` until volunteer semantics are decided.

## 12. Compatibility strategy

1. **Direct move plus immediate FQCN backfill (A):** insufficient alone; old
   workers/rollbacks and mixed deployments can write/read the opposite value.
2. **Temporary old-namespace subclass (B):** appropriate only as a time-boxed
   read/instantiation bridge. One canonical implementation and one canonical
   writer are mandatory; do not allow two divergent writable aggregates.
3. **Stable aliases (C):** retain existing subject aliases. For workflows,
   adding aliases requires an explicit resolver and a staged dual-read/data
   migration; a global morph map is prohibited.
4. **Permanent defer (D):** valid for `MonthlyActivity`/`AgendaEvent` if the
   migration benefit does not exceed operational risk.
5. **Support-only move (E):** preferred next model work after runtime recovery.

For identity-sensitive classes, first inventory distinct production values,
then add explicit old/new resolution and duplicate-prevention tests, deploy a
single canonical writer, backfill transactionally, observe, remove old writes,
and retire the compatibility class only after old rows, queue backlogs, and
rollback windows are clear. Never use unrestricted morph maps, `class_alias`
magic, or indefinite duplicate active models.

## 13. Required future compatibility tests

Before and during an identity-sensitive move, runtime tests must prove:

1. an old workflow `entity_type` resolves and continues at the correct step;
2. historical workflow/audit/action-log/notification entries render;
3. existing edit/delete/Ramadan change requests load and decide correctly;
4. implicit route binding resolves the moved aggregate and supporting models;
5. new records use only the chosen final identity;
6. old and new identities cannot create duplicate workflow instances;
7. branch-scoped workflow/report queries include pre-migration history;
8. queued payloads created before deployment deserialize, if any exist;
9. rollback during the compatibility window retains readers for both values;
10. stable subject aliases and all generalized relationships remain unchanged.
11. an existing Monthly official correspondence resolves through its historical
    `correspondable_type` after the move.

Static import/FQCN tests can guard wiring, but cannot replace these database and
deployment-transition tests.

## 14. Proposed migration sequence

All model work is conditional on completing Phase 2.6 before production release.

1. **2.8A — low-risk support namespace move:** move only the LOW group to the
   existing flat Events namespace; update imports; no renames or schema.
2. **2.8B — route-bound shared support move:** move team, supply, and attachment
   models without renaming; execute route/relationship regression.
3. **2.8C — identity compatibility infrastructure:** after live distinct-value
   inventory, add explicit old/new workflow/audit/request resolution and tests;
   do not move aggregates in the same deployment.
4. **2.8D — identity-sensitive cutover:** separately migrate request identities,
   then `AgendaEvent`, then `MonthlyActivity`, with transactional backfills and
   observation between slices. Move `PostExecutionVerification` with its audit
   identity migration.
5. **2.8E — compatibility retirement:** remove old namespace bridges only after
   stored-value, queue, rollback-window, and monitoring evidence is clean.
6. **Later naming decision:** consider model-only `ExecutionTeamMember` and
   `EventSupply`; keep the historical table names unless operational evidence
   establishes a non-aesthetic need.

## 15. Runtime debt

```text
PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED
```

No Composer installation was attempted in Phase 2.7. This audit must not be used
as production-readiness evidence; Phase 2.6 remains mandatory before any model
migration reaches production.

## 16. Phase 2.8A low-risk support-model outcome

`PHASE 2.8A COMPLETE`

The Phase 2.7 LOW group was rechecked against workflow, action-log, audit-log,
request, notification-metadata, and correspondence identity writers. No model in
the group is persisted as its own FQCN, so all fourteen approved models moved to
the existing flat `App\Modules\Events\Models` namespace:

- Agenda: `AgendaApproval`, `AgendaEventTarget`, `AgendaParticipation`;
- Monthly: `MonthlyActivityApproval`, `MonthlyActivityChangeLog`,
  `MonthlyActivityEvaluationResponse`, `MonthlyActivityFollowup`,
  `MonthlyActivityPartner`, `MonthlyActivitySponsor`;
- catalogues: `EventCategory`, `EventStatusLookup`, `EventType`,
  `ExecutionNeedType`, `TargetGroup`.

There were no candidate deferrals and no compatibility wrappers. Aggregate and
request FQCNs remain unchanged. Imports were updated across models, controllers,
services, form requests, seeders, Blade templates, and tests. No config,
command/job/listener, route definition, migration, data, or business method was
changed. The moved models use `HasFactory`, but no candidate-specific factory,
`newFactory()` override, or candidate `::factory()` call exists; future runtime
verification must still cover factory discovery if factories are later added.

Events-owned models deliberately remaining in `App\Models` are:

| Model | Reason | Risk / future slice |
|---|---|---|
| `MonthlyActivity`, `AgendaEvent` | persisted aggregate FQCNs | HIGH; identity compatibility before any move |
| `MonthlyPlanEditRequest`, `MonthlyPlanDeleteRequest`, `AnnualAgendaEditRequest`, `AnnualAgendaDeleteRequest` | persisted request workflow FQCNs and source identities | HIGH; identity compatibility slice |
| `MonthlyActivityAttachment` | implicit route binding and broader surface | MEDIUM; 2.8B |
| `MonthlyActivityTeam`, `MonthlyActivitySupply` | route-bound shared historical models; naming deferred | MEDIUM; 2.8B without rename |
| `PostExecutionVerification` | FQCN in audit history | MEDIUM/HIGH; identity compatibility slice |
| `MonthlyActivityVolunteerNeed` | volunteer semantics unresolved | DEFER until semantics decision |

The next model-only slice is 2.8B for the three route-bound support models,
without model/table renames. It remains conditional on the deferred runtime gate.

```text
PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED
```

## 17. Phase 2.8B identity and naming outcome

`PHASE 2.8B COMPLETE`

The pre/post-cutover identity search found no writer or persisted discriminator
for `App\\Models\\MonthlyActivityTeam` or
`App\\Models\\MonthlyActivitySupply`. They were therefore removed rather than
retained as writable compatibility models. Their replacements are
`App\\Modules\\Events\\Models\\ExecutionTeamMember` and
`App\\Modules\\Events\\Models\\EventSupply`.

The support models remain implicitly route-bound, but route parameter names are
not stored FQCN identity and were preserved. `MonthlyActivityAttachment` likewise
had no stored FQCN; it moved namespace only and retained its Monthly-specific
name because only Monthly controllers and the Monthly aggregate use it.

The original development-only `execution_team_members` table from Phase 1.x
was abandoned in Phase 2.3.

The current `execution_team_members` name is the renamed and generalized
historical `monthly_activity_team` table.

Historical rows and IDs were preserved.

`event_supplies` is the renamed generalized historical
`monthly_activity_supplies` table, not a newly created replacement table.
Neither rename changes stable `monthly_activity`/`ramadan_iftar` subject values,
introduces a morph map, or rewrites workflow, audit, notification,
correspondence, request, or serialized identity.

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

## 18. Phase 2.8C PostExecutionVerification compatibility outcome

`PHASE 2.8C COMPLETE`

The exhaustive source recheck narrows the stored self-identity boundary to
`audit_logs.entity_type`, written once by
`ActivityEvaluationService::verify()`. That column is a
caller-supplied indexed string: current audit readers aggregate/display rows and
do not dynamically resolve it. `workflow_instances` has a different dynamic
class contract, but no PostExecutionVerification identity is written there or to
`workflow_action_logs`.

`App\Modules\Events\Support\PostExecutionVerificationIdentity` is the focused
transition boundary. Reads that filter verification history must accept its
exact legacy and canonical values; the writer deliberately remains legacy in
2.8C. Strategy comparison, live inventory SQL, exact transactional backfill and
reverse-backfill design, safe deployment ordering, and retirement criteria are
recorded in `docs/post-execution-verification-identity-cutover.md`.

`App\Models\PostExecutionVerification` remains the only model. The future
canonical identity is
`App\Modules\Events\Models\PostExecutionVerification`, but no such production
model is introduced in this phase.

`POSTEXECUTIONVERIFICATION CUTOVER NOT READY`

NO STORED IDENTITY WAS BACKFILLED
NO MODEL NAMESPACE CUTOVER WAS PERFORMED
NO TABLE OR BUSINESS DATA WAS CHANGED
NO WORKFLOW OR MONITORING RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

## 19. Phase 2.8D blocked gate outcome

`POSTEXECUTIONVERIFICATION CUTOVER BLOCKED`

The namespace cutover was not approved: `vendor/autoload.php` is absent and no
explicitly disposable database is configured or present. Consequently Laravel
was not booted and the mandatory live legacy/canonical/unknown/missing-reference
identity inventory was not executed. The only active model remains
`App\Models\PostExecutionVerification`; `currentWriteType()` remains `LEGACY`;
`acceptedTypes()` remains the exact legacy/canonical pair.

NO MODEL NAMESPACE CUTOVER WAS PERFORMED
NO STORED IDENTITY WAS CHANGED
NO BUSINESS DATA WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

`PHASE 2.8D INCOMPLETE`

## 20. Phase 2.12 MonthlyActivityVolunteerNeed identity outcome

`PHASE 2.12 COMPLETE`

The repository-wide recheck found no use of
`App\Models\MonthlyActivityVolunteerNeed` or
`MonthlyActivityVolunteerNeed::class` as a persisted discriminator. The model
does not participate in `entity_type`, `model_type`, `auditable_type`,
`correspondable_type`, workflow subject identity, notification class metadata,
or application serialization. It also has no direct route binding.

The model therefore moved without a compatibility wrapper to
`App\Modules\Events\Models\MonthlyActivityVolunteerNeed`. This is the only
production model class for `monthly_activity_volunteer_needs`.

Events-owned models remaining in `App\Models`:

| Model | Reason | Risk | Future phase |
|---|---|---|---|
| `MonthlyActivity` | persisted aggregate identity across workflow/audit/request/notifications | HIGH | dedicated aggregate identity compatibility phase |
| `AgendaEvent` | persisted aggregate identity across workflow/audit/request history | HIGH | dedicated aggregate identity compatibility phase |
| `MonthlyPlanEditRequest` | persisted request workflow/source identity | HIGH | dedicated request identity compatibility phase |
| `MonthlyPlanDeleteRequest` | persisted request workflow/source identity | HIGH | dedicated request identity compatibility phase |
| `AnnualAgendaEditRequest` | persisted request workflow/source identity | HIGH | dedicated request identity compatibility phase |
| `AnnualAgendaDeleteRequest` | persisted request workflow/source identity | HIGH | dedicated request identity compatibility phase |
| `PostExecutionVerification` | FQCN stored in audit history | MEDIUM/HIGH | Phase 2.8D after runtime prerequisites |

NO STORED WORKFLOW/AUDIT IDENTITY WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

## 21. Phase 2.13 request-model identity outcome

`PHASE 2.13 COMPLETE`

All four Monthly/Agenda edit/delete request models are confirmed HIGH-risk
stored identities. `workflow_instances.entity_type` stores each request FQCN;
the workflow resolver dynamically queries it, model relationships match it as a
morph type, and reporting contains exact request-class filters. By contrast,
the four request tables, request notification metadata, and request-created
workflow action logs store the underlying aggregate FQCN. `workflow_logs` and
audit logs do not store these request-model identities.

The future compatibility boundary should be one focused four-entry request
identity map. It must provide exact legacy/canonical pairs, dual-read workflow
lookup, mapped dynamic resolution, and find-before-create protection across both
identities. It must not be a global alias resolver or duplicate Eloquent model.
The current writer remains legacy.

The smallest cutover grouping is `MONTHLY PAIR FIRST`, after a compatibility-only
deployment and live inventory. Monthly edit/delete share mutual-exclusion,
workflow, reporting and conditional-step behavior; splitting them would split
one business invariant. Agenda remains unchanged until a later observed slice.

See `docs/events-request-model-identity-cutover.md` for the complete contract and
safe deployment/rollback design.

NO REQUEST MODEL NAMESPACE WAS CHANGED
NO STORED REQUEST IDENTITY WAS CHANGED
NO WORKFLOW INSTANCE OR REQUEST ROW WAS BACKFILLED
NO BUSINESS OR APPROVAL RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED
