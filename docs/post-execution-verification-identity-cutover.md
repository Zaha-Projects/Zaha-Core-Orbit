# PostExecutionVerification identity compatibility and cutover design

**Phase:** 2.8C — compatibility infrastructure only
**Status:** COMPLETE; namespace cutover NOT executed
**Current model:** `App\Models\PostExecutionVerification`
**Future canonical model:** `App\Modules\Events\Models\PostExecutionVerification`
**Table:** `post_execution_verifications` (unchanged)

## 1. Repository reference classification

| Consumer/reference | Classification | Read/write | Uses model class | Uses stored FQCN | Compatibility impact |
|---|---|---|---:|---:|---|
| `ActivityEvaluationService::verify` | MODEL IMPORT, AUDIT WRITER | model read/write; audit write | yes | writes it | sole known verification identity writer; now uses the focused write boundary |
| `MonthlyActivity::postExecutionVerifications` | RELATIONSHIP | read/write relation | yes | no | import/class update only in 2.8D |
| `MonitoringReport::verifications` | RELATIONSHIP | read/write relation | yes | no | import/class update only in 2.8D |
| `FollowupWorkspaceController` | MODEL IMPORT, REPORT | read | yes | no | import update only |
| `EvaluationDashboardController` | MODEL IMPORT, REPORT | read | yes | no | import update only |
| `RamadanIftarMonitoringService` | MODEL IMPORT | read | yes, for status constant | no | import update only |
| `StoreRamadanMonitoringReportRequest` | MODEL IMPORT | read status catalogue | yes | no | import update only |
| Ramadan monitoring form/review views | OTHER (presentation) | read status catalogue | yes | no | explicit FQCN update in 2.8D |
| Monthly post-execution decision/evaluation pages | RELATIONSHIP/REPORT | read through Monthly relation | indirectly | no | namespace has no stored-identity effect |
| Ramadan review and closure readiness | RELATIONSHIP/REPORT | read through monitoring relation | indirectly | no | namespace has no stored-identity effect |
| focused feature tests | TEST | read/write | yes | only compatibility tests | update imports in 2.8D |
| architecture/design records | HISTORICAL DOC | none | textual | textual | retain old value when describing history |
| seeders | SEEDER | none found | no | no | no cutover work |
| routes/controllers | ROUTE BINDING | none found for this model | no | no | no route compatibility bridge needed |

No `PostExecutionVerification` workflow instance/action-log writer was found. No
route binds this model. The one persisted self-identity writer is the evaluation
audit write.

## 2. `audit_logs.entity_type` storage contract

The migration defines `entity_type` as an indexed string paired with
`entity_id`; there is no foreign key, morph declaration, alias constraint, or
referential enforcement. `AuditLog` exposes both fields as fillable scalar
attributes and defines only a `user` relationship.

Writers are heterogeneous: model `::class` FQCNs, the focused verification
identity helper, and route parameter-name strings from `TrackUserOperations`.
Therefore `audit_logs.entity_type` is a caller-supplied category/identity string,
not an Eloquent morph contract.

Current readers (`AdminReportsService::dailyOperationLogs` and
`userDelayStats`) aggregate audit rows without filtering or resolving
`entity_type`. Repository searches found no audit reader performing
`$entityType::find`, `new $entityType`, `is_a`, `instanceof`, `class_exists`, or
`where('entity_type', PostExecutionVerification::class)` against `audit_logs`.
`DynamicWorkflowService` does dynamically resolve `workflow_instances`, but no
verification identity is written there and that unrelated contract is outside
this phase.

## 3. Writer matrix

| File/method | Trigger | Value before 2.8C | Value after 2.8C | Source style |
|---|---|---|---|---|
| `app/Services/ActivityEvaluationService.php::verify` | evaluator records a Monthly field verification | `PostExecutionVerification::class` = `App\Models\PostExecutionVerification` | `PostExecutionVerificationIdentity::currentWriteType()` = the identical legacy FQCN | focused supplied string |

Phase 2.8C therefore changes no persisted value. Phase 2.8D has one explicit
writer switch: change `currentWriteType()` from `LEGACY` to `CANONICAL` in the
same deployment that moves the model, after rollback safety prerequisites pass.

## 4. Reader matrix and dual-read boundary

