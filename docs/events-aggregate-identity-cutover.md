# Phase 2.14 — MonthlyActivity / AgendaEvent aggregate identity compatibility audit

Date: 2026-09-15

Status: `PHASE 2.14 COMPLETE`

This is a source-backed design audit. It moves no model, changes no writer, and
does not mutate data. Repository behavior is authoritative; live counts remain
unknown because the Laravel runtime and a disposable database are unavailable.

## 1. Decision

**Selected ordering: `SEPARATE CUTOVERS, AGENDA FIRST`.**

Both aggregates require an exact, Events-scoped legacy/canonical identity map,
dual-read workflow/request/history consumers, find-before-create protection,
and a legacy writer during a compatibility deployment. `MonthlyActivity`
additionally requires proven bidirectional compatibility for the
`OfficialCorrespondence` polymorphic relationship. `AgendaEvent` has no
correspondence morph surface in current source and is therefore the smaller
first cutover.

Direct backfill followed by a move is rejected. Compatibility must precede any
canonical write. The request-model and aggregate-model identities are separate
contracts and their cutovers must not be combined.

## 2. Aggregate inventory

| Aggregate | Current FQCN | Table | Workflow module | Primary identity risk |
|---|---|---|---|---|
| Monthly activity | `App\Models\MonthlyActivity` | `monthly_activities` | `monthly_activities` | workflow instances/actions, audit rows, request rows, notification metadata, and official-correspondence morph rows |
| Agenda event | `App\Models\AgendaEvent` | `agenda_events` | `agenda` | workflow instances/actions, audit rows, request rows, and notification metadata |

Both models use `SoftDeletes`, default numeric route keys, and a
`morphOne(WorkflowInstance::class, 'entity')` relationship. Neither overrides
`getRouteKeyName()` or `resolveRouteBinding()`.

## 3. Identity storage matrix

| Storage | Identity contract | Monthly value | Agenda value | Namespace-safe? |
|---|---|---|---|---|
| `workflow_instances.entity_type` | persisted Eloquent model FQCN | `App\Models\MonthlyActivity` | `App\Models\AgendaEvent` | No |
| `workflow_action_logs.entity_type` | persisted aggregate FQCN for aggregate/request actions | `App\Models\MonthlyActivity` | `App\Models\AgendaEvent` | No for exact filters; not dynamically resolved |
| `audit_logs.entity_type` | writer-selected type; aggregate FQCN for identified actions | `App\Models\MonthlyActivity` | `App\Models\AgendaEvent` | No for exact filters; not dynamically resolved |
| Monthly/Agenda request-table `entity_type` | aggregate FQCN, not request FQCN | `App\Models\MonthlyActivity` | `App\Models\AgendaEvent` | No |
| `in_app_notifications.meta.entity_type` | scalar metadata copied from `get_class()` or request rows | current aggregate FQCN | current aggregate FQCN | No, although current readers do not resolve it |
| `official_correspondences.correspondable_type` | Eloquent polymorphic type | `App\Models\MonthlyActivity` | not used | No |
| Common Events `subject_type` | stable alias | `monthly_activity` | no registered Agenda alias | Yes for Monthly |
| Agenda participations `entity_type` | business discriminator | not applicable | `branch` / `department_unit` | Yes; not a model identity |
| ordinary `monthly_activity_id` / `agenda_event_id` FKs | numeric FK | numeric ID | numeric ID | Yes |

The Common Events alias `monthly_activity` must not be confused with aggregate
FQCN storage. No new alias is proposed, and `AgendaEvent` is not registered as a
Common Events subject.

## 4. Workflow instance contract

### Writers

`DynamicWorkflowService::forModel()` passes `get_class($model)` and its key to
`forEntity()`. Outside the four Phase 2.13A request identities, `forEntity()`
still performs `firstOrCreate` using the supplied exact class string. Thus both
aggregate writers currently emit legacy FQCNs.

### Readers and resolution

