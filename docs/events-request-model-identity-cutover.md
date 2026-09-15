# Events request-model identity compatibility audit

**Phase:** 2.13
**Scope:** four Monthly/Agenda edit/delete request models and directly required workflow infrastructure
**Outcome:** design only; no namespace, writer, route, workflow, schema, or data change

## 1. Executive conclusion

All four request model FQCNs are persistent runtime identities. Creation passes
the request object to `DynamicWorkflowService::forModel()`, which supplies
`get_class($model)` to `forEntity()`. The resulting exact FQCN is stored in
`workflow_instances.entity_type`. `DynamicWorkflowService::resolveEntity()`
then validates and dynamically executes that stored class's query. Each request
model's `morphOne(WorkflowInstance::class, 'entity')` also generates an exact
current morph-class filter. A direct namespace move would therefore orphan old
workflow instances from relationship reads and make historical resolution fail.

The identically named `entity_type` in each request table has a different
contract: it stores the aggregate FQCN (`App\Models\MonthlyActivity` or
`App\Models\AgendaEvent`), while `entity_id` is the aggregate ID. `request_type`
is the stable `edit` or `delete` code. The request row has no workflow-instance
FK; linkage is the reverse `(request FQCN, request ID)` pair in
`workflow_instances`.

Select a focused dual-read strategy backed by one four-entry Events request
identity map. Deployment 1 must teach relationship, resolver and exact-string
report readers to accept legacy and canonical request identities while writers
remain legacy. Only after live inventory and regressions may a separate cutover
move the **Monthly pair first**, switch their natural `get_class()` writer to the
canonical namespace, and leave dual-read in place. Aggregate identity is a
separate boundary and must remain unchanged.

## 2. Four-model inventory

| Model | Table | Aggregate | Request code | Workflow module / configured workflow | Status/actors/history | Relationships and consumers | Risk |
|---|---|---|---|---|---|---|---|
| `App\Models\MonthlyPlanEditRequest` | `monthly_plan_edit_requests` | `MonthlyActivity` | `edit` | `monthly_activities` / `monthly_activity_approval` | status, requester, current approver, approval history, request/decision times; old/new/changed values and approved version | requester, approver, activity, approved version, workflow instance; Monthly queues/feedback/reports/decision service | HIGH |
| `App\Models\MonthlyPlanDeleteRequest` | `monthly_plan_delete_requests` | `MonthlyActivity` | `delete` | `monthly_activities` / `monthly_activity_approval` | status, requester, current approver, approval history, request/decision times | requester, approver, activity, workflow instance; Monthly queues/feedback/reports/decision service | HIGH |
| `App\Models\AnnualAgendaEditRequest` | `annual_agenda_edit_requests` | `AgendaEvent` | `edit` | `agenda` / `agenda_approval` | status, requester, current approver, approval history, request/decision times; old/new/changed values and approved version | requester, approver, agenda event, approved version, workflow instance; Agenda approval UI/decision service | HIGH |
| `App\Models\AnnualAgendaDeleteRequest` | `annual_agenda_delete_requests` | `AgendaEvent` | `delete` | `agenda` / `agenda_approval` | status, requester, current approver, approval history, request/decision times | requester, approver, agenda event, workflow instance; Agenda approval UI/decision service | HIGH |

All four use `HasFactory`; no request-specific factory, `newFactory()` override,
or request-model `::factory()` usage was found. The edit models carry snapshot
JSON and nullable approved-version FKs; delete models do not.

## 3. Request-table identity contract

All four tables contain:

```text
id
requester_id                FK users
request_type                stable edit|delete code written by service
entity_type                 aggregate FQCN, not request FQCN
entity_id                   aggregate ID; intentionally no polymorphic FK
reason
status
current_approver_id         nullable FK users
approval_history            JSON actor/step/decision/comment/time snapshots
requested_at
decided_at
timestamps
```

Monthly tables additionally contain nullable `branch_id`. Edit tables contain
`old_values`, `new_values`, `changed_values`, and nullable
`approved_version_id`, constrained to their aggregate table.

There is an index on `(entity_type, entity_id)` and another on
`(status, current_approver_id)`. There is no uniqueness constraint preventing
multiple request rows; application checks prohibit overlapping active edit and
delete requests. There is no `workflow_instance_id` column.

Identity values written today:

| Request row | `entity_type` | `entity_id` |
|---|---|---|
| either Monthly request | `App\Models\MonthlyActivity` | Monthly activity ID |
| either Agenda request | `App\Models\AgendaEvent` | Agenda event ID |

