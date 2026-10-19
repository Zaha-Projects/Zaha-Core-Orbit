# KPI & Reporting Refactor — Phase 3 Foundation

**Date:** 2026-10-19
**Builds on:** `docs/kpi-reporting-refactor-phase1-audit.md`, `docs/kpi-reporting-refactor-phase2-cleanup.md`
**Scope:**
- KPI catalogue, shared lifecycle definitions, calculation services, `/dashboard/kpi` section, sidebar group, overview dashboard.
- A small set of cross-module KPIs to validate the architecture.
- No module KPI catalogues (Phases 4–5), no profile KPIs (6), no drill-downs or exports (7), no KPI Guide (8).
- No migrations and no KPI tables.

Static checks only; **no runtime tests executed**.

## 1. Architecture

```
config/kpis.php                         catalogue: modules, stages, KPI definitions (no SQL)
app/Services/Kpi/
  KpiRegistry.php                       reads the catalogue; instantiates module definitions
  KpiPeriod.php                         year or month; previous() = contiguous comparison period
  KpiScope.php                          canAccess(), oversight, branch scope, branch filter validation
  KpiTargetResolver.php                 target + source → configuration_required / achieved
  KpiFormatter.php                      value formatting; "بيانات غير متوفرة" for null
  KpiResult.php                         result contract (one per KPI)
  KpiCalculatorService.php              aggregates, KPI results, module summaries, chart payloads, branch table
  Modules/KpiModuleDefinition.php       shared lifecycle contract + cumulative stage composition
  Modules/{Agenda,MonthlyActivity,RamadanIftar,Bazaar}KpiDefinition.php
app/Http/Controllers/Web/Kpi/KpiDashboardController.php
resources/views/pages/kpi/{overview,branches,events,event,users}.blade.php + partials/
resources/views/layouts/app/partials/{kpi-navigation,chartjs}.blade.php
public/assets/css/kpi-dashboard.css, public/assets/js/pages/kpi-dashboard.js
```

Flow: controller validates the filters → `KpiPeriod` + `KpiScope` → `KpiCalculatorService::overview()` → `KpiResult[]`, module summaries and chart payloads → Blade renders the prepared values. Blade and JS contain no formulas.

## 2. Catalogue (`config/kpis.php`)

- **`modules`** — key → `label_ar`, `icon`, `accent`, and `definition` (the lifecycle definition class).
- **`stages`** — `planned`, `approved`, `executed`, `closed`, each with an Arabic label and description.
- **`definitions`** — keyed by code. Each definition has:
  - `name_ar`, `name_en`, `description_ar`
  - `family` (`user` | `branch` | `event`), `category`, `applicable_modules`
  - `unit` (`percentage` | `count` | `days` | `hours` | `score`)
  - `comparison_operator` (`>=` | `<=` | `=` | `null`)
  - `target`, `target_source` (`configured` | `proposed` | `none`)
  - `calculator`, `icon`, `drilldown` (`null` until Phase 7), `active`
  - `guide` (placeholder for Phase 8)
- **Calculator types:**
  - `['type' => 'stage_count', 'stage' => …]`
  - `['type' => 'stage_rate', 'numerator' => …, 'denominator' => …]`
- **Derived stages:** `approved_due` and `executed_due` are records of that stage whose date is on or before today.
- **Adding a KPI:** add a definition. Adding a new calculator type means adding a branch in `KpiCalculatorService::compute()`.

## 3. Result contract (`KpiResult`)

| Property | Meaning |
|---|---|
| `code`, `name`, `description`, `icon`, `unit` | from the catalogue |
| `value` / `formattedValue` | `null` → "بيانات غير متوفرة"; never a substitute 0 |
| `numerator`, `denominator` | raw counts (rates show "س من ص") |
| `target`, `formattedTarget`, `targetSource`, `configurationRequired` | from `KpiTargetResolver`; no target + operator → "لم يتم تحديد المستهدف" |
| `achieved` | `true` / `false` only for a **configured** target; otherwise `null` |
| `state` | `achieved` · `not_achieved` · `no_target` · `informational` (no operator) · `insufficient_data` |
| `period`, `comparisonPeriod` | Arabic labels |
| `trend` | `{direction, delta, formatted, previous}` or `null` |
| `dataAvailable`, `missingReason` | why the value is null |
| `notes` | e.g. "لا يشمل: الأجندة السنوية" |
| `breakdown` | per-module value (`لا ينطبق` + reason when the module does not own the stage) |
| `drilldown` | `{route, parameters}` or `null`. Cards render a link only when it is set |

