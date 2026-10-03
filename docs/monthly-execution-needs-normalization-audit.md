# Monthly execution-needs normalization readiness audit

**Phase:** 2.10
**Decision:** `KEEP LEGACY STORAGE`
**Scope:** semantic/data-contract audit only; no production or schema change

## 1. Executive conclusion

Historical and current Monthly execution-needs semantics cannot be represented
losslessly by the current `subject_execution_needs` schema.

Monthly stores a versioned, deeply structured planning document plus a separate
per-need decision/evaluation/post-execution document. The Common row has only a
required boolean, two free-text detail fields, a two-state status, and one
completion timestamp. It has no structured availability, section-specific
fields, decision role/name, rejection reason, effectiveness/evaluation scores,
post-execution provided/not-provided result, or feedback fields.

The canonical type vocabulary is useful and substantially reconciled, but type
mapping does not solve value/lifecycle mapping. In particular the Monthly
`certificates_thanks` decision key splits into two canonical types while its
planning payload already carries separate certificate and thanks-letter detail
sections. A single historical follow-up decision cannot be duplicated into two
rows without inventing actor/status semantics.

Keep both Monthly JSON columns authoritative. Do not add a read adapter or
transition yet: current consumers require the full JSON contracts, and no
consumer needs a Common-shaped Monthly view. A future redesign must first add a
live-data shape inventory and define a richer normalized detail/decision model;
it must not flatten data into `planned_details`/`actual_details` text.

## 2. Monthly legacy column inventory

| Column | Shape | Writer | Readers | Lifecycle/business meaning |
|---|---|---|---|---|
| `monthly_activities.execution_needs_payload` | nullable JSON object; current normalizer emits `schema_version = 2`, registry, availability map, enabled flags, and nested section details | Monthly planning create/edit normalization | Monthly model selection logic, planning/show UI, communications request gating, change formatter/history, admin reports, Zaha Time statistics | authoritative planning selection, center availability, and section-specific planned detail |
| `monthly_activities.execution_needs_followup` | nullable JSON array in persisted normalized form; keyed request objects are normalized to rows containing `key` | planning/decision endpoints and post-execution lifecycle merge | approval queues, decision UI, post-execution UI/completion, feedback/dashboard counts, admin reports | supplements the plan with pre-execution decision, evaluation and post-execution evidence; does not overwrite payload |

Both columns are Eloquent-cast to arrays. Neither stores independent relational
row IDs. The aggregate's workflow and request history can snapshot the complete
JSON values, so key presence, null/false distinctions, nested structure, and old
values remain historically relevant.

## 3. Supported planning JSON shapes

### Current normalized schema v2

Top-level keys emitted or consumed by current code:

```text
schema_version = 2
needs_registry
availability
needs_ceremony_agenda
ceremony
needs_transport
transport
needs_maintenance_workers
maintenance
needs_gifts
gifts
needs_programs_participation
programs
needs_certificates_and_thanks
certificates
thanks_letters
needs_invitations
invitations
```

`needs_registry` has entries for `ceremony`, `transport`, `maintenance`, `gifts`,
`programs`, `certificates`, `thanks_letters`, and `invitations`, each structurally:

```text
need_code
enabled
availability
future_cycle_id
```

`availability` stores `available` or `not_available` for the center-availability
codes, including needs selected from legacy aggregate columns/relations such as
volunteers, official correspondence, media coverage, supplies, sponsorship and
partners. Configuration may force selected codes to `not_available`.

Section detail shapes are:

```text
ceremony:
  need_code, future_cycle_id, items_count, time_from, time_to,
  item_name, item_description,
  items[]: order, name, time_from, time_to, description

transport:
  need_code, future_cycle_id, vehicles_count, vehicle_type,
  passengers_count, trip_direction, start_from, start_to

maintenance:
  need_code, future_cycle_id, type

gifts:
  need_code, future_cycle_id, count, description, delivery_entity

programs:
  need_code, future_cycle_id, need_trainer, trainer_description,
  trainer_count, zaha_time_options[], zaha_time_other,
  show_name, show_description, fun_note

certificates:
  need_code, future_cycle_id, count, template, for

thanks_letters:
  need_code, future_cycle_id, count, template, for

invitations:
  need_code, future_cycle_id, type, paper_template, paper_copies,
  electronic_template
```

