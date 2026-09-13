# Events data-model consolidation audit

**Status: CURRENT_SUPPORTING**

Current source of truth: `docs/events-architecture-current-state.md`.

**Phase:** 2.2

**Decision date:** 2026-09-13

**Status:** complete architecture audit; no migration or runtime change is part of this phase.

## 1. Decision rules

1. Preserve an established relational table and its primary keys when it represents the
   same business concept. Prefer `ALTER`, `RENAME`, and in-place generalization to copying
   historical Monthly rows into a newer table.
2. A table is Events-common when it is shared by Monthly Activities, Ramadan Iftars, or
   Agenda. That does not make it application-global. Events-only models ultimately belong
   in `App\Modules\Events\Models`; infrastructure used by unrelated modules remains in
   `App\Models`.
3. During a future in-place transition, `monthly_activity_id` may temporarily coexist with
   server-controlled `subject_type` and `subject_id`. The legacy foreign key is removed only
   after backfill, dual-read comparison, read cutover, write cutover, and archive/report
   regression checks.
4. New relational tables are retained where Monthly has only JSON, where the new concept
   is genuinely Ramadan-specific, or where the existing table has materially different
   cardinality and meaning.
5. No deletion-first consolidation is approved. A `DEPRECATE LATER` decision is a gate,
   not authorization to drop anything.

## 2. Current table inventory

All listed relational tables use an auto-incrementing `id` primary key unless stated
otherwise. “History” describes why identity must be preserved.

### Core plans, Agenda, and domain-owned detail

| Table | Current model | Important ownership/FKs | JSON / deletion | Consumers and historical significance | Bootstrap |
|---|---|---|---|---|---|
| `monthly_activities` | `App\Models\MonthlyActivity` | `agenda_event_id`, `branch_id`, `center_id`, `created_by`, self version FKs | execution-needs and post-execution JSON; soft deletes | Monthly and reports; authoritative historical plan/version/execution record | business-created |
| `agenda_events` | `App\Models\AgendaEvent` | departments, creator, self version FK | soft deletes | Agenda and source for Monthly/Ramadan; IDs are referenced by generated plans | business-created |
| `agenda_event_targets` | `App\Models\AgendaEventTarget` | `agenda_event_id`, polymorphic target columns | none | Agenda audience/participation targeting; not beneficiary targeting | business-created |
| `agenda_participations`, `agenda_approvals`, `agenda_event_partner_departments` | corresponding `App\Models` models | Agenda, users/departments | none | Agenda workflow and participation history | business-created |
| `ramadan_iftars` | `App\Modules\Events\Models\RamadanIftar` | Agenda, branch, users, host/reference rows, self version, guidance | soft deletes | Ramadan aggregate; immutable approved version and operational history | business-created |
| `ramadan_iftar_attendees` | `RamadanIftarAttendee` | Ramadan, target group, segment | none | actual named attendance/check-in, not a Monthly planning relation | business-created |
| `ramadan_iftar_meals`, `ramadan_iftar_meal_items` | `RamadanIftarMeal`, `RamadanIftarMealItem` | Ramadan and parent meal | none | Ramadan-specific planned/actual meal detail | business-created |
| `ramadan_iftar_gifts`, `ramadan_iftar_program_segments` | `RamadanIftarGift`, `RamadanIftarProgramSegment` | Ramadan and optional users | none | Ramadan-specific gift/program execution history | business-created |
| `monthly_activity_attachments`, `monthly_activity_sponsors`, `monthly_activity_partners` | corresponding `App\Models` models | `monthly_activity_id` and users where applicable | none | Monthly-specific artifacts and commercial relationships | business-created |
| `monthly_activity_change_logs`, `monthly_activity_approvals` | corresponding `App\Models` models | Monthly and actor | change snapshots may be JSON in change log | legacy Monthly audit/approval evidence | runtime-created |
| `monthly_activity_followups` | `App\Models\MonthlyActivityFollowup` | Monthly and creator | none | unstructured Monthly follow-up remarks; not a monitoring report | business-created |
| `monthly_activity_evaluation_responses`, `activity_evaluations`, `activity_evaluation_answers` | corresponding `App\Models` models | Monthly, forms/questions, users | none | scored Monthly evaluation history | business-created |
| `evaluation_forms`, `evaluation_questions` | corresponding `App\Models` models | users and form | none | configured Monthly evaluation instruments | business-managed/reference |
| `monthly_kpis` | `App\Models\MonthlyKpi` | branch/time ownership | none | Monthly reporting snapshots; outside Common execution storage | runtime-created |

### Targeting, execution, and monitoring

