# Monthly monitoring and post-execution normalization readiness audit

**Phase:** 2.11
**Decision:** `KEEP MONTHLY POST-EXECUTION SEPARATE`
**Scope:** architecture/semantic audit only; no production or schema change

## 1. Executive conclusion

Monthly post-execution is not one monitoring concept. It is a composition of:

1. execution-actual evidence in `post_execution_payload`;
2. an embedded supervisor clarification/rejection snapshot in that payload;
3. legacy question responses and free-form follow-up remarks;
4. newer structured quality evaluation forms/answers;
5. field-level verification/correction rows;
6. Monthly approval, audit, notification and lifecycle records.

Common `monitoring_reports` is a monitoring envelope with method, monitor,
observation/submission data and draft/submitted/returned/approved lifecycle. Its
verification children compare planned and actual snapshots. It is not a generic
container for evaluations, feedback, follow-up actions, or Monthly closure.

The only semantically shared fact today is field verification, already stored in
the generalized `post_execution_verifications` table. Even there, Monthly uses
original/corrected values and pending/correct/incorrect while Ramadan uses
planned/actual snapshots and matched/mismatched/not-observed/not-applicable.
Those modes coexist but are not interchangeable.

No current evidence supports creating a Monthly monitoring envelope without
inventing a monitoring method and changing actors/lifecycle. Keep Monthly
post-execution, evaluation, follow-up, approval and history separate. Preserve
the existing shared verification table as a carefully bounded dual-mode model.

## 2. Monthly persistence inventory

| Storage | Purpose / fact | Owner/cardinality | Writer / actor | Lifecycle/status | Readers/history/report role |
|---|---|---|---|---|---|
| `monthly_activities.post_execution_payload` | execution actual/evidence document plus embedded post-execution review snapshot | one nullable JSON document per Monthly Activity | activity post-execution submitter; supervisor review mutates `review` | aggregate status becomes `post_execution_submitted`, clarification/rejection, or closed | forms/show/feedback, execution display status, verifier field generation, admin execution counts |
| Monthly scalar fields | actual date/attendance, evaluation score/reason, status, lifecycle/execution status, official flag, assignment fields | one aggregate | submitter/supervisor/evaluator depending field | Monthly lifecycle | dashboards, reports, workflow and display |
| `monthly_activity_evaluation_responses` | one legacy response to one evaluation question for the activity | many per activity, unique activity/question | Monthly planning/evaluation synchronization; `created_by` | no row status | activity edit/show and user profile response history |
| `monthly_activity_followups` | appendable free-form follow-up remark | many per activity | Monthly synchronization; `created_by` | no status, due date or completion field | activity detail/history; no Common report envelope behavior |
| `activity_evaluations` + answers | submitted weighted internal quality assessment with immutable question snapshots | one evaluation per activity, many answers | evaluator (`evaluated_by`) | submitted timestamp, visibility lifecycle; aggregate becomes evaluated | evaluation dashboards/details/statistics |
| `post_execution_verifications` | field verification/correction for Monthly; planned-vs-actual comparison for Ramadan | many per Monthly Activity or monitoring report | follow-up/evaluation verifier or Ramadan monitor | dual status vocabulary by mode | evaluation gate, follow-up workspace, Ramadan monitoring review |
| `monthly_activity_approvals` | plan and post-execution decision history | many per activity | approver | step/decision/comment/time | approval history and post-execution review evidence |
| `workflow_action_logs` | immutable lifecycle action history | many per aggregate | performing actor | action/status/time | workflow/history reporting |
| `audit_logs` | field verification and evaluation audit snapshots | many per entity | verifier/evaluator | action/module/time | generic audit reporting; verification FQCN identity-sensitive |
| notifications | delivery/history metadata for submission, clarification/rejection, close/evaluation | many | application services | notification state | user notification history |

## 3. `post_execution_payload` contract

### Current normalized execution evidence

When non-empty team/ceremony input exists, the lifecycle normalizer emits:

```text
schema_version = 1
completed_at
teams[]:
  team_name
  planned_members_count
  all_members_attended: true | false | null
  actual_attendance_count
  accomplished_tasks
ceremony_items[]:
  order
  name
  was_implemented: true | false | null
  feedback
```

