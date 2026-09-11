# Versioned Ramadan guidance acceptance

## Infrastructure decision

Repository inspection found no reusable immutable/versioned content or acceptance mechanism; the matching “terms” files are static dashboard-template examples only. This slice therefore uses the smallest Ramadan/Event-specific version source: `event_guidance_versions` and `EventGuidanceVersion` with the stable code `ramadan_iftar`. It is not a CMS and has no administration workflow or UI.

Each version stores a code, version number, title, content, active flag, publication timestamp, optional creator, and timestamps. `(code, version_number)` is unique. No production guidance is seeded because no approved wording exists in the repository.

## Current version and immutability

The current Ramadan version must be active, published, effective as of now, and have the Ramadan code. Resolution never relies on the latest row ID. Zero matching rows blocks guidance/planning with HTTP 503; more than one matching row throws rather than selecting an ambiguous version.

After a Ramadan Iftar references a version, its code, version number, title, content, publication time, and creator cannot be changed through the model. A new version must be created instead. Activation may move to a newer version, but the restrictive FK and model guard preserve accepted historical content and prevent deletion while referenced.

## Acceptance and create flow

`GET /dashboard/events/ramadan/guidance` resolves and displays the current version and records the presented ID in the server session. `POST /dashboard/events/ramadan/guidance/accept` accepts only that still-current presented version. The server records the accepted version, actor, and timestamp in the session, then redirects to Ramadan creation.

The create page redirects to guidance until the authenticated actor has accepted the current version. Store resolves the current version again and requires the same server-session acceptance. Client-supplied guidance IDs or timestamps are outside validation and ignored. A successful transactional create writes the resolved `guidance_version_id` and server acceptance timestamp, then consumes the session acceptance.

Normal edit/update never writes either acceptance field. If version 2 becomes active after an Iftar accepted version 1, that Iftar continues to reference version 1; reacceptance for existing drafts is deferred because no such rule is approved.

`RamadanIftar::hasValidGuidanceAcceptance()` provides the future submission readiness check: both acceptance fields must exist and the referenced version must have the Ramadan code.

## Compatibility and remaining gate

The implementation uses Laravel 8-compatible migrations, request validation, sessions, route groups, Eloquent events, and relationships already established in the repository. No workflow, permissions, Monthly Activities, monitoring, execution, or approval behavior changes.

Guidance is now resolved. Execution Needs normalization remains mandatory before Phase 1.10 submission and approval can resume.
