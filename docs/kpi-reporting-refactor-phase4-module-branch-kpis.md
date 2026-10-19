# KPI & Reporting Refactor — Phase 4 Module & Branch KPIs

**Date:** 2026-10-19
**Builds on:** Phase 1 audit, Phase 2 cleanup, Phase 3 foundation (`d42a56f`)
**Scope:**
- Module KPI catalogues: Annual Agenda, Monthly Activities, Ramadan Iftars, Bazaars.
- Branch KPIs, branch comparison and branch drill-down.
- HTML KPI drill-down reports and module/branch charts.
- Bazaar closure capture and one KPI index.
- No user-performance KPIs, no exports, no KPI targets.

Static checks only; **no runtime tests executed**.

> The Phase 4 brief arrived truncated (sections 1–10 were not received). Sections 1–10 were implemented from the Phase 1 audit catalogue (AG/MA/RI/BZ) and the final-response outline (Bazaar closure capture, module KPI tables).

## 1. Architecture extension

```
app/Services/Kpi/
  Support/KpiSql.php              driver-aware SQL fragments (countIf, sumIf, daysBetween, hoursBetween, month, JSON count)
  Support/KpiMetricSource.php     one aggregate-able table/join → named metrics; branch-scope + branch-key closures
  Support/KpiWorkflowIdentity.php read-only entity_type spellings for workflow tables
  Modules/*KpiDefinition.php      lifecycle (live/planned/approved/executed/closed) + module metrics + secondary sources
  KpiCalculatorService.php        dataset engine (period | branch | month grouping) + generic KPI evaluator
  KpiChartBuilder.php             server-side chart specs
  KpiReportService.php            paginated HTML drill-down reports
  KpiRegistry / KpiPeriod / KpiScope / KpiTargetResolver / KpiFormatter / KpiResult   (Phase 3, extended)
config/kpis.php                   91 definitions (EV-, AG-, MA-, RI-, BZ-, BR-)
```

**Engine:**
1. A page asks for a set of KPI definitions. `KpiRegistry::metricsFor()` lists the metric names they need.
2. For each selected module, each metric source runs **one** grouped aggregate query containing only those metrics. The grouping is `current`/`previous`, the branch id, or the month number.
3. KPIs are evaluated in PHP from the aggregates: Σ numerator ÷ Σ denominator over the contributing modules.

**Definitions:**
- **Calculator types:** `sum`, `ratio` (with `scale`), `per_month`.
- **`availability`:** maps module → READY | PARTIAL, optionally with per-module numerator/denominator overrides (BR-12 uses this). `not_applicable` / `unavailable` give the reason for each excluded module.
- **`unavailable_reason`:** set when the whole KPI cannot be computed.
- **`exclusive_with`:** blocks Agenda + Monthly double counting.

**Data status:** every `KpiResult` carries `dataStatus` (READY · PARTIAL · UNAVAILABLE · NOT_APPLICABLE), the participating modules, exclusion notes and limitations. Cards show a status chip. UNAVAILABLE and NOT_APPLICABLE KPIs are listed separately ("مؤشرات غير متاحة حالياً") with their reason, never as a value.

**Fragments:** SQL fragments contain no request input. They use constants, integer ids (`KpiSql::intList`) and Carbon dates only, and take a table alias so the same definition works in correlated subqueries (e.g. "has a live, executed Monthly row").

## 2. Annual Agenda (AG)

Counting unit: agenda event (period `event_date`; undated events excluded). Participation KPIs count agenda participations (event × branch).

| Code | KPI | Formula | Data source | Status |
|---|---|---|---|---|
| AG-01 | فعاليات الأجندة المخططة | Σ planned | `agenda_events.status <> draft` (live) | READY |
| AG-02 | نسبة المعتمدة | approved ÷ planned | `status = published` | READY |
| AG-03 | نسبة الإعادة للتعديل | returned ÷ planned | `agenda_approvals.decision = changes_requested` | READY |
| AG-04 | متوسط زمن الاعتماد (يوم) | Σ hours ÷ measured ÷ 24 | first `workflow_logs.acted_at` on step `agenda_relations_officer_submit` → `approved_by_executive_at` | PARTIAL |
| AG-05 | طلبات تعديل/حذف | Σ requests | `annual_agenda_edit/delete_requests` by `requested_at` (no branch → oversight only) | READY |
| AG-06 | استجابة الفروع للمشاركة | answered ÷ rows | `agenda_participations.participation_status` | PARTIAL |
| AG-07 | تحويل المشاركة إلى خطة شهرية | participants with a live linked Monthly row ÷ participants | `monthly_activities.agenda_event_id + branch_id` | PARTIAL |
| AG-08 | تنفيذ الأجندة عبر الأنشطة الشهرية | participants (published, date ≤ today) whose linked Monthly row is executed ÷ those participants | Monthly lifecycle definition | PARTIAL |
| AG-09 | نافذة التاريخ ±7 | non-unified participations within ±7 days ÷ non-unified participations | `agenda_participations.proposed_date` vs `event_date` | READY |

