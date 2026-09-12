# Ramadan Iftar core aggregate

Phase 1.5 creates the independent `ramadan_iftars` aggregate and `RamadanIftar` model. It has no Monthly Activity foreign key or model dependency.

- The aggregate belongs directly to an optional Agenda Event, a required Branch, a required relations officer and creator, and optional community organization, local community, and mobilization method references. Reference deletion nulls optional references; Branch and required users are restrictive and never cascade-delete the Iftar.
- `agenda_event_id` is indexed by its FK but is not unique. Existing Agenda/Monthly versioning permits multiple version rows, and the business rule for whether all Ramadan versions retain the same Agenda source is not finalized; a unique key would prematurely block that representation.
- Location codes reuse the existing application terms `inside_center` and `outside_center`. Host codes are `association`, `center`, and `local_community`. Conditional host/reference and custom “other” requirements are deferred to the Ramadan write layer.
- Planning `status` starts at `draft`; independent `execution_status` starts at `planned`. No workflow behavior is connected.
- `version_number` starts at 1 and `parent_version_id` is a nullable self-reference. Copy/version actions are deferred.
- No compatible versioned guidance table or model exists. `guidance_version_id` is therefore deferred; `guidance_accepted_at` is retained as nullable storage, but future submission must not use it until acceptance can be tied to an implemented guidance version.
- Likely branch list filters are supported by composite indexes for planned date, planning status, and execution status. Soft-delete filtering has its own index; FK columns receive their normal database indexes.
- `targetGroupSelections()` is an explicit `hasMany` constrained to the stable `ramadan_iftar` alias. It does not use a global morph map or unrestricted dynamic subject resolution.
- Meal and attendance totals are stored summaries only. Synchronization, workflow timestamps, host rules, mobilization “other” validation, and completion invariants are deferred to later write/workflow slices.