Empty team/ceremony input preserves the existing payload rather than erasing it.
The payload does not record a dedicated submitter ID. `completed_at` is document
creation/completion time, while submitter identity is recoverable only from
surrounding request/action/notification history, not from the JSON itself.

### Embedded review extension

Clarification or rejection adds/replaces:

```text
review:
  decision: clarification | rejected
  comment
  reviewed_by
  reviewed_by_name
  reviewed_at
```

Approval is not stored as the same embedded review object: the controller sends
approved cases to the final post-execution form/closure path. Therefore absence
of `review`, an approved decision, and “not reviewed” are not equivalent.

### Historical/partial shapes supported by code/tests

Readers accept nullable/empty payloads and arbitrary partial nested arrays.
Tests and field-verification generation use simple top-level facts such as
`attendance`, as well as current teams/ceremony/review structures. The verifier
recursively flattens every non-empty leaf into a `field_key`; this means
historical custom keys and embedded review metadata can become verification
candidates unless a future migration explicitly classifies paths. No closed
schema constraint exists at the database level.

The payload carries execution actuals, completion evidence, feedback on team and
ceremony execution, and review metadata. It does not carry evaluation question
answers, weighted evaluation, follow-up remarks, or a monitoring-method identity.

## 4. Evaluation contracts

### Legacy MonthlyActivityEvaluationResponse

One row is an answer to one `EvaluationQuestion` for one Monthly Activity. It can
store `answer_value`, numeric `score`, free-text `note`, and nullable
`created_by`. The unique activity/question constraint makes it one current
response per question. The synchronization path deletes existing responses and
recreates non-empty submitted answers.

Classification: **EVALUATION / internal assessment**, not monitoring evidence.
It evaluates against a question catalogue rather than comparing a planned fact
to an actual fact. It has no monitoring method, observation time, report
submission/review lifecycle, planned snapshot, actual snapshot, or match status.

### Structured ActivityEvaluation

The newer evaluation flow creates one submitted evaluation per activity from an
active form, snapshots question text/ranges/weights into answer rows, computes a
normalized score, stores evaluator/submission/visibility metadata, and changes
the activity to evaluated. It requires all generated post-execution verification
rows to be resolved first.

Classification: **EVALUATION / internal quality assessment**. It is downstream
of verification but is not the same as monitoring review. It asks scored quality
questions, not whether observed execution matches the plan.

Neither repository flow proves beneficiary survey identity. Both are internal
application assessments unless external business evidence says otherwise.

## 5. Monthly follow-up contract

`monthly_activity_followups` stores only:

```text
monthly_activity_id
remarks
created_by
timestamps
```

It has no status, action type, assignee, due date, completion flag, evaluation FK,
verification FK, or approval FK. Current synchronization appends a row when
`followup_remarks` is non-empty; it does not update or close an existing task.

Classification: **FOLLOW-UP ACTION / administrative-operational note**, with
meaning too broad to call it a monitoring verification. A remark may describe a
recommendation, outstanding issue, corrective action or administrative follow-up,
but the schema cannot distinguish those meanings. It is not equivalent to a
planned/actual field comparison and should not be moved into verification notes
or a monitoring report's general notes without live semantic evidence.

## 6. Shared PostExecutionVerification contract

### Legacy Monthly columns

```text
monthly_activity_id
branch_id
field_key
field_label
value_type
original_value
corrected_value
status: pending | correct | incorrect
note
verified_by
verified_at
```

Monthly `ActivityEvaluationService` recursively flattens `post_execution_payload`,
creates one row per activity/field key, preserves the original value snapshot,
and lets a verifier confirm it or supply a corrected value. Evaluation cannot be
submitted until every verification is resolved.

### Common/Ramadan columns

```text
monitoring_report_id
detail_type
detail_id
field_key
field_label
planned_value
actual_value
match_status:
  matched | mismatched | not_observed | not_applicable
note
verified_by
verified_at
```

Ramadan monitoring derives candidates from aggregate and detail planned/actual
facts. Rows belong to a report, are replaced/synchronized by the report form,
and mismatches require notes before approval.

### Compatibility columns/modes