### Historical/compatible shapes proven by readers/tests

The formatter and accessors tolerate payloads without `schema_version`,
`needs_registry`, `availability`, `need_code`, or `future_cycle_id`. They also
support partial nested section objects, explicit false `needs_*` flags, null or
empty detail values, and arbitrary section ordering. Showcase fixtures emit a
schema-v2-like payload without every registry/availability compatibility key.
Therefore “missing,” `null`, `false`, empty string/array, and absent section are
not safely interchangeable without live characterization.

Monthly enabled-need discovery is not JSON-only. It combines:

- aggregate booleans: volunteers, official correspondence, media coverage,
  sponsorship and partners;
- existence of related supplies;
- JSON `needs_*` flags for ceremony, transport, maintenance, gifts, programs,
  certificates/thanks, and invitations.

A migration must reconcile all of these sources, not merely iterate JSON keys.

## 4. Monthly follow-up JSON contract

Current normalized persisted rows support:

```text
key
status: secured | not_secured | null
reason
notes (normalized mirror of reason)
effectiveness_score: 0..10 | null
evaluation_score: 0..100 | null
evaluation_reason
decision_by_role
decision_by_name
post_status: provided | not_provided | null
post_feedback
```

The incoming form may be keyed by execution-need code and omit `key`; the
normalizer converts it to a values array with explicit `key`. Non-array and
fully empty rows are removed. Invalid status values become null. `reason` falls
back to `notes`; normalized output writes both. Empty numeric values become
null. Decision role/name may be populated from the authenticated actor and
allowed role matrix, but only names/roles—not user IDs or decision timestamps—
are stored.

Decision endpoints write `secured` for approved and `not_secured` for rejected.
Post-execution requests merge only `post_status` and `post_feedback` into the
existing row while deliberately protecting decision actor fields. Follow-up is
filtered to currently enabled needs in relevant lifecycle paths, but historical
or directly stored partial arrays may exist. Code permits the column to be null;
there is no database constraint tying a follow-up row to a payload entry.

The JSON therefore combines three distinct stages:

1. pre-execution owner decision (`status`, reason/notes, role/name);
2. optional effectiveness/evaluation (`effectiveness_score`,
   `evaluation_score`, `evaluation_reason`);
3. post-execution evidence (`post_status`, `post_feedback`).

## 5. Common contracts

### ExecutionNeedType

The canonical master contains code, Arabic name, description, order, active and
canonical flags, plus Monthly/Ramadan applicability flags. Canonical bootstrap
is idempotent. Ramadan selection queries active, canonical,
`is_ramadan_iftar = true` rows.

Canonical definitions:

| Code | Name | Ramadan selectable |
|---|---|:---:|
| `volunteers` | الحاجة للمتطوعين | yes |
| `official_correspondence` | الحاجة للمخاطبة الرسمية | yes |
| `media_coverage` | الحاجة لتغطية إعلامية | yes |
| `supplies` | الحاجة للمستلزمات | yes |
| `official_sponsorship` | الحاجة لرعاية رسمية | no |
| `external_partners` | الحاجة لشركاء خارجيين | no |
| `ceremony_agenda` | الحاجة لوجود أجندة حفل | no |
| `transport` | الحاجة لتأمين مواصلات | yes |
| `maintenance_workers` | الحاجة لعمال صيانة بالموقع | yes |
| `gifts_shields` | الحاجة لهدايا ودروع | yes |
| `programs_participation` | الحاجة لمشاركة البرامج | no |
| `certificates` | الشهادات | no |
| `thanks_letters` | كتب الشكر | no |
| `invitations` | الحاجة إلى بطاقات دعوة | yes |

