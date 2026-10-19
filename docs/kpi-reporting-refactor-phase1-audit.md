# KPI & Reporting Refactor — Phase 1 Audit

**Audit date:** 2026-10-19
**Branch audited:** `codex/add-ramadan-iftar-planning-details` @ `30dabe5` (clean tree)
**Method:** static source inspection only (routes, controllers, services, models, migrations, seeders, views, config, JS assets, reference spreadsheets). No runtime tests, no DB access, no migrations, no code changes.
**Scope:** retire the legacy reporting surfaces and define a unified, source-derived KPI system for Annual Agenda, Monthly Activities, Ramadan Iftars and Bazaars. Every KPI below is mapped to real columns. Anything the data cannot support is marked PARTIAL or MISSING, never estimated.

> **Targets:** the source contains **no** business targets, SLAs or deadlines for any KPI. The only date rule is `monthly_plan_lock_days` (default 5, which produces `monthly_activities.lock_at`). Every target value in this document is a **proposed default that needs business sign-off**. Targets must be configurable, not hard-coded.

---

## 0. Executive findings

1. **Five unrelated reporting stacks exist, and no two share a metric definition:**
   - **A.** Super-admin *Admin Reports* (`AdminReportsService`).
   - **B.** *Reports Viewer* pages (`Web/Reports/*`). These are mostly list stubs, and all six exports are fake.
   - **C.** *Enterprise Analytics* (`EnterpriseAnalyticsService`, plus CSV exports and a print view, all orphaned).
   - **D.** *Monthly KPIs* (`monthly_kpis`). Values are **typed in by hand**; nothing is computed.
   - **E.** *Evaluation / Follow-up* dashboards. This is the only weighted score in the system.

   Inline stats also exist on `/dashboard`, on the profile page, in the approval queue and in change-request reports.
2. **"Executed" has four definitions:**
   - `actual_date IS NOT NULL` (Enterprise)
   - a 7-way OR (`AdminReportsService.php:210-227`)
   - `status='completed'` (Profile; this value is never written for Monthly)
   - `post_execution_payload` present (Evaluation)

   **"Approved" has three definitions.** **Period** is keyed on `proposed_date`, `activity_date` or `month/day` depending on the screen.
3. **Branch isolation is missing** on Reports, KPIs, Enterprise, Admin Reports, change-request reports and `/directory/users`. Only the Evaluation and Follow-up stacks scope by branch.
4. **Defects found in existing code** (not fixed in this run; listed so they can be fixed in a separate PR):

   | # | Defect | Evidence |
   |---|---|---|
   | D1 | Saving a KPI crashes. `MonthlyKpisController::store` matches on `center_id`, but that column was dropped. | `app/Http/Controllers/Web/Reports/MonthlyKpisController.php:42-50`; `database/migrations/2026_04_02_120000_drop_center_dependencies.php:17,63` |
   | D2 | KPI save is blocked for super_admin and `kpi.manage` holders, even though the route lets them in. | `MonthlyKpisController.php:25` (`abort_unless(hasRole('followup_officer'))`) vs `routes/web.php:443` |
   | D3 | Enterprise charts never load: the Chart.js CDN URL is malformed. | `resources/views/pages/enterprise/partials/charts-scripts.blade.php:2` (`npm/chart.min.js`) |
   | D4 | The Admin Reports "enterprise" tab renders chart canvases without the chart script. | `pages/admin/reports/partials/enterprise.blade.php` |
   | D5 | Enterprise branch "participation %" is always 100 (or 0). | `EnterpriseAnalyticsService::branchMetrics` (`total / max(1,total)`) |
   | D6 | Six report "Export" buttons only redirect back with a success flash; no file is produced. | `Web/Reports/*Controller::export` |
   | D7 | Bazaar `invitation_type`, `private_audience` and `day_count` are created on **`bazaar_tables`**, but the model and controller write them to **`bazaars`**. | `2026_10_03_000200_create_bazaar_tables_table.php:37-39`; `Bazaar.php:22`; `BazaarController.php:64,78` |
   | D8 | `TrackUserOperations` stores `entity_id = 0`: route-model binding has already turned the parameter into an object. | `app/Http/Middleware/TrackUserOperations.php:28-39` |
   | D9 | The follow-up officer's exclusive sidebar has no reports or KPI links, although the role holds `reports.view`, `kpi.view` and `kpi.manage` and is the only role that can save KPIs. | `resources/views/layouts/app.blade.php:123-133` |
   | D10 | The Admin Reports sidebar link is shown to `$canAccessAdminSidebar` users, but the route is `role:super_admin` only, so those users get a 403. | `layouts/app.blade.php:152`; `routes/web.php:174` |
   | D11 | Score weights `branch_monthly_score_weight_satisfaction` (40) and `_commitment` (60) can be edited in Site Settings, but **no code reads them**. | `SiteSettingsController.php:32-33` |

   **Phase 0.5 status (2026-10-19, static source fixes; no runtime tests executed):**

   | # | Status | Fix and source reference |
   |---|---|---|
   | D1 | **FIXED** | `updateOrCreate` now matches on `year`, `month` and `branch_id` only. The dropped `center_id` is removed from both the match keys and the validation rules (`app/Http/Controllers/Web/Reports/MonthlyKpisController.php:43-66`). |
   | D2 | **FIXED** | The controller's hard `hasRole('followup_officer')` check is replaced by `canManageKpis()`, which mirrors the route middleware: super_admin, followup_officer or `kpi.manage` (`MonthlyKpisController.php:34,74-77`). The form is shown on the same condition (`resources/views/pages/reports/kpis.blade.php:54`). Branch-scoped users (`isBranchScopedPlanningUser()`) must save against one of their `scopedBranchIds()`; the value is auto-filled when they have only one branch, and they don't see the "all branches" option (`MonthlyKpisController.php:36-48`, `kpis.blade.php:71`). |
   | D3 | **FIXED** | Chart.js is pinned to `https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js` (`resources/views/pages/enterprise/partials/charts-scripts.blade.php:2`). `pages-enterprise-charts(.min).js` only uses the v3/v4-compatible `line` and `doughnut` APIs and needs no change. |
   | D7 | **FIXED** | The three columns moved from `2026_10_03_000200_create_bazaar_tables_table.php` to `2026_10_03_000100_create_bazaars_table.php:22-24`, with identical types and defaults. The model, request, controller, form and show view already targeted `bazaars`. Staging reconciliation SQL is in §0.2. |
   | D8 | **FIXED** | `TrackUserOperations::resolveEntity()` reads `getKey()` from bound models and accepts an opt-in `audit_entity` request attribute for create routes (`app/Http/Middleware/TrackUserOperations.php:30,46-74`). `BazaarController::store` sets it (`app/Modules/Events/Http/Controllers/Bazaar/BazaarController.php:69`). Other create routes with no route parameter still log `entity_id = 0` until they opt in. |
   | D4 | **RESOLVED (Phase 2)** | The Admin Reports enterprise tab and partial were removed. |
   | D5 | **RETIRED (Phase 2)** | `branchMetrics()` is unreachable: Enterprise routes redirect and the code is `@deprecated` for Phase 9 deletion. |
   | D6 | **RESOLVED (Phase 2)** | All six fake export routes, methods and forms were removed. |
   | D10 | **FIXED (Phase 2)** | The "تقارير الإدارة" sidebar link is shown to super_admin only (`resources/views/layouts/app.blade.php`). |
   | D9 | **FIXED (Phase 3)** | The follow-up sidebar includes the shared KPI navigation partial when the user has `kpi.view`. |
   | D11 | STILL OPEN | 40/60 branch-score business decision. |

   Leftover `center_id` keys in `ZahaTimeController`, `BookingsController`, `MaintenanceRequestsController` and `UsersController` are dropped silently by `$fillable` and cause no SQL error. They are dead code, not a defect.

### 0.2 D7 — reconciling a staging DB that already ran the Bazaar migrations (MySQL, manual, run in order)

Use this SQL instead of `migrate:fresh`: editing the migration files does not change a database whose `migrations` table already records them. Nothing in the code writes these columns on `bazaar_tables` (they are not in `BazaarTable::$fillable`, and `BazaarController::sync` never sets them), so table rows should only hold the defaults. Step 3 checks this before anything is dropped.

```sql
-- 0) Inspect current placement. Expected on an affected DB: 3 rows on bazaar_tables, 0 on bazaars.
SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN ('bazaars', 'bazaar_tables')
  AND COLUMN_NAME IN ('invitation_type', 'private_audience', 'day_count')
ORDER BY TABLE_NAME, COLUMN_NAME;
-- If bazaars already has all 3 and bazaar_tables has none: stop, nothing to do.
-- If bazaar_tables has none (DB migrated before commit 30dabe5): run step 2 only.

-- 1) Back up the misplaced values (only if bazaar_tables has the columns).
CREATE TABLE bazaar_tables_d7_backup AS
SELECT id, bazaar_id, invitation_type, private_audience, day_count, NOW() AS backed_up_at
FROM bazaar_tables;

-- 2) Add the Bazaar-level columns (definitions match 2026_10_03_000100_create_bazaars_table.php).
ALTER TABLE bazaars
  ADD COLUMN invitation_type VARCHAR(20) NOT NULL DEFAULT 'public' AFTER map_url,
  ADD COLUMN private_audience TEXT NULL AFTER invitation_type,
  ADD COLUMN day_count INT UNSIGNED NOT NULL DEFAULT 1 AFTER private_audience;

-- 3) Find any non-default values that must be preserved (expected: 0 rows).
SELECT bazaar_id, COUNT(*) AS non_default_rows
FROM bazaar_tables
WHERE invitation_type <> 'public' OR private_audience IS NOT NULL OR day_count <> 1
GROUP BY bazaar_id;

-- 4) Copy them up, using the lowest-id non-default table row per bazaar. This is a no-op when step 3 returned 0 rows.
UPDATE bazaars b
JOIN (
    SELECT bazaar_id, MIN(id) AS table_id
    FROM bazaar_tables
    WHERE invitation_type <> 'public' OR private_audience IS NOT NULL OR day_count <> 1
    GROUP BY bazaar_id
) pick ON pick.bazaar_id = b.id
JOIN bazaar_tables t ON t.id = pick.table_id
SET b.invitation_type = t.invitation_type,
    b.private_audience = t.private_audience,
    b.day_count = t.day_count;

-- 5) Verify before dropping: every bazaar has valid values and the backup row count matches bazaar_tables.
SELECT COUNT(*) AS invalid_bazaars FROM bazaars
WHERE invitation_type NOT IN ('public', 'private') OR day_count < 1;          -- expected 0
SELECT (SELECT COUNT(*) FROM bazaar_tables) AS table_rows,
       (SELECT COUNT(*) FROM bazaar_tables_d7_backup) AS backup_rows;         -- expected equal

-- 6) Remove the misplaced columns from bazaar_tables (only after step 5 passes).
ALTER TABLE bazaar_tables
  DROP COLUMN invitation_type,
  DROP COLUMN private_audience,
  DROP COLUMN day_count;

-- 7) Later, once staging has been checked: DROP TABLE bazaar_tables_d7_backup;
```

5. **The live sidebar is the inline one** in `resources/views/layouts/app.blade.php:115-265`. `layouts/app/sidebar.blade.php`, `layouts/dashboard.blade.php`, `pages/reports/partials/sidebar.blade.php` and `pages/access/partials/sidebar.blade.php` are not rendered by any page.
6. **Workflow timing must come from logs, not columns:**
   - `workflow_instances.started_at` is a **lazy-creation** time: `firstOrCreate` runs when a page is viewed (`DynamicWorkflowService.php:95-106`, `MonthlyWorkflowPresenter.php:33-35`, `AgendaApprovalsController.php:57-58`).
   - Monthly has **no** `submitted_at`, `approved_at` or `closed_at` columns.
   - Agenda has no `submitted_at`.
   - Bazaar has no `closed_at`.

   Reliable sources are `workflow_logs.acted_at` and `workflow_action_logs.performed_at`. Both store `entity_type` in a legacy form and a canonical form (`EventAggregateIdentity::acceptedTypes()`).
