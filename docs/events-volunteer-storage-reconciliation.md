# Events volunteer storage semantic reconciliation

**Phase:** 2.9
**Decision:** `KEEP SEPARATE`
**Change type:** design/audit only; no production code or schema change

## 1. Executive conclusion

The tables do not represent the same business fact.

- `monthly_activity_volunteer_needs` is one optional planning summary for a
  Monthly Activity. It answers whether volunteers are needed and describes one
  aggregate headcount, one age range, one gender selection, one short need, and
  one task summary. It has no execution actual, lifecycle status, beneficiary
  segment, category, role, skill, shift, location, or target-group dimension.
- `subject_volunteer_requirements` is a repeatable Event-subject planning line.
  Each line may target a beneficiary segment and gender, has planned and actual
  counts, participates in execution/monitoring, and carries a status. Current
  production consumers use it for Ramadan Iftars.

The superficial overlaps—count, gender, and tasks—are insufficient for a
lossless reinterpretation. Monthly-only `volunteer_need` and age-range data have
no Common columns; Common segmentation, actual count, and status have no Monthly
equivalents. A Monthly `both` gender value also differs lexically from Common
`mixed`. Mapping one summary to one unsegmented Common row would invent the
meaning “unsegmented requirement,” discard age/need semantics, and alter the
execution/versioning contract.

No unified read consumer currently requires an adapter. Keep both write paths
separate and document their boundary; reconsider only after a concrete
cross-Event reporting requirement supplies an explicit, non-lossy projection.

## 2. Storage and model inventory

### Monthly summary

| Property | Evidence-based meaning |
|---|---|
| Model/table | `App\Modules\Events\Models\MonthlyActivityVolunteerNeed` / `monthly_activity_volunteer_needs` |
| Owner | one `MonthlyActivity` through `monthly_activity_id` |
| Cardinality | zero or one; model relation is `hasOne`, database has unique `monthly_activity_id` |
| Planning fields | `volunteer_need`, `required_volunteers`, `volunteer_age_range`, `volunteer_gender`, `volunteer_tasks_summary` |
| Compatibility fields | `volunteers_required` and `volunteers_count`; current synchronizer derives both from `required_volunteers` |
| Actual fields | none |
| Status/actor fields | none |
| Segmentation | one free-form age range and one aggregate gender selection; no segment FK |
| Lifecycle | created/updated/deleted with Monthly planning toggle; read through Monthly accessors and views |
| Constraints | cascading Monthly FK; unique Monthly owner; all descriptive/count fields nullable; boolean defaults false |

### Common/Ramadan requirement lines

| Property | Evidence-based meaning |
|---|---|
| Model/table | `App\Modules\Events\Models\SubjectVolunteerRequirement` / `subject_volunteer_requirements` |
| Owner | stable `subject_type` + `subject_id`; current relation filters `ramadan_iftar` |
| Cardinality | zero to many; no owner uniqueness constraint; repeated planning UI |
| Planning fields | `beneficiary_segment_id`, `gender`, `planned_count`, `tasks_summary` |
| Actual fields | `actual_count` |
| Status/actor fields | `status` defaults to `pending`; no actor columns |
| Segmentation | optional beneficiary-segment FK and gender per line; no age text, role, skill, shift, task category, location, or target-group FK |
| Lifecycle | planned as repeatable lines, actual count recorded during execution, planned/actual included in monitoring |
| Constraints | required subject type/id and planned count; optional segment/gender/actual/tasks; subject and subject/status indexes |

The Common model validates its stable subject alias through
`EventSubjectTypes::modelFor()` in `scopeForSubject()`. The registry supports
`monthly_activity` and `ramadan_iftar`, so Monthly ownership is technically
possible, but technical addressability does not make the business facts equal.

## 3. Column semantic matrix

| Business meaning | Monthly column | Common column | Equivalent? | Transformable? | Lossy / migration risk |
|---|---|---|:---:|:---:|---|
| owner kind | implicit Monthly table | `subject_type` | no | derived as `monthly_activity` | low technically, but changes ownership contract |
| owner ID | `monthly_activity_id` FK | `subject_id` without FK | partially | direct numeric copy | loses database referential constraint |
| volunteer-needed toggle | aggregate `MonthlyActivity.needs_volunteers`; mirrored by `volunteers_required` | row existence only | no | derived | absence vs false and zero-line meaning differ |
| short need/category text | `volunteer_need` | none | no | no | data loss |
| planned aggregate headcount | `required_volunteers`; duplicated into `volunteers_count` | `planned_count` per line | partially | only as one synthetic unsegmented line | changes summary into line-item semantics |
| compatibility required flag | `volunteers_required` | row existence / count | no | derived | legacy contradictions are possible and need live inventory |
| compatibility count | `volunteers_count` | `planned_count` | partially | derived/selected | two Monthly count columns may disagree historically |
| age requirement | `volunteer_age_range` | possible `beneficiary_segment_id` | no | not without a catalogue mapping and invented segment | free text/ranges cannot be mapped losslessly |
| gender | `volunteer_gender` (`male`, `female`, `both`) | `gender` per line (`male`, `female`, `mixed` in UI) | partially | normalization required | `both` → `mixed` is a semantic assumption; multiple lines differ from one summary |
| task summary | `volunteer_tasks_summary` | `tasks_summary` per line | partially | direct only for one line | duplication/allocation undefined for multiple lines |
| beneficiary segment | none | `beneficiary_segment_id` | no | default `null` only | invents “unsegmented”; cannot recover segmentation |
| planned vs actual comparison | no actual count | `planned_count`, `actual_count` | no | no | Common execution fact cannot be represented Monthly-side |
| requirement lifecycle status | none | `status` | no | default required | fabricated lifecycle state |
| timestamps | timestamps | timestamps | structural only | copyable | migration would rewrite storage history unless explicitly preserved |

