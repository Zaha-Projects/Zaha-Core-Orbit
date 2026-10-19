# KPI & Reporting Refactor — Phase 5 User Performance & Reports

**Date:** 2026-10-19
**Builds on:** Phases 0.5–4 (`docs/kpi-reporting-refactor-phase1…4-*.md`)
**Scope:**
- user-performance KPIs (RO, SV, FO, MG);
- My Profile «الأداء» and the authorised manager user view;
- `/dashboard/kpi/users` (same-role comparison) and `/dashboard/kpi/users/{user}`;
- per-user action drill-downs;
- real CSV export for all KPI detailed reports.

Not in scope: KPI Guide and optimisation/deprecated cleanup (Phase 6). Static checks only; **no runtime tests executed**.

## 1. Architecture extension (same engine, no second system)

- **`KpiMetricSource`** gains:
  - **`actor`** — SQL for the user who performed the action;
  - **`rows`** — drill-down row columns;
  - **`rowFilters`** — named row conditions such as `returned` or `approved`.

  `build()` accepts a user filter. A source with no actor never takes part in user datasets.
- **Module definitions** add `actorSources()`, one per attributable action. Two shared helpers build them: `actionLogSource()` (workflow_action_logs) and `decisionSource()` (workflow_logs on main steps).
- **`KpiCalculatorService::dataset()`** adds the `user` grouping (`GROUP BY actor`) and an optional user filter, combined with the existing period, branch and month groupings. Role grouping is the user grouping restricted to one family's users (users page).
- **`KpiScope`** adds `canViewUsers`, `canViewUser` (scope check for direct-ID access), `canExport`, `forSelf` and `allowedBranchIds`.
- **`KpiWorkflowIdentity`** adds the action-log module per KPI module and `humanDecisionSql()`, which excludes auto-approval rows by their fixed Arabic and English comments.
- **`KpiResult`** is unchanged. User KPIs carry `drilldown.route = 'user'`, and the page decides which action-report route to use (profile or management).
- **`KpiReportService`:**
  - query building is separated from pagination, so HTML and CSV run the same query;
  - `USER_ACTIONS` reports reuse the very actor sources that feed the KPIs, so each value can be explained row by row.
- **New service `KpiUserPerformanceService`:**
  - `performance()` builds the panel payload;
  - `panel()` serves the profile and admin pages;
  - `comparison()` serves the users page;
  - `candidates()` lists the users in scope.
- **New controller pieces:** `KpiUserController`, a shared `ParsesKpiFilters` trait, and a `StreamsCsv` trait extracted from `EnterpriseReportsController::csvResponse()`. The trait adds an optional UTF-8 BOM; Enterprise exports keep their original output.