`monthly_activity_id` and `branch_id` were made nullable for Common rows;
`monitoring_report_id` and planned/actual/detail columns were added for Common.
No row-mode discriminator beyond ownership/null patterns exists. The old unique
Monthly `(monthly_activity_id, field_key)` constraint remains meaningful for
Monthly, while report/detail indexes support Ramadan.

Classification: **VERIFICATION**, shared at the broad domain level but dual-mode
at field/status level. A future adapter must select the mode explicitly and must
not translate `correct/incorrect` directly into `matched/mismatched` without
preserving correction semantics.

The model remains `App\Models\PostExecutionVerification`; Phase 2.8D is blocked
and is not retried by this audit.

## 7. Common MonitoringReport contract

`monitoring_reports` is an **envelope/lifecycle**, not all post-execution data.
It stores:

```text
subject_type + subject_id
monitoring_method_id
monitor_user_id
observed_at
general_notes
submitted_at
status: draft | submitted | returned | approved
timestamps
```

It belongs to a required monitoring method and optional monitor. Review actor,
review comment and review time are not columns on the report; Ramadan writes
those facts to `workflow_action_logs`. The report owns many verification rows.

The stable `monthly_activity` subject alias makes Monthly ownership technically
possible. Semantic adoption is not automatic: Monthly currently has no selected
monitoring method, report author lifecycle, report-return/resubmission envelope,
or approved-monitoring prerequisite for closure.

## 8. Ramadan monitoring lifecycle baseline

| Stage | Model/service | Actor | Status/storage |
|---|---|---|---|
| execution completion | `RamadanIftarExecutionService` | execution actor | Iftar `execution_status=completed`; actual detail fields complete |
| report create/edit | `RamadanIftarMonitoringService::save` | authorized monitor | report draft/returned; method, observed time, notes and monitor stored |
| verification sync | monitoring service | same monitor | report-owned planned/actual snapshots, match status, note, actor/time |
| submit | monitoring service | monitor | draft/returned → submitted; `submitted_at`; action log |
| return | monitoring review | authorized non-self reviewer | submitted → returned; comment/actor/time in action log |
| resubmit | monitoring service | monitor | returned → submitted; action log |
| approve | monitoring review | authorized non-self reviewer | submitted → approved; every mismatch requires note; action log |
| close | `RamadanIftarClosureService` | supervisor/super-admin | requires completed execution and authoritative approved monitoring report; stores `closed_at` and action log referencing report |

Monitoring is allowed only for approved, in-progress/completed, non-closed
Iftars. It is not a DynamicWorkflow instance, but has an explicit state machine
and action-log history.

## 9. Monthly post-execution lifecycle

| Stage | Actor/owner | Storage and transition |
|---|---|---|
| execution actual entry | activity creator/authorized Monthly editor | actual date/attendance, execution-needs follow-up, normalized post-execution JSON |
| submit post-execution | submitter | status `post_execution_submitted`, execution status executed, payload saved, supervisor notified, action logged |
| clarification/rejection review | branch supervisor/super-admin | review snapshot added to payload; status changes to changes-requested or rejected; Monthly approval + action log + notification |
| approved/final close path | branch supervisor/super-admin | final post-execution form saves evaluation summary/evidence and closes activity; action logged; follow-up/evaluation roles notified |
| verification preparation | follow-up/evaluation service | payload leaves flattened into Monthly verification rows |
| field verification/correction | follow-up/evaluation actor | pending → correct/incorrect; corrected snapshot, note, verifier/time; audit log |
| quality evaluation | evaluator/follow-up role according to permissions | requires all verification rows resolved; creates weighted evaluation/answers; aggregate becomes evaluated |
| legacy responses/follow-up | Monthly planning/evaluation form actor | current question responses replaced; free-form follow-up remark appended |
| subsequent follow-up/reporting | follow-up officers/managers | workspaces and reports use verification status, evaluation and remarks |

Monthly closure can occur in the supervisor post-execution path before the later
quality evaluation changes the activity to evaluated. Thus Monthly “closed” and
Ramadan “closed after approved monitoring” are not the same prerequisite chain.

## 10. Semantic classification and mapping