7. **Version rows inflate counts.** Approved edit requests create new rows in agenda, monthly and Ramadan. Every KPI must count only *live* rows (see §13.1).

---

## 1. Existing KPI / Reporting Inventory

| # | Existing KPI/Report | Location (route → controller → view) | Users | Data Source | Live? | Keep/Migrate/Retire |
|---|---|---|---|---|---|---|
| 1 | Main dashboard: calendar stat pills | `dashboard` → `DashboardController@index:249-268` → `dashboard.blade.php:110-128` | all auth | counts of agenda (all years, unscoped), monthly (unscoped), ramadan / bazaar (current year, scoped) | live | **Migrate**: keep the calendar, drop the unscoped counts, link to KPI overview |
| 2 | Main dashboard: Ramadan block | `DashboardController:275-336` | `ramadan_iftars.view` + setting on | `ramadan_iftars`, `monitoring_reports` | live | **Keep** (operational work-queue, not a KPI); later reuse the RI-xx definitions |
| 3 | Main dashboard: communication-head cards | `DashboardController:34-77` | communication_head | `communications_requests` | live | **Keep** (queue, out of scope) |
| 4 | Main dashboard: nav cards | `dashboard.blade.php:70` | all | none (the value shown is `$loop->iteration`) | live | **Retire** the fake "value"; keep the links |
| 5 | Follow-up dashboard | `followup.dashboard` → `FollowupWorkspaceController@dashboard:17-57` | followup_officer, evaluation_officer | verifications, evaluations, ramadan monitoring (branch-scoped) | live | **Keep** as a work-queue; KPIs move to U-FUP/MA/RI |
| 6 | Evaluation dashboard (التقييم) | `evaluations.dashboard` → `EvaluationDashboardController` → `pages/evaluation/dashboard.blade.php` | `evaluation.view_branch` / `view_all` | `activity_evaluations`, `post_execution_verifications` | live, **no sidebar link** | **Migrate** metrics to MA-13…MA-16; keep the page for forms/queues |
| 7 | Admin Reports — relations tab | `role.super_admin.reports` → `ReportsController` + `AdminReportsService::relationsReport:81-198` → `pages/admin/reports/partials/relations` | super_admin | `monthly_activities` (by `activity_date`), workflow_instances (lazy `started_at`), edit/delete requests, agenda by status | live | **Migrate** to MA-01/02/06/09, AG-01/02; **Retire** the "approval speed" metric (wrong start time) |
| 8 | Admin Reports — overview tab | same → `partials/overview` | super_admin | Branch, User, Vehicle counts; Payment, DonationCash sums | live | **Retire** from KPI scope (structure counts / finance); finance stays with finance |
| 9 | Admin Reports — operations tab | same → `partials/operations` | super_admin | all-time counts of agenda, monthly, booking, maintenance, trip; execution-needs JSON aggregates | live | **Migrate** the needs metrics to MA-19/20; **Retire** the rest |
| 10 | Admin Reports — enterprise tab | same → `partials/enterprise` | super_admin | `EnterpriseAnalyticsService` | live (charts broken, D4) | **Retire** (replaced by the KPI overview) |
| 11 | Admin Reports — zaha_time / daily op logs / user delay stats | `AdminReportsService` (computed, never rendered) | — | `audit_logs`, `execution_needs_payload` | **dead computation** | **Dead code**: remove in Phase 9 |
| 12 | Orphan old Admin Reports page | `resources/views/roles/super_admin/reports.blade.php` (465 lines) | — | expects `$adminPlanReports` (never provided) | **dead** | **Dead code**: remove in Phase 2 |
| 13 | Enterprise dashboard (تحليلات المؤسسة) | `role.enterprise.dashboard` → `EnterpriseDashboardController@index` → `pages/enterprise/dashboard` | reports_viewer, followup_officer, super_admin (role only) | agenda/monthly by year; filters only partly applied | live (charts broken D3) | **Replace** with the KPI overview (Phase 3) |
| 14 | Branch performance report | `role.reports.enterprise.branch_performance` → `branchReport` | same | `branchMetrics` (D5 bug; "approved" counts only rows still at `approved`) | live | **Replace** with the Branch KPIs page (Phase 4) |
| 15 | Annual planning overview | `role.enterprise.annual_planning` | same | agenda per month, participations | **orphan** | **Replace** with the Agenda module KPI page; then remove |
| 16 | Enterprise CSV exports ×3 + printable | `role.reports.enterprise.{agenda,monthly,approval}_export`, `.printable` → `EnterpriseReportsController` | same | agenda/monthly + workflow presenter | **orphan** | **Migrate**: reuse `csvResponse()` as the basis of the KPI CSV export; retire the routes after Phase 7 |
| 17 | Reports overview (نظرة عامة للتقارير) | `role.reports.index` → `ReportsController` | reports_viewer, followup, super_admin, `reports.view` | none (filter form → fake export) | live, empty | **Replace** with the KPI overview |
| 18 | Agenda report | `role.reports.agenda.index` → `AgendaReportsController` | same | all agenda rows (no filters) | live | **Replace** with the AG drill-down |
| 19 | Monthly report | `role.reports.monthly.index` → `MonthlyReportsController` | same | all monthly rows incl. archived, unscoped | live | **Replace** with the MA drill-down |
| 20 | Finance / Maintenance / Transport reports | `role.reports.{finance,maintenance,transport}.index` | same | donation/booking/zaha_time counts (via `->get()->count()`), request & trip lists | live | **Still needed / out of scope**: keep in place, move into an "تقارير تشغيلية" group; not part of the events KPI system |
| 21 | Fake exports ×6 | `role.reports*.export` (POST) | same | none | live, fake | **Retire** (replaced by real CSV export in Phase 7) |
| 22 | Monthly KPIs (manual) | `role.reports.kpis.index/store` → `MonthlyKpisController` → `pages/reports/kpis` | view: reports_viewer, followup, super_admin, `kpi.view`; save: followup only (D1/D2) | `monthly_kpis`, hand-typed; seeded with demo values | live, save broken | **Replace** with computed MA/B KPIs. **Do not drop the table** (historical manual data); make it read-only "legacy" then archive |
| 23 | Change-request reports | `role.super_admin.monthly_activities.change_requests.reports` → `MonthlyActivityReportsController` | super_admin, `admin` (not seeded) | edit/delete request tables | **orphan** (tests only) | **Migrate** to MA-09 drill-down; keep the route while its tests reference it |
| 24 | Approval-queue KPI strip + admin request stats | `role.programs.approvals.index` → `MonthlyActivityApprovalQueueController:132-136, 328-347` | approvers / super_admin | paginator / current-page counts | live | **Keep** (queue counters); fix the per-page counts later (not KPI) |
| 25 | Monthly browse summary cards | `MonthlyActivitiesBrowseController:202-245` | monthly users | monthly by status / current step | live | **Keep** (list-page context) |
| 26 | Returned-feedback counts | `MonthlyActivityFeedbackController:177-184` | monthly users | returns by type | live | **Keep** (badge) |
| 27 | Profile stats + evaluations | `profile.show` → `ProfileController:28-29,103-145` | self | created, branch, "completed" (wrong definition), workflow_logs count; legacy evaluation responses | live | **Replace** with the My Profile performance section (Phase 6) |
| 28 | Admin user view stats | `role.super_admin.users.show` → `Web/Access/UsersController:31-56` | `users.view` | same as profile | live | **Replace** with the shared user performance partial |
| 29 | Role launcher dashboards (super_admin, relations_manager/officer, programs_manager/officer, reports_viewer, staff) | `Roles/*/DashboardController` → `roles/*/dashboard` | per role | none, or agenda per month | **orphan** (no `route()` references) | **Deprecated**: hide in Phase 2, delete in Phase 9 after confirming no external links/bookmarks |
| 30 | Finance / maintenance / transport officer dashboards | `Roles/*Officer/DashboardController` | per role | none | linked via module sidebars | **Out of scope**: keep |
| 31 | Activity evaluation weighted score | `ActivityEvaluationService:52-98` | followup_officer submits | `activity_evaluations.normalized_score` (1–10) | live | **Keep** as the data source for MA-16 / U-REL-09 |
| 32 | Evaluation assignments admin | `role.super_admin.evaluation_assignments.index` | super_admin | — | orphan | **Out of scope**: keep, add a link in the admin group (separate task) |

## 2. Existing Dashboard KPI Inventory

| Dashboard | Card / metric | Calculation today | Problem | Maps to |
|---|---|---|---|---|
| `/dashboard` | إجمالي السجلات / فعاليات الأجندة / الخطط الشهرية | `count()` all years; agenda & monthly unscoped | unbounded, not branch-scoped | AG-01, MA-01 (period + scope) |
| `/dashboard` | الإفطارات / البازارات | current year, scoped | OK | RI-01, BZ-01 |
| `/dashboard` | الأكثر مشاركة | branch with most calendar records | vanity metric | (drop) → B-X-01 branch ranking |
| `/dashboard` Ramadan block | total, upcoming, today, draft, submitted, approved, active, awaiting follow-up, follow-up submitted, closed | live rows in the current period | OK (queue) | RI-01…RI-05 |
| Follow-up | all plans / awaiting post-review / awaiting evaluation / evaluated; Ramadan monitoring states; verification breakdown | branch-scoped counts | OK (queue) | MA-13, MA-15, U-FUP-03, RI-04 |
| Evaluation | totals, pending verification, incorrect data, awaiting evaluation, evaluated, avg score, this-month count, completion %, branch averages, 6-month trend | `activity_evaluations`, verifications | "total activities" is unbounded; requires exactly one branch when not `view_all` | MA-13…MA-16 |
| Admin Reports relations | per-branch total / completed / pending approval / pending edit / pending delete / pending coordinator; agenda by status; approval speed | `activity_date` month; `started_at → completed_at` | approval speed uses lazy `started_at`; "completed" is a 7-way OR | MA-01, MA-02, MA-06, MA-09, AG-01 |
| Admin Reports operations | needs payload count, followup count, secured / not_secured, avg effectiveness | JSON | all-time, unscoped | MA-19, MA-20 |
| Enterprise | إجمالي الفعاليات، المعتمدة، المرفوضة، قيد الانتظار، المنفذة، مشاركة الفروع، الالتزام بالخطة; monthly trend line; approval doughnut | year only; executed = `actual_date` | charts broken; filters ignored | AG-01, AG-02, MA-02, AG-06 |
| Enterprise branch table | total / participation % / approval % / completion % | `branchMetrics` | participation is always 100 | MA-01, MA-02, B-X-01 |
| Monthly KPIs | داخل الخطة، خارج الخطة، نسبة التعديل، التزام الخطة، كفاءة الحشد، تقييم الفرع، تقييم المتابعة | **manual** | not source-derived | see §4 row 22 |
| Profile | created / branch / completed / workflow actions; evaluation assigned / completed / avg | 15-minute cache | "completed" = `status='completed'` (never written for Monthly); legacy responses | U-REL-*, U-APR-01, U-FUP-04 |

## 3. Existing Report Pages

