# RAMADAN IFTARS IMPLEMENTATION STATUS

- **Last updated:** 2026-09-12
- **Current phase:** Phase 1.13 prerequisite — monitoring review and approval
- **Latest commit before this slice:** `2fcb020`
- **Overall status:** Planning through monitoring approval implemented; final closure remains.
- **Current blocker:** Complete remaining planning/execution template localization and runtime verification; then implement final closure.
- **Next recommended slice:** Finish Ramadan template localization/runtime smoke test, then `RESUME PHASE 1.13 — RAMADAN FINAL CLOSURE`
- **Runtime status:** **RUNTIME TESTING REMAINS PENDING** — `vendor/autoload.php` is missing and Composer GitHub downloads previously returned HTTP 403.

## Completed phases

- [x] Phase 0 — safety/baseline characterization
- [x] Phase 1.1 — Events lightweight foundation
- [x] Phase 1.2 — shared-read / Execution Needs discovery
- [x] Phase 1.3 — Common targeting foundation
- [x] Phase 1.4 — Ramadan lookup/reference foundation
- [x] Phase 1.5 — Ramadan core aggregate
- [x] Phase 1.6 — Ramadan detail foundation
- [x] Phase 1.7 — Common execution foundation
- [x] Phase 1.8 — Common monitoring foundation
- [x] Phase 1.9 — Ramadan planning CRUD
- [x] Laravel 8.83 compatibility hardening
- [x] Versioned Guidance acceptance
- [x] Execution Needs normalization
- [x] Phase 1.10 — submission and planning approval
- [x] Phase 1.11 — execution actual-data flow
- [x] Phase 1.12 — monitoring and workspace
- [x] Phase 1.13 partial — execution completion
- [x] Phase 1.13 prerequisite — monitoring review/return/approval
- [ ] Phase 1.13 — final closure

## User capability checklist

### Navigation and planning
- [x] Separate Ramadan navigation, branch-scoped index, and workspace
- [x] Guidance, create, edit, targeting, meals, gifts, programs, teams, volunteers, supplies, and canonical Execution Needs
### Planning workflow
- [x] Submit, multi-step approve, return, edit, and resubmit
### Execution
- [x] Start execution, attendance/check-in, all relational actual results, and complete execution
### Monitoring
- [x] Create report, field verification, submit, review queue, approve, return, edit, and resubmit
### Closure
- [x] Deterministic approved report query contract
- [ ] Closure authorization/readiness
- [ ] Close Iftar

## Backend status

| Area | Status | Notes |
|---|---|---|
| Models/migrations/shared schema | DONE | No global morph map; aliases are explicit. |
| Seeders/lookups | DONE | Idempotent canonical/reference data. |
| Planning/execution/monitoring services | DONE | Transactional, ownership-scoped. |
| Monitoring review service/controller/request/routes | DONE | Single-step Supervisor review. |
| Permissions | DONE | Dedicated Ramadan capabilities, including monitoring review. |
| Planning workflow definition | DONE | Independent Ramadan identity. |
| Audit | DONE | Generic WorkflowActionLog. |
| Branch/subject isolation | DONE | HTTP and service checks. |
| Tampering protection | DONE | Owned relation lookups and tests. |
| Closure | TODO | Must consume approvedMonitoringReportForClosure(). |

## Frontend status

| Screen/concern | Status |
|---|---|
| Navigation/index/show/guidance/create/edit | DONE |
| Planning approval queue/review | DONE |
| Execution and monitoring edit | DONE |
| Monitoring review queue/review | DONE |
| Lifecycle hierarchy/status badges/empty states | PARTIAL — primary workspace and monitoring review complete |
| Responsive Bootstrap layout and global RTL | DONE |
| Arabic and English Ramadan namespace | PARTIAL — core workspace/review localized; legacy planning/execution labels remain |
| Closure UI | TODO |
| Runtime visual/browser verification | BLOCKED by missing vendor |

## Automated testing checklist

