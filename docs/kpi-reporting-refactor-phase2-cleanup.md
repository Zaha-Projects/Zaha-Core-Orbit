# KPI & Reporting Refactor — Phase 2 Cleanup

**Date:** 2026-10-19
**Base:** `3919fae` (Phase 0.5 committed)
**Authority:** `docs/kpi-reporting-refactor-phase1-audit.md` (Keep / Migrate / Retire classification)
**Scope:** remove source-proven dead reporting code and retire misleading or replaced KPI/report UI. No new KPI system, no KPI formulas, no targets, no migrations. Static checks only; **no runtime tests executed**.

Deletion rule used: delete only what Phase 1 classified as **dead code**, or what Phase 2's instructions name explicitly (orphan launcher dashboards). Items Phase 1 classified **replaced** are *retired*: navigation is removed, routes redirect, and the code is kept with `@deprecated` until Phase 9.

## 1. Removed (source-proven dead)

Before each deletion, a grep of `app`, `resources`, `routes`, `tests`, `config` and `public/assets/js` confirmed no remaining reference.

| Item | Files | Evidence |
|---|---|---|
| Orphan Super Admin reports page | `resources/views/roles/super_admin/reports.blade.php`, `public/assets/js/pages/roles-super_admin-reports(.min).js` | No controller renders the view; the JS is only loaded by it |
| Dead `layouts.dashboard` chain | `resources/views/layouts/dashboard.blade.php`, `layouts/app/{sidebar,header,page-header,footer}.blade.php`, `pages/{access,agenda,finance,maintenance,monthly_activities,reports,transport}/partials/sidebar.blade.php`, `public/assets/css/pages/layouts-app-{sidebar,header}(.min).css`, `public/assets/js/pages/layouts-dashboard(.min).js` | No view extends `layouts.dashboard`; every file is reachable only through it. `layouts/app/partials/*` is used by the live layout and is **kept** |
| Mermaid loader on Admin Reports "operations" tab | `public/assets/js/pages/pages-admin-reports-index(.min).js` and its `@push('scripts')` | The tab contains no `.mermaid` blocks; the only Mermaid blocks were in the deleted orphan view |
| Admin Reports "enterprise" tab | `resources/views/pages/admin/reports/partials/enterprise.blade.php`; tab removed from `config/admin_reports.php`; `ReportsController` no longer calls `EnterpriseAnalyticsService` | Charts never rendered (D4). Duplicates the retired Enterprise dashboard. `?tab=enterprise` falls back to the default tab (existing controller guard) |
| Never-rendered computations | `AdminReportsService::zahaTime()`, `dailyOperationLogs()`, `userDelayStats()`, the `payload_rows` model load; `by_branch` in `MonthlyActivityApprovalQueueController::monthlyChangeRequestStats()` | Only the deleted orphan view rendered them. `by_branch` had no reader. The three `audit_logs` aggregates ran on every Admin Reports request |
| Orphan launcher dashboards (7) | Controllers `Roles/{SuperAdmin,RelationsManager,RelationsOfficer,ProgramsManager,ProgramsOfficer,ReportsViewer,Staff}/DashboardController.php`; views `roles/{super_admin,relations_manager,relations_officer,programs_manager,programs_officer,reports_viewer,staff}/dashboard.blade.php` | No `route()` reference anywhere. Their "KPI" tiles only counted links. Routes kept as redirects (§4) |
| Fake exports (6) | POST `role.reports.{export,agenda.export,monthly.export,finance.export,maintenance.export,transport.export}`, their `export()` methods and forms | Each only redirected back with "تم تجهيز ملف التقرير."; no file was produced. Real exports arrive in Phase 7 |

## 2. Hidden / deprecated (kept in code until Phase 9)