| Table / storage | Current model | Important ownership/FKs | JSON / deletion | Consumers and historical significance | Bootstrap |
|---|---|---|---|---|---|
| `target_groups` | `App\Models\TargetGroup` | none; applicability flags | none | shared master used by Monthly and Ramadan | idempotent reference seed |
| `monthly_activities.target_group_id` and text columns | on `MonthlyActivity` | optional target master FK is not constrained | legacy scalar/text | oldest Monthly target representation; must remain readable | business-created |
| `event_target_group` | pivot, no model | Monthly + target group | none | relational Monthly many-to-many; row IDs/history can be retained | business-created |
| `subject_target_groups` | `App\Modules\Events\Models\SubjectTargetGroup` | subject alias, target group, optional segment | none | current Ramadan planned/actual segmented targeting | business-created |
| `beneficiary_segments` | `BeneficiarySegment` | none | none | new Events segmentation dimension; no Monthly relational equivalent | idempotent reference seed |
| `monthly_activity_team` | `App\Models\MonthlyActivityTeam` | Monthly + optional user | none | one row per legacy team member; IDs may appear in history | business-created |
| `execution_teams` | `ExecutionTeam` | subject alias + leader | none | Ramadan team header with planned/actual counts | business-created |
| `execution_team_members` | `ExecutionTeamMember` | team + users | none | Ramadan team-member task/confirmation evidence | business-created |
| `monthly_activity_volunteer_needs` | `MonthlyActivityVolunteerNeed` | unique Monthly FK | none | one summary row per Monthly plan, with planned and actual count | business-created |
| `subject_volunteer_requirements` | `SubjectVolunteerRequirement` | subject alias + optional segment | none | repeatable segmented Ramadan requirements | business-created |
| `monthly_activity_supplies` | `MonthlyActivitySupply` | Monthly | none | repeatable Monthly planned/availability supply rows | business-created |
| `subject_supplies` | `SubjectSupply` | subject alias | none | Ramadan planned/actual supply rows | business-created |
| `execution_need_types` | `App\Models\ExecutionNeedType` | none; canonical/applicability flags | none | single canonical master for Monthly mappings and Ramadan rows | canonical seed |
| `monthly_activities.execution_needs_payload` / `execution_needs_followup` | casts on `MonthlyActivity` | keys map to type codes | JSON | authoritative legacy Monthly planning and follow-up decisions | runtime-created |
| `subject_execution_needs` | `SubjectExecutionNeed` | subject alias + type FK | none | normalized Ramadan planned/actual execution need state | business-created |
| `monthly_activities.post_execution_payload` | cast on `MonthlyActivity` | embedded evidence | JSON | authoritative legacy Monthly post-execution report | runtime-created |
| `monthly_activity_followups` | `MonthlyActivityFollowup` | Monthly + creator | none | supplemental remarks, not structured verification | business-created |
| `post_execution_verifications` | `PostExecutionVerification` | Monthly, branch, verifier | original/corrected JSON values | structured Monthly correction decision per field | runtime-created |
| `monitoring_methods` | `MonitoringMethod` | none | none | monitoring channel lookup, currently Ramadan | idempotent reference seed |
| `monitoring_reports` | `MonitoringReport` | subject alias, method, monitor | none | normalized report envelope/review state; no Monthly relational envelope exists | business-created |
| `field_verifications` | `FieldVerification` | monitoring report, verifier, optional detail alias | planned/actual JSON values | immutable comparisons under a report; overlaps Monthly verification semantics | runtime-created |

### Change requests, guidance, lookups, and workflow

| Table | Current model/namespace | Important ownership/FKs | Consumers / history | Bootstrap |
|---|---|---|---|---|
| `monthly_plan_edit_requests`, `monthly_plan_delete_requests` | `App\Models\MonthlyPlanEditRequest`, `MonthlyPlanDeleteRequest` | generic entity columns, requester/approver, branch; edit snapshots JSON | Monthly-specific multi-actor request semantics and approved-version link | runtime-created |
| `annual_agenda_edit_requests`, `annual_agenda_delete_requests` | corresponding `App\Models` models | generic entity columns and actor/history fields | Agenda-specific request semantics | runtime-created |
| `ramadan_iftar_change_requests` | `App\Modules\Events\Models\RamadanIftarChangeRequest` | exact Ramadan version, branch, requester/reviewer/new version | Ramadan revision authorization audit; semantically not Monthly snapshot editing | runtime-created |
| `event_guidance_versions` | `EventGuidanceVersion` | creator; referenced by Ramadan | approved, versioned policy content; currently Ramadan-only in runtime | business-managed; never synthetic seeded |
| `event_types`, `event_categories`, `event_status_lookups` | `App\Models\EventType`, `EventCategory`, `EventStatusLookup` | lookup keys | Events-only lookups used by Agenda/Monthly | idempotent reference seeds |
| `mobilization_methods` | `MobilizationMethod` | none | Ramadan/Event mobilization lookup | currently business-managed (no seeder) |
| `community_organizations`, `local_communities` | corresponding Events models | branch | branch-maintained Ramadan/Event host directories | business-managed |
| `workflows`, `workflow_steps`, `workflow_instances`, `workflow_logs`, `workflow_action_logs` | `App\Models\Workflow*` | roles, permissions, entities, users | application-wide engine used by Agenda, Monthly, Ramadan, and non-Events callers | required production seed for definitions |
| `roles`, `permissions` and package pivots | application/security models | application-wide | authorization across unrelated modules | required production seed/bootstrap |