| Page | Route | Rows / columns | Filters | Export | Branch scope | Linked |
|---|---|---|---|---|---|---|
| تقارير الإدارة (4 tabs) | `role.super_admin.reports` | per-branch tables, status lists | year, month | none | none | sidebar (D10) |
| تحليلات المؤسسة | `role.enterprise.dashboard` | KPI cards, 2 charts, branch table | year (others ignored) | none | none | sidebar |
| تقرير أداء الفروع | `role.reports.enterprise.branch_performance` | branch table | year | none | none | sidebar |
| نظرة عامة على التخطيط السنوي | `role.enterprise.annual_planning` | 12 months × events | year | none | none | orphan |
| CSV: agenda / monthly / approval matrix | `role.reports.enterprise.*_export` | see controller | enterpriseFilter | **real CSV** (no BOM) | optional `branch_id` only | orphan |
| Printable | `role.reports.enterprise.printable` | agenda + monthly tables | enterpriseFilter | HTML print CSS (no print button) | none | orphan |
| نظرة عامة للتقارير | `role.reports.index` | — | form only | fake | none | sidebar |
| تقرير الأجندة / الأنشطة الشهرية | `role.reports.agenda.index`, `.monthly.index` | name, date, raw status | none applied | fake | none | sidebar |
| إيرادات / صيانة / نقل | `role.reports.{finance,maintenance,transport}.index` | counts / lists | none | fake | none | sidebar |
| مؤشرات الأداء الشهرية | `role.reports.kpis.index` | manual KPI rows | none | none | none | sidebar |
| تقارير طلبات التغيير | `role.super_admin.monthly_activities.change_requests.reports` | request counters, tables, groupBy | branch, type, status, step, requester, date | none | none | orphan |
| لوحة التقييم | `evaluations.dashboard` | evaluation analytics | — | none | yes | redirect only |

## 4. Old KPI/Report Replacement Map

Classification: **replaced** (a new KPI page supersedes it), **still needed** (keep; not a KPI), **deprecated** (hide now, delete after a grace period), **dead code** (source-proven unused; safe to remove).

| Old item | Class | Replacement | Remove when |
|---|---|---|---|
| `roles/super_admin/reports.blade.php` + `public/assets/js/pages/roles-super_admin-reports(.min).js` | dead code | — | Phase 2 |
| `layouts/app/sidebar.blade.php`, `layouts/dashboard.blade.php`, `pages/reports/partials/sidebar.blade.php`, `pages/access/partials/sidebar.blade.php`, `layouts-dashboard(.min).js` | dead code | — | Phase 2 (after a final `grep` for `layouts.dashboard`) |
| AdminReportsService `zaha_time`, `daily_operation_logs`, `user_delay_stats`; Enterprise `eventsPerDepartment`, `eventsPerBranch`, `activityCompletionRate`, `annualOverview`; `$activities` in annual planning; `by_branch` in queue stats | dead code | — | Phase 9 |
| Admin Reports relations tab | replaced | KPI Overview + MA/AG module pages | Phase 4 sign-off |
| Admin Reports overview / operations / enterprise tabs | replaced (events parts) / still needed (finance counts → finance module) | KPI Overview | Phase 4 |
| Enterprise dashboard, branch performance, annual planning | replaced | KPI Overview, Branch KPIs, Agenda KPIs | Phase 5 sign-off |
| Enterprise CSV/printable | replaced (code reused) | KPI drill-down CSV + print | Phase 7 |
| Reports overview / agenda / monthly pages | replaced | KPI Overview + drill-downs | Phase 4/5 |
| Six fake `*.export` POST routes | deprecated → removed | real CSV export | Phase 7 |
| Finance / maintenance / transport report pages | still needed | stay; regroup under "تقارير تشغيلية" | — |
| Monthly KPIs (manual) | replaced | computed equivalents (below); legacy table becomes read-only | hide in Phase 4; keep the table |
| Change-request reports | replaced | MA-09 drill-down | Phase 7 (update tests) |
| Evaluation dashboard metrics | replaced (metrics) / still needed (page as evaluation workspace) | MA-13…16 | — |
| Profile / user-admin stats | replaced | §17 performance section | Phase 6 |
| Orphan role launcher dashboards | deprecated | role-aware `/dashboard` | Phase 9 |
| `/dashboard` stat pills | replaced (scoped + bounded) | KPI Overview mini-strip | Phase 3 |

**Phase 2 status (2026-10-19)** — full detail in `docs/kpi-reporting-refactor-phase2-cleanup.md`:

| Old item | Phase 2 outcome |
|---|---|
| `roles/super_admin/reports.blade.php` + JS | **REMOVED** |
| Dead `layouts.dashboard` chain (layout, header/page-header/footer, 7 module sidebar partials, their CSS/JS) | **REMOVED** |
| AdminReportsService `zaha_time`, `daily_operation_logs`, `user_delay_stats`; queue `by_branch` | **REMOVED** |
| Admin Reports enterprise tab (+ partial) and Mermaid loader | **REMOVED** |
| Admin Reports approval-speed panel | **HIDDEN** (computation kept for identity-compatibility tests until Phase 4) |
| Admin Reports overview "centers = 0" row | **REMOVED** |
| Admin Reports relations / overview / operations tabs | **KEPT** until Phase 4 |
| Enterprise dashboard, branch performance, annual planning | **RETIRED**: nav removed, routes redirect, code `@deprecated` (delete Phase 9) |
| Enterprise CSV exports / printable | **KEPT** (Phase 7 reuse) |
| Reports overview | **REPLACED** by the "تقارير تشغيلية" hub |
| Agenda / Monthly report pages | **RETIRED**: routes redirect to the hub, code `@deprecated` |
| Finance / Maintenance / Transport reports | **KEPT**; fake exports removed; branch scope added where the table has `branch_id` |
| Six fake `*.export` routes | **REMOVED** |
| Monthly KPIs (manual) | **RETIRED**: nav, entry form and store route removed; read-only history page kept; table and rows untouched |
| Change-request reports | **KEPT** (tests; Phase 7) |
| Profile / admin user-view stats | **REMOVED** (Phase 6 adds the new panel) |
| Orphan launcher dashboards (7) | **REMOVED** (controllers + views); routes redirect |
| `/dashboard` fake card values and "الأكثر مشاركة" pill | **REMOVED**; other calendar pills kept |
| D10 (Admin Reports link 403) | **FIXED** (super_admin only) |

**Manual `monthly_kpis` fields → computed KPIs**

| Manual field | Computed replacement | Note |
|---|---|---|
| داخل الخطة `planned_activities_count` | MA-01 split by `is_from_agenda`/`is_in_agenda` = 1 | READY |
| خارج الخطة `unplanned_activities_count` | MA-01 split, `is_in_agenda = 0` | READY. Needs business confirmation that "plan" = agenda |
| نسبة التعديل % | MA-09 (edit + delete requests ÷ approved) | READY |
| التزام الخطة % | MA-02 executed ÷ planned | READY |
| كفاءة الحشد % | MA-17 attendance achievement | READY |
| تقييم الفرع % | MA-16 avg evaluation ×10 (or the §13 score if approved) | READY |
| تقييم المتابعة % | MA-13 verification + MA-15 evaluation completion | READY |

---

## 5. New KPI Architecture

### 5.1 Two families, never mixed

| Family | Owner type | Question it answers | Attribution key |
|---|---|---|---|
| **A. User Performance** (`family = user`) | `user` | How well did *this person* perform their workflow role? | actor columns: `workflow_logs.acted_by`, `workflow_action_logs.performed_by`, `post_execution_verifications.verified_by`, `activity_evaluations.evaluated_by`, `monthly_activities.created_by`, `ramadan_iftars.relations_officer_id`, `bazaars.relations_officer_id` |
| **B. Branch / Event Performance** (`family = branch`) | `branch` or `event` | How well did *a branch / module / event* deliver? | `branch_id` on the subject; event-level rows feed drill-downs |

A user KPI is never computed from branch totals. A branch KPI is never attributed to an individual.

### 5.2 Central definition layer (proposed; build in Phase 3)

```
config/kpis.php                     ← single catalogue (definitions only, no SQL)
app/Services/Kpi/
  KpiDefinition.php                 ← value object built from config
  KpiRegistry.php                   ← lookup by code / module / family / role; filters active
  KpiPeriod.php                     ← date range, year, month, previous-period helper
  KpiScope.php                      ← resolves allowed branch_ids / user_ids from the viewer (reuses scopedBranchIds)
  KpiResult.php                     ← value, numerator, denominator, target, status, trend, readiness
  KpiTargetResolver.php             ← config default → Setting override (key kpi.target.{code})
  Calculators/
    MonthlyActivityKpiCalculator.php
    AgendaKpiCalculator.php
    RamadanKpiCalculator.php
    BazaarKpiCalculator.php
    UserPerformanceKpiCalculator.php
  Queries/
    LiveSubjects.php                ← canonical live-row scopes per module (§13.1)
    WorkflowTiming.php              ← submission / decision timing from logs (§13.3)
  DrillDown/
    {Module}DrillDownQuery.php      ← row-level queries reused by page + CSV export
```

Each definition in `config/kpis.php` has these fields:

```php
'MA-02' => [
    'code' => 'MA-02',
    'name_ar' => 'نسبة الأنشطة المنفذة من المخطط',
    'name_en' => 'Planned vs executed rate',
    'description_ar' => '...',
    'family' => 'branch',              // user | branch
    'owner_type' => 'branch',          // user | branch | event
    'category' => 'execution',         // planning | approval | execution | post_execution | verification | evaluation | resources | finance
    'modules' => ['monthly_activities'],
    'formula_ar' => 'المنفذة ÷ المعتمدة للتنفيذ × 100',
    'numerator' => 'executed live activities in period',
    'denominator' => 'approved live activities whose planned date is in period and <= today',
    'unit' => 'percentage',            // percentage | count | days | hours | score | currency
    'operator' => '>=',                // >= | <= | = | null (informational, no target)
    'target' => 90,                    // proposed default; overridable via Setting
    'target_status' => 'proposed',     // proposed | approved
    'calculator' => MonthlyActivityKpiCalculator::class.'@plannedVsExecuted',
    'drilldown_route' => 'kpi.reports.show',  // + ['kpi' => 'MA-02']
    'applies_to_roles' => [],          // user family only
    'readiness' => 'READY',            // READY | PARTIAL | MISSING
    'active' => true,                  // MISSING ⇒ active=false (never rendered with a value)
    'refresh' => 'live',               // live | cached_15m | monthly_snapshot
    'guide' => ['example' => [...], 'read' => '...', 'not_met' => '...'],
],
```

Rules:
- Blade never contains a formula. Views render `KpiResult` objects.
- The KPI Guide (§18) is generated from the same config, so documentation cannot drift from the calculation.
- `readiness = MISSING` ⇒ the card shows "بيانات غير متوفرة". It never shows 0 or an estimate.
- `operator = null` ⇒ the KPI is informational: no achieved/not-achieved badge.
- The status badge is computed from operator and target: achieved / not achieved / no target.
- Trend = the same KPI over the previous equal-length period.

### 5.3 Canonical shared definitions (fixes the conflicting old definitions)

| Concept | Canonical definition |
|---|---|
| Live Monthly row | `deleted_at IS NULL AND is_archived = 0 AND status NOT IN ('archived')` AND no newer version (`NOT EXISTS (… previous_version_id = id)` / `parent_version_id = id`) |
| Monthly "planned" (approved for execution) | live AND (`status IN ('approved','post_execution_submitted','closed','evaluated')` OR `lifecycle_status IN ('Approved','Scheduled','Executed','Evaluated','Closed')`) |
| Monthly "executed" | live AND (`execution_status = 'executed'` OR `status IN ('post_execution_submitted','closed','evaluated')`). Matches `MonthlyActivity::hasExecutionEvidenceForDisplay()`; the 7-way OR is retired |
| Monthly period date | `proposed_date` (indexed in Phase 9). `activity_date` is not used |
| Live Agenda row | `deleted_at IS NULL AND is_archived = 0 AND status <> 'archived'` |
| Live Ramadan row | `deleted_at IS NULL` AND no row with `parent_version_id = id` |
| Bazaar "executed" | `status IN ('post_execution','verified','completed')` |
| Bazaar "closed" | `status = 'completed'` (no timestamp, see gap) |
| Workflow entity types | always `whereIn(entity_type, EventAggregateIdentity::acceptedTypes(...))` |
| Human decisions | `workflow_logs` on main steps, excluding auto-approval rows (`DynamicWorkflowService.php:369-404, 511-515`) |

---

## 6. User Performance KPI Catalogue

Scope: the user's own actions in the period. Shown only to roles listed in `applies_to_roles` (§17).