* Both aggregate models use `morphOne`, which matches their current morph class
  exactly.
* `DynamicWorkflowService::resolveEntity()` treats the stored value as a PHP
  class and executes that class's query. A future canonical string cannot be
  resolved before the future class is installed.
* Monthly approval/planning/change-request/trash code contains additional exact
  `MonthlyActivity::class` filters.
* Agenda approval-state code contains an exact `AgendaEvent::class` filter.
* Admin reporting joins/counts aggregate workflow identities by exact class.

The database unique key is `(workflow_id, entity_type, entity_id)`. It permits
two rows for the same logical aggregate when legacy and canonical FQCNs differ.
Future compatibility must query both exact identities, reject more than one
match, reuse one match, and retain the legacy writer until cutover.

## 5. Workflow action-log contract

`workflow_action_logs` stores `module`, `entity_type`, `entity_id`, action,
status, actor, notes, metadata, and time. Monthly aggregate approval and
lifecycle writers use `MonthlyActivity::class`; Agenda approval writers use
`AgendaEvent::class`; request-created entries use the request's aggregate class.

The model has no polymorphic resolver. These values are historical/display
identities, not dynamic request-model identities. Monthly trash history does,
however, filter the type exactly. No equivalent exact Agenda action-log reader
was found. Future code must dual-read every exact aggregate filter; generic
history remains readable without a backfill.

## 6. Audit-log contract

| Aggregate | Writer | Action | Identity source |
|---|---|---|---|
| Monthly | `ActivityEvaluationService::storeEvaluation()` | `activity_evaluated` | `MonthlyActivity::class` |
| Agenda | `AgendaEventsController::updateUnitParticipation()` | unit participation update | `AgendaEvent::class` |

`AuditLog` has no aggregate `morphTo` relationship, and current generic admin
audit reports do not dynamically instantiate `entity_type`. The
`TrackUserOperations` middleware is a different contract: it may store a route
parameter name rather than an aggregate FQCN. A migration must therefore update
only proven FQCN rows, never every audit `entity_type` value indiscriminately.

No current exact Monthly/Agenda audit reader was found. Historical FQCN rows
can remain during compatibility, but future exact filters must accept both.

## 7. Request-row aggregate identity contract

`PlanChangeRequestWorkflowService` explicitly writes aggregate identity into:

| Table | Aggregate identity |
|---|---|
| `monthly_plan_edit_requests` | `App\Models\MonthlyActivity` |
| `monthly_plan_delete_requests` | `App\Models\MonthlyActivity` |
| `annual_agenda_edit_requests` | `App\Models\AgendaEvent` |
| `annual_agenda_delete_requests` | `App\Models\AgendaEvent` |

The request models relate to aggregates by `entity_id`; current source does not
dynamically instantiate request-row `entity_type`. That does not make the value
safe to rewrite without inventory: notifications copy it and history may be
mixed after a future aggregate cutover. Compatibility readers should accept
old/new aggregate FQCNs, with writers remaining legacy until each aggregate
move. Phase 2.13B changes request-model workflow identity only and must not
change these aggregate values.

## 8. Notification identity contract

`WorkflowNotificationService` stores `get_class($entity)` and the entity key in
notification metadata. Change-request notification metadata copies the request
row's aggregate `entity_type`, `entity_id`, and request ID. `NotificationService`
JSON-encodes this scalar metadata into `in_app_notifications.meta`.

Current notification navigation follows `action_url`; no current reader was
found that dynamically instantiates or exact-filters the metadata type. The
identity is persisted history and should remain dual-era readable. It is an
optional cleanup backfill, not a cutover prerequisite, unless a live consumer
not present in source is discovered.

## 9. Official correspondence morph contract

Monthly is uniquely exposed here:

* `MonthlyActivity::officialCorrespondence()` is a `morphOne` relationship.
* `OfficialCorrespondence::correspondable()` is the inverse `morphTo`.
* planning uses the Monthly relationship and explicitly supplies
  `MonthlyActivity::class` plus the activity ID to `updateOrCreate`.
