# Events Phase 1.2 architecture review

## Scope and repository state

This review covers the proposed **Phase 1.2 — Common Lookups and Shared Models** only. It does not authorize or implement model moves, schema changes, controller moves, route changes, morph-map enforcement, or Ramadan behavior.

The checked-out branch does **not** currently contain the Phase 1.1 files described in the task context: there is no tracked `app/Modules/Events` directory and no `EventSubjectTypes` class. The current `HEAD` is the Phase-0 commit. Phase 1.1 must therefore be restored or merged and verified before Phase 1.2 implementation begins; this report does not silently recreate it.

## 1. Existing shared models and lookups

| Candidate | Current namespace/file | Responsibility and usage | Classification |
|---|---|---|---|
| `TargetGroup` | `App\Models\TargetGroup` (`app/Models/TargetGroup.php`) | Active/sorted target-group definitions. Read and administered by `EventLookupsController`; consumed by both `AgendaEventsController` and `MonthlyActivitiesController`. Monthly Activities currently stores a legacy direct FK and an `event_target_group` pivot. | Genuinely shared Event lookup, but current storage is Monthly-Activity-shaped. |
| `EventStatusLookup` | `App\Models\EventStatusLookup` (`app/Models/EventStatusLookup.php`) | Module-scoped status labels for `agenda` and `monthly_activities`; used by Agenda, Monthly Activities, lookup administration, and `AgendaWorkflowPresenter`. Its `labelFor()` embeds translation fallbacks for both flows. | Shared lookup with presentation coupling. |
| `EventCategory` | `App\Models\EventCategory` (`app/Models/EventCategory.php`) | Department-owned agenda category; consumed by Agenda and enterprise reporting and administered from the mixed lookup screen. | Agenda-specific today, despite its generic name. |
| `EventType` | `App\Models\EventType` (`app/Models/EventType.php`) | Simple lookup related from `MonthlyActivity::eventType()`. It is seeded but has almost no runtime use beyond that relationship. This is distinct from Agenda's raw `event_type` values `mandatory` / `optional`. | MonthlyActivity-specific today; name overlaps a different Agenda concept. |
| `ExecutionNeedType` | `App\Models\ExecutionNeedType` (`app/Models/ExecutionNeedType.php`) | Seeded definition table for execution-need codes. Current Monthly Activities runtime primarily uses JSON payloads and `config/execution_needs.php`; no production consumer of the model was found. | Intended shared lookup, not yet integrated. |
| `Department` / `DepartmentUnit` | `App\Models` | Organization structure used far beyond Events. `EventLookupsController` happens to administer it. | Cross-application, not Event-owned. |
| `EvaluationQuestion`, `EvaluationForm` | `App\Models` | Evaluation configuration and scoring; currently bound to Monthly Activity evaluation tables and services. | Evaluation subsystem, not a Common Event lookup yet. |
| `ZahaTimeOption` | `App\Models\ZahaTimeOption` | Option list administered by `EventLookupsController` and embedded in Monthly Activity execution-needs payloads. | Program/MonthlyActivity-specific today. |
| `AgendaEvent` and related target/participation/approval models | `App\Models` | Annual-agenda aggregate, audience selection, approvals, and workflow link. | Agenda-specific aggregate. |
| `MonthlyActivity` and `MonthlyActivity*` models | `App\Models` | Current monthly aggregate and dedicated attendance, supplies, team, attachment, approval, follow-up, sponsor, partner, evaluation, and change-request storage. | MonthlyActivity-specific legacy implementation. |
| `Attachment` | `App\Models\Attachment` | Generic `attachable()` morph relation. Current Monthly Activity and Maintenance upload controllers instead use their dedicated attachment models/tables. | Application-wide polymorphic infrastructure, not Event-owned. |
| Workflow models/services | `App\Models\Workflow*`, `App\Services\DynamicWorkflowService`, governance and notification services | Generic workflow configuration and runtime, shared by Agenda, Monthly Activities, and request entities through `entity_type` / `entity_id`. | Application-wide infrastructure. |
| `OfficialCorrespondence` | `App\Models\OfficialCorrespondence` | Generic `correspondable()` polymorphic record; currently exposed by Monthly Activity. | Cross-domain candidate, not proven as Events-only. |
| `CommunicationsRequest`, `WorkshopsRequest`, `MonthlyActivityTeam` | `App\Models` | Each has a direct `monthly_activity_id` and Monthly Activity controller/workflow assumptions. | MonthlyActivity-specific. |
| Activity evaluation models | `App\Models\ActivityEvaluation*`, `PostExecutionVerification` | Direct `monthly_activity_id`, branch scope, evaluation policy and service. | MonthlyActivity-specific at present. |

There are no `app/Enums` or `app/Repositories` directories in the current tree. `app/Support` contains focused presentation/value helpers; none is a proven reusable Common lookup abstraction.