The request namespace cutover must not change these aggregate identities.

## 4. Workflow-instance identity contract

`workflow_instances` contains workflow ID, `entity_type`, `entity_id`, current
step, status, edit iteration, and start/completion timestamps. Its unique
constraint is `(workflow_id, entity_type, entity_id)`.

For request creation:

```text
PlanChangeRequestWorkflowService::startWorkflow(request)
  -> DynamicWorkflowService::forModel(module, request)
  -> forEntity(workflow, get_class(request), request.id)
  -> firstOrCreate(workflow_id, request FQCN, request ID)
```

Thus every request model currently stores its own legacy FQCN in
`workflow_instances.entity_type`. `resolveEntity()` dynamically checks
`class_exists`, checks it is an Eloquent model, then calls
`$entityType::query()->find(entity_id)`. This is resolution, not display-only
metadata.

The model-side `morphOne` is another exact identity reader. After a direct move,
its canonical morph class would not match legacy rows. The unique constraint
also permits a second workflow for the same logical request if its namespace
changes, because old and canonical FQCN strings are different. Compatibility
must therefore find/reuse the existing logical workflow before calling
`firstOrCreate`; otherwise duplicate histories are possible.

Workflow status synchronization copies instance status/current approver and
approval-history snapshots back to the request row. `workflow_logs` points only
to `workflow_instance_id`; it does not independently store a model FQCN.

## 5. Aggregate identity is separate

| Request model | Request FQCN stored? | Aggregate FQCN stored? | Storage |
|---|:---:|:---:|---|
| Monthly edit | yes | yes | request FQCN in `workflow_instances`; `MonthlyActivity` FQCN in request row |
| Monthly delete | yes | yes | request FQCN in `workflow_instances`; `MonthlyActivity` FQCN in request row |
| Agenda edit | yes | yes | request FQCN in `workflow_instances`; `AgendaEvent` FQCN in request row |
| Agenda delete | yes | yes | request FQCN in `workflow_instances`; `AgendaEvent` FQCN in request row |

Request namespace movement and aggregate namespace movement are independent.
Moving request classes first is safe only if request workflow identities become
dual-readable while request-row aggregate FQCNs remain legacy. Aggregate
relationships use `entity_id` with explicit model relationships and do not
dynamically resolve the request-row `entity_type` in the audited request flow.

## 6. Workflow action/log identity

Two log families have different contracts:

- `workflow_logs` stores workflow-instance/step/user FKs plus decision, comment,
  iteration and time. It stores neither request nor aggregate class.
- `workflow_action_logs` stores a string `entity_type`, but request creation calls
  `PlanChangeRequestWorkflowService::log()` with the underlying aggregate. It
  writes `MonthlyActivity::class` or `AgendaEvent::class`, aggregate ID, and the
  request ID only inside `meta`. It does not store a request-model FQCN for the
  four audited flows.

Accordingly, request cutover needs no action-log backfill. Aggregate namespace
work must later handle those aggregate identities separately.

## 7. Notification, audit, queue and serialization findings

`PlanChangeRequestWorkflowService::requestMeta()` emits:

```text
request_type
entity_type     value copied from request row: aggregate FQCN
entity_id       aggregate ID
request_id
```

`NotificationService` JSON-encodes this scalar metadata into
`in_app_notifications.meta`; it does not serialize the request model. URLs use
aggregate or request IDs as appropriate, not request classes. No request-related
notification implements `ShouldQueue` or `SerializesModels`; the audited service
performs direct database inserts. No job, listener, event payload, command
payload, or queued notification was found carrying one of the four models.

No `AuditLog` writer or exact audit query uses any of the four request classes.
For these flows audit identity is **not used**. Approval history is stored in the
request JSON and workflow logs instead.

## 8. Route-binding contract

All four models are directly and implicitly route-bound:

| Route | Parameter | Controller type | Contract impact |
|---|---|---|---|
| Monthly delete decision PUT | `{deleteRequest}` | `MonthlyPlanDeleteRequest $deleteRequest` | parameter/URL must remain unchanged; import changes at cutover |
| Monthly edit decision PUT | `{editRequest}` | `MonthlyPlanEditRequest $editRequest` | parameter/URL must remain unchanged; import changes at cutover |
| Agenda delete decision PUT | `{deleteRequest}` | `AnnualAgendaDeleteRequest $deleteRequest` | parameter/URL must remain unchanged; import changes at cutover |
| Agenda edit decision PUT | `{editRequest}` | `AnnualAgendaEditRequest $editRequest` | parameter/URL must remain unchanged; import changes at cutover |

