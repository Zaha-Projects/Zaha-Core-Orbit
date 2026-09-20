# Events staging cutover runbook

This runbook is read-first and staging-first. It performs no identity backfill.
Replace `<release>`, `<backup>`, `<database>`, and `<test-year>` explicitly and
record every command/output in the release evidence. **Never use
`migrate:fresh`.**

## A. PRE-DEPLOYMENT BACKUP / SAFETY

1. Record current and target commit/tag, artifact checksum, PHP/Composer versions,
   database server/version, operator, and UTC timestamp:
   `git rev-parse HEAD`, `git describe --always --dirty`, `php -v`,
   `composer --version`.
2. Take and restore-test a complete database backup (`<backup>`). Record the
   restore target and checksum. Do not proceed without a tested backup.
3. Record the maintenance window, deploy owner, rollback owner, previous
   application artifact, symlink/release switch procedure, and go/no-go channel.
4. Verify `.env`/secret injection, database target, queue/cache/session drivers,
   mail safety, storage permissions, `APP_ENV`, `APP_DEBUG=false`, `APP_URL`, and
   `APP_TIMEZONE`. Run `php artisan tinker --execute="dump(config('app.timezone'));"`.
   Source currently defaults to UTC. If Jordan local time is the approved business
   requirement, set `APP_TIMEZONE=Asia/Amman` through environment/configuration;
   do not hardcode the header. Clear/rebuild config after the decision.
5. Verify `php -m | grep -i '^intl$'` and
   `php -r "var_dump(class_exists('IntlCalendar'));"`.
6. Confirm no backfill/update SQL is bundled and no migration is run implicitly.

## B. CODE DEPLOY

Deploy the immutable `<release>` artifact to a new release directory. Confirm:

```bash
git rev-parse HEAD
find app/Models -maxdepth 1 -type f | sort
find app/Modules/Events/Models -maxdepth 1 -type f | sort
```

All seven identity-sensitive models must exist only under Events Models. Do not
create wrappers or aliases.

## C. COMPOSER / CACHE

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan config:clear
php artisan cache:clear
php artisan about
php artisan route:list
```

`about` is available only on newer Laravel versions; this repository is Laravel
8-based, so if the installed 8.x build does not provide it, record that expected
command limitation and prove boot with `php artisan --version` plus
`php artisan route:list`. Do not treat an unsupported `about` command as an
application failure. Restart PHP-FPM and queue workers only under the documented
release procedure after go/no-go.

## D. MIGRATION STATUS

Run, capture, and review before applying anything:

```bash
php artisan migrate:status
```

Specifically reconcile:

- `2026_09_15_000100_reconcile_ramadan_iftar_business_form`
- `2026_09_16_000100_create_ramadan_periods_table`
- `2026_09_16_000200_add_guidance_source_hash`
- `2026_09_20_000100_normalize_ramadan_period_administration`
- `2026_09_20_000200_scope_guidance_source_hash_by_code`
- guidance acknowledgement constraint-name correction represented by current source
- Ramadan `ramadan_period_id`, normalized period fields, code-scoped guidance hash,
  and reference-table migrations

Do not run `php artisan migrate --force` until status and the partial-DDL checks
below agree. Never use `migrate:fresh`.

### Partial-failed MySQL migration recovery

If `2026_09_15_000100_reconcile_ramadan_iftar_business_form` was previously
attempted, assume DDL may have committed despite a failed migration record. Run:

```sql
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND ((TABLE_NAME = 'event_guidance_acknowledgements')
    OR (TABLE_NAME = 'execution_need_types' AND COLUMN_NAME IN ('mandatory_for_monthly','mandatory_for_ramadan'))
    OR (TABLE_NAME = 'ramadan_iftar_gifts' AND COLUMN_NAME = 'gift_type'))
ORDER BY TABLE_NAME, ORDINAL_POSITION;

SELECT TABLE_NAME, CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'event_guidance_acknowledgements';