## 2. Recommended Phase 1.2 moves and additions

No existing production model should be physically moved in Phase 1.2. Namespace moves would require widespread import updates and, for morph-participating classes, could alter database-facing FQCN values. Compatibility aliases would add complexity without delivering shared storage.

The safe implementation candidates are additions, not moves:

| Current location | Proposed location | Recommendation and reason | Compatibility | Risk |
|---|---|---|---|---|
| Phase 1.1 `EventSubjectTypes` (currently absent from this checkout) | `App\Modules\Events\Models\EventSubjectTypes` | Restore the small constants/mapping class first. Separate reserved aliases from models registered at runtime. | Preserve `monthly_activity` and `ramadan_iftar`; register only `MonthlyActivity`. | Low once restored. |
| No applicability model yet | `App\Modules\Events\Models\LookupApplicability` | Add only together with its reviewed schema. It should express which lookup record applies to which reserved Event subject alias without boolean columns per event type. | New table/model; no existing reads or writes replaced. | Medium. |
| Existing `TargetGroup` remains in place | New applicability rows reference `target_groups` | Reuse the existing lookup table initially; do not duplicate or move it. Add applicability alongside current behavior, with no controller switch yet. | Existing IDs, pivot, and routes remain intact. | Medium. |
| Existing `ExecutionNeedType` remains in place | New applicability rows reference `execution_need_types` | Reuse the existing definition table only after seed codes are reconciled with `config/execution_needs.php`. Do not make Monthly Activities read it yet. | Existing JSON/config behavior remains authoritative during this slice. | Medium. |
| No beneficiary-segment lookup | `App\Modules\Events\Models\BeneficiarySegment` | A genuinely new shared lookup for children/youth/young adults/women/other may be introduced because it is not equivalent to `TargetGroup`. | Additive only; no placeholder owner models. | Low/Medium. |

If a single generic `lookup_applicabilities` table cannot enforce referential integrity because it uses polymorphic lookup references, prefer explicit applicability tables per lookup family or a constrained `lookup_type` allow-list. Do not accept arbitrary FQCN lookup types. That schema choice must be settled before migration code is written.

## 3. Components that must stay where they are

- `App\Models\MonthlyActivity`, `AgendaEvent`, and every directly related aggregate model must remain in `App\Models` during this phase. Moving them would create namespace churn and may change morph values.
- `MonthlyActivityTeam`, `MonthlyActivitySupply`, `MonthlyActivityVolunteerNeed`, `MonthlyActivityAttachment`, `CommunicationsRequest`, `WorkshopsRequest`, attendance, follow-up, sponsor, partner, evaluation, and change-request models remain MonthlyActivity-specific because their schema contains `monthly_activity_id` or their behavior assumes that aggregate.
- `AgendaEventTarget`, `AgendaParticipation`, `AgendaApproval`, and annual agenda change requests remain Agenda-specific.
- Workflow models and `DynamicWorkflowService`, `WorkflowGovernanceService`, workflow notification services, and presenters remain application-level infrastructure. Common Events may consume workflow later but must not own it.
- `Attachment`, `AuditLog`, `OfficialCorrespondence`, notifications, permissions, departments, branches, users, finance, transport, and maintenance remain outside Events because their ownership is broader than Events.
- `EventLookupsController` must not move yet. It mixes target groups and Event statuses with departments, department units, evaluation questions, and Zaha Time options; moving it wholesale would incorrectly claim non-Event configuration and would violate the no-controller-movement constraint.
- `config/monthly_activity.php` and `config/execution_needs.php` remain in place because current controllers/models read them directly. Renaming or relocating them would be a behavior change.
- All existing migrations remain immutable. New migrations, when approved, must be additive.

## 4. Duplicate and overlapping lookups

1. **Event type means two different things.** Agenda validates raw `mandatory` / `optional`, while Monthly Activity has an `event_type_id` relation to `event_types`. These must not be merged until their semantics are reconciled.
2. **Target groups have three representations.** Monthly Activity has `target_group`, `target_group_id`, `target_group_other`, and the `event_target_group` many-to-many table; Agenda also carries monthly-template target IDs as a serialized string. Phase 1.2 should centralize definitions only, not selections.
3. **Execution needs have competing sources.** `execution_need_types` is seeded, `config/execution_needs.php` owns decision routing and availability rules, and Monthly Activity stores payload/follow-up JSON. Codes must be compared one by one before applicability data is seeded.
4. **Status modules are raw strings.** `agenda` and `monthly_activities` occur in lookup validation, presenters, workflow calls, services, and translations. A small constants source can prevent further duplication, but existing database values must remain unchanged.
5. **Status labels overlap database lookup and translation fallback.** `EventStatusLookup::labelFor()` mixes persistence with UI translation policy. Do not move it as-is into a shared model; first characterize fallback behavior, then optionally extract a presenter in a later logic phase.
6. **The lookup admin controller is an aggregation point, not a domain boundary.** Its mixed screen should not dictate model ownership.

