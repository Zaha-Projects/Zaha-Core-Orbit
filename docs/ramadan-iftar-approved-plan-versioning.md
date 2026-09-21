# Ramadan Iftar approved-plan change requests and versioning

**Status: CURRENT_SUPPORTING**

Current source of truth: `docs/events-architecture-current-state.md`.

## Confirmed semantics

Monthly change requests are a semantic reference only: they preserve the approved
record, require an approval workflow, and materialize a version only after final
approval. Their Monthly-specific request models and payload storage are not reused.

A Relations Officer may request a change through
`ramadan_iftars.change_request.create`. The source must be approved, open, still at
execution `planned`, have no child revision, and have no pending request. Review
uses the dedicated `ramadan_iftars.change_request.review` permission and an
independent workflow mirroring the post-submission approval roles: branch Supervisor, Branch Coordinator, Primary Relations
Manager, then Executive Manager. A requester cannot review their own request;
super-admin retains the established support override.

Only `pending -> approved` and `pending -> rejected` are supported. Rejection
requires a comment. Final approval and revision creation share one transaction.

## Version and copy contract

`parent_version_id` means the immediate prior version and `version_number` is
incremented server-side. A source may have only one child, producing a linear
`v1 -> v2 -> v3` chain. The main index shows only leaf/current versions; every
workspace exposes the complete lineage and allows read-only historical navigation.

The revision copies planning-owned core fields, targeting, meals/items, gifts,
programs, teams/members, volunteers, supplies, and canonical Execution Needs.
Every child receives a new primary key and every Common row receives the Ramadan
subject alias and new subject ID server-side.

No actual date/count/result, attendee, check-in, meal rating, monitoring report,
field verification, workflow instance/log, action history, approval timestamp, or
closure timestamp is copied. The revision starts `draft` / `planned`; submission
creates its own normal `ramadan_iftars` planning workflow.

The final approving actor is the server-side creator of the revision record;
branch and Relations Officer ownership are copied from the approved source.

The source guidance acceptance is preserved because it is immutable historical
planning context and the approved architecture has no rule requiring reacceptance
for an existing plan revision. The new draft cannot rewrite guidance fields.

Once the new draft exists, the approved source is superseded by lineage rather
than a new planning status. It remains approved and historically readable but
cannot start execution. Closed or already executing Iftars cannot request a
revision and are never reopened.