| Item | What changed | Why it is kept |
|---|---|---|
| Enterprise Analytics (`EnterpriseDashboardController`, `EnterpriseAnalyticsService`, `pages/enterprise/**`, `pages/reports/enterprise/branch-performance.blade.php`, `pages-enterprise-charts(.min).js`, `enterprise-dashboard(.min).css`) | Sidebar links removed; routes redirect; classes marked `@deprecated` | Phase 1 class: *replaced* (KPI overview, Phase 3). The pinned Chart.js 4.4.1 include and chart partials stay available until the Phase 3 KPI pages adopt Chart.js |
| Agenda / Monthly report pages (`AgendaReportsController`, `MonthlyReportsController`, `pages/reports/{agenda,monthly}.blade.php`) | Routes redirect; sidebar links removed; fake export forms stripped so the kept views do not reference removed routes | Phase 1 class: *replaced* by the AG/MA drill-downs (Phase 7) |
| Manual Monthly KPIs (`MonthlyKpisController`, `MonthlyKpi`, `monthly_kpis`) | Sidebar link removed. Entry form and `POST role.reports.kpis.store` removed. Headline "latest value" cards and the "آخر تحديث" badge removed. The page stays reachable by URL as a read-only history table with an "إدخال يدوي سابق" banner. `store()` is kept but marked `@deprecated` | Historical rows preserved; **table not dropped, no rows deleted**. Replaced by calculated KPIs in Phase 4 |
| Admin Reports approval-speed panel | Panel removed from the relations tab | The metric uses the lazily set `workflow_instances.started_at` (Phase 1 §0.6). The computation `relationsReport()['approval_speed']` is **kept** because `AgendaEventIdentityCompatibilityTest` and `EventRequestWorkflowIdentityCompatibilityTest` use it as the legacy/canonical identity-duplicate guard; it moves with those tests in Phase 4 |

## 3. Misleading metrics removed from live pages

| Page | Removed | Reason (Phase 1) |
|---|---|---|
| `/dashboard` navigation cards | Number shown in each card (`$card['value'] ?? $loop->iteration`) | The number was the card's position in the list, not a metric |
| `/dashboard` calendar stats | "الأكثر مشاركة" pill and its calculation | Vanity metric. The remaining pills count exactly the records shown in the calendar, so they are calendar context, not KPIs |
| My Profile | Stat strip (created / branch / "completed" / workflow actions), its explanation note, the legacy evaluations card, and the 15-minute stat caches | "completed" used `status='completed'`, which Monthly never writes. Evaluations came from legacy `monthly_activity_evaluation_responses`. Replaced by a neutral note until Phase 6 |
| Admin user view (`role.super_admin.users.show`) | Same four stats (its "completed" used `completed`/`closed`) | Same reason. The notifications count and recent-activities list are kept |
| Admin Reports overview | "المراكز" row | Hard-coded `0`; centers were dropped |

## 4. Redirects

Every retired GET route is a named `Route::redirect` (302, `RedirectController`, compatible with `route:cache`). Route names are kept so bookmarks and stale `route()` calls still resolve. Each URI is used by exactly one route.

| URI | Route name | Redirects to |
|---|---|---|
| `/dashboard/reports/agenda` | `role.reports.agenda.index` | `/dashboard/reports/overview` |
| `/dashboard/reports/monthly` | `role.reports.monthly.index` | `/dashboard/reports/overview` |
| `/dashboard/enterprise` | `role.enterprise.dashboard` | `/dashboard/reports/overview` |
| `/dashboard/enterprise/annual-planning` | `role.enterprise.annual_planning` | `/dashboard/reports/overview` |
| `/dashboard/reports/enterprise/branch-performance` | `role.reports.enterprise.branch_performance` | `/dashboard/reports/overview` |
| `/dashboard/reports` | `role.reports_viewer.dashboard` | `/dashboard/reports/overview` |
| `/dashboard/admin` | `role.super_admin.dashboard` | `/dashboard` |
| `/dashboard/relations/manager` | `role.relations_manager.dashboard` | `/dashboard` |
| `/dashboard/relations/officer` | `role.relations_officer.dashboard` | `/dashboard` |
| `/dashboard/programs/manager` | `role.programs_manager.dashboard` | `/dashboard` |
| `/dashboard/programs/officer` | `role.programs_officer.dashboard` | `/dashboard` |
| `/dashboard/staff` | `role.staff.dashboard` | `/dashboard` |

Removed outright (POST only, nothing to bookmark): the six fake `*.export` routes and `role.reports.kpis.store`.

## 5. Preserved

- **Operational reports (KEEP), grouped as "تقارير تشغيلية":**
  - `role.reports.index` is now an operational-reports hub that links the three reports. The fake filter/export form is gone. The `app.roles.reports.title`/`subtitle` labels now read "تقارير تشغيلية", which also relabels the sidebar item and the `/dashboard` card.
  - Finance (`role.reports.finance.index`), Maintenance (`role.reports.maintenance.index`) and Transport (`role.reports.transport.index`) are kept. Only their fake export forms were removed.