No explicit `Route::model`, `Route::bind`, or model
`resolveRouteBinding()` override was found. Namespace changes affect PHP imports
and implicit binding construction, not public route names or parameter names.

## 9. Writer matrix

| File / method | Row written | Identity written | Source |
|---|---|---|---|
| `PlanChangeRequestWorkflowService::startMonthlyEditRequest` | Monthly edit request | aggregate `MonthlyActivity::class` | explicit `::class` |
| `startMonthlyDeleteRequest` | Monthly delete request | aggregate `MonthlyActivity::class` | explicit `::class` |
| `startAgendaEditRequest` | Agenda edit request | aggregate `AgendaEvent::class` | explicit `::class` |
| `startAgendaDeleteRequest` | Agenda delete request | aggregate `AgendaEvent::class` | explicit `::class` |
| `startWorkflow` -> `DynamicWorkflowService::forModel` | workflow instance | request model FQCN | `get_class($request)` |
| `DynamicWorkflowService::recordDecision` / auto approval | workflow log | no FQCN | workflow-instance FK |
| `PlanChangeRequestWorkflowService::decide` | request status/history | no new FQCN; retains aggregate identity | existing request row |
| `PlanChangeRequestWorkflowService::log` | workflow action log | aggregate model FQCN | `$entity::class` |
| `requestMeta` -> `NotificationService` | notification JSON metadata | aggregate FQCN | request row `entity_type` |
| Audit writers | none for audited request classes | none | not used |

## 10. Reader matrix

| Storage / reader | Lookup style | Dynamic class? | Exact string? | Display only? | Move impact |
|---|---|:---:|:---:|:---:|---|
| request `workflowInstance()` | Eloquent `morphOne` | no | yes, current morph class | no | legacy workflows disappear after direct move |
| `DynamicWorkflowService::resolveEntity()` | validate stored class then `::query()->find()` | yes | stored value | no | old FQCN needs explicit mapping after old class removal |
| `DynamicWorkflowService::forEntity()` | `firstOrCreate` unique tuple | no | yes | no | canonical writer can create duplicate logical workflow unless old identity searched first |
| request queues/reports/feedback | direct model query plus eager-loaded workflow relation | indirectly | relation exact string | no | require compatibility relationship/query |
| `AdminReportsService` approval speed | `whereIn(entity_type, [...::class])` | no | yes | grouped/displayed | currently includes Monthly request legacy classes; must accept both; Agenda request types are presently omitted |
| workflow timeline | follows request relationship then workflow/log FKs | resolver used for step applicability | indirectly | no | requires compatible workflow lookup/resolution |
| request-table aggregate relation | explicit `belongsTo` on `entity_id` | no | no request-row type filter | no | unaffected while aggregate classes stay put |
| notifications | JSON scalar display/link metadata | no | aggregate FQCN retained | mostly | no request namespace impact |
| workflow action reports | aggregate FQCN | no | aggregate exact filters may exist | no | request move does not change it |
| audit logs | no audited request identity reader found | no | no | n/a | none |

## 11. Selected compatibility design

Use **focused dual-read identity compatibility**, not a backfill-first move.
Create one small Events-scoped request identity map in the future cutover
preparation deployment. Four independent helpers would duplicate identical
behavior; a generic application-wide identity framework would be excessive.
The map should contain only these explicit entries:

```text
legacy request FQCN
canonical Events request FQCN
accepted exact pair
current canonical PHP model for resolution
current write identity
```

Required integration points are limited to:

1. request `workflowInstance` lookup using exact accepted identities and request
   ID;
2. `DynamicWorkflowService::resolveEntity()` translating only the four allowed
   old/new identities to the currently installed canonical model;
3. `forModel()`/`forEntity()` searching accepted identities before creating, so
   one logical request cannot gain two workflow instances;
4. `AdminReportsService` using accepted identity pairs for request timing;
5. any other exact request-identity filter found by the cutover recheck.

The writer remains legacy in Deployment 1. After a model pair moves, normal
`get_class()` becomes its canonical writer. Readers continue accepting both.
Do not create old-namespace Eloquent bridges, a global morph map, `class_alias`,
or arbitrary dynamic fallback.

## 12. Options compared

