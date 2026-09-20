# Ramadan Admin configuration and reference data

## Decision

`event_guidance_versions` remains the single versioned guidance store. Published versions are immutable; publishing a draft transactionally archives the previous current version without changing acknowledgements. `RamadanIftarGuidanceSeeder` uses its source hash only to provide initial content and never displaces an administrator-created current version.

`ramadan_periods` was already normalized. The forward 2026-09-20 migration adds the missing explicit `hijri_year` and nullable `ramadan_iftars.ramadan_period_id`. The existing unique `year` is the Gregorian annual identity. The model is now the sole lookup contract (`current`, `contains`, `activate`); the similarly named settings helper was removed. Activation locks period rows and deactivates the previous active row. No destructive period delete is exposed.

Legacy `ramadan_period_*` and `ramadan_default_year` settings are preserved for deployment safety but are no longer application readers. The period seeder imports a complete legacy four-key set only when its year is absent. A partial set fails visibly. Existing Iftars are backfilled only when their planned date unambiguously falls in a stored period; unmatched history remains nullable/readable. New and date-changed Iftars receive the server-selected active period.

## Admin area

`/dashboard/events/ramadan/admin` is a super-admin area with:

- **Settings / periods:** list, create, edit, activate, deactivate; Gregorian/Hijri years and dates; newest first.
- **Settings / guidance:** current version, history, draft creation/edit, publish, archive. Published content is immutable.
- **Mobilization methods:** list, create, edit labels/order, activate/deactivate. Codes are immutable after creation.
- **Gift/shield types:** manages the existing Ramadan-specific coded reference table; codes are immutable after creation.

## Reference classification

| Concept | Classification | Administration decision |
|---|---|---|
| Ramadan periods, guidance | RAMADAN-SPECIFIC configuration | dedicated Settings sections |
| Mobilization methods | RAMADAN-SPECIFIC lookup | dedicated tab |
| Ramadan gift/shield types | RAMADAN-SPECIFIC lookup | manage the existing table; no duplicate catalogue |
| Host types | STRUCTURAL ENUM | stable branching/validation meanings; no arbitrary CRUD tab |
| Target groups, beneficiary segments, execution needs, monitoring methods | SHARED Events reference | no duplicate Ramadan tables; existing applicability remains authoritative |
| Organizations and local communities | SHARED branch-owned operational reference | not duplicated |
| Iftars, attendees, meals, teams, supplies, gifts, programs, volunteers | TRANSACTIONAL | never treated as reference data |

Inactive lookups remain related and displayable historically. New forms query active values and edit forms include their already-selected inactive value.

## Seeder ownership

`RamadanReferenceDataSeeder` is the production/reference bootstrap and does not include demo Iftars. Period, guidance, mobilization and other reference seeders insert missing stable identities without overwriting administrator edits. `RamadanIftarStagingSeeder` remains the explicit opt-in wrapper that adds branch-23 demo data.

## Staging checks

Run the forward migration, inspect legacy settings and nullable unmatched Iftars, verify one-active-period switching, guidance concurrency/immutability and new acknowledgement requirement, inactive historical lookup display, calendar/dashboard/create behavior, seeder reruns, authorization, and RTL/browser usability before release.
