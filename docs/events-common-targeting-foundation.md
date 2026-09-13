# Common Event targeting foundation

Phase 1.3 adds shared targeting storage without changing the current Monthly Activities read or write paths.

- `target_groups` keeps all existing columns and gains `is_monthly_activity` and `is_ramadan_iftar`, both defaulting to `true`. These flags are intentionally simpler than a generic applicability table while only two Event consumers exist. Current Agenda and Monthly Activities queries continue to use only the existing active filter.
- `beneficiary_segments` defines age, gender, social, or other segmentation. Initial rows are children, adolescents, youth, women, and other. Official age boundaries are unresolved, so every seeded `minimum_age` and `maximum_age` is intentionally `null`; women is a gender segment and other carries `is_other = true`.
- Phase 2.3 generalized the established `event_target_group` pivot to store a controlled subject alias/id, target group, optional beneficiary segment, planned and actual counts, custom “other” text, and notes. Existing Monthly row IDs and `monthly_activity_id` remain intact; Ramadan uses the subject ownership columns.
- Request-level requirements for target-group and segment custom “other” text are deferred to the first real Ramadan write flow. No premature validation service is introduced.
- Existing Monthly pivot rows receive their `monthly_activity` subject alias in place. Monthly scalar fields and relationships remain compatible; no dual-write exists.
- Execution Needs remain deferred under the Phase 1.2 decision gate.
