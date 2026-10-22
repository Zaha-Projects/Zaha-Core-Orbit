# Events identity cutover — final source state

## Authority and status

This is the authoritative source-state identity record. Older phase-specific
identity documents are focused historical references; where their status differs,
this document wins.

**All Events identity namespace work is DONE IN SOURCE / STAGING PENDING.** No
historical row was backfilled, no identity cleanup migration was added, no dual
write or unrestricted morph map exists, and compatibility must not yet be
retired.

## Canonical ownership

The only production definitions are:

- `App\Modules\Events\Models\AgendaEvent`
- `App\Modules\Events\Models\AnnualAgendaEditRequest`
- `App\Modules\Events\Models\AnnualAgendaDeleteRequest`
- `App\Modules\Events\Models\MonthlyPlanEditRequest`
- `App\Modules\Events\Models\MonthlyPlanDeleteRequest`
- `App\Modules\Events\Models\MonthlyActivity`
- `App\Modules\Events\Models\PostExecutionVerification`

The corresponding `app/Models/*.php` files are absent. There are no wrappers or
`class_alias` calls.

## Identity compatibility matrix

| Concept | Legacy identity | Canonical / installed model / current writer | Persisted surfaces | Reader compatibility | Duplicate protection | Backfill |
|---|---|---|---|---|---|---|
| AgendaEvent | `App\Models\AgendaEvent` | `App\Modules\Events\Models\AgendaEvent` | workflows/actions; request aggregate `entity_type`; audit/notification metadata where class-derived | `EventAggregateIdentity` accepts both; reports normalize both | workflow find-before-create rejects mixed pair | not performed |
| AnnualAgendaEditRequest | `App\Models\AnnualAgendaEditRequest` | `App\Modules\Events\Models\AnnualAgendaEditRequest` | request workflow `entity_type` | `EventRequestModelIdentity` accepts/resolves both | workflow find-before-create rejects mixed pair | not performed |
| AnnualAgendaDeleteRequest | `App\Models\AnnualAgendaDeleteRequest` | `App\Modules\Events\Models\AnnualAgendaDeleteRequest` | request workflow `entity_type` | same | same | not performed |
| MonthlyPlanEditRequest | `App\Models\MonthlyPlanEditRequest` | `App\Modules\Events\Models\MonthlyPlanEditRequest` | request workflow `entity_type` | same | same | not performed |
| MonthlyPlanDeleteRequest | `App\Models\MonthlyPlanDeleteRequest` | `App\Modules\Events\Models\MonthlyPlanDeleteRequest` | request workflow `entity_type` | same | same | not performed |
| MonthlyActivity | `App\Models\MonthlyActivity` | `App\Modules\Events\Models\MonthlyActivity` | workflows/actions, Monthly request aggregate identity, correspondence morph, audits/notification metadata | `EventAggregateIdentity`, request relationships, correspondence forward/inverse readers, and reports accept both | workflow and correspondence services reject mixed pairs | not performed |
| PostExecutionVerification | `App\Models\PostExecutionVerification` | `App\Modules\Events\Models\PostExecutionVerification` | `audit_logs.entity_type` only | `PostExecutionVerificationIdentity` exact pair; generic reports do not resolve classes | audit events are history, not unique logical model rows | not performed |

Request-table `entity_type` identifies the parent aggregate, while
`workflow_instances.entity_type` for request workflows identifies the request
model. These contracts must not be conflated.

## Persisted identity surface matrix

| Surface | Stored meaning and possible history | Active writer | Active reader | Duplicate risk | Stage inventory |
|---|---|---|---|---|---:|
| `workflow_instances.entity_type` | aggregate or request-model FQCN; legacy/canonical values possible | canonical helpers | exact-pair lookup and installed-model resolution | high: DB uniqueness treats strings separately | required |
| `workflow_action_logs.entity_type` | aggregate FQCN; legacy/canonical Agenda/Monthly possible | canonical class-derived | exact-pair report/history filters | report double-category/grouping risk, not row duplication | required |
| `annual_agenda_*_requests.entity_type` | Agenda aggregate FQCN | canonical AgendaEvent | relationships/services accept both | orphan risk; no request-model identity here | required |
| `monthly_plan_*_requests.entity_type` | Monthly aggregate FQCN | canonical MonthlyActivity | relationships/services accept both | orphan risk | required |
| `official_correspondences.correspondable_type` | Monthly morph FQCN | canonical helper-backed service | focused forward and inverse resolver accepts both | high: type+ID DB uniqueness permits one per FQCN | required |
| `audit_logs.entity_type` | caller-selected FQCN/category | canonical class/helper writers | generic reporting; focused verification pair when exact filtering | grouping fragmentation only | required |
| notification metadata | class-derived `entity_type` may be old/new; navigation is `action_url` | canonical class-derived | URL navigation; no unrestricted resolver | low | inventory recommended |
| shared `subject_type` columns | stable business alias, never PHP FQCN | alias constants | alias-to-model mapping | none from namespace cutover | required sanity check |

