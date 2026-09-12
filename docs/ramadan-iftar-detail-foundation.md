# Ramadan Iftar detail foundation

Phase 1.6 adds structured attendee, meal, meal-item, gift, and program-segment tables. Each row is exclusively owned through a direct `ramadan_iftar_id`; no polymorphic subject columns, Monthly Activity coupling, or JSON payloads are used.

- Attendee names, phone numbers, and ages are sensitive operational data. Future routes, exports, and masking must be policy-controlled. Phone numbers are deliberately not unique because household members may share one number.
- Meals retain unresolved `source_type` and nullable numeric `rating` fields without a source lookup, rating scale constraint, or evaluation subsystem. Values use decimal `(12, 2)` storage rather than floating point.
- Meal item types use stable `main`, `side`, `drink`, `dessert`, and `other` codes. Items and program segments use explicit `sort_order` values.
- Gifts keep supporting entities as optional free text and store unit/estimated-total values independently. No hidden total calculation is performed.
- Program segments use time-only schedule columns, optional internal or external executors, and local `planned`, `completed`, and `cancelled` status codes. Executor exclusivity is deferred to future request validation.
- Parent foreign keys cascade only on permanent deletion. Soft-deleting an Iftar leaves detail rows stored; force-deleting it removes owned details. Optional Target Group, Beneficiary Segment, and User references become null rather than deleting details.
- Conditional actual values, supporting-entity text, executor choice, check-in consistency, and aggregate count reconciliation are deferred to Ramadan CRUD/execution validation. No model events synchronize parent summaries.
- Shared execution teams, needs, supplies, monitoring, field verification, workflow, and controllers remain outside this phase.