SELECT migration, batch FROM migrations
WHERE migration = '2026_09_15_000100_reconcile_ramadan_iftar_business_form';
```

Compare actual DDL with the migration source. If all expected objects exist but
the migration row does not, stop and have a DBA/reviewer approve a targeted
reconciliation plan; do not rerun blindly or manually insert a migration row
without approval. If only some objects exist, back up again, inventory data in
each affected column/table, and prepare reviewed, idempotent ALTER/DROP/RENAME
steps for only the partial objects. Never assume `migrate:rollback` reversed
MySQL DDL and never use `migrate:fresh`.

## E. DATABASE INVENTORY

All queries in E–F and J are read-only.

```sql
-- Workflow identities.
SELECT entity_type, COUNT(*) row_count
FROM workflow_instances
WHERE entity_type IN (
 'App\\Models\\AgendaEvent','App\\Modules\\Events\\Models\\AgendaEvent',
 'App\\Models\\AnnualAgendaEditRequest','App\\Modules\\Events\\Models\\AnnualAgendaEditRequest',
 'App\\Models\\AnnualAgendaDeleteRequest','App\\Modules\\Events\\Models\\AnnualAgendaDeleteRequest',
 'App\\Models\\MonthlyPlanEditRequest','App\\Modules\\Events\\Models\\MonthlyPlanEditRequest',
 'App\\Models\\MonthlyPlanDeleteRequest','App\\Modules\\Events\\Models\\MonthlyPlanDeleteRequest',
 'App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity')
GROUP BY entity_type ORDER BY entity_type;

-- Aggregate workflow/action history.
SELECT entity_type, COUNT(*) row_count FROM workflow_action_logs
WHERE entity_type IN ('App\\Models\\AgendaEvent','App\\Modules\\Events\\Models\\AgendaEvent',
 'App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity')
GROUP BY entity_type ORDER BY entity_type;

SELECT correspondable_type, COUNT(*) row_count FROM official_correspondences
WHERE correspondable_type IN ('App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity')
GROUP BY correspondable_type ORDER BY correspondable_type;

SELECT entity_type, COUNT(*) row_count FROM annual_agenda_edit_requests
WHERE entity_type IN ('App\\Models\\AgendaEvent','App\\Modules\\Events\\Models\\AgendaEvent') GROUP BY entity_type;
SELECT entity_type, COUNT(*) row_count FROM annual_agenda_delete_requests
WHERE entity_type IN ('App\\Models\\AgendaEvent','App\\Modules\\Events\\Models\\AgendaEvent') GROUP BY entity_type;
SELECT entity_type, COUNT(*) row_count FROM monthly_plan_edit_requests
WHERE entity_type IN ('App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity') GROUP BY entity_type;
SELECT entity_type, COUNT(*) row_count FROM monthly_plan_delete_requests
WHERE entity_type IN ('App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity') GROUP BY entity_type;

SELECT entity_type, COUNT(*) row_count FROM audit_logs
WHERE entity_type IN ('App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity',
 'App\\Models\\PostExecutionVerification','App\\Modules\\Events\\Models\\PostExecutionVerification')
GROUP BY entity_type ORDER BY entity_type;
```

Inventory notification JSON using the database engine's JSON functions only
after confirming the actual notifications schema; capture distinct class-derived
values without updating payloads.

## F. IDENTITY COMPATIBILITY CHECKS

1. Exercise old-only, new-only, absent, and mixed workflow records in disposable
   staging fixtures. Confirm reuse, canonical create, and mixed-pair exception.
2. Confirm Annual/Monthly request workflow identity is the request FQCN while
   request-table `entity_type` is the aggregate FQCN.
3. Confirm old/new Agenda and Monthly report groups normalize together without
   duplicating physical rows.
4. Confirm old/new verification audit history remains visible and one new
   verification produces exactly one canonical audit row.
5. Do not modify production-like historical fixtures during inventory.

## G. RAMADAN DATA/CONFIG CHECKS

```sql
SELECT id, hijri_year, start_date, end_date, suggested_start_date,
       suggested_end_date, is_confirmed, is_active, calculation_source, synced_at