## 5. EventSubjectTypes review

The intended lightweight class should explicitly distinguish:

- **reserved aliases**: all stable values allowed by architecture (`monthly_activity`, `ramadan_iftar`);
- **registered model mappings**: only aliases whose real production model exists (`monthly_activity` today);
- **runtime resolution**: lookup against `registeredModels()` only, rejecting unknown values, raw FQCN input, and reserved-but-not-yet-registered Ramadan.

Recommended API:

```php
public const MONTHLY_ACTIVITY = 'monthly_activity';
public const RAMADAN_IFTAR = 'ramadan_iftar';

public static function reserved(): array;
public static function registeredModels(): array;
public static function modelFor(string $type): string; // throws when unregistered
```

Avoid naming the mapping `morphMap()` until it is actually safe to register globally; that name can imply boot-time enforcement that does not exist. Do not add a registry/resolver/interface hierarchy in Phase 1.2.

## 6. Morph compatibility risks

Global morph enforcement is currently unsafe:

- `workflow_instances.entity_type` is populated and queried with model FQCNs for `MonthlyActivity`, `AgendaEvent`, `MonthlyPlanEditRequest`, `MonthlyPlanDeleteRequest`, `AnnualAgendaEditRequest`, and `AnnualAgendaDeleteRequest`.
- `DynamicWorkflowService` treats `entity_type` as a class name and executes `::query()` on it.
- `workflow_action_logs`, audit logs, notification metadata, seeders, reports, and workflow/change-request services compare or emit FQCN values.
- `OfficialCorrespondence::correspondable()` and `Attachment::attachable()` are Laravel morph relations that can contain FQCNs. The generic attachments table has no discriminator constraint or migration to aliases.
- Payments use a `payable` morph relation for bookings, donations, and Zaha Time bookings; Spatie permissions also use polymorphic model tables. Global enforcement affects these non-Event systems too.
- `agenda_event_targets` and `agenda_participations` use type/id pairs with domain aliases such as `branch` and `department_unit`, but most of these are manual relationships rather than a complete global Laravel morph map.

Before `Relation::enforceMorphMap()` can be enabled, inventory distinct values from every actual morph column in the deployed database, define aliases for every active Laravel morph class (including non-Event classes), update all FQCN comparisons and dynamic resolution, migrate values transactionally, and test rollback/hydration. None of that belongs in Phase 1.2.

## 7. Dependency graph

```text
AgendaEvent
  -> EventCategory, TargetGroup (controller/template), departments/units
  -> Agenda approvals/change requests
  -> WorkflowInstance -> generic workflow services
  -> AgendaWorkflowBridgeService -> MonthlyActivity   [intentional legacy bridge]

MonthlyActivity
  -> AgendaEvent (optional source link)
  -> TargetGroup, EventType
  -> dedicated team/supply/volunteer/attachment models
  -> communications/workshops/correspondence
  -> post-execution/evaluation models and services
  -> approvals/change requests
  -> WorkflowInstance -> generic workflow services

Common lookup definitions
  -> must not depend on AgendaEvent or MonthlyActivity
  -> applicability may depend only on stable subject aliases

EventSubjectTypes
  -> currently maps monthly_activity -> legacy MonthlyActivity class
  -> must not map Ramadan until its real model exists
```

Circular-dependency risks:

- Moving `AgendaWorkflowBridgeService` into Common would make Common depend on both Agenda and Monthly Activities.
- Making lookup models call aggregate-specific controllers/services would reverse the desired dependency direction.
- Making generic workflow resolve through an Events-only subject map would couple application-wide workflow to Events and break request-entity workflows.
- Reusing MonthlyActivity-specific teams, communications, workshops, or evaluations as Common models would make future Ramadan depend indirectly on `monthly_activities`.

## 8. Proposed final Phase 1.2 structure

Subject to schema approval and after Phase 1.1 is present, keep the module shallow:

```text
app/Modules/Events/
├── Http/
│   └── Controllers/
│       ├── Common/
│       ├── MonthlyActivities/
│       └── Ramadan/
└── Models/
    ├── EventSubjectTypes.php
    ├── BeneficiarySegment.php
    └── LookupApplicability.php
```

Only create the latter two models when their migrations are implemented. Existing `TargetGroup` and `ExecutionNeedType` stay in `App\Models` for backward compatibility. No empty service, enum, contract, repository, or nested submodule directories are recommended.

## 9. Files to change in implementation

### Add

