# Common Event targeting foundation

Phase 1.3 adds shared targeting storage without changing the current Monthly Activities read or write paths.

- `target_groups` keeps all existing columns and gains `is_monthly_activity` and `is_ramadan_iftar`, both defaulting to `true`. These flags are intentionally simpler than a generic applicability table while only two Event consumers exist. Current Agenda and Monthly Activities queries continue to use only the existing active filter.
- `beneficiary_segments` defines age, gender, social, or other segmentation. Initial rows are children, adolescents, youth, women, and other. Official age boundaries are unresolved, so every seeded `minimum_age` and `maximum_age` is intentionally `null`; women is a gender segment and other carries `is_other = true`.
- `subject_target_groups` stores a controlled subject alias/id, target group, optional beneficiary segment, planned and actual counts, custom “other” text, and notes. `subject_id` has no foreign key because the parent is polymorphic. The table uses regular subject and combination indexes rather than a unique constraint because nullable segment IDs and future write semantics make database uniqueness ambiguous.
- Request-level requirements for target-group and segment custom “other” text are deferred to the first real Ramadan write flow. No premature validation service is introduced.
- Monthly Activity target-group rows are not backfilled or dual-written. Its scalar fields and `event_target_group` pivot remain authoritative until the dedicated migration phase.
- Execution Needs remain deferred under the Phase 1.2 decision gate.