### SubjectExecutionNeed

One row per subject/type is enforced by a unique key. Columns are:

```text
id
subject_type
subject_id
execution_need_type_id (restricting FK)
is_required (boolean, default true)
planned_details (nullable text)
status (pending by default)
actual_details (nullable text)
completed_at (nullable timestamp)
timestamps
```

Supported status values in the model are only `pending` and `completed`. There
are no decision/evaluation actors, decision timestamps, rejection state,
availability, scores, structured section fields, or post-result enum.

Ramadan planning writes selected and explicit `is_required` rows with planned
text, preserving execution fields on plan edits. Execution owns status,
`actual_details`, and `completed_at`. Completion requires every required row to
be completed with non-empty actual details and a completion timestamp.
Approved-plan deep copy copies type, required flag, and planned details, resets
status to pending, and excludes actual details/completion time.

## 6. Canonical type mapping

| Monthly key/source | Canonical code | Exact? | Migration risk |
|---|---|:---:|---|
| `volunteers` | `volunteers` | yes | enabled state comes from aggregate column, not payload alone |
| `official_correspondence` | `official_correspondence` | yes | aggregate flag/related detail and forced availability differ |
| `media_coverage` | `media_coverage` | yes | aggregate flag and media notes are outside need row |
| `supplies` | `supplies` | yes | enabled via related-row existence; structured supplies live elsewhere |
| `official_sponsorship` | `official_sponsorship` | yes | aggregate sponsor semantics exist outside JSON; not Ramadan-selectable |
| `external_partners` | `external_partners` | yes | aggregate partner semantics exist outside JSON; not Ramadan-selectable |
| registry/section `ceremony` or runtime `ceremony_agenda` | `ceremony_agenda` | renamed equivalent | rich ordered agenda cannot fit planned text losslessly |
| `transport` | `transport` | yes | transport structure cannot fit planned text losslessly |
| registry/section `maintenance` or runtime `maintenance_workers` | `maintenance_workers` | renamed equivalent | structured type plus availability/decision fields remain |
| registry/section `gifts` or runtime `gifts_shields` | `gifts_shields` | renamed equivalent | count/entity/detail structure remains |
| registry/section `programs` or runtime `programs_participation` | `programs_participation` | renamed equivalent | trainer/Zaha/show/fun structure remains |
| follow-up `certificates_thanks` | `certificates` + `thanks_letters` | split | one decision/actor/result cannot be duplicated safely |
| payload `certificates` | `certificates` | yes at type level | combined enabled flag and follow-up decision remain ambiguous |
| payload `thanks_letters` | `thanks_letters` | yes at type level | combined enabled flag and follow-up decision remain ambiguous |
| `invitations` | `invitations` | yes | paper/electronic structured detail and forced availability remain |
| unknown key | none | no | quarantine; never fuzzy-map |

## 7. Field semantic matrix

