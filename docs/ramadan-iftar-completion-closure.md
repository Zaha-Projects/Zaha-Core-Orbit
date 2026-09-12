# Ramadan Iftar execution completion and closure

## Legacy review and lifecycle decision

Monthly Activities combine post-execution submission, supervisor review, evaluation
handoff, and closure in Monthly-specific status and JSON behavior. Ramadan reuses
none of that storage. The Ramadan architecture explicitly says an Iftar is not
closed until required comparisons are resolved and its monitoring report is
approved.

The Ramadan schema intentionally separates planning `status`, execution status,
and `closed_at`. Phase 1.13 therefore implements `in_progress -> completed` as an
explicit operational action, while closure remains a separate action.

## Execution completion

The confirmed execution actor is the follow-up officer using the existing
`ramadan_iftars.execute` permission. Permission, aggregate branch, planning
`approved`, execution `in_progress`, and open-record state are checked at the HTTP
boundary and again under a database lock.

Completion enforces only the persisted results confirmed as universally required:

- actual date and captured attendance (`0` is valid; `null` is not captured);
- every required canonical Execution Need completed with actual details.

Optional Execution Needs do not block completion. Monitoring does not block
execution completion because monitoring may continue after operational execution.
Meal, gift, program, team, volunteer, and supply rows remain valid operational
evidence, but do not block completion because the approved design does not classify
every row in those optional collections as mandatory.
The transition is transactional, does not use `closed_at` as a completion timestamp,
and writes one `execution_completed` action to `WorkflowActionLog`. Repeating the
transition is rejected, so it cannot duplicate audit records.

## Closure readiness prerequisite resolved

Closure is **not implemented**, but its monitoring prerequisite is now deterministic.
The branch Supervisor reviews submitted reports under a dedicated permission;
self-review is prohibited. A mismatch is resolved for closure when it has a
documenting note and the Supervisor explicitly approves the report. The latest
approved report is authoritative through `approvedMonitoringReportForClosure()`.

There is still no close route and `closed_at` remains server-controlled and
unchanged. Existing closed records are treated as immutable: execution and
monitoring writes reject them, submitted monitoring evidence remains read-only,
and completed execution data is displayed read-only while open monitoring work may
continue. No workflow, planning, actual,
version, or Monthly Activity data is changed by completion.

## Workspace

The index shows planning, execution, and open/closed states separately. The Iftar
hub shows Complete Execution only to an eligible execution actor while execution is
in progress. Completed Iftars expose read-only execution and monitoring links and
do not display a closure action.