FROM ramadan_periods ORDER BY hijri_year, id;
SELECT COUNT(*) active_periods FROM ramadan_periods WHERE is_active = 1; -- must be <= 1
SELECT `key`, value FROM settings WHERE `key` = 'ramadan_dashboard_enabled';
SELECT code, id, version_number, source_sha256, is_active, published_at
FROM event_guidance_versions WHERE code = 'ramadan_iftar'
ORDER BY version_number DESC, id DESC;
SELECT COUNT(*) current_published FROM event_guidance_versions
WHERE code='ramadan_iftar' AND is_active=1 AND published_at IS NOT NULL AND published_at <= CURRENT_TIMESTAMP; -- must be <= 1
SELECT guidance_version_id, COUNT(*) FROM event_guidance_acknowledgements GROUP BY guidance_version_id;
SELECT COUNT(*) FROM mobilization_methods;
SELECT COUNT(*) FROM community_organizations;
SELECT COUNT(*) FROM local_communities;
```

Verify Admin-managed values before/after seeders; counts alone are insufficient.
Confirm seeders never replace newer Admin-created current guidance or confirmed
period dates.

Verify Umm al-Qura on staging:

```bash
php -m | grep -i '^intl$'
php -r '$c=IntlCalendar::createInstance("Asia/Amman","ar_SA@calendar=islamic-umalqura"); var_dump($c instanceof IntlCalendar, $c->getType());'
php artisan ramadan:sync-period <test-year>
```

Confirm the command creates/updates a proposal, does not activate or confirm it,
and preserves confirmed dates on rerun.

## H. TEST EXECUTION

Run focused tests in this order, then the full suite:

```bash
php artisan test tests/Unit/EventAggregateIdentityTest.php tests/Unit/EventRequestModelIdentityTest.php tests/Unit/MonthlyActivityAggregateIdentityTest.php tests/Unit/PostExecutionVerificationIdentityTest.php
php artisan test tests/Feature/AgendaEventIdentityCompatibilityTest.php tests/Feature/EventRequestWorkflowIdentityCompatibilityTest.php
php artisan test tests/Feature/MonthlyActivityIdentityCompatibilityTest.php tests/Feature/MonthlyActivityControllerRouteContractTest.php
php artisan test tests/Feature/PostExecutionVerificationIdentityCompatibilityTest.php tests/Feature/AdminReportsMonthlyExecutionStatusTest.php
php artisan test tests/Feature/RamadanReferenceFoundationTest.php tests/Feature/RamadanProductionReferenceTest.php tests/Feature/RamadanReferenceManagementTest.php tests/Feature/RamadanAdminConfigurationTest.php tests/Feature/RamadanPeriodAndGuidanceTest.php tests/Feature/RamadanPeriodAutoSyncTest.php
php artisan test tests/Feature/RamadanIftarPlanningFlowTest.php tests/Feature/RamadanIftarApprovalWorkflowTest.php tests/Feature/RamadanIftarWorkspaceMonitoringTest.php tests/Feature/RamadanMonitoringReviewWorkflowTest.php tests/Feature/RamadanIftarCompletionClosureTest.php
php artisan test
```

Record failures; never describe unexecuted tests as passing.

## I. MANUAL UI ACCEPTANCE

- Agenda routes and Annual request approval routes: no binding 404/class errors.
- Monthly request and MonthlyActivity routes: no binding 404/class errors.
- Official correspondence: legacy read/reuse, canonical read/reuse, new canonical
  creation, edit legacy without type mutation, and synthetic mixed conflict.
- Monthly: planning, submit, approval, edit/delete request, correspondence,
  execution, evaluation, post-execution verification, and reports.
- Ramadan: guidance acknowledgement, create, organization/community lookup and
  creation, planning, execution needs, submission, approval, execution,
  monitoring, closure, revision, calendar, dashboard, Admin settings/reference.
- Visual: dark/gold theme, calendar, dashboard toggle, Gregorian/Hijri/time
  header, Admin nesting/lookups, RTL, mobile, and reduced motion.

Never mutate a real mixed correspondence duplicate before review.

## J. DUPLICATE/ORPHAN REVIEW

The following duplicate queries **MUST RETURN ZERO**:

```sql
SELECT workflow_id, entity_id, COUNT(*) identity_count
FROM workflow_instances
WHERE entity_type IN ('App\\Models\\AgendaEvent','App\\Modules\\Events\\Models\\AgendaEvent')
GROUP BY workflow_id, entity_id HAVING COUNT(DISTINCT entity_type) > 1;
SELECT workflow_id, entity_id, COUNT(*) identity_count
FROM workflow_instances
WHERE entity_type IN ('App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity')
GROUP BY workflow_id, entity_id HAVING COUNT(DISTINCT entity_type) > 1;
SELECT workflow_id, entity_id, COUNT(*) identity_count FROM workflow_instances
WHERE entity_type IN ('App\\Models\\AnnualAgendaEditRequest','App\\Modules\\Events\\Models\\AnnualAgendaEditRequest')
GROUP BY workflow_id, entity_id HAVING COUNT(DISTINCT entity_type) > 1;
SELECT workflow_id, entity_id, COUNT(*) identity_count FROM workflow_instances
WHERE entity_type IN ('App\\Models\\AnnualAgendaDeleteRequest','App\\Modules\\Events\\Models\\AnnualAgendaDeleteRequest')
GROUP BY workflow_id, entity_id HAVING COUNT(DISTINCT entity_type) > 1;
SELECT workflow_id, entity_id, COUNT(*) identity_count FROM workflow_instances
WHERE entity_type IN ('App\\Models\\MonthlyPlanEditRequest','App\\Modules\\Events\\Models\\MonthlyPlanEditRequest')
GROUP BY workflow_id, entity_id HAVING COUNT(DISTINCT entity_type) > 1;
SELECT workflow_id, entity_id, COUNT(*) identity_count FROM workflow_instances
WHERE entity_type IN ('App\\Models\\MonthlyPlanDeleteRequest','App\\Modules\\Events\\Models\\MonthlyPlanDeleteRequest')
GROUP BY workflow_id, entity_id HAVING COUNT(DISTINCT entity_type) > 1;
SELECT correspondable_id, COUNT(*) identity_count
FROM official_correspondences
WHERE correspondable_type IN ('App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity')
GROUP BY correspondable_id HAVING COUNT(DISTINCT correspondable_type) > 1;
```

Orphan checks **MUST RETURN ZERO** unless an explicitly reviewed soft-delete
policy explains the row:

```sql
SELECT wi.id, wi.entity_type, wi.entity_id FROM workflow_instances wi
LEFT JOIN agenda_events a ON a.id=wi.entity_id
WHERE wi.entity_type IN ('App\\Models\\AgendaEvent','App\\Modules\\Events\\Models\\AgendaEvent') AND a.id IS NULL;
SELECT wi.id, wi.entity_type, wi.entity_id FROM workflow_instances wi
LEFT JOIN monthly_activities m ON m.id=wi.entity_id
WHERE wi.entity_type IN ('App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity') AND m.id IS NULL;
SELECT wi.id, wi.entity_type, wi.entity_id FROM workflow_instances wi LEFT JOIN annual_agenda_edit_requests r ON r.id=wi.entity_id
WHERE wi.entity_type IN ('App\\Models\\AnnualAgendaEditRequest','App\\Modules\\Events\\Models\\AnnualAgendaEditRequest') AND r.id IS NULL;
SELECT wi.id, wi.entity_type, wi.entity_id FROM workflow_instances wi LEFT JOIN annual_agenda_delete_requests r ON r.id=wi.entity_id
WHERE wi.entity_type IN ('App\\Models\\AnnualAgendaDeleteRequest','App\\Modules\\Events\\Models\\AnnualAgendaDeleteRequest') AND r.id IS NULL;
SELECT wi.id, wi.entity_type, wi.entity_id FROM workflow_instances wi LEFT JOIN monthly_plan_edit_requests r ON r.id=wi.entity_id
WHERE wi.entity_type IN ('App\\Models\\MonthlyPlanEditRequest','App\\Modules\\Events\\Models\\MonthlyPlanEditRequest') AND r.id IS NULL;
SELECT wi.id, wi.entity_type, wi.entity_id FROM workflow_instances wi LEFT JOIN monthly_plan_delete_requests r ON r.id=wi.entity_id
WHERE wi.entity_type IN ('App\\Models\\MonthlyPlanDeleteRequest','App\\Modules\\Events\\Models\\MonthlyPlanDeleteRequest') AND r.id IS NULL;
SELECT oc.id, oc.correspondable_type, oc.correspondable_id FROM official_correspondences oc
LEFT JOIN monthly_activities m ON m.id=oc.correspondable_id
WHERE oc.correspondable_type IN ('App\\Models\\MonthlyActivity','App\\Modules\\Events\\Models\\MonthlyActivity') AND m.id IS NULL;
SELECT r.id, r.entity_type, r.entity_id FROM annual_agenda_edit_requests r LEFT JOIN agenda_events a ON a.id=r.entity_id WHERE a.id IS NULL;
SELECT r.id, r.entity_type, r.entity_id FROM annual_agenda_delete_requests r LEFT JOIN agenda_events a ON a.id=r.entity_id WHERE a.id IS NULL;
SELECT r.id, r.entity_type, r.entity_id FROM monthly_plan_edit_requests r LEFT JOIN monthly_activities m ON m.id=r.entity_id WHERE m.id IS NULL;
SELECT r.id, r.entity_type, r.entity_id FROM monthly_plan_delete_requests r LEFT JOIN monthly_activities m ON m.id=r.entity_id WHERE m.id IS NULL;
SELECT p.id, p.monthly_activity_id, p.monitoring_report_id FROM post_execution_verifications p
LEFT JOIN monthly_activities m ON m.id=p.monthly_activity_id
LEFT JOIN monitoring_reports r ON r.id=p.monitoring_report_id
WHERE (p.monthly_activity_id IS NOT NULL AND m.id IS NULL)
   OR (p.monitoring_report_id IS NOT NULL AND r.id IS NULL)
   OR (p.monthly_activity_id IS NULL AND p.monitoring_report_id IS NULL);
