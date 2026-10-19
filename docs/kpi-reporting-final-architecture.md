# KPI & Reporting — Final Architecture (Phase 6)

Status: Phases 1–6 complete (2026-10-19). Static checks only; no runtime tests were executed.
Predecessor documents: `docs/kpi-reporting-refactor-phase1-audit.md` … `phase5-user-performance-reports.md`.

## 1. Overview

Every KPI is **calculated** from operational tables. No KPI value is entered by hand.
The single source of truth is `config/kpis.php`. Screens, reports, CSV exports and the KPI Guide all read the same definitions.

```
config/kpis.php ──► KpiRegistry ──► KpiCalculatorService (datasets, cached) ──► KpiEvaluator ──► KpiResult
                         │                    ▲                                         │
                         │            KpiMetricSource builders                          ▼
                         │            (module lifecycle + actor sources)       Blade partials / kpi-dashboard.js
                         ├──► KpiReportService (row reports + CSV)
                         ├──► KpiUserPerformanceService (RO/SV/FO/MG)
                         └──► KpiGuideService (دليل مؤشرات الأداء)
KpiScope: authorization + branch scope for every query (never cached).
```

## 2. Configuration (`config/kpis.php`)

- **`modules`, `branch_modules`, `stages`:** event types (agenda, monthly, ramadan, bazaar) and lifecycle stages.
- **`categories`, `statuses`:** display taxonomy. Data statuses are READY / PARTIAL / UNAVAILABLE / NOT_APPLICABLE.
- **`user_families`:** RO, SV, FO and MG. Each family lists its roles, primary metric, turnaround, comparison and reports.
- **`cache`:** `enabled` (`KPI_CACHE_ENABLED`, default true), `ttl_minutes` (`KPI_CACHE_TTL_MINUTES`, default 10) and `prefix`.
- **`metric_labels`:** Arabic labels for the calculator metric names. The guide uses them for numerators and denominators.
- **`definitions`:** 127 KPIs. The families are EV, AG, MA (MA-10 inactive), RI, BZ, BR, RO, SV, FO and MG. Each definition has:
  - code, aliases, `name_ar`, `description_ar`, icon, page, category, unit, operator;
  - calculator (sum / ratio / per_month / snapshot);
  - per-module availability;
  - `not_applicable` and `unavailable` reasons;
  - limitations and drill-down;
  - `target` (always null; see §12) and `proposed_target`, which is informational only and never evaluated.

## 3. Services (`app/Services/Kpi`)

| Service | Responsibility |
|---|---|
| `KpiRegistry` | Reads and normalises the definitions, modules and categories |
| `KpiCalculatorService` | Builds aggregate datasets. Groupings: period (current vs previous), branch, month or user. Wraps datasets in a cache. |
| `KpiMetricSource` | Describes one data source: base query, `dateExpression`, scope, branch key, metrics, actor, rows |
| `KpiEvaluator` / `KpiResult` | Applies the calculator to a dataset and returns value, status, comparison and reason |
| `KpiReportService` | Paginated detail reports (on-time, attendance, ramadan-meals, bazaar-occupancy) and CSV rows |
| `KpiUserPerformanceService` | Per-user KPIs and action lists for the RO, SV, FO and MG families |
| `KpiGuideService` | Builds the KPI Guide from the definitions and the real metric sources |
| `KpiScope` | `canAccess`, `canViewUsers`, `canViewUser`, `canExport`, `forSelf`, `hasOversight`, `allowedBranchIds` |
| `KpiSql` | Portable SQL literals. Backslashes are doubled on MySQL/MariaDB. |

Rules enforced in code:

- Business timestamps come from each source's `dateExpression`. `updated_at` and `workflow_instances.started_at` are never used.
- `exclusive_with` prevents Agenda and Monthly from being counted twice.
- Missing data shows «بيانات غير متوفرة», never a fake zero.
- There are no formulas in Blade or JS. `kpi-dashboard.js` renders chart specs and `kpi-guide.js` filters cards; neither computes anything.

## 4. Routes