| Code | الاسم | Applies to roles | Modules | Source | Readiness |
|---|---|---|---|---|---|
| **Approvers / supervisors** |||||
| U-APR-01 | عدد القرارات المنجزة | supervisor, branch_coordinator, relations_manager, executive_manager | MA, AG, RI | `workflow_logs` (`acted_by`, `action IN approved/changes_requested/rejected`, main steps, not auto) | READY |
| U-APR-02 | متوسط زمن اتخاذ القرار (ساعة) | same | MA, AG, RI | `workflow_logs.acted_at` − step entry time (§13.3) | PARTIAL |
| U-APR-03 | نسبة القرارات ضمن الزمن المستهدف | same | MA, AG, RI | derived from U-APR-02 | PARTIAL |
| U-APR-04 | نسبة الإعادة للتعديل | same | MA, AG, RI | `action='changes_requested'` ÷ decisions | READY (informational, no target) |
| U-APR-05 | زمن مراجعة ما بعد التنفيذ (ساعة) | supervisor | MA | `workflow_action_logs` `closed`/`post_execution_clarification`/`post_execution_rejected` − last `post_execution_submitted` | PARTIAL |
| U-APR-06 | زمن مراجعة تقارير متابعة الإفطار | supervisor | RI | `workflow_action_logs` `monitoring_approved/returned` − `monitoring_verification_submitted/resubmitted` | PARTIAL |
| U-APR-07 | قرارات طلبات الخصم | supervisor (`bazaars.approve`) | BZ | `bazaar_table_discounts.approved_by`, `status`, `approved_at` | PARTIAL (no time for return/reject) |
| U-APR-08 | دقة القرار | — | — | no ground truth | **MISSING**: do not build |
| **Relations Officer** |||||
| U-REL-01 | نسبة الاعتماد من أول تقديم (اكتمال الطلب) | relations_officer, supervisor (as creator) | MA, RI, AG | approved instances with `edit_request_count = 0` ÷ approved | READY |
| U-REL-02 | متوسط الزمن من الإنشاء إلى التقديم (يوم) | same | MA, RI, AG | MA: first `workflow_action_logs.submitted` − `created_at`; RI: `submitted_at − created_at`; AG: first sub-step `workflow_logs.acted_at − created_at` | READY |
| U-REL-03 | عدد/نسبة الطلبات المعادة | same | MA, RI, AG | subjects with ≥1 `changes_requested` ÷ submitted | READY |
| U-REL-04 | نسبة التقديم قبل موعد القفل | same | MA | first `submitted` ≤ `lock_at` | PARTIAL (lock is an edit lock; needs business confirmation) |
| U-REL-05 | زمن إدخال ما بعد التنفيذ (يوم) | relations_officer, volunteer_coordinator | MA | first `post_execution_submitted` − `actual_date` | PARTIAL (no SLA) |
| U-REL-06 | نسبة إعادة ما بعد التنفيذ | same | MA | activities with `monthly_activity_approvals.step='post_execution_review'` and decision in clarification/rejected ÷ submitted | READY |
| U-REL-07 | نسبة التصحيح بعد المتابعة | relations_officer | RI | iftars with ≥1 `monitoring_returned` ÷ monitored | READY |
| U-REL-08 | دقة بيانات ما بعد التنفيذ | relations_officer (creator) | MA | `post_execution_verifications.status='correct'` ÷ resolved (correct + incorrect) on own activities | READY |
| U-REL-09 | متوسط تقييم الأنشطة المنفذة | relations_officer (creator) | MA | AVG(`activity_evaluations.normalized_score`) on own activities, **respecting evaluation visibility** | READY |
| U-REL-10 | سرعة إغلاق الإفطار | relations_officer | RI | `closed_at − actual_date` (own) | PARTIAL (no SLA) |
| **Follow-up / Monitoring** |||||
| U-FUP-01 | عدد بنود التحقق المنجزة | followup_officer | MA | `post_execution_verifications.verified_by = user` and status ≠ pending | READY |
| U-FUP-02 | زمن التحقق (يوم) | followup_officer | MA | MAX(`verified_at`) − `closed` action log per activity | PARTIAL (re-verify overwrites; history in `audit_logs`) |
| U-FUP-03 | بنود تحقق غير منجزة ضمن نطاقي | followup_officer | MA | pending rows + closed activities with 0 rows, branches = `scopedBranchIds()` | READY (backlog; attributed by scope, not by actor) |
| U-FUP-04 | عدد التقييمات المنجزة | followup_officer | MA | `activity_evaluations.evaluated_by = user` | READY |
| U-FUP-05 | زمن التقييم بعد الإغلاق (يوم) | followup_officer | MA | `activity_evaluations.submitted_at` − `closed` log | READY |
| U-FUP-06 | نسبة اكتمال التقييم ضمن نطاقي | followup_officer | MA | evaluated ÷ closed (scope) | READY |
| U-FUP-07 | تقارير متابعة الإفطار المقدمة | followup_officer | RI | `monitoring_reports.monitor_user_id = user`, status submitted/approved | READY |
| U-FUP-08 | زمن تقديم تقرير المتابعة (يوم) | followup_officer | RI | first `monitoring_verification_submitted` log − `execution_completed` log | PARTIAL |
| U-FUP-09 | نسبة اعتماد تقارير المتابعة من أول مرة | followup_officer | RI | reports approved without a `monitoring_returned` log ÷ approved | READY |

## 7. Branch KPI Catalogue

Cross-module branch KPIs (`B-X`) are built from module KPIs. They are reported per branch for the branch comparison chart.

| Code | الاسم | Built from | Readiness |
|---|---|---|---|
| B-X-01 | حجم الفعاليات حسب الفرع والنوع | MA-01 + RI-01 + BZ-01 (+ AG participations) | READY |
| B-X-02 | نسبة التنفيذ الموحدة | executed ÷ planned across MA, RI, BZ (shown per module, plus a total) | READY |
| B-X-03 | الاعتماد من أول تقديم | MA-07 + RI-15 complement | READY |
| B-X-04 | تحقيق الحضور | MA-17 + RI-07 (weighted by expected) | READY |
| B-X-05 | اكتمال احتياجات التنفيذ | RI-13 (READY) + MA-20 (PARTIAL) + BZ-15 (MISSING, excluded) | PARTIAL |
| B-X-06 | اكتمال المتابعة والتقييم | MA-13, MA-15, RI-04 | READY |
| B-X-07 | زمن الإغلاق | MA-24, RI-12 (BZ excluded) | PARTIAL |
| B-X-08 | الالتزام بالأجندة | AG-07 per branch | PARTIAL |
| B-X-09 | الاعتماد على موارد الفرع (branch resource dependency) | `execution_needs_followup.status` secured vs not, `subject_execution_needs.availability` | PARTIAL. The spreadsheet's "who provided it" is not captured as a branch/HQ flag; see GAP-12 |

## 8. Annual Agenda KPI Catalogue

Annual Agenda **does not own execution, post-execution or evaluation**. Execution happens in linked Monthly rows (`monthly_activities.agenda_event_id`). AG-08 is therefore labelled cross-module.

| Code | الاسم | Formula (short) | Source | Dimensions | Drill-down | Readiness |
|---|---|---|---|---|---|---|
| AG-01 | عدد فعاليات الأجندة المخططة | COUNT live | `agenda_events` | year (`event_date`), month, `event_type` (إجباري/اختياري), `plan_type`, `owner_department_id`, `event_category_id` | event list | READY |
| AG-02 | نسبة الفعاليات المعتمدة | `status='published'` ÷ submitted (status ≠ draft) | `agenda_events.status` | same | event list + status | READY |
| AG-03 | نسبة الإعادة للتعديل | events with ≥1 `agenda_approvals.decision='changes_requested'` ÷ submitted | `agenda_approvals`, `workflow_instances.edit_request_count` | same + step | returns with comment and approver | READY |
| AG-04 | متوسط زمن الاعتماد (يوم) | `approved_by_executive_at` − first sub-step `workflow_logs.acted_at` | `agenda_events`, `workflow_logs` | step | per-step timeline | PARTIAL |
| AG-05 | طلبات تعديل/حذف الأجندة | COUNT by status; avg `decided_at − requested_at` | `annual_agenda_edit_requests`, `annual_agenda_delete_requests` | status, type | request list | READY (no `branch_id`: HQ only) |
| AG-06 | نسبة استجابة الفروع للمشاركة | branch participations with status ≠ `unspecified` ÷ expected branches | `agenda_participations`, `branches` | event, branch, event_type | branch × event matrix | PARTIAL |
| AG-07 | تحويل المشاركة إلى خطة شهرية | participant branches with a live linked monthly row ÷ participant branches | `agenda_participations` ⋈ `monthly_activities(agenda_event_id, branch_id)` | branch, month | missing-plan list | PARTIAL (mandatory sync auto-creates approved rows, even in draft) |
| AG-08 | تنفيذ فعاليات الأجندة (عبر الأنشطة الشهرية) | participant branches whose linked monthly row is executed ÷ participant branches with a date ≤ today | as AG-07 + Monthly executed definition | branch | executed/not list with planned/actual date | PARTIAL |
| AG-09 | الالتزام بنافذة التاريخ (±7 أيام) | non-unified participations with `ABS(proposed_date − event_date) ≤ 7` ÷ non-unified participations | `agenda_participations.proposed_date`, `agenda_events.event_date` | branch | out-of-window list | READY (validation already enforces it; mostly a check of data health) |

Not proposed: agenda post-execution, agenda evaluation, agenda attendance (all belong to Monthly).

## 9. Monthly Activities KPI Catalogue

The strongest coverage. Period = `proposed_date`; branch = `branch_id`; live-row rule from §5.3.

| Code | الاسم | Formula (short) | Source fields | Drill-down columns | Readiness |
|---|---|---|---|---|---|
| MA-01 | عدد الأنشطة الشهرية | COUNT live, split in-agenda / out-of-agenda / by status | `monthly_activities` | title, branch, proposed date, status, creator | READY |
| MA-02 | نسبة التنفيذ من المخطط | executed ÷ planned (date ≤ today) | status, execution_status | planned date, actual date, executed? | READY |
| MA-03 | نسبة التنفيذ في الموعد | executed with `actual_date ≤ COALESCE(rescheduled_date, proposed_date)` ÷ executed | `actual_date`, `proposed_date`, `rescheduled_date` | planned, actual, on-time?, delay days, responsible | PARTIAL (date only, no time) |
| MA-04 | متوسط التأخير في التنفيذ (يوم) | AVG(`actual_date − planned`) where > 0 | same | same | READY |
| MA-05 | نسبة الإلغاء/التأجيل | `execution_status IN (cancelled, postponed)` ÷ planned | `execution_status`, `cancellation_reason`, `reschedule_reason` | reason | READY |
| MA-06 | متوسط زمن الاعتماد (يوم) | final approval `workflow_logs.acted_at` − first `submitted` log | `workflow_logs`, `workflow_action_logs` | submitted, approved, days, steps | READY |
| MA-07 | الاعتماد من أول تقديم | approved with `edit_request_count = 0` ÷ approved | `workflow_instances` | returns count | READY |
| MA-08 | نسبة الإعادة/الرفض | ≥1 `changes_requested` (or rejected) ÷ submitted | `workflow_logs`, `meta.focus_areas` | step, approver, comment, focus areas | READY |
| MA-09 | طلبات التعديل والحذف بعد الاعتماد | COUNT; ÷ approved; avg `decided_at − requested_at` | `monthly_plan_edit_requests`, `monthly_plan_delete_requests` | request, status, requester, step | READY |
| MA-10 | نسبة استكمال ما بعد التنفيذ | with post-execution (status ∈ post_execution_submitted/closed/evaluated) ÷ executed-or-past-date approved | status, `post_execution_payload` | missing list | READY |
| MA-11 | زمن إدخال ما بعد التنفيذ (يوم) | first `post_execution_submitted` log − `actual_date` | `workflow_action_logs` | days | PARTIAL (no SLA) |
| MA-12 | نسبة إعادة ما بعد التنفيذ | clarification/rejected at `post_execution_review` ÷ post-exec submitted | `monthly_activity_approvals` | reviewer, comment | READY |
| MA-13 | نسبة اكتمال التحقق | resolved verification rows ÷ all rows (closed activities with 0 rows count as not started) | `post_execution_verifications` | pending items | READY |
| MA-14 | دقة بيانات ما بعد التنفيذ | `correct` ÷ (correct + incorrect) | same | field, original, corrected, note | READY |
| MA-15 | نسبة اكتمال التقييم | evaluated ÷ closed | `activity_evaluations` | awaiting list | READY |
| MA-16 | متوسط تقييم الأنشطة (من 10) | AVG(`normalized_score`) | `activity_evaluations` | score, form, evaluator (visibility-aware) | READY |
| MA-17 | تحقيق الحضور | Σ`actual_attendance` ÷ Σ`COALESCE(expected_attendance_to, expected_attendance)` | attendance columns (`attendance_percentage` per row) | expected, actual, % | READY |
| MA-18 | تغطية الفئات المستهدفة | planned groups per activity (READY); reached vs planned (MISSING) | `event_target_group` | groups | PARTIAL: show planned distribution only |
| MA-19 | تأمين احتياجات التنفيذ قبل الفعالية | `execution_needs_followup[].status='secured'` ÷ enabled needs | JSON | need, status, reason | PARTIAL (JSON, no time or actor id) |
| MA-20 | توفر احتياجات التنفيذ فعلياً | `post_status='provided'` ÷ rows with post_status | JSON | need, feedback | PARTIAL |
| MA-21 | إنجاز مهام فريق العمل | `teams[].all_members_attended` rate (proxy) | `post_execution_payload.teams` | team, planned, actual | PARTIAL. Task-level completion is MISSING for Monthly |
| MA-22 | تنفيذ فقرات البرنامج | `ceremony_items[].was_implemented` ÷ items | `post_execution_payload.ceremony_items` | item, feedback | PARTIAL |
| MA-23 | عدد المشكلات التشغيلية | — | no field | — | **MISSING** (inactive) |
| MA-24 | زمن الإغلاق بعد التنفيذ (يوم) | `closed` log − `actual_date` | `workflow_action_logs` | days | PARTIAL (no SLA) |
| MA-25 | الالتزام بموعد التخطيط | first `submitted` ≤ `lock_at` | `lock_at` | lock, submitted | PARTIAL |

