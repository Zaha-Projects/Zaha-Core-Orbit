# Follow-up / Monitoring full source audit

**Audit date:** 2026-09-27  
**Scope:** routes, controllers, services, models, requests, policies, views, roles/permissions, seeders, notification calls, workflow logs, statuses, reports, and navigation.

## Executive finding

Follow-up is not one generic workflow. The source contains two distinct operational flows:

1. **Monthly Activities:** post-execution verification plus a scored evaluation. Monthly uses `PostExecutionVerification` directly against `monthly_activity_id`; it does not use `MonitoringReport` or `MonitoringMethod`.
2. **Ramadan Iftars:** planned-versus-actual monitoring with a selected `MonitoringMethod`, owned by `MonitoringReport`, with canonical `PostExecutionVerification` children; supervisor review and final closure remain independent boundaries.

Annual Agenda has no post-execution monitoring/evaluation flow. It supplies approved planning context to Monthly Activities and uses its own approval/participation workflow. Reporting, enterprise dashboards, communications, finance, maintenance, and transport contain review/report concepts but do not implement Follow-up-owned post-execution verification.

## Flow inventory

| Module | Responsible role | Entry point / route | Controller/service | Before | Action | After | Notification / next role |
|---|---|---|---|---|---|---|---|
| Monthly post-execution | Branch completion actor; supervisor | `PATCH monthly-activities/{id}/close` | `MonthlyActivityLifecycleController` | approved/executable | enter actual date, attendance, needs, teams, program payload | `post_execution_submitted` / `executed` | supervisor notified |
| Monthly decision | Supervisor/authorized reviewer | post-execution decision or lifecycle close | `MonthlyActivityPostExecutionDecisionController`, lifecycle controller | `post_execution_submitted` | clarify/reject or approve | changes/rejected, or `closed` | branch owner or Follow-up/evaluation notified |
| Monthly verification | Follow-up Officer | `GET/PUT evaluations/activities/{id}/verification` | `ActivityEvaluationsController`, `ActivityEvaluationService` | post-execution exists | correct/incorrect, corrected verification value, note | verification rows resolved | incorrect value notifies creator |
| Monthly evaluation | Follow-up Officer | `GET create`, `POST evaluations/activities/{id}` | `ActivityEvaluationsController`, `ActivityEvaluationService` | all verification rows resolved | score active form questions | activity `evaluated` | Relations users notified |
| Ramadan execution | Relations Officer | Ramadan execution routes | `RamadanIftarExecutionController`, execution service | approved/planned | start, save actuals, complete | `in_progress`, then `completed` | branch Follow-up notified |
| Ramadan monitoring | Follow-up Officer | `GET/POST Ramadan monitoring`, `PUT returned report` | monitoring controller/service | completed/open | select method, compare, document mismatch, submit | report `submitted` | branch supervisors notified |
| Ramadan correction | Supervisor → Relations → Follow-up | review decision, execution correction, consolidated monitoring page | review/execution/monitoring services | submitted | return with reason; correct actual source; resubmit review | `returned → submitted` | Relations, then assigned monitor, then supervisor notified |
| Ramadan closure | Supervisor | `POST Ramadan close` | closure controller/service | execution completed + report approved | irreversible close | `closed_at` set | terminal state |
| Annual Agenda | workflow approvers | Agenda approval/participation routes | Agenda controllers + dynamic workflow | draft/submitted | planning decisions | approved/returned | next workflow participant; no Follow-up post-execution stage |

## Monthly Follow-up exact behavior

### What Follow-up sees

- a dedicated dashboard with branch-scoped plan, verification, evaluation, urgency, recent evaluation, upcoming plan, and verification-status summaries;
- an awaiting-evaluation queue with verification totals, resolved/pending/incorrect counts, relationship filter, and activity status filter;
- activity details including submitted post-execution payload and attachments;
- canonical verification cards and the evaluation form/history.

### What Follow-up enters and confirms

`ActivityEvaluationService::synchronizeVerificationFields()` flattens the branch-owned `post_execution_payload`. For every item Follow-up chooses `correct` or `incorrect`. Monthly explicitly supports a `corrected_value` when marked incorrect plus an optional note. This changes verification metadata, not the source payload. The creator is notified on an incorrect decision. Evaluation becomes available only when records exist and none remain `pending`. The activity was already marked `closed` during supervisor post-execution approval; evaluation later changes its operational status to `evaluated`.

### Monthly defect decision

No Monthly business behavior was changed. Its explicit corrected-verification-value model differs from Ramadan but is source-established. The only shared-workspace correction was removing an invalid “exactly one scoped branch” assumption so users whose actual scope contains multiple branches can use bounded `whereIn` queries consistently.

## Ramadan current behavior after re-audit