## 4. Cardinality and UI semantics

### Monthly

`MonthlyActivity::volunteerNeed()` is `hasOne`, reinforced by a unique database
constraint. The form presents a single “need volunteers” switch and one block
for count, age-from/age-to (normalized into one range string), gender, short
need, and task description. Count, gender, and task summary are conditionally
required when enabled; count must be at least one. Disabling the switch deletes
the one detail row and clears the planning values.

This is an intentional current user mental model: describe the activity's one
overall volunteer need, not a set of deployable requirement lines. The schema
cannot prove whether that choice began as a legacy/UI limitation, but current
validation, accessors, display, branch visibility, and planning synchronization
all rely on it as business behavior. It cannot be called merely accidental.

### Ramadan/Common

`RamadanIftar::volunteerRequirements()` is `hasMany`; tests explicitly create
and assert two lines. The repeated planning UI gives each row a beneficiary
segment, gender, planned count, and task summary, and allows add/remove. The
collection must be present but may be empty; each existing row requires a
non-negative planned count. Execution accepts only row IDs and optional
non-negative `actual_count`, preventing planned-field rewriting through the
execution request.

Thus Ramadan represents multiple segmented requirement lines with planned and
actual execution counts. It does not collect a textual age range, role, skill,
category, shift, location, or target group directly.

## 5. Monthly lifecycle map

| Stage | Current behavior / dependency |
|---|---|
| create/edit planning | programs/relations planning validation accepts the summary fields; `syncVolunteerNeed()` uses `updateOrCreate` for one owner row |
| disabled state | synchronizer deletes the Monthly detail row; normalizer clears submitted volunteer fields |
| approval/submission | the planning values are part of the Monthly plan representation and change/audit snapshots; no separate volunteer approval/status exists |
| workspace/display | Monthly accessors proxy the one related row; show/workshop pages render aggregate need/count/age/gender/tasks |
| execution | separate Monthly execution-needs JSON/follow-up tracks availability and post-status; the volunteer table itself has no actual count |
| post-execution | follow-up payload, not this row, records provision feedback/status; no row-level planned/actual monitoring occurs |
| reports/visibility | volunteer coordinator filtering uses `monthly_activities.needs_volunteers`; displays use the summary accessors; no raw reporting join to the detail table was found |
| change requests/versioning | volunteer planning values appear in Monthly planning/change snapshots; no independent deep-copy/version-owned volunteer row mechanism was found |
| trash/restore | Monthly uses its aggregate lifecycle; this detail model has no soft deletes; permanent parent deletion cascades |

The user entering the data is the actor permitted to create/edit the Monthly
planning form (programs/relations flow). Repository rules condition editability
on the surrounding Monthly lifecycle; the volunteer row has no independent
workflow.

## 6. Ramadan/Common lifecycle map

| Stage | Current behavior / dependency |
|---|---|
| planning create/edit | repeatable rows synchronized transactionally; planning owns segment, gender, planned count and tasks; actual count is protected from planning sync |
| approval/workspace | rows load with plans, queues, workspace, and execution pages; no independent approval workflow exists |
| execution | only `actual_count` is updated for owned row IDs after execution starts |
| monitoring | every row becomes a planned-vs-actual monitoring candidate |
| completion/closure | rows are part of the execution evidence feeding monitoring; no new volunteer-specific closure rule was found |
| versioning | approved-plan revision copies segment/gender/planned/tasks, resets status to pending, and deliberately does not copy `actual_count` |
| reporting | current Event workspace/show/monitoring views expose rows; no independent PDF/Excel/raw-SQL volunteer requirement export was found |

The rows are plan-version-owned indirectly because each row's subject ID points
to a specific Ramadan version. Unlike a true FK, subject ownership is enforced
by services/relations rather than the database.

## 7. Workflow, reporting, and historical compatibility

Neither table has an independent workflow entity, approval actor, or stored
model FQCN. Monthly approval depends on the surrounding plan and its snapshots;
Ramadan approval/versioning depends on the surrounding Iftar version. The
Common row status is operational state, not a separate DynamicWorkflow.