All domain rows above carry timestamps except package-owned pivots and any legacy schema
exceptions. Monthly and Ramadan aggregates have soft deletion; most dependent rows use
cascading foreign keys, so no consolidation phase may exercise deletes while moving data.

## 3. Monthly structure classification

| Monthly structure | Class | Finding |
|---|---|---|
| `monthly_activities`, approvals, attachments, sponsors, partners, follow-ups, evaluations, KPIs | A — Monthly-specific | Their lifecycle or payload semantics are Monthly business behavior, not generic ownership alone. |
| `event_target_group` | B — reusable Event concept | It is already relational and has stable IDs; generalize it rather than replacing Monthly rows. |
| `monthly_activity_supplies` | B — reusable Event concept | Field meanings substantially align with `subject_supplies`; preserve old rows in place. |
| `execution_needs_payload`, `execution_needs_followup`, `post_execution_payload` | C — legacy JSON | No row identity exists to preserve; later parsing and reconciliation are intrinsically high risk. |
| `monthly_activity_team` | B, but member-level | It stores members rather than team headers; it aligns with `execution_team_members`, not directly with `execution_teams`. |
| `monthly_activity_volunteer_needs` | A/B boundary | It is a single Monthly summary, whereas Common requirements are repeatable and segment-aware; keep it until an explicit semantic conversion. |
| duplicate direct target columns after pivot cutover | D — redundant later | They remain required for historical compatibility until a proven read/write cutover. |

## 4. Required decision matrix

Each “table action” is one mandatory primary decision. “Model action” names the end-state
without executing it in this phase.

