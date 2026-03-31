# Workflow Governance & Approval Transitions

## 1) Governance Rules

- Exactly **one active workflow per module** is allowed.
- Enforcement layers:
  - Database unique key on `workflows.active_module`.
  - Model-level normalization (`active_module = module` when active).
  - Service-level orchestration (`WorkflowGovernanceService`) to auto-deactivate older active workflows.
  - Controller validation and user-friendly error messages.

## 2) Dynamic Workflow is the Source of Truth

- Approval decisions are handled by `DynamicWorkflowService`.
- `MonthlyActivityWorkflowService` is now **deprecated** and only keeps compatibility for legacy status-field mirrors.

## 3) Transition Model

Supported decisions:
- `approved`
- `changes_requested` (requires comment)
- `rejected` (requires comment)

State behavior:
- `changes_requested`:
  - increments iteration counter,
  - rolls back to first editable step,
  - marks instance as `changes_requested`.
- `resubmit`:
  - done via monthly activity submit endpoint,
  - allowed only after `changes_requested`,
  - moves state to `in_progress`.
- `rejected`:
  - terminal status,
  - current step cleared and `completed_at` is set.

## 4) Authorization/Security Guarantees

- Only users assigned to the **current step role/permission** can decide.
- Self-approval is blocked for monthly activity creators.
- Prerequisite sequence checks run before every decision.

## 5) Auditability

Workflow logs include:
- actor,
- workflow step,
- decision,
- comment,
- iteration,
- timestamp.

Additional action logs include decision metadata for troubleshooting and governance traces.

## 6) Edge Cases

- No active workflow for module ⇒ explicit 422 with localized message.
- Invalid decision value ⇒ explicit 422.
- Missing comment for changes/reject ⇒ explicit 422.
- Duplicate step key or duplicate order+level in same workflow ⇒ blocked before DB exception.