- [ ] Install Composer dependencies
- [ ] Run migrations on test environment
- [ ] Run seeders
- [ ] Run RamadanGuidanceAcceptanceTest
- [ ] Run RamadanIftarPlanningFlowTest
- [ ] Run RamadanIftarApprovalWorkflowTest
- [ ] Run RamadanIftarExecutionFlowTest
- [ ] Run RamadanIftarWorkspaceMonitoringTest
- [ ] Run RamadanIftarCompletionClosureTest
- [ ] Run RamadanMonitoringReviewWorkflowTest
- [ ] Run all Common Event foundation tests
- [ ] Manual browser smoke test

Static syntax checks are not substitutes for these runtime tests.

## Manual UI-only E2E checklist

- [ ] Open Ramadan tab and index
- [ ] Start create and accept Guidance
- [ ] Create draft and fill planning
- [ ] Capture Execution Needs yes/no decisions
- [ ] Submit
- [ ] Supervisor approval
- [ ] Branch Coordinator approval
- [ ] Relations Manager approval
- [ ] Executive Manager final approval
- [ ] Start execution
- [ ] Add/check-in attendees and actual values
- [ ] Complete execution
- [ ] Create monitoring report and field verification
- [ ] Submit monitoring
- [ ] Review monitoring
- [ ] Return, correct, and resubmit monitoring
- [ ] Approve monitoring
- [ ] Close Iftar (not implemented)

## UNRESOLVED BUSINESS RULES

### Resolved
- Monitoring reviewer: branch Supervisor.
- Review permission: `ramadan_iftars.monitor.review`.
- Self-review: prohibited; explicit super-admin support override.
- Mismatch: approvable only with a documenting note.
- Authoritative report: latest approved by update time, then ID.

### Remaining
- Closure actor and permission.
- Any closure checks beyond completed execution plus authoritative approved monitoring.
- Reopening a closed Iftar.
- Approved-plan change request and version-copy semantics.
- Delete/restore lifecycle.

## Deferred Monthly migration

Monthly Activities remain on legacy behavior.

- [ ] Monthly Target Groups migration to Common
- [ ] Monthly teams migration
- [ ] Monthly volunteer migration
- [ ] Monthly supplies migration
- [ ] Monthly Execution Needs canonical backfill
- [ ] Dual-read verification
- [ ] Cutover
- [ ] Legacy JSON removal much later

## Known technical debt

- Composer/vendor environment blocker; runtime suites have not executed.
- Browser/RTL visual verification remains blocked until dependencies are available.
- Legacy Ramadan planning/execution templates still contain hard-coded English labels and must be migrated to the Ramadan translation namespace.
- Legacy Monthly compatibility aliases remain intentionally isolated.
- Closure and approved-plan versioning remain deliberately deferred.

## Reference documents

Read `docs/ramadan-iftars-data-design-ar.md`,
`docs/ramadan-iftar-submission-approval.md`,
`docs/ramadan-iftar-execution-flow.md`,
`docs/ramadan-iftar-monitoring-flow.md`,
`docs/ramadan-monitoring-review-approval.md`, and
`docs/ramadan-iftar-completion-closure.md`.

## HOW TO RESUME THIS WORK

1. Start from the current branch at the commit containing this document (parent: `2fcb020`).
2. Read the reference documents above and this tracker first.
3. Current incomplete phase: Phase 1.13 final closure.
4. Exact next slice: `RESUME PHASE 1.13 — RAMADAN FINAL CLOSURE`.
5. Once dependencies work, run the new monitoring review test first, then every Ramadan feature suite listed above.
6. Never break these invariants:
   - Ramadan remains independent from `MonthlyActivity`.
   - Planning shares the workflow engine but retains independent Ramadan workflow identity.
   - Common subject aliases are server-controlled.
   - Do not introduce a global morph map.
   - Planned and actual ownership remain separate.
   - Monitoring never mutates plan or operational actual data.
   - Monthly legacy behavior remains unchanged until an explicit migration phase.