**Fixed defect affecting Phase 4 values.** `KpiSql::literal` did not escape backslashes, and MySQL treats `\` as an escape character in string literals. The workflow `entity_type` lists (`App\Models\…`) therefore never matched. Every workflow-based KPI (MA-06/07/08, RI-11, RI-15, AG-04 …) returned 0 or no data. Backslashes are now doubled on MySQL/MariaDB.

## 2. Role families (`config/kpis.php` → `user_families`)

| Family | Roles | Primary action | Turnaround KPI | Comparison columns |
|---|---|---|---|---|
| `ro` مسؤول العلاقات | relations_officer | events created | RO-06 | RO-01, 02, 05, 06, 07, 08 |
| `sv` رئيس الفرع / منسق الفروع | supervisor, branch_coordinator | decisions | SV-05 | SV-01, 03, 05, 07, 08, 10 |
| `fo` مسؤول المتابعة | followup_officer | cases handled | FO-05 | FO-01, 02, 03, 04, 05, 10 |
| `mg` الإدارة | relations_manager, executive_manager, super_admin | decisions | MG-02 | MG-01, 02, 03, 04 |

- **Which KPIs a user sees:** only their family's KPIs. A user with several roles gets one section per family; denominators are never merged across families.
- **No overall score:** there is no universal score, grade or "best employee".

## 3. KPI catalogue

Counting unit: one attributable action. The period is the action's own timestamp.

### Relations Officer (RO)

| Code | KPI | Formula | Actor / source | Status |
|---|---|---|---|---|
| RO-01 | الفعاليات المنشأة | count | `created_by` (Monthly, Ramadan, Agenda), Bazaar `relations_officer_id` (set to the creator); period `created_at` | READY |
| RO-02 | الفعاليات المقدّمة | distinct subjects submitted | `workflow_action_logs.submitted.performed_by` (MA, RI); Agenda sub-step `workflow_logs.acted_by` | READY (Bazaar UNAVAILABLE) |
| RO-03 | نسبة استكمال التقديم | created & left draft ÷ created | creation source | READY |
| RO-04 | المعادة للتصحيح | created & returned ≥1 | `workflow_instances.edit_request_count > 0`; Agenda `agenda_approvals` | READY (Bazaar UNAVAILABLE) |
| RO-05 | نسبة الإعادة | RO-04 ÷ created & submitted | as above | READY |
| RO-06 | الإنشاء → التقديم (يوم) | `created_at` → first submission | first `submitted` log / Ramadan `submitted_at` (first) / Agenda first sub-step log | READY (Bazaar UNAVAILABLE: `submitted_at` overwritten) |
| RO-07 | إدخالات ما بعد التنفيذ | distinct subjects | `post_execution_submitted` (MA) / `execution_completed` (RI) `performed_by` | READY |
| RO-08 | التنفيذ → ما بعد التنفيذ (يوم) | `actual_date` → first log | same logs | MA PARTIAL (day-level), RI READY |
| RO-09 | مرات إعادة التقديم | submissions − distinct subjects | submission logs | READY |
| RO-10 | الاعتماد دون إعادة | approved with `edit_request_count = 0` ÷ approved with a workflow | `workflow_instances` | PARTIAL: proves "no return happened", not field completeness |

### Supervisor / Branch coordinator (SV)

| Code | KPI | Formula | Actor / source | Status |
|---|---|---|---|---|
| SV-01 | الطلبات التي راجعها | decisions on main steps | `workflow_logs.acted_by` (auto-approval excluded) | READY |
| SV-02 / 03 / 04 | معتمد / معاد / مرفوض | decisions by action | same; Ramadan has no reject → SV-04 not applicable | READY |
| SV-05 | زمن اتخاذ القرار (ساعة) | entry → decision | entry = later of the previous instance log and the latest `submitted` log ≤ decision | PARTIAL (no step-assignment time) |
| SV-06 | الزمن الكامل للاعتماد النهائي (يوم) | first submission → the user's final approval | final = approved, instance approved, no later log | PARTIAL |
| SV-07 | مراجعات ما بعد التنفيذ | decisions | MA `monthly_activity_approvals` (post_execution_review).approved_by + `closed` logs; RI `monitoring_approved/returned` logs | READY |
| SV-08 | ما بعد التنفيذ المعادة | returns | MA clarification/rejected; RI `monitoring_returned` | READY |
| SV-09 | قرارات خصومات البازار | count | `bazaar_table_discounts.approved_by` | PARTIAL (period by `requested_at`: decision time stored for approvals only) |
| SV-10 | قرارات الإغلاق | count | MA `closed` log; RI `iftar_closed` log; Bazaar `closed_by` | READY; Bazaar PARTIAL (captured since Phase 4) |
| SV-11 | دقة القرار | — | — | **UNAVAILABLE**: «النظام لا يسجل مرجعًا موضوعيًا يحدد أن القرار السابق كان صحيحًا أو خاطئًا.» |

### Follow-up Officer (FO)

| Code | KPI | Formula | Actor / source | Status |
|---|---|---|---|---|
| FO-01 | الحالات التي باشرها | distinct cases | MA `post_execution_verifications.verified_by`; RI `monitoring_verification_submitted.performed_by`; Bazaar `verified_by` | PARTIAL (no case assignment is stored, so "received" = acted on) |
| FO-02 | الحالات المكتملة | count | MA `activity_evaluations.evaluated_by`; RI first monitoring submission; Bazaar verified | READY |
| FO-03 | نسبة الإكمال | FO-02 ÷ FO-01 | — | PARTIAL (numerator and denominator dates can fall in different periods) |
| FO-04 | التقارير المعادة | count | RI `monitoring_returned` attributed to `monitoring_reports.monitor_user_id` | PARTIAL; MA not applicable; Bazaar UNAVAILABLE |
| FO-05 | زمن المتابعة (يوم) | MA `closed` → evaluation; RI `execution_completed` → first monitoring; Bazaar post-exec → verified | logs and timestamps | READY; Bazaar PARTIAL |
| FO-06 / 07 | المطابقة / عدم المطابقة | counts | `post_execution_verifications.match_status`, `verified_by` | PARTIAL: only the latest report snapshot is stored |
| FO-08 | البازارات المتحقق منها | count | `bazaars.verified_by` | READY |
| FO-09 | بنود التحقق الشهرية | resolved rows | `post_execution_verifications` (Monthly) | READY |
| FO-10 | التقييمات | count | `activity_evaluations.evaluated_by` | READY |

### Managers (MG)

| Code | KPI | Status |
|---|---|---|
| MG-01 | decisions handled | READY |
| MG-02 | average decision time | PARTIAL |
| MG-03 | distinct requests reviewed | READY |
| MG-04 | returned | READY |
| MG-05 | requests currently waiting at a step assigned to one of the user's roles (snapshot, no trend) | PARTIAL: role-assigned steps only |

**Oversight:** a manager's branch and portfolio picture is the existing branch dashboards (`/dashboard/kpi/branches`). No personal score is invented.

## 4. Actor attribution rules

- Used: `created_by`, `relations_officer_id` (Bazaar creator), `workflow_logs.acted_by`, `workflow_action_logs.performed_by`, `monthly_activity_approvals.approved_by`, `post_execution_verifications.verified_by`, `activity_evaluations.evaluated_by`, `bazaars.verified_by` and `closed_by`, `bazaar_table_discounts.approved_by`, `monitoring_reports.monitor_user_id` (report owner, FO-04 only).
- **Never used as an actor:** `updated_by`, the last updater, the current owner, or the branch manager.
- **Auto-approval rows** (`app.workflow_auto_approval.log_comment`, in both locales) are excluded from human-decision KPIs.
- **Historical spellings:** read through `KpiWorkflowIdentity`, which accepts every known spelling. No rows are rewritten and no morph map is added.

## 5. Time metrics (start → end)

| KPI | Start | End |
|---|---|---|
| RO-06 | `created_at` | first `submitted` action log (MA) / `submitted_at` (RI, first submission kept) / first sub-step `workflow_logs` (AG) |
| RO-08 | `actual_date` | first `post_execution_submitted` (MA) / first `execution_completed` (RI) |
| SV-05, MG-02 | later of the previous `workflow_logs.acted_at` in the instance and the latest `submitted` log | the decision's `acted_at` |
| SV-06 | first submission | the user's final approval `acted_at` |
| FO-05 | MA first `closed` log / RI first `execution_completed` / BZ `post_execution_submitted_at` | `activity_evaluations.submitted_at` / first `monitoring_verification_submitted` / `verified_at` |

`updated_at` and `workflow_instances.started_at` (lazy page-open time) are never used.

## 6. Access model

| Who | Can see | Export |
|---|---|---|
| Any authenticated user | own panel (My Profile → الأداء), own action reports at `/profile/performance/actions/{action}`, own actions in every branch | own actions CSV |
| `kpi.users.view` (super_admin, executive_manager, relations_manager) | `/dashboard/kpi/users`, `/dashboard/kpi/users/{user}`, the admin user page panel. The target must share the viewer's branch scope (oversight viewers: any user); out of scope → **403**. Only the target's actions in the viewer's branches are counted | needs `kpi.export` |
| `kpi.export` (super_admin, executive_manager, relations_manager) | — | CSV of Phase 4 KPI reports and other users' action reports |

- **Follow-up officers:** do **not** get cross-user access.
- **Branch filter leakage:** branch filters are validated by `KpiScope` (403 outside scope), and the `user` filter only selects among in-scope candidates.

**Navigation:**
- «مؤشرات المستخدمين» appears in the KPI sidebar and section tabs only for `canViewUsers`.
- Self performance lives in My Profile.
- The KPI overview links «أدائي».

## 7. Pages

- **My Profile → الأداء:** the shared partial `pages/kpi/partials/user-performance.blade.php`. It shows:
  - a period selector and the comparison period;
  - role-family sections with KPI cards (trend, module breakdown, data status, limitations);
  - action-report links per family and unavailable KPIs with their reasons;
  - charts: actions per month, module distribution, turnaround trend.
- **Admin user page** (`role.super_admin.users.show`) and **`/dashboard/kpi/users/{user}`:** the same partial, with the viewer's scope.
- **`/dashboard/kpi/users`:**
  - role-family tabs; filters for period, branch, role, user and module;
  - a sortable same-role comparison table (unavailable values sort last) with links to user detail;
  - charts: same-role comparison of one KPI, workload distribution, turnaround trend.
  - There is no ranking or badge.
- **Action reports** (`USER_ACTIONS`):
  - action types: created · submitted · returned · post_execution · decisions (filters approved / returned / rejected) · post_exec_reviews · closures · discounts · verifications · monitoring · evaluations;
  - columns: المستخدم، نوع الفعالية، الفعالية، الفرع، الإجراء، التاريخ، النتيجة، المدة;
  - module tabs; paginated at 25 rows.

## 8. CSV export

| Route | Authorisation |
|---|---|
| `kpi.reports.export` (Phase 4 reports) | `kpi.export` |
| `kpi.users.actions.export` | self, or `kpi.export` and in scope |
| `profile.performance.actions.export` | self only |

- **Same query as HTML:** same filters, scope and order as the page.
- **Streaming:** rows come from `cursor()` and are written by `StreamsCsv`, so the report is never loaded into memory.
- **Format:** UTF-8 BOM, Arabic headers; codes are translated in SQL (`CASE`) and on-time is written as نعم/لا.
- **Buttons:** export buttons render only when permitted and rows exist. No fake export exists.

## 9. Permission seeder

`database/seeders/KpiPermissionSeeder.php` is registered after `RolesSeeder` in `DatabaseSeeder`. It is additive only: `Permission::firstOrCreate` + `givePermissionTo`, with no sync, revoke or delete.
- `kpi.users.view` → super_admin, executive_manager, relations_manager.
- `kpi.export` → super_admin, executive_manager, relations_manager.

## 10. Performance

- **Per page:** one grouped aggregate query per actor source (`GROUP BY actor`, filtered by user ids), never one query per user per KPI.
  - users page: ≤ 24 source queries for the active family's metrics only, plus one month-dataset pass for the turnaround trend;
  - user panel: period + month datasets for one user.
- **Correlated subqueries** use existing indexes: `workflow_logs` instance FK, `workflow_action_logs(module, entity_type, entity_id)`, `workflow_instances(entity_type, entity_id)`, `post_execution_verifications(monthly_activity_id…)`, `activity_evaluations(monthly_activity_id)`.
- **Bounds:** every query is date-bounded and branch-scoped. No collections of workflow history are loaded.
- **Verification:** all 24 actor-source aggregate and row queries were generated with `toSql()` (MySQL grammar, no connection) and checked for balanced parentheses.

## 11. Migrations

None. Only the additive permission seeder.

**NO PROTECTED PRODUCTION MIGRATION WAS MODIFIED.**

## 12. Open business decisions (OPEN)

Official targets · SLA deadlines · overall user score (and weights) · 40/60 branch score · operational-issues capture · reports-viewer and follow-up officer scope · whether supervisors should get `kpi.users.view` for their own branch.

## 13. Phase 6 readiness

- **KPI Guide:** every definition now carries name, description, formula metrics, unit, operator, availability, not-applicable/unavailable reasons, limitations and drill-down. The guide can be generated from `config/kpis.php`.
- **Optimisation and caching:** datasets are pure functions of (modules, grouping, period, scope, metrics, users). A cache key can be built from those inputs, plus flush on writes. Avoid `Cache::flush()` collisions (SiteSettings).
- **Indexes to evaluate with real `EXPLAIN`:**
  - `workflow_action_logs(action_type, performed_at)`
  - `workflow_logs(acted_by, acted_at)`
  - `post_execution_verifications(verified_by, verified_at)`
  - `activity_evaluations(evaluated_by, submitted_at)`
- **Deprecated cleanup:** Enterprise controller, service and views; Agenda/Monthly report controllers; manual KPI store; the orphan launcher redirects; dead computations listed in the Phase 2 doc.