| Business meaning | Monthly planning JSON/source | Monthly follow-up | Common column | Classification / losslessness |
|---|---|---|---|---|
| subject ownership | Monthly row identity | implicit | `subject_type`, `subject_id` | DIRECT using stable `monthly_activity`; no FK |
| need type | flags, registry/section code, aggregate source | `key` | type FK | DIRECT/RENAMED/SPLIT by explicit mapping; unknown is NO_EQUIVALENT |
| explicit required | enabled aggregate/flag and registry `enabled` | none | `is_required` | DERIVED; conflicting sources make it ambiguous |
| explicit not required | false flag/registry entry may preserve “no” | usually absent | `is_required=false` row | potentially DIRECT, but missing key is not explicit no |
| center availability | `availability.*`, registry availability | decision may later differ | none | NO_EQUIVALENT / LOSSY |
| structured planned details | section-specific nested fields | none | `planned_details` text | LOSSY if serialized/flattened; no schema contract |
| pre-execution decision | none | `secured` / `not_secured` | `status` pending/completed | NO_EQUIVALENT; approval/rejection is not completion |
| decision reason/notes | nested planning details may include notes | `reason`, `notes` | no dedicated field | NO_EQUIVALENT; putting in text conflates stage |
| decision actor role/name | none | `decision_by_role`, `decision_by_name` | none | NO_EQUIVALENT / LOSSY |
| decision timestamp | no | no per-row timestamp | timestamps/completed_at | DEFAULT_REQUIRED would fabricate attribution/time |
| effectiveness | none | `effectiveness_score` | none | NO_EQUIVALENT |
| evaluation score/reason | none | `evaluation_score`, `evaluation_reason` | none | NO_EQUIVALENT |
| post-execution result | none | `provided` / `not_provided` | status completed plus actual text | ambiguous and LOSSY; not-provided is not pending |
| post feedback | none | `post_feedback` | `actual_details` text | PARTIAL only; loses typed post status |
| completion time | none | no per-row timestamp | `completed_at` | NO_EQUIVALENT; cannot infer from aggregate update time |
| section future linkage | `future_cycle_id` placeholders | none | none | NO_EQUIVALENT |
| schema version | `schema_version` | none | none | NO_EQUIVALENT but required for historical parsing |

## 8. Explicit yes/no and status semantics

Monthly preserves explicit false in current `needs_*` flags and registry
`enabled=false`; missing keys may instead mean an older/partial shape. For
aggregate-backed needs, false comes from other columns or relation absence.
Follow-up row absence means no stored decision, not rejection. A row with null
status may still contain evaluation or post-execution fields.

Common supports explicit yes/no through an existing row's `is_required`, and
Ramadan may persist rows for selected canonical options with true or false.
Absence still means “no row,” not necessarily explicit no. A migration must not
convert every absent Monthly key to `is_required=false`, nor drop explicit false
registry entries.

Status mapping:

| Monthly state | Common state | Exact? | Ambiguity |
|---|---|:---:|---|
| no follow-up row / null status | `pending` | no | could be unanswered, disabled, historical partial, or absent |
| `secured` | `pending` or future approved state | no | resource commitment decision is not execution completion |
| `not_secured` | no supported equivalent | no | `pending` hides rejection; `completed` is false |
| `post_status=provided` | `completed` | partial | Common also requires actual details and completed time |
| `post_status=not_provided` | no exact equivalent | no | not-provided is neither pending nor successfully completed |
| effectiveness/evaluation only | no equivalent | no | Common has no assessment lifecycle |

## 9. Actor and timestamp risks

Monthly follow-up retains decision attribution as role and display name, not a
user FK. Normalization may infer those strings from the current authenticated
actor when omitted. No per-row decision or post-execution timestamp is stored;
aggregate timestamps and workflow/action logs cannot safely be assigned to a
specific need decision.

Common stores no actor and only `created_at`, `updated_at`, and `completed_at`.
Backfilling current time or `monthly_activities.updated_at` would invent history.
Dropping role/name would lose attribution. A safe normalized design therefore
needs an explicit policy/schema for decision actor snapshots and stage times
before migration.

## 10. UI, validation, and lifecycle comparison

Monthly planning shows a large multi-section form with checkboxes/switches,
center availability selects, and section-specific typed inputs. Requiredness is
mostly conditional on enabling each section; many detail fields remain nullable.
The decision UI is role-routed by `config/execution_needs.php`, writes
secured/not-secured plus comment and actor snapshot, and post-execution UI later
collects provided/not-provided plus feedback. Planning payload and follow-up are
edited at distinct lifecycle stages and merged rather than flattened.

Ramadan planning presents canonical type selection, explicit required boolean,
and general planned-details text. Execution later accepts only owned row IDs,
pending/completed status, and actual-details text. Completion applies a strict
rule to required rows. These simpler Common semantics cannot currently render or
validate Monthly's section detail contracts.