Historical Monthly reporting would change if readers were redirected because:

- existing accessors expect zero/one summary row;
- the `needs_volunteers` aggregate flag drives coordinator visibility;
- age range and short need would disappear without schema expansion;
- legacy `required_volunteers` and `volunteers_count` may require conflict rules;
- Common totals would require aggregation across lines rather than one value;
- Common actual/status fields have no historical Monthly source.

A live database audit would be required before any future reconsideration to
count missing/orphaned rows, compare the two legacy count columns and boolean,
inspect age/gender/free-text values, and identify activities whose aggregate
flag conflicts with row existence. Phase 2.9 performs no such runtime inventory
and makes no claim about production values.

## 8. Options considered

| Option | Benefits | Costs/risks | Decision |
|---|---|---|---|
| A. Keep separate permanently | preserves accurate business language, Monthly history, constraints, UI and reports; no dual-write | two models/tables and separate reporting projections | **selected under current requirements** |
| B. Migrate Monthly into subject storage | one physical table and shared subject querying | lossy age/need mapping, fabricated status/segmentation, cardinality/report/version rewrite, weaker FK, difficult rollback | reject |
| C. Compatibility adapter | can provide a unified read projection without data movement | no current consumer needs it; premature service/DTO abstraction | defer until a concrete cross-Event report requires it |
| D. Hybrid cutoff | new Monthly plans gain segmented execution rows while old plans remain intact | two semantics inside one aggregate, cutoff/version complexity, historical comparability issues, strong risk of indefinite dual storage/write | reject |

## 9. Final architecture decision

`KEEP SEPARATE`

Boundary terminology:

- **Monthly volunteer summary:** optional zero/one aggregate planning description
  in `monthly_activity_volunteer_needs`, supplemented at execution time by the
  existing Monthly execution-needs/follow-up structures.
- **Common/Ramadan segmented volunteer requirement:** repeatable planned/actual
  requirement lines in `subject_volunteer_requirements`, currently owned by a
  Ramadan Iftar subject/version.

The Common table may technically address `monthly_activity`, but Monthly must
not write it without a future business/UI decision that explicitly replaces the
summary concept with segmented lines. Do not add a permanent dual-write.

## 10. Test coverage and gaps

Existing static inventory found coverage for:

- Monthly conditional required-count validation;
- Monthly planning summary fields and execution-completion compatibility;
- volunteer-coordinator visibility driven by `needs_volunteers`;
- Common multiple-row cardinality and beneficiary-segment relationship;
- Ramadan planning persistence;
- Ramadan actual-count updates and ownership checks;
- Ramadan approved-plan deep copy with actual count reset.

Future runtime characterization should add explicit tests for:

- preservation of contradictory/null legacy Monthly compatibility fields;
- Monthly summary create, update, disable/delete, trash/restore and permanent
  cascade behavior;
- Monthly edit/change-request snapshots retaining all summary values;
- reporting totals from each model without conflating them;
- Ramadan empty/multiple segmentation, approval immutability, monitoring
  candidates, and revision status reset;
- proof that neither flow writes the other table.

## 11. Future plan

No implementation is currently justified. The next volunteer-specific slice,
only when a real cross-Event reporting consumer exists, should be:

**Phase 2.9A — Volunteer Requirement Read-Projection Contract Audit**

That design slice should define the exact report questions and whether totals
can be projected without pretending Monthly age/need data is Common
segmentation. If a projection is justified, prefer one focused read service with
two source-specific queries; keep both canonical writers separate. Do not add a
repository framework, shared writable model, migration, or dual-write.

## 12. Integrity and open runtime debt

NO VOLUNTEER DATA WAS MIGRATED
NO VOLUNTEER TABLE WAS RENAMED OR DELETED
NO BUSINESS RULE WAS CHANGED
NO DUAL-WRITE WAS INTRODUCED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

`PHASE 2.9 COMPLETE`

## 13. Phase 2.12 namespace ownership update

The semantic conclusion above remains unchanged. After a fresh identity and
route-binding recheck found no stored self-FQCN or public binding contract,
`MonthlyActivityVolunteerNeed` moved to
`App\Modules\Events\Models\MonthlyActivityVolunteerNeed`.

This is a namespace-only ownership correction. The model remains the
Monthly-specific zero/one planning summary, continues to use
`monthly_activity_volunteer_needs`, and remains distinct from
`SubjectVolunteerRequirement`. No adapter, Common write, migration, table
rename, cardinality change, or data conversion occurred.

NO VOLUNTEER TABLE WAS RENAMED
NO VOLUNTEER DATA WAS MIGRATED
NO VOLUNTEER CARDINALITY OR BUSINESS SEMANTICS WERE CHANGED
NO DUAL-WRITE WAS INTRODUCED
NO STORED WORKFLOW/AUDIT IDENTITY WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

`PHASE 2.12 COMPLETE`