| Concept | Existing Monthly Table/Storage | Existing Common/New Table | Existing Monthly Model | Existing Common/New Model | Same Business Concept? | Historical Risk | Recommended Table Action | Recommended Model Action | Final Table | Final Model Namespace | Seeder Action | Future Phase |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| Monthly plan aggregate/version | `monthly_activities` | `ramadan_iftars` | `MonthlyActivity` | `RamadanIftar` | No; sibling aggregates | High | KEEP DOMAIN-SPECIFIC | MOVE MODEL ONLY | `monthly_activities` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.3 |
| Agenda aggregate/version | `agenda_events` | none | `AgendaEvent` | none | N/A | High | KEEP DOMAIN-SPECIFIC | MOVE MODEL ONLY | `agenda_events` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.3 |
| Ramadan aggregate/version | none | `ramadan_iftars` | none | `RamadanIftar` | New domain | High | KEEP DOMAIN-SPECIFIC | KEEP EXISTING AS-IS | `ramadan_iftars` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | keep |
| Target-group master | `target_groups` | same table | `TargetGroup` | reused model | Yes | Medium | KEEP EXISTING AS-IS | MOVE MODEL ONLY | `target_groups` | `App\Modules\Events\Models` | REFERENCE_DATA_SEED | 2.3 |
| Subject target selection | `event_target_group` + legacy scalar columns | `subject_target_groups` | pivot/no model | `SubjectTargetGroup` | Yes at target level; Common adds segment/counts | High | GENERALIZE EXISTING TABLE | MOVE MODEL ONLY; bind `SubjectTargetGroup` to generalized table | `event_target_group` (rename only after cutover if desired) | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.4 |
| New duplicate target pivot | `event_target_group` | `subject_target_groups` | pivot/no model | `SubjectTargetGroup` | Yes | High | MERGE NEW INTO EXISTING | DEPRECATE LATER after model rebind | generalized `event_target_group` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.4 |
| Beneficiary segmentation | text/age-range fields only | `beneficiary_segments` | none | `BeneficiarySegment` | No equivalent master | Low | NEW TABLE JUSTIFIED | KEEP EXISTING AS-IS | `beneficiary_segments` | `App\Modules\Events\Models` | REFERENCE_DATA_SEED | keep/2.4 |
| Execution team header | no relational header | `execution_teams` | none | `ExecutionTeam` | New header concept | Medium | NEW TABLE JUSTIFIED | KEEP EXISTING AS-IS | `execution_teams` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.5 |
| Execution team members | `monthly_activity_team` | `execution_team_members` | `MonthlyActivityTeam` | `ExecutionTeamMember` | Yes at member level | High | GENERALIZE EXISTING TABLE | MOVE MODEL ONLY; make it the Event member model | `monthly_activity_team` (future rename to `execution_team_members`) | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.5 |
| New duplicate team-member table | `monthly_activity_team` | `execution_team_members` | `MonthlyActivityTeam` | `ExecutionTeamMember` | Yes, Common adds task evidence | High | MERGE NEW INTO EXISTING | DEPRECATE LATER after model/table rebind | generalized/renamed `monthly_activity_team` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.5 |
| Volunteer summary/requirements | `monthly_activity_volunteer_needs` | `subject_volunteer_requirements` | `MonthlyActivityVolunteerNeed` | `SubjectVolunteerRequirement` | Similar, not identical cardinality | High | KEEP DOMAIN-SPECIFIC | MOVE MODEL ONLY for both Events-only models | both tables | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.6 semantic adapter audit |
| Supplies | `monthly_activity_supplies` | `subject_supplies` | `MonthlyActivitySupply` | `SubjectSupply` | Yes; Common separates planned/actual | High | GENERALIZE EXISTING TABLE | MOVE MODEL ONLY; bind Event supply model to generalized table | `monthly_activity_supplies` (future rename optional) | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.6 |
| New duplicate supplies | `monthly_activity_supplies` | `subject_supplies` | `MonthlyActivitySupply` | `SubjectSupply` | Yes | High | MERGE NEW INTO EXISTING | DEPRECATE LATER after rebind | generalized `monthly_activity_supplies` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.6 |
| Execution-need type master | `execution_need_types` | same table | `ExecutionNeedType` | reused model | Yes | Medium | ALTER EXISTING TABLE (retain as sole canonical master) | MOVE MODEL ONLY | `execution_need_types` | `App\Modules\Events\Models` | REQUIRED_PRODUCTION_SEED | 2.3/2.7 |
| Execution-need transactions | JSON payload/follow-up | `subject_execution_needs` | `MonthlyActivity` casts | `SubjectExecutionNeed` | Yes, but legacy is non-relational | High | NEW TABLE JUSTIFIED | KEEP EXISTING AS-IS | `subject_execution_needs` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.7 |
| Monitoring method | no master | `monitoring_methods` | none | `MonitoringMethod` | New master | Low | NEW TABLE JUSTIFIED | KEEP EXISTING AS-IS | `monitoring_methods` | `App\Modules\Events\Models` | REFERENCE_DATA_SEED | keep |
| Monitoring report envelope | `post_execution_payload` JSON + follow-up remarks | `monitoring_reports` | `MonthlyActivity`, `MonthlyActivityFollowup` | `MonitoringReport` | Common covers structured report; no old envelope | High | NEW TABLE JUSTIFIED | KEEP EXISTING AS-IS | `monitoring_reports` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.8 |
| Field verification | `post_execution_verifications` | `field_verifications` | `PostExecutionVerification` | `FieldVerification` | Yes; ownership/envelope differs | High | GENERALIZE EXISTING TABLE | MOVE MODEL ONLY; adapt to report ownership after staged backfill | `post_execution_verifications` (future rename optional) | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.8 |
| New duplicate field verification | `post_execution_verifications` | `field_verifications` | `PostExecutionVerification` | `FieldVerification` | Yes | High | MERGE NEW INTO EXISTING | DEPRECATE LATER after rebind | generalized `post_execution_verifications` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.8 |
| Monthly follow-up remarks | `monthly_activity_followups` | none | `MonthlyActivityFollowup` | none | No Common equivalent | Medium | KEEP DOMAIN-SPECIFIC | MOVE MODEL ONLY | `monthly_activity_followups` | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.3 |
| Monthly evaluation | evaluation tables | none | evaluation models | none | No Common equivalent | High | KEEP DOMAIN-SPECIFIC | MOVE MODEL ONLY | existing evaluation tables | `App\Modules\Events\Models` | BUSINESS_MANAGED_DATA for forms/questions | 2.3 |
| Guidance versions | none | `event_guidance_versions` | none | `EventGuidanceVersion` | New approved-content concept | Medium | NEW TABLE JUSTIFIED | KEEP EXISTING AS-IS | `event_guidance_versions` | `App\Modules\Events\Models` | BUSINESS_MANAGED_DATA / DO_NOT_AUTO_SEED | keep |
| Change requests | Monthly edit/delete request tables | `ramadan_iftar_change_requests` | Monthly request models | Ramadan request model | No; approval and revision semantics differ | High | KEEP DOMAIN-SPECIFIC | MOVE MODEL ONLY for Events-owned request models | all existing domain request tables | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | 2.3 |
| Status/type/category lookups | existing Event lookup tables | same | lookup models | same | Yes | Medium | KEEP EXISTING AS-IS | MOVE MODEL ONLY | existing lookup tables | `App\Modules\Events\Models` | REFERENCE_DATA_SEED | 2.3 |
| Mobilization methods | none | `mobilization_methods` | none | `MobilizationMethod` | New Events lookup | Low | NEW TABLE JUSTIFIED | KEEP EXISTING AS-IS | `mobilization_methods` | `App\Modules\Events\Models` | BUSINESS_MANAGED_DATA pending approved catalogue | 2.9 |
| Host/community directories | none | `community_organizations`, `local_communities` | none | corresponding Events models | New branch-owned concepts | Medium | NEW TABLE JUSTIFIED | KEEP EXISTING AS-IS | current tables | `App\Modules\Events\Models` | BUSINESS_MANAGED_DATA | keep |
| Ramadan attendee/meal/gift/program details | none | Ramadan detail tables | none | Ramadan detail models | New Ramadan concepts | Medium | KEEP DOMAIN-SPECIFIC | KEEP EXISTING AS-IS | current Ramadan detail tables | `App\Modules\Events\Models` | DO_NOT_AUTO_SEED | keep |
| Workflow definitions/runtime/history | workflow tables | same | `Workflow*` | reused | Application-wide infrastructure | High | KEEP EXISTING AS-IS | KEEP EXISTING AS-IS | current workflow tables | `App\Models` | REQUIRED_PRODUCTION_SEED for definitions only | keep |
| Roles and permissions | package/application tables | same | role/security models | reused | Application-wide infrastructure | High | KEEP EXISTING AS-IS | KEEP EXISTING AS-IS | package/application tables | `App\Models` / package namespace | REQUIRED_PRODUCTION_SEED | keep |

