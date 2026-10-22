# Production seeder safety audit

**Audit date:** 2026-10-03
**Command audited:** `php artisan db:seed --class=rightDatabaseSeeder`
**Method:** static source trace only; the command was not executed.

## Call graph and classification

`DatabaseSeeder` invokes only `rightDatabaseSeeder`. The production-oriented chain is:

- `rightDatabaseSeeder`
  - `EventReferenceDataSeeder`
    - `DepartmentSeeder` — idempotent canonical update; preserves unknown rows.
    - `EventTypeSeeder` — idempotent canonical update; preserves unknown rows.
    - `TargetGroupSeeder` — idempotent canonical update; now preserves unknown rows and their active state.
    - `MonitoringMethodSeeder` — additive `insertOrIgnore`.
    - `EventStatusLookupSeeder` — idempotent canonical update; preserves unknown rows.
    - `EventCategorySeeder` — idempotent canonical update; preserves unknown rows.
  - `CanonicalExecutionNeedTypeSeeder` — additive canonical inserts/upgrades; Bazaar config is merged only when absent, preserving all existing and unknown `module_config` keys and explicit Bazaar configuration.
  - `RamadanReferenceDataSeeder`
    - `MobilizationMethodSeeder` — additive `insertOrIgnore`.
    - `CommunityOrganizationSeeder` — additive `firstOrCreate` per existing branch.
    - `LocalCommunitySeeder` — additive `firstOrCreate` per existing branch.
    - `MealTypeSeeder` — additive `firstOrCreate`.
    - `RamadanPeriodSeeder` — additive `firstOrCreate`; it validates legacy settings and activates the initial period only when no active period exists.
    - `RamadanIftarGuidanceSeeder` — hash/version additive; never replaces the administrator's active version.
    - `RamadanDashboardSettingSeeder` — additive `firstOrCreate`.
  - `CompleteRolePermissionSeeder`
    - `RolePermissionSeeder` — idempotent permission metadata updates; no deletion.
    - `RolesSeeder` — idempotent role metadata updates and additive grants; one documented intentional revoke below.
    - `WorkflowSeeder` — now additive `firstOrCreate` for canonical workflows/steps; preserves administrator-added and historical steps and does not rewrite existing step configuration.
    - `EvaluationWorkflowAccessSeeder` — idempotent permission/role metadata updates and additive grants.

No demo, showcase, staging, temporary-user, or sample-record seeder is reachable from `DatabaseSeeder` or `rightDatabaseSeeder`. `BeneficiarySegmentSeeder` deactivates its deprecated catalogue, but it is not reachable from either production chain. User/demo seeders containing `syncRoles()` and showcase seeders containing record deletion are likewise outside the production chain.

## Unsafe findings and fixes

| Seeder | Unsafe behavior | Risk | Fix |
|---|---|---|---|
| `TargetGroupSeeder` | Deactivated every target group outside the six canonical codes. | Silently disabled administrator-created or historical production groups. | Removed blanket `whereNotIn(...)->update()`; canonical rows remain updated/created while unknown rows are preserved. |
| `WorkflowSeeder` | Deleted steps missing from the code catalogue and overwrote existing canonical workflow/step fields. | Removed historical/admin-managed steps and could unexpectedly reset configured workflow behavior. | Changed canonical workflow/step writes to additive `firstOrCreate` and removed missing-step deletion. |
| `CanonicalExecutionNeedTypeSeeder` | Existing canonical rows did not receive Bazaar availability; replacing the full JSON would have endangered unknown module keys. | Bazaar could be unavailable on existing databases, while a naïve fix could reset Monthly/Ramadan or future module settings. | Merge only the missing `bazaar` key into the existing cast array; preserve existing Bazaar, Monthly, Ramadan, and unknown keys. |

## Permission safety and intentional revoke

`RolePermissionSeeder`, `RolesSeeder`, and `EvaluationWorkflowAccessSeeder` use `updateOrCreate` for catalogue identities and `givePermissionTo` for additive grants. They do not use `syncPermissions`, delete permissions or roles, clear pivot tables, or replace user role assignments.

One explicit, pre-existing business correction remains:

| Seeder | Role | Permission | Why revoked | Production safe? |
|---|---|---|---|---|
| `RolesSeeder` | `followup_officer` | `ramadan_iftars.execute` | Ramadan actual entry belongs to Relations; Follow-up verifies and must not execute. | Yes, intentional and narrowly scoped; it does not affect unrelated permissions or user roles. |

## Result

From the traced source, `php artisan db:seed --class=rightDatabaseSeeder` is production-safe with respect to preserving unrelated permissions, roles, user-role assignments, settings, reference records, workflow steps, and unknown module configuration. It performs the single intentional permission revoke listed above. Seed execution can still stop safely on invalid/incomplete legacy Ramadan settings or a changed/missing guidance source; those guards do not delete or reset data.
