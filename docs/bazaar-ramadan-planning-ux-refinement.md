# Bazaar and Ramadan planning UX refinement

## Persistence decisions

- Bazaar inside locations show only the named place; the request clears location details and map URL for inside locations. Outside fields remain available. Stored inside details are omitted on show, without a global schema deletion.
- Bazaar's original enum contains only `individual` and `organization`; `local_community` was wording, not an allowed stored renter code in the repository schema. New choices are فردي / مؤسسة / جمعية. One additive nullable `bazaar_tables.renter_entity_type` column retains the institution/association distinction without altering the existing enum or shared reference data. Historical generic organizations remain explicitly unclassified; an unchanged historical reservation can keep that value, while new reservations must select a supported type. No database/runtime audit was performed.
- Bazaar colors now use the application brand, surface, text, muted, and border variables. The orange light/dark overrides were removed. Show/execution use the existing application Bootstrap theme; no business status colors or workflow actions were redesigned.
- General Iftar description is no longer collected, validated, or written by planning. Existing description remains in storage and is available as historical text in review.
- Supporting entity terminology is الجهة الداعمة. `individual` is added alongside existing `center` (displayed مؤسسة), `association`, and `local_community` codes. CommunityOrganization remains the shared untyped reference for individuals/institutions/associations; LocalCommunity remains separate. The entity type is owned by the Iftar, following the existing architecture.
- Existing `ramadan_iftars.contact_name` and `contact_phone` columns hold Iftar-specific overrides. Selection initializes them from the shared entity; planning never updates that entity. Approved-plan revision copying includes the overrides. References on edit use an authorized Iftar ID to derive the branch server-side; no arbitrary client branch ID is trusted.
- Inline reference creation collects type, name, phone and liaison name. An individual's liaison defaults to their own name, with no duplicate name input in the creation editor. Saving creates/selects the reference; later contact edits belong only to the Iftar.
- Primary target Other requires the existing selection `notes` field, hides/disables classification, and rejects submitted classifications server-side. Persistence clears classification for Other and clears Other notes for ordinary selections. Opposite-type classification remains for ordinary groups.
- Meal-item quantity is no longer collected or written. Meal-level planned quantity and actual quantity, derived Iftar meal totals, and KPI sources remain intact.
- The old “مرفقات / مكونات الطبق” control was text backed by item `notes`, not a file uploader or attachment relation. It is removed from input/validation/write paths; historical notes and quantities remain stored. No files or attachment data are deleted.
- Meal items use one `name` field labeled الصنف / الوصف (existing 255-character storage limit). The separate meal-description input is removed; new parent meal descriptions are initialized from item names, while historical parent descriptions and item notes are preserved internally/read-only. Review/show/execution display current item names. No destructive schema merge is used.
- Only the duplicate Ramadan calendar sidebar entry is removed. The calendar route/page remains.

## Reference picker diagnosis and fix

Static source inspection found that add-new availability was tied to search-result rendering/empty-search state, delayed AJAX responses were unguarded, and conditional switching reset picker editors. These state transitions made the creation flow dependent on transient search state. The create action is now always available in search mode. Explicit search/create/selected state and search revision invalidation prevent stale responses from changing the editor. Switching supporting entity type preserves editor state. Enter inside the editor saves the reference rather than submitting the Iftar; submitting an unfinished inline editor is blocked with an Arabic message. No timeout is used to keep the editor visible; the existing search debounce remains only for network efficiency.

This diagnosis is based on source inspection. The reported browser symptom was not reproduced because runtime/browser tests were explicitly excluded.

## Guidance authorization and versioning

The existing management authorization is super_admin. Edit links and GET/POST endpoints retain that restriction; Relations Officers and ordinary branch users cannot edit global guidance. The edit page preloads current title/content and clearly states that saving publishes a new version. Structured seeded content is presented as readable plain text in the editor; the original structured version remains unchanged.

Saving locks the Ramadan version set, checks the source version is still current, inserts max(version_number)+1, publishes/activates it, and deactivates the previous active version. Only activation metadata is changed on old versions. Their content, title, publication date, and acceptance records remain unchanged. Concurrent stale edits receive an Arabic validation error. The existing admin draft-edit/publish actions now also reload under the same lock so a draft cannot be edited after concurrent publication.

Existing user acknowledgements remain tied to their original version. The unchanged current-version acceptance service requires acknowledgement of the new active version for new planning/create/store. Existing Iftars retain their original accepted guidance; their edit/submission behavior is unchanged. No acceptance records are reassigned or deleted.

