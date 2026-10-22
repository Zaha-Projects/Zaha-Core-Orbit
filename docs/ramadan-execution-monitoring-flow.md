# Ramadan execution, post-execution, and monitoring flow

**Source audit date:** 2026-09-27  
**Scope:** the focused execution → post-execution → monitoring → review → closure correction. Monthly Activities are reference behavior only and were not modified.

## Source-derived Monthly Activities reference flow

1. A branch Relations user owns planning and lifecycle submission.
2. The branch post-execution completion actor enters actual date, actual attendance, execution-needs follow-up, team attendance/tasks, and ceremony/program outcomes through `MonthlyActivityLifecycleController::close`.
3. A non-reviewer submission writes `post_execution_payload`, `actual_date`, `actual_attendance`, `execution_status=executed`, and `status=post_execution_submitted`; it logs `post_execution_submitted` and notifies branch supervisors.
4. The supervisor can request clarification or reject through `MonthlyActivityPostExecutionDecisionController`; the submitted payload records review history and the branch-side completion owner is notified.
5. Supervisor approval writes `status=closed`, advances the lifecycle through Executed/Evaluated/Closed, logs closure, and notifies follow-up/evaluation users.
6. Follow-up/evaluation flattens the already-entered `post_execution_payload` into canonical `PostExecutionVerification` records. The reviewer marks values correct/incorrect rather than filling out the execution form again. Monthly currently permits a verification-side corrected value and notifies the creator for incorrect values.
7. Evaluation is blocked until all verification records are resolved. Evaluation submission writes `status=evaluated` and the score. Monthly business closure therefore occurs before this evaluation step.

Monthly has no `MonitoringReport` or `MonitoringMethod` in this path. Those are valid Ramadan-specific concepts and remain in Ramadan.

## Previous Ramadan flow (before this correction)

| Step | Owner | Route/action | Before → after | Data/action | Classification |
|---|---|---|---|---|---|
| 1 | Follow-up/Monitoring | `GET execution`, `POST start-execution` | approved/planned → approved/in_progress | Starts execution | Wrong owner; Relations Officer is already stored on the aggregate |
| 2 | Follow-up/Monitoring | `PUT execution` | in_progress → in_progress | Enters date, attendance, meals, gifts, program outcomes, teams, volunteers, supplies, needs | Duplicates post-execution responsibility and compromises independent review |
| 3 | Follow-up/Monitoring | `POST complete-execution` | in_progress → completed | Separate completion click | Redundant UI hop after actual-data entry |
| 4 | Follow-up/Monitoring | `GET monitoring` | completed unchanged | Opens report list plus create form | Needed review entry, but report-list/create staging was unnecessary |
| 5 | Follow-up/Monitoring | `POST monitoring` | no report → draft | Selects method and verifies planned vs actual | Necessary data, but draft save was only an implementation hop |
| 6 | Follow-up/Monitoring | redirected `GET monitoring/{report}` | draft unchanged | Reopens the same verification on a second page | Redundant screen |
| 7 | Follow-up/Monitoring | `PUT monitoring/{report}` | draft unchanged | Saves the same report again | Redundant save action |
| 8 | Follow-up/Monitoring | `POST monitoring/{report}/submit` | draft/returned → submitted | Separate submission click | Redundant action; no business boundary between save and submit |
| 9 | Supervisor/reviewer | monitoring review decision | submitted → approved/returned | Independent approval or correction return | Real approval boundary; preserved |
| 10 | Supervisor | close | approved monitoring/open → closed | Irreversible final closure | Real closure boundary; preserved |

Monitoring was also allowed while execution was still `in_progress`, permitting comparison against mutable and incomplete actuals.

## Redundant previous Monitoring-user steps

1. **Starting and recording execution:** existed because `ramadan_iftars.execute` was assigned to `followup_officer`; redundant because the plan already identifies a branch Relations Officer and independent Monitoring must review, not create, actuals. Replaced by Relations Officer ownership.
2. **Completing execution separately after saving the same form:** existed as a separate endpoint/button; it added a UI hop but no new reviewer boundary. Replaced by “complete and send to monitoring” on the actual-data form. Draft saving remains available because it has real value during execution.
3. **Creating a report and being redirected to its edit page:** existed to support draft persistence; redundant for the requested review flow. Replaced by one consolidated page.
4. **Saving verification and then submitting it separately:** existed as draft workflow mechanics; redundant because Monitoring has already completed every verification input on that form. Replaced by atomic save-and-submit.
5. **Monitoring before execution completion:** existed because monitorability accepted `in_progress`; unsafe rather than business-required. Monitoring now starts only at `completed`.

The supervisor review and closure confirmation were not removed: they represent independent approval and irreversible closure boundaries.

## Gap analysis captured before implementation

