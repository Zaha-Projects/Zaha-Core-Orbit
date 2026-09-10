# Ramadan Event reference foundation

Phase 1.4 introduces `mobilization_methods`, `monitoring_methods`, `community_organizations`, and `local_communities` with matching models in the shared Events namespace.

- Repository inspection found no reusable mobilization, monitoring-method, community-organization, or local-community master table. The deprecated `centers` concept represented Zaha operating centers and was removed in favor of branches, so it is not reused for external associations/centers.
- Mobilization options are not yet approved beyond the need for an administrator-managed lookup and possible custom “other” handling. The schema supports `is_other`, but no speculative mobilization rows are seeded.
- Monitoring seeds only the confirmed source terms “Cameras” and “Field Visit”. The uncertain “Mystery Shopper” terminology is deliberately not seeded until its business name is confirmed.
- Organizations and local communities are branch-owned through restrictive foreign keys. They use `is_active`, matching current lookup/master-data practice, rather than soft deletes. No fake operational records are seeded.
- Future management should extend the existing super-admin Events lookup/master-data screen rather than introduce a second settings pattern; no CRUD is added in this phase.
- Single versus multiple hosts/supporters remains unresolved, so no stakeholder polymorphism is introduced. The Ramadan aggregate and all references from it are deferred to Phase 1.5.
- Existing Agenda, Monthly Activities, Target Groups, Execution Needs, routes, workflows, views, payloads, and storage do not consume these tables and remain unchanged.