Charts: planned vs approved by month, category distribution (`event_categories`, top 12), branch participation (top 15, with executed share).

## 3. Monthly Activities (MA)

Period `proposed_date`; live = not deleted, not archived, newest version.

| Code | KPI | Formula | Data source | Status |
|---|---|---|---|---|
| MA-01 | المخطط | Σ planned | status | READY |
| MA-02 | التنفيذ من المعتمد المستحق | executed_due ÷ approved_due | lifecycle + `proposed_date ≤ today` | READY |
| MA-03 | التنفيذ في الموعد | ontime ÷ executed with actual date | `actual_date ≤ COALESCE(rescheduled_date, proposed_date)` | PARTIAL (day-level) |
| MA-04 | متوسط التأخير (يوم) | Σ delay ÷ late | same | PARTIAL |
| MA-05 | الإلغاء/التأجيل | cancelled+postponed ÷ planned | `execution_status` | READY |
| MA-06 | زمن الاعتماد (يوم) | first `submitted` action log → `MAX(workflow_instances.completed_at)` (approved) | workflow tables, both spellings | PARTIAL |
| MA-07 | الاعتماد من أول تقديم | approved with `edit_request_count = 0` ÷ approved with a workflow | `workflow_instances` | READY |
| MA-08 | الإعادة للتعديل | `edit_request_count > 0` ÷ planned with a workflow | `workflow_instances` | READY |
| MA-09 | طلبات التعديل/الحذف لكل 100 معتمد | requests (by `requested_at`, `branch_id`) ÷ approved × 100 | `monthly_plan_edit/delete_requests` | READY |
| MA-10 | استكمال ما بعد التنفيذ | — | identical to MA-02 (execution is recorded by the post-execution submission) | inactive |
| MA-11 | زمن إدخال ما بعد التنفيذ | `actual_date` → first `post_execution_submitted` log | action logs | PARTIAL |
| MA-12 | إعادة ما بعد التنفيذ | executed with a `post_execution_review` clarification/rejection ÷ executed | `monthly_activity_approvals` | READY |
| MA-13 | اكتمال التحقق | closed with ≥1 verification row and none pending ÷ closed | `post_execution_verifications` | READY |
| MA-14 | دقة بيانات ما بعد التنفيذ | correct ÷ (correct + incorrect) rows | same | READY |
| MA-15 | اكتمال التقييم | closed with an evaluation ÷ closed | `activity_evaluations` | READY |
| MA-16 | متوسط التقييم | AVG `normalized_score` (aggregate only) | same | READY |
| MA-17 | تحقيق الحضور | Σ actual ÷ Σ expected (executed, both present) | `actual_attendance`, `COALESCE(expected_attendance_to, expected_attendance)` | READY |
| MA-18 | تغطية الفئات | — | no actual per-group counts | UNAVAILABLE |
| MA-19 | تأمين الاحتياجات | secured ÷ (secured + not_secured) | JSON `execution_needs_followup[].status` (MySQL `JSON_SEARCH`) | PARTIAL |
| MA-20 | توفر الاحتياجات فعلياً | provided ÷ (provided + not_provided) | JSON `[].post_status` | PARTIAL |
| MA-21 | مهام الفريق | — | `task_completed` not captured for Monthly | UNAVAILABLE |
| MA-22 | فقرات البرنامج | — | JSON booleans; not aggregatable without loading rows | UNAVAILABLE |
| MA-23 | الملاحظات التشغيلية | — | no authoritative source; decision open | UNAVAILABLE |
| MA-24 | زمن الإغلاق | `actual_date` → `closed` action log | action logs | PARTIAL |
| MA-25 | الالتزام بموعد التخطيط | — | 5-day rule decision open | UNAVAILABLE |

Charts: planned vs executed by month, lifecycle stages (incl. evaluated), expected vs actual attendance by month.

