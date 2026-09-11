# Ramadan Iftar planning flow

Phase 1.9 adds only `create`, `store`, `edit`, and `update` routes beneath `dashboard/events/ramadan/iftars`. Access temporarily reuses the established relations planning roles and `monthly_activities.create` / `monthly_activities.edit` permissions until dedicated Ramadan permissions are introduced; authentication and branch isolation remain mandatory.

`StoreRamadanIftarRequest` validates the core plan, Ramadan-applicable active target groups, active beneficiary segments and mobilization methods, branch-owned references, and nested planning payloads. System and execution fields are absent from its validated contract. Common rows always receive the server-owned `ramadan_iftar` alias and parent ID.

`RamadanIftarPlanningService` is the single transaction boundary for the parent, targeting, meals/items, gifts, program segments, execution teams/members, volunteer requirements, and supplies. Expected attendance is the sum of target-group planned counts; planned meals are the sum of meal planned quantities. Gift totals are derived in integer cents from unit value and planned quantity.

Draft updates synchronize rows by IDs scoped through the Iftar relationships. New rows are inserted, omitted planning-only rows are removed, and rows carrying actual/execution evidence are retained. Foreign child IDs are rejected rather than reassigned. Planning updates write only planning fields and preserve all actual, rating, task, availability, confirmation, and lifecycle values.

Agenda visibility and organization/community/user references are checked against the selected branch. Guidance acceptance remains absent because no versioned guidance source exists. Execution Needs remain deferred under the Phase 1.2 decision. Submission, approval, attendance, monitoring, post-execution, deletion, restoration, and workflow are outside this phase.