- A reviewed additive migration for beneficiary segments, if that lookup is approved for this slice.
- A reviewed additive applicability migration after choosing an integrity-safe lookup-family design.
- `app/Modules/Events/Models/BeneficiarySegment.php` only with its real table.
- `app/Modules/Events/Models/LookupApplicability.php` only with its real table.
- focused factories/seeders only where tests or deterministic baseline data require them.
- unit/feature tests for aliases, applicability constraints, lookup scopes, and compatibility.

### Update

- The restored Phase 1.1 `EventSubjectTypes` to expose `reserved()` separately from `registeredModels()` and reject reserved-but-unregistered types.
- Seeder registration only after seed codes and idempotency are reviewed.
- concise Events structure documentation with the approved schema and ownership rules.

### Move

- None in Phase 1.2.

### Leave untouched

- Existing controllers and routes.
- All existing aggregate models and relationships.
- Workflow, approvals, attachment, communication, workshop, team, and evaluation production code.
- Existing database migrations and stored `entity_type` values.
- Existing Monthly Activity configuration and storage.

## 10. Required tests

- Stable reserved subject aliases and exact database-facing values.
- `registeredModels()` contains `monthly_activity` only until a real Ramadan model exists.
- `ramadan_iftar`, arbitrary strings, and `App\Models\User` cannot resolve as registered subjects.
- Lookup applicability accepts only approved subject aliases and lookup families; duplicate applicability rows are rejected.
- Active/ordered lookup behavior and `other` semantics for target groups and beneficiary segments.
- Existing `TargetGroup` IDs and Monthly Activity pivot behavior remain unchanged.
- Existing execution-needs config/JSON behavior remains unchanged while definitions are introduced alongside it.
- Legacy `WorkflowInstance` relationships still hydrate FQCN values for Monthly Activity, Agenda, and change-request entities.
- Representative `OfficialCorrespondence`, Payment, permission, and Attachment morph relationships continue to hydrate without a global enforced map.
- Existing Phase-0 route, authorization, branch-scope, Agenda, Monthly Activity, approval, workflow, notification, and Feature suites remain green.
- Static assertion that no `Relation::enforceMorphMap()` call is introduced in this slice.

## 11. Risk assessment

| Change | Risk | Reason / mitigation |
|---|---|---|
| Restore and clarify `EventSubjectTypes` | Low | Pure allow-list/constants; test exact values and rejection paths. |
| Add beneficiary-segment lookup table/model | Low/Medium | Additive, but names, age semantics, localization, and `other` rules require product confirmation. |
| Add applicability schema/model | Medium | Generic references can weaken FK integrity; approve lookup-family design and unique constraints first. |
| Seed applicability against current lookup rows | Medium | IDs vary; resolve by stable codes, which `TargetGroup` currently lacks. |
| Add stable `code` to existing target groups | Medium/High | Requires backfill and uniqueness decisions; defer unless explicitly designed. |
| Move existing lookup models into Events | Medium/High | Widespread namespace/import churn with no behavior benefit. Do not do it now. |
| Switch Monthly Activities to new Common reads | High | Migration/dual-read concern explicitly outside Phase 1.2. |
| Enforce a global morph map | High | Breaks legacy FQCN and non-Event morph systems. Explicitly forbidden. |

## 12. Safest implementation order

1. Restore or merge the reviewed Phase 1.1 lightweight structure and `EventSubjectTypes`; run its tests and the Phase-0 safety suite.
2. Confirm product-owned lookup values: beneficiary segments, `other` behavior, and which existing target/execution-need definitions apply to each Event subject.
3. Reconcile `ExecutionNeedType` seed codes against `config/execution_needs.php`; do not change current Monthly Activity reads.
4. Choose an applicability schema that has an explicit lookup-family allow-list and uniqueness guarantees without accepting arbitrary FQCNs.
5. Add one lookup family and its model/migration first (prefer beneficiary segments because it is additive and independent).
6. Add applicability storage and tests, registering only `monthly_activity`; keep Ramadan reserved but unregistered until its aggregate exists.
7. Seed by stable codes with idempotent seeders; if existing lookups lack codes, stop and approve a backfill design rather than matching translated names.
8. Run focused lookup tests, all Phase-0 Events tests, relevant Agenda/Monthly/workflow tests, then the Feature suite.
9. Do not connect existing Monthly Activity controllers or relationships to new Common storage in this phase.

## Decision

**PHASE 1.2 NEEDS PREPARATION FIRST**

The blocker is concrete: the stated Phase 1.1 module and `EventSubjectTypes` are absent from the checked-out branch. In addition, applicability seeding needs stable lookup codes and an integrity-safe schema decision. The next smallest implementation slice is: **restore and verify the lightweight Phase 1.1 `EventSubjectTypes`, separating reserved aliases from registered runtime model mappings, without enabling a morph map or changing production behavior.**
