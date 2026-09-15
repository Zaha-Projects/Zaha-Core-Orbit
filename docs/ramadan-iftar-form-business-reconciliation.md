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
4. Targets: generalized `subject_target_groups` with `TargetGroup` and `BeneficiarySegment`.
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