There is currently no identity-specific production audit reader to modify.
Generic audit reports already include both strings because they do not filter by
`entity_type`. Any transition-aware history reader introduced before retirement
must use:

```php
->whereIn(
    'entity_type',
    PostExecutionVerificationIdentity::acceptedTypes()
)
```

`acceptedTypes()` is an exact two-value allow-list. It neither dynamically
resolves classes nor falls back to arbitrary values. SQL `IN` cannot duplicate a
single audit row, so dual-read adds no duplicate-row risk; callers must still
key/paginate by `audit_logs.id`, not union two independently executed queries.

## 5. Live identity inventory required before cutover

No results are claimed. Run these against the deployment database:

| Query/command | Expected purpose | Required before cutover |
|---|---|:---:|
| `SELECT entity_type, COUNT(*) AS row_count FROM audit_logs GROUP BY entity_type ORDER BY entity_type;` | complete distinct audit identity inventory | yes |
| `SELECT entity_type, COUNT(*) AS row_count FROM audit_logs WHERE entity_type IN ('App\\Models\\PostExecutionVerification', 'App\\Modules\\Events\\Models\\PostExecutionVerification') GROUP BY entity_type ORDER BY entity_type;` | exact old/new verification counts | yes |
| `SELECT COUNT(*) AS missing_entities FROM audit_logs a LEFT JOIN post_execution_verifications p ON p.id = a.entity_id WHERE a.entity_type IN ('App\\Models\\PostExecutionVerification', 'App\\Modules\\Events\\Models\\PostExecutionVerification') AND p.id IS NULL;` | identify audit history whose referenced verification row no longer exists | yes |
| `SELECT entity_type, entity_id, COUNT(*) AS row_count FROM audit_logs WHERE entity_type IN ('App\\Models\\PostExecutionVerification', 'App\\Modules\\Events\\Models\\PostExecutionVerification') GROUP BY entity_type, entity_id HAVING COUNT(*) > 1;` | characterize repeated audit events before judging duplicates; action/timestamps must also be reviewed | yes |
| `php artisan tinker --execute="dump(DB::table('audit_logs')->selectRaw('entity_type, COUNT(*) AS row_count')->groupBy('entity_type')->orderBy('entity_type')->get());"` | Laravel-compatible equivalent where direct SQL access is unavailable | yes |

Repository evidence found no verification self-FQCN in `workflow_instances`,
`workflow_action_logs`, notifications, correspondence, or request tables. Before
2.8D, nevertheless run exact-value count queries on any additional identity
column reported by the live schema/data inventory; never broad-replace substrings.

## 6. Strategy comparison and decision

| Strategy | Advantages | Risks / rollback | Decision |
|---|---|---|---|
| A. Backfill before move | one final stored value | mutates history before old code can understand new identity; unsafe rollback ordering | reject as first step |
| B. Dual-read, then canonical writer/backfill | preserves visibility, simple exact `whereIn`, observation and rollback windows | two values temporarily; retirement requires verified zero legacy rows | **selected** |
| C. Old namespace compatibility model | old FQCN dynamically resolves | creates two writable Eloquent entry points and ambiguous model identity; no dynamic audit resolution requires it | reject |
| D. Stable alias | namespace-independent future | audit infrastructure currently accepts heterogeneous caller strings and has no alias resolver; disproportionate generic change | reject |

Selected strategy: an exact, model-specific identity helper; deploy dual-read
capability first, retain the old writer in 2.8C, move/switch the canonical writer
only in 2.8D, then optionally backfill exact audit values after rollback safety is
established. No global registry, morph map, `class_alias`, or arbitrary resolver
is introduced.

## 7. Historical backfill plan (design only)

Only `audit_logs.entity_type` is proven by repository code to contain this
model's self-identity. A future transactional migration/command must:

1. lock or otherwise coordinate the verification audit writer;
2. record precheck counts for exact `LEGACY` and `CANONICAL` values;
3. update only `WHERE entity_type = 'App\\Models\\PostExecutionVerification'`;
4. set only `entity_type = 'App\\Modules\\Events\\Models\\PostExecutionVerification'`;
5. compare affected rows with the legacy precheck count;
6. verify legacy count is zero, final count equals both precheck counts combined,
   and total `audit_logs` count is unchanged;
7. retain a reversible migration/command that performs the exact inverse update
   while both application versions still accept both values.

