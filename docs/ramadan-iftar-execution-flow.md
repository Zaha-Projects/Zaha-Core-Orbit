# Ramadan Iftar execution flow

## Existing behavior reviewed

Monthly Activities currently combine execution and post-execution data in aggregate
columns, `post_execution_payload`, and `execution_needs_followup`, then submit that
payload for a separate supervisor review. Those lifecycle and authorization
semantics were used only as evidence: Ramadan does not read or write that JSON.
The Ramadan design explicitly assigns actual quantities, attendance, meal ratings,
teams, and task outcomes to an operational follow-up user after plan approval.

## Eligibility and authorization

The dedicated permission is `ramadan_iftars.execute`, assigned to
`followup_officer` (and inherited by `super_admin`). Every read and write also checks
the Iftar's persisted `branch_id`; explicit `branches.view.all` remains the existing
administrative override. Only a planning `status` of `approved` is eligible.

Execution starts explicitly. Its intentionally small lifecycle is
`planned -> in_progress`. Completion is not implemented because closure requires
monitoring and matching decisions that belong to a later phase. Starting execution
does not set `actual_date`; the operational user records the real date.

## Planned and actual ownership

The execution request accepts only actual fields. Approved planning dates, counts,
descriptions, identities, assignments, guidance, and workflow state are never
filled from this request. The execution screen displays the approved plan as a
read-only baseline beside actual entry fields.

- `actual_attendance` is always recalculated from attendee rows with
  `attended=true`. Check-in sets `checked_in_at` to server time on the false-to-true
  transition and clears it when attendance is false. Names and phone numbers appear
  only on the permission- and branch-protected execution page; phone is not unique.
- `actual_meals_count` is null until at least one meal actual is recorded, then is
  the sum of meal `actual_quantity`. Meal rating uses the existing 1–5 field and
  rating notes. Gift actual quantity is independent from its planned quantity.
- Program rows accept only the existing `planned`, `completed`, and `cancelled`
  execution statuses plus actual notes.
- Teams accept actual member counts. A member task keeps tri-state semantics; a
  non-null result records the authenticated confirmer and server time, while reset
  to null clears confirmation.
- Volunteer requirements accept only actual counts. Supply actual quantity and
  tri-state availability are editable. Their broader status fields remain
  unchanged because no additional lifecycle was confirmed.
- Canonical `subject_execution_needs` accept actual details and `pending` or
  `completed`; completion time is server-owned. Planning details, requirement flags,
  type, and subject identity remain immutable. Not-required needs need no evidence.

## Transactions, ownership, and audit

A full progressive save is one database transaction. Every child ID is resolved
through the current Iftar's constrained relationship; a foreign ID raises a
validation error and rolls back every earlier actual change. Common records remain
constrained to `subject_type=ramadan_iftar` through those relationships. Attendee
deletion is explicit rather than inferred from omission.

Execution actions reuse `WorkflowActionLog` with execution-specific action names,
but never change `WorkflowInstance`, `WorkflowLog`, planning status, approval
timestamps, or approval progress. No monitoring report, field verification,
version, closure timestamp, or Monthly Activity row is created or changed.

## Deferred

Execution completion, monitoring reports, field verification, matching, closure,
approved-plan change requests, and version-copy behavior remain deferred.
