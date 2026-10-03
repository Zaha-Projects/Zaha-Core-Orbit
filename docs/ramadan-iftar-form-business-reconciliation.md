# Ramadan Iftar form and execution-needs business reconciliation

## Decision summary

The prior form rendered a canonical execution-needs switch and then rendered team, supplies, and gifts again as independent sections. This allowed selection and detail state to diverge. The reconciled contract uses one `ExecutionNeedType` catalogue, one `SubjectExecutionNeed` selection, and an existing detail model only inside the selected need:

| Need | Selection | Detail storage | Ramadan rule |
|---|---|---|---|
| `execution_team` | `subject_execution_needs` | `execution_teams` / `execution_team_members` | mandatory |
| `supplies` | `subject_execution_needs` | `event_supplies` | optional |
| `gifts_shields` | `subject_execution_needs` | `ramadan_iftar_gifts` | optional |
| `volunteers` | `subject_execution_needs` | `subject_volunteer_requirements` | optional |

Monthly storage is unchanged. The catalogue flags only control where a type is available and mandatory.

## Creation and guidance gate

The create route resolves the current active, published `EventGuidanceVersion`. It redirects to guidance until the authenticated user accepts that exact version. Acceptance is written to `event_guidance_acknowledgements` (`user_id`, `event_guidance_version_id`, `acknowledged_at`) and is also bound to the current browser flow. Publishing a newer active version therefore requires a new acknowledgement. The Iftar keeps its immutable accepted version/time snapshot.

## Form contract

1. Guidance acknowledgement state.
2. Basic information: location, Ramadan date, supporting entity, relations officer.
3. Attendance source: `host_type`; organization/center maps to `CommunityOrganization`, local-community maps to `LocalCommunity` plus `MobilizationMethod` and `ramadan_iftar_attendees`.
4. Targets: generalized `event_target_group` through `SubjectTargetGroup` with `TargetGroup` and `BeneficiarySegment`.
5. Meals: `ramadan_iftar_meals` and items. `planned_meals_count` is derived from meal quantities and is not independently posted.
6. Execution needs: canonical selection plus conditional team/supply/gift details.
7. Program segments: `ramadan_iftar_program_segments`.
8. Volunteer requirements: `subject_volunteer_requirements`.

`branch_id` is absent from create/edit markup. The request derives it from the bound Iftar on edit or the authenticated user's branch/scoped branch on create. Posted branch input is rejected. Organization and community IDs are checked against that branch.

## Transaction and synchronization

`RamadanIftarPlanningService` keeps core and all child planning changes inside one database transaction. It normalizes mandatory and optional needs before syncing. Disabled supplies/gifts synchronize to no planned detail rows, while actual/history-bearing rows retain the existing deletion guards. Owned child IDs are checked before update. Repeated saves update by owned IDs rather than duplicating children.

## Version-copy boundary

`RamadanIftarChangeRequestService` copies attendance-source core fields, attendee planning rows, targets, meal plans/items, selected needs, team/member plans, supply plans including basic availability, gift plans/type, program plans, and volunteer plans. It resets execution actuals, confirmations, monitoring, completion, approval, and closure state.

## Schema changes

Forward migration `2026_09_15_000100_reconcile_ramadan_iftar_business_form.php` adds:

- `mandatory_for_monthly` and `mandatory_for_ramadan` to the existing catalogue.
- `gift_type` to the existing Ramadan gift detail table.
- focused version-bound `event_guidance_acknowledgements` storage.

Existing `is_monthly_activity` and `is_ramadan_iftar` columns remain the applicability flags; no parallel catalogue or attendance table was created.

## Staging bootstrap

Run after normal schema, branch, role, permission, and workflow bootstrap:

```bash
php artisan db:seed --class=RamadanIftarStagingSeeder
```

The orchestration seeds Events reference data, canonical need metadata, mobilization methods, the initial active period when keys are absent, published guidance, users, acknowledgement, and branch-23 demo Iftars. Existing administrator period keys are never overwritten. If administrators intentionally configured an inactive/invalid period, staging must correct it rather than the seeder silently overriding policy.

## Required staging checks

Run migrations and PHPUnit; verify guidance redirect/acceptance/new-version behavior, branch tampering, cross-branch IDs, both attendance modes, repeatable rows, mandatory team, optional supplies/gifts, edit idempotency, version copy, period boundaries, demo idempotency, and all lifecycle/approval/monitoring regressions. Runtime results are not claimed from Codex.


## Hard-review clarifications

