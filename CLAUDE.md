# CLAUDE.md

## Movement module — plan discipline

Applies to all Movement/transport work (`app/Modules/Movement`, `movement_*` tables, movement permissions/roles, `routes/movement.php`, transport code) on branch `movement-module`. Run the `movement-plan-guard` skill before writing code and before reporting completion.

**Source of truth.** `docs/movement-module-implementation-plan.md` ("the plan") is authoritative. Code must match the plan, not the other way round.

**Phase boundary.** Implement only the current phase's "In scope". Never create files, tables, routes, permissions, config keys or UI belonging to a later phase. No empty placeholders.

**Change protocol.** For any deviation — scope, schema, naming, order, behaviour, an inconsistency found in the plan, or a needed decision — before writing code that depends on it:
1. stop;
2. describe the conflict with plan section numbers;
3. propose the exact plan edit;
4. apply it as a new "Revision N" entry at the top of the plan, listing each change;
5. get explicit owner approval, unless the owner already approved that exact change in the prompt.

Never diverge silently and never "fix it later".

**Decisions.** A §22 recommendation is never an approval. Implement only decisions marked "Approved" in §22. If a phase needs an open decision, stop and ask.

**Hard rules (from the plan).**
- Never edit historical migrations.
- No `role:` middleware in the module; use permissions/Gate.
- No branch isolation (R5).
- No demo or fake seed data.
- All datetimes UTC through `MovementTime`.
- Categorise every new migration per §18.0.
- Take row locks in the §5.2 order.

**Database safety.**
- Never run migrations, seeders or `RefreshDatabase` tests against the configured default DB connection.
- Run DB tests only in disposable containers or isolated in-memory SQLite.
- Never touch production or shared databases.

**Git.** Do not commit, push or change branches unless told to; the owner commits.

**Toolchain.** Use `php8.2`; the default `php` CLI is 7.4 and cannot parse this codebase.

**End of each phase.** Report:
- files changed;
- plan sections satisfied;
- tests and where they ran;
- anything not verified;
- plan revisions made.