| Monthly source | Meaning | Category | Potential Common destination | Equivalent? |
|---|---|---|---|:---:|
| actual date/attendance | aggregate execution result | EXECUTION ACTUAL | verification candidate or subject actual fields | partial |
| payload teams attendance/tasks | team execution evidence | EXECUTION ACTUAL / FEEDBACK | verification snapshots | partial; rich structure remains Monthly |
| payload ceremony implementation/feedback | segment execution evidence | EXECUTION ACTUAL / FEEDBACK | verification snapshots | partial |
| payload `completed_at` | document completion time | REPORTING METADATA | report observed/submitted time | no |
| payload review decision/comment/actor/time | supervisor clarification/rejection | APPROVAL / WORKFLOW | report returned/approved plus action log | partial but lifecycle differs |
| legacy evaluation response | answer to evaluation question | EVALUATION | none | separate concept |
| activity evaluation/answers | weighted quality assessment | EVALUATION | none | separate concept |
| evaluation score/reason scalar | summary quality assessment | EVALUATION | none | separate concept |
| follow-up remark | free-form subsequent action/note | FOLLOW-UP ACTION | none; possibly projection only | separate concept |
| Monthly verification original/corrected/status | truth/correction of submitted payload field | VERIFICATION | existing shared verification table | shared storage, different mode |
| verification audit row | history of correction action | APPROVAL / WORKFLOW / audit | audit log remains | no report-column equivalent |
| Monthly approval row | post-execution decision history | APPROVAL / WORKFLOW | monitoring action lifecycle | not exact |
| workflow action log | aggregate lifecycle history | APPROVAL / WORKFLOW | existing action log | shared infrastructure, different actions |
| notification metadata | delivery/history | REPORTING METADATA | none | separate concept |

## 11. Strict field mapping

| Business meaning | Monthly source | Common source | Classification | Lossless? / notes |
|---|---|---|---|---|
| subject owner | activity ID | subject alias + ID | DIRECT technically | stable alias exists; semantics still differ |
| report method | none | required `monitoring_method_id` | NO_EQUIVALENT | would require fabricated/default method |
| monitor/report author | submit/action history, not payload field | `monitor_user_id` | DERIVED/LOSSY | actor cannot always be reconstructed safely |
| observation time | actual date/payload completion/submit times | `observed_at` | LOSSY | these timestamps mean different events |
| report notes | review comment, follow-up remark, evaluation reason, payload feedback | `general_notes` | LOSSY | merging distinct note types destroys purpose/actor |
| report status | Monthly aggregate/review statuses | draft/submitted/returned/approved | LOSSY | state machines and approved path differ |
| report submitted time | workflow action/aggregate update | `submitted_at` | DERIVED | requires live history reconciliation |
| planned snapshot | original payload may itself be actual evidence | verification `planned_value` | NO_EQUIVALENT for many Monthly fields | Monthly verification compares submitted vs corrected truth, not plan vs actual |
| actual snapshot | `original_value` or `corrected_value` depending verification outcome | `actual_value` | DERIVED/ambiguous | correction provenance must remain |
| match result | correct/incorrect | matched/mismatched | PARTIAL/LOSSY | incorrect may be corrected; mismatch means observed variance |
| verification actor/time | `verified_by`, `verified_at` | same columns | DIRECT | already shared |
| detail identity | flattened `field_key`; no detail row ID | detail type/ID/key | PARTIAL | nested array indexes/path stability require live audit |
| evaluation questions/scores | response/evaluation tables | none | SEPARATE_CONCEPT | keep Monthly-specific |
| follow-up remarks | follow-up table | none | SEPARATE_CONCEPT | keep Monthly-specific |
| closure decision | aggregate status + action/approval history | approved report + close action | SEPARATE_CONCEPT | cannot replace without rule change |

## 12. Evaluation versus monitoring decision

Monthly evaluation is **not** the same concept as monitoring review.

| Dimension | Monthly evaluation | Ramadan monitoring review |
|---|---|---|
| purpose | score quality/performance through configured questions | approve or return an observation report comparing plan to actual |
| primary actor | evaluator/follow-up user permitted for evaluation | monitoring reviewer, branch-scoped and distinct from monitor |
| input | evaluation form/questions, weights, scores, notes | report envelope, verification match states, mismatch notes |
| timing | after submitted payload is verified | after execution and report submission, before closure |
| lifecycle | one submitted evaluation; visibility and aggregate evaluated state | draft/submitted/returned/approved report state machine |
| reports | normalized score, answer detail, visibility | monitoring status/method/mismatches/closure evidence |

