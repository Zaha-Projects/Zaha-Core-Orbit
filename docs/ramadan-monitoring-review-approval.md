# Ramadan monitoring review and approval

## Architecture and actor

This is **CASE B**: a single-step report lifecycle is sufficient, so the planning
`DynamicWorkflowService` is not reused. The existing Monthly post-execution path is
semantic evidence that the branch Supervisor reviews submitted post-execution
evidence. Ramadan keeps independent relational monitoring storage and assigns the
dedicated `ramadan_iftars.monitor.review` permission to `supervisor`.

Reviewers are branch-scoped. A report monitor cannot review their own report;
`super_admin` is the explicit support override.

## Lifecycle and decisions

`draft -> submitted -> approved`

`submitted -> returned -> submitted`

Only `draft` and `returned` are editable. Return requires a comment. Review does
not regenerate snapshots or mutate planning/actual data. Return/resubmission and
approval history is preserved in `WorkflowActionLog` as `monitoring_returned`,
`monitoring_resubmitted`, and `monitoring_approved`.

## Verification and closure contract

A mismatched verification may be approved only when its monitoring note documents
the variance. Review never corrects the operational actual value. This treats a
documented and consciously approved variance as resolved.

When multiple reports exist, the latest approved report by `updated_at`, then ID,
is authoritative for closure. `RamadanIftar::approvedMonitoringReportForClosure()`
encapsulates this deterministic rule. Final closure is deliberately not implemented
in this slice.