## 5. New Ramadan/Common table justification

| New table | Classification | Decision basis |
|---|---|---|
| `beneficiary_segments` | JUSTIFIED | No equivalent normalized segment master exists. |
| `subject_target_groups` | SHOULD MERGE INTO EXISTING | `event_target_group` already holds the same Monthly relation and IDs; extend that pivot with subject/segment/count fields. |
| `mobilization_methods`, `monitoring_methods` | JUSTIFIED | New reference concepts have no legacy masters. |
| `community_organizations`, `local_communities` | JUSTIFIED | No equivalent branch-owned relational directories were found. |
| `ramadan_iftars` and its attendee/meal/item/gift/program tables | JUSTIFIED | They are Ramadan-specific aggregates/details, not replacements for Monthly. |
| `execution_teams` | JUSTIFIED | Monthly has member rows but no normalized team header. |
| `execution_team_members` | SHOULD MERGE INTO EXISTING | Member identity overlaps `monthly_activity_team`; preserve old member IDs by generalizing/renaming the older table. |
| `subject_volunteer_requirements` | JUSTIFIED | Its repeatable, segmented requirement semantics do not safely reinterpret the one-row Monthly summary. |
| `subject_supplies` | SHOULD MERGE INTO EXISTING | It overlaps the established relational Monthly supply concept. |
| `monitoring_reports` | JUSTIFIED | Monthly has JSON evidence and remarks but no report envelope with method/submission/status. |
| `field_verifications` | SHOULD MERGE INTO EXISTING | `post_execution_verifications` already represents field-level original/corrected verification with actor/time history. |
| `event_guidance_versions` | JUSTIFIED | Versioned, approved guidance has no legacy equivalent. |
| `subject_execution_needs` | JUSTIFIED | Monthly state is embedded JSON; a normalized typed transaction cannot be achieved by generalizing a relational legacy table. |
| `ramadan_iftar_change_requests` | JUSTIFIED | It authorizes creation of a revision and intentionally differs from Monthly old/new-value edit requests. |

No introduced table is classified `REQUIRES MORE EVIDENCE`: actual migrations, models,
relations, controllers, tests, and seeders provide enough evidence for the decisions above.

## 6. Final model namespace map

### Events-only models (final namespace `App\Modules\Events\Models`)

| Models | Tables | Used by | Move required later? |
|---|---|---|---|
| `MonthlyActivity` and all `MonthlyActivity*`, `MonthlyPlan*` models | Monthly tables | Monthly/Event controllers and reports | Yes, from `App\Models` |
| `AgendaEvent`, `AgendaEventTarget`, `AgendaApproval`, `AgendaParticipation`, `AnnualAgenda*Request` | Agenda tables | Agenda and Events plan creation | Yes, from `App\Models` |
| `TargetGroup`, `ExecutionNeedType`, `EventType`, `EventCategory`, `EventStatusLookup` | Event lookup tables | Agenda/Monthly/Ramadan | Yes, from `App\Models`; cross-Event use is not global use |
| `PostExecutionVerification` and Monthly evaluation/follow-up models | existing Monthly tables | Monthly post-execution | Yes, from `App\Models` |
| `RamadanIftar*` | Ramadan tables | Ramadan | No |
| `BeneficiarySegment`, `SubjectTargetGroup`, `ExecutionTeam*`, `SubjectVolunteerRequirement`, `SubjectSupply`, `SubjectExecutionNeed` | Common Events tables (or future generalized tables) | Ramadan now; Monthly after explicit cutovers | No namespace move; some future table rebinds |
| `MonitoringMethod`, `MonitoringReport`, `FieldVerification`, `MobilizationMethod`, `CommunityOrganization`, `LocalCommunity`, `EventGuidanceVersion` | Events reference/operational tables | Ramadan now; selected future Event flows | No |

