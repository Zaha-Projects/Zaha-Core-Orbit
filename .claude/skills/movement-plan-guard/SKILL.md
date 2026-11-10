---
name: movement-plan-guard
description: Mandatory checklist for any work touching the Movement/transport module — app/Modules/Movement, movement_* tables or migrations, movement permissions or roles (movement_manager, movement_driver, transport_officer, movement_editor, movement_viewer, movement.* permissions), routes/movement.php, transport controllers/views, or docs/movement-module-implementation-plan.md. Run before writing code and again before reporting completion.
---

# Movement plan guard

The plan is `docs/movement-module-implementation-plan.md`. It is the source of truth; this skill only checks against it. Rules live in `CLAUDE.md` → "Movement module — plan discipline".

## Before writing code

1. **Branch.** `git branch --show-current` must print `movement-module`. Otherwise stop.
2. **Phase.** Identify the current phase in plan §19. Quote its "In scope" and "Out of scope" lists verbatim in your working notes.
3. **Authorisation.** For each intended change, cite the plan section that authorises it. If none does, follow the change protocol:
   - stop and describe the conflict with section numbers;
   - propose the exact plan edit and record it as a new "Revision N" entry at the top of the plan;
   - wait for owner approval, unless the owner already approved that exact change.
4. **Decisions.** Every §22 decision the phase relies on must have status "Approved". A recommendation is not an approval. If one is open, stop and ask.
5. **Phase boundary.** Confirm nothing belongs to a later phase: no files, tables, routes, permissions, config keys, UI or empty placeholders.

## Before reporting completion

6. **Re-diff against the plan.**
   - Every in-scope item is done.
   - Every acceptance criterion has a test, or an explicit "not verified" note with the reason.
   - No unapproved deviation exists, and all plan revisions are recorded.
   - Each DB test ran only in a disposable container or isolated in-memory SQLite, never against the default connection.
7. **Report:** files changed, plan sections satisfied, tests and where they ran, items not verified, plan revisions made.
