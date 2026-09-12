# Events Execution Needs normalization

## Production source audit

`execution_need_types` previously had eight seeded codes but no production reader and its old seeder was disabled. Monthly Activities remain authoritative through three separate legacy representations: `MonthlyActivity::EXECUTION_NEED_DEFINITIONS` for display, `config/execution_needs.php` for decision roles and center availability, and `execution_needs_payload` / `execution_needs_followup` JSON for planning details, decisions, and post-execution evidence. Agenda has no Execution Needs path.

| Business concept | DB seed | Config code | Monthly runtime code | Existing Arabic label | English label | Planning / availability / decision / follow-up semantics | Mapping |
|---|---|---|---|---|---|---|---|
| Volunteers | — | `volunteers` | `volunteers` | الحاجة للمتطوعين | Not present in repository | legacy column; center availability; volunteer coordinator decision; JSON follow-up | EXACT |
| Official correspondence | — | `official_correspondence` | `official_correspondence` | الحاجة للمخاطبة الرسمية | Not present | legacy column; forced unavailable; branch coordinator decision; JSON follow-up | EXACT |
| Media coverage | — | `media_coverage` | `media_coverage` | الحاجة لتغطية إعلامية | Not present | legacy column; center availability; communication head decision; JSON follow-up | EXACT |
| Supplies | — | `supplies` | `supplies` | الحاجة للمستلزمات | Not present | legacy relation; center availability; supervisor decision; JSON follow-up | EXACT |
| Official sponsorship | — | `official_sponsorship` | `official_sponsorship` | الحاجة لرعاية رسمية | Not present | `has_sponsor`; center availability; branch coordinator decision; JSON follow-up | EXACT |
| External partners | — | `external_partners` | `external_partners` | الحاجة لشركاء خارجيين | Not present | `has_partners`; center availability; branch coordinator decision; JSON follow-up | EXACT |
| Ceremony agenda | `ceremony` | — | `ceremony_agenda` | الحاجة لوجود أجندة حفل | Not present | JSON selection/details and availability; no config decision role; JSON follow-up | RENAMED_EQUIVALENT (`ceremony` → `ceremony_agenda`) |
| Transport | `transport` | `transport` | `transport` | الحاجة لتأمين مواصلات | Not present | JSON selection/details; center availability; transport/movement decision; JSON follow-up | EXACT |
| Maintenance workers | `maintenance` | `maintenance_workers` | `maintenance_workers` | الحاجة لعمال صيانة بالموقع | Not present | JSON selection/details; center availability; administration decision; JSON follow-up | RENAMED_EQUIVALENT |
| Gifts and shields | `gifts` | `gifts_shields` | `gifts_shields` | الحاجة لهدايا ودروع | Not present | JSON selection/details; center availability; branch coordinator decision; JSON follow-up | RENAMED_EQUIVALENT |
| Programs participation | `programs` | `programs_participation` | `programs_participation` | الحاجة لمشاركة البرامج | Not present | JSON selection/details; center availability; supervisor decision; JSON follow-up | RENAMED_EQUIVALENT |
| Certificates | `certificates` | part of `certificates_thanks` | part of `certificates_thanks` | الشهادات | Not present | one combined selection/decision, but distinct certificate detail and forced-unavailable payload section | SPLIT from runtime combined concept |
| Thanks letters | `thanks_letters` | part of `certificates_thanks` | part of `certificates_thanks` | كتب الشكر | Not present | one combined selection/decision, but distinct letter detail and forced-unavailable payload section | SPLIT from runtime combined concept |
| Invitations | `invitations` | `invitations` | `invitations` | الحاجة إلى بطاقات دعوة | Not present | JSON selection/details; forced unavailable; communication decision; JSON follow-up | EXACT |

The explicit compatibility map is code-based in `ExecutionNeedType::LEGACY_MAPPINGS`; unknown strings return no mapping. No labels, fuzzy matching, or string replacement participate. `certificates_thanks` deliberately maps to two canonical codes because the live form/payload keeps separate certificate and thanks-letter details. There are no forced `MERGED` or `NO_SAFE_MAPPING` outcomes in the known code set; unrecognized values remain unmapped rather than guessed.

## Canonical master and applicability

`execution_need_types` is now the canonical master. Canonical rows are identified independently of legacy/custom rows by `is_canonical`; applicability flags reserve future Monthly migration and limit Ramadan selection. The idempotent canonical seeder uses `updateOrCreate`, preserves matching IDs and descriptions, never deletes aliases/custom rows, and marks only evidence-backed Ramadan options selectable: volunteers, official correspondence, media coverage, supplies, transport, maintenance workers, gifts/shields, and invitations. Sponsorship/partners are already core planning concepts, while ceremony/programs have specialized Ramadan details and certificates/thanks are not confirmed Ramadan needs, so those rows remain canonical but are not Ramadan-selectable.

The config remains Monthly-only authorization/availability configuration. It is not the vocabulary store and is not changed by this normalization.

## Shared planning storage

`subject_execution_needs` stores one canonical need type per subject through the unique key `(subject_type, subject_id, execution_need_type_id)`. It has a restrictive lookup FK. Planning owns `is_required` and `planned_details`; `status` starts as `pending`, while `actual_details` and `completed_at` are reserved for later execution. No responsible user is stored because current production assigns decision responsibility to roles, not a planning-selected person; person assignment remains unresolved rather than invented.

Ramadan uses the controlled `ramadan_iftar` alias and a constrained `executionNeeds()` relationship. Its form submits the canonical lookup ID, required toggle, and planning details. Validation requires a unique, active, canonical, Ramadan-applicable type. Persistence filters unselected rows and synchronizes selected rows inside the existing aggregate transaction. IDs are checked through the constrained parent relationship before update/delete; subject identity and execution fields are server-owned.

## Monthly compatibility and future migration

Monthly Activities are unchanged: definitions, config, columns, relationships, JSON payloads, decisions, follow-up, views, controllers, reports, and notifications remain authoritative. There is no Monthly `executionNeeds()` relationship, read, backfill, or dual write.

A later migration must read each legacy source, apply only `LEGACY_MAPPINGS`, explicitly split `certificates_thanks`, backfill Common rows idempotently, compare old/new reads, and cut over behind independently controlled rollout steps. Legacy storage must be removed only after compatibility is proven.