## 4. Ramadan Iftars (RI)

Period `planned_date`; live = not deleted, no child version.

| Code | KPI | Formula | Data source | Status |
|---|---|---|---|---|
| RI-01 / 02 / 03 | مخطط / اعتماد / تنفيذ | Σ planned; approved ÷ planned; executed_due ÷ approved_due | status, execution_status | READY |
| RI-04 | المتابعة | executed with report submitted/approved ÷ executed | `monitoring_reports` | READY |
| RI-05 | الإغلاق | closed ÷ executed | `closed_at` | READY |
| RI-06 | الوجبات | Σ actual ÷ Σ planned meals | `actual_meals_count`, `planned_meals_count` | READY |
| RI-07 | الحضور | Σ actual ÷ Σ expected | attendance columns | READY |
| RI-08 | تغطية الفئات | attended attendees in planned groups ÷ Σ planned_count | `ramadan_iftar_attendees`, `event_target_group` | READY |
| RI-09 / RI-10 | تطابق / عدم تطابق | matched ÷ (matched + mismatched); mismatched ÷ (m + mm + not_observed) | `post_execution_verifications.match_status` | PARTIAL (latest snapshot) |
| RI-11 | التصحيح | executed with ≥1 `monitoring_returned` log ÷ monitored | `workflow_action_logs` | READY |
| RI-12 | زمن الإغلاق | `actual_date` → `closed_at` | iftar | PARTIAL (no SLA) |
| RI-13 | الاحتياجات | required needs completed ÷ required needs | `subject_execution_needs` | READY |
| RI-14 | زمن الاعتماد | `submitted_at` → `approved_at` | iftar | READY |
| RI-15 | الإعادة | `edit_request_count > 0` ÷ planned with a workflow | `workflow_instances` | READY |
| RI-16 | طلبات التغيير المعتمدة | approved ÷ decided (by `created_at`, `branch_id`) | `ramadan_iftar_change_requests` | READY |
| RI-17 | فرق التاريخ | AVG(actual − planned) days | iftar | READY |
| RI-18 / 19 / 20 | الهدايا / الفقرات / مهام الفريق | actual ÷ planned; completed ÷ segments; confirmed ÷ members with recorded task status | gifts, segments, `execution_team_members.task_completed` | READY |
| RI-21 | الإرشادات | — | no eligible-user denominator | UNAVAILABLE |

Charts: planned vs actual meals by month, attendance by month, match vs mismatch vs not observed.

## 5. Bazaars (BZ)

Period `bazaar_date`; all rows live.

| Code | KPI | Formula | Data source | Status |
|---|---|---|---|---|
| BZ-01 / 02 | مخطط / تنفيذ | Σ planned; executed_due ÷ approved_due | status | READY |
| BZ-03 | الإشغال | Σ booked ÷ Σ planned tables | `actual_occupied_table_count`, `planned_table_count` | READY |
| BZ-04 / 05 | ثبات المستأجر / تطابق المعروضات | matches ÷ booked (or assessed) | `bazaar_tables` flags | READY (forms default to "match") |
| BZ-06 | التحصيل | Σ paid ÷ Σ due (booked) | `amount_paid`, `amount_due` | PARTIAL |
| BZ-07 | غير مسدد/جزئي | count booked with paid < due | amounts (not `payment_status`) | PARTIAL |
| BZ-08 / 09 / 10 | طلبات الخصم / نسبة الاعتماد / القيمة | Σ; approved ÷ decided; Σ(original − final) | `bazaar_table_discounts` | READY |
| BZ-11 | الإيراد مقابل المخطط | Σ paid ÷ Σ planned rent (booked) | `planned_rental_amount` | PARTIAL |
| BZ-12 / 13 | زمن الاعتماد / التحقق | `submitted_at` → `approved_at`; `post_execution_submitted_at` → `verified_at` | bazaar timestamps (overwritten on resubmit) | PARTIAL |
| BZ-14 | زمن الإغلاق | `bazaar_date` → `closed_at` | **new** `bazaars.closed_at` | PARTIAL (historical NULL) |
| BZ-15 | الاحتياجات | — | bazaar flow never records fulfilment | UNAVAILABLE |
| BZ-16 | الإعادة | — | no status history | UNAVAILABLE |

Charts: planned vs booked tables by month, payment state (paid / partial / unpaid, derived from amounts), discount status.

## 6. Branch KPIs (BR)