Do not update `entity_id`, timestamps, action/module, payloads, workflow/action
logs, or `post_execution_verifications`. Do not run backfill concurrently with a
writer version whose identity is unknown.

## 8. Rollback-safe deployment order

1. **Deployment 1 (this phase):** readers may accept old + new through the exact
   helper; writer explicitly remains old; model remains `App\Models`.
2. Execute Phase 2.6 runtime tests and the live inventory above. Stop if unknown
   verification identities or unexplained counts exist.
3. **Deployment 2 / Phase 2.8D compatibility release:** move the sole model,
   update imports/views/relations, and switch `currentWriteType()` to canonical;
   readers continue accepting both. Do not backfill yet. The immediately prior
   application must also be patched/deployed with dual-read before this writer
   switch if rollback can serve audit history by identity.
4. Observe writes and verify only canonical identity is newly emitted, old rows
   remain visible, and no duplicate audit event is created.
5. **Deployment 3:** transactionally backfill exact legacy audit identities and
   verify counts. During its rollback window, both old and new application
   versions must accept both values; rollback the data to legacy before rolling
   code back to any version that writes/accepts only legacy.
6. After the observation/rollback window, remove the legacy read identity in a
   separate retirement change.

## 9. Retirement criteria

Remove `LEGACY` from accepted reads only after all are true:

- live inventories show zero legacy verification audit identities across at
  least the agreed observation window;
- all deployed writers and workers emit only `CANONICAL`;
- no rollback target that requires the legacy-only identity remains supported;
- focused old/new visibility, report inclusion, writer, and duplication tests
  pass in the production-equivalent environment;
- queues/backlogs and external consumers have been checked;
- the reversible backfill and its recorded before/after counts have passed review.

## 10. Required runtime matrix

- old-only audit history remains visible through transition-aware reads;
- new-only audit history remains visible;
- mixed old/new history maps to the same logical verification category;
- the current release writes only legacy and Phase 2.8D writes only canonical;
- one business verification action creates exactly one audit row;
- generic/historical reports include old and new identities;
- backfill preserves row IDs, row count, entity IDs, payloads, actors and times;
- reverse backfill restores exact old values;
- Monthly verification/evaluation behavior is unchanged;
- Ramadan monitoring creation, review, completion and closure are unchanged;
- fresh migrations, full tests, queue/worker checks, and browser/API checks pass.

## 11. Cutover gate

The compatibility design and source boundary are complete, but cutover is not
ready until Phase 2.6 and the required live identity inventory succeed.

`POSTEXECUTIONVERIFICATION CUTOVER NOT READY`

NO STORED IDENTITY WAS BACKFILLED
NO MODEL NAMESPACE CUTOVER WAS PERFORMED
NO TABLE OR BUSINESS DATA WAS CHANGED
NO WORKFLOW OR MONITORING RULE WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

## 12. Phase 2.8D prerequisite attempt (2026-09-14)

`POSTEXECUTIONVERIFICATION CUTOVER BLOCKED`

The mandatory gate was checked before any namespace or writer modification:

| Prerequisite | Actual evidence | Result |
|---|---|---|
| `vendor/autoload.php` | file absent | blocked |
| Laravel boot | not attempted because the required autoloader is absent | blocked |
| disposable database | no `.env`, no `APP_ENV`/`DB_CONNECTION`/`DB_DATABASE` environment variables, and no SQLite/database file found under `database` or `storage` | blocked |
| live audit identity inventory | not executed because Laravel/database safety prerequisites failed | blocked |

No live counts are available for legacy identities, canonical identities,
unknown related values, or missing referenced verification rows. Static source
inspection is not substituted for those required live results. Composer and
network restoration were not retried.

The model remains `App\Models\PostExecutionVerification`, the writer remains
`PostExecutionVerificationIdentity::LEGACY`, and both accepted read identities
remain configured. Phase 2.8D may be resumed only in an environment where the
locked dependencies are already restored and a database is explicitly proven
disposable; it must then execute the complete inventory and runtime matrix in
this document before approving the cutover.

NO MODEL NAMESPACE CUTOVER WAS PERFORMED
NO STORED IDENTITY WAS CHANGED
NO BUSINESS DATA WAS CHANGED

PHASE 2.6 REMAINS INCOMPLETE
RUNTIME VERIFICATION IS DEFERRED, NOT WAIVED

`PHASE 2.8D INCOMPLETE`