## 4. Shared lifecycle definitions

`KpiModuleDefinition` is the **single** place that defines live / planned / approved / executed / closed for each module. Each module supplies raw conditions. The base class composes **cumulative** stages ("reached at least"), so they nest by construction:

- `planned = P`
- `approved = P AND (A OR E OR C)`
- `executed = P AND (E OR C)`
- `closed = P AND C`

| Module (period date) | Live record | Planned (P) | Approved (A) | Executed (E) | Closed (C) |
|---|---|---|---|---|---|
| Annual Agenda (`event_date`) | not soft-deleted, `is_archived = 0`, `status <> 'archived'` | `status <> 'draft'` | `status = 'published'` | **not owned** | **not owned** |
| Monthly Activities (`proposed_date`) | not soft-deleted, `is_archived = 0`, `status <> 'archived'`, no newer version (`previous_version_id` / `parent_version_id`) | `status <> 'draft'` | `status ∈ {approved, post_execution_submitted, closed, evaluated}` or `lifecycle_status ∈ {Approved, Scheduled, Executed, Evaluated, Closed}` | `execution_status = 'executed'` or `status ∈ {post_execution_submitted, closed, evaluated}` | `status ∈ {closed, evaluated}` |
| Ramadan Iftars (`planned_date`) | not soft-deleted, no child version (`parent_version_id`) | `status <> 'draft'` | `status = 'approved'` | `execution_status = 'completed'` | `closed_at IS NOT NULL` |
| Bazaars (`bazaar_date`) | all rows | `status <> 'draft'` | `status ∈ {approved, executing, post_execution, verified, completed}` | `status ∈ {post_execution, verified, completed}` | `status = 'completed'` |

- These replace the conflicting old definitions (Phase 1 §0.2). Workflows and statuses are unchanged; this is calculation normalisation only.
- **Versioning:** the newest version is the live record. A Monthly post-approval edit starts a draft revision, so the plan counts as a draft until that revision is approved.

## 5. Routes / controller

All routes are inside the `auth` group and use middleware `role_or_permission:super_admin|kpi.view` and `branch.isolation`.

| URI | Name | Page |
|---|---|---|
| `/dashboard/kpi` | `kpi.overview` | overview dashboard |
| `/dashboard/kpi/branches` | `kpi.branches.index` | stage counts per branch |
| `/dashboard/kpi/events` | `kpi.events.index` | module selector (module cards) |
| `/dashboard/kpi/events/{module}` | `kpi.events.show` | one module (`agenda` \| `monthly` \| `ramadan` \| `bazaar`) |
| `/dashboard/kpi/users` | `kpi.users.index` | "قريبًا" placeholder (Phase 6 / open decision) |

## 6. Navigation

- **Partial:** `layouts/app/partials/kpi-navigation.blade.php`. It renders a collapsible group "مؤشرات الأداء والتقارير" (`fa-chart-line`) containing:
  - نظرة عامة
  - مؤشرات الفروع
  - مؤشرات المستخدمين (placeholder page)
  - one item per module (الأجندة السنوية، الأنشطة الشهرية، الإفطارات الرمضانية، البازارات)
  - التقارير التشغيلية → `role.reports.index`
- **Visibility:** shown only when `KpiScope::canAccess($user)`.
  - **Main sidebar:** the group replaces the standalone operational-reports block for KPI users. Users without KPI access but with report access (e.g. finance_officer) keep the Phase 2 block. The "التقارير التشغيلية" item follows the Phase 2 rule: hidden for relations_manager.
  - **Follow-up sidebar:** the same partial (`kpiMenuId = kpiMenuFollowup`), so no sidebar markup is duplicated. It appears only with `kpi.view`.