## Static regression review

Bazaar: branch derivation/isolation, public/private invitation and audience, days, target Other, execution-needs availability and Monthly parity, paper/electronic cards and channels, separate workforce/team section, table counts, planned/actual KPI capture, draft/submit, approval, execution, monitoring and closure were reviewed. Changes are restricted to requested location, renter labels/subtype, theme and display paths.

Ramadan: branch derivation/isolation, guidance acceptance, draft/submit, approval, execution, post-execution, monitoring, supervisor review and closure remain on their existing paths. Execution-needs and workforce logic is unchanged. Ordinary target classifications retain the opposite-type restriction. Contact overrides are copied to plan revisions. Only execution meal labels and new submission log wording changed outside the planning views; execution/KPI arithmetic is unchanged.

## Checks and deployment limitation

- Standalone Blade compilation with the installed Laravel compiler, followed by PHP lint: passed. The application was not booted and no database was contacted.
- PHP lint for every changed PHP file: passed using PHP 8.3.6.
- Requested `php8.2 -l`: unavailable (`php8.2: command not found`). No PHP 8.2 binary or Docker was available. Exact PHP 8.2 verification remains outstanding; PHP 8.3 lint is not represented as PHP 8.2 verification.
- `node --check` on three changed JavaScript assets and the Ramadan form inline script: passed.
- `git diff b677f3f --check`: passed. This includes work captured by the workspace commit during the session.
- Searches: old Ramadan heading, general description input, Bazaar local-community renter option, duplicate sidebar calendar, meal-item quantity input, meal component collection fields, raw English planning validation message, and published-guidance content mutation guards: passed.
- Broader “المجتمع المحلي” matches remain intentionally in Bazaar private-audience/distribution examples; they are unrelated to renter type and were preserved.
- New migration is additive only and has NOT been executed. Deploy it before using the new Bazaar subtype field.

NO PROTECTED PRODUCTION MIGRATION WAS MODIFIED

NO RUNTIME TESTS EXECUTED

## Files added

- `database/migrations/2026_10_19_000200_add_renter_entity_type_to_bazaar_tables.php`
- `docs/bazaar-ramadan-planning-ux-refinement.md`
- `resources/views/pages/events/ramadan/_supporting_entity.blade.php`
- `resources/views/pages/events/ramadan/guidance_edit.blade.php`

## Files changed

- `app/Modules/Events/Http/Controllers/Admin/RamadanGuidanceAdminController.php`
- `app/Modules/Events/Http/Controllers/Bazaar/BazaarController.php`
- `app/Modules/Events/Http/Controllers/Ramadan/RamadanGuidanceController.php`
- `app/Modules/Events/Http/Controllers/Ramadan/RamadanIftarController.php`
- `app/Modules/Events/Http/Controllers/Ramadan/RamadanReferenceController.php`
- `app/Modules/Events/Http/Requests/Bazaar/StoreBazaarRequest.php`
- `app/Modules/Events/Http/Requests/Ramadan/StoreRamadanIftarRequest.php`
- `app/Modules/Events/Models/BazaarTable.php`
- `app/Modules/Events/Models/RamadanIftar.php`
- `app/Modules/Events/Services/RamadanIftarChangeRequestService.php`
- `app/Modules/Events/Services/RamadanIftarPlanningService.php`
- `app/Modules/Events/Services/RamadanIftarSubmissionService.php`
- `public/assets/css/bazaar-form.css`
- `public/assets/css/bazaar-index.css`
- `public/assets/js/bazaar-form.js`
- `public/assets/js/ramadan-iftars.js`
- `public/assets/js/ramadan-reference-picker.js`
- `resources/lang/ar/bazaars.php`
- `resources/lang/ar/ramadan_iftars.php`
- `resources/lang/en/ramadan_iftars.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/pages/events/bazaars/_form.blade.php`
- `resources/views/pages/events/bazaars/_table_card.blade.php`
- `resources/views/pages/events/bazaars/show.blade.php`
- `resources/views/pages/events/ramadan/_form.blade.php`
- `resources/views/pages/events/ramadan/_planning_collections.blade.php`
- `resources/views/pages/events/ramadan/_reference_picker.blade.php`
- `resources/views/pages/events/ramadan/admin/index.blade.php`
- `resources/views/pages/events/ramadan/approvals/show.blade.php`
- `resources/views/pages/events/ramadan/execution.blade.php`
- `resources/views/pages/events/ramadan/guidance.blade.php`
- `resources/views/pages/events/ramadan/show.blade.php`
- `routes/web.php`