## 10. Ramadan KPI Catalogue

Period = `ramadan_period_id` (fallback `planned_date` in period range). Live-row rule from §5.3.

| Code | الاسم | Formula (short) | Source | Readiness |
|---|---|---|---|---|
| RI-01 | عدد الإفطارات المخططة | COUNT live | `ramadan_iftars` | READY |
| RI-02 | نسبة الاعتماد | `status='approved'` ÷ submitted | `status`, `submitted_at` | READY |
| RI-03 | نسبة التنفيذ | `execution_status='completed'` ÷ approved | `execution_status` | READY |
| RI-04 | نسبة المتابعة | iftars with report submitted/approved ÷ executed | `monitoring_reports` | READY |
| RI-05 | نسبة الإغلاق | `closed_at` not null ÷ executed | `closed_at` | READY |
| RI-06 | تحقيق الوجبات | Σ`actual_meals_count` ÷ Σ`planned_meals_count`; line level via `ramadan_iftar_meals` | meals | READY (item level MISSING) |
| RI-07 | تحقيق الحضور | Σ`actual_attendance` ÷ Σ`expected_attendance` | iftar | READY |
| RI-08 | تغطية الفئات المستهدفة | attended attendees per `target_group_id` ÷ `event_target_group.planned_count` | attendees, event_target_group | READY |
| RI-09 | نسبة التطابق في المتابعة | `matched` ÷ (matched + mismatched) | `post_execution_verifications.match_status` via `monitoring_report_id` | READY (latest snapshot only) |
| RI-10 | نسبة عدم التطابق | `mismatched` ÷ total, by `detail_type` | same | READY (latest snapshot only) |
| RI-11 | نسبة التصحيح | iftars with ≥1 `monitoring_returned` ÷ monitored | `workflow_action_logs` | READY |
| RI-12 | زمن الإغلاق (يوم) | `closed_at − actual_date` | iftar | PARTIAL (no SLA) |
| RI-13 | اكتمال احتياجات التنفيذ | `subject_execution_needs.status='completed'` ÷ required | needs (`subject_type='ramadan_iftar'`) | READY |
| RI-14 | متوسط زمن الاعتماد (يوم) | `approved_at − submitted_at` | iftar | READY (includes correction time) |
| RI-15 | نسبة الإعادة للتعديل | `edit_request_count > 0` ÷ submitted | `workflow_instances` | READY |
| RI-16 | طلبات التغيير | COUNT by status; approval rate; avg `reviewed_at − created_at` | `ramadan_iftar_change_requests` | READY |
| RI-17 | فرق تاريخ التنفيذ (يوم) | `actual_date − planned_date` | iftar | READY |
| RI-18 | تحقيق الهدايا/الدروع | Σactual ÷ Σplanned by `gift_type` | `ramadan_iftar_gifts` | READY |
| RI-19 | تنفيذ فقرات البرنامج | completed ÷ (completed + cancelled + planned) | `ramadan_iftar_program_segments.execution_status` | READY |
| RI-20 | تحقيق فرق العمل والمتطوعين | actual ÷ planned members/volunteers; `task_completed` rate | `execution_teams`, `execution_team_members`, `subject_volunteer_requirements` | READY |
| RI-21 | الالتزام بالإرشادات المعتمدة | iftars on the current `guidance_version_id` ÷ submitted | `ramadan_iftars.guidance_version_id`, `event_guidance_versions` | PARTIAL (no user denominator) |

## 11. Bazaar KPI Catalogue

Period = `bazaar_date`. Drill-down is per table (`bazaar_tables`).

| Code | الاسم | Formula (short) | Source | Readiness |
|---|---|---|---|---|
| BZ-01 | عدد البازارات المخططة | COUNT where status ≠ draft (rejected shown separately) | `bazaars` | READY |
| BZ-02 | نسبة تنفيذ البازارات | executed ÷ approved-or-later | `status` | READY |
| BZ-03 | نسبة إشغال الطاولات | Σ`actual_occupied_table_count` ÷ Σ`planned_table_count` (executed only) | `bazaars` / `bazaar_tables.was_booked` | READY |
| BZ-04 | ثبات المستأجر | `actual_renter_matches=1` ÷ booked | `bazaar_tables` | READY (the form defaults to "match": flagged in the Guide) |
| BZ-05 | تطابق المعروضات | `material_matches=1` ÷ booked with a non-null flag | `bazaar_tables` | READY (nullable; defaults to "match") |
| BZ-06 | نسبة التحصيل | Σ`amount_paid` ÷ Σ`amount_due` where `was_booked=1` | `bazaar_tables` | PARTIAL (`payment_status` is free-chosen and inconsistent; derive the state from the amounts) |
| BZ-07 | طاولات غير مسددة/جزئية | COUNT booked where paid < due; outstanding Σ(due − paid) | same | PARTIAL |
| BZ-08 | طلبات الخصم | COUNT by status | `bazaar_table_discounts` | READY |
| BZ-09 | نسبة اعتماد الخصم | approved ÷ decided | same | READY |
| BZ-10 | قيمة الخصومات الممنوحة | Σ(`original_amount − final_amount`) approved | same | READY |
| BZ-11 | الإيراد المحصل مقابل المخطط | Σ`amount_paid` ÷ Σ`planned_rental_amount` (booked) | `bazaar_tables` | PARTIAL (unbooked tables also carry `amount_due`) |
| BZ-12 | زمن الاعتماد | `approved_at − submitted_at` | `bazaars` | PARTIAL (`submitted_at` overwritten on resubmit) |
| BZ-13 | زمن التحقق بعد التنفيذ | `verified_at − post_execution_submitted_at` | `bazaars` | PARTIAL (`post_execution_submitted_at` reset by non-complete saves) |
| BZ-14 | زمن إغلاق البازار | — | no `closed_at` | **MISSING** |
| BZ-15 | اكتمال احتياجات التنفيذ | — | needs never updated after planning | **MISSING** (code gap) |
| BZ-16 | نسبة الإعادة | — | no status history | **MISSING** |

## 12. KPI Formula / Target Matrix

`Op` = comparison against the target. All targets are **proposed** and need sign-off. `—` = informational only (no achieved/not badge).

| Code | Formula | Unit | Op | Proposed target |
|---|---|---|---|---|
| U-APR-01 | COUNT human decisions | count | — | — |
| U-APR-02 | AVG(decision − entry) | hours | <= | 48 |
| U-APR-03 | decisions ≤ target ÷ decisions ×100 | % | >= | 85 |
| U-APR-04 | returns ÷ decisions ×100 | % | — | — |
| U-APR-05 | AVG(post-exec decision − last submission) | hours | <= | 72 |
| U-APR-06 | AVG(review − submission) | hours | <= | 48 |
| U-APR-07 | COUNT decided discounts | count | — | — |
| U-REL-01 | first-pass approved ÷ approved ×100 | % | >= | 80 |
| U-REL-02 | AVG(first submit − created) | days | <= | 3 |
| U-REL-03 | returned ÷ submitted ×100 | % | <= | 20 |
| U-REL-04 | submitted ≤ lock_at ÷ submitted ×100 | % | >= | 95 |
| U-REL-05 | AVG(first post-exec submit − actual_date) | days | <= | 3 |
| U-REL-06 | post-exec returned ÷ post-exec submitted ×100 | % | <= | 15 |
| U-REL-07 | corrected ÷ monitored ×100 | % | <= | 15 |
| U-REL-08 | correct ÷ resolved ×100 | % | >= | 95 |
| U-REL-09 | AVG(normalized_score) | score /10 | >= | 7 |
| U-REL-10 | AVG(closed_at − actual_date) | days | <= | 7 |
| U-FUP-01 | COUNT resolved items | count | — | — |
| U-FUP-02 | AVG(last verify − closed) | days | <= | 5 |
| U-FUP-03 | COUNT pending | count | <= | 0 |
| U-FUP-04 | COUNT evaluations | count | — | — |
| U-FUP-05 | AVG(evaluation − closed) | days | <= | 7 |
| U-FUP-06 | evaluated ÷ closed ×100 | % | >= | 90 |
| U-FUP-07 | COUNT reports | count | — | — |
| U-FUP-08 | AVG(report − execution completed) | days | <= | 3 |
| U-FUP-09 | first-time approved ÷ approved ×100 | % | >= | 85 |
| AG-01 | COUNT | count | — | — |
| AG-02 | published ÷ submitted ×100 | % | >= | 90 |
| AG-03 | returned ÷ submitted ×100 | % | <= | 20 |
| AG-04 | AVG(final approval − submit) | days | <= | 7 |
| AG-05 | COUNT; AVG decision days | count / days | — / <= | — / 5 |
| AG-06 | answered ÷ expected ×100 | % | >= | 100 (mandatory) |
| AG-07 | with plan ÷ participant ×100 | % | >= | 95 |
| AG-08 | executed ÷ participant due ×100 | % | >= | 90 |
| AG-09 | in window ÷ non-unified ×100 | % | = | 100 |
| MA-01 | COUNT | count | — | — |
| MA-02 | executed ÷ planned ×100 | % | >= | 90 |
| MA-03 | on-time ÷ executed ×100 | % | >= | 85 |
| MA-04 | AVG delay days | days | <= | 2 |
| MA-05 | cancelled+postponed ÷ planned ×100 | % | <= | 10 |
| MA-06 | AVG approval days | days | <= | 5 |
| MA-07 | first-pass ÷ approved ×100 | % | >= | 80 |
| MA-08 | returned ÷ submitted ×100 | % | <= | 20 |
| MA-09 | requests ÷ approved ×100 | % | <= | 10 |
| MA-10 | with post-exec ÷ due ×100 | % | >= | 95 |
| MA-11 | AVG days | days | <= | 3 |
| MA-12 | returned ÷ submitted ×100 | % | <= | 15 |
| MA-13 | resolved ÷ total ×100 | % | >= | 95 |
| MA-14 | correct ÷ resolved ×100 | % | >= | 95 |
| MA-15 | evaluated ÷ closed ×100 | % | >= | 90 |
| MA-16 | AVG score | score /10 | >= | 7 |
| MA-17 | Σactual ÷ Σexpected ×100 | % | >= | 80 |
| MA-19 | secured ÷ enabled ×100 | % | >= | 90 |
| MA-20 | provided ÷ assessed ×100 | % | >= | 90 |
| MA-21 | full-attendance teams ÷ teams ×100 | % | >= | 90 |
| MA-22 | implemented ÷ items ×100 | % | >= | 90 |
| MA-24 | AVG days | days | <= | 7 |
| MA-25 | before lock ÷ submitted ×100 | % | >= | 95 |
| RI-02/03/04/05 | stage ÷ previous stage ×100 | % | >= | 95 / 95 / 100 / 100 |
| RI-06 / RI-07 | Σactual ÷ Σplanned ×100 | % | >= | 90 / 80 |
| RI-08 | attended ÷ planned per group ×100 | % | >= | 80 |
| RI-09 | matched ÷ assessed ×100 | % | >= | 90 |
| RI-10 | mismatched ÷ total ×100 | % | <= | 10 |
| RI-11 | corrected ÷ monitored ×100 | % | <= | 15 |
| RI-12 | AVG days | days | <= | 7 |
| RI-13 | completed ÷ required ×100 | % | = | 100 |
| RI-14 | AVG days | days | <= | 5 |
| RI-15 | returned ÷ submitted ×100 | % | <= | 20 |
| RI-16 | COUNT; approval rate | count / % | — | — |
| RI-17 | AVG days | days | <= | 0 |
| RI-18 / RI-19 / RI-20 | actual ÷ planned ×100 | % | >= | 90 |
| RI-21 | current version ÷ submitted ×100 | % | = | 100 |
| BZ-02 | executed ÷ approved ×100 | % | >= | 95 |
| BZ-03 | occupied ÷ planned ×100 | % | >= | 85 |
| BZ-04 / BZ-05 | matches ÷ booked ×100 | % | >= | 90 |
| BZ-06 | Σpaid ÷ Σdue ×100 | % | >= | 100 |
| BZ-07 | COUNT; Σoutstanding | count / currency | <= | 0 |
| BZ-08 / BZ-10 | COUNT / Σ | count / currency | — | — |
| BZ-09 | approved ÷ decided ×100 | % | — | — |
| BZ-11 | Σpaid ÷ Σplanned ×100 | % | >= | 90 |
| BZ-12 / BZ-13 | AVG days | days | <= | 3 / 3 |