**Counting unit:** a branch-owned event record — a Monthly activity, a Ramadan iftar, or a Bazaar.

**No double counting:**
- Annual Agenda events are **not** added to branch totals; a branch's realisation of an agenda event is its Monthly activity.
- Agenda contributes only to BR-11, which counts participations.
- On the overview, EV-PLANNED, EV-APPROVED and EV-APPROVAL-RATE exclude Agenda whenever Monthly is in the same total (`exclusive_with`). Agenda figures remain on its own module card.

| Code | KPI | Modules | Status |
|---|---|---|---|
| BR-01 / 02 / 03 | المخطط / المعتمد / المنفذ | MA, RI, BZ | READY |
| BR-04 | نسبة التنفيذ (executed_due ÷ approved_due) | MA, RI, BZ | READY |
| BR-05 | التنفيذ في الموعد | MA (PARTIAL), RI; BZ excluded: no actual execution date | PARTIAL |
| BR-06 | اكتمال مراجعة ما بعد التنفيذ (reviewed ÷ executed): MA closed, RI monitoring report approved, BZ verified/completed | MA, RI, BZ | READY |
| BR-07 | الإغلاق (closed ÷ executed) | MA, RI, BZ | READY |
| BR-08 | تحقيق الحضور | MA, RI; BZ has no attendance | READY |
| BR-09 | عدد الفعاليات المدخلة/المخططة شهرياً (planned ÷ months elapsed) — proposed "4+" target **not configured** | MA, RI, BZ | READY |
| BR-11 | الالتزام بخطة الأجندة (executed participations ÷ due participations) | AG participations | PARTIAL |
| BR-12 | اكتمال الاحتياجات: RI needs_completed ÷ required; MA provided ÷ assessed (per-module metrics, listed on the card) | RI, MA; BZ unavailable | PARTIAL |
| BR-13 | تغطية الفئات | RI; MA/BZ unavailable | READY |
| BR-14 | أداء فرق العمل | — (task completion stored for Ramadan only; see RI-20) | UNAVAILABLE |
| BR-15 | الملاحظات التشغيلية | — (decision open) | UNAVAILABLE |

**Phase 1 code mapping:**
- B-X-01 → BR-01, B-X-02 → BR-04, B-X-04 → BR-08, B-X-05 → BR-12, B-X-08 → BR-11/AG-08 (kept in `aliases`).
- B-X-03, B-X-06, B-X-07 and B-X-09 are not carried as separate codes:
  - first-pass approval is in MA-07;
  - follow-up and evaluation are in BR-06, MA-13 and MA-15;
  - closure lag is in MA-24 and RI-12;
  - resource dependency has no captured source.
- Module distribution is the BR-01 per-module breakdown plus a chart, not a separate code.

## 7. Branch comparison (`/dashboard/kpi/branches`)

- Columns: branch, BR-01, BR-02, BR-03, BR-04, BR-08, BR-07.
- Data: one branch-grouped dataset query per module source.
- Sorting is server-side through `sort` and `dir`, both whitelisted; unavailable values always sort last.
- Filters: period, branch, and module (branch modules only).
- No "best branch", ranking, or 40/60 score.

## 8. Branch drill-down (`/dashboard/kpi/branches/{branch}`)

- An out-of-scope branch returns **403** (`KpiScope::forUser`); a missing branch returns 404.
- The page shows:
  - module stage cards and all BR KPI cards;
  - the monthly volume table (12 months: planned, approved, executed);
  - charts: planned/approved/executed by month, module distribution, execution-rate trend, attendance trend;
  - the unavailable-KPI list with reasons.

## 9. KPI drill-down reports (`/dashboard/kpi/reports/{report}`)

- **`on-time`** (monthly | ramadan): event, branch, planned date, actual date, on time?, delay (days), responsible (creator or relations officer).
- **`attendance`** (monthly | ramadan): event, branch, expected, actual, achievement %.
- **`ramadan-meals`**: iftar, branch, planned, actual, variance, achievement %.
- **`bazaar-occupancy`**: bazaar, branch, planned tables, booked, occupancy %.

All reports are paginated (25 rows), filtered by period and branch, scoped, and computed in SQL. They are HTML only; exports come in Phase 5. Cards link to them through `drilldown` in the catalogue.

## 10. Timestamp semantics