- **Operational queues:**
  - approval-queue KPI strip and admin change-request counters;
  - monthly browse summary cards and returned-feedback badges;
  - Follow-up dashboard and queues;
  - Ramadan dashboard block and monitoring queues;
  - communication-head cards.
- **Evaluation workflow:** Evaluation dashboard, forms, `ActivityEvaluationService` (weighted score), and `PostExecutionVerification`.
- **Admin Reports** (super_admin): overview, relations (per-branch table, agenda-by-status) and operations tabs. These are replaced in Phase 4.
- **Enterprise CSV exports and printable view** (`EnterpriseReportsController`): real but orphaned. `csvResponse()` is the starting point for Phase 7 exports.
- **Change-request reports** (`MonthlyActivityReportsController`): orphan, but covered by route-contract tests. Migrates in Phase 7.
- **Year archive** (`ArchiveController`): an administrative action, not reporting.

## 6. Branch isolation (KEEP reports)

| Report | Data | Rule applied |
|---|---|---|
| Finance | `bookings`, `zaha_time_bookings` (have `branch_id`); `donations_cash` (no branch column) | Booking counts are limited to `scopedBranchIds()` when `isBranchScopedPlanningUser()`. Donations are organisation-level and stay a global count. Counts now use `count()` instead of loading every row (`->get()->count()`) |
| Maintenance | `maintenance_requests` (`branch_id`) | Limited to `scopedBranchIds()` when `isBranchScopedPlanningUser()` |
| Transport | `trips` (no branch column) | Organisation-level fleet data; no branch scope applies |

`isBranchScopedPlanningUser()` is the same predicate `EnforceBranchIsolation` uses. It restricts users who hold `branches.view.own` without `branches.view.all` (and are not main-branch users). It deliberately **does not** change today's reports_viewer access, because reports_viewer branch scope is an open Phase 3 business decision.

## 7. Sidebar

- **Reports block** (`layouts/app.blade.php`): now only the hub plus the three operational reports. Removed links: agenda report, monthly report, KPIs, Enterprise Analytics, branch performance. Removed flags: `$canAccessReportsSidebar`, `$canAccessKpiPage`.
- **D10 fixed:** the "تقارير الإدارة" link is shown only to super_admin, matching its `role:super_admin` route.
- The new "مؤشرات الأداء والتقارير" group is **not** added. That is Phase 3.

## 8. Intentionally postponed

| Item | Phase |
|---|---|
| New KPI architecture, sidebar group, overview | 3 |
| Admin Reports relations tab (7-way "executed" OR) and `approval_speed` computation + its tests | 4 |
| Deleting the deprecated Enterprise, Agenda/Monthly report and manual-KPI code | 9 |
| Real CSV/print exports; retiring the orphan enterprise export routes; change-request report migration | 7 |
| User performance panel on My Profile and admin user view | 6 |
| Follow-up officer sidebar KPI/report links (D9) | 3 |
| reports_viewer scope, targets, deadlines, 5-day lock, 40/60 score, `kpi.users.view`, operational-issues capture | Business decisions (block Phase 3) |
| Other create routes passing `audit_entity` (D8 follow-up) | As touched |

## 9. Static checks

- `php8.2 -l` on every changed PHP file: pass. The default CLI is 7.4, which cannot parse the existing PHP 8 syntax in this codebase.
- Changed Blade views: each compiled with `Illuminate\View\Compilers\BladeCompiler::compileString()` (no app boot) and the output linted with `php8.2 -l`: 14/14 pass.
- `node --check`: no JS file was changed (JS changes are deletions only).
- `git diff --check`: clean.
- Stale-reference greps for every removed route, view, asset, class and view variable: none outside the deprecated code listed in §2.

**NO RUNTIME TESTS EXECUTED**

## Phase 3 status (2026-10-19)

The KPI section (`/dashboard/kpi`, sidebar group "مؤشرات الأداء والتقارير") replaces the retired analytical entry points. None of the Phase 2 retirements were reverted. The deprecated Enterprise chart partial now loads Chart.js through the shared `layouts/app/partials/chartjs.blade.php`. Details: `docs/kpi-reporting-refactor-phase3-foundation.md`.