* showcase seeding also writes the legacy Monthly FQCN.
* the table has an index and unique constraint on
  `(correspondable_type, correspondable_id)`.

**Yes: historical official correspondences would fail to resolve correctly if
`MonthlyActivity` moved without morph compatibility.** The forward relationship
would query the new exact morph type, while inverse `morphTo` would try to load
the legacy class string. The unique key also permits one old and one new row for
the same logical activity.

A future Monthly compatibility deployment must prove all of the following:

1. forward lookup accepts both exact FQCNs and throws on duplicates;
2. inverse resolution maps both strings to the one installed aggregate class;
3. writes remain legacy before the move and canonical only after the move;
4. `updateOrCreate` searches both identities before creating;
5. rollback can still resolve canonical rows.

A narrowly enumerated two-key morph map may satisfy inverse resolution, but its
key order can influence the value written by Eloquent. It is acceptable only if
tests prove legacy-write and later canonical-write phases. A focused custom
resolver is preferable if the framework cannot guarantee that behavior. A
global unrestricted morph map is rejected. Agenda has no corresponding morph
surface in current source.

## 10. Route binding and serialized runtime state

Routes use implicit `{monthlyActivity}` and `{agendaEvent}` parameters with
typed controller arguments. No explicit `Route::model`, `Route::bind`, custom
route key, or binding override was found. Normal implicit binding excludes
soft-deleted rows. Monthly trash/restore paths use scalar IDs and explicit
`onlyTrashed()` queries where deleted records are required. Future imports may
change, but parameter names, URLs, IDs, and deleted-record behavior must not.

No application job, event, listener, notification, or mail using `ShouldQueue`
or `SerializesModels` with either aggregate was found. Notifications persist
scalar metadata synchronously. This is source evidence, not a live queue
inventory: queued jobs and worker deployments must still be checked before a
namespace cutover.

## 11. Relationship boundary

Most aggregate references are ordinary `belongsTo`, `hasOne`, or `hasMany`
class references. They require import changes after a move but do not themselves
persist an FQCN. Persisted polymorphic boundaries are:

* `MonthlyActivity` and `AgendaEvent` → `WorkflowInstance` (`morphOne`);
* `MonthlyActivity` → `OfficialCorrespondence` (`morphOne`);
* `OfficialCorrespondence` → owner (`morphTo`).

This distinction prevents ordinary FK relationships from being needlessly
backfilled.

## 12. Writer matrix

| Storage | Monthly writer/source | Agenda writer/source | Cutover behavior |
|---|---|---|---|
| workflow instance | `DynamicWorkflowService::forModel()` / `get_class()` | same | dual-read/find first; legacy write until respective move |
| workflow action log | Monthly controllers/concern and change-request service / explicit class | Agenda approval controller and change-request service / explicit class | update imports naturally after move; retain mixed-history reads |
| audit log | evaluation service / explicit class | Agenda controller / explicit class | canonical after move; dual-read history |
| request row | change-request service / explicit aggregate class | same | legacy before move; canonical after move only after readers support both |
| notification metadata | workflow service / `get_class()`; request metadata copy | same | follows aggregate/request-row writer; no dual-write |
| official correspondence | Monthly relationship/planning/seeder | none | find across both; legacy then canonical single writer |

## 13. Reader matrix

| Storage | Reader | Dynamic resolution? | Exact filter? | Required compatibility |
|---|---|---:|---:|---|
| workflow instance | aggregate `workflowInstance()` | morph resolution | yes | both identities plus duplicate conflict |
| workflow instance | `DynamicWorkflowService::resolveEntity()` | yes | stored type | map both exact identities to installed class |
| workflow instance | Monthly/Agenda approvals, planning, change requests, reports | no/relationship | yes in several paths | exact `whereIn` for respective pair |
| workflow action log | Monthly trash/history | no | yes | exact `whereIn` |
| workflow action log | other history display | no | no | preserve stored value |
| audit log | generic admin audit readers | no | no aggregate exact filter found | preserve mixed history; future filters use both |
| request row | request relationships/services | no | primarily ID/status | retain old/new value compatibility |
| notification metadata | in-app navigation | no | no | preserve scalar history |
| official correspondence | Monthly forward relation | Eloquent morph query | yes | forward dual-read and conflict detection |
| official correspondence | inverse `morphTo` | yes | stored type | exact old/new resolver |