| URI | Name | Access |
|---|---|---|
| `/dashboard/kpi` | `kpi.overview` | `super_admin` or `kpi.view`, plus `branch.isolation` |
| `/dashboard/kpi/branches`, `/branches/{branch}` | `kpi.branches.index/show` | same |
| `/dashboard/kpi/events`, `/events/{module}` | `kpi.events.index/show` | same |
| `/dashboard/kpi/reports/{report}` (+`/export`) | `kpi.reports.show/export` | same; export also requires `kpi.export` |
| `/dashboard/kpi/users`, `/users/{user}` (+`/actions/{action}` (+`/export`)) | `kpi.users.*` | same, plus `kpi.users.view` checked in the controller |
| `/profile/performance/…` | `profile.performance.*` | the authenticated user's own data (`KpiScope::forSelf`) |
| `/dashboard/kpi/guide` | `kpi.guide` | any authenticated user. It shows definitions and no data. |
| `/dashboard/reports/overview`, `finance`, `maintenance`, `transport` | `role.reports.*` | operational reports (kept) |
| `/dashboard/reports/kpis` | `role.reports.kpis.index` | read-only legacy `monthly_kpis` history |

Retired URLs are named `Route::redirect`s, kept so bookmarks still work:

- `/dashboard/enterprise` → `/dashboard/kpi`
- `/dashboard/enterprise/annual-planning` → `/dashboard/kpi/events/agenda`
- `/dashboard/reports/enterprise/branch-performance` → `/dashboard/kpi/branches`
- agenda-export → `/events/agenda`
- monthly-export and approval-export → `/events/monthly`
- printable → `/dashboard/kpi`
- `/dashboard/reports/agenda` and `/monthly` → the operational reports hub

## 5. Permissions and authorization

| Permission | Granted to (by `KpiPermissionSeeder`, additive) | Purpose |
|---|---|---|
| `kpi.view` | existing role mapping (RolesSeeder) | Opens the KPI section |
| `kpi.manage` | existing mapping | Reserved; no write screens remain |
| `kpi.users.view` | super_admin, executive_manager, relations_manager | Cross-user KPI views |
| `kpi.export` | super_admin, executive_manager, relations_manager | CSV exports |

- `Gate::before` grants super_admin everything.
- Branch scope is always computed by `KpiScope::allowedBranchIds` and is part of every query and every cache key.
- The follow-up officer has no cross-user access.
- Seeders use only `firstOrCreate` and `givePermissionTo`. There is no `syncPermissions`, `truncate` or `delete`.

## 6. KPI pages

- **Overview:** EV cards, lifecycle stages and charts.
- **Branches:** BR-01…09 and 11…15, with branch comparison and branch drill-down.
- **Events:** per-module pages (AG, MA, RI, BZ).
- **Users:** the RO / SV / FO / MG families.
- **Profile «الأداء»:** the signed-in user's own performance.
- **Detail reports:** every report has CSV export, gated by `kpi.export`.
- **KPI Guide:** see §7.

## 7. KPI Guide «دليل مؤشرات الأداء»

The controller is `Web/Kpi/KpiGuideController`. It renders `KpiGuideService::build()` into `pages/kpi/guide.blade.php`, with the assets `kpi-guide.css` and `kpi-guide.js`.

For every active definition, the guide shows:

- code and aliases, Arabic name and description, group, category, family, roles, unit;
- formula, numerator and denominator;
- a worked example with illustrative numbers;
- target and proposed target;
- «كيف أقرأ هذا المؤشر؟», «لماذا قد يظهر غير متاح؟» and limitations;
- drill-down;
- «مصدر البيانات», per module: status, tables, timestamp expression and actor column. These come from the **actual** `KpiMetricSource` builders, so the guide cannot drift from the code.

UX:

- Search plus module, group, role, category and status filters, with a live result count and an empty state.
- Expand / collapse all.
- Native `<details>` cards, which are keyboard-accessible.
- Deep links via `#kpi-guide-<code>`.
- RTL and light/dark themes through the `.kpi-dashboard` tokens.
- Responsive layout (the filter bar stops being sticky on mobile) and `prefers-reduced-motion` support.

## 8. Caching

