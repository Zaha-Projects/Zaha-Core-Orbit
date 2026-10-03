# Bazaar schema and lifecycle audit

**Audit date:** 2026-10-03
**Scope:** staging source only; static inspection; no runtime migration or application execution.

## Data model finding

The Bazaar aggregate is correctly normalized. `bazaars` contains branch, owner, schedule/location, planned and aggregate actual table counts, lifecycle status, decision actors/notes, and handoff timestamps. It has no embedded table JSON. `bazaar_tables` owns one numbered table per Bazaar and contains planned renter/contact/material/rent/liaison plus actual booking/renter/material/payment/notes. The composite unique key prevents duplicate table numbers per Bazaar. `bazaar_table_discounts` owns each discount request and its original value, discount calculation inputs, approved final value, reason, requester, decision actor/timestamps, status, and decision note.

Target groups and execution needs remain in the shared polymorphic event support tables, selected with the `bazaar` subject type. Organizations and liaison staff reuse `community_organizations` and `users`. `LocalCommunity` exists as shared reference data but the current Bazaar form does not source a local-community field; adding one without a source requirement would change behavior.

Displayed material is currently deliberately free text (`planned_material_description` and `actual_material_description`). There is no source-proven reporting requirement or existing product-category reference architecture. Therefore this audit does **not** add a product/material lookup. The examples مأكولات, منتجات منزلية, ملابس, إكسسوارات, حرف يدوية, منتجات زراعية, and أخرى remain candidates for a separately approved reporting requirement. Rental type, discount type, payment status, and workflow states remain constrained enums/status values rather than unjustified lookup tables.

## Migration split and dependency order

The staging-only combined migration was replaced by four migrations:

1. `000050` adds Bazaar applicability to shared target groups and execution-need configuration.
2. `000100` creates `bazaars`.
3. `000200` creates `bazaar_tables`, after its parent.
4. `000300` creates `bazaar_table_discounts`, after its parent.

Each rollback owns only the schema/configuration introduced by that migration. No migration earlier than `2026_09_10_000100_add_event_applicability_to_target_groups_table` was changed.

## Monthly Activities lifecycle traced from source

Monthly planning is created/edited/submitted by Relations branch actors. Submission enters the configured dynamic workflow; applicable workflow-step actors approve, return for changes, or reject, and notifications follow workflow handoffs. Returned planning becomes editable by the branch actor. After approval, the branch operational owner records post-execution actuals and submits them to the Supervisor. The Supervisor approves or returns post-execution. Approved results proceed to Follow-up, who verifies individual values rather than owning operational entry. Incorrect verification metadata notifies the activity creator. Once every verification is resolved, Follow-up submits the evaluation; the evaluated result is audited and Relations recipients are notified. Monthly status concepts include draft/returned planning, in-review workflow state, approved planning, post-execution submission/review, verification/evaluation, completion/closure; exact workflow approvers are configuration-driven rather than hard-coded.

The Bazaar planning decision remains permission-driven rather than being migrated to the dynamic workflow engine in this focused staging refinement. Replacing its already implemented single planning decision with a configurable multi-step engine would be a business-flow change without a Bazaar workflow configuration. This is recorded as a future configuration-backed enhancement, not silently invented here.

## Bazaar lifecycle before and after

Before this refinement, Bazaar planning, table actuals, discount decisions, and Follow-up verification were separated correctly. A mismatch existed at the last handoff: `bazaars.monitor` allowed Follow-up to choose `complete`, combining independent verification with final closure even though the permission catalogue already grants `bazaars.close` to Supervisor and the role guide assigns closure to Supervisor.

After refinement, Follow-up can only verify or return actuals. Verification moves `post_execution` to `verified`; return moves it to `executing`, allowing Relations to correct and resubmit the same actual records. Only `bazaars.close` can move `verified` to `completed`. Pending discounts continue to block verification, so Follow-up never treats an unresolved payment outcome as final.

| Stage | Monthly Activities | Bazaar Before | Bazaar After | Owner |
|---|---|---|---|---|
| Planning | Create/edit plan and shared target/need data | Create/edit Bazaar, targets, needs, and tables in `draft`/`returned` | Unchanged | Relations Officer |
| Submission | Submit into configured approval workflow | `draft`/`returned` → `submitted` | Unchanged | Relations Officer |
| Approval | Applicable configured workflow steps approve | Permission holder decides `submitted` → `approved` | Unchanged; no unproven workflow redesign | Configured approver / Supervisor |
| Return | Workflow returns editable plan to branch actor | `submitted` → `returned` | Unchanged | Approver → Relations Officer |
| Execution | Branch operational actor records execution | `approved` → `executing`; Relations owns per-table actuals | Unchanged | Relations Officer |
| Post-execution | Branch actor submits actuals; Monthly has Supervisor post-execution decision | `executing` → `post_execution` | Unchanged; pending discounts cannot become final | Relations Officer |
| Follow-up | Verify source values, then evaluate; do not own actual entry | Verify/return/**complete** | Verify or return only | Follow-up Officer |
| Correction | Source owner corrects returned actuals and resubmits | `post_execution` → `executing` → `post_execution` | Unchanged and explicitly retained | Relations Officer |
| Final completion | Monthly evaluation/closure follows its accepted owners | Follow-up could set `completed` | `verified` → `completed` requires `bazaars.close` | Supervisor / final authorized role |

## Action/status/permission matrix

| Role | Action | Before | After | Next owner | Permission |
|---|---|---|---|---|---|
| Relations | create/edit plan and tables | absent/`draft`/`returned` | `draft` | Relations | `bazaars.create`, `bazaars.edit` |
| Relations | submit planning | `draft`/`returned` | `submitted` | Approver | `bazaars.submit` |
| Approver | approve/return/reject planning | `submitted` | `approved`/`returned`/`rejected` | Relations when approved or returned | `bazaars.approve` |
| Relations | save execution | `approved`/`executing` | `executing` | Relations | `bazaars.post_execution` |
| Relations | request discount | execution save | pending request | Supervisor | `bazaars.post_execution` |
| Supervisor | approve/return/reject discount | `pending` | decision state | Relations | `bazaars.discount_review` |
| Relations | submit actuals | `approved`/`executing` | `post_execution` | Follow-up | `bazaars.post_execution` |
| Follow-up | verify | `post_execution` | `verified` | Supervisor | `bazaars.monitor` |
| Follow-up | return correction | `post_execution` | `executing` | Relations | `bazaars.monitor` |
| Supervisor | final close | `verified` | `completed` | closed | `bazaars.close` |

## Navigation, authorization, notification, and audit findings

The shared sidebar already places Bazaars beside Monthly Activities and Ramadan Iftars, uses a storefront icon, highlights all Bazaar routes, and is guarded by `bazaars.view`. Permission grants provide Relations create/edit/submit/execute/post-execution, Supervisor approve/discount-review/close, Follow-up monitor, and Super Admin all permissions. Route middleware remains authoritative; menu visibility grants no action.

The role guide now lists Bazaar pages/modules for Relations, Follow-up, and Supervisor and states that Follow-up neither edits actuals nor closes a Bazaar. The source currently has no Bazaar-specific notifications or audit-log writes. Adding ad-hoc controller notifications while planning approval remains outside the shared workflow would create a second, partial notification model; notification/audit alignment is therefore documented as incomplete pending adoption of configured workflow infrastructure. Existing timestamps, decision actors, and notes remain meaningful record-level traceability, but are not a substitute for immutable audit logs.