| Measure | Source | Never used |
|---|---|---|
| Monthly actual execution | `monthly_activities.actual_date` (day, entered at post-execution) | `updated_at` |
| Monthly submission / closure / post-execution | `workflow_action_logs` `submitted` / `closed` / `post_execution_submitted` (`performed_at`) | `workflow_instances.started_at` (lazy) |
| Monthly final approval | `MAX(workflow_instances.completed_at)` where `status = approved` | |
| Agenda submission | first `workflow_logs.acted_at` on step `agenda_relations_officer_submit` | `started_at` |
| Ramadan execution / closure | `actual_date`, `closed_at` | `updated_at` |
| Ramadan review | `workflow_action_logs` `monitoring_returned` | |
| Bazaar execution date | none (no actual date) → excluded from on-time KPIs | `execution_started_at` (data-entry time) |
| Bazaar closure | `bazaars.closed_at` (new; set by `close`) | `updated_at` |

**Workflow log compatibility:** `KpiWorkflowIdentity` reads every known `entity_type` spelling through `EventAggregateIdentity::acceptedTypes()`. Ramadan has a single spelling. Historical rows are never rewritten and no morph map is registered.

## 11. Historical / version safety

- Every query starts from the Phase 3 live-record rule:
  - Monthly: not deleted, not archived, newest version via `previous_version_id` / `parent_version_id`;
  - Agenda: not deleted, not archived;
  - Ramadan: not deleted, not superseded.
- Correlated "Monthly exists" checks (AG-07, AG-08, BR-11) reuse `MonthlyActivityKpiDefinition::liveSql('m')`.
- Change requests are counted by their own date and branch, so approved edits that archive the source are not lost.
- Agenda deletion detaches its Monthly rows (`agenda_event_id = NULL`); those rows stop counting toward AG-07, AG-08 and BR-11. This is a known lineage limitation.

## 12. Performance

- **No per-KPI queries:** one grouped aggregate query per metric source per grouping, containing only the requested metrics.
- **Query counts:**
  - module page: 1–3 period queries + 1 month query (+2 for Agenda charts);
  - branch comparison: 3–4 queries;
  - branch drill-down: up to 9.
- **Boundaries:** every query is bounded by date (`whereBetween` with an end-of-day bound) and branch, and is module-aware.
- **Correlated subqueries** hit existing indexes:
  - `post_execution_verifications(monthly_activity_id, field_key)`, `activity_evaluations(monthly_activity_id)`
  - `workflow_instances(entity_type, entity_id)`, `workflow_action_logs(module, entity_type, entity_id)`
  - `monitoring_reports(subject_type, subject_id[, status])`, `subject_execution_needs(subject_type, subject_id)`
  - `bazaar_tables(bazaar_id, table_number)`, `agenda_participations(agenda_event_id, entity_type, entity_id)`
  - `ramadan_iftar_attendees(ramadan_iftar_id, attended)`
- **New index:** `monthly_activities(proposed_date)`. It is the Monthly KPI period column and had no index. No other index was added: `ramadan_iftars(branch_id, planned_date)` and `agenda_events(event_date)` already exist, and Bazaar and request tables are small.
- **SQL check:** all 18 generated source queries were produced with `toSql()` against the MySQL grammar (no connection) and checked for balanced parentheses.

## 13. Migrations (new only)

- `2026_10_19_000100_add_closure_capture_to_bazaars_table.php` adds nullable `closed_at` and `closed_by` (FK users, `nullOnDelete`), guarded by `hasColumn`. `BazaarController::close` now sets both.
- `2026_10_19_000200_add_kpi_period_index_to_monthly_activities_table.php` adds the `monthly_activities_proposed_date_idx` index.

**NO PROTECTED PRODUCTION MIGRATION WAS MODIFIED.**

## 14. Open business decisions (unchanged, OPEN)

Official targets · SLA deadlines · 5-day planning rule (MA-25) · 40/60 branch score · reports-viewer scope · operational-issues capture (MA-23/BR-15) · `kpi.users.view` · whether workshops_secretary and communication_head keep `kpi.view`.

## 15. Phase 5 readiness

- **User performance:** reuse `KpiMetricSource` with actor columns (`workflow_logs.acted_by`, `verified_by`, `evaluated_by`, `created_by`) and group by user. Still blocked on `kpi.users.view`.
- **Profile performance:** the same engine with `scope = user`; the `KpiResult` and card partials are reusable.
- **Detailed reports and exports:** `KpiReportService` row queries are ready to stream to CSV (reuse `EnterpriseReportsController::csvResponse`).
