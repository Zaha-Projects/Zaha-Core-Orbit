# Common Event execution foundation

Phase 1.7 adds `execution_teams`, `execution_team_members`, `subject_volunteer_requirements`, and `subject_supplies` for Ramadan-first use and later Monthly Activity adoption.

- Legacy Monthly Activities remain authoritative in `monthly_activity_team`, `monthly_activity_volunteer_needs`, and `monthly_activity_supplies`. Their email uniqueness, single volunteer row, availability defaults, insurance aliases, controllers, forms, JSON follow-up payloads, and workflow behavior are intentionally not copied or changed.
- Shared parents use the stable `subject_type` and `subject_id` pair. Model `forSubject()` scopes validate aliases through `EventSubjectTypes`; Ramadan relationships additionally constrain the alias to `ramadan_iftar`. No morph map or unrestricted subject relationship is used.
- `subject_id` has no foreign key because it can identify different Event tables. Consequently, direct database writes cannot enforce alias or orphan integrity. Future transactional write/delete logic must validate ownership and explicitly clean Common rows when a subject is permanently deleted.
- Teams allow multiple rows per subject. Members support registered users or lightweight external identity fields, nullable task evaluation, and optional confirmation. Team deletion cascades to members; deleting referenced users only nulls their references.
- Volunteer and supply statuses start at the repository-standard `pending` code. Broader status vocabularies and transitions remain deferred. Gender and provider fields remain lightweight strings rather than speculative lookups.
- Planned and actual counts remain separate, supply availability is nullable until assessed, and monetary estimates use decimal `(12, 2)` storage. No observers, synchronization, or calculations were introduced.
- Execution Needs remain excluded under the Phase 1.2 decision gate. Monitoring reports, field verification, attachments, evaluations, CRUD, workflow, and Monthly Activity migration remain deferred.
