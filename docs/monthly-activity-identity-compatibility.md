# MonthlyActivity aggregate identity compatibility

## Status

Phase 2.17 is **DONE IN SOURCE / STAGING PENDING**. `MonthlyActivity` remains
installed at `App\Models\MonthlyActivity`; the future canonical identity is
`App\Modules\Events\Models\MonthlyActivity`. Both are recognized by
`EventAggregateIdentity`, while `installedModelFor()` and `currentWriteType()`
still select the legacy class. No wrapper, alias, model move, migration, or
backfill exists.

## Persisted identity inventory

| Boundary | Storage | Current writer | Compatibility reader |
|---|---|---|---|
| activity workflow | `workflow_instances.entity_type` | legacy | aggregate identity pair; conflict on two logical rows |
| workflow action history | `workflow_action_logs.entity_type` | legacy | exact Monthly filters use the aggregate identity pair |
| official correspondence | `official_correspondences.correspondable_type` | legacy | relationship/service accept the pair; conflict on duplicates |
| Monthly request aggregate | `monthly_plan_*_requests.entity_type` | legacy | business workflow lookup accepts the pair; request workflow identity remains separate |
| evaluation audit | `audit_logs.entity_type` | legacy | no exact Monthly audit reader was found; no speculative reader added |
| notifications | JSON metadata may copy request aggregate identity | legacy | navigation uses `action_url`; no dynamic FQCN instantiation found |
| common event children | stable `subject_type = monthly_activity` | stable alias | excluded from FQCN compatibility |

## Workflow contract

`DynamicWorkflowService` already centralizes aggregate find-before-create. It
queries both accepted MonthlyActivity identities, reuses exactly one, throws on
a mixed legacy/canonical duplicate, and creates with the current legacy writer.
Reports and exact workflow/action-log readers use the same accepted pair.

## Official correspondence decision

No global morph map is installed. The public `MonthlyActivity::officialCorrespondence`
relationship is a focused `hasOne` constrained by correspondence ID and both
accepted types. `OfficialCorrespondence::correspondable()` returns a normal
`belongsTo` to the currently installed MonthlyActivity model when either exact
Monthly identity is stored; unrelated polymorphic records continue through
Laravel's existing `morphTo()` behavior. These relationship APIs exist in
Laravel 8.83 and require no newer morph-map feature.

All operational correspondence writes go through
`MonthlyActivityOfficialCorrespondenceService`. It searches both identities
before write/delete, reuses one row, throws `LogicException` when both logical
identities exist, and creates with `currentWriteType()`, which remains legacy in
this preparation phase. The database unique key on `(correspondable_type,
correspondable_id)` remains useful but is not treated as logical duplicate
protection across FQCNs.

## Audit, notification, and aliases

Monthly evaluation audit writes currently store the legacy FQCN. No production
reader filters audit logs by the exact MonthlyActivity FQCN, so no speculative
resolver was added. Notification navigation is URL-based and does not dynamically
instantiate the copied aggregate identity. Stable common Events aliases such as
`monthly_activity` are business discriminators, not PHP identities, and remain
unchanged.

## Future Phase 2.18 requirements

The cutover must move the sole model definition, switch the aggregate helper's
installed/current writer to canonical, update PHP imports, retain all dual-read
boundaries, and stage mixed-history workflow/correspondence/report checks. It
must not backfill stored identities as part of the model move.
