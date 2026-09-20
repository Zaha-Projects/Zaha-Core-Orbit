# MonthlyActivity aggregate identity compatibility

## Status

Phase 2.18 is **DONE IN SOURCE / STAGING PENDING**. The sole installed model is
`App\Modules\Events\Models\MonthlyActivity`; `App\Models\MonthlyActivity` is a
permanent historical storage identity only. No wrapper or class alias exists.
No migration or historical backfill was performed.

## Persisted identity inventory

| Boundary | New writer | Historical read contract |
|---|---|---|
| `workflow_instances.entity_type` | canonical MonthlyActivity FQCN | legacy and canonical; reuse one and reject mixed duplicates |
| `workflow_action_logs.entity_type` | canonical when class-derived | exact Monthly readers accept both identities |
| `official_correspondences.correspondable_type` | canonical MonthlyActivity FQCN | focused inverse resolution maps either identity to the canonical model; reuse one and reject mixed duplicates |
| `monthly_plan_*_requests.entity_type` | canonical aggregate FQCN | legacy and canonical aggregate identities remain readable; request workflow identity is separate |
| `audit_logs.entity_type` | canonical when class-derived | no exact Monthly audit reader was found; historical rows remain unchanged |
| notification JSON | canonical when class-derived | historical JSON remains unchanged and navigation remains `action_url` based |
| shared Events subject columns | stable `monthly_activity` alias | deliberately not an FQCN and unchanged |

## Compatibility contract

`EventAggregateIdentity` accepts both MonthlyActivity FQCNs and resolves both to
the canonical installed model. Its current writer is canonical. Workflow and
official-correspondence creation retain find-before-create semantics: no match
creates canonical, exactly one legacy/canonical match is reused without changing
its stored type, and a mixed pair throws `LogicException`. No dual write occurs.

`OfficialCorrespondence::correspondable()` uses focused MonthlyActivity handling;
no unrestricted global morph map was introduced. Monthly request relationships
use the canonical model while request rows with the historical aggregate FQCN
remain readable. Stable business aliases including `monthly_activity` were not
changed.

## Staging gate

Exercise legacy-only, canonical-only, absent, and mixed-duplicate workflow and
correspondence records; verify request history and new canonical request writes;
verify route model binding, approval/planning/execution flows, branch scope,
reports, audit emission, and action-log history against staging data.
`PostExecutionVerification` is **NOT CUT OVER** and is the next identity review.
