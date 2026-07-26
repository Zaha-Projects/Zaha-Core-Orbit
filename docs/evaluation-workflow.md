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

All previously executed seeders are commented out in `DatabaseSeeder`, and only the four evaluation workflow seeders are active. After `php artisan migrate`, run all four together with `php artisan db:seed`, or run them independently in order:

1. `php artisan db:seed --class=EvaluationWorkflowPermissionSeeder`
2. `php artisan db:seed --class=FollowupOfficerUsersSeeder`
3. `php artisan db:seed --class=EvaluationOfficerUsersSeeder`
4. `php artisan db:seed --class=ActivityEvaluationFormSeeder`

The user seeders follow the existing `BranchStaffUsersSeeder` convention: deterministic `@zaha.test` emails, sequential phone numbers, `updateOrCreate`, and `syncRoles`. Follow-up accounts use `followup-officer.branch01@zaha.test` and continue sequentially per branch; evaluation accounts use `evaluation-officer01@zaha.test` through `evaluation-officer03@zaha.test`. Development credentials are intentionally omitted from this technical log.

Run the suite with `php artisan test`. Existing JSON completion data is synchronized lazily into verification records, avoiding a destructive production backfill and preserving legacy screens.

## Seeded user log

The seeders print this table after execution via Symfony Console. The rows below are generated from the configured branch records in `config/branches.php`; evaluation officers are global.

| Name | Email | Branch | Role Code | Role Name |
|---|---|---|---|---|
| مسؤول متابعة - المنصورة | followup-officer.branch01@zaha.test | مركز زها الثقافي - المنصورة | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - اربد طارق | followup-officer.branch02@zaha.test | مركز زها الثقافي - اربد طارق | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - عجلون | followup-officer.branch03@zaha.test | مركز زها الثقافي - عجلون | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - جرش | followup-officer.branch04@zaha.test | مركز زها الثقافي - جرش | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - المفرق | followup-officer.branch05@zaha.test | مركز زها الثقافي - المفرق | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - الرمثا | followup-officer.branch06@zaha.test | مركز زها الثقافي - الرمثا | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - المشارع | followup-officer.branch07@zaha.test | مركز زها الثقافي - المشارع | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - الرصيفة | followup-officer.branch08@zaha.test | مركز زها الثقافي - الرصيفة | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - الهاشمية | followup-officer.branch09@zaha.test | مركز زها الثقافي - الهاشمية | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - مأدبا | followup-officer.branch10@zaha.test | مركز زها الثقافي - مأدبا | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - ماعين | followup-officer.branch11@zaha.test | مركز زها الثقافي - ماعين | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - دير علا | followup-officer.branch12@zaha.test | مركز زها الثقافي - دير علا | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - الكرك | followup-officer.branch13@zaha.test | مركز زها الثقافي - الكرك | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - الطفيلة | followup-officer.branch14@zaha.test | مركز زها الثقافي - الطفيلة | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - معان | followup-officer.branch15@zaha.test | مركز زها الثقافي - معان | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - العقبة | followup-officer.branch16@zaha.test | مركز زها الثقافي - العقبة | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - غور الصافي | followup-officer.branch17@zaha.test | مركز زها الثقافي - غور الصافي | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - خلدا | followup-officer.branch18@zaha.test | مركز زها الثقافي - خلدا | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - أم عمر الفيصل | followup-officer.branch19@zaha.test | مركز زها الثقافي - أم عمر الفيصل | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - باب الواد | followup-officer.branch20@zaha.test | مركز زها الثقافي - باب الواد | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - طارق | followup-officer.branch21@zaha.test | مركز زها الثقافي - طارق | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - المستندة | followup-officer.branch22@zaha.test | مركز زها الثقافي - المستندة | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - الزهور | followup-officer.branch23@zaha.test | مركز زها الثقافي - الزهور | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - أبو علندا | followup-officer.branch24@zaha.test | مركز زها الثقافي - أبو علندا | followup_officer | مسؤول المتابعة |
| مسؤول متابعة - التقوى | followup-officer.branch25@zaha.test | مركز زها الثقافي - التقوى | followup_officer | مسؤول المتابعة |
| مسؤول التقييم 01 | evaluation-officer01@zaha.test | جميع الفروع | evaluation_officer | مسؤول التقييم |
| مسؤول التقييم 02 | evaluation-officer02@zaha.test | جميع الفروع | evaluation_officer | مسؤول التقييم |
| مسؤول التقييم 03 | evaluation-officer03@zaha.test | جميع الفروع | evaluation_officer | مسؤول التقييم |

## Follow-up workspace interface

The `followup_officer` sidebar is intentionally isolated from the shared role menu. Its final order is: Dashboard, Monthly Plans, Awaiting Evaluation, Previous Evaluations, User Directory, Profile. Other roles continue to use the existing shared sidebar.

The branch-scoped workspace uses reusable `MonthlyActivity` scopes for follow-up branch ownership, completed post-execution payloads, pending evaluations, completed evaluations, and the authenticated user's responsible relationship. The dashboard includes current-month statistics, workflow counts, urgent review actions, upcoming plans, recent evaluations, branch performance, and verification totals. No cross-branch comparison is shown.

### Follow-up routes

- `followup.dashboard`
- `followup.monthly-plans`
- `followup.monthly-plans.show`
- `followup.awaiting-evaluation`
- `followup.evaluations.index`
- Existing protected workflow routes remain in use for verification, evaluation creation, and historical evaluation details.

### Follow-up permissions

- `followup.dashboard.view`
- `followup.monthly_plans.view`
- `followup.post_execution.view`
- `followup.post_execution.verify`
- `followup.evaluations.create`
- `followup.evaluations.view`
- `users.directory.view`
- `profile.view`

The “My Relationship” filter uses the existing activity ownership data (`created_by` and `responsible_party`) rather than introducing a duplicate relationship table.

## Visual identity and unified calendar

All follow-up and evaluation routes load the shared evaluation visual-identity stylesheet. The primary surface and action gradient is `linear-gradient(135deg, #00a9c4, #2fc9e2)`, with matching accessible dark and soft cyan tokens.

`followup.monthly-plans` now delegates to the production monthly-plans index used by `role.relations.activities.index`. This guarantees the same FullCalendar implementation, navigation, filters, status rendering, assets, and data-loading behavior instead of maintaining a duplicate calendar. Follow-up officers remain branch-scoped by the existing branch visibility service; evaluation officers use their global monthly-plan permission. Filters and previous/next navigation preserve the follow-up URL.

## Evaluation officer dashboard and navigation

The evaluation dashboard now presents global, real-data KPIs, verification distribution, evaluation completion rate, latest and low-scoring evaluations, branch performance, monthly score trends, and pending evaluations by branch. Aggregate queries remain branch-scoped automatically when the viewer does not hold `evaluation.view_all`.

For `evaluation_officer`, the sidebar keeps the standard monthly-plans link (`/dashboard/relations/monthly-activities`) and suppresses only the duplicate `?scope=all_branches` link. The underlying global read permission is unchanged; this is a navigation cleanup rather than a reduction in authorized evaluation visibility.