| Option | Benefit | Failure/risk | Decision |
|---|---|---|---|
| A. Direct FQCN backfill then move | one stored identity immediately | old application cannot resolve canonical class; rollback requires reverse data mutation; risky ordering | reject |
| B. Exact dual-read, canonical writer after move | preserves history, staged rollback and one future identity | small changes at the three proven identity-reader/writer boundaries | **selected** |
| C. Temporary old namespace model bridge | dynamic old FQCN remains resolvable | two writable Eloquent models, ambiguous morph identity and retirement | reject |
| D. Stable workflow alias abstraction | namespace-independent future | current workflow stores/resolves FQCNs; global redesign exceeds four-model need | reject for this cutover |

## 13. Grouping and ordering decision

`MONTHLY PAIR FIRST`

Move request models before aggregates. Request-row aggregate identities can
remain `App\Models\MonthlyActivity`/`App\Models\AgendaEvent`; changing them is
not required for request workflow resolution.

Move Monthly edit/delete together because the service enforces mutual exclusion
across the pair, approval queues and reports query both, and both share the same
module/workflow and branch-scoped conditions. Moving only one would create a
mixed identity implementation inside one atomic business rule. The Monthly pair
also has broader exact-string reporting and conditional-step behavior, so it is
the appropriate first proof of the focused compatibility map. Move the Agenda
pair only in a later separately gated slice after observation.

## 14. Read-only live inventory queries

No results are claimed. Run these against the explicitly disposable/approved
runtime database before compatibility or cutover.

```sql
SELECT entity_type, COUNT(*) AS row_count
FROM workflow_instances
GROUP BY entity_type
ORDER BY entity_type;
```

```sql
SELECT entity_type, status, COUNT(*) AS row_count
FROM workflow_instances
WHERE entity_type IN (
  'App\\Models\\MonthlyPlanEditRequest',
  'App\\Modules\\Events\\Models\\MonthlyPlanEditRequest',
  'App\\Models\\MonthlyPlanDeleteRequest',
  'App\\Modules\\Events\\Models\\MonthlyPlanDeleteRequest',
  'App\\Models\\AnnualAgendaEditRequest',
  'App\\Modules\\Events\\Models\\AnnualAgendaEditRequest',
  'App\\Models\\AnnualAgendaDeleteRequest',
  'App\\Modules\\Events\\Models\\AnnualAgendaDeleteRequest'
)
GROUP BY entity_type, status
ORDER BY entity_type, status;
```

For every request table:

```sql
SELECT entity_type, request_type, status, COUNT(*) AS row_count
FROM monthly_plan_edit_requests
GROUP BY entity_type, request_type, status
ORDER BY entity_type, request_type, status;
```

Repeat with `monthly_plan_delete_requests`, `annual_agenda_edit_requests`, and
`annual_agenda_delete_requests`. Expected code-written combinations are the
aggregate FQCN plus the matching `edit` or `delete` code; treat all other values
as blockers, not aliases to normalize silently.

Inventory notification and action-log aggregate metadata without asserting it
is a request identity:

```sql
SELECT entity_type, action_type, COUNT(*) AS row_count
FROM workflow_action_logs
WHERE action_type IN ('edit_request_created', 'delete_request_created')
GROUP BY entity_type, action_type
ORDER BY entity_type, action_type;
```

Use application/Tinker JSON decoding for `in_app_notifications.meta` because
portable JSON extraction differs by database engine. Group decoded
`request_type`, `entity_type`, and presence of `request_id` for notification
types beginning `plan_change_request_`.

## 15. Referential consistency queries

### Workflow instance to request row

Run one query for each exact legacy/canonical pair. Example Monthly edit:

```sql
SELECT wi.id, wi.entity_type, wi.entity_id
FROM workflow_instances wi
LEFT JOIN monthly_plan_edit_requests r ON r.id = wi.entity_id
WHERE wi.entity_type IN (
  'App\\Models\\MonthlyPlanEditRequest',
  'App\\Modules\\Events\\Models\\MonthlyPlanEditRequest'
)
AND r.id IS NULL;
```

Repeat against the other three request tables with their exact identity pair.
Every result is an orphan blocker.

### Request row to aggregate

```sql
SELECT r.id, r.entity_type, r.entity_id
FROM monthly_plan_edit_requests r
LEFT JOIN monthly_activities a ON a.id = r.entity_id
WHERE r.entity_type <> 'App\\Models\\MonthlyActivity' OR a.id IS NULL;
```