| Area | Monthly Activities | Ramadan before | Problem | Target Ramadan |
|---|---|---|---|---|
| Execution owner | Branch-side completion workflow | Follow-up/Monitoring | Reviewer created actuals | Relations Officer |
| Post-execution owner | Branch-side actor enters once | Follow-up/Monitoring | No separation of duties | Relations Officer enters once |
| Monitoring owner | Follow-up/evaluation | Follow-up/Monitoring | Correct owner, wrong preceding duties | Follow-up/Monitoring review only |
| Monitoring method | Not used in Monthly | Existing `MonitoringMethod` | None | Preserve existing lookup |
| Planned/actual review | Canonical verification of submitted values | Existing stable candidate map | Could start before actuals final | Only after completed execution |
| Mismatch | correct/incorrect and note/correction metadata | matched/mismatched; note enforced only at approval | Invalid report could reach reviewer | Mismatch note required on Monitoring submit and approval |
| Correction | Creator notified for incorrect verification; Monthly also permits corrected verification value | Supervisor returned report to Monitor | Relations Officer was not asked to correct actual source | Notify Relations Officer; reopen actual form; Monitoring resubmits refreshed verification |
| Final confirmation | Evaluation after verification; Monthly activity closure occurs earlier | Supervisor review then supervisor closure | Extra boundaries were partly UI-only | Preserve the two real boundaries |
| Status transitions | post_execution_submitted → closed/evaluated | execution in_progress/completed; report draft/submitted/returned/approved; closed_at | Draft hops exposed as separate screens | completed → submitted/returned/approved → closed |
| Monitoring screens/actions | One verification screen, then separate evaluation | list/create → edit/save → submit → review → close | Two Monitoring-only UI hops | One Monitoring verification submit → independent review → close |

## Simplified Ramadan lifecycle

`approved / planned`
→ Relations Officer starts execution (`in_progress`)
→ Relations Officer saves actual Ramadan data as needed
→ Relations Officer selects **complete and send to monitoring** (`completed`)
→ Monitoring user opens one consolidated planned-vs-actual review
→ Monitoring selects `MonitoringMethod`, records match status and mismatch reasons, and submits atomically (`submitted`)
→ supervisor review:

- **approved** → closure becomes eligible;
- **returned** → Relations Officer receives a correction notification, edits the actual source data while execution remains `completed`, Monitoring reopens the returned verification and resubmits it.

→ supervisor final closure sets `closed_at`.

No additional workflow status or database table is introduced.

## Responsibility matrix

| Action | Relations Officer | Monitoring user |
|---|:---:|:---:|
| Start execution | ✓ | — |
| Enter actual date/attendance | ✓ | Review |
| Enter attendee segments | ✓ | Review aggregate comparisons |
| Enter meal/gift/program outcomes | ✓ | Review |
| Enter team/volunteer/supply outcomes | ✓ | Review |
| Enter execution-need outcomes | ✓ | Review |
| Complete and submit post-execution | ✓ | — |
| Select monitoring method | — | ✓ |
| Mark match/mismatch and reasons | — | ✓ |
| Edit actual execution data | ✓ | — |
| Submit monitoring verification | — | ✓ |

## Storage mapping and comparison identity

- `ramadan_iftars`: planned/actual date, attendance, and meal totals; the planned values are never overwritten by monitoring.
- Ramadan detail tables/generalized detail tables: actual attendance/check-in, meal and gift quantities, program execution status, team counts/member outcomes, volunteer counts, supply quantities/availability, and execution-need actual details.
- `monitoring_reports`: Ramadan report owner, existing `MonitoringMethod`, monitor, observed timestamp, general note, submitted timestamp, and report state.
- `post_execution_verifications`: canonical verification metadata and snapshots: `detail_type`, `detail_id`, `field_key`, `planned_value`, `actual_value`, `match_status`, `note`, `verified_by`, and `verified_at`.

Repeatable candidates use `detail_type + detail_id + field_key`, never array position. Aggregate date/attendance/meals use their stable aggregate keys. Target-group attendance uses the stable target selection ID. Unsupported separate “actual entity/location” columns were not invented: the current schema has one planning value for supporting entity, host organization/community, and location, so these are displayed as plan context rather than falsely duplicated as Monitoring actuals.

## Verification coverage

The consolidated report compares all currently supported separate planned/actual pairs:

- event date;
- total attendance and stable target-group attendance counts;
- total meals and every meal quantity;
- every gift/shield quantity;
- every program segment execution status;
- every execution-team member count;
- every volunteer requirement count;
- every supply quantity;
- every execution need's planned details against actual details.

Attendee names/contact details and team-member notes remain sensitive execution evidence, not duplicate Monitoring-owned actual fields.

## Status and correction semantics

- Ramadan planning status remains `approved` throughout execution/monitoring.
- Execution remains `planned → in_progress → completed`; no redundant new status was added.
- Report states remain `draft`, `submitted`, `returned`, `approved` for backward compatibility. New UI makes draft an atomic internal save step rather than a separate screen/action.
- `returned` now authorizes only the branch Relations execution owner (or super admin) to correct completed actuals; the reviewer cannot silently mutate them.
- Closure still requires approved planning, completed execution, approved monitoring, and an open aggregate.

## Permissions, notifications, and audit

- `ramadan_iftars.execute` is assigned to `relations_officer`, removed from `followup_officer`, and remains available to super admin. Every execution action remains branch scoped.
- `ramadan_iftars.monitor` remains assigned to follow-up/Monitoring and branch scoped.
- Supervisor monitoring review and closure permissions are unchanged.
- Completion notifies branch Monitoring users.
- Monitoring submission notifies branch supervisors.
- A returned review notifies the assigned Relations Officer with the correction reason and execution link.
- Existing meaningful workflow audit actions remain. Added correction updates use `post_execution_correction_resubmitted`; atomic Monitoring entry records save and submission without a separate user-visible hop.

## Database decision

**NO DATABASE MIGRATION REQUIRED.** Existing actual columns, `monitoring_reports`, and canonical `post_execution_verifications` fully support the corrected flow.