- `SubjectTargetGroup` is bound to the Phase 2.3 generalized `event_target_group`; `subject_target_groups` remains abandoned and must not be recreated.
- Guidance acknowledgement is authoritative per user and exact published version. The session records only the version actually presented while accepting, preventing a stale-page POST; subsequent creates for the same current version rely on persistent acknowledgement.
- Host codes are exact: `association` (جمعية), `center` (مركز), and `local_community` (مجتمع محلي). Association/center use branch-owned `CommunityOrganization` (`name`, `contact_name`, `contact_phone`, `location_name`, address/map); local community uses branch-owned `LocalCommunity`, `MobilizationMethod`, and attendee rows.
- Meal-item `notes` represents textual meal accompaniments/components, not file uploads. Restaurant name/contact are columns on `ramadan_iftar_meals`.
- The current schema and UI permit multiple execution-team rows per Iftar; at least one is mandatory.

### Period bootstrap precedence

| Existing state | Seeder behavior |
|---|---|
| valid active admin configuration | preserve |
| complete inactive admin configuration | preserve intentional inactive state; demo reports actionable failure |
| missing keys | create explicit active demo defaults |
| partial keys | fill only missing keys; never overwrite supplied values; invalid result fails clearly |

## Source consistency hard review

### Planning-copy boundary

| Planning concept | Copied to N+1 | Execution/actual state excluded |
|---|---|---|
| attendance source core fields | yes | actual attendance/date excluded |
| local-community attendee contacts | yes | `attended`, `checked_in_at` excluded |
| targets | yes | `actual_count` excluded |
| meals/items | yes | actual quantity, rating excluded |
| execution needs | yes | status, actual details, completion excluded |
| teams/members | yes | actual member count, task confirmation excluded |
| supplies | yes | planned availability copied; actual quantity/availability excluded |
| gifts/shields | yes | actual quantity excluded |
| program segments | yes | reset to planned; actual notes excluded |
| volunteer requirements | yes | actual count/status excluded |
| workflow/monitoring/closure | no | all excluded |

### Demo idempotency

All created children use stable natural keys under the stable demo Iftar: attendee name, target type, meal description, meal item name, execution-need type, team name, team user, supply name, gift description, program name, volunteer gender, workflow identity, monitoring method, guidance user/version, and branch/title for the version child. The demo creates no workflow logs or monitoring verification rows. Reruns update these rows and do not append them.

### Dashboard contract

The general dashboard panel requires an active Ramadan period and `ramadan_iftars.view` (or super admin). Normal users are constrained by `scopedBranchIds`; broad users follow `branches.view.all`. One conditional aggregate query computes bounded lifecycle metrics and one eager-loaded query returns at most five upcoming Iftars. No demo branch constant participates in dashboard queries.

## Form UX and inline-reference contract (2026-09-20)

Create and edit continue to share `_form.blade.php`, but the planning experience
is now organized into numbered, navigable cards for core data, host/attendance,
targets, meals, execution needs, program/volunteers, and final review. A summary
explains failed validation, invalid controls retain row-level messages and old
input, invalid cards are visibly marked, and the first invalid control is focused
when practical. Conditional host and need panels disable hidden inputs so
inactive sections are not validated accidentally; existing server-side create/edit
rules and inactive historical-reference allowances remain authoritative.

Relations Officer reference pickers retain search, select, and create. Both
`CommunityOrganization` and `LocalCommunity` quick creation require only `name`
and `contact_phone`. Contact name, location name, and address are optional behind
“إضافة تفاصيل إضافية”. Google Maps URL is deliberately absent from quick-create
validation and payloads; its database column and the full Iftar/Admin forms remain
unchanged. Branch ownership is server-derived, search/create stay branch-scoped,
and normalized same-branch duplicates (whitespace, case, and Arabic tatweel) are
rejected with an Arabic select-the-existing-record message. Successful creation
auto-selects the new record and closes the editor.

Execution needs use responsive selection cards showing an icon, name,
description when available, mandatory/optional badge, current enabled state, and
the relevant detail form inside the selected card. Mandatory execution team is
shown as `إلزامي`, permanently enabled, and cannot be deselected. Inactive
historical selections remain in the existing read-only historical-needs display.

The form palette is scoped to `.ramadan-module`: Light Mode uses neutral page
backgrounds, white surfaces, dark text, and restrained gold emphasis; Dark Mode
uses distinct charcoal/navy surfaces, warm gold, off-white text, readable muted
text, and visible focus/error states. Cards collapse to one column on mobile,
actions remain reachable, RTL is preserved, and reduced-motion behavior remains
in force. No workflow, approval, monitoring, closure, persistence, period,
guidance, branch, or versioning rule changed.