- **Not restored:** Enterprise Analytics, the manual Monthly KPI page/editor, the agenda/monthly report pages, fake exports, profile stats.

## 7. Authorization

- **Access:** super_admin, or the existing `kpi.view` permission. Seeded holders: executive_manager, programs_manager, relations_manager, followup_officer, workshops_secretary, communication_head, reports_viewer.
- **No new permissions.** `kpi.users.view` is **not** added, and no role was granted anything new.
- `kpi.manage` is not used yet: no editable targets exist.

## 8. Branch scope (`KpiScope`)

- **Oversight** (super_admin or `branches.view.all`): all branches. The branch filter is optional.
- **Everyone else:** limited to `scopedBranchIds()`.
  - A `branch_id` outside that list → **403**. A nonexistent branch → 404.
  - A user with no scoped branch sees **no data** (an explicit notice), never global totals.
  - reports_viewer holds neither `branches.view.all` nor `branches.view.own`, so it is limited to its own branch or assigned branches. This is the safe default until the reports-viewer scope decision is made.
- **Agenda events have no `branch_id`.** A branch is in scope when it is a `participant` in `agenda_participations`. On the branch table an event is counted once per participating branch.
- `branch.isolation` middleware also runs on the KPI routes, so scoped single-branch users get their branch pre-selected.

## 9. Filters

- **Parameters:**
  - `year`: 2020 … next year; default is the current year.
  - `month`: 1–12, or empty for the whole year.
  - `branch_id`: positive integer, scope-checked.
  - `module`: on the overview and branches pages; must be one of the catalogue keys.
- **Validation:** all values go through `filter_var` with ranges; `Request::integer()` is not used (Laravel 8). Invalid values fall back to defaults and show a notice.
- **Carrying filters:** the filters are kept in the query string and carried by the section tabs, module-card links and "next" links.

## 10. Initial KPIs

| Code | KPI | Unit | Modules | Target |
|---|---|---|---|---|
| EV-PLANNED | الفعاليات المخططة | count | all 4 | informational |
| EV-APPROVED | الفعاليات المعتمدة | count | all 4 | informational |
| EV-EXECUTED | الفعاليات المنفذة | count | monthly, ramadan, bazaar | informational |
| EV-CLOSED | الفعاليات المغلقة | count | monthly, ramadan, bazaar | informational |
| EV-APPROVAL-RATE | نسبة الاعتماد (approved ÷ planned) | % | all 4 | `>=`, **no target → configuration_required** |
| EV-EXECUTION-RATE | نسبة التنفيذ من المعتمد المستحق (executed_due ÷ approved_due) | % | monthly, ramadan, bazaar | `>=`, **no target → configuration_required** |

- **Module summary cards:** live records in the period (including drafts) and the four stage counts. Agenda shows "لا ينطبق" for executed and closed, with the reason.
- **Module page** (`/dashboard/kpi/events/{module}`): only the KPIs applicable to that module, plus its stage card and two charts.

## 11. Unavailable data

- A stage the module does not own → `null` with a reason. It is never 0.
- A rate with a zero denominator → `null` ("لا توجد سجلات كافية لحساب النسبة ضمن الفترة").
- A user with no scoped branches → `null` everywhere, with an explicit reason.
- A zero **count** is shown only when the module owns the stage and the scope is valid: that is a real "none in this period".
- Totals across modules include only the modules that own the stage; the excluded modules are listed in the card notes.
- **Trend** is shown only when both periods have data: count KPIs need live records in both periods, and rate KPIs need a non-zero denominator in both. Otherwise the card says "لا تتوفر بيانات مقارنة".

## 12. Charts