**Score (Phase 13).** The only existing weighted score is `activity_evaluations.normalized_score`:
- each answer is normalised to 1–10: `((score − min) / (max − min)) × 9 + 1`;
- the result is weighted by question weight (`ActivityEvaluationService.php:52-98`).

Unused Site Settings (40% satisfaction / 60% commitment) suggest a *branch monthly score* was intended. "Satisfaction" is ambiguous: it could be `audience_satisfaction_percent` or `normalized_score`. **No universal user score is proposed.** If the business confirms the branch score:
- `BranchMonthlyScore = w_s × MA-16×10 (or AVG audience_satisfaction_percent) + w_c × MA-02`;
- the weights come from the existing settings;
- the component scores are always shown.

---

## 13. Data Source Mapping

### 13.1 Live-subject scopes (to be implemented once, in `Kpi/Queries/LiveSubjects`)

| Module | Table | Period column | Branch column | Live filter | Version filter |
|---|---|---|---|---|---|
| Agenda | `agenda_events` | `event_date` (+ `month`) | none (branch via `agenda_participations.entity_id` where `entity_type='branch'`) | `deleted_at IS NULL`, `is_archived=0` | `status <> 'archived'` |
| Monthly | `monthly_activities` | `proposed_date` | `branch_id` | `deleted_at IS NULL`, `is_archived=0` | no newer row via `previous_version_id` / `parent_version_id` |
| Ramadan | `ramadan_iftars` | `ramadan_period_id` / `planned_date` | `branch_id` | `deleted_at IS NULL` | no child via `parent_version_id` |
| Bazaar | `bazaars` | `bazaar_date` | `branch_id` | — | — |

### 13.2 Actor sources (user family)

| Action | Table.column (actor) | Time |
|---|---|---|
| Plan decision (MA / AG / RI) | `workflow_logs.acted_by` | `workflow_logs.acted_at` |
| Submission (MA) | `workflow_action_logs.performed_by` (`action_type='submitted'`) | `performed_at` |
| Submission (RI) | `ramadan_iftars.relations_officer_id` / `created_by` | `submitted_at` (first) |
| Submission (AG) | `workflow_logs.acted_by` on step `agenda_relations_officer_submit` | `acted_at` |
| Post-exec submit (MA) | `workflow_action_logs` `post_execution_submitted` | `performed_at` |
| Post-exec decision (MA) | `monthly_activity_approvals.approved_by` (returns); `workflow_action_logs` `closed` (approval) | `approved_at` / `performed_at` |
| Verification (MA) | `post_execution_verifications.verified_by` | `verified_at` (latest only; history in `audit_logs` `post_execution_verified`) |
| Evaluation (MA) | `activity_evaluations.evaluated_by` | `submitted_at` |
| Monitoring (RI) | `monitoring_reports.monitor_user_id` | `submitted_at` (latest) + action logs |
| Monitoring review / close (RI) | `workflow_action_logs` `monitoring_approved/returned`, `iftar_closed` | `performed_at` |
| Bazaar decisions | `bazaars.approved_by`, `verified_by`; `bazaar_table_discounts.approved_by` | `approved_at`, `verified_at` (approve only) |

### 13.3 Workflow timing algorithm (`Kpi/Queries/WorkflowTiming`)

For each workflow instance:
1. Order its `workflow_logs` by `acted_at, id`.
2. **Entry time** of log *L* is the previous log's `acted_at`. For the first log, and for the first log after a `changes_requested`, it is the latest `workflow_action_logs.submitted.performed_at` that is ≤ `L.acted_at`.
3. **Decision time** = `L.acted_at − entry`, attributed to `L.acted_by` and `L.workflow_step_id`.
4. Exclude auto-approval rows. **Never** use `workflow_instances.started_at`.

The algorithm runs only over instances in the requested period, aggregated in SQL with window functions (MySQL 8 `LAG()`), or in a bounded chunked PHP pass if the DB version lacks them. *(Check the DB version before Phase 4.)*

### 13.4 Reference spreadsheet ↔ system field mapping

| Spreadsheet column (`docs/خطة الفعاليات الشهرية المراكز تعديل.xlsx`, `docs/اجندة زها نهائي.xlsx`) | System field | KPI |
|---|---|---|
| التاريخ المقترح / التاريخ الفعلي / تاريخ التعديل | `proposed_date` / `actual_date` / `rescheduled_date` | MA-03, MA-04 |
| العدد المتوقع / العدد الفعلي للحضور | `expected_attendance(_to)` / `actual_attendance` | MA-17 |
| هل الفعالية ضمن أجندة زها | `is_in_agenda` / `is_from_agenda` | MA-01 split |
| اعتماد … (ضابط/مدير العلاقات/البرامج) | workflow steps | MA-06…08, U-APR-* |
| وجود المستلزمات … ومن قام بتوفيره | `event_supplies.available`, `provider_type` | B-X-09 (PARTIAL) |
| فريق العمل + مسؤوليته | `execution_team_members.role_desc/task_description` | MA-21 (task completion MISSING) |
| الفئة المستهدفة | `event_target_group` | MA-18 |
| اجباري / اختياري؛ موحد / غير موحد؛ أعمدة الفروع | `event_type`, `plan_type`, `agenda_participations` | AG-06…09 |

`docs/1000424752.png` is a transport-trip flowchart, so it is not a KPI source.

## 14. Data Gap Matrix

| KPI | Module | Required Fields | Existing Support | Gap | Action |
|---|---|---|---|---|---|
| U-APR-02/03 | MA/AG/RI | step entry + decision time | `workflow_logs.acted_at`, submitted action logs | no `assigned_at` | Derive via §13.3; no schema change. Optional later: index `workflow_logs(workflow_instance_id, acted_at)` |
| U-APR-08 decision accuracy | all | ground truth for a "correct" decision | none | MISSING | **Do not build.** Requires a business definition |
| U-REL-04 / MA-25 | MA | planning deadline | `lock_at` (edit lock) | rule not confirmed as a KPI deadline | Business decision; config only |
| U-REL-05 / MA-11 / MA-24 / RI-12 / U-REL-10 | MA/RI | SLA days | none | no SLA anywhere | Add `config/kpis.php` SLA defaults + Setting overrides; **no schema** |
| MA-03 | MA | actual time | `actual_date` only | no actual time | Accept date-level. Capture actual time only if the business requires it |
| MA-18 | MA | planned/actual count per target group | columns `planned_count`, `actual_count` exist but are not written for Monthly; new rows lack `subject_type/subject_id` | capture gap (code) | Phase 4: write `subject_type/subject_id` and counts in the Monthly form and post-execution. **No migration** |
| MA-19/20 | MA | per-need status, actor, time | JSON `execution_needs_followup` (no time, no id) | PARTIAL | Read JSON for now. Later: move Monthly to `subject_execution_needs.status/completed_at` (already used by Ramadan) |
| MA-21 / RI-20 task | MA | per-member `task_completed` | column exists; Ramadan only | capture gap (code) | Phase 4: capture in Monthly post-execution |
| MA-23 | MA (+all) | operational issue records | none (proxies in feedback text) | MISSING | Propose only on business request: an `event_operational_issues` (subject_type/id, category, severity, resolved_at) table. **Inactive until then** |
| MA-24 | MA | close time | `workflow_action_logs` `closed` | no column | Derive; optional index `workflow_action_logs(entity_id, action_type, performed_at)` |
| MA-13 | MA | verification rows exist when activity closed | rows created lazily | "not started" ≠ "0 rows" | Treat closed + 0 rows as not started (query rule) |
| RI-09/10 | RI | mismatch history before correction | rows overwritten (`syncVerifications` 353-375) | history lost | Accept the latest snapshot. Snapshot table only if the business needs first-pass match rate |
| RI-06 item level | RI | actual qty per meal item | planned only | MISSING | Not needed for headline KPI; skip |
| RI-21 | RI | eligible-user denominator | acknowledgements per user | PARTIAL | Use per-iftar compliance only |
| AG-04 | AG | submitted time | sub-step `workflow_logs` | no column | Derive |
| AG-06/07 | AG | confirmation flag/time, required-branch list | `participation_status`, `updated_by/at` | PARTIAL | Accept proxies; optional `confirmed_at/confirmed_by` (additive) if confirmation timeliness is wanted |
| AG-07/08 | AG | lineage after agenda delete | `agenda_event_id` nulled on delete | history lost | Code change (keep the link or log it) — separate decision |
| AG ↔ RI link | AG/RI | `ramadan_iftars.agenda_event_id` | column exists, never written (not fillable) | MISSING | Out of KPI scope; note only |
| BZ-14 | BZ | `closed_at`, `closed_by` | none | MISSING | **DONE (Phase 4):** additive migration `2026_10_19_000100` + set in `BazaarController::close`; historical closures remain NULL |
| BZ-15 | BZ | needs fulfilment | `subject_execution_needs` exists; never updated in the bazaar flow | capture gap (code) | Phase 5: add needs actuals to bazaar execution (reuse the Ramadan pattern) |
| BZ-16 / BZ-12 | BZ | decision history | `submitted_at` overwritten; no status log | MISSING | Write `workflow_action_logs` rows in `BazaarController` (no schema) |
| BZ-06/07/11 | BZ | consistent payment state | `amount_due/paid`, `payment_status`, `is_paid` | inconsistent | Derive the state from amounts; fix the controller so `approved_discount` doesn't overwrite payment state |
| D7 | BZ | `invitation_type`, `private_audience`, `day_count` on `bazaars` | on `bazaar_tables` | schema bug | **FIXED in Phase 0.5.** The staging-only Bazaar migrations (newer than the protected cutoff `2026_09_10_000100`) were corrected directly; existing staging DBs use the SQL in §0.2 |
| D8 | all | audit entity id | `entity_id=0` | bug | **FIXED in Phase 0.5** (`getKey()` for bound models + `audit_entity` attribute for create routes) |
| Performance | MA/AG/WF | indexes | see §21 | missing | Additive index migration (Phase 9, or earlier if Phase 4 queries need them) |