Monthly planning payload is authoritative for planned details. Follow-up
supplements it with decisions/evaluation/evidence. Follow-up can technically
exist with null/partial planning because the JSON columns lack referential
constraints, though normal UI paths filter to enabled needs. Approved/returned
plan editability is controlled by the Monthly workflow, and snapshots can retain
old payload values; follow-up does not overwrite payload.

## 11. Workflow, change-history, and reporting dependencies

Monthly workflow approval screens derive enabled needs from aggregate flags,
relations and payload, then route each pending decision to configured roles.
Decision results are stored in follow-up JSON. Post-execution submission merges
post fields into those rows, changes aggregate lifecycle state, and writes
workflow actions. Neither JSON document is an independent workflow entity.

Monthly edit requests store `old_values`, `new_values`, and `changed_values`;
planning snapshots include both JSON columns. `MonthlyActivityChangeValueFormatter`
knows how to render and compare nested payload sections and deliberately hides
internal keys while preserving user-visible details. Replacing current reads
would affect historical comparison unless immutable JSON snapshots remain
supported indefinitely.

`AdminReportsService` directly loads both JSON columns. It counts activities
with payload/follow-up, counts `secured` and `not_secured` rows, averages
`effectiveness_score`, and reads `programs.zaha_time_options`. Monthly show,
approval, feedback, communications, and planning pages also decode specific
paths. Reports do not depend on JSON object order, but do depend on exact key
names/shapes and explicit states.

Trash/restore preserves the JSON on the soft-deleted Monthly aggregate. Delete
requests and old approval/change history may still display captured JSON even if
future current-row storage changes.

## 12. Architecture options

| Option | Benefits | Risks | Decision |
|---|---|---|---|
| A. Keep Monthly JSON permanently | maximum historical fidelity and no workflow/report rewrite | continued parsing and feature complexity | **selected for current schema/requirements** |
| B. Full migration to current subject rows | normalized ownership/type FK | highly lossy details, actors, scores, availability and statuses; unsafe rollback/reporting | reject |
| C. Legacy history + Common for new versions | avoids rewriting old JSON | two Monthly semantics, cutoff/version/report complexity, permanent legacy readers | reject |
| D. Read adapter first | could offer Common-shaped totals while JSON stays canonical | no current consumer requires it; any projection drops rich data | defer |
| E. Structured compatibility transition | can eventually preserve history with staged read/backfill/write rollout | requires richer schema/contract and live inventory first | future redesign candidate, not currently recommended for implementation |

## 13. Final architecture decision

`KEEP LEGACY STORAGE`

This is not a declaration that JSON is ideal forever. It means the current
Common row is not an adequate lossless target and no present consumer justifies
a reduced projection. `execution_need_types` remains the shared canonical
vocabulary; Monthly payload/follow-up remain the authoritative Monthly
transaction documents; `subject_execution_needs` remains canonical for
Ramadan/Common flows.

## 14. Preconditions for any future compatibility transition

A future design may reconsider Option E only after all of these are complete:

1. production live-data preflight and unknown-shape quarantine;
2. a normalized schema capable of preserving structured planned details,
   availability, decision result/reason/actor snapshot, evaluation fields,
   post-result/feedback, and trustworthy stage timestamps;
3. explicit treatment of missing/null/false/empty and combined/split keys;
4. compatibility readers that keep immutable JSON history available;
5. idempotent backfill into separate candidate rows with reconciliation reports;
6. single legacy writer during validation—no dual-write yet;
7. explicit writer cutover only after behavior/report parity;
8. observation and rollback window;
9. later legacy-writer retirement; JSON columns retained until historical
   readers and rollback requirements expire.

No stage is authorized by Phase 2.10.

## 15. Required live-data audit

No counts are claimed. Run only against an explicitly authorized database.
Portable SQL provides null/non-null counts; JSON introspection below is
MySQL/MariaDB-specific and must be translated for PostgreSQL/SQLite.

