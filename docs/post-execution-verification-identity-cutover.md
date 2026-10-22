# PostExecutionVerification identity review and namespace cutover

## Status and decision

Phase 2.19 is **SOURCE COMPLETE — DIRECT CUTOVER COMPLETE / STAGING PENDING**.
The repository audit found one persisted self-FQCN surface:
`audit_logs.entity_type`, written by `ActivityEvaluationService`. The focused
`PostExecutionVerificationIdentity` compatibility boundary was already present,
accepts the exact legacy/canonical pair, and has now switched its writer to the
canonical identity. This made a direct model move safe without new compatibility
infrastructure.

**Decision: DIRECT CUTOVER SAFE.**

The sole installed model is now
`App\Modules\Events\Models\PostExecutionVerification`. The removed
`App\Models\PostExecutionVerification` name remains only as the historical audit
identity accepted by the focused helper. There is no wrapper or class alias.

## Identity inventory

| Surface | Storage | Writer | Reader | FQCN sensitive? | Compatibility needed? |
|---|---|---|---|---:|---:|
| verification records | `post_execution_verifications` | Monthly evaluation and Ramadan monitoring services | model relationships/reports | no | no |
| verification audit identity | `audit_logs.entity_type` | `ActivityEvaluationService::verify` through `PostExecutionVerificationIdentity::currentWriteType()` | generic audit reports; focused transition queries accept both | yes | yes; already implemented |
| workflows | `workflow_instances.entity_type` / `workflow_action_logs.entity_type` | no verification-model writer found | none for this model | no | no |
| notifications | notification JSON / `action_url` | notification metadata contains parent Monthly IDs, not verification FQCNs | URL navigation | no | no |
| Monthly ownership | `post_execution_verifications.monthly_activity_id` | relationship/service | `MonthlyActivity::postExecutionVerifications()` | no; direct FK | no |
| Ramadan ownership | `post_execution_verifications.monitoring_report_id` | `MonitoringReport::verifications()` | Ramadan monitoring/review/closure | no; direct FK | no |
| Ramadan subject | `monitoring_reports.subject_type = ramadan_iftar` | Ramadan monitoring service | `MonitoringReport::forSubject()` | no; stable alias | no |
| verification detail discriminator | `detail_type` | Ramadan monitoring service | report verification logic | no; stable detail alias | no |
| route binding | none | none | no route parameter uses this model | no | no |

Repository search found no verification-model identity in polymorphic columns,
`model_type`, queues, serialized Eloquent jobs, correspondence, request tables,
workflow instances, or workflow action logs. Generic `get_class()` writers apply
to workflow entities and no source passes a PostExecutionVerification model to
them.

## Storage model

Laravel predictably maps the model to `post_execution_verifications`; the move
does not affect table inference. The table has direct nullable foreign keys to
`monthly_activities`, `branches`, `monitoring_reports`, and `users`, plus detail,
value, status, comparison, note, and timestamp columns. It has no polymorphic
`*_type` column and no workflow column.

Monthly evaluation uses `monthly_activity_id`, `branch_id`, `status`, original
and corrected values. Ramadan monitoring uses the same table through
`monitoring_report_id`, planned/actual values, `match_status`, `detail_type`, and
`detail_id`. Ramadan's `ramadan_iftar` discriminator lives on the parent
`monitoring_reports.subject_type`; it is a stable business alias, not a model
FQCN.

## Compatibility and historical data

`PostExecutionVerificationIdentity::acceptedTypes()` remains the exact legacy
and canonical audit allow-list. `currentWriteType()` now emits canonical for new
audit rows. Historical legacy audit rows are not changed. Generic audit reports
do not dynamically instantiate `entity_type`, and any exact transition reader
must continue using the accepted pair.

No migration, backfill, dual write, morph map, registry, wrapper, or alias was
added. Verification lifecycle, statuses, Monthly evaluation, Ramadan monitoring,
review, closure, permissions, and branch behavior were not changed.

## Staging verification

Run the focused identity/relationship tests and the full suite. Inventory exact
legacy/canonical verification values in `audit_logs`; verify historical legacy
rows remain visible and one new verification action writes exactly one canonical
audit row. Exercise Monthly evaluation and Ramadan monitoring create/edit,
submit, review, completion, and closure flows. Confirm no workflow/action-log or
notification record contains the verification model identity. No source runtime
success is claimed for this slice.
