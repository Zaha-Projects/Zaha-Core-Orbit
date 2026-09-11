# Ramadan Iftar submission and approval

## Reuse and identity

Phase 1.10 reuses `Workflow`, `WorkflowStep`, `WorkflowInstance`, `WorkflowLog`,
`WorkflowActionLog`, `DynamicWorkflowService`, and the generic workflow notification
service. The independent identity is module `ramadan_iftars`, entity type
`App\Modules\Events\Models\RamadanIftar`, and the Iftar primary key. No Ramadan
approval tables exist.

The workflow is the established planning sequence: Relations Officer submission,
Supervisor, Branch Coordinator, Primary Relations Manager, and Executive Manager.
The first three roles are branch-scoped where applicable. Branch behavior is now
configured per module in `config/workflows.php`; the prior Monthly Activity behavior
is represented unchanged, Ramadan opts in, and Agenda remains unscoped.

## Permissions and lifecycle

Dedicated `ramadan_iftars.view`, `create`, `edit`, `submit`, and `approve`
permissions are seeded. Relations officers plan and submit; relations managers can
plan, submit, and approve; supervisors, branch coordinators, and executive managers
can view and approve; super administrators retain the complete catalogue.

The coarse aggregate lifecycle is:

`draft -> submitted -> approved`

or `submitted -> changes_requested -> submitted`. Draft and changes-requested
plans are editable. Submitted and approved plans are locked. A correction reuses
the existing workflow instance and its incremented iteration; `submitted_at`
preserves the first successful submission time.

## Readiness and transactions

Submission reads persisted data and requires branch, relations officer, planned
date, valid historical Ramadan guidance evidence, and an explicit yes/no row for
every active canonical Ramadan Execution Need. It does not require all needs to be
selected. Guidance is not upgraded to the latest version during submission.

Submission and each decision lock the Iftar and workflow state in one database
transaction. Intermediate approval advances only the workflow. Final workflow
completion sets `status=approved` and `approved_at`; `execution_status` remains
`planned`, and execution, monitoring, closure, and planning collections are not
mutated.

## Queue, decisions, and audit

The Ramadan queue applies module, FQCN entity, active instance, current step role,
and branch filters in SQL before pagination. Review and decision endpoints repeat
branch and current-step authorization. The decision request carries an expected
step ID to reject stale screens, but the server resolves the authoritative current
step. Only `approved` and `changes_requested` are exposed; Ramadan reject is not an
approved requirement.

Decision comments remain in `WorkflowLog`; cross-cutting actions use
`WorkflowActionLog`. Existing generic notifications resolve current eligible users
after submission or approval. Numeric IDs shared with Monthly Activities cannot
collide because every workflow lookup includes the Ramadan model FQCN and workflow.

## Deferred

Actual execution, attendance, monitoring, closure, approved-plan changes,
version-copy, deletion/restoration, and Monthly Activity migration remain outside
Phase 1.10.