Repeat for Monthly delete, and use `agenda_events` plus
`App\\Models\\AgendaEvent` for both Agenda tables. Soft-deleted aggregates still
exist physically and are not orphans.

### Unknown identities

```sql
SELECT entity_type, COUNT(*) AS row_count
FROM workflow_instances
WHERE entity_type LIKE '%Plan%Request%'
   OR entity_type LIKE '%Agenda%Request%'
GROUP BY entity_type
ORDER BY entity_type;
```

Compare results strictly with the eight accepted old/new request FQCNs rather
than treating the pattern as an allow-list.

### Duplicate logical workflows

The database unique key prevents exact tuple duplication, but mixed namespaces
can bypass it:

```sql
SELECT workflow_id, entity_id, COUNT(*) AS row_count
FROM workflow_instances
WHERE entity_type IN (
  'App\\Models\\MonthlyPlanEditRequest',
  'App\\Modules\\Events\\Models\\MonthlyPlanEditRequest'
)
GROUP BY workflow_id, entity_id
HAVING COUNT(*) > 1;
```

Repeat per request type. Also verify that each request row has exactly one
workflow instance by left joining and grouping with `HAVING COUNT(wi.id) <> 1`.

### Application active-request invariant

For Monthly and Agenda separately, query edit and delete tables for the same
aggregate ID where both statuses are in:

```text
pending, pending_approval, in_progress,
waiting_approval, waiting, changes_requested
```

Any overlap violates the service's mutual-exclusion invariant and blocks
cutover.

## 16. Backfill design

Backfill is optional and must not be part of the namespace/writer cutover.
Before approval:

1. record counts by all eight accepted identities and status;
2. run every orphan, duplicate and active-overlap query;
3. snapshot exact old-identity counts for the selected pair;
4. ensure readers and resolver already accept both identities;
5. prove new writes are canonical and do not duplicate workflows.

If later approved, transactionally update only exact values:

```sql
UPDATE workflow_instances
SET entity_type = 'App\\Modules\\Events\\Models\\MonthlyPlanEditRequest'
WHERE entity_type = 'App\\Models\\MonthlyPlanEditRequest';
```

Repeat for Monthly delete in the same approved transaction. Capture affected
row counts, compare them to prechecks, rerun distinct/orphan/duplicate checks,
and verify zero legacy rows for that pair. Do not update request-table
`entity_type`, notifications, or workflow action logs: those store aggregate
identity.

Inverse rollback is the same exact update from canonical to legacy. It must be
exercised on a disposable database before production approval.

## 17. Rollback strategy

| State | Required behavior |
|---|---|
| old application + old identities | current baseline |
| compatibility deployment + old writer | resolves/queries old and future canonical; creates old only |
| moved Monthly pair + mixed identities | resolves/queries both; creates canonical only |
| code rollback after canonical writes | roll back to compatibility deployment, not pre-compatibility code; it maps canonical identities to legacy installed classes |
| rollback to pre-compatibility code | first stop writes and reverse-backfill canonical identities to legacy, verify zero canonical and no duplicates, then deploy old code |
| after optional forward backfill | dual-read remains through observation; inverse update is available |

Never deploy pre-compatibility code while canonical identities remain: its
`class_exists` check and exact morph relationship cannot read them.

## 18. Safe deployment sequence

1. **Deployment 1 — compatibility only:** add the focused identity map; make
   request workflow relationships, dynamic resolver, `forEntity` reuse, and
   report filters accept exact old/new identities. Keep all four models and
   writers under `App\Models`.
2. **Runtime gate:** run inventories and consistency checks on a disposable copy;
   exercise old rows, injected canonical rows, route binding, reporting,
   approvals and rollback mapping.
3. **Deployment 2 — Monthly pair cutover:** move Monthly edit/delete request
   models together, update imports/type checks/relationships, let `get_class()`
   write canonical identities, retain dual-read, and do not change aggregate
   FQCNs or routes.
4. **Observation:** verify one request produces one workflow, old history remains
   visible/resolvable, status synchronization and reports include both, and code
   rollback to Deployment 1 works.
5. **Optional later backfill:** separately approve exact transactional Monthly
   identity replacement and inverse rollback. It is not required for cutover.
6. **Agenda preparation/cutover:** repeat inventory and a separately reviewed
   pair cutover after Monthly observation.
7. **Retirement:** only after zero legacy rows are proven across retained
   environments and the rollback horizon closes; aggregate compatibility remains
   independent.