## Writers and mixed-history readers

`EventAggregateIdentity::currentWriteType()`,
`EventRequestModelIdentity::currentWriteType()`, and
`PostExecutionVerificationIdentity::currentWriteType()` return canonical FQCNs.
The dynamic workflow service searches the accepted pair before creating and
throws on a mixed logical duplicate. Monthly official correspondence searches
both exact types, reuses one without changing its stored type, creates canonical
when absent, and rejects two matches. Its inverse relationship maps either
stored Monthly identity to the canonical model without a global morph map.

Admin/report filters must keep exact-pair normalization. Historical audit and
notification payloads remain unchanged.

## Stable business aliases

The following are business discriminators and **must never be backfilled to PHP
class names**:

- `monthly_activity`
- `ramadan_iftar`
- other catalogue-approved `EventSubjectTypes` aliases used by
  `event_target_group`, `execution_teams`, `event_supplies`,
  `subject_execution_needs`, `subject_volunteer_requirements`, and
  `monitoring_reports`
- verification `detail_type` values such as `meal`, `gift`, `program_segment`,
  `execution_team`, `volunteer_requirement`, `supply`, and `execution_need`

## Remaining legacy-reference classification

- **Compatibility constants:** exact legacy strings in
  `EventAggregateIdentity`, `EventRequestModelIdentity`, and
  `PostExecutionVerificationIdentity`.
- **Historical fixtures/tests:** deliberately constructed old identities proving
  dual-read, inverse resolution, and duplicate rejection.
- **Documentation/history:** phase records describing the pre-cutover state.
- **Staging inventory queries:** exact old/new values in the staging runbook.
- **Stale production imports/executable references:** zero after final audit.

## Identity test inventory

| Group | Tests | Scope | Runtime/staging dependency |
|---|---|---|---|
| Agenda aggregate | `tests/Unit/EventAggregateIdentityTest.php`; `tests/Feature/AgendaEventIdentityCompatibilityTest.php` | exact mapping, installed class, writer, workflow reuse/conflict | unit/source plus Laravel DB feature |
| Request models | `tests/Unit/EventRequestModelIdentityTest.php`; `tests/Feature/EventRequestWorkflowIdentityCompatibilityTest.php` | four pairs, installed models, canonical creation, mixed-history reuse/conflict | unit/source plus Laravel DB feature |
| MonthlyActivity | `tests/Unit/MonthlyActivityAggregateIdentityTest.php`; `tests/Feature/MonthlyActivityIdentityCompatibilityTest.php`; `tests/Feature/MonthlyActivityControllerRouteContractTest.php` | class installation, workflows, request aggregates, route contract | Laravel boot/DB for feature tests |
| Official correspondence | `tests/Feature/MonthlyActivityIdentityCompatibilityTest.php` | legacy/canonical inverse read, reuse, canonical create, conflict | Laravel DB |
| PostExecutionVerification | `tests/Unit/PostExecutionVerificationIdentityTest.php`; `tests/Feature/PostExecutionVerificationIdentityCompatibilityTest.php` | exact audit pair, writer, installed model, table and relationships | unit/source plus Laravel DB feature |
| Report normalization | `tests/Feature/AdminReportsMonthlyExecutionStatusTest.php`; identity compatibility feature tests | accepted-pair filters and nonduplicating history | Laravel DB |

No runtime execution is claimed by this document.

## No-backfill and retirement policy

Legacy compatibility remains. Retirement requires a staging inventory, a
production observation window, proof that no legacy writer remains, an explicit
backfill/retention decision, and a tested rollback plan. Compatibility may remain
permanently if removal has no operational value. Never infer permission to
rewrite history from source completion.

## Remaining gate

Only staging/runtime verification remains. Execute
`docs/events-staging-cutover-runbook.md`; do not schedule another identity
namespace source phase.