They must remain separate even if Monthly later gains a genuine monitoring
envelope.

## 13. Follow-up versus monitoring decision

Monthly follow-up is **not** equivalent to monitoring verification.

The follow-up table is an actor-attributed free-form remark without typed action,
status, due date or completion. It may record corrective action, recommendation,
outstanding task, issue, or administrative note, but the database cannot
separate those cases. Monitoring verification is a structured assertion about a
specific planned/actual or original/corrected field with status, actor and time.

Do not copy follow-up remarks into `general_notes` or verification notes as if
they were evidence. A future read-only report may display both under explicitly
different labels.

## 14. Actor comparison

| Action | Monthly actor storage | Ramadan/Common actor storage | Compatibility |
|---|---|---|---|
| post-execution submission | workflow action/notification; no payload submitter | monitor stored on report + action log | not direct |
| supervisor clarification/rejection | payload reviewer ID/name + Monthly approval + action log | reviewer in action log, not report column | partial; different state/actions |
| final Monthly close | supervisor/action log; aggregate update | supervisor/action log linked to approved report | different prerequisite |
| field verification | `verified_by`, `verified_at` | same columns | direct shared fact |
| quality evaluation | `evaluated_by`, submitted time; response `created_by` | none | separate concept |
| follow-up remark | `created_by`, row timestamps | none | separate concept |
| report submission | none as envelope | `monitor_user_id`, `submitted_at`, action log | no equivalent |
| monitoring observation | no method/observer field | method, monitor, `observed_at` | no equivalent |

Actor attribution cannot be safely synthesized from `updated_by` or current
users. Future mapping must inventory action/approval logs and tolerate missing
history rather than inventing actors.

## 15. Closure readiness comparison

### Monthly

The post-execution submitter provides execution data. A supervisor can request
clarification/reject or use the final form to close. Verification and weighted
evaluation form a later evaluation workflow, and follow-up remarks are not a
formal closure gate. Current code can therefore represent closed and later
evaluated states without an approved monitoring envelope.

### Ramadan

Closure requires completed execution and an authoritative approved monitoring
report. Monitoring report approval requires submitted state, authorized
non-self review, and notes for mismatched verification rows.

A Common monitoring envelope cannot replace Monthly closure prerequisites
without changing actor responsibilities, ordering and status meaning.

## 16. Reporting and history dependencies

Active Monthly consumers include:

- `AdminReportsService`, which treats non-null post payload/status as execution
  evidence for aggregate execution reporting;
- evaluation dashboards, which use payload presence and evaluation absence;
- follow-up workspaces, which count pending/correct/incorrect verification rows;
- feedback pages, which query embedded review decisions/comments/actors/times;
- Monthly show/edit/approval screens, which render teams, ceremony evidence,
  review feedback, evaluation responses and follow-up remarks;
- user profiles, which query evaluation responses by `created_by`;
- audit logs for field corrections/evaluation;
- Monthly approvals and workflow action logs for lifecycle history.

Historical compatibility also includes soft-deleted Monthly rows, request/change
snapshots, approval history, notification metadata and audit FQCN identity. A
migration must keep those readers working and must not rewrite old meaning.

No source evidence shows that Monthly reports currently calculate a Common
monitoring-method/report-status statistic. A new projection would therefore be a
new reporting contract, not a storage-only refactor.

## 17. Options considered

| Option | Benefits | Risks | Decision |
|---|---|---|---|
| A. Keep Monthly post-execution architecture separate | preserves history, actors, workflow, evaluation/follow-up meaning | parallel lifecycle/reporting concepts remain | **selected** |
| B. Move all Monthly post-execution into monitoring reports | one envelope/table family | severe semantic loss, fabricated method/actors/times, workflow and closure rewrite | reject |
| C. Normalize only true monitoring/verification facts | recognizes shared verification while retaining evaluation/follow-up/workflow | Monthly verification remains dual-mode; envelope adoption still unproven | current bounded architecture; no new migration recommended |
| D. Read-only Common projection | non-destructive combined reporting | no current consumer; must label different facts and modes | defer until concrete report exists |
| E. New Monthly plans use Common monitoring | avoids rewriting old rows | cutoff creates two Monthly closure/user models and incomparable history; dual-write risk | reject |

