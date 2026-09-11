# Ramadan Iftar workspace and monitoring flow

## Reachable workspace

Ramadan Iftars have an independent, permission-aware dashboard entry. The
branch-filtered index is the entry point for creation (which still redirects through
versioned guidance acceptance), details, approvals, execution, and monitoring.
Monthly Activities retain their existing separate navigation item and routes.

The Iftar details page is the operational hub. It shows planning, accepted guidance,
targeting, canonical Execution Needs, meals, gifts, programs, teams and member
results, volunteers, supplies, aggregate attendance, generic workflow history, and
monitoring summaries. Attendee PII remains confined to the execution screen.
Lifecycle actions are rendered only when permission and state allow them; endpoint
authorization remains authoritative.

## Monitoring ownership and lifecycle

`ramadan_iftars.monitor` is assigned to follow-up officers. Monitoring requires an
approved Iftar whose execution is `in_progress`, plus branch access. Reports use the
existing Common `monitoring_reports` and `field_verifications` tables with
`subject_type=ramadan_iftar`; no Ramadan-specific monitoring tables exist.

Implemented report lifecycle:

`draft -> submitted`

Existing `returned` reports may also be edited and resubmitted. Approval/return
decisions are deferred because no monitoring approval actor sequence is confirmed.
Submission requires at least one verification and sets `submitted_at` on the server.

## Verification snapshots and security

The server builds an allow-list of aggregate and owned-detail comparisons from the
Iftar: date, attendance, meals, gifts, programs, teams, volunteers, supplies, and
canonical Execution Needs. A request selects a target and records match status and
notes; planned/actual snapshot values, label, verifier, and verification time are
resolved server-side. Foreign report, verification, or detail IDs are rejected.

Create/update and submission are transactional and use execution-specific entries
in the existing `WorkflowActionLog`. They do not alter operational actual data,
approved planning, approval workflow history, guidance, execution lifecycle, or
closure state.

## Deferred

Monitoring approval/return decisions, report attachments, final execution
completion, closure, and evaluation remain deferred.