- **Cached:** `KpiCalculatorService::dataset()` aggregate datasets, via `Cache::remember`.
  - Key: `kpi:v1:dataset:` + md5 of (modules, grouping, period start and end, sorted branch scope, sorted metrics, sorted users, today's date).
  - TTL: `KPI_CACHE_TTL_MINUTES`, default 10 minutes, minimum 1.
- **Never cached:**
  - authorization and scope decisions (`KpiScope`);
  - single-user datasets (profile and user detail), which stay fresh;
  - paginated report rows and CSV streams;
  - the workflow workload snapshot;
  - the agenda category chart;
  - the KPI Guide, which is cheap and config-driven.
- **Why prefixed keys:** `SiteSettings` calls `Cache::flush()`. A flush only forces a recompute; there is no correctness risk. Disable the cache with `KPI_CACHE_ENABLED=false`.
- **Staleness:** values may lag writes by up to the TTL. This is acceptable for analytics, and it is documented on purpose.

## 9. Indexes (new migrations only)

| Migration | Index | Justification |
|---|---|---|
| `2026_10_19_000100_add_closure_capture_to_bazaars_table` | bazaar closure columns | BZ closure KPIs |
| `2026_10_19_000200_add_kpi_period_index_to_monthly_activities_table` | `proposed_date` period index | MA period filters |
| `2026_10_19_000300_add_kpi_actor_indexes` | `workflow_action_logs(module, action_type, performed_at)`, `workflow_logs(acted_by, acted_at)` | Action-log sources filter on module + action type + date range. Decision sources filter `acted_by IN` + an `acted_at` range, and no index existed for that. |

Not added, because they are already covered:

- `post_execution_verifications.verified_by` and `activity_evaluations.evaluated_by` already have foreign-key indexes.
- Bazaar and discount tables are small.

Validate with `EXPLAIN` on production-sized data.

## 10. Deprecated code removed (Phase 6)

- **Enterprise Analytics:**
  - `EnterpriseDashboardController`, `EnterpriseReportsController`, `EnterpriseAnalyticsService`;
  - `resources/views/pages/enterprise/*` and `pages/reports/enterprise/*`;
  - the `pages-enterprise-charts`, `enterprise-dashboard` and `pages-reports-enterprise-printable` JS/CSS (normal and `.min`).
- **Old report controllers:** `AgendaReportsController`, `MonthlyReportsController`, and the `reports/agenda` and `reports/monthly` views.
- **Manual monthly KPI write path:**
  - `MonthlyKpisController::store` and its route;
  - `MonthlyKpiSeeder`, which sample-wrote rows, and its call in DatabaseSeeder.
  - The `monthly_kpis` table and all its rows are **preserved** and still readable at `role.reports.kpis.index`.
- **Kept:**
  - the finance, maintenance and transport reports and the reports hub;
  - the `StreamsCsv` trait and the shared `layouts/app/partials/chartjs.blade.php`;
  - `ArchiveController`;
  - `AdminReportsService` (used by tests).

## 11. Known gaps

- No official targets exist, so no KPI is marked achieved or not achieved; they display "لا يوجد مستهدف".
- Operational-issues capture (MA-23, BR-15) does not exist, so those KPIs are UNAVAILABLE.
- PARTIAL KPIs list their limitations in the config and the guide.
- Cached values can lag writes by up to the TTL.

## 12. Open business decisions (OPEN — none decided)

1. Official targets for every KPI, including whether to adopt the `proposed_target` values (e.g. BR-09 ≥ 4).
2. SLA deadlines for approval and turnaround stages.
3. The 5-day planning rule (MA-25), and whether `lock_at` is an acceptable planning deadline.
4. Confirm that "داخل الخطة" means agenda-linked.
5. Branch monthly score: wanted? Which satisfaction input? Use the 40/60 weights?
6. An overall user score and its weights. Not implemented; there is no universal score.
7. Operational-issues capture (MA-23, BR-15).
8. Scope of reports_viewer: organisation-wide or own branch?
9. Scope of followup_officer: all branches or assigned branches?
10. Whether supervisors should get `kpi.users.view` for their own branch.
11. Whether workshops_secretary and communication_head keep `kpi.view`.

## 13. Deployment checklist (non-destructive)

1. Back up the database.
2. `git pull` the release, then run `composer install --no-dev --optimize-autoloader` (deployment only).
3. Run `php artisan migrate --pretend` and review it. Only the new `2026_10_19_*` migrations should appear.
4. Run `php artisan migrate --force`. This only adds columns and indexes. **Never** use migrate:fresh, refresh, reset or truncate.
5. Run `php artisan db:seed --class=KpiPermissionSeeder --force`. It is additive only.
6. Run `php artisan permission:cache-reset`.
7. Run `php artisan config:clear && php artisan config:cache`.
8. Run `php artisan route:clear && php artisan route:cache`.
9. Run `php artisan view:clear && php artisan view:cache`.
10. Optional `.env` settings: `KPI_CACHE_ENABLED=true` and `KPI_CACHE_TTL_MINUTES=10`. Re-cache config after changing them.
11. Check these pages as super_admin, as a branch-scoped manager and as an RO user:
    - `/dashboard/kpi`, `/dashboard/kpi/branches`, `/dashboard/kpi/events/agenda` (and `monthly`, `ramadan`, `bazaar`)
    - `/dashboard/kpi/users`, `/dashboard/kpi/reports/on-time` (and its CSV)
    - `/dashboard/kpi/guide`, `/profile`
    - the legacy redirects (`/dashboard/enterprise`)
12. Confirm that the `monthly_kpis` row count is unchanged.
