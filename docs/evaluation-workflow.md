# Follow-up and activity evaluation workflow

## Architecture and access

The implementation extends the existing `MonthlyActivity`, branch assignment, Spatie Permission, audit-log, localization, and Blade architecture. It intentionally retains the legacy evaluation-response tables for backward compatibility. A follow-up officer has one primary/assigned branch and every query and policy action is scoped to it. Evaluation officers use global read permissions and cannot verify, submit, change visibility, or manage forms.

## Workflow

Approved activity plan → activity executed → post-execution completion submitted → follow-up officer verifies every submitted leaf value → incorrect values retain the original and record a typed correction → follow-up officer answers the active configurable form → the server calculates a weighted score out of 10 in one database transaction → activity becomes `evaluated` / `Evaluated` → branch relations officer selects `branch_only` or `authorized_users` → evaluation officer can always review the result.

Verification decisions store the field path, label/type, immutable submitted JSON value, corrected JSON value, decision, note, user, and timestamp. Audit logs record verification, evaluation, and visibility changes. Evaluation answers snapshot bilingual text, range, weight, score, and weighted score so historical results remain readable after configuration changes.

## Permissions

- Follow-up: `evaluation.view_branch`, `evaluation.submit_branch`, `post_execution.view_branch`, `post_execution.verify_branch`, `branches.view.own`.
- Evaluation officer: `evaluation.view_all`, `post_execution.view_all`, `branches.view.all` (read-only).
- Branch relations officer: `evaluation.view_branch`, `evaluation.visibility.manage`.
- Main relations manager/admin: global view plus form/question/visibility management; `super_admin` retains all permissions.

## Score

Each score is normalized to 1–10 using its configured minimum/maximum. The final value, consistently stored to two decimals, is `sum(normalized score × weight) / sum(weights)`. Required active questions must be answered. A unique constraint prevents duplicate final evaluations.

## Setup and development accounts

The new seeders are intentionally **not** registered in `DatabaseSeeder`. Running the legacy `php artisan db:seed` therefore keeps the previous seeding flow unchanged. After `php artisan migrate`, run only the independent evaluation seeders in order:

1. `php artisan db:seed --class=EvaluationWorkflowPermissionSeeder`
2. `php artisan db:seed --class=FollowupOfficerUsersSeeder`
3. `php artisan db:seed --class=EvaluationOfficerUsersSeeder`
4. `php artisan db:seed --class=ActivityEvaluationFormSeeder`

The seeders are additive and non-destructive: they use `firstOrCreate` and only assign roles, permissions, or branches when the corresponding record was created in that same run. They never call `updateOrCreate`, `syncRoles`, `syncPermissions`, detach assignments, remove roles, or overwrite an existing record. Re-running them therefore preserves manual edits made after the first run, including changed user fields and removed role/permission assignments. Existing follow-up branch assignments are detected and left untouched. The user seeders create only missing `followup.branch.{branch-slug}@zaha.local` accounts and `evaluation.officer.1@zaha.local` through `.3`. Local password: `Password123!`; rotate or disable these accounts outside development.

Run the suite with `php artisan test`. Existing JSON completion data is synchronized lazily into verification records, avoiding a destructive production backfill and preserving legacy screens.