```sql
SELECT
  COUNT(*) AS total,
  SUM(execution_needs_payload IS NOT NULL) AS with_payload,
  SUM(execution_needs_followup IS NOT NULL) AS with_followup,
  SUM(execution_needs_payload IS NOT NULL AND execution_needs_followup IS NULL) AS payload_only,
  SUM(execution_needs_payload IS NULL AND execution_needs_followup IS NOT NULL) AS followup_only
FROM monthly_activities;
```

```sql
-- MySQL/MariaDB: top-level payload shapes and schema versions
SELECT JSON_TYPE(execution_needs_payload) AS json_type,
       JSON_UNQUOTE(JSON_EXTRACT(execution_needs_payload, '$.schema_version')) AS schema_version,
       COUNT(*) AS row_count
FROM monthly_activities
WHERE execution_needs_payload IS NOT NULL
GROUP BY json_type, schema_version;
```

```sql
-- MySQL 8+: distinct top-level payload keys
SELECT DISTINCT keys_table.payload_key
FROM monthly_activities m
JOIN JSON_TABLE(JSON_KEYS(m.execution_needs_payload), '$[*]'
  COLUMNS(payload_key VARCHAR(255) PATH '$')) keys_table
WHERE m.execution_needs_payload IS NOT NULL
ORDER BY keys_table.payload_key;
```

```sql
-- MySQL 8+: distinct normalized follow-up keys/status combinations
SELECT f.need_key, f.decision_status, f.post_status, COUNT(*) AS row_count
FROM monthly_activities m
JOIN JSON_TABLE(m.execution_needs_followup, '$[*]' COLUMNS(
  need_key VARCHAR(100) PATH '$.key',
  decision_status VARCHAR(50) PATH '$.status',
  post_status VARCHAR(50) PATH '$.post_status'
)) f
WHERE m.execution_needs_followup IS NOT NULL
GROUP BY f.need_key, f.decision_status, f.post_status
ORDER BY f.need_key, f.decision_status, f.post_status;
```

Also inventory:

- unknown payload/registry/section/follow-up keys against explicit legacy maps;
- missing keys, JSON null, false, empty strings/arrays and malformed row types;
- registry `enabled` values conflicting with top-level flags;
- aggregate-backed enabled state conflicting with payload/registry entries;
- follow-up keys with no enabled planning need;
- duplicate follow-up keys within one activity;
- `reason` versus `notes` differences;
- actor name without role and role without name;
- invalid/unknown decision/post statuses and out-of-range scores;
- combined `certificates_thanks` decisions versus separate detail sections;
- existing subject rows already using `monthly_activity`, if any;
- request/change-history snapshots containing shapes no longer present on current
  activities.

## 16. Test inventory and future gaps

Existing tests cover schema-v2 saving, center availability, forced unavailable
codes, enabled-need discovery, decisions, post-status merge, completion,
admin-report aggregates, rich formatting/comparison, canonical mapping,
Ramadan explicit required flags, ownership, execution completion, and version
copy/reset behavior.

A future transition requires additional tests for:

- every historical/partial JSON shape and malformed-value quarantine;
- missing versus null versus false versus empty semantics;
- every exact/renamed/split mapping and unknown keys;
- explicit yes and explicit no preservation;
- partial follow-up and follow-up-without-plan behavior;
- actor snapshot and timestamp preservation/non-fabrication;
- immutable request/approval/change-history rendering;
- old JSON and candidate-row report parity;
- combined certificates/thanks reconciliation;
- idempotent backfill, mixed old/new reads, one canonical writer, no duplicate
  rows, rollback, and JSON fallback;
- Monthly workflow and role authorization parity;
- Ramadan regression isolation.

## 17. Integrity and runtime debt

NO MONTHLY EXECUTION-NEEDS DATA WAS MIGRATED
NO MONTHLY EXECUTION-NEEDS WRITER WAS CHANGED
NO LEGACY JSON COLUMN WAS REMOVED OR RENAMED
NO DUAL-WRITE WAS INTRODUCED
NO BUSINESS OR WORKFLOW RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

`PHASE 2.10 COMPLETE`