Moving `MonthlyActivity` and `AgendaEvent` is a namespace-only concern with broad import and
polymorphic `entity_type` implications. Phase 2.3 must either preserve stored class aliases
or migrate type strings atomically; a blind PHP file move is not approved.

### True application-common models (remain `App\Models`)

| Models | Why application-common |
|---|---|
| `Workflow`, `WorkflowStep`, `WorkflowInstance`, `WorkflowLog`, `WorkflowActionLog` | The dynamic workflow engine is infrastructure, and repository callers are not limited to one Event aggregate. Stored `entity_type` also makes namespace stability historically significant. |
| `User`, `Role`, `Branch`, `Center`, `Department` and authorization models | They are organizational/security concepts consumed by unrelated modules. |
| Official correspondence, donation, workshop, communication, and file infrastructure models | Their relations reach Events, but their ownership and lifecycle are not Events-only. |

The present split of Event-only lookup and Monthly models under `App\Models` and newer
Common models under the Events module is therefore transitional mixed ownership, not the
desired end state.

## 7. Relationship and raw-reference blast radius

Repository inspection found these coupling forms that future migrations must account for:

- `MonthlyActivity` directly declares `belongsTo`, `hasMany`, and `belongsToMany` relations
  to `target_groups`, `event_target_group`, team, volunteer, supplies, attachments,
  approvals, follow-ups, evaluation, request, Agenda, and workflow models.
- `RamadanIftar` filters Common rows with both `subject_id` and the server-controlled
  `subject_type`; changing a table without preserving both predicates risks numeric-ID
  collision across subject classes.
- focused Monthly controllers and their concerns directly read/write legacy JSON and
  legacy relations. Reports, Blade forms, and tests also rely on those casts and relation
  names. They require staged model compatibility, not a table-only rename.
- migrations and tests contain literal table names and foreign-key targets. Seeders import
  current model namespaces. `WorkflowInstance.entity_type`, request `entity_type`, and
  workflow action logs store class/type identity and are higher risk than ordinary imports.
- `event_target_group`, `monthly_activity_team`, `monthly_activity_supplies`, and
  `post_execution_verifications` have uniqueness/index rules that differ from the Common
  replacements. A future migration must create new indexes before relaxing old constraints.
- cascading deletes exist throughout both sets of tables. Consolidation must use insert/
  update-only transactions and must not use model deletion as a transfer mechanism.

Raw-table search scope completed across `app`, `routes`, `database`, `tests`, `resources`,
and `config`. The dominant references are Eloquent relationship declarations, migrations,
focused Monthly controllers/services, Ramadan services, seeders, and schema assertions.
No evidence supports a safe one-step table rename or model namespace move.

## 8. Seeder and bootstrap audit

| Data | Seeder/current source | Idempotent? | Classification | Decision |
|---|---|---:|---|---|
| `target_groups` | `TargetGroupSeeder::updateOrCreate` | Yes | REFERENCE_DATA_SEED | Keep as the shared Events catalogue; preserve business additions. |
| `beneficiary_segments` | `BeneficiarySegmentSeeder::updateOrCreate` | Yes | REFERENCE_DATA_SEED | Keep; production installation requires this reference catalogue. |
| `execution_need_types` | `CanonicalExecutionNeedTypeSeeder::updateOrCreate` | Yes | REQUIRED_PRODUCTION_SEED | This is the authoritative canonical bootstrap. Retire the older overlapping `ExecutionNeedTypeSeeder` only in Phase 2.9 after install/upgrade ordering is proven. |
| `monitoring_methods` | `MonitoringMethodSeeder::updateOrCreate` | Yes | REFERENCE_DATA_SEED | Keep cameras/field-visit bootstrap; business can manage active state. |
| `event_guidance_versions` | application-managed publishing | N/A | BUSINESS_MANAGED_DATA + DO_NOT_AUTO_SEED | Never synthesize approved production guidance or acceptance. |
| `mobilization_methods` | no seeder found | N/A | BUSINESS_MANAGED_DATA | Establish an approved catalogue before considering a reference seed; do not invent values. |
| community/local directories | branch users | N/A | BUSINESS_MANAGED_DATA + DO_NOT_AUTO_SEED | Real contacts/locations must not be fabricated. |
| `event_types`, `event_categories`, `event_status_lookups` | corresponding idempotent seeders | Yes | REFERENCE_DATA_SEED | Keep and later update imports after namespace consolidation. |
| `workflows`, `workflow_steps` | `WorkflowSeeder::updateOrCreate` plus stale-step cleanup | Yes, definition-driven | REQUIRED_PRODUCTION_SEED | Required for runtime approval flows; deploy after roles/permissions. |
| permissions and roles | permission/role seeders | Designed as repeatable bootstrap | REQUIRED_PRODUCTION_SEED | Application-wide prerequisite; not owned by Events consolidation. |
| showcase seeders | Agenda/Monthly/workflow/post-execution showcase seeders | Environment data | TEST_DEV_FIXTURE | Never run as production reference bootstrap. |