## 14. Live inventory queries

These are read-only MySQL-compatible queries. Backslashes are SQL string
literals as shown; verify the production SQL mode before use.

```sql
SELECT entity_type, COUNT(*) AS row_count
FROM workflow_instances
WHERE entity_type IN (
  'App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity',
  'App\\Models\\AgendaEvent',
  'App\\Modules\\Events\\Models\\AgendaEvent'
)
GROUP BY entity_type ORDER BY entity_type;

SELECT entity_type, COUNT(*) AS row_count
FROM workflow_action_logs
WHERE entity_type IN (
  'App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity',
  'App\\Models\\AgendaEvent',
  'App\\Modules\\Events\\Models\\AgendaEvent'
)
GROUP BY entity_type ORDER BY entity_type;

SELECT entity_type, action, COUNT(*) AS row_count
FROM audit_logs
WHERE entity_type IN (
  'App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity',
  'App\\Models\\AgendaEvent',
  'App\\Modules\\Events\\Models\\AgendaEvent'
)
GROUP BY entity_type, action ORDER BY entity_type, action;

SELECT correspondable_type, COUNT(*) AS row_count
FROM official_correspondences
GROUP BY correspondable_type ORDER BY correspondable_type;

SELECT correspondable_type, COUNT(*) AS row_count
FROM official_correspondences
WHERE correspondable_type IN (
  'App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity'
)
GROUP BY correspondable_type ORDER BY correspondable_type;

SELECT entity_type, request_type, status, COUNT(*) AS row_count
FROM monthly_plan_edit_requests GROUP BY entity_type, request_type, status;
SELECT entity_type, request_type, status, COUNT(*) AS row_count
FROM monthly_plan_delete_requests GROUP BY entity_type, request_type, status;
SELECT entity_type, request_type, status, COUNT(*) AS row_count
FROM annual_agenda_edit_requests GROUP BY entity_type, request_type, status;
SELECT entity_type, request_type, status, COUNT(*) AS row_count
FROM annual_agenda_delete_requests GROUP BY entity_type, request_type, status;
```

Notification metadata is JSON. For MySQL 5.7+/8.0, inventory it separately:

```sql
SELECT JSON_UNQUOTE(JSON_EXTRACT(meta, '$.entity_type')) AS entity_type,
       COUNT(*) AS row_count
FROM in_app_notifications
WHERE JSON_VALID(meta)
GROUP BY JSON_UNQUOTE(JSON_EXTRACT(meta, '$.entity_type'))
ORDER BY entity_type;
```

## 15. Referential and conflict checks

