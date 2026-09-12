# Execution Needs Phase 1.2 gate

## Production trace

- `ExecutionNeedType` is currently referenced only by its migration and `ExecutionNeedTypeSeeder`; the seeder call is commented out in `DatabaseSeeder`. No controller, service, request, view, report, or Agenda flow reads `execution_need_types` in production.
- Monthly Activities owns the live definition catalog through `MonthlyActivity::EXECUTION_NEED_DEFINITIONS`. `executionNeedDefinitions()` overlays decision roles from `config/execution_needs.php`, and `enabledExecutionNeeds()` determines selected needs from legacy columns, relationships, and `execution_needs_payload`.
- Users select needs in the Monthly Activity form. The controller validates the individual request fields, normalizes them into versioned `execution_needs_payload` JSON, and later stores decision/post-execution rows in `execution_needs_followup` JSON.
- Monthly Activity controllers, approval screens, show/edit views, reports, notifications, seeders, and formatters consume the model definitions or stored JSON. Agenda has no equivalent execution-needs selection or storage path.

## Sources compared

| Source | Keys/codes | Labels and order | Metadata | Authority / consumers |
|---|---|---|---|---|
| `execution_need_types` seed | `ceremony`, `transport`, `maintenance`, `gifts`, `programs`, `certificates`, `thanks_letters`, `invitations` | Arabic database names; `sort_order` 10–80; `is_active` | description | Not authoritative today; no production reader and its seeder is not enabled by `DatabaseSeeder`. |
| `config/execution_needs.php` | `volunteers`, `official_correspondence`, `media_coverage`, `supplies`, `official_sponsorship`, `external_partners`, `transport`, `maintenance_workers`, `gifts_shields`, `programs_participation`, `certificates_thanks`, `invitations` | No labels or ordering | decision roles; center-availability defaults and forced-unavailable keys | Authoritative for decision routing and availability normalization in Monthly Activities. |
| `MonthlyActivity::EXECUTION_NEED_DEFINITIONS` | The twelve config decision keys plus `ceremony_agenda` | Arabic labels in declaration order | default owner role, overlaid by config roles | Authoritative display/decision catalog for Monthly Activities. |
| `execution_needs_payload` JSON | Schema v2 registry uses DB-like codes for eight sections, while flags/definition keys use Monthly-specific names | No independent catalog order; structured details are rendered through model definitions and formatters | availability, enabled flags, section links, detailed payloads, `future_cycle_id` placeholders | Authoritative per-activity planned selection/details. |
| `execution_needs_followup` JSON | Definition keys such as `volunteers`, `maintenance_workers`, and `certificates_thanks` | Derived labels from model definitions | status, notes, scores, decision actor/role, post status/feedback | Authoritative per-activity decision and post-execution state. |

Only `transport` and `invitations` align exactly across the seeded DB codes, config decision keys, and Monthly definition keys. Other concepts are renamed (`ceremony` / `ceremony_agenda`, `maintenance` / `maintenance_workers`, `gifts` / `gifts_shields`, `programs` / `programs_participation`) or combined/split (`certificates` plus `thanks_letters` / `certificates_thanks`). The DB seed also omits six legacy/runtime needs. These sources are therefore not interchangeable.

## Decision

There is no production-backed shared Execution Needs read behavior to extract in Phase 1.2. Adding scopes to `ExecutionNeedType` would create unused code; switching consumers to it would change the source of truth and require code/data normalization. The repeated config access is confined to the Monthly Activities flow and is coupled to its existing decisions, JSON keys, permissions, and notifications.

The normalization prerequisite is now resolved for new Event consumers: `execution_need_types` is the canonical master, an explicit compatibility map covers every known legacy code, and Ramadan writes controlled `subject_execution_needs` rows. Monthly Activities intentionally remain on their legacy sources with no read, backfill, dual write, or behavior change. See `docs/events-execution-needs-normalization.md`.