## 15. Authorization Matrix

Existing permissions are reused: `kpi.view`, `kpi.manage`, `reports.view`, `branches.view.all`, `branches.view.own`, `scopedBranchIds()`.

**Proposed new permissions** (granted through a new additive seeder; role IDs are never hard-coded):
- `kpi.users.view` — view other users' performance within scope.
- `kpi.export` — export drill-downs.

| Role | Own KPIs (My Profile) | Branch / module KPIs | Scope | Other users' KPIs | Export | Targets/weights | Gap vs today |
|---|---|---|---|---|---|---|---|
| super_admin | ✓ | ✓ | all (Gate::before) | ✓ | ✓ | ✓ | — |
| executive_manager | ✓ | ✓ (`kpi.view`) | all (`branches.view.all`) | ✓ (grant `kpi.users.view`) | ✓ | ✓ (`kpi.manage`) | lacks `bazaars.view`: Bazaar KPI aggregates are gated by `kpi.view`; links to bazaar records are shown only if `can('bazaars.view')` |
| relations_manager | ✓ | ✓ | all | ✓ | ✓ | — | today the reports sidebar is hidden from them (`layouts/app.blade.php:246`) → show the new group |
| programs_manager | ✓ | ✓ | all | — (decision) | ✓ | — | — |
| supervisor | ✓ | ✓ for own branch(es) | `scopedBranchIds()` | ✓ for users of own branch(es) | ✓ | — | **has no `kpi.view` today**: grant `kpi.view` + `kpi.users.view` |
| branch_coordinator | ✓ | ✓ for assigned branches (decision) | scoped | — | — | — | decision |
| followup_officer | ✓ | ✓ verification/evaluation/monitoring KPIs | **today has `branches.view.all`, yet its dashboard scopes by `scopedBranchIds()`** → confirm | — | ✓ | — (drop `kpi.manage`; manual KPI entry is retired) | add the group to the exclusive follow-up sidebar (D9) |
| evaluation_officer / evaluation_followup_viewer | ✓ | read-only MA-13…16 | `evaluation.view_all` | — | — | — | — |
| relations_officer | ✓ own only | — | — | — | — | — | — |
| reports_viewer | ✓ | ✓ read-only | **no branch-scope permission today** → `scopedBranchIds()` falls back to `branch_id` | — | ✓ | — | **decision needed**: org-wide or own branch |
| workshops_secretary, communication_head | ✓ | currently hold `kpi.view` → decide whether they keep it | — | — | — | — | review |
| finance_officer | ✓ | — (keeps `reports.view` for finance reports) | — | — | — | — | — |
| staff / others | ✓ own (if any applicable KPI) | — | — | — | — | — | — |

Enforcement:
- A `KpiScope` service is resolved per request: `branch_ids` = all when super_admin or `branches.view.all`, else `scopedBranchIds()`; `user_ids` for `kpi.users.view` = users whose `branch_id` or assigned branches intersect `branch_ids`.
- Every calculator and drill-down applies `whereIn('branch_id', $scope->branchIds)`.
- Routes use `permission:` middleware plus `branch.isolation`.
- A user profile view authorizes through a `UserPerformancePolicy@view`.
- Evaluation scores shown inside drill-downs respect `MonthlyActivityEvaluationPolicy@view` and the visibility rules.

## 16. Sidebar / Navigation Proposal

The group goes in the live sidebar, `resources/views/layouts/app.blade.php`, using the existing collapse pattern (like `ramadanAdminMenu`). Add a `$canAccessKpiSection = auth()->user()->can('kpi.view') || hasRole('super_admin')` flag in the top `@php` block. Replace the current headless reports block (`:247-261`). **Also add the group to the follow-up exclusive menu (`:123-133`).**

```
<li class="side-comment">مؤشرات الأداء والتقارير</li>
▸ مؤشرات الأداء والتقارير             (fa-chart-line)       #kpiMenu
   ├ نظرة عامة                        kpi.overview            (fa-gauge-high)
   ├ مؤشرات أداء المستخدمين           kpi.users.index          (fa-user-check)     @can('kpi.users.view')
   ├ مؤشرات أداء الفروع               kpi.branches.index       (fa-code-branch)
   ├ الأجندة السنوية                   kpi.modules.show agenda  (fa-calendar-days)
   ├ الأنشطة الشهرية                   kpi.modules.show monthly (fa-calendar-check)
   ├ الإفطارات الرمضانية               kpi.modules.show ramadan (fa-moon)
   ├ البازارات                         kpi.modules.show bazaar  (fa-store)
   ├ التقارير التفصيلية                kpi.reports.index         (fa-table-list)
   └ دليل مؤشرات الأداء                kpi.guide                 (fa-book-open)      (all auth users)
▸ تقارير تشغيلية (finance / maintenance / transport — existing pages, unchanged)
```

- Route prefix: `dashboard/kpi`, name prefix `kpi.`.
- Middleware: `auth`, `permission:kpi.view` (the guide is auth only), `branch.isolation`.
- During Phases 3–5 the old links stay under a temporary collapsed "التقارير السابقة" sub-group, and are removed once each replacement is signed off.
- Add "دليل مؤشرات الأداء" next to `site-guide.index` (`:132`, `:143`).
- Add "أدائي" as a tab inside My Profile, not as a sidebar item.

## 17. My Profile KPI Proposal

- **Where:**
  - New "الأداء" tab/section in `resources/views/profile/show.blade.php` (self).
  - A new route `kpi.users.show {user}` (policy-protected) that renders the **same partial** for managers.
  - `role.super_admin.users.show` embeds the same partial.
  - `UserDirectoryController` rows link to it only when `can('kpi.users.view')` and the user is in scope.
- **Content:**
  - Period selector (month / quarter / year / custom) with the previous-period comparison.
  - Scorecards: actual, target, achieved/not, trend arrow, numerator/denominator.
  - A 6-month sparkline per KPI.
  - "Recent history" table: the last 10 subjects contributing to each KPI, each linking to the drill-down.
- **Role-to-KPI mapping**, via `applies_to_roles` (a user with several roles sees the union, grouped by role):

  | Role | KPIs shown |
  |---|---|
  | relations_officer | U-REL-01…10 |
  | supervisor | U-APR-01…07 + U-REL-01…03 if they create plans |
  | branch_coordinator, relations_manager, executive_manager | U-APR-01…04 |
  | followup_officer | U-FUP-01…09 |
  | Roles with no applicable KPI | a short "لا توجد مؤشرات أداء مرتبطة بدورك حالياً" message, not an empty grid |

- **Replaces:** `ProfileController::buildStats/buildEvaluations`. Their "completed" uses the wrong status, and the evaluation figures come from legacy responses. Keep the 15-minute cache pattern, keyed by user + period.

## 18. KPI Guide Proposal

`دليل مؤشرات الأداء`:
- route `kpi.guide` (auth only);
- controller `KpiGuideController`;
- view `resources/views/pages/help/kpi-guide.blade.php`;
- CSS/JS cloned from `public/assets/css/site-role-guide.css` and `public/assets/js/site-role-guide.js` (search, filter, modal, `[data-theme="dark"]` tokens, RTL, reduced-motion).

**Content is generated from `config/kpis.php`.** There is no second copy of the formulas. Each KPI card shows:

| Guide field | Source |
|---|---|
| اسم المؤشر | `name_ar` |
| الهدف | `target` + operator, rendered "≥ 90%" with "مقترح" if `target_status=proposed` |
| ماذا يقيس؟ | `description_ar` |
| لمن يطبق؟ | `family` / `applies_to_roles` → Arabic role names (`Role::display_name`) |
| على أي Module؟ | `modules` |
| طريقة الاحتساب | visual formula card: numerator / denominator boxes (from `numerator`, `denominator`) |
| مثال رقمي | `guide.example` (e.g. "18 منفذة ÷ 20 معتمدة = 90%") |
| المستهدف | target |
| مصدر البيانات | `source` (Arabic description of the screen where the data is entered, not table names; tables shown only to super_admin as in the Site Role Guide) |
| متى يتم تحديثه | `refresh` → "فوري" / "كل 15 دقيقة" / "شهرياً" |
| كيف أقرأ النتيجة؟ | `guide.read` |
| ماذا يعني عدم تحقيق المستهدف؟ | `guide.not_met` (with suggested action) |
| جاهزية البيانات | readiness badge; PARTIAL shows its caveat, MISSING shows "غير مفعّل" |

Layout:
- hero;
- sticky navigator with search, family tabs (المستخدمين / الفروع) and module chips;
- KPI grid;
- a lifecycle diagram per module (planned → approved → executed → post-execution → verified → evaluated / closed), with each KPI pinned to its stage, built as inline SVG or Mermaid (Mermaid is already loaded via ESM elsewhere).

## 19. Charts / Analytics Proposal

- **Library:** Chart.js, the one library already used by app pages. Fix the CDN reference to a pinned version (`https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js`) and load it once per KPI page. Do **not** adopt the bundled ApexCharts theme assets (no app page uses them).
- **Theming:** read the CSS custom properties at render time; re-render on theme toggle; RTL axis direction; Arabic number formatting.
- **Charts, each tied to a decision and linked to a drill-down:**

| Chart | Where | KPIs | Click-through |
|---|---|---|---|
| Target vs actual (bullet / horizontal bar) | Overview, module pages | any % KPI | KPI drill-down |
| Monthly trend (12 periods, line) with target line | Overview, module, profile sparkline | MA-02, MA-17, RI-07, BZ-03, U-* | month → drill-down filtered to that month |
| Branch comparison (sorted horizontal bar) | Branch KPIs | B-X-*, MA-02, MA-16 | branch → branch-filtered drill-down |
| User comparison (bar, within scope) | User KPIs (`kpi.users.view`) | U-APR-02, U-REL-01, U-FUP-06 | user → profile performance |
| Lifecycle funnel (planned → approved → executed → post-exec → verified → evaluated/closed) | Monthly, Ramadan, Bazaar | MA-01/02/10/13/15, RI-01…05, BZ-01/02 | stage → list at that stage |
| Approval turnaround by step (bar) | Monthly, Agenda, Ramadan | MA-06, AG-04, RI-14, U-APR-02 | step → slowest items |
| Status distribution (stacked bar, not pie) | module pages | status counts | status → list |
| Attendance planned vs actual (scatter or paired bar) | Monthly, Ramadan | MA-17, RI-07 | point → activity |
| Bazaar table occupancy & collection | Bazaar | BZ-03, BZ-06 | bazaar → per-table drill-down |

No decorative charts (vanity "most active branch", pies of totals) are planned. The CSS-bar charts on the Evaluation dashboard can stay until that page is migrated.

## 20. Recommended Implementation Phases

Each phase is a separate PR, reviewable and mergeable on its own.