`CanonicalExecutionNeedTypeSeeder` and `config/execution_needs.php` have distinct roles:
the seeder owns canonical type rows; configuration retains Monthly decision-role and center-
availability behavior. A future normalized write must not treat configuration as row data or
change those business mappings during backfill.

## 9. Historical Monthly safety gates

Every proposed in-place generalization must satisfy all five historical questions:

| Gate | Required proof before cutover |
|---|---|
| Meaning retained | Legacy columns and casts continue to resolve exactly until normalized equivalence is reconciled row by row. |
| Foreign IDs retained | Existing target/member/supply/verification row IDs stay in their original tables; new subject ownership is added to those rows. |
| Reports retained | Monthly report queries run against compatibility relations during dual-read comparison and produce identical filters/totals. |
| Audit retained | Workflow/action logs, change logs, approvals, actor IDs, timestamps, and stored entity types are not rewritten as a side effect. |
| Archived/deleted records retained | Backfills include soft-deleted Monthly aggregates via explicit `withTrashed` handling and never cascade-delete child rows. |

The JSON transitions are **HIGH** risk because embedded entries have no database primary
keys, old codes need canonical mapping, malformed/unknown keys may exist, and follow-up
decisions must remain attributable. Phase 2.7 must be additive, preserve JSON as the audit
source, record reconciliation failures, and prohibit cleanup until multiple releases after
read/write cutover.

## 10. Proposed final Events schema

| Final table | Purpose / owner | Model(s) | Monthly | Ramadan | Agenda | End-state action |
|---|---|---|:---:|:---:|:---:|---|
| `monthly_activities` | Monthly aggregate | `MonthlyActivity` | Yes | No | source link only | keep domain-specific |
| `agenda_events` and Agenda detail/history tables | Agenda aggregate | Agenda models | source | source | Yes | keep domain-specific |
| `ramadan_iftars` and Ramadan detail/history tables | Ramadan aggregate | Ramadan models | No | Yes | source link | keep domain-specific |
| `target_groups` | Events target master | `TargetGroup` | Yes | Yes | future only | keep |
| generalized `event_target_group` | subject targeting, segment/count data | `SubjectTargetGroup` | Yes | Yes | future only | alter in place; merge new table |
| `beneficiary_segments` | Events segmentation master | `BeneficiarySegment` | future | Yes | future | keep new |
| `execution_teams` | Event team headers | `ExecutionTeam` | future | Yes | No | keep new |
| renamed/generalized `monthly_activity_team` | Event team members | `ExecutionTeamMember` | Yes | Yes | No | alter/rename; merge new member rows |
| `monthly_activity_volunteer_needs` | legacy Monthly summary | `MonthlyActivityVolunteerNeed` | Yes | No | No | keep domain-specific |
| `subject_volunteer_requirements` | repeatable Event requirements | `SubjectVolunteerRequirement` | future optional | Yes | No | keep new |
| generalized `monthly_activity_supplies` | Event supplies | `SubjectSupply` | Yes | Yes | No | alter in place; merge new table |
| `execution_need_types` | canonical Events execution-need master | `ExecutionNeedType` | Yes | Yes | potential | keep/alter only |
| `subject_execution_needs` | normalized Event execution-need transactions | `SubjectExecutionNeed` | future | Yes | potential | keep new; migrate JSON later |
| `monitoring_methods`, `monitoring_reports` | Event monitoring master/envelope | monitoring models | future | Yes | No | keep new |
| generalized `post_execution_verifications` | field comparison evidence | `FieldVerification` | Yes | Yes | No | alter in place; merge new table |
| `event_guidance_versions` | approved Events guidance versions | `EventGuidanceVersion` | no requirement | Yes | No | keep new, business-managed |
| Event lookup/reference tables | Events catalogues/directories | Events lookup models | selected | selected | selected | keep |
| workflow/security tables | application-wide infrastructure | `App\Models\Workflow*` / security models | Yes | Yes | Yes | keep globally owned |

