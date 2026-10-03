# Ramadan Admin configuration and reference data

## Current Admin boundary

`/dashboard/events/ramadan/admin` is restricted to super administrators and contains only:

- **Settings / Ramadan periods:** manual draft creation, local proposal sync, review, adjustment, confirmation, activation and deactivation.
- **Settings / guidance:** version history, draft creation/edit, publication and archive; published content is immutable.
- **Mobilization methods:** create, edit labels/order, activate and deactivate; codes are immutable after creation.

Shared Events catalogues remain in shared administration. Gift/shield types are not reference data.

## Gift/shield structural enum

`RamadanIftarGift` is the single contract. Its constants and `types()` return exactly `gifts`, `shields`, and `both`. There is no gift-type model, table, seeder, route, controller or Admin tab. `ramadan_iftar_gifts.gift_type` remains the stored transaction value.

## Automatic proposal calculation

`RamadanPeriodCalculator` uses native PHP `IntlCalendar` with ICU's `islamic-umalqura` calendar. No Composer package or HTTP API is used. The optional sync capability requires ext-intl/ICU at runtime and reports `intl_umm_al_qura` as its source.

Calculated dates are proposals, not official dates:

- `suggested_start_date`, `suggested_end_date`, `calculation_source`, and `synced_at` record the calculation.
- `start_date` and `end_date` remain the only operational dates used by create validation, period ownership, calendar and dashboard.
- A missing year is created inactive and unconfirmed, with operational dates initially copied from the proposal for immediate review.
- Resync updates proposal metadata only. It never activates a period and never overwrites confirmed operational dates.
- “Use suggested dates” copies the proposal to operational inputs but leaves the period unconfirmed and inactive.
- Administrators may freely adjust dates, with ±1-day UI helpers. Saving changed operational dates resets confirmation.
- Confirmation is explicit. Activation rejects unconfirmed periods and transactionally deactivates the previous active period.

Workflow: **Sync → Review/adjust → Confirm → Activate**.

## Command and scheduling

Run `php artisan ramadan:sync-period {year?}`. With a year it refreshes that proposal; without one it selects the later of the current Gregorian year and the year following the newest stored period. The command uses the same sync service as Admin and never confirms or activates.

No scheduler was added. Scheduling remains an optional future convenience because annual Admin/command sync is sufficient and silent calendar-state changes are undesirable.

## Seeder and historical policy

`RamadanPeriodSeeder` remains initial fallback/legacy-import data only and uses create-if-missing behavior. Operational annual maintenance belongs to Admin sync or the command. Legacy settings remain preserved for deployment inventory. Historical periods and nullable unmatched historical Iftars remain preserved.

Inactive references remain readable historically. Reference bootstrap and opt-in branch-23 demo bootstrap remain separate.

## Staging checks

Verify ext-intl and the ICU Umm al-Qura calendar on the deployment PHP build; compare proposals with the organization-approved calendar; test migration rollback, sync/resync, confirmation concurrency, explicit activation, inactive historical display, calendar/dashboard/create behavior, command output, seeder reruns and RTL/browser usability.