- **Library:** Chart.js 4.4.1, from the shared `layouts/app/partials/chartjs.blade.php`. The deprecated Enterprise partial now uses the same include, so the pinned version lives in one place.
- **Data:** payloads are built in `KpiCalculatorService` and emitted with `@json($kpiChartPayload)`, with the default HEX escaping. Laravel 8's `@json` splits its argument on commas, so the payload is put into a variable first.
- **Charts:**
  1. **الفعاليات حسب النوع والمرحلة:** grouped bars of stage counts per module.
  2. **توزيع الحالات:** stacked bars of exclusive buckets (draft, awaiting/returned, approved-open, executed-open, closed). They are derived from the nested cumulative stages, so each record is counted once.
  3. **الاتجاه الشهري:** planned records per month of the selected year, per module.
- **No-data state:** a chart with no records shows "بيانات غير متوفرة" instead of an empty canvas.
- **`kpi-dashboard.js`:** renders only. It reads colours from the CSS tokens, re-renders when `html[data-theme]` changes, handles RTL axes and legends, and disables animation under `prefers-reduced-motion`.

## 13. Performance

- **Totals:** one aggregate query per module covers **both** the current and the comparison period. The query reads the contiguous range and groups by a `period_key` alias.
- **Other charts:** one grouped query per module for the 12-month trend. The branch page uses one grouped query per module.
- **Overview cost:** at most 8 queries for four modules. The module selector page runs only the 4 totals queries.
- **SQL shape:** `COUNT`/`SUM(CASE …)` only; no rows are loaded into PHP and there is no N+1. Every query is bounded by date range and branch scope.
- **Grouping:** aggregates group by select aliases (`period_key`, `month_number`, `branch_key`), which is safe under MySQL `ONLY_FULL_GROUP_BY`. The month expression is driver-aware (MySQL `MONTH()`, SQLite `strftime`, PostgreSQL `EXTRACT`).
- **Indexes:** none added (no migrations). The Phase 1 §21 index gaps (`monthly_activities(branch_id, proposed_date)`, `agenda_events(status)`, …) remain the Phase 9 item.
- **Verification:** the generated SQL was inspected statically with `toSql()` against the MySQL grammar; no connection was made.

## 14. Phase 4+ integration points

- **New KPIs:** add a catalogue definition and, if needed, a calculator type in `compute()`. Module-specific KPIs (MA-xx, RI-xx, …) should reuse `KpiModuleDefinition::liveQuery()` and `applyBranchScope()`.
- **Targets:** once approved, set `target` and `target_source => 'configured'`. `KpiTargetResolver` is the only place that evaluates achievement; a Setting-based override can be added there.
- **Drill-downs** (Phase 7): set `drilldown` on a definition. The card renders the link automatically.
- **Workflow timing KPIs** need the Phase 1 §13.3 log-based timing helper. It is not built yet.
- **User KPIs** (Phase 6): the `/dashboard/kpi/users` page and the `family = user` definitions; blocked on `kpi.users.view`.

## 15. Open business decisions (still OPEN)

1. KPI targets and SLAs/deadlines (all targets are `null`; rate KPIs show "لم يتم تحديد المستهدف").
2. Whether the 5-day plan lock (`lock_at`) counts as a planning deadline.
3. Whether "داخل الخطة" means agenda-linked.
4. The 40/60 branch monthly score and its "satisfaction" input.
5. reports_viewer branch scope (currently own or assigned branches only, the safe default) and followup_officer scope.
6. Who receives `kpi.users.view`.
7. Operational-issues capture (MA-23).
8. Whether workshops_secretary and communication_head keep `kpi.view`.

## 16. Static checks

- `php8.2 -l`: all new and changed PHP files pass.
- Blade: every new or changed template compiled with `BladeCompiler::compileString()` and the output linted.
  - All KPI views and layout partials pass.
  - The deprecated `pages/enterprise/partials/charts-scripts.blade.php` fails identically at HEAD: its multi-line `@json([...])` hits the same Laravel 8 comma-splitting bug. The page is unreachable (its routes redirect), so it is left for Phase 9 deletion.
- `node --check public/assets/js/pages/kpi-dashboard.js`: pass.
- `git diff --check`: clean.
- `rg "Enterprise Analytics|monthly-kpis|kpi"`: no retired navigation restored. The legacy `role.reports.kpis.index` history page remains unlinked.

**NO RUNTIME TESTS EXECUTED**
