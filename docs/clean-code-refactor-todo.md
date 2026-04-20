# Clean Code Refactor TODO (CSS/JS Separation)

## Objective
Move page-specific inline `<style>` and `<script>` blocks from Blade templates into dedicated asset files under `public/assets/css` and `public/assets/js`, then load them with `asset()` from each page.

## Completed in this branch
- [x] `resources/views/pages/monthly_activities/lookups/admin.blade.php`
  - Moved page CSS to: `public/assets/css/lookups-admin.css`
  - Kept Blade focused on markup/forms only.
- [x] `resources/views/pages/monthly_activities/approvals/index.blade.php`
  - Moved page CSS to: `public/assets/css/monthly-approvals.css`
  - Moved page JS to: `public/assets/js/monthly-approvals.js`
  - Page now loads assets via `asset()`.
- [x] `resources/views/pages/agenda/events/_form.blade.php`
  - Moved page CSS to: `public/assets/css/agenda-events-form.css`
  - Moved page JS to: `public/assets/js/agenda-events-form.js`
  - Replaced inline translation bindings with form-level `data-*` labels consumed by JS.
- [x] `resources/views/pages/agenda/events/index.blade.php`
  - Moved page CSS to: `public/assets/css/agenda-events-index.css`
  - Moved calendar/view-toggle JS to: `public/assets/js/agenda-events-index.js`
  - Kept server data in JSON script tags and `data-*` attributes only.

## Remaining pages with inline CSS/JS (next steps)
### Agenda module
### Monthly activities module
- [x] `resources/views/pages/monthly_activities/activities/_form.blade.php`
  - Moved page CSS to: `public/assets/css/monthly-activity-form.css`
  - Moved page JS to: `public/assets/js/monthly-activity-form.js`
- [x] `resources/views/pages/monthly_activities/activities/index.blade.php`
  - Moved page CSS to: `public/assets/css/monthly-activities-index.css`
  - Moved page JS to: `public/assets/js/monthly-activities-index.js`
- [x] `resources/views/pages/monthly_activities/activities/show.blade.php`
  - Moved page CSS to: `public/assets/css/monthly-activity-show.css`
- [x] `resources/views/pages/monthly_activities/activities/edit.blade.php`
  - Moved page JS to: `public/assets/js/monthly-activity-edit.js`

## Coding conventions for migration
- Keep business rules in controllers/services; keep Blade for markup only.
- Prefer `data-*` attributes for server-provided values consumed by JS.
- No inline `<style>` or `<script>` unless absolutely required for dynamic bootstrapping.
- For compatibility, avoid Blade short directives that leak in older runtimes (`@checked`, `@selected`) in critical forms.
- Ensure each new asset is loaded only on its page to avoid global bloat.

## Verification checklist per migrated page
1. `php -l <blade file>`
2. Confirm no remaining inline blocks in migrated page:
   - `rg -n "<style>|<script" <blade file>`
3. Confirm no `@checked/@selected` leakage where applicable:
   - `rg -n "@checked|@selected" <blade file>`
4. Visual smoke test for form actions, toggles, and modals.
