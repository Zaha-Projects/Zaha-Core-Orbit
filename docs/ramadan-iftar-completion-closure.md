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

## Final closure

Production Monthly post-execution review confirms that the branch Supervisor owns
the final operational approval/closure action. Ramadan therefore uses the same
confirmed responsibility boundary, through the dedicated
`ramadan_iftars.close` permission, without reusing Monthly storage or status.

Closure is implemented as a separate, explicit transition. Its monitoring prerequisite is deterministic.
The branch Supervisor reviews submitted reports under a dedicated permission;
self-review is prohibited. A mismatch is resolved for closure when it has a
documenting note and the Supervisor explicitly approves the report. The latest
approved report is authoritative through `approvedMonitoringReportForClosure()`.

The close route locks the Iftar, rechecks approved planning, completed execution,
open state, branch-scoped Supervisor authorization, and the authoritative approved
monitoring report, then writes the server timestamp and one `iftar_closed` generic
action log in the same transaction. Documented mismatches in an approved report do
not receive a second review during closure. Duplicate closure is rejected under the
lock and cannot rewrite `closed_at` or duplicate the audit.

Existing closed records are treated as immutable: planning, execution and
monitoring writes reject them, submitted monitoring evidence remains read-only,
and completed execution data is displayed read-only. No workflow, planning, actual,
version, or Monthly Activity data is changed by completion.

## Workspace

The index shows planning, execution, monitoring, and open/closed states separately.
The Iftar hub shows one contextual lifecycle action, a five-stage progress summary,
and an irreversible closure confirmation only to an eligible branch Supervisor.
Closed Iftars remain fully readable and are clearly marked as historical.
