# Ramadan Iftars UX and demo data enhancement

Date: 2026-09-15

Status: `SOURCE IMPLEMENTATION COMPLETE — STAGING VERIFICATION REQUIRED`

## Theme and assets

All `events.ramadan.*` routes receive the scoped `ramadan-module` body class and
`public/assets/css/ramadan-iftars.css`. The stylesheet supplies a restrained
green/gold Ramadan palette, crescent header accent, cards, status pills,
responsive needs/calendar grids, RTL-safe logical borders, accessible contrast,
and print rules. `public/assets/js/ramadan-iftars.js` owns execution-need toggles
and first-invalid-field assistance. No global theme or frontend framework was
changed.

## Browse and calendar

The index is a responsive card view showing the real title, branch, planned
date, expected attendance, supporting entity, planning/execution/monitoring
states, closure, and version. Existing permissions and model state continue to
gate actions. A Cards/Calendar control links to a dedicated route.

The calendar renders only the active administrative Ramadan start/end range. It
groups eager-loaded Iftars by `planned_date`, respects branch scope, provides a
mobile day-card layout, and links every event to its existing workspace. It does
not navigate arbitrary Gregorian months and presents an explicit empty state
when no active period exists.

## Ramadan period administration and validation

The existing `settings` architecture now owns four exact keys:

- `ramadan_period_year`
- `ramadan_period_start_date`
- `ramadan_period_end_date`
- `ramadan_period_is_active`

Super Admin site settings exposes these fields and validates end date at or
after start date. `RamadanPeriod` is the focused read contract. Create/edit
server validation rejects a planned date outside the active inclusive range
with a clear Arabic error. The form mirrors the range with `min`/`max`, but
server validation remains authoritative.

`RamadanPeriodSeeder` uses `firstOrCreate` for an explicit demonstration period
(2026-02-18 through 2026-03-19). It never overwrites administrator-edited
values; business logic depends only on settings, not on those seed literals.

## Guidance

`RamadanIftarGuidanceSeeder` publishes version 1 only when no Ramadan guidance
history exists. It never overwrites or adds a competing version after an
administrator has authored any version. The visible source heading is preserved
exactly as `تعليمات عامة لإفطارات رمضان 2025`; documentation notes that the
external uploaded reference says 2026.

The JSON content contains ten structured sections: الجهة المنفذة، الجهة
المستفيدة، المتطوعين، الوجبات، الكادر المرافق، فريق التنفيذ، موقع التنفيذ،
الموارد اللازمة، الهدايا، واعتبارات عامة. The redesigned view renders cards and
bullets with emphasis for safety/resources/general rules, retains a plain-text
fallback for historical versions, and prints without navigation/decorative UI.

## Execution-needs activation

All currently Ramadan-selectable canonical needs are optional because the
existing canonical definitions expose Ramadan selectability but no mandatory
metadata. The UI does not invent mandatory rules. Each need uses the existing
`is_required` boolean as an accessible switch; OFF disables and hides planning
details, while ON restores/shows them. The Common `subject_execution_needs`
contract remains unchanged and no Monthly JSON semantics are reused.

| Need | Type | Default | UI behavior |
|---|---|---|---|
| volunteers | Optional | Off unless stored | Switch and conditional details |
| official_correspondence | Optional | Off unless stored | Switch and conditional details |
| media_coverage | Optional | Off unless stored | Switch and conditional details |
| supplies | Optional | Off unless stored | Switch and conditional details |
| transport | Optional | Off unless stored | Switch and conditional details |
| maintenance_workers | Optional | Off unless stored | Switch and conditional details |
| gifts_shields | Optional | Off unless stored | Switch and conditional details |
| invitations | Optional | Off unless stored | Switch and conditional details |

## Forms and validation UX

Create/edit retain one non-wizard form and all existing planning collections.
Cards, icons, a compact section index, active-period notice, top-level error
summary, adjacent planned-date feedback, and automatic first-error focus improve
orientation without duplicating business validation in JavaScript. Existing
old input and edit collection population remain authoritative.

## Reference and demonstration bootstrap

`RamadanIftarDemoSeeder` calls existing Event reference and canonical need
seeders, then the focused period and guidance seeders. It does not join
production `DatabaseSeeder`. It fails clearly unless branch ID 23, an active
branch-23 user, published guidance, and the active Ramadan workflow already
exist. This prevents silent cross-branch or structurally invalid data.

The idempotent synthetic title keys `[DEMO-RAMADAN-01]` through
`[DEMO-RAMADAN-10]` are scoped with `branch_id = 23`. `updateOrCreate` updates
only those synthetic rows; nothing is truncated or deleted. Nine primary
scenarios cover draft, submitted, mid-workflow, approved, execution in progress,
execution completed, monitoring submitted, monitoring approved, and closed. A
tenth child plan demonstrates version 2 linked to the approved original. Dates
are distributed inside the configured period, including a multiple-event day.
Canonical Ramadan needs vary by scenario and completion state.

The seeder intentionally requires existing roles/users/workflow rather than
inventing security identities. Run the approved role/user/workflow seeders for
the environment first.

## Running focused seeders

```bash
php artisan db:seed --class=Database\\Seeders\\RamadanPeriodSeeder
php artisan db:seed --class=Database\\Seeders\\RamadanIftarGuidanceSeeder
php artisan db:seed --class=Database\\Seeders\\RamadanIftarDemoSeeder
```

Run only in local/staging demonstration environments. The demo seeder is not
part of production reference bootstrapping.

## Staging verification

Verify desktop/mobile RTL, every Ramadan page, Cards/Calendar switching,
period administration and range rejection, all guidance text and printing,
need toggles and hidden-field submission, create/edit errors, seeder idempotency,
branch 23 ownership, all workflow stages, execution, monitoring, closure,
version history, authorization, query counts, and browser console output.

```text
NO RAMADAN WORKFLOW OR APPROVAL SEQUENCE WAS CHANGED
NO RAMADAN MONITORING OR CLOSURE RULE WAS CHANGED
NO EXISTING RAMADAN HISTORY WAS DELETED OR REWRITTEN
NO MONTHLY EXECUTION-NEEDS STORAGE WAS REUSED FOR RAMADAN
RAMADAN DATES ARE ADMIN-CONFIGURABLE AND NOT HARD-CODED IN BUSINESS LOGIC
ALL DEMO IFTARS ARE SCOPED TO BRANCH_ID = 23
```

```text
CODEX RUNTIME VERIFICATION IS UNAVAILABLE
STAGING VERIFICATION IS REQUIRED
```
