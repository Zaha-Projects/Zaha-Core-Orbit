# Common Event monitoring foundation

Phase 1.8 adds shared `monitoring_reports` and owned `field_verifications` for Ramadan-first monitoring without changing existing Monthly Activity behavior.

- Legacy `monthly_activity_followups` remains a Monthly Activity remark log, while `post_execution_verifications` remains the branch-scoped evaluation input using `original_value`, `corrected_value`, and `pending`/`correct`/`incorrect`. Existing services, workspaces, JSON payloads, controllers, views, and evaluation behavior remain authoritative and untouched.
- Monitoring reports use indexed `subject_type` and `subject_id` ownership, allow multiple visits/revisions per subject, and use `draft`, `submitted`, `returned`, and `approved` lifecycle codes without transitions or workflow. The Ramadan relationship is explicitly constrained to `ramadan_iftar`.
- Monitoring method deletion is restricted to preserve evidence. Monitor and verifier user references are nullable and become null on user deletion; reports and verification snapshots remain stored.
- Field verifications store `planned_value` and `actual_value` as JSON historical snapshots. This approved snapshot use does not replace relational meals, gifts, teams, supplies, target groups, or other operational records.
- Nullable `detail_type` and `detail_id` identify a checked row only as reference metadata. No FK or dynamic class resolution is possible across heterogeneous detail tables. Repeated field keys are allowed for different detail rows.
- A monitoring report owns its verification rows through a cascading FK. Conversely, polymorphic `subject_id` has no FK; permanent Event deletion must transactionally clean `subject_target_groups`, `execution_teams`, `subject_volunteer_requirements`, `subject_supplies`, and `monitoring_reports` in a future write/delete layer.
- No snapshots are generated automatically and no actual values are propagated to operational data. Evaluation, attachments, Execution Needs, monitoring UI, CRUD, workflow, and notifications remain separate and deferred.