## 19. Required runtime tests

- legacy and canonical workflow identity resolve to the same request category;
- morph/compatibility relationship sees legacy-only, canonical-only and mixed
  histories without duplicate selection;
- `forModel()` reuses a legacy workflow after code cutover;
- one new request creates exactly one request row and one workflow instance;
- uniqueness remains logical across old/new identities;
- Monthly pair active edit/delete mutual exclusion remains intact;
- Monthly conditional workflow steps still inspect the underlying activity;
- Agenda pair workflows remain unchanged during Monthly cutover;
- implicit route binding retains `{editRequest}` and `{deleteRequest}` URLs;
- approval, changes-requested, rejection and final application behavior;
- current approver, approval history, version creation and deletion behavior;
- approval queues, feedback, timelines and Monthly/Admin reports include mixed
  identities;
- action logs and notifications retain aggregate identity and request IDs;
- orphan/duplicate/precheck/postcheck queries return expected controlled results;
- code rollback and inverse-backfill rollback on a disposable database;
- factory discovery remains unaffected or explicitly handled if factories exist.

## 20. Current remaining Events-owned models under App\Models

| Model | Reason |
|---|---|
| `MonthlyActivity` | stored aggregate identity; broad workflow/audit/request/notification surface |
| `AgendaEvent` | stored aggregate identity; broad workflow/audit/request surface |
| `MonthlyPlanEditRequest` | request FQCN in workflow instances |
| `MonthlyPlanDeleteRequest` | request FQCN in workflow instances |
| `AnnualAgendaEditRequest` | request FQCN in workflow instances |
| `AnnualAgendaDeleteRequest` | request FQCN in workflow instances |
| `PostExecutionVerification` | audit FQCN; Phase 2.8D runtime-gated |

`ExecutionNeedType` is already under `App\Modules\Events\Models` from Phase
2.8A. `MonthlyActivityVolunteerNeed` moved in Phase 2.12. Neither belongs in the
remaining list.

## 21. Integrity and runtime debt

NO REQUEST MODEL NAMESPACE WAS CHANGED
NO STORED REQUEST IDENTITY WAS CHANGED
NO WORKFLOW INSTANCE OR REQUEST ROW WAS BACKFILLED
NO BUSINESS OR APPROVAL RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

`PHASE 2.13 COMPLETE`

## 22. Phase 2.13A compatibility infrastructure outcome (2026-09-15)

`PHASE 2.13A COMPLETE`

`App\Modules\Events\Support\EventRequestModelIdentity` now contains exactly the
four legacy/canonical request pairs. In this compatibility deployment both
stored identities resolve to the installed legacy model and
`currentWriteType()` returns the legacy FQCN. Unknown identities are returned to
the pre-existing DynamicWorkflowService resolution path unchanged.

`DynamicWorkflowService::resolveEntity()` maps only these four exact identities.
`forEntity()` searches both identities for the active workflow and request ID
before creating; it reuses one match, writes the legacy identity when none
exists, and throws `LogicException` if both identities already exist. It neither
merges nor deletes conflicting rows.

Each request model remains in `App\Models`. Its public `workflowInstance()` name
is preserved and now uses a `hasOne` query constrained by request ID plus the
exact accepted identity pair. Aggregate relationships and request-row
`entity_type` values are unchanged.

`AdminReportsService` includes both Monthly request identity pairs, normalizes
them to the legacy logical category for grouping, and fails on an old/new
workflow duplicate for the same workflow/request rather than double-counting.
Agenda request workflow reporting was not added because it was not part of that
report's existing business scope.

Focused unit and feature tests were added for the four-entry map, unknown
identity behavior, canonical-to-installed resolution, relationship dual-read,
legacy/canonical reuse, legacy-only creation, mixed-identity conflict, and
normalized Monthly report totals. They are `ADDED / NOT EXECUTED` because
`vendor/autoload.php` remains unavailable.

Source compatibility for the Monthly pair is complete, but the actual namespace
cutover remains gated by the Phase 2.13 live inventory and runtime matrix.

NO REQUEST MODEL NAMESPACE WAS CHANGED
NO STORED REQUEST IDENTITY WAS BACKFILLED
NEW REQUEST WORKFLOW WRITES STILL USE LEGACY FQCNS
NO REQUEST-ROW AGGREGATE IDENTITY WAS CHANGED
NO BUSINESS OR APPROVAL RULE WAS CHANGED
NO DUAL-WRITE WAS INTRODUCED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED
