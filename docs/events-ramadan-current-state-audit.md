# Events / Ramadan current-state audit and synchronized TODO

**Audit date:** 2026-09-20  
**Audited branch/tip:** `work` at `7bb705f`  
**Status:** CURRENT / AUTHORITATIVE  
**Scope:** source, migrations, tests, documentation and Git history. Runtime and browser behavior are not certified because `vendor/autoload.php` and a usable application/database environment are absent.

This is the single current continuation ledger. Historical phase documents remain useful decision records, but their old “next”, “blocked”, model-location, and phase statements are superseded by this file.

## Executive finding and exact milestone

**CURRENT MILESTONE: Agenda aggregate namespace cutover complete in source; Ramadan source implementation and business-form reconciliation complete; identity-sensitive request, Monthly aggregate, and post-execution verification cutovers remain. All runtime/staging verification remains outstanding.**

The repository is beyond Phase 2.14B in source terms. AgendaEvent is not pending and Ramadan is not merely planned. Conversely, source completion is not production certification: migrations, seeders, workflows, browser behavior, and regression tests have not run in this checkout.

## Git and recent implementation ledger

| Commit | Purpose | Important impact | Status |
|---|---|---|---|
| `7bb705f` | merge PR 132 | integrates the current Events/Ramadan line | merged/current |
| `3739bf0` | finish planning/reference management | Monthly custom needs, QA/preflight commands, uniqueness hardening | source complete; runtime pending |
| `d5bb7d1`, `71bce1c`, `867bd75` | reference administration/scope hardening | admin CRUD, explicit scopes, period table, source-hashed guidance, gift/target references, stricter planning | source complete; runtime pending |
| `995bf7b` | shorten acknowledgement identifiers | replaces unsafe implicit MySQL names with `ega_user_fk`, `ega_guidance_fk`, `ega_user_guidance_uq` | source complete; migration pending |
| `e6296ce` | source/dashboard hardening | persistent guidance authority, planned supply availability, copy fidelity, bounded dashboard | source complete; runtime pending |
| `c4c3dd1` | reconcile Ramadan form | host flow, acknowledgement table, canonical needs, synchronized children, staging/demo orchestration | source complete; runtime pending |
| `cee1bbd` | Ramadan UX/calendar/demo | theme, cards, calendar, period settings, guidance, ten demo scenarios | source complete; browser pending |
| `657e506` | AgendaEvent cutover | sole production class moved to Events while retaining exact identity compatibility | source complete; staging pending |
| `e6b66b3` | Agenda compatibility | dual identity reads/resolution and duplicate prevention | source complete; staging pending |
| `7ed74ef` | aggregate identity audit | selected separate, Agenda-first cutovers | decision complete |
| `b2f6c9e` | request identity compatibility | exact four-model map and guarded workflow reads/creation | source complete; staging pending |
| `e9b9675` | request identity audit | established request models as independent stored identities | decision complete |

## Migration audit

| Migration | Static result |
|---|---|
| `2026_09_15_000100_reconcile_ramadan_iftar_business_form.php` | Adds mandatory flags, gift type, and acknowledgement storage. Explicit names are under MySQL's 64-character limit. Unique `(user_id,event_guidance_version_id)` is version-specific; both FKs cascade; `down()` order is consistent. |
| `2026_09_15_000200_add_planned_availability_to_event_supplies.php` | Adds/removes nullable `planned_available`, intentionally separate from execution availability. |
| `2026_09_16_000100_create_ramadan_periods_table.php` | Unique Gregorian year, dates, active/date index and MySQL date-order check. Imports only complete valid legacy settings; no abandoned table is recreated. |
| `2026_09_16_000200`–`000500` | Adds guidance hash, preserves Monthly needs, target codes, and gift-type reference storage; rollback behavior avoids unsafe catalogue deletion. |
| `2026_09_17_000100` | Adds nullable `scope_configured_at` for explicit admin scope choices. |
| `2026_09_18_000100` | Preflights whitespace/duplicates before named branch/name and global-name unique indexes; matching `down()` names, all below 64 characters. |

The unsafe implicit `event_guidance_acknowledgements_event_guidance_version_id_foreign` is absent from current migration source. Final names are `ega_user_fk`, `ega_guidance_fk`, and `ega_user_guidance_uq`. Because this modified a recent migration rather than adding a corrective migration, deployment history must be confirmed before using the branch against a database that may have run its older form.

## Actual model ownership

| Model | Current namespace | Table | Identity sensitive? | Status / remaining action |
|---|---|---|:---:|---|
| AgendaEvent | `App\Modules\Events\Models` | `agenda_events` | yes | **SOURCE COMPLETE**; sole production definition; stage old/new identities |
| MonthlyActivity | `App\Modules\Events\Models` | `monthly_activities` | yes, including correspondence morph | **SOURCE COMPLETE / STAGING PENDING** |
| AnnualAgendaEditRequest / DeleteRequest | `App\Modules\Events\Models` | `annual_agenda_edit_requests` / `annual_agenda_delete_requests` | yes | **SOURCE COMPLETE / STAGING PENDING**; sole canonical definitions |
| MonthlyPlanEditRequest / DeleteRequest | `App\Modules\Events\Models` | `monthly_plan_edit_requests` / `monthly_plan_delete_requests` | yes | **SOURCE COMPLETE / STAGING PENDING** |
| PostExecutionVerification | `App\Modules\Events\Models` | `post_execution_verifications` | yes (audit identity) | **SOURCE COMPLETE / STAGING PENDING** |
| ExecutionNeedType | `App\Modules\Events\Models` | `execution_need_types` | no FQCN dependency found | moved/current |
| TargetGroup | same | `target_groups` | no | moved/current |
| SubjectTargetGroup | same | **`event_target_group`** | stable subject alias | moved/current; no alternate table |
| ExecutionTeam / ExecutionTeamMember | same | `execution_teams` / `execution_team_members` | stable alias / no FQCN found | moved/current |
| EventSupply | same | `event_supplies` | stable subject alias | moved/current |
| SubjectExecutionNeed | same | `subject_execution_needs` | stable subject alias | moved/current; Ramadan transactions |
| SubjectVolunteerRequirement | same | `subject_volunteer_requirements` | stable subject alias | moved/current |
| RamadanIftar | same | `ramadan_iftars` | workflow identity, canonical | current |
| Attendee / Meal / MealItem / Gift / ProgramSegment / ChangeRequest | same | matching `ramadan_iftar_*` tables | change request has workflow identity | current |

There is no production `app/Models/AgendaEvent.php`. Exact `App\Models\AgendaEvent` occurrences are compatibility identity strings, tests, or historical documentation—not an executable model.

## Identity-refactor state

| Phase | Actual source status | Meaning |
|---|---|---|
| 2.13A request workflow compatibility | DONE IN SOURCE / STAGING PENDING | four exact pairs; dual reads/find-before-create; Annual Agenda writers canonical, Monthly writers legacy |
| 2.14 aggregate audit | DONE | identity surfaces and order documented |
| 2.14A Agenda compatibility | DONE IN SOURCE / STAGING PENDING | exact Agenda pair resolves to installed model and protects uniqueness |
| 2.14B AgendaEvent cutover | DONE IN SOURCE / STAGING PENDING | canonical class is sole production definition; new class-derived writes canonical |

Annual Agenda and Monthly Plan request models, `MonthlyActivity`, and `PostExecutionVerification` are **SOURCE COMPLETE / STAGING PENDING**. No Events-owned identity-sensitive model remains under `App\Models`; completed slices retain their staging debt.

## Ramadan architecture and form

| Area | Classification | Qualification |
|---|---|---|
| theme, cards, calendar | COMPLETE IN SOURCE | browser/RTL staging remains |
| period/admin settings | COMPLETE IN SOURCE | normalized period plus settings compatibility; migration pending |
| guidance seed/ack/create gate | COMPLETE IN SOURCE | hashed version, persistent acknowledgement, session presentation guard |
| server branch | COMPLETE IN SOURCE | derived by request; posted branch absent from markup; branch references checked |
| host/organization/community/mobilization/attendees | COMPLETE IN SOURCE | conditional validation; local-community attendees required |
| targets/meals | COMPLETE IN SOURCE | generalized target pivot and nested synchronization |
| needs/team/supplies/gifts | COMPLETE IN SOURCE | one catalogue; team mandatory; supplies/gifts optional; planned availability distinct |
| programs/volunteers | COMPLETE IN SOURCE | repeatable synchronized children |
| approvals/execution/monitoring/review/closure | COMPLETE IN SOURCE | services/controllers present; staging regression required |
| approved-plan versioning | COMPLETE IN SOURCE | planning copied; actual/workflow/monitoring/closure reset |
| dashboard | COMPLETE IN SOURCE | period/permission/branch gated, aggregate metrics, five upcoming |

Create/edit share `_form` and planning partials. Order is guidance, basics, host and conditional owner, targets, meals, canonical execution needs and conditional details, programs, volunteers, review/notes. Team, supplies and gifts are detail panels inside one need selection, not duplicate selectors. Child IDs are ownership-checked and synchronization updates/removes owned planning rows instead of appending. No source-contract mismatch was found.

## Execution-needs contract

Fields are `code`, `name`, `description`, `sort_order`, `is_active`, `is_canonical`, `is_monthly_activity`, `is_ramadan_iftar`, `mandatory_for_monthly`, `mandatory_for_ramadan`, and `scope_configured_at`.

| Code | Monthly | Ramadan | Mandatory monthly | Mandatory Ramadan |
|---|:---:|:---:|:---:|:---:|
| execution_team | yes | yes | no | **yes** |
| volunteers | yes | yes | no | no |
| official_correspondence | yes | yes | no | no |
| media_coverage | yes | yes | no | no |
| supplies | yes | yes | no | no |
| official_sponsorship | yes | no | no | no |
| external_partners | yes | no | no | no |
| ceremony_agenda | yes | no | no | no |
| transport | yes | yes | no | no |
| maintenance_workers | yes | yes | no | no |
| gifts_shields | yes | yes | no | no |
| programs_participation | yes | no | no | no |
| certificates | yes | no | no | no |
| thanks_letters | yes | no | no | no |
| invitations | yes | yes | no | no |

Defaults come from `CANONICAL_DEFINITIONS` (`monthly` defaults true; mandatory defaults false). Ramadan choices are queried from the catalogue; no duplicate hardcoded selectable list remains. Monthly retains legacy fields, JSON payload, and follow-up semantics; Ramadan uses `SubjectExecutionNeed`.

## Target-group integrity

`SubjectTargetGroup::$table` is `event_target_group`. Remaining `subject_target_groups` references are historical consolidation records or negative tests asserting the abandoned table is not created. No current document treats it as active storage; it must not be recreated.

## Guidance and period behavior

Persistent acknowledgement of the exact active/published version authorizes subsequent create requests. Session state records the version presented and prevents stale-page acceptance; it is cleared after create. The contract is **persistent acknowledgement + session presentation guard**, not session acceptance. A new version ID/hash requires a new acknowledgement.

Keys are `ramadan_period_year`, `ramadan_period_start_date`, `ramadan_period_end_date`, `ramadan_period_is_active`; selection also supports `ramadan_default_year`, falling back to period year then current year. Seeder defaults are `2026`, `2026-02-18`, `2026-03-19`, active `1`.

| Existing configuration | Behavior |
|---|---|
| complete valid active | preserve; insert absent year; active helper resolves it |
| complete valid inactive | preserve; no active period; demo fails rather than overriding policy |
| all four missing | insert defaults and period |
| partial | throw actionable exception; do **not** guess/fill |
| invalid complete | validation fails; do not silently activate |

The Arabic “no active Ramadan period” error is addressed in source for truly missing configuration by the staging seeder. It remains correct for inactive/invalid/partial configuration. Runtime success is not claimed.

## Seeder and demo inventory

| Seeder | Purpose / prerequisites | Idempotency/scope | Status |
|---|---|---|---|
| RamadanPeriodSeeder | bootstrap period; settings/schema | `insertOrIgnore`, preserves admin values; reference | source complete |
| RamadanIftarGuidanceSeeder | verify PDF hash and publish version | hash-idempotent; production reference | source complete |
| MobilizationMethodSeeder | seven coded methods | `insertOrIgnore`; production reference | source complete |
| RamadanReferenceDataSeeder | targets, segments, monitoring, mobilization, gifts, needs, period, guidance | stable child keys; production/reference | source complete |
| RamadanIftarDemoSeeder | users, acknowledgement, lifecycle examples | stable upserts; **demo only, branch 23**; needs branch/roles/workflow/PDF/references | source complete; staging pending |
| RamadanIftarStagingSeeder | explicit reference+demo orchestration | intended idempotent; staging only; not production DatabaseSeeder | source complete |

Scenarios 01–09 use active-period start plus days 1/4/7/10/10/14/18/22/27 (clamped): draft; submitted/pending; submitted/in-progress; approved/planned; approved/in-progress; execution completed; monitoring submitted; monitoring approved; closed. Scenario 10 is a version-2 draft child of scenario 04. Workflow instances are approximate; no workflow logs or monitoring verification rows are made, so staging validation remains necessary.

## Dashboard contract

The panel requires an active period and Ramadan view permission (or super admin). Users lacking all-branch permission use `scopedBranchIds`; branch 23 is not hardcoded. One conditional aggregate query calculates total, upcoming, today, draft, awaiting approval, approved, planned/in-progress execution, completed-awaiting-monitoring, monitoring-submitted, and closed. A second query eager-loads branch/organization and limits upcoming rows to five. Cards, calendar, conditional create/approval actions, and create-aware zero state exist. No obvious Ramadan N+1 exists.

## Test inventory

| Tests | Coverage | Execution status |
|---|---|---|
| `EventRequestModelIdentityTest`, `EventRequestWorkflowIdentityCompatibilityTest` | four request mappings, dual reads/resolution, duplicates | present; not run here |
| `EventAggregateIdentityTest`, `AgendaEventIdentityCompatibilityTest`, `AgendaBranchInteractionTest` | Agenda old/new identity/namespace behavior | present; not run here |
| `RamadanPeriodAndGuidanceTest`, `RamadanGuidanceAcceptanceTest` | periods and version-bound persistence | present; not run here |
| `RamadanIftarBusinessReconciliationTest`, `RamadanIftarPlanningFlowTest`, `TargetGroupSelectionTest` | branch/host validation, needs, synchronized children | present; not run here |
| `RamadanProductionReferenceTest`, `RamadanReferenceManagementTest`, `RamadanScopePreservationTest`, `MonthlyCustomExecutionNeedsTest` | reference/admin/scope/legacy Monthly | present; not run here |
| `RamadanSourceConsistencyAndDashboardTest` | abandoned table, copy/dashboard contracts | source-oriented; PHPUnit not run |
| Ramadan aggregate/detail/approval/execution/monitoring/review/closure/version tests | feature lifecycle slices | present; not run; staging required |

## Documentation classification

| Document | Classification |
|---|---|
| this file | **CURRENT / AUTHORITATIVE** continuation/TODO ledger |
| `events-architecture-current-state.md` | current architecture reference; this audit governs milestone/next |
| `events-branch-handover.md` | current context, but embedded old phase/model/next statements are superseded |
| `events-model-identity-and-namespace-audit.md` | current evidence through 2.14B; earlier interim sections are historical |
| `events-runtime-verification-report.md` | runtime-debt history; not current success proof |
| aggregate/request cutover docs | authoritative focused design/history; current TODO is here |
| form reconciliation/production reference docs | current focused contracts; conflicting period wording is superseded |
| `ramadan-iftars-ui-and-demo-data.md` | current feature description; runtime pending |
| `ramadan-iftars-master-todo.md` | historical/superseded checklist |
| Phase 1 and consolidation/scope failure docs | historical evidence; old NEXT/BLOCKED labels are not current |

## One synchronized TODO matrix

| Phase / task | Status | Evidence | Remaining work | Runtime required? |
|---|---|---|---|:---:|
| 2.13A request compatibility | DONE IN SOURCE / STAGING PENDING | identity helper/services/tests | stage old/new/mixed cases | yes |
| 2.14A Agenda compatibility | DONE IN SOURCE / STAGING PENDING | aggregate helper/guards | inventory and duplicate/orphan checks | yes |
| 2.14B AgendaEvent cutover | DONE IN SOURCE / STAGING PENDING | sole canonical class/imports | stage workflow/request/report/rollback | yes |
| Ramadan UX/period/guidance/form/reference/dashboard | DONE IN SOURCE / STAGING PENDING | source artifacts/tests | migrate, seed, PHPUnit, browser/RTL/lifecycle | yes |
| `subject_target_groups` plan | SUPERSEDED | generalized pivot and negative tests | never recreate | no |
| Agenda request pair cutover | DONE IN SOURCE / STAGING PENDING | sole canonical model definitions and dual-read compatibility | stage mixed identity, binding and workflow regressions; no backfill | yes |
| Monthly request pair cutover | DONE IN SOURCE / STAGING PENDING | canonical models and writers; legacy workflow dual-read | staging verification only | yes |
| MonthlyActivity compatibility preparation | DONE IN SOURCE / STAGING PENDING | aggregate helper, workflow/report dual-read, correspondence resolver and duplicate guard | Phase 2.18 model move only after staging | yes |
| PostExecutionVerification cutover | DONE IN SOURCE / STAGING PENDING | focused audit identity helper; sole canonical model | stage legacy/canonical audit visibility and Monthly/Ramadan regressions | yes |
| Monthly need transaction normalization | PARTIAL / DEFERRED | shared catalogue, legacy JSON transactions | separately authorized business/data migration | yes |
| Phase 2.6/general runtime gate | NOT STARTED here | missing vendor and DB | consolidated staging run later | yes |

## Remaining source work and exactly one next slice

The aggregate/request/verification namespace cutovers are complete in source. Monthly transaction normalization remains separately authorized work and is not part of identity cleanup.

**Recommended next slice: final Events identity cleanup and staging cutover runbook preparation.** Preserve all historical compatibility identities until the staged inventory and observation gates are complete.

Do **not** include MonthlyActivity, PostExecutionVerification, backfills, broad morph maps, dual writes, workflow/business changes, or Ramadan features.

## Consolidated runtime/staging debt

**SOURCE STATUS:** Agenda cutover and reviewed Ramadan implementation are complete as described; remaining namespace work is ordered above.

**STAGING VERIFICATION STATUS:** pending. Restore lock-authoritative dependencies and a configured database; run migration status/migrations and identifier inspection, relevant PHPUnit, reference preflight/staging seeder/idempotency, identity duplicate/orphan inventories, full Ramadan lifecycle/version copy, branch/permission checks, and browser/RTL checks. This audit does not create the final runbook or claim the period error was observed as resolved.


## 2026-09-20 Ramadan Admin configuration slice

DONE IN SOURCE / STAGING PENDING. Ramadan periods now carry explicit Hijri years and Iftars have a nullable, backfill-aware period FK. One model owns active lookup/activation. A focused super-admin area manages annual periods, immutable versioned guidance, and mobilization methods. Legacy setting readers were removed while keys remain preserved for deployment inventory. Reference seeders preserve administrator changes; demo orchestration stays separate. The next source slice returns to the Annual Agenda request-model namespace cutover after staging debt is recorded.


## 2026-09-20 Ramadan period proposal and gift simplification

DONE IN SOURCE / STAGING PENDING. Gift/shield types are again the three-value `RamadanIftarGift` structural enum; the recent unshipped reference table/model/seeder/Admin CRUD were removed. Native IntlCalendar/ICU Umm al-Qura calculation now produces stored proposals only. Operational dates remain Admin-reviewed `start_date`/`end_date`; confirmation and activation are separate, and resync preserves confirmed values. Admin and `ramadan:sync-period` share one service; no scheduler or external API was added. The next source slice remains the Annual Agenda request-model namespace cutover.


## Phase 2.15 — Annual Agenda request model namespace cutover

DONE IN SOURCE / STAGING PENDING. `AnnualAgendaEditRequest` and `AnnualAgendaDeleteRequest` now have one production definition each under `App\Modules\Events\Models`. The exact removed `App\Models` names remain only as compatibility strings. Dynamic workflow reads/reuses both identities, resolves both to canonical installed models, creates only canonical Annual Agenda request workflow identities, and still detects mixed duplicates. Monthly request installed/writer identities remain legacy. Request-row `entity_type` remains the AgendaEvent aggregate identity, with no schema migration, data backfill, dual write, or business-rule change. Phase 2.16 is next.

## Phase 2.16 — Monthly Plan request model namespace cutover

DONE IN SOURCE / STAGING PENDING. `MonthlyPlanEditRequest` and
`MonthlyPlanDeleteRequest` now exist only under `App\Modules\Events\Models`.
Legacy and canonical workflow identities dual-read and resolve to the canonical
installed models; new workflow writers are canonical and mixed duplicates still
fail explicitly. Request-row `entity_type` remains
`App\Models\MonthlyActivity`. `MonthlyActivity`, PostExecutionVerification, and
the official-correspondence morph boundary remain unchanged. No schema migration,
historical backfill, dual write, or Ramadan change was made. The next source
slice is MonthlyActivity aggregate identity compatibility preparation; it must
prepare persisted readers/writers before any MonthlyActivity move.


## Phase 2.17 — MonthlyActivity aggregate identity compatibility preparation

DONE IN SOURCE / STAGING PENDING. `EventAggregateIdentity` recognizes legacy and future canonical MonthlyActivity identities while installed/current write identity remains `App\Models\MonthlyActivity`. Workflow, reports, action-log readers, request aggregate lookup, and official correspondence now dual-read. Correspondence inverse resolution is focused and no global morph map was added; writes find before create and reject mixed duplicates. Stable `monthly_activity` subject aliases remain unchanged. No model move, migration, backfill, PostExecutionVerification, or Ramadan change occurred.

## 2026-09-20 Admin navigation and lookup-management UX slice

DONE IN SOURCE / STAGING PENDING. Ramadan dashboard visibility is now a persisted, default-enabled setting and an additional gate ahead of Ramadan period/metric work. The main header has an application-timezone live clock plus Gregorian and display-only ICU Umm al-Qura Hijri dates; approved `RamadanPeriod` dates remain the sole operational authority. Shared and Ramadan-specific lookup management is inventoried in `docs/admin-reference-data-and-navigation-audit.md` and grouped in the Admin navigation. No Events aggregate identity, MonthlyActivity, PostExecutionVerification, backfill, approval, monitoring, closure, period-rule, or guidance-acknowledgement change was made. The next source slice remains Phase 2.18 — MonthlyActivity namespace cutover.

## Phase 2.18 — MonthlyActivity namespace cutover

DONE IN SOURCE / STAGING PENDING. `MonthlyActivity` now has one installed model at
`App\Modules\Events\Models\MonthlyActivity`; its removed `App\Models` name is
retained only as a historical compatibility identity. New workflow,
workflow-action, official-correspondence, Monthly request aggregate, audit, and
class-derived metadata writes are canonical. Workflow, Monthly request, and
official-correspondence readers still accept both identities, reuse a single
historical row, and reject mixed logical duplicates where creation is guarded.
The focused correspondence inverse resolver maps both stored identities to the
canonical model without a global morph map. Stable `monthly_activity` subject
aliases are unchanged. No migration, backfill, dual write, business-rule,
Ramadan, or PostExecutionVerification change was made. PostExecutionVerification
remains NOT CUT OVER.

## Phase 2.19 — PostExecutionVerification identity review and safe cutover

SOURCE COMPLETE — DIRECT CUTOVER COMPLETE / STAGING PENDING. The audit confirmed
that `audit_logs.entity_type` is the sole persisted PostExecutionVerification
self-FQCN boundary and that the existing focused identity helper already accepts
both historical and canonical values. The sole model moved to
`App\Modules\Events\Models\PostExecutionVerification`, and new verification
audit writes now use the canonical identity. Monthly ownership remains the
direct `monthly_activity_id` foreign key; Ramadan ownership remains the direct
`monitoring_report_id` foreign key with stable `ramadan_iftar` parent subject and
detail aliases. No verification workflow identity, polymorphic model identity,
route binding, queued model serialization, migration, backfill, dual write, or
business-rule change was introduced. No Events-owned identity-sensitive models
remain under `App\Models`.