## 11. Future migration sequence and risks

| Phase | Scope | Risk | Required safety mechanism |
|---|---|---|---|
| **2.3 — Pre-release schema consolidation** | Generalize the four established tables, rebind Ramadan, and remove four unshipped duplicate create migrations | High | retain established IDs, one runtime path, fresh-install schema assertions |
| **2.4 — Event model namespace consolidation** | Move remaining Events-only PHP models; preserve aliases and stored morph/entity types | Medium | import inventory, class aliases/morph compatibility, route/job serialization regression |
| **2.5 — Volunteer semantic adapter audit** | Decide whether the Monthly summary and repeatable requirements should remain separate permanently | Medium | explicit cardinality and planned/actual semantic proof |
| **2.6 — Execution Needs migration** | Backfill JSON into `subject_execution_needs`, then dual-read/write validation | High | canonical-code mapping, unknown-key quarantine, immutable JSON fallback, audit comparison |
| **2.7 — Monthly monitoring report migration** | Add report envelopes for Monthly while retaining generalized historical verification rows | High | preserve corrected/original semantics and actor timestamps; report/verification FK bridge |
| **2.8 — Seeder/bootstrap completion** | Resolve overlapping execution-need seeders and approved missing catalogues | Medium | production bootstrap order, idempotency and business-content approval |
| **2.9 — Legacy compatibility cleanup** | Consider old columns/JSON and aliases only after all cutovers | High | multi-release telemetry plus reports/audit/archive regression |

After the Phase 2.3 pre-release consolidation, the smallest safe implementation slice is
Events model namespace consolidation. It can establish coherent Events ownership without
changing table identity or production data, but must preserve stored class names before
moving `MonthlyActivity` or `AgendaEvent`.

## 12. Phase outcome

- **Existing relational identity wins:** target selection, team-member, supply, and field-
  verification consolidation starts from the older Monthly tables.
- **New tables remain where justified:** segmentation, team headers, normalized execution
  needs, monitoring report envelopes/methods, guidance, directories, and Ramadan details.
- **Domain-specific differences remain explicit:** Monthly/Ramadan aggregates, volunteer
  summary versus repeatable requirements, follow-ups/evaluations, and change-request flows
  are not forcibly merged.
- **Namespace boundary is decided:** Events-only models converge under the Events module;
  workflow, organization, user, and security infrastructure remains application-global.
- **No runtime work occurred:** this document is the sole Phase 2.2 artifact.

**PHASE 2.2 COMPLETE**

## 13. Phase 2.3 implementation outcome

Phase 2.3 implemented only the four pre-release consolidation decisions below. The newer
tables had not been deployed or accepted as historical storage, so their create migrations
were removed rather than followed by misleading create-then-drop migrations.

| Final table | Removed development table | Removed create migration | Final model | Ramadan path | Monthly historical path |
|---|---|---|---|---|---|
| `event_target_group` | `subject_target_groups` | `2026_09_10_000300_create_subject_target_groups_table.php` | `SubjectTargetGroup` rebound to the established pivot | planning, workspace, and revision copying use the rebound relationship | existing pivot IDs and `monthly_activity_id` remain intact |
| `monthly_activity_team` | `execution_team_members` | `2026_09_10_001500_create_execution_team_members_table.php` | `MonthlyActivityTeam` repurposed as the shared member model | team planning, execution task confirmation, and revision copying use `ExecutionTeam::members()` | legacy model, table, member IDs, and Monthly relationships remain intact |
| `monthly_activity_supplies` | `subject_supplies` | `2026_09_10_001700_create_subject_supplies_table.php` | `MonthlyActivitySupply` repurposed as the shared supply model | planning, actual updates, monitoring candidates, workspace, and revision copying use the rebound relation | legacy quantity/availability fields and IDs remain intact |
| `post_execution_verifications` | `field_verifications` | `2026_09_10_001900_create_field_verifications_table.php` | `PostExecutionVerification` repurposed as the shared verification model | report editing, mismatch review, display, and closure's approved-report path use `MonitoringReport::verifications()` | legacy original/corrected/status fields, IDs, branch ownership, and relations remain intact |

The forward migration
`2026_09_14_000100_generalize_existing_event_detail_tables.php` adds only the nullable
ownership and Common-detail columns required by these bindings. It backfills subject aliases
on existing targeting and supply rows without changing their primary keys. There is one
runtime table and one model path per consolidated concept; no dual-write or fallback adapter
was introduced.

The justified tables listed in the Phase 2.2 decision matrix—including `execution_teams`,
`subject_volunteer_requirements`, `subject_execution_needs`, `monitoring_reports`, guidance,
segments, methods, and directories—remain unchanged.

**PHASE 2.3 COMPLETE**