```

For request-model workflow orphans, join each exact request identity pair to its
matching request table; do not join request identities to parent aggregates.

## K. ROLLBACK DECISION

Rollback triggers include boot/route failure, canonical class resolution errors,
mixed-history invisibility, nonzero unexplained duplicates/orphans, failed
focused flows, migration ambiguity, or Ramadan data mutation.

1. Switch back to the recorded prior code artifact.
2. Run `php artisan config:clear` and `php artisan cache:clear`; restart workers
   and PHP-FPM using the deployment procedure.
3. No identity data rollback is needed because no backfill occurred. Never delete
   or rewrite legacy identity rows.
4. Roll back only migrations applied in this release and only after verifying
   their `down()` operations are safe against actual DDL/data. MySQL partial DDL
   requires a DBA-reviewed targeted plan, not blind rollback.
5. Compatibility remains in source; preserve it across rollback targets wherever
   possible.

## L. PRODUCTION READINESS

Go only when backup restore is proven, artifact/commit and timezone decisions are
recorded, boot/routes pass, migration status is reconciled, all MUST RETURN ZERO
queries are clean or formally explained, focused/full tests pass, Monthly and
Ramadan manual acceptance passes, and rollback is rehearsed.

### Seeder plan

Preferred production/reference command (review data diffs first):

```bash
php artisan db:seed --class=RamadanReferenceDataSeeder
```

That composite seeder runs the approved order: target groups, beneficiary
segments, monitoring methods, mobilization methods, community organizations,
local communities, dashboard default, canonical execution needs, Ramadan
period, then Ramadan guidance. If operational policy requires component runs,
execute only the needed component in that same dependency order; do not run the
components and composite seeder redundantly. `EventReferenceDataSeeder` covers
shared Events catalogues separately; inspect its contents before use. Verify the
guidance seeder's
Ramadan code/source hash, code-scoped uniqueness, current published resolution,
idempotent rerun, immunity to unrelated hashes, and preservation of a newer
Admin-created current version.

Staging/demo only: `RamadanIftarStagingSeeder` and `RamadanIftarDemoSeeder`.
**Never run `RamadanIftarDemoSeeder` in production.** Showcase/temporary seeders
are also non-production unless separately authorized.

### Compatibility retirement

Do not retire compatibility during cutover. Retirement needs staging inventory,
a production observation window, zero legacy-writer evidence, an explicit
backfill-versus-retention decision, and a verified rollback plan. Permanent
compatibility is acceptable where removal offers little value.