## 18. Final architecture decision

`KEEP MONTHLY POST-EXECUTION SEPARATE`

Final boundaries:

```text
KEEP MONTHLY-SPECIFIC:
  post_execution_payload and embedded review snapshot
  Monthly scalar lifecycle/evaluation fields
  MonthlyActivityEvaluationResponse
  ActivityEvaluation and answers
  MonthlyActivityFollowup
  Monthly approval/workflow/notification semantics

KEEP SHARED BUT DUAL-MODE:
  post_execution_verifications

KEEP COMMON/RAMADAN:
  monitoring_methods
  monitoring_reports and their lifecycle
```

This preserves the already-achieved normalization of genuine field verification
without claiming that evaluation, follow-up, approval or closure are monitoring.

## 19. Future compatibility plan

No implementation is currently approved. If a concrete Monthly monitoring
business requirement is adopted, use separate gated stages:

1. live inventory of payload shapes, review states, evaluations, follow-ups,
   verification ownership/modes, approvals/actions and missing actors;
2. define the new Monthly monitoring question, method and actor—not a synthetic
   default created for migration;
3. define explicit verification row-mode compatibility and field-path mapping;
4. deploy read-only compatibility/projection retaining every Monthly reader;
5. backfill only genuine monitoring envelope/verification facts into candidate
   records with row-count and semantic reconciliation;
6. retain one canonical writer; do not dual-write by default;
7. separately approve writer cutover after Monthly workflow/closure parity;
8. observe and retain rollback;
9. retire only redundant current-state reads, never immutable historical
   evaluation/follow-up/approval documents.

Evaluation responses, activity evaluations, follow-up remarks, Monthly approval
semantics and historical JSON do not move under this plan.

## 20. Phase dependencies

Any implementation that changes `PostExecutionVerification` namespace/imports or
uses its canonical future FQCN **depends on successful Phase 2.8D**. That phase
requires restored runtime dependencies, Laravel boot, disposable database, live
audit identity inventory and focused regressions. Phase 2.11 does not bypass or
retry it.

A read-only conceptual report that does not move the model might be designed
before 2.8D, but no writer/backfill/retirement involving verification identity
may proceed. Phase 2.6 remains the broader runtime gate.

## 21. Test inventory and future gaps

Existing tests cover Monthly post-execution submission, clarification/rejection,
final close, team/ceremony evidence, execution-needs follow-up, payload-derived
verification creation, correction validation, evaluation gate/weighted scoring,
follow-up workspace counts, feedback queues, execution reporting, shared schema
IDs, Ramadan report save/edit/ownership, submit, return/resubmit, mismatch-note
review, approved monitoring and closure.

Future compatibility tests must cover:

- every historical/partial payload and review shape;
- payload flattening path stability and exclusion/classification of review
  metadata versus execution evidence;
- Monthly original/corrected versus Ramadan planned/actual row-mode separation;
- old and canonical verification audit identities after Phase 2.8D;
- monitor-method/actor/time mapping without defaults;
- legacy evaluation responses and structured evaluations remaining distinct;
- follow-up remarks remaining visible and not becoming monitoring evidence;
- Monthly approval/action/notification history readability;
- Monthly closure behavior unchanged under any projection;
- Ramadan monitoring/review/closure regression isolation;
- mixed old/new reads, idempotent backfill, single writer, no duplicate rows,
  rollback and observation;
- historical AdminReports, feedback, evaluation and follow-up parity.

## 22. Integrity and runtime debt

NO MONTHLY MONITORING OR POST-EXECUTION DATA WAS MIGRATED
NO MONTHLY EVALUATION OR FOLLOW-UP SEMANTICS WERE CHANGED
NO MONITORING WRITER WAS CHANGED
NO LEGACY STORAGE WAS REMOVED
NO DUAL-WRITE WAS INTRODUCED
NO BUSINESS OR WORKFLOW RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
PHASE 2.8D REMAINS INCOMPLETE / BLOCKED

`PHASE 2.11 COMPLETE`