| Phase | Content | Key files | Exit criteria |
|---|---|---|---|
| **0.5 (separate bug-fix PR) — source complete** | D1, D2, D3, D7 (staging Bazaar migrations corrected + staging SQL), D8. D10 deferred to Phase 2 (sidebar) | `MonthlyKpisController`, `kpis.blade.php`, `charts-scripts.blade.php`, Bazaar migrations `000100`/`000200`, `TrackUserOperations`, `BazaarController` | existing screens work; no KPI work mixed in |
| **1 (this doc)** | Audit, catalogue, gap matrix | `docs/kpi-reporting-refactor-phase1-audit.md` | business sign-off on targets, SLAs, open decisions (§21) |
| **2** | Retire dead code and hide deprecated UI only: orphan `roles/super_admin/reports.blade.php` + JS; dead `layouts/dashboard` sidebar chain; hide orphan launcher routes. **No live report removed yet** | views, assets | `grep` proves no references; existing tests pass |
| **3 — foundation complete (see `kpi-reporting-refactor-phase3-foundation.md`)** | `config/kpis.php`, `app/Services/Kpi/*` skeleton (Registry, Period, Scope, TargetResolver, LiveSubjects, WorkflowTiming), ~~permissions seeder (`kpi.users.view`, `kpi.export`, grant `kpi.view` to supervisor)~~ *deferred: no new permissions until the business decisions are made*, `dashboard/kpi` routes, sidebar group, Overview page (cards + filters + trend) | new files + sidebar | Overview renders READY KPIs for MA-01/02, RI-01, BZ-01, AG-01 with scope |
| **4 — complete (see `kpi-reporting-refactor-phase4-module-branch-kpis.md`; AG/RI/BZ were pulled forward from Phase 5)** | Monthly calculators MA-01…25; Branch KPIs page; Monthly module page; Monthly target-group / team capture (code only); hide Admin Reports relations/enterprise tabs, Enterprise pages, Reports overview/agenda/monthly, manual KPI page (read-only legacy) | calculators, views, Monthly form/post-execution | numbers reconcile against the old Admin Reports for a sample month (documented diffs explained by the canonical definitions) |
| **5** | Agenda AG-*, Ramadan RI-*, Bazaar BZ-* calculators and pages; Bazaar `closed_at/closed_by` additive migration + action logging + needs actuals | calculators, BazaarController | each module page complete |
| **6** | User performance: U-* calculators, profile partial, `kpi.users.show`, policy, directory links; remove the old profile stats | ProfileController, views | each role sees only its KPIs |
| **7** | Drill-down report engine (`kpi.reports.show {code}`), CSV export (shared from `EnterpriseReportsController::csvResponse`, add a UTF-8 BOM for Excel), print view; Chart.js charts with click-through; remove fake exports & orphan enterprise routes; migrate change-request report tests | DrillDown queries, export | every card links to rows; exports respect scope |
| **8** | KPI Guide | guide controller/view/CSS/JS | guide generated from config |
| **9** | Additive index migration, query profiling, caching, optional monthly snapshot table for closed months, delete remaining deprecated code (launcher dashboards, dead computations, legacy enterprise service) | migration, services | p95 KPI page ≤ agreed budget |

## 21. Risks / Compatibility Notes

1. **Protected migrations.** Every schema change above is a **new additive** migration. No migration before `2026_09_10_000100_add_event_applicability_to_target_groups_table` may be edited. The D7 fix corrected only the staging-only Bazaar migrations dated `2026_10_03`.
2. **Number drift.** The new canonical "executed" and "approved" definitions will not match old screens. Publish a reconciliation note in Phase 4 before hiding old pages.
3. **Mixed `entity_type` values.** Every log query must use `EventAggregateIdentity::acceptedTypes()`, or counts will be silently halved.
4. **Version duplication.** Apply the live-row rules everywhere, or approved edit requests will double count.
5. **Lazy `workflow_instances.started_at`.** Never use it. The existing Admin Reports "approval speed" is wrong for this reason.
6. **Evaluation visibility.** Score-bearing drill-downs and the profile must use the existing visibility policy.
7. **`Cache::flush()`** in `SiteSettingsController.php:72` wipes all KPI caches. Use tagged/prefixed keys, and accept or replace the flush.
8. **Branch scope inconsistencies.** reports_viewer has no scope permission; followup_officer has `branches.view.all` but works branch-scoped. These need decisions before Phase 3.
9. **Bazaar permissions.** Management roles lack `bazaars.view`. KPI aggregates must not require it, while record links must.
10. **Tests referencing old routes.** These must be updated, not deleted: `tests/Feature/AdminReportsMonthlyExecutionStatusTest.php`, `MonthlyActivityPhaseZeroEndpointsTest.php`, `MonthlyActivityControllerRouteContractTest.php`, `EventsPhaseZeroRouteContractTest.php`, `ActivityEvaluationWorkflowTest.php`.
11. **`monthly_kpis` historical data.** Keep the table; hide the entry form and show old rows read-only labelled "إدخال يدوي سابق".
12. **Index gaps.** These will hurt KPI queries:
    - `monthly_activities` (`branch_id, proposed_date`), (`status`), (`execution_status`), (`deleted_at`)
    - `agenda_events` (`status`), (`deleted_at`)
    - `workflow_action_logs` (`entity_id, action_type, performed_at`)
    - `workflow_logs` (`workflow_instance_id, acted_at`)
    - `workflow_instances` (`status`)
    - `monthly_plan_*_requests` (`branch_id, requested_at`)
    - `ramadan_iftars` (`closed_at`)
13. **Query safety.** Every calculator takes a mandatory `KpiPeriod`:
    - aggregates use `selectRaw` with `COUNT/SUM/AVG`, never `->get()->count()`;
    - JSON KPIs (MA-19…22) must use MySQL `JSON_TABLE` or a bounded chunked pass;
    - cache current-period results for 15 minutes;
    - closed past months may be snapshotted (Phase 9).
14. **No export packages.** Excel = UTF-8-BOM CSV; PDF = the print stylesheet. Adding maatwebsite/excel or dompdf is a separate approval.
15. **No business targets or SLAs exist.** Until they are approved, targets show as "مقترح".

### Open decisions for the business (block Phase 3)

1. Approve or adjust the proposed targets (§12) and the SLA defaults.
2. Is `lock_at` an acceptable planning deadline (U-REL-04 / MA-25)?
3. Confirm "داخل الخطة" = agenda-linked.
4. Branch monthly score: is it wanted? Which "satisfaction" input? Use the 40/60 weights?
5. reports_viewer scope: org-wide or own branch? followup_officer: all or assigned branches?
6. Which roles get `kpi.users.view` (supervisor for their branch; executive / relations manager org-wide)?
7. Is the operational-issues capture (MA-23) wanted?
8. Should workshops_secretary and communication_head keep `kpi.view`?

## 22. Files Audited

- **Routes / config:**
  - `routes/web.php`
  - `config/admin_reports.php`, `config/site_role_guide.php`, `config/workflows.php`, `config/roles.php`, `config/cache.php`
  - `composer.json`, `composer.lock`, `package.json`
- **Kernel / providers / middleware / policies:**
  - `app/Http/Kernel.php`
  - `app/Providers/AuthServiceProvider.php`, `AppServiceProvider.php`
  - `app/Http/Middleware/EnforceBranchIsolation.php`, `TrackUserOperations.php`
  - `app/Policies/MonthlyActivityEvaluationPolicy.php`
  - `app/Console/Kernel.php`, `routes/console.php`
- **Controllers:**
  - `DashboardController`, `ProfileController`, `UserDirectoryController`, `SiteRoleGuideController`
  - `Roles/*/DashboardController` (all), `Roles/SuperAdmin/{ReportsController, SiteSettingsController, EvaluationAssignmentsController}`, `Roles/FollowupOfficer/FollowupWorkspaceController`
  - `Web/Reports/*` (7)
  - `Web/Enterprise/{EnterpriseDashboardController, EnterpriseReportsController, ArchiveController}`
  - `Web/Evaluation/*` (3)
  - `Web/Access/UsersController`
  - `Web/Agenda/AgendaEventsController`, `AgendaApprovalsController`
  - `Modules/Events/Http/Controllers/MonthlyActivities/*`, `Ramadan/*`, `Bazaar/BazaarController`
- **Services:**
  - `AdminReports/AdminReportsService`, `EnterpriseAnalyticsService`, `ActivityEvaluationService`
  - `DynamicWorkflowService`, `MonthlyActivityLifecycleService`, `MonthlyActivityWorkflowService`, `MonthlyWorkflowPresenter`, `PlanChangeRequestWorkflowService`, `AgendaWorkflowBridgeService`, `AgendaWorkflowPresenter`, `WorkflowGovernanceService`
  - `Modules/Events/Services/RamadanIftar*Service`, `RamadanGuidanceAcceptanceService`, `RamadanPeriod*`
- **Models:**
  - `app/Models/{User, Role, MonthlyKpi, ActivityEvaluation*, EvaluationForm, EvaluationQuestion, ActivityAttendance, AuditLog, Workflow*, Branch, Center, Setting}`
  - `app/Modules/Events/Models/*` (all Agenda, Monthly, Ramadan, Bazaar and shared-foundation models)
  - `app/Modules/Events/Support/*Identity`
- **Requests:** `StoreBazaarRequest`, `StoreRamadanIftarRequest`, Ramadan monitoring requests.
- **Migrations:**
  - agenda: `2024_02_01_010300`, `010400`, `010500`, `2026_02_13_000002`, `2026_04_07_130000`, `2026_06_12_000001`
  - monthly: `2024_02_01_010600`, `011000`, `011100`, `2026_02_13_000004`, `2026_03_16_000091..93`, `2026_03_19_120004`, `2026_05_05_010000`
  - workflow: `2026_02_13_0000062`, `2026_03_31_000200..203`
  - KPI / centers: `2026_02_13_000009`, `2026_04_02_120000`
  - evaluation: `2026_07_25_000100`, `2026_07_26_190500`
  - Ramadan: `2026_09_10_000800..001800`, `2026_09_11_000100..000400`, `2026_09_13_000100`, `2026_09_14_000100/000200`, `2026_09_15_000100`, `2026_09_16_000100/000200`, `2026_09_20_000100/000200`, `2026_09_21_000100`
  - Bazaar: `2026_10_03_000050..000300`
- **Seeders:** `DatabaseSeeder`, `RolePermissionSeeder`, `RolesSeeder`, `BazaarRolePermissionSeeder`, `EvaluationWorkflowPermissionSeeder`, `EvaluationWorkflowAccessSeeder`, `MonthlyKpiSeeder`, `WorkflowSeeder`, `EventStatusLookupSeeder`, `SettingSeeder`, `EventTypeSeeder`, `EventCategorySeeder`.
- **Views:**
  - `dashboard`
  - `layouts/app`, `layouts/app/sidebar`, `layouts/dashboard`
  - `roles/*/dashboard`, `roles/partials/dashboard-actions`, `roles/super_admin/reports`, `roles/followup_officer/*`
  - `pages/admin/reports/**`, `pages/admin/site-settings/index`
  - `pages/reports/**`, `pages/enterprise/**`, `pages/evaluation/**`
  - `pages/monthly_activities/reports/**`, `pages/monthly_activities/approvals/**`
  - `pages/events/bazaars/**`, `pages/events/ramadan/**`
  - `pages/help/site-role-guide`, `profile/show`, `directory/users`
- **Assets:**
  - `public/assets/js/pages/{pages-enterprise-charts, pages-admin-reports-index, roles-super_admin-reports}.js`
  - `public/assets/js/site-role-guide.js`, `public/assets/css/site-role-guide.css`
  - `public/assets/vendors/js/apexcharts.min.js` (presence only)
- **Lang:** `resources/lang/ar/{app, evaluation, bazaars}.php`.
- **Docs:**
  - `docs/follow-up-monitoring-audit.md`, `bazaar-schema-lifecycle-audit.md`, `monthly-execution-needs-normalization-audit.md`, `monthly-monitoring-normalization-audit.md`, `monthly-activity-identity-compatibility.md`, `evaluation-workflow.md`, `ramadan-*.md`, `events-*.md`
  - reference spreadsheets `خطة الفعاليات الشهرية المراكز تعديل.xlsx`, `اجندة زها نهائي.xlsx`; image `1000424752.png`
- **Tests (grep):** the five listed in §21.

## 23. Final Status

KPI & REPORTING REFACTOR PHASE 1 AUDIT COMPLETE

All ten Phase 1 deliverables are in this document. Nothing was deleted, migrated or modified outside this file. Phase 3 is blocked only on the business decisions listed in §21.