```sql
-- Mixed-identity duplicate logical workflows.
SELECT workflow_id, entity_id, COUNT(*) AS identity_count
FROM workflow_instances
WHERE entity_type IN (
  'App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity'
)
GROUP BY workflow_id, entity_id HAVING COUNT(*) > 1;

SELECT workflow_id, entity_id, COUNT(*) AS identity_count
FROM workflow_instances
WHERE entity_type IN (
  'App\\Models\\AgendaEvent',
  'App\\Modules\\Events\\Models\\AgendaEvent'
)
GROUP BY workflow_id, entity_id HAVING COUNT(*) > 1;

-- Orphan aggregate workflows.
SELECT wi.id, wi.entity_type, wi.entity_id
FROM workflow_instances wi
LEFT JOIN monthly_activities ma ON ma.id = wi.entity_id
WHERE wi.entity_type IN ('App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity') AND ma.id IS NULL;

SELECT wi.id, wi.entity_type, wi.entity_id
FROM workflow_instances wi
LEFT JOIN agenda_events ae ON ae.id = wi.entity_id
WHERE wi.entity_type IN ('App\\Models\\AgendaEvent',
  'App\\Modules\\Events\\Models\\AgendaEvent') AND ae.id IS NULL;

-- Monthly correspondence orphans and old/new duplicates.
SELECT oc.id, oc.correspondable_type, oc.correspondable_id
FROM official_correspondences oc
LEFT JOIN monthly_activities ma ON ma.id = oc.correspondable_id
WHERE oc.correspondable_type IN ('App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity') AND ma.id IS NULL;

SELECT correspondable_id, COUNT(*) AS identity_count
FROM official_correspondences
WHERE correspondable_type IN ('App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity')
GROUP BY correspondable_id HAVING COUNT(*) > 1;

-- Request-row aggregate orphans (repeat for the four named tables).
SELECT r.id, r.entity_type, r.entity_id
FROM monthly_plan_edit_requests r
LEFT JOIN monthly_activities ma ON ma.id = r.entity_id
WHERE r.entity_type IN ('App\\Models\\MonthlyActivity',
  'App\\Modules\\Events\\Models\\MonthlyActivity') AND ma.id IS NULL;

SELECT r.id, r.entity_type, r.entity_id
FROM annual_agenda_edit_requests r
LEFT JOIN agenda_events ae ON ae.id = r.entity_id
WHERE r.entity_type IN ('App\\Models\\AgendaEvent',
  'App\\Modules\\Events\\Models\\AgendaEvent') AND ae.id IS NULL;
```

Also run distinct-value inventories without an `IN` filter before declaring any
identity unknown. Audit/action-log entity IDs are meaningful only within their
writer's action/module contract, so orphan checks must retain those filters.

## 16. Options compared

### A — direct backfill plus move

Rejected. It creates an interval in which old code cannot resolve canonical
workflow/correspondence values, complicates rollback, and does not prevent
duplicate logical rows under the FQCN-bearing unique keys.

### B — focused dual-read, canonical writer after move

Selected for both aggregates. It permits mixed history, protects rollback, and
keeps one writer. It requires exact integrations rather than a global registry.

### C — Monthly-only explicit morph compatibility

Potentially required as one part of B for inverse correspondence resolution.
Accept only an exact old/new map after testing write-key selection. It is not a
substitute for workflow/request/report dual-read.

### D — compatibility bridge models

Rejected. Two writable aggregate model classes risk divergent events, scopes,
factories, observers, and persisted identities.

### E — stable aggregate identity abstraction

Not selected now. Stable aliases already protect Common Events subject tables,
but retrofitting all workflow/audit/request infrastructure would be a broad
redesign unsupported by the current task.

## 17. Selected per-aggregate strategies

### AgendaEvent

Introduce exact legacy/canonical mapping, mapped workflow resolution,
dual-read relationship/exact filters, find-before-create conflict protection,
request-row read compatibility, and legacy writer. After live/runtime gates,
move Agenda alone, switch naturally to the canonical writer, retain dual-read,
observe, and only then consider cleanup.

### MonthlyActivity

Use the same focused layers plus a separate correspondence gate: forward and
inverse morph compatibility, duplicate detection, and find-before-create across
both types. Do not move Monthly until both workflow and correspondence mixed-
identity behavior are proven. Its evaluation/audit/history semantics remain
unchanged.

One `EventAggregateIdentity` helper with exactly these two pairs is preferable
to multiple generic mechanisms, but it should be implemented only with the
actual reader integrations in a later phase.

## 18. Backfill classification

### Required for cutover

No historical backfill is required if all readers/resolvers accept both exact
identities and writers are switched only with installed compatibility. Source
and live duplicate/orphan checks are required.

### Optional cleanup (required only before retiring compatibility)