- Relations Officer owns start, actual-data entry, completion, and correction of returned actuals.
- Follow-up cannot use execution routes and does not receive `ramadan_iftars.execute` from the canonical role catalogue.
- Monitoring opens only for an approved, completed, open Iftar.
- One screen captures monitoring method, observed time, notes, planned/actual comparisons, match status, and mismatch reason.
- Save and submit are atomic. A mismatch reason is enforced in the request and again in the service.
- Supervisor approves or returns. Return does not let Monitoring edit Relations-owned actuals.
- Closure still requires approved planning, completed execution, an approved monitoring report, and an open record.

## Stale/redundant component classification

| Candidate | Classification | Evidence / decision |
|---|---|---|
| Ramadan monitoring `GET .../{report}` edit route/action/view | **Safe to remove; removed** | consolidated index owns initial and returned review; no remaining legitimate link required the second screen |
| Ramadan standalone `POST .../{report}/submit` route/action | **Safe to remove; removed** | consolidated `POST/PUT` invokes atomic `saveAndSubmit`; duplicate endpoint restored the removed two-step flow |
| Ramadan monitoring `PUT .../{report}` | **Actively used** | consolidated page needs it for a returned report |
| Ramadan complete-execution endpoint | **Legacy compatibility** | no active button links to it; controller contract/tests/integrations may still call it, so it was not deleted blindly |
| Ramadan execution permission on Follow-up | **Stale permission assignment** | removed from canonical role map; note seeders are additive, so deployments must reconcile historical manual/granted permissions operationally |
| Deleted `monitoring/edit.blade.php` | **Safe to remove; removed** | its only route/action was removed |
| Monthly corrected verification value | **Actively used** | explicit Monthly service/request/UI behavior; preserved |
| `evaluation_officer` access to Follow-up monthly-plan route | **Actively used** | route middleware and evaluation access seeders intentionally include it |

## Navigation, queues, and dashboard improvements

- Follow-up sidebar now labels the Ramadan link as **إفطارات بانتظار المتابعة** and opens the existing Ramadan list with `monitoring_status=pending`.
- The existing Ramadan list gained one status filter: pending, returned, submitted, approved. No duplicate queue page or state column was created.
- Follow-up dashboard gained four bounded counters using existing execution/report states: waiting, returned, submitted, approved.
- Existing Monthly queue/dashboard behavior remains unchanged.
- `scopedBranchIds()` is used for all dashboard counts; empty scope is rejected, while valid multi-branch scope is no longer incorrectly rejected.
- No overdue counter was added because no audited business deadline defines “overdue.”

## Access-control matrix

| Capability | Relations Officer | Follow-up Officer | Supervisor | Super Admin |
|---|:---:|:---:|:---:|:---:|
| Monthly plan/create/edit | ✓ | view | approve/review | oversight |
| Monthly post-execution source data | branch workflow | verify only | approve/return | oversight |
| Monthly corrected verification metadata | — | ✓ (explicit Monthly model) | — | oversight |
| Monthly evaluation | view per permission | submit | view | oversight |
| Ramadan planning | create/edit/submit | view only | approve | oversight |
| Ramadan execution/actuals | ✓ | — | — | ✓ |
| Ramadan monitoring method/comparison | — | ✓ | review | ✓ |
| Ramadan actual correction | ✓ | — | request only | ✓ |
| Ramadan close | — | — | ✓ | ✓ |

All scoped controllers use user permissions/role names and branch helpers; no role ID is hardcoded.

## Validation and status language

The Monitoring request provides Arabic attribute names for method, observation date, general notes, verification items, match result, and mismatch reason. `MonitoringMethod` is required and must exist/active. Match status must be a canonical `PostExecutionVerification` status. Every mismatch requires a reason before submission. The active UI uses: **بانتظار المتابعة، معاد للتصحيح، بانتظار الاعتماد، مكتمل، مطابق، غير مطابق** without adding database aliases.

## Notifications and audit

| Trigger | Recipient | Existing infrastructure/action |
|---|---|---|
| Monthly branch post-execution submission | Supervisor | `NotificationService`, `post_execution_submitted` workflow log |
| Monthly post-execution supervisor approval | Follow-up/evaluation | `NotificationService`, closure log |
| Monthly incorrect verification | activity creator | `post_execution_incorrect`, canonical audit log |
| Ramadan completion | branch Follow-up | `ramadan_post_execution_submitted`, `execution_completed` |
| Ramadan monitoring submission | branch supervisor | `ramadan_monitoring_submitted`, `monitoring_verification_submitted` |
| Ramadan monitoring return | assigned Relations Officer | correction notification, `monitoring_returned` |
| Ramadan actual correction resubmission | assigned monitor | corrected notification, `post_execution_correction_resubmitted` |
| Ramadan monitoring approval | workflow audit | `monitoring_approved` |

The redundant `monitoring_report_saved` audit event was removed because save is no longer a user-visible business step. Page visits are not audited.

## Reports and filters

Monthly evaluation history already filters activity, date range, month/year, score range, form, and visibility. Ramadan’s existing listing now filters monitoring pending/returned/submitted/approved in addition to planning, execution, closure, branch, and search. Closed remains the existing closure filter. No reporting engine was added.