* `workflow_instances.entity_type`;
* request-table `entity_type` in all four tables;
* exact aggregate FQCNs in `workflow_action_logs` and meaningful `audit_logs`;
* Monthly `official_correspondences.correspondable_type`;
* notification JSON metadata.

Each optional backfill must use exact old/new predicates, a transaction and
recorded pre/post counts. Correspondence and workflow updates need preflight
duplicate checks because their unique keys include the type. Rollback is the
same exact update in reverse while compatibility remains deployed.

### Must not change

Stable Common aliases (`monthly_activity`, `ramadan_iftar`), numeric IDs,
request types/statuses, ordinary foreign keys, action/audit meaning,
notification URLs, workflow decisions, and business payloads.

## 19. Rollback design

1. **Old app/old identities:** current safe baseline.
2. **Compatibility app/legacy writer:** fully backward compatible; rollback
   code without data changes is safe because no canonical writes exist.
3. **Canonical writer/mixed history:** roll back only to the compatibility app,
   not to pre-compatibility code; it must map canonical stored types to the
   installed legacy class.
4. **After optional backfill:** retain compatibility and verify inverse update
   counts before any code rollback.
5. **Retirement:** only after old/new live counts, queue/worker inventory,
   reports, routes, morphs, and rollback rehearsals prove no legacy dependency.

Any live queued aggregate payload discovered must be drained or processed by a
deployment that has both identity mappings. None was found in source.

## 20. Safe deployment sequence and dependency graph

1. Deploy aggregate compatibility readers for both pairs with legacy writers.
2. Run all live inventory, orphan, duplicate, correspondence, notification,
   route, report, and worker checks.
3. Cut over **AgendaEvent only**; canonical writer, dual-read retained.
4. Observe and optionally backfill Agenda exact identities.
5. Complete the separately gated Monthly request-pair cutover when Phase 2.13B
   prerequisites pass; this is operational ordering, not a technical aggregate
   prerequisite.
6. Prove Monthly correspondence compatibility, then cut over Monthly alone.
7. Observe, optionally backfill, and retire compatibility only in a later phase.

Dependency graph:

```text
Phase 2.13A -> Phase 2.13B (runtime/live gated request-model cutover)
Phase 2.14  -> aggregate compatibility implementation -> live gates
                                                     -> Agenda cutover
Phase 2.13B observed + Monthly morph proof ----------> Monthly cutover
```

Request-model FQCNs in workflow instances and aggregate FQCNs in request rows
are independent columns/contracts. Source does not require Phase 2.13B before
Agenda or Monthly compatibility work. The cutovers are nevertheless sequenced
separately to avoid changing two persisted identity boundaries at once.

## 21. Required runtime tests

* legacy and canonical workflow resolution for each aggregate;
* aggregate relationship lookup under both identities;
* find-before-create reuse, legacy writer, and mixed-identity conflict;
* every exact workflow/action/report reader under both identities;
* request rows with old/new aggregate identities without changing request-model
  workflow identity;
* audit and notification history visibility under mixed identities;
* Monthly correspondence forward and inverse resolution under both identities;
* Monthly correspondence duplicate conflict and single-writer behavior;
* implicit route binding, authorization, soft-delete, trash, and restore paths;
* worker/queue inventory and deployment rollback rehearsal;
* exact backfill and inverse rollback in a disposable database.

No runtime test was executed in Phase 2.14.

## 22. Runtime debt and integrity

```text
PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED
PHASE 2.13B REMAINS BLOCKED BY RUNTIME/LIVE INVENTORY
```

```text
NO AGGREGATE MODEL NAMESPACE WAS CHANGED
NO STORED AGGREGATE IDENTITY WAS CHANGED
NO WORKFLOW, REQUEST, AUDIT, ACTION-LOG, NOTIFICATION, OR CORRESPONDENCE ROW WAS BACKFILLED
NO BUSINESS OR APPROVAL RULE WAS CHANGED
```
